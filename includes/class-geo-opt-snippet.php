<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Snippet {

    public function init(): void {
        add_action( 'wp_head', array( $this, 'inject_meta_tags' ), 5 );
        add_filter( 'the_content', array( $this, 'inject_hidden_div' ), 999 );
    }

    public function inject_meta_tags(): void {
        if ( ! is_singular() ) {
            return;
        }

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        $ai_summary      = get_post_meta( $post_id, '_geo_opt_ai_summary', true );
        $primary_entity  = get_post_meta( $post_id, '_geo_opt_primary_entity', true );
        $target_question = get_post_meta( $post_id, '_geo_opt_target_question', true );

        if ( ! empty( $ai_summary ) && ! $this->rankmath_owns_field( 'ai-summary', $post_id ) ) {
            echo '<meta name="geo-opt:ai-summary" content="' . esc_attr( $ai_summary ) . '" />' . "\n";
        }

        if ( ! empty( $primary_entity ) ) {
            echo '<meta name="geo-opt:primary-entity" content="' . esc_attr( $primary_entity ) . '" />' . "\n";
        }

        if ( ! empty( $target_question ) ) {
            echo '<meta name="geo-opt:target-question" content="' . esc_attr( $target_question ) . '" />' . "\n";
        }
    }

    public function inject_hidden_div( string $content ): string {
        if ( ! is_singular() ) {
            return $content;
        }

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return $content;
        }

        $ai_summary      = get_post_meta( $post_id, '_geo_opt_ai_summary', true );
        $primary_entity  = get_post_meta( $post_id, '_geo_opt_primary_entity', true );
        $target_question = get_post_meta( $post_id, '_geo_opt_target_question', true );
        $score           = get_post_meta( $post_id, '_geo_opt_score', true );

        $schema_class = new Geo_Opt_Schema();
        $schema_type  = $schema_class->get_schema_type( $post_id );

        // Only inject if at least one field has data
        if ( empty( $ai_summary ) && empty( $primary_entity ) && empty( $target_question ) ) {
            return $content;
        }

        $div = '<div class="geo-opt-ai-context" style="display:none;" aria-hidden="true" data-purpose="ai-context">' . "\n";
        $div .= '<dl>' . "\n";

        if ( ! empty( $primary_entity ) ) {
            $div .= '<dt>Primary Entity</dt>' . "\n";
            $div .= '<dd>' . esc_html( $primary_entity ) . '</dd>' . "\n";
        }

        if ( ! empty( $target_question ) ) {
            $div .= '<dt>Target Question</dt>' . "\n";
            $div .= '<dd>' . esc_html( $target_question ) . '</dd>' . "\n";
        }

        if ( ! empty( $ai_summary ) ) {
            $div .= '<dt>AI Summary</dt>' . "\n";
            $div .= '<dd>' . esc_html( $ai_summary ) . '</dd>' . "\n";
        }

        $div .= '<dt>Content Type</dt>' . "\n";
        $div .= '<dd>' . esc_html( $schema_type ) . '</dd>' . "\n";

        if ( $score !== '' && $score !== false ) {
            $div .= '<dt>GEO Score</dt>' . "\n";
            $div .= '<dd>' . esc_html( $score ) . '/100</dd>' . "\n";
        }

        $div .= '</dl>' . "\n";
        $div .= '</div>';

        return $content . "\n" . $div;
    }

    public function rankmath_owns_field( string $field, int $post_id ): bool {
        if ( ! class_exists( 'RankMath' ) ) {
            return false;
        }

        // For ai-summary, check if RankMath has a custom description set for this post
        if ( $field === 'ai-summary' ) {
            $rm_description = get_post_meta( $post_id, 'rank_math_description', true );
            return ! empty( $rm_description );
        }

        return false;
    }
}
