<?php
/**
 * AJAX Handler Class
 *
 * Handles all AJAX requests and responses for WP Mixcloud Archives
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles AJAX requests and responses
 */
class WP_Mixcloud_Archives_AJAX_Handler {
    
    /**
     * Reference to main plugin instance
     *
     * @var WP_Mixcloud_Archives
     */
    private $plugin;
    
    /**
     * Rate limit settings
     *
     * @var array
     */
    private $rate_limit_settings = array(
        'requests_per_window' => 30,
        'window_duration' => 300, // 5 minutes
    );
    
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
        // Register AJAX handlers for date filtering
        add_action('wp_ajax_mixcloud_filter_by_date', array($this, 'ajax_filter_by_date'));
        add_action('wp_ajax_nopriv_mixcloud_filter_by_date', array($this, 'ajax_filter_by_date'));
    }
    
    /**
     * AJAX handler for date filtering
     */
    public function ajax_filter_by_date() {
        // Verify nonce for security
        if (!$this->verify_ajax_nonce()) {
            wp_die(__('Security check failed.', 'wp-mixcloud-archives'));
        }
        
        // Check rate limiting
        if (!$this->check_rate_limit()) {
            wp_send_json_error(array(
                'message' => __('Rate limit exceeded. Please wait before making more requests.', 'wp-mixcloud-archives')
            ));
        }
        
        // Get and validate request parameters
        $params = $this->get_validated_request_params();
        if (is_wp_error($params)) {
            wp_send_json_error(array(
                'message' => $params->get_error_message()
            ));
        }
        
        // Fetch and filter cloudcasts data
        $cloudcasts_data = $this->fetch_filtered_cloudcasts($params);
        
        // Handle API errors
        if (is_wp_error($cloudcasts_data)) {
            $this->send_ajax_error_response($cloudcasts_data);
        }
        
        // Generate and send successful response
        $this->send_ajax_success_response($cloudcasts_data, $params);
    }
    
    /**
     * Verify AJAX nonce
     *
     * @return bool True if nonce is valid
     */
    private function verify_ajax_nonce() {
        return isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp-mixcloud-archives');
    }
    
    /**
     * Check rate limiting for AJAX requests
     *
     * @return bool True if request is allowed
     */
    private function check_rate_limit() {
        $user_ip = $this->get_client_ip();
        $rate_limit_key = 'mixcloud_ajax_limit_' . md5($user_ip);
        $current_requests = get_transient($rate_limit_key);
        
        if ($current_requests && $current_requests >= $this->rate_limit_settings['requests_per_window']) {
            return false;
        }
        
        // Increment request count
        $current_requests = $current_requests ? $current_requests + 1 : 1;
        set_transient($rate_limit_key, $current_requests, $this->rate_limit_settings['window_duration']);
        
        return true;
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
     * Get and validate request parameters
     *
     * @return array|WP_Error Validated parameters or error
     */
    private function get_validated_request_params() {
        // Get parameters from request
        $account = isset($_POST['account']) ? sanitize_text_field($_POST['account']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        $lazy_load = isset($_POST['lazy_load']) ? ($_POST['lazy_load'] === 'true') : true;
        $mini_player = isset($_POST['mini_player']) ? ($_POST['mini_player'] === 'true') : true;
        
        // Validate account parameter
        if (empty($account)) {
            return new WP_Error('missing_account', __('Account parameter is required.', 'wp-mixcloud-archives'));
        }
        
        // AIDEV-NOTE: Validate account name follows Mixcloud username rules (alphanumeric, underscore, hyphen)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $account)) {
            return new WP_Error(
                'invalid_account',
                __('Invalid account name. Only letters, numbers, underscores, and hyphens are allowed.', 'wp-mixcloud-archives')
            );
        }
        
        // Validate date formats
        if (!empty($start_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            return new WP_Error(
                'invalid_start_date',
                __('Invalid start date format. Use YYYY-MM-DD.', 'wp-mixcloud-archives')
            );
        }
        if (!empty($end_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            return new WP_Error(
                'invalid_end_date',
                __('Invalid end date format. Use YYYY-MM-DD.', 'wp-mixcloud-archives')
            );
        }
        
        // Set reasonable limits
        $limit = max(1, min($limit, 50));
        
        return array(
            'account' => $account,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'limit' => $limit,
            'lazy_load' => $lazy_load,
            'mini_player' => $mini_player,
        );
    }
    
    /**
     * Fetch filtered cloudcasts data
     *
     * @param array $params Request parameters
     * @return array|WP_Error Cloudcasts data or error
     */
    private function fetch_filtered_cloudcasts($params) {
        $cache_args = array(
            'limit'    => $params['limit'],
            'metadata' => true,
        );
        
        // Get managers
        $cache_manager = $this->plugin->get_cache_manager();
        $api_handler = $this->plugin->get_api_handler();
        
        // Try multi-tier cache first
        $cloudcasts_data = $cache_manager->get_cached_cloudcasts($params['account'], $cache_args);
        
        if (false === $cloudcasts_data) {
            $cloudcasts_data = $api_handler->get_user_cloudcasts($params['account'], $cache_args);
            
            // Store in multi-tier cache if successful
            if (!is_wp_error($cloudcasts_data)) {
                $cache_manager->set_cached_cloudcasts($params['account'], $cache_args, $cloudcasts_data);
            }
        }
        
        // Apply date filtering if successful
        if (!is_wp_error($cloudcasts_data) && (!empty($params['start_date']) || !empty($params['end_date']))) {
            $cloudcasts_data['data'] = $this->filter_cloudcasts_by_date_range(
                $cloudcasts_data['data'], 
                $params['start_date'], 
                $params['end_date']
            );
        }
        
        return $cloudcasts_data;
    }
    
    /**
     * Filter cloudcasts by date range
     *
     * @param array  $cloudcasts Array of cloudcast data
     * @param string $start_date Start date in YYYY-MM-DD format
     * @param string $end_date   End date in YYYY-MM-DD format
     * @return array             Filtered cloudcasts
     */
    private function filter_cloudcasts_by_date_range($cloudcasts, $start_date = '', $end_date = '') {
        $filtered = array();
        
        // Convert dates to timestamps
        $start_timestamp = !empty($start_date) ? strtotime($start_date . ' 00:00:00') : 0;
        $end_timestamp = !empty($end_date) ? strtotime($end_date . ' 23:59:59') : PHP_INT_MAX;
        
        // If neither date is provided, return all
        if (!$start_timestamp && $end_timestamp === PHP_INT_MAX) {
            return $cloudcasts;
        }
        
        foreach ($cloudcasts as $cloudcast) {
            $created_timestamp = strtotime($cloudcast['created_time']);
            
            if ($created_timestamp >= $start_timestamp && $created_timestamp <= $end_timestamp) {
                $filtered[] = $cloudcast;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Send AJAX error response
     *
     * @param WP_Error $error Error object
     */
    private function send_ajax_error_response($error) {
        $html_generator = $this->plugin->get_html_generator();
        $error_html = $html_generator->generate_user_friendly_error($error, 'ajax');
        
        wp_send_json_error(array(
            'message' => strip_tags($error->get_error_message()),
            'html' => $error_html,
            'error_code' => $error->get_error_code()
        ));
    }
    
    /**
     * Send AJAX success response
     *
     * @param array $cloudcasts_data Cloudcasts data
     * @param array $params          Request parameters
     */
    private function send_ajax_success_response($cloudcasts_data, $params) {
        $html_generator = $this->plugin->get_html_generator();
        
        // Prepare options for HTML generation
        $options = array(
            'lazy_load'   => $params['lazy_load'],
            'mini_player' => $params['mini_player'],
            'show_date_filter' => false, // Don't include filter in AJAX response
            'show_social' => true, // Include social sharing buttons
            'account' => $params['account'],
        );
        
        // Generate HTML for cloudcasts
        if (empty($cloudcasts_data['data'])) {
            $html = '<div class="mixcloud-archives-empty-message">' . 
                    esc_html__('No cloudcasts found for the selected date range.', 'wp-mixcloud-archives') . 
                    '</div>';
        } else {
            $html = '';
            foreach ($cloudcasts_data['data'] as $cloudcast) {
                $html .= $html_generator->generate_cloudcast_html($cloudcast, $options);
            }
        }
        
        // Send successful response
        wp_send_json_success(array(
            'html' => $html,
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
     * Clear rate limiting data
     *
     * Used during plugin deactivation
     */
    public function clear_rate_limit_data() {
        global $wpdb;
        
        // AIDEV-NOTE: Remove all rate limiting transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mixcloud_ajax_limit_%' 
             OR option_name LIKE '_transient_timeout_mixcloud_ajax_limit_%'"
        );
    }
}