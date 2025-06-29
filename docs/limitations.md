# Limitations & Compatibility

## Known Limitations

### API Limitations

**Single Account Per Installation**
- Currently supports one Mixcloud account per WordPress installation
- Each shortcode can specify different accounts, but global settings apply to one account
- **Workaround**: Use shortcode `account` parameter to display different accounts

**Mixcloud API Rate Limits**
- Mixcloud API has usage limits to prevent abuse
- Plugin implements rate limiting (30 requests per 5 minutes per IP)
- Heavy usage may temporarily limit API access
- **Mitigation**: Built-in caching reduces API calls

**Public Content Only**
- Only displays public Mixcloud content
- Private or unlisted shows will not appear
- **Requirement**: Mixcloud account must be public

### Content Limitations

**Show Limit**
- Maximum 50 shows can be fetched per API request
- Large archives may require pagination
- **Best Practice**: Use date filtering for better performance

**Historical Content**
- Plugin cannot retrieve deleted Mixcloud content
- Shows removed from Mixcloud will disappear from archives
- **Note**: This is a Mixcloud platform limitation

**Metadata Dependency**
- Display quality depends on Mixcloud's provided metadata
- Missing descriptions or artwork are Mixcloud-side issues
- **Impact**: Some shows may have incomplete information

### Technical Limitations

**JavaScript Dependency**
- Date filtering and pagination require JavaScript
- Players require JavaScript for interactive features
- **Fallback**: Basic content displays without JavaScript

**Internet Connectivity**
- Requires active internet connection for API calls
- Offline viewing not supported (except cached content)
- **Impact**: Shows won't load without internet access

**Caching Constraints**
- Cache duration limited to prevent stale content
- Cache clearing may temporarily impact performance
- **Balance**: 15-minute cache provides reasonable freshness

## WordPress Compatibility

### WordPress Versions

**Supported Versions**
- WordPress 5.0 and higher ✅
- WordPress 6.0+ recommended ✅
- Classic Editor supported ✅
- Block Editor (Gutenberg) supported ✅

**Tested Versions**
- WordPress 6.0 ✅
- WordPress 6.1 ✅
- WordPress 6.2 ✅
- WordPress 6.3 ✅

### PHP Compatibility

**Minimum Requirements**
- PHP 7.4 minimum ✅
- PHP 8.0+ recommended ✅
- PHP 8.1 tested ✅
- PHP 8.2 tested ✅

**Deprecated PHP Versions**
- PHP 7.3 and below ❌ Not supported
- PHP 5.x ❌ Not supported

### Theme Compatibility

**Universal Compatibility**
- Works with any properly coded WordPress theme
- Uses standard WordPress hooks and filters
- Responsive design adapts to theme containers

**Tested Themes**
- Twenty Twenty-Three ✅
- Twenty Twenty-Two ✅
- Twenty Twenty-One ✅
- Astra ✅
- GeneratePress ✅
- OceanWP ✅

**Potential Theme Issues**
- Themes with aggressive CSS may override plugin styles
- Themes blocking external JavaScript may affect players
- **Solution**: Custom CSS may be needed for specific themes

### Plugin Compatibility

**Caching Plugins**
- WP Rocket ✅ Compatible
- W3 Total Cache ✅ Compatible
- WP Super Cache ✅ Compatible
- LiteSpeed Cache ✅ Compatible
- **Note**: Plugin includes its own smart caching

**SEO Plugins**
- Yoast SEO ✅ Compatible
- RankMath ✅ Compatible
- All in One SEO ✅ Compatible

**Security Plugins**
- Wordfence ✅ Compatible
- Sucuri ✅ Compatible
- iThemes Security ✅ Compatible
- **Note**: May need to whitelist Mixcloud API endpoints

**Page Builders**
- Elementor ✅ Compatible (via shortcode widget)
- Beaver Builder ✅ Compatible
- Divi ✅ Compatible
- Gutenberg ✅ Native support
- **Usage**: Insert shortcodes through text/HTML modules

## Browser Compatibility

### Supported Browsers

**Desktop Browsers**
- Chrome 90+ ✅
- Firefox 88+ ✅  
- Safari 14+ ✅
- Edge 90+ ✅

**Mobile Browsers**
- Chrome Mobile 90+ ✅
- Safari Mobile 14+ ✅
- Firefox Mobile 88+ ✅
- Samsung Internet 15+ ✅

**Legacy Browser Support**
- Internet Explorer ❌ Not supported
- Chrome < 70 ⚠️ Limited support
- Firefox < 70 ⚠️ Limited support

