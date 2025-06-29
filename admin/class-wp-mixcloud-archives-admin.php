<?php
/**
 * Admin functionality for WP Mixcloud Archives
 *
 * @package WPMixcloudArchives
 */

// AIDEV-NOTE: Prevent direct access to file for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all admin functionality for the plugin
 */
class WP_Mixcloud_Archives_Admin {
    
    /**
     * Plugin options group name
     */
    const OPTION_GROUP = 'wp_mixcloud_archives_settings';
    
    /**
     * Plugin options section name
     */
    const OPTION_SECTION = 'wp_mixcloud_archives_main_section';
    
    /**
     * Plugin menu slug
     */
    const MENU_SLUG = 'wp-mixcloud-archives';
    
    /**
     * Instance of the main plugin class
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            __('Mixcloud Archives Settings', 'wp-mixcloud-archives'),
            __('Mixcloud Archives', 'wp-mixcloud-archives'),
            'manage_options',
            self::MENU_SLUG,
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Initialize settings using WordPress Settings API
     */
    public function init_settings() {
        // AIDEV-NOTE: Register settings group for all plugin options
        register_setting(
            self::OPTION_GROUP,
            'wp_mixcloud_archives_options',
            array($this, 'sanitize_settings')
        );
        
        // Add main settings section
        add_settings_section(
            self::OPTION_SECTION,
            __('Mixcloud Configuration', 'wp-mixcloud-archives'),
            array($this, 'render_section_description'),
            self::MENU_SLUG
        );
        
        // Add Mixcloud account field
        add_settings_field(
            'mixcloud_account',
            __('Mixcloud Account', 'wp-mixcloud-archives'),
            array($this, 'render_account_field'),
            self::MENU_SLUG,
            self::OPTION_SECTION
        );
        
        // Add default days field
        add_settings_field(
            'default_days',
            __('Default Days to Show', 'wp-mixcloud-archives'),
            array($this, 'render_days_field'),
            self::MENU_SLUG,
            self::OPTION_SECTION
        );
        
        // Add API status section
        add_settings_section(
            'wp_mixcloud_archives_api_section',
            __('API Status', 'wp-mixcloud-archives'),
            array($this, 'render_api_section_description'),
            self::MENU_SLUG
        );
        
        // AIDEV-TODO: Add cache management field after implementation
    }
    
    /**
     * Sanitize and validate settings input
     *
     * @param array $input Raw settings input
     * @return array       Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // AIDEV-NOTE: Sanitize Mixcloud account username
        if (isset($input['mixcloud_account'])) {
            $account = sanitize_text_field($input['mixcloud_account']);
            // Remove @ symbol if present and validate username format
            $account = ltrim($account, '@');
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $account)) {
                $sanitized['mixcloud_account'] = $account;
            } else {
                add_settings_error(
                    'wp_mixcloud_archives_options',
                    'invalid_account',
                    __('Invalid Mixcloud account name. Please use only letters, numbers, underscores, and hyphens.', 'wp-mixcloud-archives')
                );
                $sanitized['mixcloud_account'] = '';
            }
        }
        
        // Sanitize default days
        if (isset($input['default_days'])) {
            $days = absint($input['default_days']);
            if ($days > 0 && $days <= 365) {
                $sanitized['default_days'] = $days;
            } else {
                add_settings_error(
                    'wp_mixcloud_archives_options',
                    'invalid_days',
                    __('Default days must be between 1 and 365.', 'wp-mixcloud-archives')
                );
                $sanitized['default_days'] = 30; // Default fallback
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Render main settings section description
     */
    public function render_section_description() {
        echo '<p>' . esc_html__('Configure your Mixcloud account settings for the archives display.', 'wp-mixcloud-archives') . '</p>';
    }
    
    /**
     * Render API section description
     */
    public function render_api_section_description() {
        echo '<p>' . esc_html__('Monitor the status of your Mixcloud API connection.', 'wp-mixcloud-archives') . '</p>';
        $this->render_api_status();
    }
    
