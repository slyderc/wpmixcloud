# Testing Environment Setup Instructions

## Quick Start (Recommended)

### Option 1: WordPress Environment (@wordpress/env)

1. **Install WordPress Environment Tool**
   ```bash
   npm install -g @wordpress/env
   ```

2. **Start Test Environment**
   ```bash
   # From plugin root directory
   npx wp-env start
   ```

3. **Access Test Site**
   - **Frontend**: http://localhost:8888
   - **Admin**: http://localhost:8888/wp-admin
   - **Credentials**: admin / password

4. **Configure Plugin**
   - Plugin will be auto-installed and activated
   - Go to Settings > Mixcloud Archives
   - Enter test account: `NowWaveRadio`

### Option 2: Local Development Stack

1. **Install Local Development Tool**
   - [Local by Flywheel](https://localwp.com/) (Recommended)
   - [XAMPP](https://www.apachefriends.org/)
   - [MAMP](https://www.mamp.info/)

2. **Create WordPress Site**
   - WordPress 6.0+
   - PHP 8.0+
   - Latest MySQL/MariaDB

3. **Install Plugin**
   ```bash
   # Copy plugin to WordPress plugins directory
   cp -r . /path/to/wordpress/wp-content/plugins/wp-mixcloud-archives
   ```

4. **Activate and Configure**
   - Activate plugin in WordPress admin
   - Configure settings with test account

## Testing Phases

### Phase 1: Environment Verification
```bash
# Check WordPress environment
npx wp-env run cli wp core version
npx wp-env run cli wp plugin list
npx wp-env run cli wp theme list
```

### Phase 2: Manual Testing
- Follow `manual-testing-checklist.md`
- Test all functionality systematically
- Document any issues found

### Phase 3: Cross-Browser Testing
- Chrome (latest)
- Firefox (latest)
- Safari (latest, Mac only)
- Edge (latest)

### Phase 4: Mobile Testing
- iOS Safari (iPhone/iPad)
- Android Chrome
- Various screen sizes

### Phase 5: Performance Testing
- Page load times
- AJAX response times
- Memory usage
- API rate limiting

## Test Data Requirements

### Mixcloud Account
- **Test Account**: `NowWaveRadio` (or any public account with 10+ shows)
- **Requirements**:
  - Public profile
  - Multiple published shows
  - Shows from different date ranges
  - Various show lengths and types

### WordPress Test Content
```bash
# Create test pages with shortcodes
npx wp-env run cli wp post create --post_type=page --post_title="Test Page 1" --post_content="[mixcloud_archives account=\"NowWaveRadio\"]"
npx wp-env run cli wp post create --post_type=page --post_title="Test Page 2" --post_content="[mixcloud_archives account=\"NowWaveRadio\" limit=\"5\" mini_player=\"no\"]"
```

## Browser Testing Tools

### Desktop Testing
- **Chrome DevTools**: Network, Console, Device simulation
- **Firefox Developer Tools**: Responsive design mode
- **Safari Web Inspector**: iOS simulation
- **Edge DevTools**: Cross-browser compatibility

### Mobile Testing
- **Physical Devices**: iPhone, Android phone/tablet
- **Browser DevTools**: Device simulation mode
- **Online Tools**: BrowserStack, Sauce Labs (if available)

## Performance Testing Tools

### WordPress Specific
- **Query Monitor Plugin**: Database queries, PHP performance
- **Debug Bar Plugin**: WordPress debugging information
- **P3 Profiler**: Plugin performance impact

### General Tools
- **Lighthouse**: Performance, accessibility, SEO
- **GTmetrix**: Page speed analysis
- **WebPageTest**: Detailed performance metrics

## Security Testing

### WordPress Security
- **Wordfence Scan**: Security vulnerabilities
- **Plugin Check**: WordPress.org plugin guidelines
- **PHPCS**: WordPress coding standards

### Manual Security Tests
- SQL injection attempts
- XSS prevention
- CSRF protection
- Input validation
- Output escaping

## Issue Reporting

### Bug Report Template
```markdown
**Bug Description**: Brief description of the issue

**Steps to Reproduce**:
1. Step one
2. Step two
3. Step three

**Expected Behavior**: What should happen

**Actual Behavior**: What actually happens

**Environment**:
- WordPress Version: 
- PHP Version: 
- Browser: 
- Device: 

**Screenshots**: [Attach if applicable]

**Console Errors**: [Any JavaScript errors]

**Severity**: Critical / High / Medium / Low
```

### Severity Levels
- **Critical**: Plugin doesn't work, security vulnerability
- **High**: Major feature broken, data loss possible
- **Medium**: Minor feature issue, workaround available
- **Low**: Cosmetic issue, enhancement request

## Cleanup After Testing

### WordPress Environment
```bash
# Stop environment
npx wp-env stop

# Destroy environment (removes all data)
npx wp-env destroy
```

### Local Development
- Delete WordPress installation
- Remove database
- Clear any cached files

## Continuous Testing

### Automated Checks
- Run tests before commits
- Test on multiple PHP versions
- Regular security scans
- Performance monitoring

### Manual Testing Schedule
- Full testing before releases
- Regression testing after bug fixes
- Compatibility testing with WordPress updates
- Regular user experience reviews

## Current Test Environment Status

### Active Test Environment (Local)
- **WordPress URL**: http://localhost:8888
- **Admin URL**: http://localhost:8888/wp-admin  
- **Credentials**: admin / password
- **WordPress Version**: 6.4
- **PHP Version**: 8.0
- **Plugin Status**: Active (as 'wpmixcloud')

### Test Pages Available
- **Main Test Page**: http://localhost:8888/mixcloud-archives-test/
  - Content: `[mixcloud_archives account="NowWaveRadio" limit="6"]`
  - Tests: New card layout, modal player, responsive design
- **Advanced Test Page**: http://localhost:8888/advanced-test-page/
- **Basic Test Page**: http://localhost:8888/mixcloud-test-page/

### Quick Test Commands
```bash
# Start test environment
npx wp-env start

# Check plugin status  
npx wp-env run cli wp plugin list

# Create new test page
npx wp-env run cli wp post create --post_type=page --post_title="New Test" --post_content="[mixcloud_archives account=\"NowWaveRadio\"]" --post_status=publish

# Stop test environment
npx wp-env stop
```

## Automated Testing with Puppeteer

### Puppeteer Integration
The project includes Puppeteer for automated browser testing, which can be used to test the new card layout and modal functionality.

### Puppeteer Test Scripts
```javascript
// Test card layout rendering
const page = await browser.newPage();
await page.goto('http://localhost:8888/mixcloud-archives-test/');

// Test card elements exist
const cards = await page.$$('.mixcloud-archive-card');
expect(cards.length).toBeGreaterThan(0);

// Test artwork click opens modal
await page.click('.mixcloud-card-artwork');
const modal = await page.$('.mixcloud-modal');
const modalVisible = await modal.isVisible();
expect(modalVisible).toBe(true);

// Test modal close
await page.click('.mixcloud-modal-close');
const modalHidden = await modal.isHidden();
expect(modalHidden).toBe(true);
```

### Visual Regression Testing
- Take screenshots of card layout at different screen sizes
- Compare new layout against previous table layout
- Test modal appearance and functionality
- Verify responsive behavior across devices

### Performance Testing
- Measure page load times with new card layout
- Test modal loading performance
- Monitor memory usage during interactions
- Verify lazy loading effectiveness