<?php
/**
 * Plugin Name: WP Mixcloud Archives
 * Plugin URI: https://github.com/your-username/wp-mixcloud-archives
 * Description: A WordPress plugin to display Mixcloud archives with embedded players, supporting date filtering, pagination, and social sharing.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

// AIDEV-NOTE: Include admin class only in admin area for performance
if (is_admin()) {
    require_once WP_MIXCLOUD_ARCHIVES_ADMIN_DIR . 'class-wp-mixcloud-archives-admin.php';
}

/**
 * Main plugin class
 */
class WP_Mixcloud_Archives {
    
    /**
     * Plugin instance
     *
     * @var WP_Mixcloud_Archives
     */
    private static $instance = null;
    
    /**
     * Mixcloud API handler instance
     *
     * @var Mixcloud_API_Handler
     */
    private $api_handler = null;
    
    /**
     * Admin handler instance
     *
     * @var WP_Mixcloud_Archives_Admin
     */
    private $admin_handler = null;
    
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
        $this->init_hooks();
        $this->init_api_handler();
        $this->init_admin_handler();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AIDEV-NOTE: Performance optimization hooks
        add_action('init', array($this, 'init_performance_optimizations'));
        
        // AIDEV-NOTE: Register shortcode for frontend display
        add_action('init', array($this, 'register_shortcodes'));
        
        // AIDEV-NOTE: Register AJAX handlers for date filtering and pagination
        add_action('wp_ajax_mixcloud_filter_by_date', array($this, 'ajax_filter_by_date'));
        add_action('wp_ajax_nopriv_mixcloud_filter_by_date', array($this, 'ajax_filter_by_date'));
        add_action('wp_ajax_mixcloud_paginate', array($this, 'ajax_paginate'));
        add_action('wp_ajax_nopriv_mixcloud_paginate', array($this, 'ajax_paginate'));
        
        // AIDEV-NOTE: Register cache warming action
        add_action('mixcloud_warm_cache_single', array($this, 'warm_single_account_cache'));
        
        // AIDEV-NOTE: Hook into admin settings save to clear cache when settings change
        add_action('updated_option', array($this, 'on_settings_updated'), 10, 3);
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize Mixcloud API handler
     */
    private function init_api_handler() {
        if (null === $this->api_handler) {
            $this->api_handler = new Mixcloud_API_Handler();
        }
    }
    
    /**
     * Get Mixcloud API handler instance
     *
     * @return Mixcloud_API_Handler
     */
    public function get_api_handler() {
        if (null === $this->api_handler) {
            $this->init_api_handler();
        }
        return $this->api_handler;
    }
    
    /**
     * Initialize admin handler
     */
    private function init_admin_handler() {
        if (is_admin() && null === $this->admin_handler && class_exists('WP_Mixcloud_Archives_Admin')) {
            $this->admin_handler = new WP_Mixcloud_Archives_Admin($this);
        }
    }
    
    /**
     * Get admin handler instance
     *
     * @return WP_Mixcloud_Archives_Admin|null
     */
    public function get_admin_handler() {
        if (is_admin() && null === $this->admin_handler) {
            $this->init_admin_handler();
        }
        return $this->admin_handler;
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
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // AIDEV-NOTE: Only enqueue styles when shortcode is likely to be used
        global $post;
        
        $should_enqueue = false;
        
        // Check if we're on a page/post that might contain the shortcode
        if (is_singular() && isset($post->post_content)) {
            if (has_shortcode($post->post_content, 'mixcloud_archives')) {
                $should_enqueue = true;
            }
        }
        
        // Always enqueue on admin preview or if force loading is needed
        if (is_preview() || is_admin() || apply_filters('wp_mixcloud_archives_force_enqueue', false)) {
            $should_enqueue = true;
        }
        
        if ($should_enqueue) {
            // AIDEV-NOTE: Use minified assets in production for better performance
            $min_suffix = (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';
            
            wp_enqueue_style(
                'wp-mixcloud-archives-style',
                WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'assets/css/style' . $min_suffix . '.css',
                array(),
                WP_MIXCLOUD_ARCHIVES_VERSION
            );
            
            // AIDEV-NOTE: Enqueue JavaScript for player functionality and lazy loading
            wp_enqueue_script(
                'wp-mixcloud-archives-script',
                WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'assets/js/script' . $min_suffix . '.js',
                array(),
                WP_MIXCLOUD_ARCHIVES_VERSION,
                true
            );
            
            // Localize script with translatable strings
            wp_localize_script(
                'wp-mixcloud-archives-script',
                'wpMixcloudArchives',
                array(
                    'loadingText'          => __('Loading...', 'wp-mixcloud-archives'),
                    'errorText'            => __('Error loading player', 'wp-mixcloud-archives'),
                    'noArtworkText'        => __('No artwork available', 'wp-mixcloud-archives'),
                    'filteringText'        => __('Filtering...', 'wp-mixcloud-archives'),
                    'applyFilterText'      => __('Apply Filter', 'wp-mixcloud-archives'),
                    'filterErrorText'      => __('Failed to filter results. Please try again.', 'wp-mixcloud-archives'),
                    'invalidDateRangeText' => __('End date must be after start date.', 'wp-mixcloud-archives'),
                    'paginationErrorText'  => __('Failed to load page. Please try again.', 'wp-mixcloud-archives'),
                    'ajaxUrl'              => admin_url('admin-ajax.php'),
                    'nonce'                => wp_create_nonce('wp-mixcloud-archives'),
                )
            );
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // AIDEV-NOTE: Admin assets are now handled by the admin class
        // See admin/class-wp-mixcloud-archives-admin.php for implementation
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
                $this->clear_all_caches();
                
                // AIDEV-NOTE: Log cache clearing for debugging
                if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('WP Mixcloud Archives: Cache cleared due to settings update');
                }
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
        $this->clear_rate_limit_data();
        
        // AIDEV-NOTE: Log deactivation for debugging purposes
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('WP Mixcloud Archives deactivated at ' . current_time('mysql'));
        }
        
        // AIDEV-NOTE: Set deactivation timestamp but preserve settings for reactivation
        update_option('wp_mixcloud_archives_deactivated', time());
        
        flush_rewrite_rules();
    }
    
