# Development Documentation

## Development Environment Setup

### Prerequisites

**Required Software**
- PHP 7.4+ (8.0+ recommended)
- Node.js 16+ and npm
- WordPress development environment
- Git for version control

**Recommended Tools**
- VS Code or PhpStorm
- WordPress CLI (WP-CLI)
- Xdebug for debugging
- Composer for dependency management

### Local Development Setup

1. **Clone Repository**
   ```bash
   git clone [repository-url]
   cd wp-mixcloud-archives
   ```

2. **Install Build Dependencies**
   ```bash
   cd build-tools
   ./setup.sh
   ```

3. **Development Build**
   ```bash
   npm run watch  # For development with file watching
   npm run build  # For production build
   ```

## Project Structure

### Directory Organization

```
wp-mixcloud-archives/
├── wp-mixcloud-archives.php    # Main plugin file
├── readme.txt                  # WordPress.org readme
├── admin/                      # Admin interface
│   └── class-wp-mixcloud-archives-admin.php
├── includes/                   # Core functionality
│   └── class-mixcloud-api.php
├── assets/                     # Frontend assets
│   ├── css/
│   │   ├── style.css          # Source CSS
│   │   └── style.min.css      # Minified CSS
│   └── js/
│       ├── script.js          # Source JavaScript
│       └── script.min.js      # Minified JavaScript
├── build-tools/               # Build system
│   ├── build.js              # Build configuration
│   ├── package.json          # Node dependencies
│   └── setup.sh              # Setup script
├── docs/                      # Documentation
├── templates/                 # Template files (future)
└── dist/                     # Distribution builds
```

### File Responsibilities

**Main Plugin File (`wp-mixcloud-archives.php`)**
- Plugin initialization and singleton pattern
- Hook registration
- Shortcode handling
- AJAX handlers
- Frontend asset management

**Admin Class (`admin/class-wp-mixcloud-archives-admin.php`)**
- WordPress admin integration
- Settings page rendering
- Settings sanitization and validation
- Admin-specific functionality

**API Handler (`includes/class-mixcloud-api.php`)**
- Mixcloud API communication
- Data formatting and validation
- Error handling and retry logic
- Caching implementation

## Architecture Patterns

### Singleton Pattern

The main plugin class uses the singleton pattern for global access:

```php
class WP_Mixcloud_Archives {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
}
```

### Hook-Based Architecture

All functionality is initialized through WordPress hooks:

```php
private function init_hooks() {
    add_action('init', array($this, 'load_textdomain'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('wp_ajax_mixcloud_filter_by_date', array($this, 'ajax_filter_by_date'));
    // ... more hooks
}
```

### Circuit Breaker Pattern

API requests implement circuit breaker pattern for resilience:

```php
private function is_circuit_breaker_open() {
    $failures = get_transient($this->circuit_breaker_key);
    return $failures >= $this->circuit_breaker_threshold;
}
```

## Code Standards

### PHP Standards

**WordPress Coding Standards**
- Follow WordPress PHP Coding Standards
- Use WordPress functions over native PHP when available
- All inputs sanitized, all outputs escaped
- Proper documentation with DocBlocks

**Security Best Practices**
```php
// Input sanitization
$account = sanitize_text_field($_POST['account']);

// Output escaping
echo esc_html($cloudcast['name']);

// Nonce verification
if (!wp_verify_nonce($_POST['nonce'], 'wp-mixcloud-archives')) {
    wp_die(__('Security check failed.', 'wp-mixcloud-archives'));
}

// Capability checks
if (!current_user_can('manage_options')) {
    wp_die(__('Insufficient permissions.', 'wp-mixcloud-archives'));
}
```

### JavaScript Standards

**Modern ES6+ with Fallbacks**
```javascript
// Use modern syntax with polyfills
const playerObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
        if (entry.isIntersecting) {
            initPlayerLoadButton(entry.target);
        }
    });
});

// Graceful degradation
if ('IntersectionObserver' in window) {
    // Use modern API
} else {
    // Fallback for older browsers
}
```

**Error Handling**
```javascript
async function makeAjaxRequest(url, data, options = {}) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: new URLSearchParams(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('AJAX request failed:', error);
        throw error;
    }
}
```

