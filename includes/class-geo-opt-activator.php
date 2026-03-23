<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Activator {

    private const REQUIRED_PHP = '8.0';
    private const REQUIRED_WP  = '6.0';

    public static function activate(): void {
        if ( version_compare( PHP_VERSION, self::REQUIRED_PHP, '<' ) ) {
            set_transient( 'geo_opt_activation_error', sprintf(
                'GEO Optimizer requires PHP %s or higher. You are running PHP %s.',
                self::REQUIRED_PHP,
                PHP_VERSION
            ), 60 );
            deactivate_plugins( plugin_basename( dirname( __DIR__ ) . '/geo-optimzer.php' ) );
            return;
        }

        $wp_version = get_bloginfo( 'version' );
        if ( version_compare( $wp_version, self::REQUIRED_WP, '<' ) ) {
            set_transient( 'geo_opt_activation_error', sprintf(
                'GEO Optimizer requires WordPress %s or higher. You are running WordPress %s.',
                self::REQUIRED_WP,
                $wp_version
            ), 60 );
            deactivate_plugins( plugin_basename( dirname( __DIR__ ) . '/geo-optimzer.php' ) );
            return;
        }

        $defaults = array(
            'schema_enabled'           => true,
            'schema_default_type'      => 'Article',
            'scoring_enabled'          => true,
            'snippet_enabled'          => true,
            'sitemap_enabled'          => true,
            'sitemap_min_score'        => 50,
            'robots_bots'              => array( 'GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended' ),
            'robots_mode'              => 'allow',
            'woocommerce_schema'       => true,
            'delete_data_on_uninstall' => false,
        );

        add_option( 'geo_opt_settings', $defaults );
        add_option( 'geo_opt_version', GEO_OPT_VERSION );

        require_once dirname( __DIR__ ) . '/includes/class-geo-opt-sitemap.php';
        Geo_Opt_Sitemap::register_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function admin_notice_requirements(): void {
        $error = get_transient( 'geo_opt_activation_error' );
        if ( ! $error ) {
            return;
        }
        delete_transient( 'geo_opt_activation_error' );
        echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
    }
}
