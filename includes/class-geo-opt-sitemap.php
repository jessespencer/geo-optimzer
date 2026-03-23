<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Sitemap {

    public function init(): void {
        add_action( 'template_redirect', array( $this, 'render_sitemap' ) );
    }

    public static function register_rewrite_rules(): void {
        add_rewrite_rule( '^ai-sitemap\.xml$', 'index.php?geo_opt_ai_sitemap=1', 'top' );
    }

    public function render_sitemap(): void {
        if ( ! get_query_var( 'geo_opt_ai_sitemap' ) ) {
            return;
        }

        $settings  = get_option( 'geo_opt_settings', array() );
        $min_score = (int) ( $settings['sitemap_min_score'] ?? 50 );

        $posts = $this->get_scored_posts( $min_score );
        $xml   = $this->build_sitemap_xml( $posts );

        status_header( 200 );
        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex' );
        echo $xml;
        exit;
    }

    public function get_scored_posts( int $min_score ): array {
        $query = new WP_Query( array(
            'post_type'      => array( 'post', 'page', 'product' ),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_geo_opt_score',
            'meta_value_num' => $min_score,
            'meta_compare'   => '>=',
            'meta_type'      => 'NUMERIC',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ) );

        return $query->posts;
    }

    public function build_sitemap_xml( array $posts ): string {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:geo="http://geo-optimizer.com/schema/1.0">' . "\n";

        foreach ( $posts as $post ) {
            $score           = (int) get_post_meta( $post->ID, '_geo_opt_score', true );
            $primary_entity  = get_post_meta( $post->ID, '_geo_opt_primary_entity', true );
            $target_question = get_post_meta( $post->ID, '_geo_opt_target_question', true );

            $schema_class = new Geo_Opt_Schema();
            $schema_type  = $schema_class->get_schema_type( $post->ID );

            $priority = number_format( $score / 100, 2 );
            $lastmod  = get_the_modified_date( 'c', $post );

            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . esc_url( get_permalink( $post ) ) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . esc_html( $lastmod ) . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>' . esc_html( $priority ) . '</priority>' . "\n";
            $xml .= '    <geo:score>' . esc_html( (string) $score ) . '</geo:score>' . "\n";

            if ( ! empty( $primary_entity ) ) {
                $xml .= '    <geo:entity>' . esc_html( $primary_entity ) . '</geo:entity>' . "\n";
            }

            $xml .= '    <geo:schema-type>' . esc_html( $schema_type ) . '</geo:schema-type>' . "\n";

            if ( ! empty( $target_question ) ) {
                $xml .= '    <geo:target-question>' . esc_html( $target_question ) . '</geo:target-question>' . "\n";
            }

            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }
}
