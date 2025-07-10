<?php
/**
 * Mixcloud API Handler Class
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access to file for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all interactions with the Mixcloud API v1
 */
class Mixcloud_API_Handler {
    
    /**
     * API base URL
     */
    const API_BASE_URL = 'https://api.mixcloud.com/';
    
    /**
     * Cache expiration time in seconds (1 hour)
     */
    const CACHE_EXPIRATION = 3600;
    
    /**
     * API timeout in seconds (optimized for performance)
     */
    const API_TIMEOUT = 15;
    
    /**
     * Maximum number of cloudcasts to fetch per request
     */
    const MAX_CLOUDCASTS_LIMIT = 100;
    
    /**
     * Maximum number of retry attempts for failed requests
     */
    const MAX_RETRY_ATTEMPTS = 3;
    
    /**
     * Base delay for exponential backoff (in seconds)
     */
    const RETRY_BASE_DELAY = 1;
    
    /**
     * Connection timeout in seconds (separate from total timeout)
     */
    const CONNECTION_TIMEOUT = 5;
    
    /**
     * Circuit breaker threshold (consecutive failures before temporary disable)
     */
    const CIRCUIT_BREAKER_THRESHOLD = 5;
    
    /**
     * Circuit breaker timeout (seconds to wait before retry)
     */
    const CIRCUIT_BREAKER_TIMEOUT = 300; // 5 minutes
    
    /**
     * Constructor
     */
    public function __construct() {
        // AIDEV-NOTE: Constructor intentionally minimal for singleton pattern compatibility
    }
    
