<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$settings = get_option( 'geo_opt_settings', array() );

if ( ! empty( $settings['delete_data_on_uninstall'] ) ) {
    global $wpdb;

    // Delete all plugin options
    delete_option( 'geo_opt_settings' );
    delete_option( 'geo_opt_version' );
    delete_option( 'geo_opt_license_key' );

    // Delete all post meta with _geo_opt_ prefix
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_geo\_opt\_%'" );

    // Delete transients
    delete_transient( 'geo_opt_activation_error' );
}

// Always flush rewrite rules on uninstall to clean up the ai-sitemap rule
flush_rewrite_rules();