    /**
     * Clear rate limiting data on deactivation
     */
    private function clear_rate_limit_data() {
        global $wpdb;
        
        // AIDEV-NOTE: Remove all rate limiting transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mixcloud_ajax_limit_%' 
             OR option_name LIKE '_transient_timeout_mixcloud_ajax_limit_%'"
        );
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('mixcloud_archives', array($this, 'shortcode_mixcloud_archives'));
    }
    
    /**
     * Handle mixcloud_archives shortcode
     *
     * @param array  $atts    Shortcode attributes
     * @param string $content Shortcode content
     * @return string         HTML output
     */
    public function shortcode_mixcloud_archives($atts, $content = '') {
        // Parse shortcode attributes with defaults
        $atts = shortcode_atts(array(
            'account'          => '',
            'days'             => 30,
            'limit'            => 10,
            'lazy_load'        => 'yes',
            'mini_player'      => 'yes',
            'show_date_filter' => 'yes',
            'start_date'       => '',
            'end_date'         => '',
            'page'             => 1,
            'per_page'         => 10,
            'show_pagination'  => 'yes',
            'show_social'      => 'yes',
        ), $atts, 'mixcloud_archives');
        
        // Validate required account parameter
        if (empty($atts['account'])) {
            return '<div class="mixcloud-archives-error">' . 
                   esc_html__('Error: Account parameter is required for mixcloud_archives shortcode.', 'wp-mixcloud-archives') . 
                   '</div>';
        }
        
        // Sanitize and validate attributes
        $account = sanitize_text_field($atts['account']);
        $days = absint($atts['days']);
        $limit = absint($atts['limit']);
        $page = max(1, absint($atts['page'])); // Ensure page is at least 1
        $per_page = absint($atts['per_page']);
        
        // Set reasonable limits
        $days = max(1, min($days, 365)); // Between 1 and 365 days
        $limit = max(1, min($limit, 100)); // Between 1 and 100 results for API
        $per_page = max(1, min($per_page, 50)); // Between 1 and 50 results per page
        
        // Get cloudcasts from API with simple caching
        $cache_args = array(
            'limit'    => $limit,
            'metadata' => true,
        );
        
        // AIDEV-NOTE: Simple transient caching for API responses
        $cache_key = 'mixcloud_cloudcasts_' . md5($account . serialize($cache_args));
        $cloudcasts_data = get_transient($cache_key);
        
        if (false === $cloudcasts_data) {
            $cloudcasts_data = $this->get_api_handler()->get_user_cloudcasts($account, $cache_args);
            
            // Store in transient cache if successful
            if (!is_wp_error($cloudcasts_data)) {
                $options = get_option('wp_mixcloud_archives_options', array());
                $cache_expiration = isset($options['cache_expiration']) ? $options['cache_expiration'] : 3600;
                set_transient($cache_key, $cloudcasts_data, $cache_expiration);
            }
        }
        
        // Handle API errors with user-friendly messages
        if (is_wp_error($cloudcasts_data)) {
            // Check if we should show fallback content for severe failures
            $error_code = $cloudcasts_data->get_error_code();
            $severe_errors = array('api_error_500', 'api_error_502', 'api_error_503', 'api_request_failed');
            
            if (in_array($error_code, $severe_errors, true)) {
                // AIDEV-NOTE: Simplified fallback - just show generic error for now
                // Complex fallback system removed for simplicity
            }
            
            // Show user-friendly error message
            return $this->generate_user_friendly_error($cloudcasts_data, 'shortcode');
        }
        
        // Filter cloudcasts by date range (custom dates take priority over days)
        if (!empty($atts['start_date']) || !empty($atts['end_date'])) {
            $cloudcasts_data['data'] = $this->filter_cloudcasts_by_custom_dates(
                $cloudcasts_data['data'], 
                $atts['start_date'], 
                $atts['end_date']
            );
        } elseif ($days < 365) {
            $cloudcasts_data['data'] = $this->filter_cloudcasts_by_date($cloudcasts_data['data'], $days);
        }
        
        // Calculate pagination information
        $total_items = count($cloudcasts_data['data']);
        $pagination_info = $this->calculate_pagination($total_items, $page, $per_page);
        
        // Apply pagination to cloudcasts data
        $cloudcasts_data['data'] = $this->paginate_cloudcasts($cloudcasts_data['data'], $page, $per_page);
        
        // Generate HTML output with additional options
        $options = array(
            'lazy_load'        => ($atts['lazy_load'] === 'yes'),
            'mini_player'      => ($atts['mini_player'] === 'yes'),
            'show_date_filter' => ($atts['show_date_filter'] === 'yes'),
            'show_pagination'  => ($atts['show_pagination'] === 'yes'),
            'show_social'      => ($atts['show_social'] === 'yes'),
            'account'          => $account,
            'current_start_date' => $atts['start_date'],
            'current_end_date'   => $atts['end_date'],
            'pagination'       => $pagination_info,
            'per_page'         => $per_page,
        );
        
        return $this->generate_shortcode_html($cloudcasts_data, $account, $options);
    }
    
    /**
     * Filter cloudcasts by date range
     *
     * @param array $cloudcasts Array of cloudcast data
     * @param int   $days       Number of days to filter
     * @return array            Filtered cloudcasts
     */
    private function filter_cloudcasts_by_date($cloudcasts, $days) {
        $cutoff_date = current_time('timestamp') - ($days * DAY_IN_SECONDS);
        $filtered = array();
        
        foreach ($cloudcasts as $cloudcast) {
            $created_timestamp = strtotime($cloudcast['created_time']);
            if ($created_timestamp >= $cutoff_date) {
                $filtered[] = $cloudcast;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Filter cloudcasts by custom date range
     *
     * @param array  $cloudcasts Array of cloudcast data
     * @param string $start_date Start date in YYYY-MM-DD format
     * @param string $end_date   End date in YYYY-MM-DD format
     * @return array             Filtered cloudcasts
     */
    private function filter_cloudcasts_by_custom_dates($cloudcasts, $start_date = '', $end_date = '') {
        $filtered = array();
        
        // Convert dates to timestamps for comparison
        $start_timestamp = !empty($start_date) ? strtotime($start_date . ' 00:00:00') : 0;
        $end_timestamp = !empty($end_date) ? strtotime($end_date . ' 23:59:59') : PHP_INT_MAX;
        
        // If neither date is provided, return all cloudcasts
        if (!$start_timestamp && $end_timestamp === PHP_INT_MAX) {
            return $cloudcasts;
        }
        
        foreach ($cloudcasts as $cloudcast) {
            $created_timestamp = strtotime($cloudcast['created_time']);
            
            // Check if cloudcast falls within date range
            if ($created_timestamp >= $start_timestamp && $created_timestamp <= $end_timestamp) {
                $filtered[] = $cloudcast;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Calculate pagination information
     *
     * @param int $total_items Total number of items
     * @param int $current_page Current page number
     * @param int $per_page Items per page
     * @return array Pagination information
     */
    private function calculate_pagination($total_items, $current_page, $per_page) {
        $total_pages = max(1, ceil($total_items / $per_page));
        $current_page = max(1, min($current_page, $total_pages));
        
        // Calculate start and end items for current page
        $start_item = ($current_page - 1) * $per_page + 1;
        $end_item = min($current_page * $per_page, $total_items);
        
        return array(
            'total_items'  => $total_items,
            'total_pages'  => $total_pages,
            'current_page' => $current_page,
            'per_page'     => $per_page,
            'start_item'   => $start_item,
            'end_item'     => $end_item,
            'has_prev'     => $current_page > 1,
            'has_next'     => $current_page < $total_pages,
            'prev_page'    => max(1, $current_page - 1),
            'next_page'    => min($total_pages, $current_page + 1),
        );
    }
    
    /**
     * Paginate cloudcasts array
     *
     * @param array $cloudcasts Array of cloudcast data
     * @param int   $page       Current page number
     * @param int   $per_page   Items per page
     * @return array            Paginated cloudcasts
     */
    private function paginate_cloudcasts($cloudcasts, $page, $per_page) {
        $offset = ($page - 1) * $per_page;
        return array_slice($cloudcasts, $offset, $per_page);
    }
    
    /**
     * Generate HTML output for shortcode
     *
     * @param array  $cloudcasts_data Cloudcasts data from API
     * @param string $account         Mixcloud account name
     * @param array  $options         Display options
     * @return string                 HTML output
     */
    private function generate_shortcode_html($cloudcasts_data, $account, $options = array()) {
        if (empty($cloudcasts_data['data'])) {
            return '<div class="mixcloud-archives-empty">' . 
                   esc_html(sprintf(__('No cloudcasts found for account "%s".', 'wp-mixcloud-archives'), $account)) . 
                   '</div>';
        }
        
        $html = '<div class="mixcloud-archives-container" data-account="' . esc_attr($account) . '">';
        $html .= '<h3 class="mixcloud-archives-title">' . 
                 esc_html(sprintf(__('Mixcloud Archives for %s', 'wp-mixcloud-archives'), $account)) . 
                 '</h3>';
        
        // AIDEV-NOTE: Add date filter UI if enabled
        if (!empty($options['show_date_filter'])) {
            $html .= $this->generate_date_filter_html($options);
        }
        
        // AIDEV-NOTE: Grid layout for better visual display of cloudcast data
        $html .= '<div class="mixcloud-archives-grid">';
        
        foreach ($cloudcasts_data['data'] as $cloudcast) {
            $html .= $this->generate_cloudcast_html($cloudcast, $options);
        }
        
        $html .= '</div>'; // .mixcloud-archives-grid
        
        // Modal container for player popups
        $html .= '<div id="mixcloud-player-modal" class="mixcloud-modal">';
        $html .= '<div class="mixcloud-modal-content">';
        $html .= '<span class="mixcloud-modal-close">&times;</span>';
        $html .= '<div class="mixcloud-modal-player-container"></div>';
        $html .= '</div>';
        $html .= '</div>'; // .mixcloud-modal
        
        // AIDEV-NOTE: Add pagination controls if enabled
        if (!empty($options['show_pagination']) && !empty($options['pagination'])) {
            $html .= $this->generate_pagination_html($options['pagination'], $options['account']);
        }
        
        $html .= '</div>'; // .mixcloud-archives-container
        
        return $html;
    }
    
    /**
     * Generate date filter HTML
     *
     * @param array $options Display options including current dates
     * @return string        Date filter HTML
     */
    private function generate_date_filter_html($options) {
        $start_date = !empty($options['current_start_date']) ? $options['current_start_date'] : '';
        $end_date = !empty($options['current_end_date']) ? $options['current_end_date'] : '';
        $account = $options['account'];
        
        $html = '<div class="mixcloud-date-filter">';
        $html .= '<h4 class="mixcloud-date-filter-title">' . esc_html__('Filter by Date Range', 'wp-mixcloud-archives') . '</h4>';
        $html .= '<div class="mixcloud-date-filter-controls">';
        
        // Start date input
        $html .= '<div class="mixcloud-date-input-group">';
        $html .= '<label for="mixcloud-start-date-' . esc_attr($account) . '" class="mixcloud-date-label">' . 
                 esc_html__('From:', 'wp-mixcloud-archives') . '</label>';
        $html .= '<input type="date" 
                    id="mixcloud-start-date-' . esc_attr($account) . '" 
                    class="mixcloud-date-input mixcloud-start-date" 
                    value="' . esc_attr($start_date) . '"
                    data-account="' . esc_attr($account) . '">';
        $html .= '</div>';
        
        // End date input  
        $html .= '<div class="mixcloud-date-input-group">';
        $html .= '<label for="mixcloud-end-date-' . esc_attr($account) . '" class="mixcloud-date-label">' . 
                 esc_html__('To:', 'wp-mixcloud-archives') . '</label>';
        $html .= '<input type="date" 
                    id="mixcloud-end-date-' . esc_attr($account) . '" 
                    class="mixcloud-date-input mixcloud-end-date" 
                    value="' . esc_attr($end_date) . '"
                    data-account="' . esc_attr($account) . '">';
        $html .= '</div>';
        
        // Filter buttons
        $html .= '<div class="mixcloud-date-filter-buttons">';
        $html .= '<button type="button" class="mixcloud-date-filter-btn mixcloud-date-apply" data-account="' . esc_attr($account) . '">';
        $html .= esc_html__('Apply Filter', 'wp-mixcloud-archives');
        $html .= '</button>';
        $html .= '<button type="button" class="mixcloud-date-filter-btn mixcloud-date-clear" data-account="' . esc_attr($account) . '">';
        $html .= esc_html__('Clear Filter', 'wp-mixcloud-archives');
        $html .= '</button>';
        $html .= '</div>';
        
        $html .= '</div>'; // .mixcloud-date-filter-controls
        $html .= '</div>'; // .mixcloud-date-filter
        
        return $html;
    }
    
    /**
     * Generate pagination HTML
     *
     * @param array  $pagination Pagination information
     * @param string $account    Mixcloud account name
     * @return string            Pagination HTML
     */
    private function generate_pagination_html($pagination, $account) {
        // Don't show pagination if there's only one page
        if ($pagination['total_pages'] <= 1) {
            return '';
        }
        
        $html = '<div class="mixcloud-pagination" data-account="' . esc_attr($account) . '">';
        
        // Pagination info
        $html .= '<div class="mixcloud-pagination-info">';
        $html .= esc_html(sprintf(
            __('Showing %d-%d of %d shows', 'wp-mixcloud-archives'),
            $pagination['start_item'],
            $pagination['end_item'],
            $pagination['total_items']
        ));
        $html .= '</div>';
        
        $html .= '<div class="mixcloud-pagination-controls">';
        
        // Previous button
        if ($pagination['has_prev']) {
            $html .= '<button type="button" class="mixcloud-pagination-btn mixcloud-pagination-prev" ' .
                     'data-page="' . esc_attr($pagination['prev_page']) . '" ' .
                     'data-account="' . esc_attr($account) . '">';
            $html .= '<span class="dashicons dashicons-arrow-left-alt2"></span>';
            $html .= esc_html__('Previous', 'wp-mixcloud-archives');
            $html .= '</button>';
        } else {
            $html .= '<span class="mixcloud-pagination-btn mixcloud-pagination-disabled">';
            $html .= '<span class="dashicons dashicons-arrow-left-alt2"></span>';
            $html .= esc_html__('Previous', 'wp-mixcloud-archives');
            $html .= '</span>';
        }
        
        // Page numbers
        $html .= '<div class="mixcloud-pagination-numbers">';
        $html .= $this->generate_page_numbers($pagination, $account);
        $html .= '</div>';
        
        // Next button
        if ($pagination['has_next']) {
            $html .= '<button type="button" class="mixcloud-pagination-btn mixcloud-pagination-next" ' .
                     'data-page="' . esc_attr($pagination['next_page']) . '" ' .
                     'data-account="' . esc_attr($account) . '">';
            $html .= esc_html__('Next', 'wp-mixcloud-archives');
            $html .= '<span class="dashicons dashicons-arrow-right-alt2"></span>';
            $html .= '</button>';
        } else {
            $html .= '<span class="mixcloud-pagination-btn mixcloud-pagination-disabled">';
            $html .= esc_html__('Next', 'wp-mixcloud-archives');
            $html .= '<span class="dashicons dashicons-arrow-right-alt2"></span>';
            $html .= '</span>';
        }
        
        $html .= '</div>'; // .mixcloud-pagination-controls
        $html .= '</div>'; // .mixcloud-pagination
        
        return $html;
    }
    
    /**
     * Generate page numbers for pagination
     *
     * @param array  $pagination Pagination information
     * @param string $account    Mixcloud account name
     * @return string            Page numbers HTML
     */
    private function generate_page_numbers($pagination, $account) {
        $current_page = $pagination['current_page'];
        $total_pages = $pagination['total_pages'];
        $html = '';
        
        // AIDEV-NOTE: Intelligent page number display logic
        $range = 2; // Show 2 pages on each side of current page
        $start = max(1, $current_page - $range);
        $end = min($total_pages, $current_page + $range);
        
        // Always show first page
        if ($start > 1) {
            $html .= $this->generate_page_number_link(1, $account, $current_page);
            if ($start > 2) {
                $html .= '<span class="mixcloud-pagination-ellipsis">...</span>';
            }
        }
        
        // Show page range around current page
        for ($i = $start; $i <= $end; $i++) {
            $html .= $this->generate_page_number_link($i, $account, $current_page);
        }
        
        // Always show last page
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                $html .= '<span class="mixcloud-pagination-ellipsis">...</span>';
            }
            $html .= $this->generate_page_number_link($total_pages, $account, $current_page);
        }
        
        return $html;
    }
    
    /**
     * Generate single page number link
     *
     * @param int    $page_number Page number
     * @param string $account     Mixcloud account name
     * @param int    $current_page Current page number
     * @return string             Page number HTML
     */
    private function generate_page_number_link($page_number, $account, $current_page) {
        if ($page_number == $current_page) {
            return '<span class="mixcloud-pagination-number mixcloud-pagination-current" aria-current="page">' . 
                   esc_html($page_number) . '</span>';
        } else {
            return '<button type="button" class="mixcloud-pagination-number mixcloud-pagination-link" ' .
                   'data-page="' . esc_attr($page_number) . '" ' .
                   'data-account="' . esc_attr($account) . '" ' .
                   'aria-label="' . esc_attr(sprintf(__('Go to page %d', 'wp-mixcloud-archives'), $page_number)) . '">' .
                   esc_html($page_number) . '</button>';
        }
    }
    
    /**
     * Generate HTML for a single cloudcast
     *
     * @param array $cloudcast Cloudcast data
     * @param array $options   Display options
     * @return string          HTML output for cloudcast
     */
    private function generate_cloudcast_html($cloudcast, $options = array()) {
        $html = '<div class="mixcloud-archive-card">';
        
        // Artwork section - clickable to open player modal
        $html .= '<div class="mixcloud-card-artwork" data-cloudcast-key="' . esc_attr($cloudcast['key']) . '" data-cloudcast-url="' . esc_url($cloudcast['url']) . '" data-cloudcast-name="' . esc_attr($cloudcast['name']) . '">';
        $html .= $this->generate_artwork_html($cloudcast);
        $html .= '<div class="mixcloud-play-overlay">';
        $html .= '<span class="dashicons dashicons-controls-play"></span>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Content section
        $html .= '<div class="mixcloud-card-content">';
        
        // Title
        $html .= '<h3 class="mixcloud-card-title">';
        $html .= '<a href="' . esc_url($cloudcast['url']) . '" target="_blank" rel="noopener" class="mixcloud-title-link">';
        $html .= esc_html($cloudcast['name']);
        $html .= '</a>';
        $html .= '</h3>';
        
        // Description
        if (!empty($cloudcast['description'])) {
            $html .= '<div class="mixcloud-card-description">';
            $html .= wp_kses_post(wp_trim_words($cloudcast['description'], 30));
            $html .= '</div>';
        }
        
        // Metadata (duration and date)
        $html .= '<div class="mixcloud-card-meta">';
        
        // Duration
        if ($cloudcast['audio_length'] > 0) {
            $duration = gmdate('H:i:s', $cloudcast['audio_length']);
            $html .= '<span class="mixcloud-meta-item mixcloud-duration">';
            $html .= '<span class="dashicons dashicons-clock"></span>';
            $html .= esc_html($duration);
            $html .= '</span>';
        }
        
        // Date
        $timestamp = strtotime($cloudcast['created_time']);
        $formatted_date = date_i18n(get_option('date_format'), $timestamp);
        $html .= '<span class="mixcloud-meta-item mixcloud-date">';
        $html .= '<span class="dashicons dashicons-calendar-alt"></span>';
        $html .= esc_html($formatted_date);
        $html .= '</span>';
        
        // Play count
        if ($cloudcast['play_count'] > 0) {
            $html .= '<span class="mixcloud-meta-item mixcloud-plays">';
            $html .= '<span class="dashicons dashicons-visibility"></span>';
            $html .= esc_html(number_format($cloudcast['play_count']));
            $html .= '</span>';
        }
        
        $html .= '</div>'; // .mixcloud-card-meta
        
        // Social sharing (if enabled)
        if (!empty($options['show_social'])) {
            $html .= '<div class="mixcloud-card-social">';
            $html .= $this->generate_social_sharing_html($cloudcast);
            $html .= '</div>';
        }
        
        $html .= '</div>'; // .mixcloud-card-content
        $html .= '</div>'; // .mixcloud-archive-card
        
        return $html;
    }
    
    /**
     * Generate social sharing HTML for cloudcast
     *
     * @param array $cloudcast Cloudcast data
     * @return string          Social sharing HTML
     */
    private function generate_social_sharing_html($cloudcast) {
        $share_url = esc_url($cloudcast['url']);
        $share_title = esc_attr($cloudcast['name']);
        $share_description = !empty($cloudcast['description']) ? 
            esc_attr(wp_trim_words(strip_tags($cloudcast['description']), 20)) : 
            esc_attr(sprintf(__('Check out this mix by %s on Mixcloud', 'wp-mixcloud-archives'), $cloudcast['user']['name']));
        
        $html = '<div class="mixcloud-social-sharing" data-url="' . $share_url . '" data-title="' . $share_title . '" data-description="' . $share_description . '">';
        
        // Facebook share button
        $html .= '<button type="button" class="mixcloud-social-btn mixcloud-facebook-btn" ' .
                 'data-platform="facebook" ' .
                 'aria-label="' . esc_attr(sprintf(__('Share %s on Facebook', 'wp-mixcloud-archives'), $cloudcast['name'])) . '">';
        $html .= '<span class="dashicons dashicons-facebook-alt"></span>';
        $html .= '<span class="mixcloud-social-label">' . esc_html__('Facebook', 'wp-mixcloud-archives') . '</span>';
        $html .= '</button>';
        
        // Twitter/X share button
        $html .= '<button type="button" class="mixcloud-social-btn mixcloud-twitter-btn" ' .
                 'data-platform="twitter" ' .
                 'aria-label="' . esc_attr(sprintf(__('Share %s on Twitter', 'wp-mixcloud-archives'), $cloudcast['name'])) . '">';
        $html .= '<span class="dashicons dashicons-twitter"></span>';
        $html .= '<span class="mixcloud-social-label">' . esc_html__('Twitter', 'wp-mixcloud-archives') . '</span>';
        $html .= '</button>';
        
        // Bluesky share button
        $html .= '<button type="button" class="mixcloud-social-btn mixcloud-bluesky-btn" ' .
                 'data-platform="bluesky" ' .
                 'aria-label="' . esc_attr(sprintf(__('Share %s on Bluesky', 'wp-mixcloud-archives'), $cloudcast['name'])) . '">';
        $html .= '<span class="mixcloud-bluesky-icon">🦋</span>';
        $html .= '<span class="mixcloud-social-label">' . esc_html__('Bluesky', 'wp-mixcloud-archives') . '</span>';
        $html .= '</button>';
        
        // Copy link button
        $html .= '<button type="button" class="mixcloud-social-btn mixcloud-copy-btn" ' .
                 'data-platform="copy" ' .
                 'aria-label="' . esc_attr(sprintf(__('Copy link for %s', 'wp-mixcloud-archives'), $cloudcast['name'])) . '">';
        $html .= '<span class="dashicons dashicons-admin-links"></span>';
        $html .= '<span class="mixcloud-social-label">' . esc_html__('Copy Link', 'wp-mixcloud-archives') . '</span>';
        $html .= '</button>';
        
        $html .= '</div>'; // .mixcloud-social-sharing
        
        return $html;
    }
    
    /**
     * Generate artwork HTML for cloudcast
     *
     * @param array $cloudcast Cloudcast data
     * @return string          Artwork HTML
     */
    private function generate_artwork_html($cloudcast) {
        if (empty($cloudcast['picture_urls']) || !is_array($cloudcast['picture_urls'])) {
            return '<div class="mixcloud-no-artwork-large"><span class="dashicons dashicons-format-audio"></span><span>' . esc_html__('No artwork', 'wp-mixcloud-archives') . '</span></div>';
        }
        
        // AIDEV-NOTE: Use large size artwork for card display for better visual impact
        $artwork_url = '';
        if (isset($cloudcast['picture_urls']['large'])) {
            $artwork_url = $cloudcast['picture_urls']['large'];
        } elseif (isset($cloudcast['picture_urls']['extra_large'])) {
            $artwork_url = $cloudcast['picture_urls']['extra_large'];
        } elseif (isset($cloudcast['picture_urls']['medium'])) {
            $artwork_url = $cloudcast['picture_urls']['medium'];
        } elseif (isset($cloudcast['picture_urls']['small'])) {
            $artwork_url = $cloudcast['picture_urls']['small'];
        }
        
        if (empty($artwork_url)) {
            return '<div class="mixcloud-no-artwork-large"><span class="dashicons dashicons-format-audio"></span><span>' . esc_html__('No artwork', 'wp-mixcloud-archives') . '</span></div>';
        }
        
        return sprintf(
            '<img data-src="%s" alt="%s" class="mixcloud-artwork" loading="lazy" />',
            esc_url($artwork_url),
            esc_attr(sprintf(__('Artwork for %s', 'wp-mixcloud-archives'), $cloudcast['name']))
        );
    }
    
    /**
     * Generate embedded player HTML for cloudcast
     *
     * @param array $cloudcast Cloudcast data
     * @param array $options   Display options
     * @return string          Player HTML
     */
    private function generate_player_html($cloudcast, $options = array()) {
        // Default options
        $defaults = array(
            'lazy_load'   => true,
            'mini_player' => true,
        );
        $options = wp_parse_args($options, $defaults);
        
        // AIDEV-NOTE: Mixcloud embed parameters for better player experience
        $embed_params = array(
            'hide_cover'    => 1,        // Hide the large cover art
            'mini'          => $options['mini_player'] ? 1 : 0,  // Use mini player if enabled
            'light'         => 1,        // Use light theme
            'hide_artwork'  => 0,        // Show small artwork in player
            'autoplay'      => 0,        // Disable autoplay
        );
        
        // Build embed URL with parameters
        $base_embed_url = str_replace('https://www.mixcloud.com/', 'https://www.mixcloud.com/widget/iframe/?feed=', $cloudcast['url']);
        $embed_url = add_query_arg($embed_params, $base_embed_url);
        
        // AIDEV-NOTE: Implement lazy loading with data-src for performance
        if ($options['lazy_load']) {
            $player_html = sprintf(
                '<div class="mixcloud-player-wrapper" data-cloudcast-key="%s">
                    <button class="mixcloud-player-load-btn" type="button" aria-label="%s">
                        <span class="dashicons dashicons-controls-play"></span>
                        %s
                    </button>
                    <iframe 
                        width="100%%" 
                        height="60" 
                        data-src="%s" 
                        frameborder="0" 
                        class="mixcloud-player mixcloud-player-lazy"
                        allowfullscreen
                        title="%s"
                        loading="lazy">
                    </iframe>
                </div>',
                esc_attr($cloudcast['key']),
                esc_attr(sprintf(__('Load player for %s', 'wp-mixcloud-archives'), $cloudcast['name'])),
                esc_html__('Load Player', 'wp-mixcloud-archives'),
                esc_url($embed_url),
                esc_attr(sprintf(__('Mixcloud player for %s', 'wp-mixcloud-archives'), $cloudcast['name']))
            );
        } else {
            // Direct loading without lazy load
            $player_html = sprintf(
                '<iframe 
                    width="100%%" 
                    height="60" 
                    src="%s" 
                    frameborder="0" 
                    class="mixcloud-player"
                    allowfullscreen
                    title="%s">
                </iframe>',
                esc_url($embed_url),
                esc_attr(sprintf(__('Mixcloud player for %s', 'wp-mixcloud-archives'), $cloudcast['name']))
            );
        }
        
        return $player_html;
    }
    
    /**
     * Generate show notes HTML for cloudcast
     *
     * @param array $cloudcast Cloudcast data
     * @return string          Show notes HTML
     */
    private function generate_show_notes_html($cloudcast) {
        $html = '';
        
        // Description
        if (!empty($cloudcast['description'])) {
            // AIDEV-NOTE: Use restricted HTML allowed list for better security than wp_kses_post
            $allowed_html = array(
                'a' => array(
                    'href' => true,
                    'title' => true,
                    'target' => true,
                    'rel' => true,
                ),
                'br' => array(),
                'em' => array(),
                'strong' => array(),
                'p' => array(),
                'span' => array(),
            );
            
            $html .= '<div class="mixcloud-description">';
            $html .= wp_kses(wp_trim_words($cloudcast['description'], 50), $allowed_html);
            $html .= '</div>';
        }
        
        // Stats
        $stats = array();
        if ($cloudcast['play_count'] > 0) {
            $stats[] = sprintf(__('Plays: %s', 'wp-mixcloud-archives'), number_format($cloudcast['play_count']));
        }
        if ($cloudcast['favorite_count'] > 0) {
            $stats[] = sprintf(__('Favorites: %s', 'wp-mixcloud-archives'), number_format($cloudcast['favorite_count']));
        }
        if ($cloudcast['audio_length'] > 0) {
            $duration = gmdate('H:i:s', $cloudcast['audio_length']);
            $stats[] = sprintf(__('Duration: %s', 'wp-mixcloud-archives'), $duration);
        }
        
        if (!empty($stats)) {
            $html .= '<div class="mixcloud-stats">';
            $html .= esc_html(implode(' • ', $stats));
            $html .= '</div>';
        }
        
        return $html ?: '<div class="mixcloud-no-notes">' . esc_html__('No description available', 'wp-mixcloud-archives') . '</div>';
    }
    
    /**
     * Generate formatted date HTML for cloudcast
     *
     * @param array $cloudcast Cloudcast data
     * @return string          Formatted date HTML
     */
    private function generate_date_html($cloudcast) {
        $timestamp = strtotime($cloudcast['created_time']);
        
        if (!$timestamp) {
            return '<span class="mixcloud-date-error">' . esc_html__('Invalid date', 'wp-mixcloud-archives') . '</span>';
        }
        
        // AIDEV-NOTE: Use WordPress date_i18n for proper localization support
        $formatted_date = date_i18n(get_option('date_format'), $timestamp);
        $relative_time = human_time_diff($timestamp, current_time('timestamp'));
        
        return sprintf(
            '<time datetime="%s" class="mixcloud-date" title="%s">%s<br><small>%s %s</small></time>',
            esc_attr(date('c', $timestamp)),
            esc_attr($formatted_date),
            esc_html($formatted_date),
            esc_html($relative_time),
            esc_html__('ago', 'wp-mixcloud-archives')
        );
    }
    
    /**
     * AJAX handler for date filtering
     */
    public function ajax_filter_by_date() {
        // AIDEV-NOTE: Enhanced nonce validation with isset check for security
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp-mixcloud-archives')) {
            wp_die(__('Security check failed.', 'wp-mixcloud-archives'));
        }
        
        // AIDEV-NOTE: Basic rate limiting to prevent AJAX abuse
        $user_ip = $this->get_client_ip();
        $rate_limit_key = 'mixcloud_ajax_limit_' . md5($user_ip);
        $current_requests = get_transient($rate_limit_key);
        
        if ($current_requests && $current_requests >= 30) { // 30 requests per 5 minutes
            wp_send_json_error(array(
                'message' => __('Rate limit exceeded. Please wait before making more requests.', 'wp-mixcloud-archives')
            ));
        }
        
        // Increment request count
        $current_requests = $current_requests ? $current_requests + 1 : 1;
        set_transient($rate_limit_key, $current_requests, 300); // 5 minutes
        
        // Get parameters from AJAX request
        $account = sanitize_text_field($_POST['account']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // AIDEV-NOTE: Validate date format (YYYY-MM-DD) for enhanced security
        if (!empty($start_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            wp_send_json_error(array(
                'message' => __('Invalid start date format. Use YYYY-MM-DD.', 'wp-mixcloud-archives')
            ));
        }
        if (!empty($end_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            wp_send_json_error(array(
                'message' => __('Invalid end date format. Use YYYY-MM-DD.', 'wp-mixcloud-archives')
            ));
        }
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        $lazy_load = isset($_POST['lazy_load']) ? ($_POST['lazy_load'] === 'true') : true;
        $mini_player = isset($_POST['mini_player']) ? ($_POST['mini_player'] === 'true') : true;
        
        // Validate account parameter
        if (empty($account)) {
            wp_send_json_error(array(
                'message' => __('Account parameter is required.', 'wp-mixcloud-archives')
            ));
        }
        
        // AIDEV-NOTE: Validate account name follows Mixcloud username rules (alphanumeric, underscore, hyphen)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $account)) {
            wp_send_json_error(array(
                'message' => __('Invalid account name. Only letters, numbers, underscores, and hyphens are allowed.', 'wp-mixcloud-archives')
            ));
        }
        
        // Set reasonable limits
        $limit = max(1, min($limit, 50));
        
        // Get cloudcasts from API with enhanced caching
        $cache_args = array(
            'limit'    => $limit,
            'metadata' => true,
        );
        
        // Try multi-tier cache first
        $cloudcasts_data = $this->get_cached_cloudcasts($account, $cache_args);
        
        if (false === $cloudcasts_data) {
            $cloudcasts_data = $this->get_api_handler()->get_user_cloudcasts($account, $cache_args);
            
            // Store in multi-tier cache if successful
            if (!is_wp_error($cloudcasts_data)) {
                $this->set_cached_cloudcasts($account, $cache_args, $cloudcasts_data);
            }
        }
        
        // Handle API errors with user-friendly messages
        if (is_wp_error($cloudcasts_data)) {
            $error_html = $this->generate_user_friendly_error($cloudcasts_data, 'ajax');
            wp_send_json_error(array(
                'message' => strip_tags($cloudcasts_data->get_error_message()),
                'html' => $error_html,
                'error_code' => $cloudcasts_data->get_error_code()
            ));
        }
        
        // Filter cloudcasts by custom date range
        if (!empty($start_date) || !empty($end_date)) {
            $cloudcasts_data['data'] = $this->filter_cloudcasts_by_custom_dates(
                $cloudcasts_data['data'], 
                $start_date, 
                $end_date
            );
        }
        
        // Prepare options for HTML generation
        $options = array(
            'lazy_load'   => $lazy_load,
            'mini_player' => $mini_player,
            'show_date_filter' => false, // Don't include filter in AJAX response
            'show_social' => true, // Include social sharing buttons
        );
        
        // Generate only the table content (not the full container)
        if (empty($cloudcasts_data['data'])) {
            $table_html = '<tr><td colspan="6" class="mixcloud-archives-empty-row">' . 
                         esc_html(sprintf(__('No cloudcasts found for the selected date range.', 'wp-mixcloud-archives'))) . 
                         '</td></tr>';
        } else {
            $table_html = '';
            foreach ($cloudcasts_data['data'] as $cloudcast) {
                $table_html .= $this->generate_cloudcast_html($cloudcast, $options);
            }
        }
        
        // Send successful response
        wp_send_json_success(array(
            'html' => $table_html,
            'count' => count($cloudcasts_data['data']),
            'message' => sprintf(
                _n(
                    'Found %d cloudcast for the selected date range.',
                    'Found %d cloudcasts for the selected date range.',
                    count($cloudcasts_data['data']),
                    'wp-mixcloud-archives'
                ),
                count($cloudcasts_data['data'])
            )
        ));
    }
    
    /**
     * AJAX handler for pagination
     */
    public function ajax_paginate() {
        // AIDEV-NOTE: Enhanced nonce validation with isset check for security
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp-mixcloud-archives')) {
            wp_die(__('Security check failed.', 'wp-mixcloud-archives'));
        }
        
        // AIDEV-NOTE: Basic rate limiting to prevent AJAX abuse
        $user_ip = $this->get_client_ip();
        $rate_limit_key = 'mixcloud_ajax_limit_' . md5($user_ip);
        $current_requests = get_transient($rate_limit_key);
        
        if ($current_requests && $current_requests >= 30) { // 30 requests per 5 minutes
            wp_send_json_error(array(
                'message' => __('Rate limit exceeded. Please wait before making more requests.', 'wp-mixcloud-archives')
            ));
        }
        
        // Increment request count
        $current_requests = $current_requests ? $current_requests + 1 : 1;
        set_transient($rate_limit_key, $current_requests, 300); // 5 minutes
        
        // Get parameters from AJAX request
        $account = sanitize_text_field($_POST['account']);
        $page = max(1, absint($_POST['page']));
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // AIDEV-NOTE: Validate date format (YYYY-MM-DD) for enhanced security
        if (!empty($start_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            wp_send_json_error(array(
                'message' => __('Invalid start date format. Use YYYY-MM-DD.', 'wp-mixcloud-archives')
            ));
        }
        if (!empty($end_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            wp_send_json_error(array(
                'message' => __('Invalid end date format. Use YYYY-MM-DD.', 'wp-mixcloud-archives')
            ));
        }
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 100;
        $lazy_load = isset($_POST['lazy_load']) ? ($_POST['lazy_load'] === 'true') : true;
        $mini_player = isset($_POST['mini_player']) ? ($_POST['mini_player'] === 'true') : true;
        
        // Validate account parameter
        if (empty($account)) {
            wp_send_json_error(array(
                'message' => __('Account parameter is required.', 'wp-mixcloud-archives')
            ));
        }
        
        // AIDEV-NOTE: Validate account name follows Mixcloud username rules (alphanumeric, underscore, hyphen)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $account)) {
            wp_send_json_error(array(
                'message' => __('Invalid account name. Only letters, numbers, underscores, and hyphens are allowed.', 'wp-mixcloud-archives')
            ));
        }
        
        // Set reasonable limits
        $per_page = max(1, min($per_page, 50));
        $limit = max(1, min($limit, 100));
        
        // Get cloudcasts from API with enhanced caching
        $cache_args = array(
            'limit'    => $limit,
            'metadata' => true,
        );
        
        // Try multi-tier cache first
        $cloudcasts_data = $this->get_cached_cloudcasts($account, $cache_args);
        
        if (false === $cloudcasts_data) {
            $cloudcasts_data = $this->get_api_handler()->get_user_cloudcasts($account, $cache_args);
            
            // Store in multi-tier cache if successful
            if (!is_wp_error($cloudcasts_data)) {
                $this->set_cached_cloudcasts($account, $cache_args, $cloudcasts_data);
            }
        }
        
        // Handle API errors with user-friendly messages
        if (is_wp_error($cloudcasts_data)) {
            $error_html = $this->generate_user_friendly_error($cloudcasts_data, 'ajax');
            wp_send_json_error(array(
                'message' => strip_tags($cloudcasts_data->get_error_message()),
                'html' => $error_html,
                'error_code' => $cloudcasts_data->get_error_code()
            ));
        }
        
        // Filter cloudcasts by custom date range if provided
        if (!empty($start_date) || !empty($end_date)) {
            $cloudcasts_data['data'] = $this->filter_cloudcasts_by_custom_dates(
                $cloudcasts_data['data'], 
                $start_date, 
                $end_date
            );
        }
        
        // Calculate pagination information
        $total_items = count($cloudcasts_data['data']);
        $pagination_info = $this->calculate_pagination($total_items, $page, $per_page);
        
        // Apply pagination to cloudcasts data
        $paginated_data = $this->paginate_cloudcasts($cloudcasts_data['data'], $page, $per_page);
        
        // Prepare options for HTML generation
        $options = array(
            'lazy_load'   => $lazy_load,
            'mini_player' => $mini_player,
            'show_date_filter' => false, // Don't include filter in AJAX response
            'show_pagination' => false,  // Don't include pagination in table response
            'show_social' => true, // Include social sharing buttons
        );
        
        // Generate table content and pagination HTML separately
        if (empty($paginated_data)) {
            $table_html = '<tr><td colspan="6" class="mixcloud-archives-empty-row">' . 
                         esc_html(__('No cloudcasts found on this page.', 'wp-mixcloud-archives')) . 
                         '</td></tr>';
        } else {
            $table_html = '';
            foreach ($paginated_data as $cloudcast) {
                $table_html .= $this->generate_cloudcast_html($cloudcast, $options);
            }
        }
        
        // Generate pagination HTML
        $pagination_html = $this->generate_pagination_html($pagination_info, $account);
        
        // Send successful response
        wp_send_json_success(array(
            'table_html' => $table_html,
            'pagination_html' => $pagination_html,
            'pagination_info' => $pagination_info,
            'count' => count($paginated_data),
            'message' => sprintf(
                __('Showing page %d of %d', 'wp-mixcloud-archives'),
                $pagination_info['current_page'],
                $pagination_info['total_pages']
            )
        ));
    }
    
    /**
     * Generate user-friendly error message based on error type
     *
     * @param WP_Error $error The error object
     * @param string   $context Context where error occurred (shortcode, ajax, etc.)
     * @return string  HTML formatted error message
     */
    private function generate_user_friendly_error($error, $context = 'general') {
        if (!is_wp_error($error)) {
            return $this->generate_generic_error_html(__('An unexpected error occurred.', 'wp-mixcloud-archives'));
        }
        
        $error_code = $error->get_error_code();
        $error_data = $error->get_error_data();
        
        // Determine user-friendly message based on error code
        switch ($error_code) {
            case 'invalid_username':
                $message = __('Please check the Mixcloud username and try again.', 'wp-mixcloud-archives');
                break;
                
            case 'api_error_404':
                $message = __('The requested Mixcloud account was not found. Please check the username.', 'wp-mixcloud-archives');
                break;
                
            case 'api_error_429':
                $message = __('Too many requests to Mixcloud. Please wait a moment and try again.', 'wp-mixcloud-archives');
                break;
                
            case 'api_error_500':
            case 'api_error_502':
            case 'api_error_503':
            case 'api_error_504':
                $message = __('Mixcloud is temporarily unavailable. Please try again in a few minutes.', 'wp-mixcloud-archives');
                break;
                
            case 'api_request_failed':
                if (isset($error_data['attempt']) && $error_data['attempt'] >= 3) {
                    $message = __('Unable to connect to Mixcloud after multiple attempts. Please check your internet connection and try again later.', 'wp-mixcloud-archives');
                } else {
                    $message = __('Connection to Mixcloud failed. Please try again.', 'wp-mixcloud-archives');
                }
                break;
                
            case 'invalid_json_response':
                $message = __('Received invalid data from Mixcloud. Please try again.', 'wp-mixcloud-archives');
                break;
                
            case 'invalid_response_structure':
                $message = __('Mixcloud returned unexpected data format. Please contact support if this persists.', 'wp-mixcloud-archives');
                break;
                
            default:
                // Check if error is retryable
                if (isset($error_data['retryable']) && $error_data['retryable']) {
                    $message = __('A temporary error occurred. Please try again in a few moments.', 'wp-mixcloud-archives');
                } else {
                    $message = __('An error occurred while loading Mixcloud data. Please try again later.', 'wp-mixcloud-archives');
                }
                break;
        }
        
        // Add retry suggestion for AJAX contexts
        if ($context === 'ajax' && isset($error_data['retryable']) && $error_data['retryable']) {
            $message .= ' ' . __('You can try refreshing the page or clicking the button again.', 'wp-mixcloud-archives');
        }
        
        return $this->generate_error_html($message, $error_code, $context);
    }
    
    /**
     * Generate error HTML with optional retry button
     *
     * @param string $message Error message
     * @param string $error_code Error code for debugging
     * @param string $context Context where error occurred
     * @return string HTML error message
     */
    private function generate_error_html($message, $error_code = '', $context = 'general') {
        $html = '<div class="mixcloud-archives-error" role="alert">';
        $html .= '<div class="mixcloud-error-icon">⚠️</div>';
        $html .= '<div class="mixcloud-error-content">';
        $html .= '<p class="mixcloud-error-message">' . esc_html($message) . '</p>';
        
        // Add retry button for AJAX contexts
        if ($context === 'ajax') {
            $html .= '<button type="button" class="mixcloud-error-retry" onclick="location.reload()">';
            $html .= esc_html__('Try Again', 'wp-mixcloud-archives');
            $html .= '</button>';
        }
        
        // Add debug info in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($error_code)) {
            $html .= '<details class="mixcloud-error-debug">';
            $html .= '<summary>' . esc_html__('Debug Information', 'wp-mixcloud-archives') . '</summary>';
            $html .= '<code>Error Code: ' . esc_html($error_code) . '</code>';
            $html .= '</details>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate generic error HTML
     *
     * @param string $message Error message
     * @return string HTML error message
     */
    private function generate_generic_error_html($message) {
        return '<div class="mixcloud-archives-error" role="alert">' . 
               esc_html($message) . 
               '</div>';
    }
    
    /**
     * Get client IP address safely
     *
     * @return string Client IP address
     */
    private function get_client_ip() {
        // AIDEV-NOTE: Safely get client IP with proper validation and sanitization
        $ip_keys = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP', 
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR even if it's private (better than nothing)
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }
    
    /**
     * Generate fallback content when API is unavailable
     *
     * @param string $account Mixcloud account name
     * @param array  $options Display options
     * @return string HTML fallback content
     */
    private function generate_fallback_content($account, $options = array()) {
        $html = '<div class="mixcloud-archives-fallback">';
        $html .= '<div class="mixcloud-fallback-icon">🎵</div>';
        $html .= '<div class="mixcloud-fallback-content">';
        $html .= '<h4>' . esc_html(sprintf(__('Mixcloud Archives for %s', 'wp-mixcloud-archives'), $account)) . '</h4>';
        $html .= '<p>' . esc_html__('We\'re having trouble loading the latest mixes right now.', 'wp-mixcloud-archives') . '</p>';
        $html .= '<p>' . sprintf(
            /* translators: %s: Mixcloud account URL */
            __('You can visit the <a href="%s" target="_blank" rel="noopener">Mixcloud profile directly</a> to hear the latest tracks.', 'wp-mixcloud-archives'),
            esc_url('https://www.mixcloud.com/' . $account . '/')
        ) . '</p>';
        
        // Add cached data if available
        $cached_data = $this->get_cached_fallback_data($account);
        if (!empty($cached_data)) {
            $html .= '<div class="mixcloud-fallback-cached">';
            $html .= '<h5>' . esc_html__('Recently Cached Tracks:', 'wp-mixcloud-archives') . '</h5>';
            $html .= '<ul class="mixcloud-fallback-list">';
            
            foreach (array_slice($cached_data, 0, 5) as $track) {
                $html .= '<li>';
                $html .= '<a href="' . esc_url($track['url']) . '" target="_blank" rel="noopener">';
                $html .= esc_html($track['name']);
                $html .= '</a>';
                if (!empty($track['created_time'])) {
                    $html .= ' <small>(' . esc_html(date_i18n(get_option('date_format'), strtotime($track['created_time']))) . ')</small>';
                }
                $html .= '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        $html .= '<button type="button" class="mixcloud-fallback-retry" onclick="location.reload()">';
        $html .= esc_html__('Try Loading Again', 'wp-mixcloud-archives');
        $html .= '</button>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get cached fallback data for when API is unavailable
     *
     * @param string $username Mixcloud username
     * @return array Cached cloudcast data
     */
    private function get_cached_fallback_data($username) {
        // Try to get from recent successful cache
        $cache_key = 'mixcloud_fallback_' . md5($username);
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        // If no recent cache, try to get from any existing cache
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 ORDER BY option_name DESC 
                 LIMIT 1",
                '%mixcloud_cloudcasts_' . md5(serialize(array('username' => $username))) . '%'
            ),
            ARRAY_A
        );
        
        if (!empty($results)) {
            $data = maybe_unserialize($results[0]['option_value']);
            if (isset($data['data']) && is_array($data['data'])) {
                // Cache this as fallback data for future use
                set_transient($cache_key, $data['data'], DAY_IN_SECONDS);
                return $data['data'];
            }
        }
        
        return array();
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
        
        // Optimize database queries
        add_filter('wp_mixcloud_archives_cache_expiration', array($this, 'optimize_cache_expiration'));
        
        // Add resource hints for external dependencies
        add_action('wp_head', array($this, 'add_resource_hints'));
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
     * Optimize cache expiration based on content type
     *
     * @param int $expiration Default expiration time
     * @return int Optimized expiration time
     */
    public function optimize_cache_expiration($expiration) {
        // Use longer cache for API responses during peak hours
        $current_hour = (int) current_time('H');
        
        // Peak hours (9 AM - 6 PM): longer cache
        if ($current_hour >= 9 && $current_hour <= 18) {
            return $expiration * 2; // Double the cache time during peak hours
        }
        
        return $expiration;
    }
    
    /**
     * Add resource hints for external dependencies
     */
    public function add_resource_hints() {
        global $post;
        
        // Only add hints if shortcode is present
        if (is_singular() && isset($post->post_content) && has_shortcode($post->post_content, 'mixcloud_archives')) {
            // Preconnect to Mixcloud API and CDN
            echo '<link rel="preconnect" href="https://api.mixcloud.com" crossorigin>' . "\n";
            echo '<link rel="preconnect" href="https://www.mixcloud.com" crossorigin>' . "\n";
            echo '<link rel="dns-prefetch" href="//thumbnailer.mixcloud.com">' . "\n";
        }
    }
    
    /**
     * Get cached cloudcasts using multi-tier caching strategy
     *
     * @param string $account Account name
     * @param array  $args    Request arguments
     * @return array|false    Cached data or false if not found
     */
    private function get_cached_cloudcasts($account, $args = array()) {
        // L1 Cache: Object cache (fastest, in-memory)
        $l1_key = $this->get_cache_key($account, $args, 'l1');
        $cached_data = wp_cache_get($l1_key, 'mixcloud_archives');
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        // L2 Cache: Transients (database-backed)
        $l2_key = $this->get_cache_key($account, $args, 'l2');
        $cached_data = get_transient($l2_key);
        
        if (false !== $cached_data) {
            // Warm L1 cache for next request
            wp_cache_set($l1_key, $cached_data, 'mixcloud_archives', 300); // 5 minutes in object cache
            return $cached_data;
        }
        
        return false;
    }
    
    /**
     * Set cached cloudcasts using multi-tier caching strategy
     *
     * @param string $account Account name
     * @param array  $args    Request arguments
     * @param array  $data    Data to cache
     */
    private function set_cached_cloudcasts($account, $args, $data) {
        if (empty($data) || is_wp_error($data)) {
            return;
        }
        
        $l1_key = $this->get_cache_key($account, $args, 'l1');
        $l2_key = $this->get_cache_key($account, $args, 'l2');
        
        // Determine cache expiration based on data freshness and time of day
        $expiration = $this->calculate_optimal_cache_expiration($data);
        
        // L1 Cache: Object cache (5 minutes)
        wp_cache_set($l1_key, $data, 'mixcloud_archives', 300);
        
        // L2 Cache: Transients (longer duration)
        set_transient($l2_key, $data, $expiration);
        
        // Store cache metadata for monitoring
        $this->update_cache_stats($account, $expiration);
    }
    
    /**
     * Generate optimized cache key
     *
     * @param string $account Account name
     * @param array  $args    Request arguments
     * @param string $tier    Cache tier (l1, l2)
     * @return string         Cache key
     */
    private function get_cache_key($account, $args = array(), $tier = 'l2') {
        // Normalize account name
        $account = strtolower(trim($account));
        
        // Create consistent args hash
        $args_hash = md5(serialize($args));
        
        // Include tier and version for cache busting
        $key_parts = array(
            'mixcloud',
            $tier,
            'v3', // Cache version for invalidation
            $account,
            $args_hash
        );
        
        return implode('_', $key_parts);
    }
    
    /**
     * Calculate optimal cache expiration based on content and timing
     *
     * @param array $data Cached data
     * @return int        Expiration time in seconds
     */
    private function calculate_optimal_cache_expiration($data) {
        $base_expiration = 3600; // 1 hour default
        
        // Adjust based on data recency
        if (isset($data['data']) && is_array($data['data'])) {
            $newest_timestamp = 0;
            
            foreach ($data['data'] as $item) {
                if (isset($item['created_time'])) {
                    $timestamp = strtotime($item['created_time']);
                    $newest_timestamp = max($newest_timestamp, $timestamp);
                }
            }
            
            if ($newest_timestamp > 0) {
                $age_hours = (time() - $newest_timestamp) / 3600;
                
                // Recent content (< 24 hours): shorter cache
                if ($age_hours < 24) {
                    $base_expiration = 1800; // 30 minutes
                }
                // Older content (> 7 days): longer cache
                elseif ($age_hours > 168) {
                    $base_expiration = 7200; // 2 hours
                }
            }
        }
        
        // Apply time-based optimization filter
        return apply_filters('wp_mixcloud_archives_cache_expiration', $base_expiration);
    }
    
    /**
     * Update cache statistics for monitoring
     *
     * @param string $account    Account name
     * @param int    $expiration Cache expiration time
     */
    private function update_cache_stats($account, $expiration) {
        $stats_key = 'mixcloud_cache_stats';
        $stats = get_transient($stats_key);
        
        if (false === $stats) {
            $stats = array(
                'hits' => 0,
                'misses' => 0,
                'accounts' => array(),
                'last_updated' => time(),
            );
        }
        
        $stats['misses']++;
        $stats['accounts'][$account] = array(
            'last_cached' => time(),
            'expiration' => $expiration,
        );
        $stats['last_updated'] = time();
        
        // Store stats for 24 hours
        set_transient($stats_key, $stats, DAY_IN_SECONDS);
    }
    
    /**
     * Get cache statistics for admin review
     *
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        $stats = get_transient('mixcloud_cache_stats');
        
        if (false === $stats) {
            return array(
                'hits' => 0,
                'misses' => 0,
                'hit_ratio' => 0,
                'accounts' => array(),
                'last_updated' => 0,
            );
        }
        
        $total_requests = $stats['hits'] + $stats['misses'];
        $stats['hit_ratio'] = $total_requests > 0 ? ($stats['hits'] / $total_requests) * 100 : 0;
        
        return $stats;
    }
    
    /**
     * Warm cache for frequently accessed accounts
     */
    public function warm_cache() {
        $popular_accounts = $this->get_popular_accounts();
        
        foreach ($popular_accounts as $account) {
            // Warm cache in background if possible
            if (function_exists('wp_schedule_single_event')) {
                wp_schedule_single_event(time() + rand(1, 300), 'mixcloud_warm_cache_single', array($account));
            } else {
                // Fallback: warm immediately (may slow down response)
                $this->warm_single_account_cache($account);
            }
        }
    }
    
    /**
     * Get list of popular accounts for cache warming
     *
     * @return array Account names
     */
    private function get_popular_accounts() {
        $stats = $this->get_cache_stats();
        $accounts = array();
        
        if (isset($stats['accounts']) && is_array($stats['accounts'])) {
            // Sort by last cached time (most recent first)
            uasort($stats['accounts'], function($a, $b) {
                return $b['last_cached'] - $a['last_cached'];
            });
            
            // Return top 5 accounts
            $accounts = array_keys(array_slice($stats['accounts'], 0, 5, true));
        }
        
        return $accounts;
    }
    
    /**
     * Warm cache for a single account
     *
     * @param string $account Account name
     */
    public function warm_single_account_cache($account) {
        if (empty($account)) {
            return;
        }
        
        // Fetch fresh data to warm cache
        $args = array(
            'limit' => 20,
            'metadata' => true,
        );
        
        $this->get_api_handler()->get_user_cloudcasts($account, $args);
    }
    
    /**
     * Clear all plugin caches
     *
     * @param string $account Optional specific account to clear
     * @return bool Success status
     */
    public function clear_all_caches($account = '') {
        global $wpdb;
        
        try {
            // AIDEV-NOTE: Clear transients only (simplified caching approach)
            if (!empty($account)) {
                // Clear specific account cache
                $like_pattern = '%mixcloud_cloudcasts%' . strtolower(trim($account)) . '%';
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name LIKE '_transient_%'",
                        $like_pattern
                    )
                );
            } else {
                // Clear all plugin transients
                $wpdb->query(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mixcloud%' OR option_name LIKE '_transient_timeout_mixcloud%'"
                );
            }
            
            return true;
        } catch (Exception $e) {
            // AIDEV-NOTE: Log error for debugging
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('WP Mixcloud Archives: Failed to clear cache - ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Enhanced shortcode handler with better error handling
     */
    private function enhanced_shortcode_handler($atts, $content = '') {
        try {
            return $this->shortcode_mixcloud_archives($atts, $content);
        } catch (Exception $e) {
            // Log unexpected PHP exceptions
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('WP Mixcloud Archives Exception: ' . $e->getMessage());
            }
            
            return $this->generate_generic_error_html(
                __('An unexpected error occurred while loading Mixcloud data.', 'wp-mixcloud-archives')
            );
        }
    }
}

// AIDEV-NOTE: Initialize plugin instance
WP_Mixcloud_Archives::get_instance();