    /**
     * Get cloudcasts for a specific user
     *
     * @param string $username Mixcloud username
     * @param array  $args     Optional arguments (limit, offset, metadata)
     * @return array|WP_Error  Array of cloudcasts or WP_Error on failure
     */
    public function get_user_cloudcasts($username, $args = array()) {
        // AIDEV-NOTE: Sanitize username to prevent injection attacks
        $username = sanitize_text_field($username);
        
        if (empty($username)) {
            return new WP_Error('invalid_username', __('Username cannot be empty.', 'wp-mixcloud-archives'));
        }
        
        // Default arguments
        $defaults = array(
            'limit'    => 20,
            'offset'   => 0,
            'metadata' => true,
        );
        $args = wp_parse_args($args, $defaults);
        
        // AIDEV-NOTE: Allow unlimited fetching when limit is 0, otherwise validate limit
        if ($args['limit'] > 0) {
            $args['limit'] = min(absint($args['limit']), self::MAX_CLOUDCASTS_LIMIT);
        }
        $args['offset'] = absint($args['offset']);
        
        // Check circuit breaker first
        if ($this->is_circuit_breaker_open()) {
            return new WP_Error('circuit_breaker_open', 
                __('API temporarily unavailable due to repeated failures. Please try again later.', 'wp-mixcloud-archives'));
        }
        
        // Check cache first
        $cache_key = $this->get_cache_key($username, $args);
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        // AIDEV-NOTE: Handle unlimited fetching by paginating through all results
        if ($args['limit'] == 0) {
            return $this->get_all_user_cloudcasts($username, $args, $cache_key);
        }
        
        // Build API URL
        $api_url = $this->build_api_url($username, 'cloudcasts', $args);
        
        // Make API request
        $response = $this->make_api_request($api_url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Parse and format response data
        $formatted_data = $this->format_cloudcasts_response($response);
        
        if (is_wp_error($formatted_data)) {
            return $formatted_data;
        }
        
        // Cache the result
        set_transient($cache_key, $formatted_data, self::CACHE_EXPIRATION);
        
        return $formatted_data;
    }
    
    /**
     * Get all user cloudcasts by fetching all pages
     *
     * @param string $username Mixcloud username
     * @param array  $args     Arguments (offset, metadata)
     * @param string $cache_key Cache key for storing results
     * @return array|WP_Error  Array of all cloudcasts or WP_Error on failure
     */
    private function get_all_user_cloudcasts($username, $args, $cache_key) {
        $all_cloudcasts = array();
        $paging_info = array();
        $offset = $args['offset'];
        $page_size = 50; // Use max page size for efficiency
        $max_requests = 20; // Prevent infinite loops (max 1000 shows)
        $request_count = 0;
        
        do {
            // Set current page arguments
            $page_args = $args;
            $page_args['limit'] = $page_size;
            $page_args['offset'] = $offset;
            
            // Build API URL for this page
            $api_url = $this->build_api_url($username, 'cloudcasts', $page_args);
            
            // Make API request
            $response = $this->make_api_request($api_url);
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            // Parse and format response data
            $formatted_data = $this->format_cloudcasts_response($response);
            
            if (is_wp_error($formatted_data)) {
                return $formatted_data;
            }
            
            // Append cloudcasts to our collection
            if (!empty($formatted_data['data'])) {
                $all_cloudcasts = array_merge($all_cloudcasts, $formatted_data['data']);
            }
            
            // Update paging info
            $paging_info = $formatted_data['paging'];
            
            // Calculate next offset
            $offset += $page_size;
            $request_count++;
            
            // AIDEV-NOTE: Debug logging for pagination
        if (isset($_GET['debug_mixcloud']) && $_GET['debug_mixcloud'] === '1') {
            error_log("Mixcloud Debug: Page $request_count - Fetched " . count($formatted_data['data']) . " shows, Total so far: " . count($all_cloudcasts));
            error_log("Mixcloud Debug: Has next page: " . (!empty($paging_info['next']) ? 'YES' : 'NO'));
        }
        
        // Continue if there are more results and we haven't hit our safety limit
        } while (!empty($paging_info['next']) && $request_count < $max_requests);
        
        // Prepare final result
        $result = array(
            'data'       => $all_cloudcasts,
            'paging'     => $paging_info,
            'total'      => count($all_cloudcasts),
            'fetched_at' => current_time('mysql'),
        );
        
        // AIDEV-NOTE: Final debug log
        if (isset($_GET['debug_mixcloud']) && $_GET['debug_mixcloud'] === '1') {
            error_log("Mixcloud Debug: FINAL RESULT - Total shows: " . count($all_cloudcasts) . " in $request_count requests");
        }
        
        // Cache the complete result
        set_transient($cache_key, $result, self::CACHE_EXPIRATION);
        
        return $result;
    }
    
    /**
     * Get user information
     *
     * @param string $username Mixcloud username
     * @return array|WP_Error  User data or WP_Error on failure
     */
    public function get_user_info($username) {
        $username = sanitize_text_field($username);
        
        if (empty($username)) {
            return new WP_Error('invalid_username', __('Username cannot be empty.', 'wp-mixcloud-archives'));
        }
        
        // Check circuit breaker first
        if ($this->is_circuit_breaker_open()) {
            return new WP_Error('circuit_breaker_open', 
                __('API temporarily unavailable due to repeated failures. Please try again later.', 'wp-mixcloud-archives'));
        }
        
        $cache_key = 'mixcloud_user_' . md5($username);
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        $api_url = $this->build_api_url($username);
        $response = $this->make_api_request($api_url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $formatted_data = $this->format_user_response($response);
        
        if (!is_wp_error($formatted_data)) {
            set_transient($cache_key, $formatted_data, self::CACHE_EXPIRATION);
        }
        
        return $formatted_data;
    }
    
    /**
     * Build API URL with parameters
     *
     * @param string $username Mixcloud username
     * @param string $endpoint Optional endpoint (e.g., 'cloudcasts')
     * @param array  $args     Optional query parameters
     * @return string          Complete API URL
     */
    private function build_api_url($username, $endpoint = '', $args = array()) {
        $url = self::API_BASE_URL . $username . '/';
        
        if (!empty($endpoint)) {
            $url .= $endpoint . '/';
        }
        
        $query_params = array();
        
        // Add metadata parameter if requested
        if (!empty($args['metadata'])) {
            $query_params['metadata'] = '1';
        }
        
        // Add limit parameter (skip if limit is 0 to fetch all)
        if (!empty($args['limit'])) {
            $query_params['limit'] = $args['limit'];
        }
        
        // Add offset parameter
        if (!empty($args['offset'])) {
            $query_params['offset'] = $args['offset'];
        }
        
        if (!empty($query_params)) {
            $url = add_query_arg($query_params, $url);
        }
        
        return $url;
    }
    
    /**
     * Make HTTP request to Mixcloud API with retry logic
     *
     * @param string $url API URL
     * @param int    $attempt Current attempt number
     * @return array|WP_Error Response data or WP_Error on failure
     */
    private function make_api_request($url, $attempt = 1) {
        // AIDEV-NOTE: Using WordPress HTTP API with optimized settings for performance
        $request_args = array(
            'timeout'          => self::API_TIMEOUT,
            'connect_timeout'  => self::CONNECTION_TIMEOUT,
            'user-agent'       => $this->get_user_agent(),
            'compress'         => true,
            'decompress'       => true,
            'headers'          => array(
                'Accept'           => 'application/json',
                'Accept-Encoding'  => 'gzip, deflate',
                'Cache-Control'    => 'max-age=0',
            ),
        );
        
        // Add conditional request headers if we have cached ETag or Last-Modified
        $conditional_headers = $this->get_conditional_headers($url);
        if (!empty($conditional_headers)) {
            $request_args['headers'] = array_merge($request_args['headers'], $conditional_headers);
        }
        
        $response = wp_remote_get($url, $request_args);
        
        // Check for HTTP errors
        if (is_wp_error($response)) {
            $error = new WP_Error(
                'api_request_failed',
                sprintf(
                    /* translators: %s: Error message */
                    __('Failed to connect to Mixcloud API: %s', 'wp-mixcloud-archives'),
                    $response->get_error_message()
                ),
                array(
                    'url' => $url,
                    'attempt' => $attempt,
                    'original_error' => $response->get_error_message()
                )
            );
            
            // Log error and update circuit breaker
            $this->log_api_error($error, $url, $attempt);
            $this->record_api_failure();
            
            if ($this->should_retry_error($response) && $attempt < self::MAX_RETRY_ATTEMPTS) {
                $this->wait_for_retry($attempt);
                return $this->make_api_request($url, $attempt + 1);
            }
            
            return $error;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // AIDEV-NOTE: Handle different HTTP status codes appropriately
        if (200 !== $response_code) {
            $error = $this->handle_api_error($response_code, $response_body, $url, $attempt);
            
            // Log error
            $this->log_api_error($error, $url, $attempt);
            
            // Retry for certain status codes
            if ($this->should_retry_status_code($response_code) && $attempt < self::MAX_RETRY_ATTEMPTS) {
                $this->wait_for_retry($attempt);
                return $this->make_api_request($url, $attempt + 1);
            }
            
            return $error;
        }
        
        // Parse JSON response
        $data = json_decode($response_body, true);
        
        if (null === $data) {
            $error = new WP_Error(
                'invalid_json_response',
                __('Invalid JSON response from Mixcloud API.', 'wp-mixcloud-archives'),
                array(
                    'url'          => $url,
                    'response_code' => $response_code,
                    'response_body' => $response_body,
                    'attempt'      => $attempt,
                )
            );
            
            $this->log_api_error($error, $url, $attempt);
            return $error;
        }
        
        // Handle conditional request headers for future optimization
        $this->store_conditional_headers($url, $response);
        
        // Record successful request for circuit breaker
        $this->record_api_success();
        
        // Log successful request if it was retried
        if ($attempt > 1) {
            $this->log_api_success($url, $attempt);
        }
        
        return $data;
    }
    
    /**
     * Handle API error responses
     *
     * @param int    $response_code HTTP response code
     * @param string $response_body Response body
     * @param string $url           Request URL
     * @param int    $attempt       Current attempt number
     * @return WP_Error             Error object
     */
    private function handle_api_error($response_code, $response_body, $url, $attempt = 1) {
        $error_messages = array(
            400 => __('Bad request to Mixcloud API.', 'wp-mixcloud-archives'),
            401 => __('Unauthorized access to Mixcloud API.', 'wp-mixcloud-archives'),
            403 => __('Forbidden access to Mixcloud API.', 'wp-mixcloud-archives'),
            404 => __('User or resource not found on Mixcloud.', 'wp-mixcloud-archives'),
            429 => __('Too many requests to Mixcloud API. Please try again later.', 'wp-mixcloud-archives'),
            500 => __('Mixcloud API server error.', 'wp-mixcloud-archives'),
            503 => __('Mixcloud API service unavailable.', 'wp-mixcloud-archives'),
        );
        
        $error_message = isset($error_messages[$response_code]) 
            ? $error_messages[$response_code]
            : sprintf(
                /* translators: %d: HTTP status code */
                __('Mixcloud API returned status code %d.', 'wp-mixcloud-archives'),
                $response_code
            );
        
        return new WP_Error(
            'api_error_' . $response_code,
            $error_message,
            array(
                'url'           => $url,
                'response_code' => $response_code,
                'response_body' => $response_body,
                'attempt'       => $attempt,
                'retryable'     => $this->should_retry_status_code($response_code),
            )
        );
    }
    
    /**
     * Format cloudcasts response data
     *
     * @param array $response Raw API response
     * @return array|WP_Error Formatted data or WP_Error on failure
     */
    private function format_cloudcasts_response($response) {
        if (!isset($response['data']) || !is_array($response['data'])) {
            return new WP_Error(
                'invalid_response_structure',
                __('Invalid response structure from Mixcloud API.', 'wp-mixcloud-archives')
            );
        }
        
        $cloudcasts = array();
        
        foreach ($response['data'] as $cloudcast) {
            $formatted_cloudcast = $this->format_single_cloudcast($cloudcast);
            if (!is_wp_error($formatted_cloudcast)) {
                $cloudcasts[] = $formatted_cloudcast;
            }
        }
        
        return array(
            'data'       => $cloudcasts,
            'paging'     => isset($response['paging']) ? $response['paging'] : array(),
            'total'      => count($cloudcasts),
            'fetched_at' => current_time('mysql'),
        );
    }
    
    /**
     * Format single cloudcast data
     *
     * @param array $cloudcast Raw cloudcast data
     * @return array|WP_Error  Formatted cloudcast or WP_Error on failure
     */
    private function format_single_cloudcast($cloudcast) {
        // AIDEV-NOTE: Essential fields validation to prevent broken displays
        $required_fields = array('key', 'name', 'url', 'created_time');
        
        foreach ($required_fields as $field) {
            if (!isset($cloudcast[$field])) {
                return new WP_Error(
                    'missing_required_field',
                    sprintf(
                        /* translators: %s: Field name */
                        __('Missing required field "%s" in cloudcast data.', 'wp-mixcloud-archives'),
                        $field
                    )
                );
            }
        }
        
        return array(
            'key'          => sanitize_text_field($cloudcast['key']),
            'name'         => sanitize_text_field($cloudcast['name']),
            'url'          => esc_url_raw($cloudcast['url']),
            'created_time' => sanitize_text_field($cloudcast['created_time']),
            'description'  => isset($cloudcast['description']) ? wp_kses_post($cloudcast['description']) : '',
            'play_count'   => isset($cloudcast['play_count']) ? absint($cloudcast['play_count']) : 0,
            'favorite_count' => isset($cloudcast['favorite_count']) ? absint($cloudcast['favorite_count']) : 0,
            'comment_count' => isset($cloudcast['comment_count']) ? absint($cloudcast['comment_count']) : 0,
            'audio_length' => isset($cloudcast['audio_length']) ? absint($cloudcast['audio_length']) : 0,
            'picture_urls' => isset($cloudcast['pictures']) ? $this->sanitize_picture_urls($cloudcast['pictures']) : array(),
            'tags'         => isset($cloudcast['tags']) ? array_map('sanitize_text_field', $cloudcast['tags']) : array(),
            'user'         => isset($cloudcast['user']) ? $this->format_user_data($cloudcast['user']) : array(),
        );
    }
    
    /**
     * Format user response data
     *
     * @param array $response Raw API response
     * @return array|WP_Error Formatted user data or WP_Error on failure
     */
    private function format_user_response($response) {
        if (!isset($response['username'])) {
            return new WP_Error(
                'invalid_user_response',
                __('Invalid user response from Mixcloud API.', 'wp-mixcloud-archives')
            );
        }
        
        return $this->format_user_data($response);
    }
    
    /**
     * Format user data
     *
     * @param array $user_data Raw user data
     * @return array           Formatted user data
     */
    private function format_user_data($user_data) {
        return array(
            'username'     => isset($user_data['username']) ? sanitize_text_field($user_data['username']) : '',
            'name'         => isset($user_data['name']) ? sanitize_text_field($user_data['name']) : '',
            'url'          => isset($user_data['url']) ? esc_url_raw($user_data['url']) : '',
            'picture_urls' => isset($user_data['pictures']) ? $user_data['pictures'] : array(),
            'city'         => isset($user_data['city']) ? sanitize_text_field($user_data['city']) : '',
            'country'      => isset($user_data['country']) ? sanitize_text_field($user_data['country']) : '',
            'biography'    => isset($user_data['biog']) ? wp_kses_post($user_data['biog']) : '',
            'follower_count' => isset($user_data['follower_count']) ? absint($user_data['follower_count']) : 0,
            'following_count' => isset($user_data['following_count']) ? absint($user_data['following_count']) : 0,
            'cloudcast_count' => isset($user_data['cloudcast_count']) ? absint($user_data['cloudcast_count']) : 0,
        );
    }
    
    
    /**
     * Validate and sanitize picture URLs
     *
     * @param array $pictures Raw pictures array from API
     * @return array Sanitized picture URLs
     */
    private function sanitize_picture_urls($pictures) {
        // AIDEV-NOTE: Validate and sanitize all picture URLs from API response
        if (!is_array($pictures)) {
            return array();
        }
        
        $sanitized = array();
        $valid_sizes = array('small', 'medium', 'large', 'extra_large');
        
        foreach ($pictures as $size => $url) {
            // Only process valid sizes
            if (in_array($size, $valid_sizes, true) && is_string($url)) {
                // Validate URL format and ensure it's from Mixcloud CDN
                $parsed_url = wp_parse_url($url);
                if ($parsed_url && isset($parsed_url['host'])) {
                    // Check if URL is from trusted Mixcloud domains
                    $trusted_domains = array(
                        'thumbnails.mixcloud.com',
                        'thumbnailer.mixcloud.com',
                        'images.mixcloud.com',
                        'is1-ssl.mzstatic.com',  // Apple CDN used by Mixcloud
                        'is2-ssl.mzstatic.com',
                        'is3-ssl.mzstatic.com',
                        'is4-ssl.mzstatic.com',
                        'is5-ssl.mzstatic.com',
                    );
                    
                    if (in_array($parsed_url['host'], $trusted_domains, true)) {
                        $sanitized[$size] = esc_url_raw($url, array('http', 'https'));
                    }
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get user agent string for API requests
     *
     * @return string User agent string
     */
    private function get_user_agent() {
        return sprintf(
            'WP-Mixcloud-Archives/%s (WordPress/%s; %s)',
            WP_MIXCLOUD_ARCHIVES_VERSION,
            get_bloginfo('version'),
            home_url()
        );
    }
    
    /**
     * Clear cache for a specific user
     *
     * @param string $username Mixcloud username
     * @return bool            True on success, false on failure
     */
    public function clear_user_cache($username) {
        $username = sanitize_text_field($username);
        
        if (empty($username)) {
            return false;
        }
        
        // AIDEV-NOTE: Clear both user info and cloudcasts cache
        $user_cache_key = 'mixcloud_user_' . md5($username);
        delete_transient($user_cache_key);
        
        // AIDEV-NOTE: Clear all cloudcasts cache variations for this user
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '%mixcloud_cloudcasts_' . md5(serialize(array('username' => $username))) . '%'
            )
        );
        
        return true;
    }
    
    /**
     * Clear all plugin cache
     *
     * @return bool True on success, false on failure
     */
    public function clear_all_cache() {
        global $wpdb;
        
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mixcloud_%' OR option_name LIKE '_transient_timeout_mixcloud_%'"
        );
        
        return false !== $deleted;
    }
    
    /**
     * Determine if an error should trigger a retry
     *
     * @param WP_Error $error The error object
     * @return bool           True if error is retryable
     */
    private function should_retry_error($error) {
        if (!is_wp_error($error)) {
            return false;
        }
        
        $error_code = $error->get_error_code();
        $retryable_errors = array(
            'http_request_timeout',
            'http_request_failed',
            'http_no_url',
            'http_not_get',
        );
        
        return in_array($error_code, $retryable_errors, true);
    }
    
    /**
     * Determine if a status code should trigger a retry
     *
     * @param int $status_code HTTP status code
     * @return bool            True if status code is retryable
     */
    private function should_retry_status_code($status_code) {
        $retryable_codes = array(
            429, // Too Many Requests
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
            504, // Gateway Timeout
        );
        
        return in_array($status_code, $retryable_codes, true);
    }
    
    /**
     * Wait for retry with exponential backoff
     *
     * @param int $attempt Current attempt number
     */
    private function wait_for_retry($attempt) {
        $delay = self::RETRY_BASE_DELAY * pow(2, $attempt - 1);
        $max_delay = 10; // Maximum 10 seconds
        $delay = min($delay, $max_delay);
        
        // Add some jitter to avoid thundering herd
        $jitter = rand(0, 100) / 100; // 0 to 1 second
        $delay += $jitter;
        
        // AIDEV-NOTE: Use WordPress's built-in sleep function if available
        if (function_exists('wp_sleep')) {
            wp_sleep($delay);
        } else {
            sleep((int) $delay);
        }
    }
    
    /**
     * Log API errors for admin review
     *
     * @param WP_Error $error   The error object
     * @param string   $url     Request URL
     * @param int      $attempt Current attempt number
     */
    private function log_api_error($error, $url, $attempt) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return; // Only log in debug mode
        }
        
        $log_data = array(
            'timestamp'    => current_time('mysql'),
            'error_code'   => $error->get_error_code(),
            'error_message' => $error->get_error_message(),
            'url'          => $url,
            'attempt'      => $attempt,
            'user_agent'   => $this->get_user_agent(),
            'error_data'   => $error->get_error_data(),
        );
        
        // Store in WordPress transient for admin review
        $log_key = 'mixcloud_api_error_' . time() . '_' . rand(1000, 9999);
        set_transient($log_key, $log_data, WEEK_IN_SECONDS);
        
        // Also log to error_log if WP_DEBUG_LOG is enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('WP Mixcloud Archives API Error: ' . wp_json_encode($log_data));
        }
    }
    
    /**
     * Log successful API requests after retry
     *
     * @param string $url     Request URL
     * @param int    $attempt Final successful attempt number
     */
    private function log_api_success($url, $attempt) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return; // Only log in debug mode
        }
        
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'message'   => 'API request succeeded after retry',
            'url'       => $url,
            'attempts'  => $attempt,
        );
        
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('WP Mixcloud Archives API Success: ' . wp_json_encode($log_data));
        }
    }
    
    /**
     * Get recent API error logs for admin review
     *
     * @param int $limit Maximum number of errors to retrieve
     * @return array     Array of error log entries
     */
    public function get_error_logs($limit = 50) {
        global $wpdb;
        
        $transient_prefix = '_transient_mixcloud_api_error_';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 ORDER BY option_name DESC 
                 LIMIT %d",
                $transient_prefix . '%',
                $limit
            ),
            ARRAY_A
        );
        
        $error_logs = array();
        foreach ($results as $result) {
            $log_data = maybe_unserialize($result['option_value']);
            if (is_array($log_data)) {
                $error_logs[] = $log_data;
            }
        }
        
        return $error_logs;
    }
    
    /**
     * Clear error logs
     *
     * @return bool True on success
     */
    public function clear_error_logs() {
        global $wpdb;
        
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mixcloud_api_error_%'"
        );
        
        return false !== $deleted;
    }
    
    /**
     * Check if circuit breaker is open (API temporarily disabled)
     *
     * @return bool True if circuit breaker is open
     */
    private function is_circuit_breaker_open() {
        $failure_count = get_transient('mixcloud_api_failure_count');
        $circuit_open_until = get_transient('mixcloud_api_circuit_open_until');
        
        // If circuit is explicitly opened, check if timeout has passed
        if ($circuit_open_until && $circuit_open_until > time()) {
            return true;
        }
        
        // If timeout has passed, reset the circuit
        if ($circuit_open_until && $circuit_open_until <= time()) {
            delete_transient('mixcloud_api_circuit_open_until');
            delete_transient('mixcloud_api_failure_count');
            return false;
        }
        
        // Check if we've exceeded the failure threshold
        return ($failure_count >= self::CIRCUIT_BREAKER_THRESHOLD);
    }
    
    /**
     * Record an API failure for circuit breaker tracking
     */
    private function record_api_failure() {
        $failure_count = get_transient('mixcloud_api_failure_count');
        $failure_count = ($failure_count === false) ? 1 : $failure_count + 1;
        
        // Store failure count for 1 hour
        set_transient('mixcloud_api_failure_count', $failure_count, HOUR_IN_SECONDS);
        
        // If we've exceeded threshold, open the circuit
        if ($failure_count >= self::CIRCUIT_BREAKER_THRESHOLD) {
            $open_until = time() + self::CIRCUIT_BREAKER_TIMEOUT;
            set_transient('mixcloud_api_circuit_open_until', $open_until, self::CIRCUIT_BREAKER_TIMEOUT);
            
            // Log circuit breaker activation
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log(sprintf('WP Mixcloud Archives: Circuit breaker opened due to %d consecutive failures', $failure_count));
            }
        }
    }
    
    /**
     * Record an API success for circuit breaker tracking
     */
    private function record_api_success() {
        // Reset failure count on successful request
        delete_transient('mixcloud_api_failure_count');
        delete_transient('mixcloud_api_circuit_open_until');
    }
    
    /**
     * Get conditional request headers for caching optimization
     *
     * @param string $url Request URL
     * @return array Conditional headers
     */
    private function get_conditional_headers($url) {
        $headers = array();
        $cache_key = 'mixcloud_headers_' . md5($url);
        $cached_headers = get_transient($cache_key);
        
        if ($cached_headers && is_array($cached_headers)) {
            if (!empty($cached_headers['etag'])) {
                $headers['If-None-Match'] = $cached_headers['etag'];
            }
            if (!empty($cached_headers['last_modified'])) {
                $headers['If-Modified-Since'] = $cached_headers['last_modified'];
            }
        }
        
        return $headers;
    }
    
    /**
     * Store response headers for future conditional requests
     *
     * @param string $url Request URL
     * @param array  $response HTTP response
     */
    private function store_conditional_headers($url, $response) {
        $headers = wp_remote_retrieve_headers($response);
        $cache_key = 'mixcloud_headers_' . md5($url);
        $store_headers = array();
        
        if (isset($headers['etag'])) {
            $store_headers['etag'] = $headers['etag'];
        }
        if (isset($headers['last-modified'])) {
            $store_headers['last_modified'] = $headers['last-modified'];
        }
        
        if (!empty($store_headers)) {
            // Store headers for 24 hours
            set_transient($cache_key, $store_headers, DAY_IN_SECONDS);
        }
    }
    
    /**
     * Enhanced cache key generation with better distribution
     *
     * @param string $username Mixcloud username
     * @param array  $args     Request arguments
     * @return string          Optimized cache key
     */
    private function get_cache_key($username, $args = array()) {
        // Normalize arguments for consistent caching
        $normalized_args = array(
            'username' => strtolower(trim($username)),
            'limit'    => isset($args['limit']) ? (int) $args['limit'] : 20,
            'offset'   => isset($args['offset']) ? (int) $args['offset'] : 0,
            'metadata' => isset($args['metadata']) ? (bool) $args['metadata'] : true,
        );
        
        // Sort args for consistent key generation
        ksort($normalized_args);
        
        // Create more descriptive cache key
        $key_components = array(
            'mixcloud_api',
            'v2', // Version for cache busting if needed
            'cloudcasts',
            md5(serialize($normalized_args))
        );
        
        return implode('_', $key_components);
    }
    
    /**
     * Optimized cache cleanup method
     *
     * @param string $username Optional username to clear specific cache
     * @return bool Success status
     */
    public function clear_optimized_cache($username = '') {
        global $wpdb;
        
        if (!empty($username)) {
            // Clear specific user cache more efficiently
            $username_pattern = strtolower(trim($username));
            $like_pattern = '%mixcloud_api_v2_%' . md5(serialize(array('username' => $username_pattern))) . '%';
            
            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $like_pattern
                )
            );
        } else {
            // Clear all plugin cache
            $deleted = $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mixcloud_%' OR option_name LIKE '_transient_timeout_mixcloud_%'"
            );
        }
        
        return false !== $deleted;
    }
}