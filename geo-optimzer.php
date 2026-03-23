<?php
/**
 * Plugin Name: GEO Optimizer
 * Plugin URI: https://www.jessedestroys.com/geo-optimizer
 * Description: Optimize your content for AI search engines and generative engine optimization (GEO). Schema markup, content scoring, AI snippet optimization, and AI bot crawlability management.
 * Version: 1.0.0
 * Author: GEO Optimizer
 * Author URI: https://www.jessedestroys.com
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: geo-optimzer
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GEO_OPT_VERSION', '1.0.0' );
define( 'GEO_OPT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEO_OPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GEO_OPT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-activator.php';

register_activation_hook( __FILE__, array( 'Geo_Opt_Activator', 'activate' ) );

add_action( 'plugins_loaded', 'geo_opt_init', 20 );
add_action( 'init', 'geo_opt_register_rewrites' );
add_filter( 'query_vars', 'geo_opt_query_vars' );
add_action( 'admin_enqueue_scripts', 'geo_opt_admin_assets' );
add_action( 'admin_notices', array( 'Geo_Opt_Activator', 'admin_notice_requirements' ) );

function geo_opt_init(): void {
    require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-settings.php';
    require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-scoring.php';
    require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-metabox.php';
    require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-schema.php';
    require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-woocommerce.php';
    require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-snippet.php';
    require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-sitemap.php';
    require_once GEO_OPT_PLUGIN_DIR . 'includes/class-geo-opt-robots.php';

    $settings = get_option( 'geo_opt_settings', array() );

    ( new Geo_Opt_Settings() )->init();

    if ( ! empty( $settings['scoring_enabled'] ) ) {
        ( new Geo_Opt_Scoring() )->init();
    }

    ( new Geo_Opt_Metabox() )->init();

    if ( ! empty( $settings['schema_enabled'] ) ) {
        ( new Geo_Opt_Schema() )->init();
        if ( ! empty( $settings['woocommerce_schema'] ) ) {
            ( new Geo_Opt_Woocommerce() )->init();
        }
    }

    if ( ! empty( $settings['snippet_enabled'] ) ) {
        ( new Geo_Opt_Snippet() )->init();
    }

    if ( ! empty( $settings['sitemap_enabled'] ) ) {
        ( new Geo_Opt_Sitemap() )->init();
    }

    ( new Geo_Opt_Robots() )->init();
}

function geo_opt_register_rewrites(): void {
    Geo_Opt_Sitemap::register_rewrite_rules();
}

function geo_opt_query_vars( array $vars ): array {
    $vars[] = 'geo_opt_ai_sitemap';
    return $vars;
}

function geo_opt_admin_assets( string $hook ): void {
    $screen = get_current_screen();
    $allowed_screens = array( 'post', 'page', 'product' );
    $is_editor = $screen && in_array( $screen->base, array( 'post' ), true ) && in_array( $screen->post_type, $allowed_screens, true );
    $is_settings = $hook === 'toplevel_page_geo-optimzer';

    if ( ! $is_editor && ! $is_settings ) {
        return;
    }

    wp_enqueue_style(
        'geo-opt-admin',
        GEO_OPT_PLUGIN_URL . 'admin/css/geo-opt-admin.css',
        array(),
        GEO_OPT_VERSION
    );

    if ( $is_settings ) {
        wp_enqueue_script(
            'geo-opt-admin',
            GEO_OPT_PLUGIN_URL . 'admin/js/geo-opt-admin.js',
            array(),
            GEO_OPT_VERSION,
            true
        );
    }

    if ( $is_editor ) {
        wp_enqueue_script(
            'geo-opt-scoring',
            GEO_OPT_PLUGIN_URL . 'admin/js/geo-opt-scoring.js',
            array(),
            GEO_OPT_VERSION,
            true
        );
        wp_localize_script( 'geo-opt-scoring', 'geoOptAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'geo_opt_score_nonce' ),
        ) );
    }
}
