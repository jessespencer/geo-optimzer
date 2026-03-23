<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Schema {

    public function init(): void {
        add_action( 'wp_head', array( $this, 'inject_schema' ), 1 );
    }

    public function inject_schema(): void {
        if ( ! is_singular() ) {
            return;
        }

        $post = get_post();
        if ( ! $post ) {
            return;
        }

        if ( apply_filters( 'geo_opt_skip_base_schema', false, $post->ID ) ) {
            return;
        }

        $schema_type = $this->get_schema_type( $post->ID );
        $schema      = match ( $schema_type ) {
            'FAQPage' => $this->build_faq_schema( $post ),
            'HowTo'   => $this->build_howto_schema( $post ),
            'Product' => $this->build_product_schema( $post ),
            default   => $this->build_article_schema( $post ),
        };

        if ( empty( $schema ) ) {
            return;
        }

        echo "\n" . '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        echo "\n" . '</script>' . "\n";
    }

    public function get_schema_type( int $post_id ): string {
        $override = get_post_meta( $post_id, '_geo_opt_schema_type', true );
        if ( ! empty( $override ) ) {
            return $override;
        }

        $post_type = get_post_type( $post_id );
        if ( $post_type === 'product' ) {
            return 'Product';
        }

        $settings = get_option( 'geo_opt_settings', array() );
        return $settings['schema_default_type'] ?? 'Article';
    }

    public function build_article_schema( WP_Post $post ): array {
        $content   = apply_filters( 'the_content', $post->post_content );
        $text      = wp_strip_all_tags( $content );
        $words     = preg_split( '/\s+/', $text, 201 );
        $excerpt   = $post->post_excerpt ?: wp_trim_words( $text, 55, '' );

        $schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'Article',
            'headline'        => $post->post_title,
            'description'     => $excerpt,
            'datePublished'   => get_the_date( 'c', $post ),
            'dateModified'    => get_the_modified_date( 'c', $post ),
            'author'          => $this->get_author_schema( $post ),
            'publisher'       => $this->get_publisher_schema(),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id'   => get_permalink( $post ),
            ),
            'wordCount'       => str_word_count( $text ),
            'articleBody'     => implode( ' ', array_slice( $words, 0, 200 ) ),
        );

        $image = $this->get_image_schema( $post->ID );
        if ( $image ) {
            $schema['image'] = $image;
        }

        return $schema;
    }

    public function build_faq_schema( WP_Post $post ): array {
        $content = do_blocks( $post->post_content );
        $content = wpautop( $content );

        // Parse question headings (h2/h3 ending with ?)
        $questions = array();
        $parts = preg_split( '/(<h[23][^>]*>.+?<\/h[23]>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

        $current_question = null;
        $current_answer   = '';

        foreach ( $parts as $part ) {
            if ( preg_match( '/<h[23][^>]*>(.+?)<\/h[23]>/i', $part, $match ) ) {
                // Save previous question/answer pair
                if ( $current_question !== null && trim( $current_answer ) !== '' ) {
                    $questions[] = array(
                        '@type' => 'Question',
                        'name'  => $current_question,
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text'  => trim( wp_strip_all_tags( $current_answer ) ),
                        ),
                    );
                }

                $heading_text = wp_strip_all_tags( $match[1] );
                if ( str_ends_with( trim( $heading_text ), '?' ) ) {
                    $current_question = trim( $heading_text );
                    $current_answer   = '';
                } else {
                    $current_question = null;
                    $current_answer   = '';
                }
            } elseif ( $current_question !== null ) {
                $current_answer .= $part;
            }
        }

        // Save last question/answer pair
        if ( $current_question !== null && trim( $current_answer ) !== '' ) {
            $questions[] = array(
                '@type' => 'Question',
                'name'  => $current_question,
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => trim( wp_strip_all_tags( $current_answer ) ),
                ),
            );
        }

        if ( empty( $questions ) ) {
            return $this->build_article_schema( $post );
        }

        return array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $questions,
        );
    }

    public function build_howto_schema( WP_Post $post ): array {
        $content = do_blocks( $post->post_content );
        $content = wpautop( $content );

        $steps = array();
        $parts = preg_split( '/(<h[23][^>]*>.+?<\/h[23]>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

        $current_step_name = null;
        $current_step_text = '';

        foreach ( $parts as $part ) {
            if ( preg_match( '/<h[23][^>]*>(.+?)<\/h[23]>/i', $part, $match ) ) {
                if ( $current_step_name !== null && trim( $current_step_text ) !== '' ) {
                    $steps[] = array(
                        '@type' => 'HowToStep',
                        'name'  => $current_step_name,
                        'text'  => trim( wp_strip_all_tags( $current_step_text ) ),
                    );
                }
                $current_step_name = trim( wp_strip_all_tags( $match[1] ) );
                $current_step_text = '';
            } elseif ( $current_step_name !== null ) {
                $current_step_text .= $part;
            }
        }

        if ( $current_step_name !== null && trim( $current_step_text ) !== '' ) {
            $steps[] = array(
                '@type' => 'HowToStep',
                'name'  => $current_step_name,
                'text'  => trim( wp_strip_all_tags( $current_step_text ) ),
            );
        }

        if ( empty( $steps ) ) {
            return $this->build_article_schema( $post );
        }

        $excerpt = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $content ), 55, '' );

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'HowTo',
            'name'        => $post->post_title,
            'description' => $excerpt,
            'step'        => $steps,
        );

        $image = $this->get_image_schema( $post->ID );
        if ( $image ) {
            $schema['image'] = $image;
        }

        return $schema;
    }

    public function build_product_schema( WP_Post $post ): array {
        $excerpt = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 55, '' );

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $post->post_title,
            'description' => $excerpt,
        );

        $image = $this->get_image_schema( $post->ID );
        if ( $image ) {
            $schema['image'] = $image;
        }

        return $schema;
    }

    private function get_author_schema( WP_Post $post ): array {
        $author_id = $post->post_author;
        return array(
            '@type' => 'Person',
            'name'  => get_the_author_meta( 'display_name', $author_id ),
            'url'   => get_author_posts_url( $author_id ),
        );
    }

    private function get_publisher_schema(): array {
        $schema = array(
            '@type' => 'Organization',
            'name'  => get_bloginfo( 'name' ),
        );

        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( $custom_logo_id ) {
            $logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
            if ( $logo_url ) {
                $schema['logo'] = array(
                    '@type' => 'ImageObject',
                    'url'   => $logo_url,
                );
            }
        } else {
            $site_icon_id = get_option( 'site_icon' );
            if ( $site_icon_id ) {
                $icon_url = wp_get_attachment_image_url( $site_icon_id, 'full' );
                if ( $icon_url ) {
                    $schema['logo'] = array(
                        '@type' => 'ImageObject',
                        'url'   => $icon_url,
                    );
                }
            }
        }

        return $schema;
    }

    private function get_image_schema( int $post_id ): ?array {
        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( ! $thumb_id ) {
            return null;
        }

        $image_url  = wp_get_attachment_image_url( $thumb_id, 'full' );
        $image_meta = wp_get_attachment_metadata( $thumb_id );

        if ( ! $image_url ) {
            return null;
        }

        $schema = array(
            '@type' => 'ImageObject',
            'url'   => $image_url,
        );

        if ( ! empty( $image_meta['width'] ) ) {
            $schema['width'] = $image_meta['width'];
        }
        if ( ! empty( $image_meta['height'] ) ) {
            $schema['height'] = $image_meta['height'];
        }

        return $schema;
    }
}
