<?php
/**
 * Plugin Name: WP Mixcloud Archives
 * Plugin URI: https://github.com/slyderc/wpmixcloud
 * Description: A WordPress plugin to display Mixcloud archives with embedded players, supporting date filtering, pagination, and social sharing.
 * Version: 1.0.0
 * Author: Now Wave Radio, LLC
 * Author URI: https://nowwave.radio
 * License: MIT with Attribution
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: wp-mixcloud-archives
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access to plugin file for security
if (!defined('ABSPATH')) {
    exit;
}

// AIDEV-NOTE: Define plugin constants for paths and URLs
define('WP_MIXCLOUD_ARCHIVES_VERSION', '1.0.0');
define('WP_MIXCLOUD_ARCHIVES_PLUGIN_FILE', __FILE__);
define('WP_MIXCLOUD_ARCHIVES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_MIXCLOUD_ARCHIVES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_MIXCLOUD_ARCHIVES_INCLUDES_DIR', WP_MIXCLOUD_ARCHIVES_PLUGIN_DIR . 'includes/');
define('WP_MIXCLOUD_ARCHIVES_ADMIN_DIR', WP_MIXCLOUD_ARCHIVES_PLUGIN_DIR . 'admin/');
define('WP_MIXCLOUD_ARCHIVES_ASSETS_DIR', WP_MIXCLOUD_ARCHIVES_PLUGIN_DIR . 'assets/');
define('WP_MIXCLOUD_ARCHIVES_TEMPLATES_DIR', WP_MIXCLOUD_ARCHIVES_PLUGIN_DIR . 'templates/');

// AIDEV-NOTE: Include required classes
require_once WP_MIXCLOUD_ARCHIVES_INCLUDES_DIR . 'class-mixcloud-api.php';
require_once WP_MIXCLOUD_ARCHIVES_INCLUDES_DIR . 'class-shortcode-handler.php';
require_once WP_MIXCLOUD_ARCHIVES_INCLUDES_DIR . 'class-ajax-handler.php';
require_once WP_MIXCLOUD_ARCHIVES_INCLUDES_DIR . 'class-html-generator.php';
require_once WP_MIXCLOUD_ARCHIVES_INCLUDES_DIR . 'class-cache-manager.php';
require_once WP_MIXCLOUD_ARCHIVES_INCLUDES_DIR . 'class-assets-manager.php';

// AIDEV-NOTE: Include admin class only in admin area for performance
if (is_admin()) {
    require_once WP_MIXCLOUD_ARCHIVES_ADMIN_DIR . 'class-wp-mixcloud-archives-admin.php';
}

/**
 * Main plugin class
 * 
 * Coordinates between component classes and manages plugin lifecycle
 */
class WP_Mixcloud_Archives {
    
    /**
     * Plugin instance
     *
     * @var WP_Mixcloud_Archives
     */
    private static $instance = null;
    
    /**
     * Component instances
     *
     * @var array
     */
    private $components = array();
    
