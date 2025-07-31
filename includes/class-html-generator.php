<?php
/**
 * HTML Generator Class
 *
 * Handles all HTML output generation for WP Mixcloud Archives
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles HTML generation for all plugin output
 */
class WP_Mixcloud_Archives_HTML_Generator {
    
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
    }
    
    /**
     * Generate HTML output for shortcode
     *
     * @param array  $cloudcasts_data Cloudcasts data from API
     * @param string $account         Mixcloud account name
     * @param array  $options         Display options
     * @return string                 HTML output
     */
    public function generate_shortcode_html($cloudcasts_data, $account, $options = array()) {
        if (empty($cloudcasts_data['data'])) {
            return '<div class="mixcloud-archives-empty">' . 
                   esc_html(sprintf(__('No cloudcasts found for account "%s".', 'wp-mixcloud-archives'), $account)) . 
                   '</div>';
        }
        
        $html = '<div class="mixcloud-archives-container" data-account="' . esc_attr($account) . '">';
        
        // Filter tabs
        $html .= $this->generate_filter_tabs_html($cloudcasts_data, $options);
        
        // AIDEV-NOTE: List layout for Mixcloud-style interface
        $html .= '<div class="mixcloud-archives-list">';
        
        foreach ($cloudcasts_data['data'] as $cloudcast) {
            $html .= $this->generate_cloudcast_html($cloudcast, $options);
        }
        
        $html .= '</div>'; // .mixcloud-archives-list
        
        // Modal container for player popups
        $html .= $this->generate_player_modal_html();
        
        $html .= '</div>'; // .mixcloud-archives-container
        
        return $html;
    }
    
    /**
     * Generate HTML for a single cloudcast
     *
     * @param array $cloudcast Cloudcast data
     * @param array $options   Display options
     * @return string          HTML output for cloudcast
     */
    public function generate_cloudcast_html($cloudcast, $options = array()) {
        $html = '<div class="mixcloud-list-item" data-cloudcast-key="' . esc_attr($cloudcast['key']) . '">';
        
        // Thumbnail with hover play button
        $html .= $this->generate_thumbnail_html($cloudcast);
        
        // Content area with simplified info
        $html .= '<div class="mixcloud-list-content">';
        
        // Title
        $html .= '<div class="mixcloud-list-title">' . esc_html($cloudcast['name']) . '</div>';
        
        // Duration and published time
        $html .= $this->generate_metadata_html($cloudcast);
        
        $html .= '</div>'; // .mixcloud-list-content
        
        // Social sharing buttons
        if (!empty($options['show_social'])) {
            $html .= $this->generate_social_buttons_html($cloudcast);
        }
        
        $html .= '</div>'; // .mixcloud-list-item
        
        return $html;
    }
    
    /**
     * Generate thumbnail HTML with hover play button
     *
     * @param array $cloudcast Cloudcast data
     * @return string          Thumbnail HTML
     */
    private function generate_thumbnail_html($cloudcast) {
        $html = '<div class="mixcloud-list-thumbnail">';
        
        $thumbnail_url = $this->get_thumbnail_url($cloudcast);
        
        if ($thumbnail_url) {
            $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($cloudcast['name']) . '" loading="lazy">';
        } else {
            // Fallback placeholder for missing artwork
            $html .= '<div class="mixcloud-list-thumbnail-fallback"><span class="dashicons dashicons-format-audio"></span></div>';
        }
        
        // Hover play button overlay
        $html .= '<div class="mixcloud-play-overlay">';
        $html .= '<button class="mixcloud-play-button" ';
        $html .= 'data-cloudcast-key="' . esc_attr($cloudcast['key']) . '" ';
        $html .= 'data-cloudcast-url="' . esc_url($cloudcast['url']) . '" ';
        $html .= 'data-cloudcast-name="' . esc_attr($cloudcast['name']) . '" ';
        $html .= 'data-cloudcast-image="' . esc_attr($thumbnail_url) . '" ';
        $html .= 'aria-label="' . esc_attr__('Play', 'wp-mixcloud-archives') . '">';
        $html .= '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        $html .= '<path d="M5 3L19 12L5 21V3Z" fill="white"/>';
        $html .= '</svg>';
        $html .= '<span class="mixcloud-play-text">PLAY</span>';
        $html .= '</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get best available thumbnail URL
     *
     * @param array $cloudcast Cloudcast data
     * @return string|null     Thumbnail URL or null
     */
    private function get_thumbnail_url($cloudcast) {
        if (empty($cloudcast['picture_urls']) || !is_array($cloudcast['picture_urls'])) {
            return null;
        }
        
        // Try sizes in order of preference
        $sizes = array('large', 'medium', 'small');
        
        foreach ($sizes as $size) {
            if (!empty($cloudcast['picture_urls'][$size])) {
                return $cloudcast['picture_urls'][$size];
            }
        }
        
        return null;
    }
    
    /**
     * Generate metadata HTML with duration and published time
     *
     * @param array $cloudcast Cloudcast data
     * @return string          Metadata HTML
     */
    private function generate_metadata_html($cloudcast) {
        $html = '<div class="mixcloud-list-metadata">';
        
        // Duration
        $duration = '';
        if (!empty($cloudcast['audio_length']) && $cloudcast['audio_length'] > 0) {
            $duration = $this->format_duration($cloudcast['audio_length']);
        }
        
        // Published time
        $date_display = $this->get_relative_date_display($cloudcast['created_time']);
        
        if (!empty($duration)) {
            $html .= '<span class="mixcloud-duration">' . esc_html($duration) . '</span>';
            $html .= '<span class="mixcloud-separator">•</span>';
        }
        
        $html .= '<span class="mixcloud-published">' . esc_html($date_display) . '</span>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get relative date display string
     *
     * @param string $created_time Creation timestamp
     * @return string              Relative date display
     */
    private function get_relative_date_display($created_time) {
        $timestamp = strtotime($created_time);
        $days_ago = floor((time() - $timestamp) / (60 * 60 * 24));
        
        if ($days_ago == 0) {
            return __('Today', 'wp-mixcloud-archives');
        } elseif ($days_ago == 1) {
            return __('Yesterday', 'wp-mixcloud-archives');
        } else {
            return sprintf(_n('%d day ago', '%d days ago', $days_ago, 'wp-mixcloud-archives'), $days_ago);
        }
    }
    
    /**
     * Generate duration HTML
     *
     * @param array $cloudcast Cloudcast data
     * @return string          Duration HTML
     */
    private function generate_duration_html($cloudcast) {
        if (empty($cloudcast['audio_length']) || $cloudcast['audio_length'] <= 0) {
            return '';
        }
        
        $duration = $this->format_duration($cloudcast['audio_length']);
        
        $html = '<div class="mixcloud-list-duration">';
        $html .= esc_html($duration);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format duration from seconds
     *
     * @param int $seconds Audio length in seconds
     * @return string      Formatted duration
     */
    private function format_duration($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
    }
    
    /**
     * Generate social sharing buttons HTML
     *
     * @param array $cloudcast Cloudcast data
     * @return string          Social buttons HTML
     */
    private function generate_social_buttons_html($cloudcast) {
        $encoded_url = urlencode($cloudcast['url']);
        $encoded_title = urlencode($cloudcast['name']);
        
        $html = '<div class="mixcloud-list-social">';
        
        // Facebook
        $html .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url . '" target="_blank" rel="noopener" class="mixcloud-social-btn mixcloud-social-facebook" aria-label="' . esc_attr__('Share on Facebook', 'wp-mixcloud-archives') . '">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-brand-facebook"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3" /></svg>';
        $html .= '</a>';
        
        // X (Twitter)
        $html .= '<a href="https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title . '" target="_blank" rel="noopener" class="mixcloud-social-btn mixcloud-social-twitter" aria-label="' . esc_attr__('Share on X', 'wp-mixcloud-archives') . '">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>';
        $html .= '</a>';
        
        // Bluesky
        $html .= '<a href="https://bsky.app/intent/compose?text=' . $encoded_title . '%20' . $encoded_url . '" target="_blank" rel="noopener" class="mixcloud-social-btn mixcloud-social-bluesky" aria-label="' . esc_attr__('Share on Bluesky', 'wp-mixcloud-archives') . '">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-brand-bluesky"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6.335 5.144c-1.654 -1.199 -4.335 -2.127 -4.335 .826c0 .59 .35 4.953 .556 5.661c.713 2.463 3.13 2.75 5.444 2.369c-4.045 .665 -4.889 3.208 -2.667 5.41c1.03 1.018 1.913 1.59 2.667 1.59c2 0 3.134 -2.769 3.5 -3.5c.333 -.667 .5 -1.167 .5 -1.5c0 .333 .167 .833 .5 1.5c.366 .731 1.5 3.5 3.5 3.5c.754 0 1.637 -.571 2.667 -1.59c2.222 -2.203 1.378 -4.746 -2.667 -5.41c2.314 .38 4.73 .094 5.444 -2.369c.206 -.708 .556 -5.072 .556 -5.661c0 -2.953 -2.68 -2.025 -4.335 -.826c-2.293 1.662 -4.76 5.048 -5.665 6.856c-.905 -1.808 -3.372 -5.194 -5.665 -6.856z" /></svg>';
        $html .= '</a>';
        
        // Instagram
        $html .= '<a href="https://www.instagram.com/" target="_blank" rel="noopener" class="mixcloud-social-btn mixcloud-social-instagram" aria-label="' . esc_attr__('Share on Instagram', 'wp-mixcloud-archives') . '" data-share-url="' . esc_attr($cloudcast['url']) . '" data-share-title="' . esc_attr($cloudcast['name']) . '">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.216.598 1.772 1.153a4.908 4.908 0 0 1 1.153 1.772c.247.637.415 1.363.465 2.428.047 1.066.06 1.405.06 4.122 0 2.717-.01 3.056-.06 4.122-.05 1.065-.218 1.79-.465 2.428a4.883 4.883 0 0 1-1.153 1.772 4.915 4.915 0 0 1-1.772 1.153c-.637.247-1.363.415-2.428.465-1.066.047-1.405.06-4.122.06-2.717 0-3.056-.01-4.122-.06-1.065-.05-1.79-.218-2.428-.465a4.89 4.89 0 0 1-1.772-1.153 4.904 4.904 0 0 1-1.153-1.772c-.248-.637-.415-1.363-.465-2.428C2.013 15.056 2 14.717 2 12c0-2.717.01-3.056.06-4.122.05-1.066.217-1.79.465-2.428a4.88 4.88 0 0 1 1.153-1.772A4.897 4.897 0 0 1 5.45 2.525c.638-.248 1.362-.415 2.428-.465C8.944 2.013 9.283 2 12 2zm0 1.802c-2.67 0-2.986.01-4.04.059-.976.045-1.505.207-1.858.344-.466.182-.8.398-1.15.748-.35.35-.566.684-.748 1.15-.137.353-.3.882-.344 1.857-.048 1.055-.058 1.37-.058 4.04 0 2.668.01 2.985.058 4.04.044.975.207 1.504.344 1.856.182.466.399.8.748 1.15.35.35.684.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.04.058 2.67 0 2.986-.01 4.04-.058.976-.045 1.504-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.684.748-1.15.137-.352.3-.882.344-1.857.048-1.054.058-1.37.058-4.04 0-2.668-.01-2.985-.058-4.04-.044-.975-.207-1.504-.344-1.856a3.1 3.1 0 0 0-.748-1.15 3.09 3.09 0 0 0-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.054-.048-1.37-.058-4.04-.058zm0 3.063a5.135 5.135 0 1 1 0 10.27 5.135 5.135 0 0 1 0-10.27zm0 1.802a3.333 3.333 0 1 0 0 6.666 3.333 3.333 0 0 0 0-6.666zm6.538-3.11a1.2 1.2 0 1 1-2.4 0 1.2 1.2 0 0 1 2.4 0z"/></svg>';
        $html .= '</a>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate filter tabs/dropdown HTML
     *
     * @param array $cloudcasts_data Cloudcasts data
     * @param array $options         Display options
     * @return string                Filter HTML
     */
    private function generate_filter_tabs_html($cloudcasts_data, $options) {
        // Extract unique show titles
        $show_counts = $this->extract_show_counts($cloudcasts_data['data']);
        $total_count = count($cloudcasts_data['data']);
        
        
        // Generate custom dropdown HTML
        $html = '<div class="mixcloud-filter-dropdown-container">';
        
        // Custom dropdown
        $html .= '<div class="mixcloud-custom-dropdown" data-current-filter="all">';
        
        // Selected option display
        $html .= '<div class="mixcloud-dropdown-selected" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">';
        $html .= '<span class="mixcloud-dropdown-text">' . sprintf(__('All Shows (%d)', 'wp-mixcloud-archives'), $total_count) . '</span>';
        $html .= '<span class="mixcloud-dropdown-arrow">▼</span>';
        $html .= '</div>';
        
        // Options list
        $html .= '<ul class="mixcloud-dropdown-options" role="listbox" aria-label="' . esc_attr__('Show filter options', 'wp-mixcloud-archives') . '">';
        
        // "All Shows" option (active by default)
        $html .= '<li class="mixcloud-dropdown-option mixcloud-dropdown-option-active" data-value="all" role="option" aria-selected="true">';
        $html .= sprintf(__('All Shows (%d)', 'wp-mixcloud-archives'), $total_count);
        $html .= '</li>';
        
        // Individual show options
        foreach ($show_counts as $title => $count) {
            // Normalize characters for consistent filtering - convert en dash and bullet to regular dash
            $normalized_title = str_replace(['–', '•'], '-', $title);
            $html .= '<li class="mixcloud-dropdown-option" data-value="' . esc_attr($normalized_title) . '" role="option" aria-selected="false">';
            $html .= sprintf('%s (%d)', esc_html($title), $count);
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Extract show counts from cloudcasts
     *
     * @param array $cloudcasts Cloudcasts data
     * @return array            Show titles with counts
     */
    private function extract_show_counts($cloudcasts) {
        $show_counts = array();
        
        foreach ($cloudcasts as $cloudcast) {
            $original_title = $cloudcast['name'];
            $title = $original_title;
            
            // RIGHT-TO-LEFT APPROACH: Work backwards from the end to find dates
            // This is much more reliable since dates are always at the end
            
            // Find the last position of common separators and check if what follows looks like a date
            $separators = ['•', '–', '-', '|', ':'];
            $date_removed = false;
            
            foreach ($separators as $sep) {
                if ($date_removed) break;
                
                $last_pos = strrpos($title, $sep);
                if ($last_pos !== false) {
                    $before_sep = trim(substr($title, 0, $last_pos));
                    $after_sep = trim(substr($title, $last_pos + strlen($sep)));
                    
                    // Check if what's after the separator looks like a date
                    $date_patterns = [
                        '/^(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01])-(20\d{2}|19\d{2})$/',  // MM-DD-YYYY
                        '/^\d{1,2}\/\d{1,2}\/\d{2,4}$/',                                        // M/D/YY
                        '/^\d{4}-\d{1,2}-\d{1,2}$/',                                           // YYYY-MM-DD
                        '/^\d{1,2}\.\d{1,2}\.\d{2,4}$/',                                       // M.D.YY
                        '/^(0?[1-9]|1[0-2])–(0?[1-9]|[12][0-9]|3[01])-(20\d{2}|19\d{2})$/',  // MM–DD-YYYY
                        '/^(20\d{2}|19\d{2})$/'                                                // Just year
                    ];
                    
                    foreach ($date_patterns as $pattern) {
                        if (preg_match($pattern, $after_sep)) {
                            $title = $before_sep;
                            $date_removed = true;
                            break;
                        }
                    }
                }
            }
            
            $title = trim($title);
            
            // Enhanced invisible character detection and cleanup
            // Remove various Unicode whitespace and invisible characters
            $title = preg_replace('/[\x{00A0}\x{1680}\x{2000}-\x{200B}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}\x{200C}\x{200D}]/u', '', $title);
            $title = trim($title);
            
            // Skip empty titles or titles that are too short (likely just dates/numbers)
            // Enhanced empty check for invisible characters
            $is_empty_or_invisible = ($title === '' || strlen($title) < 3 || ctype_space($title) || 
                                    preg_match('/^[\s\p{Z}\p{C}]*$/u', $title) || 
                                    mb_strlen(trim($title), 'UTF-8') === 0);
            
            if ($is_empty_or_invisible) {
                continue;
            }
            
            // Double-check before adding to show_counts (safety net)
            // Enhanced check for invisible characters
            $is_still_empty = ($title === '' || strlen($title) < 3 || ctype_space($title) || 
                             preg_match('/^[\s\p{Z}\p{C}]*$/u', $title) || 
                             mb_strlen(trim($title), 'UTF-8') === 0);
            
            if ($is_still_empty) {
                continue;
            }
            
            if (!isset($show_counts[$title])) {
                $show_counts[$title] = 0;
            }
            $show_counts[$title]++;
        }
        
        // Final cleanup: Remove any empty or invalid titles
        $cleaned_show_counts = array();
        foreach ($show_counts as $title => $count) {
            $is_valid_title = ($title !== '' && strlen($title) >= 3 && !ctype_space($title) && 
                             !preg_match('/^[\s\p{Z}\p{C}]*$/u', $title) && 
                             mb_strlen(trim($title), 'UTF-8') >= 3);
            
            if ($is_valid_title) {
                $cleaned_show_counts[$title] = $count;
            }
        }
        
        $show_counts = $cleaned_show_counts;
        
        // Sort alphabetically
        ksort($show_counts);
        
        // Consolidate entries with different separators (en dash, bullet, etc) into single menu items
        $consolidated_counts = array();
        foreach ($show_counts as $title => $count) {
            // Create normalized key for grouping (convert en dash and bullet to regular dash)
            $normalized_key = str_replace(['–', '•'], '-', $title);
            
            if (!isset($consolidated_counts[$normalized_key])) {
                $consolidated_counts[$normalized_key] = $count;
            } else {
                $consolidated_counts[$normalized_key] += $count;
            }
        }
        
        // Sort consolidated results
        ksort($consolidated_counts);
        
        return $consolidated_counts;
    }
    
    /**
     * Generate player modal HTML
     *
     * @return string Modal HTML
     */
    private function generate_player_modal_html() {
        $html = '<div id="mixcloud-player-modal" class="mixcloud-modal">';
        $html .= '<div class="mixcloud-modal-overlay"></div>';
        $html .= '<div class="mixcloud-modal-content">';
        $html .= '<button class="mixcloud-modal-close" aria-label="' . esc_attr__('Close player', 'wp-mixcloud-archives') . '">';
        $html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        $html .= '<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '<div class="mixcloud-modal-image-container">';
        $html .= '<img class="mixcloud-modal-image" src="" alt="" />';
        $html .= '</div>';
        $html .= '<div class="mixcloud-modal-player-container"></div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate embedded player HTML
     *
     * @param array $cloudcast Cloudcast data
     * @param array $options   Display options
     * @return string          Player HTML
     */
    public function generate_player_html($cloudcast, $options = array()) {
        // Default options
        $defaults = array(
            'lazy_load'   => true,
            'mini_player' => true,
        );
        $options = wp_parse_args($options, $defaults);
        
        // AIDEV-NOTE: Mixcloud embed parameters for better player experience
        $embed_params = array(
            'hide_cover'    => 1,
            'mini'          => $options['mini_player'] ? 1 : 0,
            'light'         => 1,
            'hide_artwork'  => 0,
            'autoplay'      => 0,
        );
        
        // Build embed URL
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
     * Generate user-friendly error message
     *
     * @param WP_Error $error   The error object
     * @param string   $context Context where error occurred
     * @return string           HTML formatted error message
     */
    public function generate_user_friendly_error($error, $context = 'general') {
        if (!is_wp_error($error)) {
            return $this->generate_generic_error_html(__('An unexpected error occurred.', 'wp-mixcloud-archives'));
        }
        
        $error_code = $error->get_error_code();
        $error_data = $error->get_error_data();
        
        // Determine user-friendly message based on error code
        $message = $this->get_error_message_by_code($error_code, $error_data);
        
        // Add retry suggestion for AJAX contexts
        if ($context === 'ajax' && isset($error_data['retryable']) && $error_data['retryable']) {
            $message .= ' ' . __('You can try refreshing the page or clicking the button again.', 'wp-mixcloud-archives');
        }
        
        return $this->generate_error_html($message, $error_code, $context);
    }
    
    /**
     * Get error message by error code
     *
     * @param string $error_code Error code
     * @param array  $error_data Error data
     * @return string            Error message
     */
    private function get_error_message_by_code($error_code, $error_data = array()) {
        switch ($error_code) {
            case 'invalid_username':
                return __('Please check the Mixcloud username and try again.', 'wp-mixcloud-archives');
                
            case 'api_error_404':
                return __('The requested Mixcloud account was not found. Please check the username.', 'wp-mixcloud-archives');
                
            case 'api_error_429':
                return __('Too many requests to Mixcloud. Please wait a moment and try again.', 'wp-mixcloud-archives');
                
            case 'api_error_500':
            case 'api_error_502':
            case 'api_error_503':
            case 'api_error_504':
                return __('Mixcloud is temporarily unavailable. Please try again in a few minutes.', 'wp-mixcloud-archives');
                
            case 'api_request_failed':
                if (isset($error_data['attempt']) && $error_data['attempt'] >= 3) {
                    return __('Unable to connect to Mixcloud after multiple attempts. Please check your internet connection and try again later.', 'wp-mixcloud-archives');
                } else {
                    return __('Connection to Mixcloud failed. Please try again.', 'wp-mixcloud-archives');
                }
                
            case 'invalid_json_response':
                return __('Received invalid data from Mixcloud. Please try again.', 'wp-mixcloud-archives');
                
            case 'invalid_response_structure':
                return __('Mixcloud returned unexpected data format. Please contact support if this persists.', 'wp-mixcloud-archives');
                
            default:
                // Check if error is retryable
                if (isset($error_data['retryable']) && $error_data['retryable']) {
                    return __('A temporary error occurred. Please try again in a few moments.', 'wp-mixcloud-archives');
                } else {
                    return __('An error occurred while loading Mixcloud data. Please try again later.', 'wp-mixcloud-archives');
                }
        }
    }
    
    /**
     * Generate error HTML with optional retry button
     *
     * @param string $message    Error message
     * @param string $error_code Error code for debugging
     * @param string $context    Context where error occurred
     * @return string            HTML error message
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
     * @return string         HTML error message
     */
    private function generate_generic_error_html($message) {
        return '<div class="mixcloud-archives-error" role="alert">' . 
               esc_html($message) . 
               '</div>';
    }
    
    /**
     * Generate fallback content when API is unavailable
     *
     * @param string $account Mixcloud account name
     * @param array  $options Display options
     * @return string         HTML fallback content
     */
    public function generate_fallback_content($account, $options = array()) {
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
        $cached_data = $this->plugin->get_cached_fallback_data($account);
        if (!empty($cached_data)) {
            $html .= $this->generate_cached_tracks_list($cached_data);
        }
        
        $html .= '<button type="button" class="mixcloud-fallback-retry" onclick="location.reload()">';
        $html .= esc_html__('Try Loading Again', 'wp-mixcloud-archives');
        $html .= '</button>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate cached tracks list HTML
     *
     * @param array $cached_data Cached cloudcast data
     * @return string            HTML list
     */
    private function generate_cached_tracks_list($cached_data) {
        $html = '<div class="mixcloud-fallback-cached">';
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
        
        return $html;
    }
}