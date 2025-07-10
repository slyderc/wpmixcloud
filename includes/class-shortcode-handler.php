<?php
/**
 * Shortcode Handler Class
 *
 * Handles shortcode registration and processing for WP Mixcloud Archives
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles shortcode registration and processing
 */
class WP_Mixcloud_Archives_Shortcode_Handler {
    
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
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('mixcloud_archives', array($this, 'handle_mixcloud_archives_shortcode'));
    }
    
    /**
     * Handle mixcloud_archives shortcode
     *
     * @param array  $atts    Shortcode attributes
     * @param string $content Shortcode content
     * @return string         HTML output
     */
    public function handle_mixcloud_archives_shortcode($atts, $content = '') {
        // Parse and validate shortcode attributes
        $validated_atts = $this->parse_and_validate_attributes($atts);
        
        // Handle validation errors
        if (is_wp_error($validated_atts)) {
            return '<div class="mixcloud-archives-error">' . 
                   esc_html($validated_atts->get_error_message()) . 
                   '</div>';
        }
        
        // Get cache manager and check for cache clearing
        $cache_manager = $this->plugin->get_cache_manager();
        $this->handle_cache_clearing($cache_manager, $validated_atts['account']);
        
        // Fetch cloudcasts data
        $cloudcasts_data = $this->fetch_cloudcasts_data($validated_atts);
        
        // Handle API errors
        if (is_wp_error($cloudcasts_data)) {
            return $this->plugin->get_html_generator()->generate_user_friendly_error($cloudcasts_data, 'shortcode');
        }
        
        // Apply date filtering
        $filtered_cloudcasts = $this->apply_date_filtering($cloudcasts_data, $validated_atts);
        
        // Generate and return HTML output
        return $this->generate_shortcode_output($filtered_cloudcasts, $validated_atts);
    }
    
    /**
     * Parse and validate shortcode attributes
     *
     * @param array $atts Raw shortcode attributes
     * @return array|WP_Error Validated attributes or error
     */
    private function parse_and_validate_attributes($atts) {
        // Parse attributes with defaults
        $parsed_atts = shortcode_atts(array(
            'account'          => '',
            'days'             => 0, // 0 = show all shows (no date filtering)
            'limit'            => 10,
            'lazy_load'        => 'yes',
            'mini_player'      => 'yes',
            'show_date_filter' => 'yes',
            'start_date'       => '',
            'end_date'         => '',
            'show_social'      => 'yes',
        ), $atts, 'mixcloud_archives');
        
        // Validate required account parameter
        if (empty($parsed_atts['account'])) {
            return new WP_Error(
                'missing_account',
                __('Account parameter is required for mixcloud_archives shortcode.', 'wp-mixcloud-archives')
            );
        }
        
        // Sanitize and validate individual attributes
        $validated = array();
        $validated['account'] = sanitize_text_field($parsed_atts['account']);
        $validated['days'] = max(0, min(absint($parsed_atts['days']), 365));
        $validated['limit'] = 0; // Remove limit to fetch all shows
        $validated['lazy_load'] = ($parsed_atts['lazy_load'] === 'yes');
        $validated['mini_player'] = ($parsed_atts['mini_player'] === 'yes');
        $validated['show_date_filter'] = ($parsed_atts['show_date_filter'] === 'yes');
        $validated['show_social'] = ($parsed_atts['show_social'] === 'yes');
        
        // Validate and sanitize date parameters
        $validated['start_date'] = $this->validate_date_parameter($parsed_atts['start_date']);
        $validated['end_date'] = $this->validate_date_parameter($parsed_atts['end_date']);
        
        // Check date range logic
        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            if (strtotime($validated['start_date']) > strtotime($validated['end_date'])) {
                return new WP_Error(
                    'invalid_date_range',
                    __('End date must be after start date.', 'wp-mixcloud-archives')
                );
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate date parameter format
     *
     * @param string $date Date string to validate
     * @return string      Validated date or empty string
     */
    private function validate_date_parameter($date) {
        if (empty($date)) {
            return '';
        }
        
        $sanitized = sanitize_text_field($date);
        
        // Validate YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sanitized)) {
            // Additional validation to ensure it's a real date
            $timestamp = strtotime($sanitized);
            if ($timestamp !== false && $timestamp > 0) {
                return $sanitized;
            }
        }
        
        return '';
    }
    
    /**
     * Handle cache clearing if requested
     *
     * @param object $cache_manager Cache manager instance
     * @param string $account       Account name
     */
    private function handle_cache_clearing($cache_manager, $account) {
        // AIDEV-NOTE: Allow cache clearing via URL parameter for testing
        if (isset($_GET['clear_mixcloud_cache']) && $_GET['clear_mixcloud_cache'] === '1') {
            $cache_manager->clear_user_cache($account);
            $cache_manager->clear_all_cache();
        }
    }
    
    /**
     * Fetch cloudcasts data from API with caching
     *
     * @param array $atts Validated shortcode attributes
     * @return array|WP_Error Cloudcasts data or error
     */
    private function fetch_cloudcasts_data($atts) {
        $cache_args = array(
            'limit'    => $atts['limit'],
            'metadata' => true,
        );
        
        $cache_manager = $this->plugin->get_cache_manager();
        $api_handler = $this->plugin->get_api_handler();
        
        // Try cache first
        $cloudcasts_data = $cache_manager->get_cached_cloudcasts($atts['account'], $cache_args);
        
        if (false === $cloudcasts_data) {
            // Fetch from API
            $cloudcasts_data = $api_handler->get_user_cloudcasts($atts['account'], $cache_args);
            
            // Cache successful responses
            if (!is_wp_error($cloudcasts_data)) {
                $cache_manager->set_cached_cloudcasts($atts['account'], $cache_args, $cloudcasts_data);
            }
        }
        
        return $cloudcasts_data;
    }
    
    /**
     * Apply date filtering to cloudcasts data
     *
     * @param array $cloudcasts_data Raw cloudcasts data
     * @param array $atts            Validated attributes
     * @return array                 Filtered cloudcasts data
     */
    private function apply_date_filtering($cloudcasts_data, $atts) {
        if (empty($cloudcasts_data['data'])) {
            return $cloudcasts_data;
        }
        
        // Apply custom date range filtering (takes priority)
        if (!empty($atts['start_date']) || !empty($atts['end_date'])) {
            $cloudcasts_data['data'] = $this->filter_cloudcasts_by_custom_dates(
                $cloudcasts_data['data'], 
                $atts['start_date'], 
                $atts['end_date']
            );
        }
        // Apply days-based filtering
        elseif ($atts['days'] > 0 && $atts['days'] < 365) {
            $cloudcasts_data['data'] = $this->filter_cloudcasts_by_days(
                $cloudcasts_data['data'], 
                $atts['days']
            );
        }
        // If days = 0, show all shows (no date filtering)
        
        return $cloudcasts_data;
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
     * Filter cloudcasts by number of days
     *
     * @param array $cloudcasts Array of cloudcast data
     * @param int   $days       Number of days to filter
     * @return array            Filtered cloudcasts
     */
    private function filter_cloudcasts_by_days($cloudcasts, $days) {
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
     * Generate final shortcode output with debug info if requested
     *
     * @param array $cloudcasts_data Processed cloudcasts data
     * @param array $atts            Validated attributes
     * @return string                Final HTML output
     */
    private function generate_shortcode_output($cloudcasts_data, $atts) {
        // Prepare display options
        $options = array(
            'lazy_load'        => $atts['lazy_load'],
            'mini_player'      => $atts['mini_player'],
            'show_date_filter' => $atts['show_date_filter'],
            'show_social'      => $atts['show_social'],
            'account'          => $atts['account'],
            'current_start_date' => $atts['start_date'],
            'current_end_date'   => $atts['end_date'],
        );
        
        // Generate and return HTML content
        $html_generator = $this->plugin->get_html_generator();
        return $html_generator->generate_shortcode_html($cloudcasts_data, $atts['account'], $options);
    }
}