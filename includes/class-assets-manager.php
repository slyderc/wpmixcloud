<?php
/**
 * Assets Manager Class
 *
 * Handles script and style management for WP Mixcloud Archives
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages plugin assets and performance optimizations
 */
class WP_Mixcloud_Archives_Assets_Manager {
    
    /**
     * Reference to main plugin instance
     *
     * @var WP_Mixcloud_Archives
     */
    private $plugin;
    
    /**
     * Constructor
     *
     * @param WP_Mixcloud_Archives $plugin Main plugin instance
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Performance optimization hooks
        add_action('init', array($this, 'init_performance_optimizations'));
        add_action('wp_head', array($this, 'add_resource_hints'));
        add_action('wp_loaded', array($this, 'maybe_enable_compression'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_assets() {
        // AIDEV-NOTE: Only enqueue styles when shortcode is likely to be used
        if (!$this->should_enqueue_assets()) {
            return;
        }
        
        // AIDEV-NOTE: Use minified assets in production for better performance
        $min_suffix = $this->get_min_suffix();
        
        // Enqueue styles
        wp_enqueue_style(
            'wp-mixcloud-archives-style',
            WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'assets/css/style' . $min_suffix . '.css',
            array('dashicons'),
            WP_MIXCLOUD_ARCHIVES_VERSION
        );
        
        // AIDEV-NOTE: Enqueue JavaScript for player functionality and lazy loading
        wp_enqueue_script(
            'wp-mixcloud-archives-script',
            WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'assets/js/script' . $min_suffix . '.js',
            array('jquery'), // Add jQuery dependency for theme compatibility
            WP_MIXCLOUD_ARCHIVES_VERSION,
            true // Load in footer
        );
        
        // Localize script with translatable strings and data
        wp_localize_script(
            'wp-mixcloud-archives-script',
            'wpMixcloudArchives',
            $this->get_localized_script_data()
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // AIDEV-NOTE: Admin assets are handled by the admin class
        // This is kept for compatibility and potential future use
    }
    
    /**
     * Check if assets should be enqueued
     *
     * @return bool True if assets should be loaded
     */
    private function should_enqueue_assets() {
        global $post;
        
        // Check if we're on a page/post that might contain the shortcode
        if (is_singular() && isset($post->post_content)) {
            if (has_shortcode($post->post_content, 'mixcloud_archives')) {
                return true;
            }
        }
        
        // Always enqueue on admin preview or if force loading is needed
        if (is_preview() || is_admin() || apply_filters('wp_mixcloud_archives_force_enqueue', false)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get minification suffix based on debug mode
     *
     * @return string Minification suffix
     */
    private function get_min_suffix() {
        return (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';
    }
    
    /**
     * Get localized script data
     *
     * @return array Localized data for JavaScript
     */
    private function get_localized_script_data() {
        return array(
            // Text strings
            'loadingText'          => __('Loading...', 'wp-mixcloud-archives'),
            'errorText'            => __('Error loading player', 'wp-mixcloud-archives'),
            'noArtworkText'        => __('No artwork available', 'wp-mixcloud-archives'),
            'filteringText'        => __('Filtering...', 'wp-mixcloud-archives'),
            'applyFilterText'      => __('Apply Filter', 'wp-mixcloud-archives'),
            'filterErrorText'      => __('Failed to filter results. Please try again.', 'wp-mixcloud-archives'),
            'invalidDateRangeText' => __('End date must be after start date.', 'wp-mixcloud-archives'),
            'paginationErrorText'  => __('Failed to load page. Please try again.', 'wp-mixcloud-archives'),
            
            // AJAX configuration
            'ajaxUrl'              => admin_url('admin-ajax.php'),
            'nonce'                => wp_create_nonce('wp-mixcloud-archives'),
            
            // Performance settings
            'lazyLoadOffset'       => apply_filters('wp_mixcloud_archives_lazy_load_offset', '200px'),
            'debounceDelay'        => apply_filters('wp_mixcloud_archives_debounce_delay', 300),
            
            // Feature flags
            'enableIntersectionObserver' => $this->supports_intersection_observer(),
        );
    }
    
    /**
     * Initialize performance optimizations
     */
    public function init_performance_optimizations() {
        // Enable output compression for plugin assets if not already enabled
        if (!ini_get('zlib.output_compression') && !headers_sent()) {
            // Check if we're serving plugin assets
            add_action('wp_loaded', array($this, 'maybe_enable_compression'));
        }
        
        // Optimize database queries for caching
        add_filter('wp_mixcloud_archives_cache_expiration', array($this, 'optimize_cache_expiration'));
    }
    
    /**
     * Maybe enable compression for plugin responses
     */
    public function maybe_enable_compression() {
        if ($this->is_plugin_request() && function_exists('ob_gzhandler')) {
            ob_start('ob_gzhandler');
        }
    }
    
    /**
     * Check if current request is for plugin content
     *
     * @return bool True if plugin content is being requested
     */
    private function is_plugin_request() {
        global $post;
        
        // Check if we're on a page with the shortcode
        if (is_singular() && isset($post->post_content)) {
            return has_shortcode($post->post_content, 'mixcloud_archives');
        }
        
        // Check for AJAX requests
        if (wp_doing_ajax()) {
            $action = isset($_POST['action']) ? $_POST['action'] : '';
            return in_array($action, array('mixcloud_filter_by_date', 'mixcloud_paginate'));
        }
        
        return false;
    }
    
    /**
     * Add resource hints for external dependencies
     */
    public function add_resource_hints() {
        global $post;
        
        // Only add hints if shortcode is present
        if (!is_singular() || !isset($post->post_content)) {
            return;
        }
        
        if (!has_shortcode($post->post_content, 'mixcloud_archives')) {
            return;
        }
        
        // Preconnect to Mixcloud API and CDN
        echo '<link rel="preconnect" href="https://api.mixcloud.com" crossorigin>' . "\n";
        echo '<link rel="preconnect" href="https://www.mixcloud.com" crossorigin>' . "\n";
        echo '<link rel="dns-prefetch" href="//thumbnailer.mixcloud.com">' . "\n";
        
        // Add preload for critical assets if minified versions exist
        $min_suffix = $this->get_min_suffix();
        $css_url = WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'assets/css/style' . $min_suffix . '.css';
        
        echo '<link rel="preload" href="' . esc_url($css_url) . '" as="style">' . "\n";
    }
    
    /**
     * Optimize cache expiration based on content type
     *
     * @param int $expiration Default expiration time
     * @return int            Optimized expiration time
     */
    public function optimize_cache_expiration($expiration) {
        // Use cache manager's optimization if available
        $cache_manager = $this->plugin->get_cache_manager();
        if ($cache_manager && method_exists($cache_manager, 'optimize_cache_expiration')) {
            return $cache_manager->optimize_cache_expiration($expiration);
        }
        
        return $expiration;
    }
    
    /**
     * Check if browser supports Intersection Observer
     *
     * @return bool True if supported
     */
    private function supports_intersection_observer() {
        // AIDEV-NOTE: Conservative approach - assume support for modern browsers
        // Can be enhanced with actual browser detection if needed
        return true;
    }
    
    /**
     * Get critical CSS for inline loading
     *
     * @return string Critical CSS
     */
    public function get_critical_css() {
        // AIDEV-NOTE: Return minimal critical CSS for above-the-fold content
        return '
            .mixcloud-archives-container {
                opacity: 1;
                transition: opacity 0.3s ease;
            }
            .mixcloud-archives-loading {
                opacity: 0.5;
            }
            .mixcloud-list-item {
                margin-bottom: 1rem;
                padding: 1rem;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
        ';
    }
    
    /**
     * Preload critical resources
     */
    public function preload_critical_resources() {
        // This method can be called to preload specific resources
        // when we know they'll be needed (e.g., after detecting shortcode usage)
        
        $critical_resources = array(
            array(
                'href' => WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'assets/css/style' . $this->get_min_suffix() . '.css',
                'as' => 'style',
            ),
            array(
                'href' => WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'assets/js/script' . $this->get_min_suffix() . '.js',
                'as' => 'script',
            ),
        );
        
        foreach ($critical_resources as $resource) {
            printf(
                '<link rel="preload" href="%s" as="%s">' . "\n",
                esc_url($resource['href']),
                esc_attr($resource['as'])
            );
        }
    }
    
    /**
     * Get asset version for cache busting
     *
     * @param string $file Asset file path
     * @return string      Version string
     */
    public function get_asset_version($file) {
        // In development, use file modification time for cache busting
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $file_path = WP_MIXCLOUD_ARCHIVES_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                return filemtime($file_path);
            }
        }
        
        // In production, use plugin version
        return WP_MIXCLOUD_ARCHIVES_VERSION;
    }
}