### CSS Standards

**CSS Custom Properties**
```css
:root {
    --onair-primary-color: #314b6d;
    --onair-background: #ffffff;
    --onair-border-radius: 6px;
}

.mixcloud-archives-container {
    background: var(--onair-background);
    border-radius: var(--onair-border-radius);
}
```

**Mobile-First Responsive Design**
```css
/* Mobile first */
.mixcloud-archives-table {
    display: block;
    overflow-x: auto;
}

/* Tablet and up */
@media (min-width: 768px) {
    .mixcloud-archives-table {
        display: table;
        width: 100%;
    }
}
```

## Build System

### Build Configuration

The build system uses Node.js tools for asset processing:

```javascript
// build-tools/build.js
const CONFIG = {
    terserOptions: {
        mangle: { 
            reserved: ['wpMixcloudArchives', 'wpMixcloudArchivesRefresh'] 
        }
    },
    package: {
        include: ['*.php', 'assets/**/*', 'includes/**/*', 'admin/**/*'],
        additionalExcludes: ['BUILD-SYSTEM.md', 'PRD.md', '.taskmaster/**/*']
    }
};
```

### Build Commands

```bash
# Development
npm run watch     # Watch files and rebuild on changes
npm run dev      # Development build (unminified)

# Production
npm run build    # Production build (minified)
npm run package  # Create distribution package

# Maintenance
npm run clean    # Clean generated files
npm run lint     # Code linting
```

### Asset Pipeline

1. **CSS Processing**
   - Autoprefixer for browser compatibility
   - Minification for production
   - Source maps for development

2. **JavaScript Processing**
   - Babel transpilation for older browsers
   - Terser minification with reserved identifiers
   - Source maps for debugging

3. **Package Creation**
   - Automated exclusion of development files
   - Version detection from plugin header
   - ZIP archive creation for distribution

## API Integration

### Mixcloud API Handler

**Base Configuration**
```php
class Mixcloud_API_Handler {
    private $base_url = 'https://api.mixcloud.com';
    private $timeout = 15;
    private $cache_duration = 900; // 15 minutes
}
```

**Request Pattern**
```php
public function get_user_cloudcasts($username, $args = array()) {
    // Build cache key
    $cache_key = $this->build_cache_key($username, $args);
    
    // Try cache first
    $cached_data = $this->get_cached_data($cache_key);
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    // Make API request
    $response = $this->make_api_request($endpoint, $args);
    
    // Cache successful responses
    if (!is_wp_error($response)) {
        $this->cache_data($cache_key, $response);
    }
    
    return $response;
}
```

**Error Handling Strategy**
```php
private function handle_api_error($response_code, $response_body) {
    switch ($response_code) {
        case 404:
            return new WP_Error('api_error_404', __('Account not found.'));
        case 429:
            return new WP_Error('api_error_429', __('Rate limit exceeded.'));
        case 500:
        case 502:
        case 503:
        case 504:
            return new WP_Error('api_error_server', __('Service temporarily unavailable.'));
        default:
            return new WP_Error('api_error_unknown', __('Unknown API error.'));
    }
}
```

## Testing

### Unit Testing

**PHPUnit Tests** (Future Implementation)
```php
class Test_Mixcloud_API extends WP_UnitTestCase {
    public function test_format_single_cloudcast() {
        $raw_data = array(
            'key' => 'test-key',
            'name' => 'Test Show',
            'url' => 'https://www.mixcloud.com/test/test-show/',
            'created_time' => '2024-01-01T00:00:00Z'
        );
        
        $formatted = $this->api_handler->format_single_cloudcast($raw_data);
        
        $this->assertArrayHasKey('key', $formatted);
        $this->assertEquals('test-key', $formatted['key']);
    }
}
```

### Integration Testing

**Manual Testing Checklist**
- [ ] Plugin activation/deactivation
- [ ] Settings page functionality
- [ ] Shortcode rendering
- [ ] AJAX date filtering
- [ ] Pagination controls
- [ ] Player loading
- [ ] Mobile responsiveness
- [ ] Browser compatibility