    /**
     * Render Mixcloud account field
     */
    public function render_account_field() {
        $options = get_option('wp_mixcloud_archives_options', array());
        $account = isset($options['mixcloud_account']) ? $options['mixcloud_account'] : '';
        
        echo '<input type="text" id="mixcloud_account" name="wp_mixcloud_archives_options[mixcloud_account]" value="' . esc_attr($account) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Enter your Mixcloud username (without the @ symbol).', 'wp-mixcloud-archives') . '</p>';
    }
    
    /**
     * Render default days field
     */
    public function render_days_field() {
        $options = get_option('wp_mixcloud_archives_options', array());
        $days = isset($options['default_days']) ? $options['default_days'] : 30;
        
        echo '<input type="number" id="default_days" name="wp_mixcloud_archives_options[default_days]" value="' . esc_attr($days) . '" min="1" max="365" class="small-text" />';
        echo '<p class="description">' . esc_html__('Default number of days to show in archives (1-365).', 'wp-mixcloud-archives') . '</p>';
    }
    
    /**
     * Render API status monitoring
     */
    public function render_api_status() {
        $options = get_option('wp_mixcloud_archives_options', array());
        $account = isset($options['mixcloud_account']) ? $options['mixcloud_account'] : '';
        
        if (empty($account)) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('Please configure your Mixcloud account first.', 'wp-mixcloud-archives') . '</p></div>';
            return;
        }
        
        // AIDEV-NOTE: Test API connection with configured account
        $api_handler = $this->plugin->get_api_handler();
        $user_info = $api_handler->get_user_info($account);
        
        if (is_wp_error($user_info)) {
            echo '<div class="notice notice-error inline">';
            echo '<p><strong>' . esc_html__('API Connection Failed:', 'wp-mixcloud-archives') . '</strong></p>';
            echo '<p>' . esc_html($user_info->get_error_message()) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-success inline">';
            echo '<p><strong>' . esc_html__('API Connection Successful', 'wp-mixcloud-archives') . '</strong></p>';
            echo '<p>' . sprintf(
                /* translators: %1$s: User display name, %2$s: Username */
                esc_html__('Connected to: %1$s (@%2$s)', 'wp-mixcloud-archives'),
                esc_html($user_info['name']),
                esc_html($user_info['username'])
            ) . '</p>';
            if (!empty($user_info['cloudcast_count'])) {
                echo '<p>' . sprintf(
                    /* translators: %d: Number of cloudcasts */
                    esc_html__('Total cloudcasts: %d', 'wp-mixcloud-archives'),
                    absint($user_info['cloudcast_count'])
                ) . '</p>';
            }
            echo '</div>';
        }
        
