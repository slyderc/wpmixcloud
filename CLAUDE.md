# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**WP Mixcloud Archives** is a WordPress plugin that integrates with the Mixcloud API to display radio show archives with embedded players, date filtering, and social sharing. Built for the NowWaveRadio account with the OnAir2 theme.

## Development Commands

### Build System
```bash
cd build-tools
npm run build     # Production build (minify CSS/JS)
npm run watch     # Development with file watching
npm run package   # Create distribution ZIP
npm run clean     # Remove minified files
```

### Initial Setup
```bash
cd build-tools && ./setup.sh
```

### Testing
No automated tests - uses comprehensive manual testing checklist in `tests/manual-testing-checklist.md`.

## Code Architecture

### Core Plugin Structure
- **Main Class**: `WP_Mixcloud_Archives` (singleton pattern in `wp-mixcloud-archives.php`)
- **Component System**: Six key classes in `includes/` directory
- **Hook-based**: Proper WordPress lifecycle integration

### Key Classes
1. **Mixcloud_API** - API integration with caching and circuit breaker
2. **Shortcode_Handler** - Processes `[mixcloud_archives]` shortcode  
3. **AJAX_Handler** - Date filtering and pagination with rate limiting
4. **HTML_Generator** - Renders responsive archive display
5. **Cache_Manager** - Multi-tier caching (Object → Transients → API)
6. **Assets_Manager** - CSS/JS enqueue management

### Shortcode Usage
```
[mixcloud_archives account="NowWaveRadio" days="7" mini_player="yes"]
```

**Key Parameters:**
- `account` (required): Mixcloud username
- `days`: Show last N days (0-365, default: 0 = show all)
- `start_date`: Show from date (YYYY-MM-DD, optional)
- `end_date`: Show until date (YYYY-MM-DD, optional)
- `mini_player`: Compact players (yes/no, default: yes)
- `lazy_load`: Enable image lazy loading (yes/no, default: yes)
- `show_social`: Social sharing buttons (yes/no, default: yes)

## Security & Performance

### Security Features
- Input sanitization on all user inputs
- Output escaping for XSS prevention
- Nonce verification for AJAX requests
- Rate limiting (30 requests per 5 minutes)
- Capability checks for admin access

### Performance Optimizations
- Multi-tier caching strategy
- Lazy loading for Mixcloud players
- Circuit breaker pattern for API failures
- Efficient WordPress option storage

## Asset Pipeline

### File Structure
- **Source**: `assets/css/style.css`, `assets/js/script.js`
- **Built**: `assets/css/style.min.css`, `assets/js/script.min.js`
- **Build tools preserve WordPress globals and remove debug output**

### Browser Compatibility
- **Cross-browser layout**: Uses Flexbox instead of CSS Grid for Safari compatibility
- **JavaScript fallbacks**: XMLHttpRequest fallback for older Safari versions (< 10.1)
- **CSS fallbacks**: Hard-coded fallback values for CSS custom properties (Safari < 9.1)
- **No autoplay**: Mixcloud iframes don't autoplay for Safari compliance
- **Date sanitization**: Consistent regex patterns between PHP and JavaScript for show title filtering

## WordPress Integration

### Plugin Lifecycle
- Activation hook registers default settings
- Admin settings page at **Settings → WP Mixcloud Archives**
- Single option array for efficient storage
- Translation-ready with textdomain support

### AJAX Endpoints
- `wp_ajax_mixcloud_filter_archives` - Date filtering
- `wp_ajax_nopriv_mixcloud_filter_archives` - Public access
- Rate limited and nonce-protected

## Development Notes

### Code Quality Standards
- Uses AIDEV anchor comments for greppable documentation
- Singleton pattern for main plugin class
- Proper WordPress hooks and filters
- Security-first approach with sanitization/escaping
- Production-ready code with debug statements removed

### Missing Infrastructure
- No automated unit tests (PHPUnit recommended)
- No linting configuration (.phpcs.xml, .eslintrc)
- No Composer dependencies
- No CI/CD pipeline

### File Watching
Development workflow uses `npm run watch` for automatic rebuilds when source files change.