### Performance Testing

**Load Testing Scenarios**
- High traffic page loads
- Multiple shortcodes per page
- Large archive displays
- Concurrent AJAX requests
- Cache invalidation scenarios

## Security Implementation

### Input Validation

```php
// Date format validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    wp_send_json_error(array(
        'message' => __('Invalid date format. Use YYYY-MM-DD.')
    ));
}

// Account name validation
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $account)) {
    wp_send_json_error(array(
        'message' => __('Invalid account name format.')
    ));
}
```

### Output Escaping

```php
// Restricted HTML for descriptions
$allowed_html = array(
    'a' => array('href' => true, 'title' => true),
    'br' => array(),
    'em' => array(),
    'strong' => array(),
    'p' => array(),
);

$description = wp_kses($raw_description, $allowed_html);
```

### Rate Limiting

```php
private function check_rate_limit($ip) {
    $rate_limit_key = 'mixcloud_ajax_limit_' . md5($ip);
    $current_requests = get_transient($rate_limit_key);
    
    if ($current_requests && $current_requests >= 30) {
        wp_send_json_error(array(
            'message' => __('Rate limit exceeded.')
        ));
    }
    
    set_transient($rate_limit_key, ($current_requests + 1), 300);
}
```

## Performance Optimization

### Caching Strategy

**Multi-Tier Caching**
```php
// L1: Object Cache (fast, request-scoped)
$data = wp_cache_get($cache_key, 'mixcloud_cloudcasts');

// L2: Transients (persistent, database-backed)
if ($data === false) {
    $data = get_transient($transient_key);
}

// L3: API Request (slowest, external)
if ($data === false) {
    $data = $this->make_api_request($endpoint);
}
```

**Cache Invalidation**
```php
private function invalidate_cache($username) {
    // Clear all related cache entries
    wp_cache_delete($username, 'mixcloud_user');
    delete_transient("mixcloud_cloudcasts_{$username}");
}
```

### Database Optimization

**Efficient Option Storage**
```php
// Store settings as single option array
$options = array(
    'mixcloud_account' => $account,
    'default_days' => $days,
    'cache_duration' => $duration
);
update_option('wp_mixcloud_archives_options', $options);
```

## Debugging

### Debug Mode Features

```php
// Debug information in error messages
if (defined('WP_DEBUG') && WP_DEBUG) {
    $html .= '<details class="mixcloud-error-debug">';
    $html .= '<summary>Debug Information</summary>';
    $html .= '<code>Error Code: ' . esc_html($error_code) . '</code>';
    $html .= '</details>';
}
```

### Logging

```php
// Error logging for debugging
if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('Mixcloud API Error: ' . $error_message);
}
```

## Contributing Guidelines

### Code Contribution Process

1. **Fork Repository**: Create a fork of the main repository
2. **Create Branch**: Create feature branch from main
3. **Make Changes**: Implement changes following code standards
4. **Test Thoroughly**: Test changes across different environments
5. **Submit Pull Request**: Submit PR with detailed description

### Pull Request Requirements

- [ ] Code follows WordPress coding standards
- [ ] All functions properly documented
- [ ] Security best practices followed
- [ ] Backward compatibility maintained
- [ ] No PHP errors or warnings
- [ ] JavaScript console clean
- [ ] Mobile responsive
- [ ] Cross-browser tested

### Documentation Requirements

- Update relevant documentation files
- Add code comments for complex logic
- Update changelog for user-facing changes
- Include examples for new features

## Release Process

### Version Management

**Semantic Versioning**
- **Major** (1.0.0): Breaking changes
- **Minor** (1.1.0): New features, backward compatible
- **Patch** (1.0.1): Bug fixes, backward compatible

### Release Checklist

- [ ] Update version numbers in plugin header
- [ ] Update changelog in readme.txt
- [ ] Run full test suite
- [ ] Build production assets
- [ ] Create distribution package
- [ ] Tag release in Git
- [ ] Deploy to WordPress.org (if applicable)

### Deployment

```bash
# Build production version
npm run build

# Create distribution package
npm run package:zip

# The resulting ZIP file is ready for distribution
```