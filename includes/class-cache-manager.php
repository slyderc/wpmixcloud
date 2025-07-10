<?php
/**
 * Cache Manager Class
 *
 * Handles multi-tier caching for WP Mixcloud Archives
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages caching strategies and operations
 */
class WP_Mixcloud_Archives_Cache_Manager {
    
    /**
     * Reference to main plugin instance
     *
     * @var WP_Mixcloud_Archives
     */
    private $plugin;
    
    /**
     * Cache version for invalidation
     *
     * @var string
     */
    private $cache_version = 'v3';
    
    /**
     * Cache group for object cache
     *
     * @var string
     */
    private $cache_group = 'mixcloud_archives';
    
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
        // Hook into cron for cache warming
        add_action('mixcloud_warm_cache_single', array($this, 'warm_single_account_cache'));
    }
    
    /**
     * Get cached cloudcasts using multi-tier caching strategy
     *
     * @param string $account Account name
     * @param array  $args    Request arguments
     * @return array|false    Cached data or false if not found
     */
    public function get_cached_cloudcasts($account, $args = array()) {
        // L1 Cache: Object cache (fastest, in-memory)
        $l1_key = $this->get_cache_key($account, $args, 'l1');
        $cached_data = wp_cache_get($l1_key, $this->cache_group);
        
        if (false !== $cached_data) {
            $this->update_cache_hit_stats();
            return $cached_data;
        }
        
        // L2 Cache: Transients (database-backed)
        $l2_key = $this->get_cache_key($account, $args, 'l2');
        $cached_data = get_transient($l2_key);
        
        if (false !== $cached_data) {
            // Warm L1 cache for next request
            wp_cache_set($l1_key, $cached_data, $this->cache_group, 300); // 5 minutes in object cache
            $this->update_cache_hit_stats();
            return $cached_data;
        }
        
        $this->update_cache_miss_stats();
        return false;
    }
    
    /**
     * Set cached cloudcasts using multi-tier caching strategy
     *
     * @param string $account Account name
     * @param array  $args    Request arguments
     * @param array  $data    Data to cache
     */
    public function set_cached_cloudcasts($account, $args, $data) {
        if (empty($data) || is_wp_error($data)) {
            return;
        }
        
        $l1_key = $this->get_cache_key($account, $args, 'l1');
        $l2_key = $this->get_cache_key($account, $args, 'l2');
        
        // Determine cache expiration based on data freshness and time of day
        $expiration = $this->calculate_optimal_cache_expiration($data);
        
        // L1 Cache: Object cache (5 minutes)
        wp_cache_set($l1_key, $data, $this->cache_group, 300);
        
        // L2 Cache: Transients (longer duration)
        set_transient($l2_key, $data, $expiration);
        
        // Store cache metadata for monitoring
        $this->update_cache_stats($account, $expiration);
        
        // Store as fallback data
        $this->update_fallback_cache($account, $data);
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
            $this->cache_version,
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
     * Clear all plugin caches
     *
     * @param string $account Optional specific account to clear
     * @return bool           Success status
     */
    public function clear_all_cache($account = '') {
        global $wpdb;
        
        try {
            // Clear object cache
            wp_cache_flush();
            
            // Clear transients
            if (!empty($account)) {
                // Clear specific account cache
                $like_pattern = '%mixcloud%' . strtolower(trim($account)) . '%';
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} 
                         WHERE option_name LIKE %s 
                         AND option_name LIKE '_transient_%'",
                        $like_pattern
                    )
                );
            } else {
                // Clear all plugin transients
                $wpdb->query(
                    "DELETE FROM {$wpdb->options} 
                     WHERE option_name LIKE '_transient_mixcloud%' 
                     OR option_name LIKE '_transient_timeout_mixcloud%'"
                );
            }
            
            // Clear cache statistics
            delete_transient('mixcloud_cache_stats');
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Clear cache for specific user
     *
     * @param string $account Account name
     * @return bool           Success status
     */
    public function clear_user_cache($account) {
        if (empty($account)) {
            return false;
        }
        
        // Use the API handler's method if available
        $api_handler = $this->plugin->get_api_handler();
        if (method_exists($api_handler, 'clear_user_cache')) {
            $api_handler->clear_user_cache($account);
        }
        
        return $this->clear_all_cache($account);
    }
    
    /**
     * Update cache hit statistics
     */
    private function update_cache_hit_stats() {
        $stats = get_transient('mixcloud_cache_stats');
        
        if (false === $stats) {
            $stats = $this->get_default_stats();
        }
        
        $stats['hits']++;
        $stats['last_updated'] = time();
        
        set_transient('mixcloud_cache_stats', $stats, DAY_IN_SECONDS);
    }
    
    /**
     * Update cache miss statistics
     */
    private function update_cache_miss_stats() {
        $stats = get_transient('mixcloud_cache_stats');
        
        if (false === $stats) {
            $stats = $this->get_default_stats();
        }
        
        $stats['misses']++;
        $stats['last_updated'] = time();
        
        set_transient('mixcloud_cache_stats', $stats, DAY_IN_SECONDS);
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
            $stats = $this->get_default_stats();
        }
        
        $stats['accounts'][$account] = array(
            'last_cached' => time(),
            'expiration' => $expiration,
        );
        $stats['last_updated'] = time();
        
        // Store stats for 24 hours
        set_transient($stats_key, $stats, DAY_IN_SECONDS);
    }
    
    /**
     * Get default stats structure
     *
     * @return array Default stats
     */
    private function get_default_stats() {
        return array(
            'hits' => 0,
            'misses' => 0,
            'accounts' => array(),
            'last_updated' => time(),
        );
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
        
        $api_handler = $this->plugin->get_api_handler();
        $data = $api_handler->get_user_cloudcasts($account, $args);
        
        // Cache the data if successful
        if (!is_wp_error($data)) {
            $this->set_cached_cloudcasts($account, $args, $data);
        }
    }
    
    /**
     * Update fallback cache for API failures
     *
     * @param string $account Account name
     * @param array  $data    Cloudcast data
     */
    private function update_fallback_cache($account, $data) {
        if (empty($data['data']) || !is_array($data['data'])) {
            return;
        }
        
        $cache_key = 'mixcloud_fallback_' . md5($account);
        
        // Store only essential data for fallback
        $fallback_data = array_slice($data['data'], 0, 10);
        
        // Keep fallback data for 7 days
        set_transient($cache_key, $fallback_data, WEEK_IN_SECONDS);
    }
    
    /**
     * Get cached fallback data for when API is unavailable
     *
     * @param string $username Mixcloud username
     * @return array           Cached cloudcast data
     */
    public function get_cached_fallback_data($username) {
        // Try to get from recent fallback cache
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
                '%mixcloud_l2_%' . md5($username) . '%'
            ),
            ARRAY_A
        );
        
        if (!empty($results)) {
            $data = maybe_unserialize($results[0]['option_value']);
            if (isset($data['data']) && is_array($data['data'])) {
                // Cache this as fallback data for future use
                $fallback_data = array_slice($data['data'], 0, 10);
                set_transient($cache_key, $fallback_data, DAY_IN_SECONDS);
                return $fallback_data;
            }
        }
        
        return array();
    }
    
    /**
     * Optimize cache expiration based on content type
     *
     * @param int $expiration Default expiration time
     * @return int            Optimized expiration time
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
}