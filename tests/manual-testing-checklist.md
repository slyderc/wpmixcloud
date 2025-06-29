# Manual Testing Checklist

## Pre-Testing Setup

### Environment Verification
- [ ] WordPress 6.0+ installed and running
- [ ] Plugin activated without errors
- [ ] No PHP errors in error logs
- [ ] All plugin files present and readable
- [ ] Internet connection available for API calls

### Test Data Setup
- [ ] Test Mixcloud account configured: `NowWaveRadio`
- [ ] Account has multiple published shows (10+ recommended)
- [ ] Account has shows from different date ranges
- [ ] Test pages/posts created for shortcode testing

## Core Functionality Testing

### Plugin Activation/Deactivation
- [ ] Plugin activates without errors
- [ ] Settings page accessible after activation
- [ ] No database errors during activation
- [ ] Plugin deactivates cleanly
- [ ] No orphaned data after deactivation

### Settings Page Testing
- [ ] Navigate to Settings > Mixcloud Archives
- [ ] Page loads without errors
- [ ] All form fields present and functional
- [ ] Account field accepts valid usernames
- [ ] Account field rejects invalid characters
- [ ] Settings save successfully
- [ ] Settings persist after page refresh
- [ ] Capability checks prevent unauthorized access

### Shortcode Basic Functionality
- [ ] `[mixcloud_archives account="NowWaveRadio"]` displays content
- [ ] Shows load with proper formatting
- [ ] Player buttons are present and clickable
- [ ] No JavaScript errors in console
- [ ] Page loads within reasonable time (< 5 seconds)

## Shortcode Parameter Testing

### Required Parameters
- [ ] Shortcode fails gracefully without `account` parameter
- [ ] Error message displayed for missing account
- [ ] Invalid account names show appropriate error

### Display Parameters
- [ ] `limit="5"` shows exactly 5 items
- [ ] `limit="50"` respects maximum limit
- [ ] `days="7"` shows only recent content
- [ ] `start_date="2024-01-01"` filters correctly
- [ ] `end_date="2024-01-31"` filters correctly
- [ ] Date range combinations work properly

### Interface Parameters
- [ ] `mini_player="no"` shows full-size players
- [ ] `mini_player="yes"` shows compact players
- [ ] `lazy_load="no"` loads all players immediately
- [ ] `lazy_load="yes"` loads players on scroll
- [ ] `show_date_filter="no"` hides date controls
- [ ] `show_pagination="no"` hides pagination
- [ ] `show_social="no"` hides social buttons

### Pagination Parameters
- [ ] `per_page="5"` shows 5 items per page
- [ ] `page="2"` starts on second page
- [ ] Pagination controls function correctly
- [ ] Page navigation preserves filters

## AJAX Functionality Testing

### Date Filtering
- [ ] Date picker controls appear and function
- [ ] "Apply Filter" button works
- [ ] "Clear Filter" button resets dates
- [ ] Invalid date formats show errors
- [ ] Date filtering updates content correctly
- [ ] Loading states display during requests
- [ ] Error handling works for API failures

### Pagination
- [ ] "Next" and "Previous" buttons work
- [ ] Numbered page links function
- [ ] First/Last page buttons work
- [ ] Pagination preserves date filters
- [ ] AJAX loading states display
- [ ] Page URLs update correctly (if applicable)

### Error Handling
- [ ] Network errors display user-friendly messages
- [ ] Rate limiting shows appropriate warnings
- [ ] Invalid nonces are rejected
- [ ] Malformed requests are handled gracefully

## Security Testing

### Input Validation
- [ ] SQL injection attempts are blocked
- [ ] XSS attempts are prevented
- [ ] Invalid file uploads rejected
- [ ] Malformed AJAX requests handled

### Access Control
- [ ] Non-admin users cannot access settings
- [ ] Nonce verification works for AJAX
- [ ] Rate limiting prevents abuse
- [ ] Capability checks function properly

### Output Escaping
- [ ] User content is properly escaped
- [ ] API responses are sanitized
- [ ] No raw HTML output without escaping
- [ ] URLs are properly validated

## Performance Testing

### Load Time Testing
- [ ] Initial page load < 3 seconds
- [ ] AJAX requests complete < 2 seconds
- [ ] Large result sets load reasonably
- [ ] Multiple shortcodes don't conflict

### Caching Testing
- [ ] Repeated requests use cached data
- [ ] Cache invalidation works properly
- [ ] Stale cache doesn't persist indefinitely
- [ ] Cache size remains reasonable

### Memory Usage
- [ ] Plugin doesn't cause memory exhaustion
- [ ] Large datasets handled efficiently
- [ ] No memory leaks during extended use

## Browser Compatibility Testing

### Desktop Browsers
- [ ] Chrome (latest) - Full functionality
- [ ] Firefox (latest) - Full functionality  
- [ ] Safari (latest) - Full functionality
- [ ] Edge (latest) - Full functionality

