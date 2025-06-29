# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WP Mixcloud Archives is a WordPress plugin that displays Mixcloud show archives with embedded players, supporting date filtering, pagination, and social sharing. The plugin fetches data from the Mixcloud API for the "NowWaveRadio" account and provides a shortcode for integration into WordPress pages.

## Essential Development Commands

### Asset Building
```bash
# One-time setup of build system
cd build-tools && ./setup.sh

# Build all assets (CSS + JS minification)
cd build-tools && npm run build

# Development mode with file watching
cd build-tools && npm run watch

# Create distribution package
cd build-tools && npm run package:zip

# Clean all generated files
cd build-tools && npm run clean
```

### WordPress Development
```bash
# Install plugin (copy to WordPress plugins directory)
cp -r . /path/to/wordpress/wp-content/plugins/wp-mixcloud-archives

# Activate via WP-CLI (if available)
wp plugin activate wp-mixcloud-archives
```

## Architecture Overview

### Core Plugin Structure
- **Singleton Pattern**: Main plugin class (`WP_Mixcloud_Archives`) uses singleton pattern for global access
- **Autoload System**: Classes are loaded conditionally (admin classes only in admin area)
- **Hook-Based Architecture**: WordPress hooks drive all functionality initialization
- **API Handler Separation**: Mixcloud API logic isolated in dedicated handler class

### Key Components

#### 1. Main Plugin Class (`wp-mixcloud-archives.php`)
```php
WP_Mixcloud_Archives::get_instance()
```
- Singleton entry point
- Manages hook registration
- Coordinates between API handler and admin interface
- Handles asset enqueuing and performance optimizations

#### 2. API Handler (`includes/class-mixcloud-api.php`)
```php
Mixcloud_API_Handler
```
- **Circuit Breaker Pattern**: Prevents cascading failures with automatic recovery
- **Multi-tier Caching**: Object cache (L1) + WordPress transients (L2)
- **Retry Logic**: Exponential backoff for failed requests
- **Performance Optimized**: 15s timeout, 5s connection timeout
- **Conditional Requests**: ETag headers for efficient caching

#### 3. Admin Interface (`admin/class-wp-mixcloud-archives-admin.php`)
- WordPress admin integration
- Settings management
- Plugin configuration interface

#### 4. Frontend Assets (`assets/`)
- **CSS**: OnAir2 theme with CSS custom properties
- **JavaScript**: Modern ES6+ with Intersection Observer for lazy loading
- **AJAX Enhancement**: Request deduplication and retry logic
- **Performance Features**: Lazy loading for images and players

### Build System Architecture

#### Automated Pipeline (`build-tools/`)
```bash
Source Files → Minification → Package Creation → Distribution
```

**Key Features:**
- **DRY Principle**: Uses `.gitignore` as single source of truth for file exclusions
- **Smart Filtering**: Automatically excludes development files from distribution
- **Version Detection**: Reads plugin version from main PHP file header
- **Asset Validation**: Ensures critical files are present in packages

**Build Configuration:**
```javascript
// build-tools/build.js
CONFIG = {
    terserOptions: { 
        mangle: { reserved: ['wpMixcloudArchives', 'wpMixcloudArchivesRefresh'] }
    },
    package: {
        include: ['*.php', 'assets/**/*', 'includes/**/*', 'admin/**/*'],
        additionalExcludes: ['BUILD-SYSTEM.md', 'PRD.md', '.taskmaster/**/*']
    }
}
```

### Performance Architecture

#### Caching Strategy
```php
// L1: Object Cache (in-memory, fastest)
wp_cache_get($cache_key, 'mixcloud_cloudcasts')

// L2: Transients (database-backed, persistent)  
get_transient($transient_key)
```

#### API Optimization
- **Circuit Breaker**: 5 failure threshold, 5-minute timeout
- **Request Deduplication**: Prevents concurrent identical requests
- **Conditional Headers**: ETag-based cache validation
- **Timeout Optimization**: Separate connection (5s) and total (15s) timeouts

