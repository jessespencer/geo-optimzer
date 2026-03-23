<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Scoring {

    public function init(): void {
        add_action( 'wp_ajax_geo_opt_calculate_score', array( $this, 'ajax_calculate_score' ) );
        add_action( 'save_post', array( $this, 'update_score_on_save' ), 20, 2 );
    }

    public function ajax_calculate_score(): void {
        check_ajax_referer( 'geo_opt_score_nonce', 'nonce' );

        $post_id = absint( $_POST['post_id'] ?? 0 );
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( 'Invalid post.' );
        }

        $result = $this->calculate_score( $post_id );
        wp_send_json_success( $result );
    }

    public function update_score_on_save( int $post_id, WP_Post $post ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $allowed = array( 'post', 'page', 'product' );
        if ( ! in_array( $post->post_type, $allowed, true ) ) {
            return;
        }

        $result = $this->calculate_score( $post_id );
        update_post_meta( $post_id, '_geo_opt_score', $result['score'] );
        update_post_meta( $post_id, '_geo_opt_score_breakdown', $result['breakdown'] );
    }

    public function calculate_score( int $post_id ): array {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array( 'score' => 0, 'breakdown' => array(), 'suggestions' => array() );
        }

        $content = apply_filters( 'the_content', $post->post_content );
        $content = do_blocks( $post->post_content );
        $content = wpautop( $content );

        $breakdown = array(
            'direct_answers'    => $this->score_direct_answers( $content ),
            'question_headings' => $this->score_question_headings( $content ),
            'reading_level'     => $this->score_reading_level( $content ),
            'entity_clarity'    => $this->score_entity_clarity( $content, $post_id ),
            'content_length'    => $this->score_content_length( $content ),
        );

        $score = array_sum( $breakdown );
        $suggestions = $this->generate_suggestions( $breakdown, $content, $post_id );

        update_post_meta( $post_id, '_geo_opt_score', $score );
        update_post_meta( $post_id, '_geo_opt_score_breakdown', $breakdown );

        return array(
            'score'       => $score,
            'breakdown'   => $breakdown,
            'suggestions' => $suggestions,
        );
    }

    public function score_direct_answers( string $content ): int {
        $text = wp_strip_all_tags( $content );
        $words = preg_split( '/\s+/', $text );
        $first_300 = implode( ' ', array_slice( $words, 0, 300 ) );

        $sentences = preg_split( '/(?<=[.!?])\s+/', $first_300, -1, PREG_SPLIT_NO_EMPTY );
        $count = 0;

        $patterns = array(
            '/^[\w\s]+ (?:is|are|was|were|means|refers to) /i',
            '/^(?:the answer|this means|in short|to summarize|essentially|in other words|put simply)/i',
            '/^(?:yes|no),?\s/i',
        );

        foreach ( $sentences as $sentence ) {
            foreach ( $patterns as $pattern ) {
                if ( preg_match( $pattern, trim( $sentence ) ) ) {
                    $count++;
                    break;
                }
            }
        }

        // Check for sentences right after a question
        $all_text = wp_strip_all_tags( $content );
        $all_sentences = preg_split( '/(?<=[.!?])\s+/', $all_text, -1, PREG_SPLIT_NO_EMPTY );
        $prev_was_question = false;
        foreach ( $all_sentences as $sentence ) {
            if ( $prev_was_question ) {
                $count++;
                $prev_was_question = false;
                continue;
            }
            $prev_was_question = str_ends_with( trim( $sentence ), '?' );
        }

        return match ( true ) {
            $count >= 3 => 20,
            $count >= 2 => 14,
            $count >= 1 => 8,
            default     => 0,
        };
    }

    public function score_question_headings( string $content ): int {
        preg_match_all( '/<h[23][^>]*>(.+?)<\/h[23]>/i', $content, $matches );

        $count = 0;
        if ( ! empty( $matches[1] ) ) {
            foreach ( $matches[1] as $heading ) {
                $heading_text = wp_strip_all_tags( $heading );
                if ( str_ends_with( trim( $heading_text ), '?' ) ) {
                    $count++;
                }
            }
        }

        return match ( true ) {
            $count >= 4 => 20,
            $count >= 3 => 15,
            $count >= 2 => 10,
            $count >= 1 => 5,
            default     => 0,
        };
    }

    public function score_reading_level( string $content ): int {
        $text = wp_strip_all_tags( $content );
        $text = trim( $text );

        if ( empty( $text ) ) {
            return 0;
        }

        $sentences = preg_split( '/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY );
        $sentence_count = max( count( $sentences ), 1 );

        $words = preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );
        $word_count = max( count( $words ), 1 );

        $syllable_count = 0;
        foreach ( $words as $word ) {
            $syllable_count += $this->count_syllables( $word );
        }

        $grade = 0.39 * ( $word_count / $sentence_count ) + 11.8 * ( $syllable_count / $word_count ) - 15.59;
        $grade = round( $grade, 1 );

        return match ( true ) {
            $grade >= 6 && $grade <= 10   => 20,
            $grade == 5 || $grade == 11   => 15,
            $grade == 4 || $grade == 12   => 10,
            $grade == 3 || $grade == 13   => 5,
            default                       => 0,
        };
    }

    public function score_entity_clarity( string $content, int $post_id ): int {
        $primary_entity  = get_post_meta( $post_id, '_geo_opt_primary_entity', true );
        $target_question = get_post_meta( $post_id, '_geo_opt_target_question', true );
        $post            = get_post( $post_id );
        $title           = $post ? $post->post_title : '';
        $text            = wp_strip_all_tags( $content );

        // Get first paragraph
        $paragraphs = preg_split( '/\n\n|\r\n\r\n/', $text, 2 );
        $first_para = trim( $paragraphs[0] ?? '' );

        if ( ! empty( $primary_entity ) || ! empty( $target_question ) ) {
            $score = 0;

            if ( ! empty( $primary_entity ) ) {
                $score += 6;
                if ( stripos( $title, $primary_entity ) !== false ) {
                    $score += 4;
                }
                if ( stripos( $first_para, $primary_entity ) !== false ) {
                    $score += 4;
                }
            }

            if ( ! empty( $target_question ) ) {
                $score += 6;
            }

            return min( $score, 20 );
        }

        // Fallback: analyze title words in first paragraph
        $title_words = preg_split( '/\s+/', strtolower( $title ) );
        $title_words = array_filter( $title_words, function ( string $word ): bool {
            return strlen( $word ) > 3;
        } );

        if ( empty( $title_words ) ) {
            return 0;
        }

        $first_para_lower = strtolower( $first_para );
        $mentions = 0;
        foreach ( $title_words as $word ) {
            $mentions += substr_count( $first_para_lower, $word );
        }

        return $mentions >= 3 ? 10 : 0;
    }

    public function score_content_length( string $content ): int {
        $text = wp_strip_all_tags( $content );
        $word_count = str_word_count( $text );

        return match ( true ) {
            $word_count >= 2000 => 20,
            $word_count >= 1000 => 15,
            $word_count >= 600  => 10,
            $word_count >= 300  => 5,
            default             => 0,
        };
    }

    public function generate_suggestions( array $breakdown, string $content, int $post_id ): array {
        $suggestions = array();

        if ( $breakdown['direct_answers'] < 20 ) {
            $suggestions[] = 'Add more direct, concise answers to common questions in your opening paragraphs. Use sentence patterns like "X is..." or "The answer is...".';
        }

        if ( $breakdown['question_headings'] < 20 ) {
            $suggestions[] = 'Use question-based headings (H2/H3 ending with ?) to improve discoverability by AI engines. Aim for at least 4 question headings.';
        }

        if ( $breakdown['reading_level'] < 20 ) {
            $text = wp_strip_all_tags( $content );
            $sentences = preg_split( '/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY );
            $sentence_count = max( count( $sentences ), 1 );
            $words = preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );
            $word_count = max( count( $words ), 1 );
            $syllables = 0;
            foreach ( $words as $w ) {
                $syllables += $this->count_syllables( $w );
            }
            $grade = round( 0.39 * ( $word_count / $sentence_count ) + 11.8 * ( $syllables / $word_count ) - 15.59, 1 );
            $suggestions[] = sprintf( 'Adjust reading level to grade 6–10 for optimal AI comprehension. Current: grade %s.', $grade );
        }

        if ( $breakdown['entity_clarity'] < 20 ) {
            $primary_entity = get_post_meta( $post_id, '_geo_opt_primary_entity', true );
            if ( empty( $primary_entity ) ) {
                $suggestions[] = 'Fill in the Primary Entity and Target Question fields to improve entity clarity scoring.';
            } else {
                $suggestions[] = 'Ensure your primary entity appears in both the post title and the first paragraph.';
            }
        }

        if ( $breakdown['content_length'] < 20 ) {
            $word_count = str_word_count( wp_strip_all_tags( $content ) );
            $suggestions[] = sprintf( 'Increase content length. Current: %d words. Aim for 2,000+ words for best GEO performance.', $word_count );
        }

        return $suggestions;
    }

    private function count_syllables( string $word ): int {
        $word = strtolower( trim( $word ) );
        $word = preg_replace( '/[^a-z]/', '', $word );

        if ( strlen( $word ) <= 2 ) {
            return 1;
        }

        // Remove silent e at end
        if ( str_ends_with( $word, 'e' ) && ! str_ends_with( $word, 'le' ) ) {
            $word = substr( $word, 0, -1 );
        }

        // Count vowel groups
        preg_match_all( '/[aeiouy]+/', $word, $matches );
        $count = count( $matches[0] );

        // Adjust for common patterns
        if ( preg_match( '/(ia|io|iu|ua|uo|ue(?!d$))/', $word ) ) {
            $count++;
        }

        return max( $count, 1 );
    }
}
