# Changelog

All notable changes to the WP Mixcloud Archives plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-01-31

### Added
- Improved event binding for play buttons to work immediately without page reload
- MutationObserver to detect and bind dynamically loaded content
- Better error handling for disabled play buttons
- More compact modal design with appropriate sizing
- Safari compatibility improvements for modals

### Changed
- Modal now uses more appropriate sizing for content
- Reduced padding and spacing in modal for better visual balance
- Updated close button styling for better visibility
- Improved responsive design for mobile devices

### Fixed
- Play button click not working on first interaction
- Modal extending too far below content
- Modal size being too large for the contained elements
- Image sizing in modal player view

## [1.0.0] - 2025-01-29

### Added
- Initial release of WP Mixcloud Archives plugin
- Mixcloud API integration with caching
- Shortcode support for displaying archives
- Date filtering functionality
- AJAX-powered pagination
- Embedded Mixcloud players in modal popups
- Social sharing buttons (Facebook, Twitter, WhatsApp, Email)
- Admin settings page
- Responsive design with OnAir2 theme compatibility
- Multi-tier caching system
- Rate limiting for API requests
- Circuit breaker pattern for API failures
- Translation-ready with textdomain support