### Mobile Browsers
- [ ] Chrome Mobile - Touch interactions work
- [ ] Safari Mobile - iOS compatibility
- [ ] Firefox Mobile - Basic functionality
- [ ] Samsung Internet - Android compatibility

### JavaScript Compatibility
- [ ] ES6 features work or have polyfills
- [ ] Fetch API works or has fallback
- [ ] Intersection Observer works or has fallback
- [ ] Console shows no errors

## Responsive Design Testing

### Screen Sizes
- [ ] Desktop (1920x1080) - Full layout
- [ ] Laptop (1366x768) - Adapted layout
- [ ] Tablet (768x1024) - Mobile-friendly
- [ ] Mobile (375x667) - Touch-optimized
- [ ] Large mobile (414x896) - Proper scaling

### Orientation Testing
- [ ] Portrait mode works correctly
- [ ] Landscape mode adapts properly
- [ ] Rotation doesn't break functionality
- [ ] Content remains accessible

### Touch Interface
- [ ] Buttons are appropriately sized
- [ ] Touch targets meet accessibility guidelines
- [ ] Swipe gestures work where applicable
- [ ] No hover-dependent functionality

## Accessibility Testing

### Keyboard Navigation
- [ ] All interactive elements focusable
- [ ] Tab order is logical
- [ ] Keyboard shortcuts work
- [ ] No keyboard traps

### Screen Reader Compatibility
- [ ] Content has proper ARIA labels
- [ ] Dynamic content updates announced
- [ ] Form fields properly labeled
- [ ] Error messages accessible

### Visual Accessibility
- [ ] Sufficient color contrast
- [ ] Text remains readable when zoomed
- [ ] No information conveyed by color alone
- [ ] Focus indicators visible

## WordPress Integration Testing

### Theme Compatibility
- [ ] Plugin works with default WordPress themes
- [ ] Custom themes don't break functionality
- [ ] CSS conflicts resolved or documented
- [ ] Plugin styles don't affect theme

### Plugin Compatibility
- [ ] No conflicts with popular plugins
- [ ] Caching plugins work correctly
- [ ] SEO plugins don't interfere
- [ ] Security plugins allow functionality

### WordPress Features
- [ ] Works in posts and pages
- [ ] Compatible with block editor
- [ ] Works with classic editor
- [ ] Functions in widgets (if applicable)

## API Integration Testing

### Mixcloud API
- [ ] Valid accounts load correctly
- [ ] Invalid accounts show appropriate errors
- [ ] API rate limits handled gracefully
- [ ] Network timeouts handled properly
- [ ] Malformed API responses handled

### Error Scenarios
- [ ] No internet connection
- [ ] Mixcloud API unavailable
- [ ] Account deleted/private
- [ ] Rate limit exceeded
- [ ] Invalid API responses

## Content Display Testing

### Show Information
- [ ] Show titles display correctly
- [ ] Descriptions formatted properly
- [ ] Dates show in correct format
- [ ] Play counts display accurately
- [ ] Artwork loads and displays

### Player Functionality
- [ ] Players load Mixcloud content
- [ ] Play buttons function correctly
- [ ] Volume controls work
- [ ] Progress bars update
- [ ] Fullscreen mode works (if available)

### Social Sharing
- [ ] Facebook sharing works
- [ ] Twitter sharing works
- [ ] Copy link functionality works
- [ ] Share URLs are correct
- [ ] Share content includes proper metadata

## Edge Case Testing

### Data Edge Cases
- [ ] Empty result sets handled gracefully
- [ ] Very long show titles/descriptions
- [ ] Shows without artwork
- [ ] Shows with special characters
- [ ] Very old or very new shows

### Usage Edge Cases
- [ ] Multiple shortcodes on same page
- [ ] Nested shortcodes (if applicable)
- [ ] Very large limit values
- [ ] Extreme date ranges
- [ ] Rapid consecutive AJAX requests

### Error Recovery
- [ ] Plugin recovers from JavaScript errors
- [ ] API failures don't break page
- [ ] Cache corruption handled
- [ ] Database connection issues handled

## Final Verification

### User Experience
- [ ] Interface is intuitive
- [ ] Loading states are clear
- [ ] Error messages are helpful
- [ ] Performance is acceptable
- [ ] No broken workflows

### Documentation Accuracy
- [ ] All documented features work
- [ ] Examples in docs are correct
- [ ] Installation instructions accurate
- [ ] Troubleshooting guides helpful

### Code Quality
- [ ] No PHP warnings or notices
- [ ] JavaScript console is clean
- [ ] CSS validates properly
- [ ] Code follows WordPress standards

## Test Environment Details

**WordPress Version**: ___________
**PHP Version**: ___________
**Browser/Version**: ___________
**Screen Resolution**: ___________
**Date Tested**: ___________
**Tester**: ___________

## Notes and Issues Found

_Document any issues discovered during testing, including:_
- Description of issue
- Steps to reproduce
- Expected vs actual behavior
- Severity level
- Screenshots (if applicable)