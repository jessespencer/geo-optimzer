<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Robots {

    public function init(): void {
        add_filter( 'robots_txt', array( $this, 'modify_robots_txt' ), 100, 2 );
    }

    public function modify_robots_txt( string $output, bool $public ): string {
        if ( ! $public ) {
            return $output;
        }

        $bot_rules = $this->build_bot_rules();
        if ( ! empty( $bot_rules ) ) {
            $output .= "\n# GEO Optimizer - AI Bot Rules\n";
            $output .= $bot_rules;
        }

        $settings = get_option( 'geo_opt_settings', array() );
        if ( ! empty( $settings['sitemap_enabled'] ) ) {
            $output .= "\n# GEO Optimizer - AI Sitemap\n";
            $output .= 'Sitemap: ' . esc_url( home_url( '/ai-sitemap.xml' ) ) . "\n";
        }

        return $output;
    }

    public function build_bot_rules(): string {
        $settings = get_option( 'geo_opt_settings', array() );
        $bots     = $settings['robots_bots'] ?? array( 'GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended' );
        $mode     = $settings['robots_mode'] ?? 'allow';

        if ( empty( $bots ) ) {
            return '';
        }

        $directive = $mode === 'block' ? 'Disallow' : 'Allow';
        $rules     = '';

        foreach ( $bots as $bot ) {
            $bot = trim( $bot );
            if ( $bot === '' ) {
                continue;
            }
            $rules .= 'User-agent: ' . $bot . "\n";
            $rules .= $directive . ': /' . "\n\n";
        }

        return $rules;
    }
}