### JavaScript Features

**Modern Features Used**
- Intersection Observer API (with fallback)
- Fetch API (with fallback)
- ES6+ features (transpiled for older browsers)
- CSS Grid and Flexbox

**Fallbacks Provided**
- Graceful degradation for older browsers
- Polyfills for critical features
- Progressive enhancement approach

## Server Requirements

### Hosting Compatibility

**Shared Hosting**
- Most shared hosts ✅ Compatible
- **Requirements**: PHP 7.4+, outbound HTTPS allowed

**VPS/Dedicated Hosting**
- Full compatibility ✅
- **Advantage**: Better performance and control

**WordPress Hosting**
- WP Engine ✅ Compatible
- Kinsta ✅ Compatible
- SiteGround ✅ Compatible
- Bluehost ✅ Compatible

### Server Configuration

**Required PHP Extensions**
- cURL or allow_url_fopen ✅ Required for API calls
- JSON ✅ Required for data processing
- mbstring ✅ Recommended for text handling

**Firewall Considerations**
- Must allow outbound HTTPS to `api.mixcloud.com`
- May need to whitelist Mixcloud CDN domains
- **Impact**: Blocked connections prevent content loading

**Memory Limits**
- Minimum 128MB PHP memory limit
- 256MB+ recommended for heavy usage
- **Note**: Large archives may need more memory

## Performance Considerations

### Load Time Factors

**Factors Affecting Speed**
- Number of shows displayed per page
- Image loading (lazy loading helps)
- Player embedding (mini players are faster)
- Server response time

**Optimization Recommendations**
- Use `per_page` parameter to limit items
- Enable lazy loading (`lazy_load="yes"`)
- Use mini players (`mini_player="yes"`)
- Implement caching plugins

### Scalability Limits

**High Traffic Sites**
- Plugin handles moderate to high traffic well
- Built-in rate limiting prevents abuse
- **Consideration**: Very high traffic may need custom solutions

**Large Archives**
- Performance may decrease with 100+ shows
- Use date filtering to improve load times
- **Best Practice**: Paginate large collections

## Known Issues

### Current Known Issues

**Mobile Player Loading**
- **Issue**: Occasional delay in player loading on mobile
- **Workaround**: Disable lazy loading for mobile if needed
- **Status**: Investigating optimization improvements

**Safari Date Picker**
- **Issue**: Date picker styling inconsistency in Safari
- **Impact**: Functional but may look different
- **Status**: Cosmetic issue, does not affect functionality

### Resolved Issues

**Cache Invalidation** (Fixed in v1.0.0)
- **Previous Issue**: Cache not clearing properly
- **Solution**: Improved cache key generation and invalidation

**AJAX Rate Limiting** (Fixed in v1.0.0)  
- **Previous Issue**: No protection against AJAX abuse
- **Solution**: Implemented IP-based rate limiting

## WordPress Multisite

### Compatibility Status
- ✅ Compatible with WordPress Multisite
- ✅ Each site maintains independent settings
- ✅ Network activation supported

### Multisite Considerations
- Plugin settings are per-site, not network-wide
- Each site needs individual configuration
- Network-wide caching may affect performance

## Future Roadmap

### Planned Improvements

**Multiple Account Support**
- Allow configuration of multiple default accounts
- Global account switching in admin
- **Timeline**: Under consideration for future release

**Enhanced Caching**
- Background cache warming
- Smarter cache invalidation
- **Timeline**: Ongoing optimization

**Performance Enhancements**
- Reduced JavaScript bundle size
- Optimized API call patterns
- **Timeline**: Continuous improvement

### Feature Requests Under Review

- Custom player themes
- Advanced filtering options
- Integration with other audio platforms
- Bulk archive management tools

## Getting Support

### Before Seeking Support

1. **Check Documentation**: Review all documentation files
2. **Test with Default Theme**: Isolate theme-related issues
3. **Disable Other Plugins**: Identify plugin conflicts
4. **Check Browser Console**: Look for JavaScript errors
5. **Verify Server Requirements**: Ensure all requirements are met

### Support Channels

1. **WordPress.org Support Forums**: For general WordPress questions
2. **Plugin Documentation**: For usage and configuration help
3. **Hosting Provider**: For server and hosting-related issues
4. **WordPress Community**: For development and customization questions

### Providing Bug Reports

When reporting issues, please include:
- WordPress version
- PHP version
- Plugin version
- Theme name and version
- List of active plugins
- Browser and version
- Steps to reproduce the issue
- Error messages (if any)