#### Frontend Performance
- **Lazy Loading**: Intersection Observer for images and players
- **Asset Minification**: Automated CSS/JS compression
- **Resource Hints**: Preconnect and DNS prefetch for external resources
- **AJAX Optimization**: Request deduplication with Map-based tracking

### WordPress Integration Patterns

#### Shortcode System
```php
[mixcloud_archives account="NowWaveRadio" limit="20" mini_player="true"]
```

#### Hook Architecture
```php
// Performance optimizations
add_action('init', 'init_performance_optimizations')

// Asset loading
add_action('wp_enqueue_scripts', 'enqueue_scripts')

// AJAX handlers  
add_action('wp_ajax_mixcloud_filter_by_date', 'handle_date_filter')
add_action('wp_ajax_nopriv_mixcloud_filter_by_date', 'handle_date_filter')
```

#### Caching Integration
```php
// WordPress transients for short-term cache
set_transient($key, $data, $expiration)

// Object cache for request-level performance
wp_cache_set($key, $data, $group, $expiration)
```

## Code Conventions

### PHP Standards
- **WordPress Coding Standards**: Follow WP coding conventions
- **Security First**: All inputs sanitized, outputs escaped
- **AIDEV Comments**: Use `AIDEV-NOTE:`, `AIDEV-TODO:`, `AIDEV-QUESTION:` for context
- **Class Naming**: PascalCase with descriptive prefixes (`Mixcloud_API_Handler`)

### JavaScript Patterns
- **Modern ES6+**: Use const/let, arrow functions, async/await
- **Performance Focus**: Intersection Observer over scroll events
- **Error Handling**: Comprehensive try/catch with user feedback
- **Global Preservation**: Maintain `wpMixcloudArchives` namespace

### CSS Architecture
- **CSS Custom Properties**: Use `--onair-*` variables for theming
- **Mobile First**: Responsive design with progressive enhancement
- **Performance**: Minimal reflows, optimized animations
- **Accessibility**: WCAG compliant with proper ARIA labels

## File Organization

### Development vs Distribution
```
Development:
├── build-tools/          # Build system (NOT distributed)
├── BUILD-SYSTEM.md       # Documentation (NOT distributed)
├── assets/css/style.css  # Source files (distributed)
└── assets/css/style.min.css # Generated files (distributed)

Distribution (via npm run package):
├── wp-mixcloud-archives.php
├── assets/css/style.min.css
├── assets/js/script.min.js
├── includes/
└── admin/
```

### Critical Files
- `wp-mixcloud-archives.php` - Plugin entry point
- `includes/class-mixcloud-api.php` - API handler
- `assets/css/style.min.css` - Minified styles
- `assets/js/script.min.js` - Minified JavaScript

## Development Workflow

### Local Development
1. **Setup**: Run `cd build-tools && ./setup.sh` once
2. **Development**: Use `npm run watch` for auto-rebuilding
3. **Testing**: Copy plugin to WordPress installation
4. **Distribution**: Use `npm run package:zip` for final package

### Code Modification Patterns
1. **Edit Source Files**: Never edit `.min.css` or `.min.js` directly
2. **API Changes**: Modify `class-mixcloud-api.php` with caching considerations
3. **Frontend Updates**: Update `assets/css/style.css` and `assets/js/script.js`
4. **Build Assets**: Run build system before committing

### Performance Considerations
- **Caching**: Always implement appropriate caching for API calls
- **Lazy Loading**: Use Intersection Observer for deferred loading
- **Error Handling**: Implement graceful degradation for API failures
- **Asset Optimization**: Leverage build system for production assets

## WordPress Specific Notes

### Plugin Standards
- **Activation/Deactivation**: Clean up on plugin lifecycle events
- **Security**: Nonce verification for all AJAX requests
- **Internationalization**: Text domain `wp-mixcloud-archives`
- **Database**: Use WordPress transients, avoid custom tables

### API Integration
- **External Requests**: Use `wp_remote_get()` instead of cURL
- **Caching**: Leverage WordPress object cache and transients
- **Error Handling**: WordPress-style error objects (`WP_Error`)
- **Rate Limiting**: Implement circuit breaker for API protection