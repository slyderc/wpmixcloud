<?php
/**
 * WP Mixcloud Archives Uninstall Script
 * 
 * This file is executed when the plugin is uninstalled via WordPress admin.
 * It completely removes all plugin data from the database.
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access and ensure this is a legitimate uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all plugin options
 */
function wp_mixcloud_archives_remove_options() {
    delete_option('wp_mixcloud_archives_options');
    delete_option('wp_mixcloud_archives_activated');
    delete_option('wp_mixcloud_archives_deactivated');
    delete_option('wp_mixcloud_archives_version');
}

/**
 * Remove all plugin transients
 */
function wp_mixcloud_archives_remove_transients() {
    global $wpdb;
    
    // AIDEV-NOTE: Remove all plugin-related transients including timeouts
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_mixcloud%' 
         OR option_name LIKE '_transient_timeout_mixcloud%'"
    );
}

/**
 * Remove scheduled events
 */
function wp_mixcloud_archives_remove_scheduled_events() {
    wp_clear_scheduled_hook('wp_mixcloud_archives_cleanup_cache');
    wp_clear_scheduled_hook('wp_mixcloud_archives_warm_cache');
}

/**
 * Remove user meta data related to plugin
 */
function wp_mixcloud_archives_remove_user_meta() {
    global $wpdb;
    
    // AIDEV-NOTE: Remove any user meta data that might have been stored by the plugin
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} 
             WHERE meta_key LIKE %s",
            'wp_mixcloud_archives_%'
        )
    );
}

/**
 * Clean up any temporary files or directories
 */
function wp_mixcloud_archives_cleanup_files() {
    $upload_dir = wp_upload_dir();
    $plugin_cache_dir = $upload_dir['basedir'] . '/mixcloud-archives';
    
    // AIDEV-NOTE: Remove cache directory if it exists (though we don't actually use it)
    if (is_dir($plugin_cache_dir)) {
        wp_delete_file_from_directory($plugin_cache_dir, true);
        rmdir($plugin_cache_dir);
    }
}

/**
 * Log uninstall for debugging (if enabled)
 */
function wp_mixcloud_archives_log_uninstall() {
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('WP Mixcloud Archives completely uninstalled at ' . current_time('mysql'));
    }
}

// AIDEV-NOTE: Execute uninstall procedures in logical order
wp_mixcloud_archives_remove_scheduled_events();
wp_mixcloud_archives_remove_transients();
wp_mixcloud_archives_remove_user_meta();
wp_mixcloud_archives_cleanup_files();
wp_mixcloud_archives_remove_options(); // Remove options last for debugging
wp_mixcloud_archives_log_uninstall();