        // AIDEV-TODO: Add cache clear button after cache management implementation
        echo '<p><button type="button" class="button" onclick="location.reload();">' . esc_html__('Refresh Status', 'wp-mixcloud-archives') . '</button></p>';
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wp-mixcloud-archives'));
        }
        
        // AIDEV-NOTE: Add security headers for admin pages to prevent XSS and clickjacking
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::MENU_SLUG);
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2><?php esc_html_e('Usage Instructions', 'wp-mixcloud-archives'); ?></h2>
                <p><?php esc_html_e('Use the following shortcode to display your Mixcloud archives:', 'wp-mixcloud-archives'); ?></p>
                
                <h3><?php esc_html_e('Basic Usage', 'wp-mixcloud-archives'); ?></h3>
                <code>[mixcloud_archives account="your-username"]</code>
                
                <h3><?php esc_html_e('Available Parameters', 'wp-mixcloud-archives'); ?></h3>
                <div style="margin: 15px 0;">
                    <h4><?php esc_html_e('Required', 'wp-mixcloud-archives'); ?></h4>
                    <ul style="margin-left: 20px;">
                        <li><strong>account</strong> - <?php esc_html_e('Mixcloud username (required)', 'wp-mixcloud-archives'); ?></li>
                    </ul>
                    
                    <h4><?php esc_html_e('Display Options', 'wp-mixcloud-archives'); ?></h4>
                    <ul style="margin-left: 20px;">
                        <li><strong>limit</strong> - <?php esc_html_e('Number of shows to fetch (1-50, default: 10)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>days</strong> - <?php esc_html_e('Show archives from last X days (default: 30)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>start_date</strong> - <?php esc_html_e('Show archives from date (YYYY-MM-DD)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>end_date</strong> - <?php esc_html_e('Show archives until date (YYYY-MM-DD)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>per_page</strong> - <?php esc_html_e('Items per page (1-50, default: 10)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>page</strong> - <?php esc_html_e('Starting page number (default: 1)', 'wp-mixcloud-archives'); ?></li>
                    </ul>
                    
                    <h4><?php esc_html_e('Interface Options', 'wp-mixcloud-archives'); ?></h4>
                    <ul style="margin-left: 20px;">
                        <li><strong>mini_player</strong> - <?php esc_html_e('Use compact players (yes/no, default: yes)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>lazy_load</strong> - <?php esc_html_e('Enable lazy loading (yes/no, default: yes)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>show_date_filter</strong> - <?php esc_html_e('Show date filter controls (yes/no, default: yes)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>show_pagination</strong> - <?php esc_html_e('Show pagination controls (yes/no, default: yes)', 'wp-mixcloud-archives'); ?></li>
                        <li><strong>show_social</strong> - <?php esc_html_e('Show social sharing buttons (yes/no, default: yes)', 'wp-mixcloud-archives'); ?></li>
                    </ul>
                </div>
                
                <h3><?php esc_html_e('Example Usage', 'wp-mixcloud-archives'); ?></h3>
                <div style="background: #f1f1f1; padding: 15px; border-radius: 4px; margin: 10px 0;">
                    <p><strong><?php esc_html_e('Basic display:', 'wp-mixcloud-archives'); ?></strong><br>
                    <code>[mixcloud_archives account="NowWaveRadio"]</code></p>
                    
                    <p><strong><?php esc_html_e('Show 20 items with full-size players:', 'wp-mixcloud-archives'); ?></strong><br>
                    <code>[mixcloud_archives account="username" limit="20" mini_player="no"]</code></p>
                    
                    <p><strong><?php esc_html_e('Recent shows (last 7 days):', 'wp-mixcloud-archives'); ?></strong><br>
                    <code>[mixcloud_archives account="username" days="7"]</code></p>
                    
                    <p><strong><?php esc_html_e('Specific date range:', 'wp-mixcloud-archives'); ?></strong><br>
                    <code>[mixcloud_archives account="username" start_date="2024-01-01" end_date="2024-01-31"]</code></p>
                    
                    <p><strong><?php esc_html_e('Minimal interface:', 'wp-mixcloud-archives'); ?></strong><br>
                    <code>[mixcloud_archives account="username" show_date_filter="no" show_social="no"]</code></p>
                </div>
                
                <p><em><?php esc_html_e('For complete documentation, see the plugin documentation files.', 'wp-mixcloud-archives'); ?></em></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our settings page
        if ('settings_page_' . self::MENU_SLUG !== $hook) {
            return;
        }
        
        // AIDEV-TODO: Enqueue admin CSS and JS files after creation
        /*
        wp_enqueue_style(
            'wp-mixcloud-archives-admin',
            WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            WP_MIXCLOUD_ARCHIVES_VERSION
        );
        
        wp_enqueue_script(
            'wp-mixcloud-archives-admin',
            WP_MIXCLOUD_ARCHIVES_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            WP_MIXCLOUD_ARCHIVES_VERSION,
            true
        );
        */
    }
    
    /**
     * Get plugin options with defaults
     *
     * @return array Plugin options
     */
    public function get_options() {
        $defaults = array(
            'mixcloud_account' => '',
            'default_days'     => 30,
        );
        
        return wp_parse_args(get_option('wp_mixcloud_archives_options', array()), $defaults);
    }
}