    /**
     * Get plugin instance
     *
     * @return WP_Mixcloud_Archives
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
    }
    
    /**
     * Initialize component classes
     */
    private function init_components() {
        // Initialize core components
        $this->components['api_handler'] = new Mixcloud_API_Handler();
        $this->components['cache_manager'] = new WP_Mixcloud_Archives_Cache_Manager($this);
        $this->components['html_generator'] = new WP_Mixcloud_Archives_HTML_Generator($this);
        $this->components['assets_manager'] = new WP_Mixcloud_Archives_Assets_Manager($this);
        $this->components['shortcode_handler'] = new WP_Mixcloud_Archives_Shortcode_Handler($this);
        $this->components['ajax_handler'] = new WP_Mixcloud_Archives_AJAX_Handler($this);
        
        // Initialize admin component if in admin area
        if (is_admin() && class_exists('WP_Mixcloud_Archives_Admin')) {
            $this->components['admin_handler'] = new WP_Mixcloud_Archives_Admin($this);
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Plugin lifecycle hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Localization
        add_action('init', array($this, 'load_textdomain'));
        
        // Settings update hook
        add_action('updated_option', array($this, 'on_settings_updated'), 10, 3);
    }
    
    /**
     * Get API handler instance
     *
     * @return Mixcloud_API_Handler
     */
    public function get_api_handler() {
        return $this->components['api_handler'];
    }
    
    /**
     * Get cache manager instance
     *
     * @return WP_Mixcloud_Archives_Cache_Manager
     */
    public function get_cache_manager() {
        return $this->components['cache_manager'];
    }
    
    /**
     * Get HTML generator instance
     *
     * @return WP_Mixcloud_Archives_HTML_Generator
     */
    public function get_html_generator() {
        return $this->components['html_generator'];
    }
    
    /**
     * Get assets manager instance
     *
     * @return WP_Mixcloud_Archives_Assets_Manager
     */
    public function get_assets_manager() {
        return $this->components['assets_manager'];
    }
    
    /**
     * Get shortcode handler instance
     *
     * @return WP_Mixcloud_Archives_Shortcode_Handler
     */
    public function get_shortcode_handler() {
        return $this->components['shortcode_handler'];
    }
    
    /**
     * Get AJAX handler instance
     *
     * @return WP_Mixcloud_Archives_AJAX_Handler
     */
    public function get_ajax_handler() {
        return $this->components['ajax_handler'];
    }
    
    /**
     * Get admin handler instance
     *
     * @return WP_Mixcloud_Archives_Admin|null
     */
    public function get_admin_handler() {
        return isset($this->components['admin_handler']) ? $this->components['admin_handler'] : null;
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-mixcloud-archives',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Plugin activation hook
     */
    public function activate() {
        // AIDEV-NOTE: Set up default plugin options on activation
        $this->setup_default_options();
        
        // AIDEV-NOTE: Set plugin activation timestamp for tracking
        update_option('wp_mixcloud_archives_activated', time());
        
        // AIDEV-NOTE: Set plugin version for upgrade tracking
        update_option('wp_mixcloud_archives_version', WP_MIXCLOUD_ARCHIVES_VERSION);
        
        flush_rewrite_rules();
    }
    
    /**
     * Set up default plugin options on activation
     */
    private function setup_default_options() {
        $default_options = array(
            'default_account' => 'NowWaveRadio',
            'default_limit' => 20,
            'default_per_page' => 10,
            'cache_expiration' => 3600, // 1 hour
            'enable_lazy_loading' => true,
            'enable_mini_player' => true,
            'enable_date_filter' => true,
            'enable_pagination' => true,
            'enable_social_sharing' => true,
            'api_timeout' => 15,
            'api_connection_timeout' => 5,
            'rate_limit_requests' => 30,
            'rate_limit_window' => 300, // 5 minutes
            'debug_mode' => false,
        );
        
        $current_options = get_option('wp_mixcloud_archives_options', array());
        
        // AIDEV-NOTE: Merge with existing options to preserve user settings
        $merged_options = array_merge($default_options, $current_options);
        
        update_option('wp_mixcloud_archives_options', $merged_options);
    }
    
    /**
     * Handle settings updates to clear cache when needed
     *
     * @param string $option     Option name
     * @param mixed  $old_value  Old option value
     * @param mixed  $new_value  New option value
     */
    public function on_settings_updated($option, $old_value, $new_value) {
        // AIDEV-NOTE: Clear cache when plugin settings are updated
        if ($option === 'wp_mixcloud_archives_options') {
            // Check if account or cache settings changed
            $old_account = isset($old_value['mixcloud_account']) ? $old_value['mixcloud_account'] : '';
            $new_account = isset($new_value['mixcloud_account']) ? $new_value['mixcloud_account'] : '';
            
            $old_cache_expiration = isset($old_value['cache_expiration']) ? $old_value['cache_expiration'] : 3600;
            $new_cache_expiration = isset($new_value['cache_expiration']) ? $new_value['cache_expiration'] : 3600;
            
            // Clear cache if account or cache expiration changed
            if ($old_account !== $new_account || $old_cache_expiration !== $new_cache_expiration) {
                $this->get_cache_manager()->clear_all_cache();
            }
        }
    }
    
    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // AIDEV-NOTE: Clear scheduled events to prevent orphaned cron jobs
        wp_clear_scheduled_hook('wp_mixcloud_archives_cleanup_cache');
        wp_clear_scheduled_hook('wp_mixcloud_archives_warm_cache');
        
        // AIDEV-NOTE: Clear rate limiting transients to reset limits
        if (isset($this->components['ajax_handler'])) {
            $this->components['ajax_handler']->clear_rate_limit_data();
        }
        
        // AIDEV-NOTE: Set deactivation timestamp but preserve settings for reactivation
        update_option('wp_mixcloud_archives_deactivated', time());
        
        flush_rewrite_rules();
    }
    
    /**
     * Get cached fallback data for when API is unavailable
     *
     * This method provides backward compatibility for components that might call it
     *
     * @param string $username Mixcloud username
     * @return array           Cached cloudcast data
     */
    public function get_cached_fallback_data($username) {
        return $this->get_cache_manager()->get_cached_fallback_data($username);
    }
}

// AIDEV-NOTE: Initialize plugin instance
WP_Mixcloud_Archives::get_instance();