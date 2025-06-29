# Admin Configuration Guide

## Accessing Plugin Settings

After installing and activating the WP Mixcloud Archives plugin, you can access the configuration settings through your WordPress admin dashboard.

### Navigation Path
1. Log in to your WordPress admin dashboard
2. Navigate to **Settings** in the left sidebar
3. Click on **Mixcloud Archives**

## Settings Overview

The plugin settings page provides essential configuration options for connecting to Mixcloud and customizing the display behavior.

### Page Layout

The settings page is organized into several sections:

1. **Mixcloud Configuration** - Core API settings
2. **API Status** - Connection status and diagnostics
3. **Usage Instructions** - Quick reference for shortcode usage

## Configuration Settings

### Mixcloud Account

**Field**: Mixcloud Account  
**Required**: Yes  
**Description**: Your Mixcloud username for API connections

#### How to Configure:
1. Visit your Mixcloud profile (e.g., https://www.mixcloud.com/YourUsername/)
2. Copy just the username portion (everything after the last slash)
3. Enter this username in the "Mixcloud Account" field
4. Click "Save Changes"

#### Examples:
- **Profile URL**: `https://www.mixcloud.com/NowWaveRadio/`
- **Username to Enter**: `NowWaveRadio`

#### Validation:
- The field only accepts alphanumeric characters, underscores, and hyphens
- Special characters and spaces are automatically removed
- The system validates that the account exists and is accessible

### Default Days to Show

**Field**: Default Days to Show  
**Default**: 30  
**Description**: Default number of days of content to display when no specific date range is provided

#### Configuration Options:
- **Minimum**: 1 day
- **Maximum**: 365 days (1 year)
- **Recommended**: 30-90 days for optimal performance

#### Performance Considerations:
- **1-30 days**: Fast loading, good for frequently updated accounts
- **31-90 days**: Balanced performance and content coverage
- **91+ days**: Slower loading, use only if necessary

## API Status Section

The API Status section provides real-time information about your Mixcloud connection.

### Status Indicators

**Connected Successfully**:
- Green checkmark icon
- "API connection successful"
- Shows last successful connection time

**Connection Issues**:
- Red warning icon
- Specific error message
- Troubleshooting suggestions

### Common Status Messages

| Status | Meaning | Action Required |
|--------|---------|-----------------|
| ✅ Connected | API working normally | None |
| ⚠️ Account not found | Username doesn't exist | Check username spelling |
| ❌ API unavailable | Mixcloud API is down | Wait and try again |
| 🔄 Rate limited | Too many requests | Wait 5-10 minutes |

## Security Features

### Input Sanitization
All settings inputs are automatically sanitized to prevent security issues:
- Usernames are validated against allowed characters
- Numeric inputs are bounded to safe ranges
- All data is escaped before storage

### Access Control
- Settings page requires `manage_options` capability
- Only administrators can modify plugin settings
- All form submissions include security nonces

### Rate Limiting
- Plugin includes built-in rate limiting for API requests
- Prevents excessive API calls that could impact performance
- Automatic retry logic with exponential backoff

## Best Practices

### Account Configuration

1. **Use Primary Account**: Configure the account you use most frequently
2. **Public Profile**: Ensure your Mixcloud profile is public
3. **Active Account**: Use an account that regularly publishes content
4. **Verify Spelling**: Double-check username spelling to avoid errors

### Performance Optimization

1. **Conservative Days Setting**: Start with 30 days and increase if needed
2. **Monitor Load Times**: Check page load speed after configuration
3. **Cache Plugin**: Use a caching plugin for optimal performance
4. **Regular Updates**: Keep the plugin updated for best performance

### Content Management

1. **Test Shortcodes**: Test shortcodes on draft pages before publishing
2. **Multiple Accounts**: Use shortcode parameters to display different accounts
3. **Content Strategy**: Plan how you'll organize archives on your site

## Troubleshooting

### Common Configuration Issues

**Settings Won't Save**:
1. Check user permissions (need administrator role)
2. Disable caching plugins temporarily
3. Check for JavaScript errors in browser console
4. Verify WordPress file permissions

**API Connection Fails**:
1. Verify username is correct and account exists
2. Check that Mixcloud profile is public
3. Ensure server has internet connectivity
4. Try again after a few minutes (may be temporary)

**Account Not Found Error**:
1. Double-check username spelling and capitalization
2. Verify the account exists on Mixcloud
3. Ensure the account is public (not private)
4. Check that the account has published content

### Network and Server Issues

**Firewall Blocking API**:
- Contact hosting provider about Mixcloud API access
- Ensure outbound HTTPS connections are allowed
- Check if specific domains need to be whitelisted

**SSL/TLS Issues**:
- Verify your site has a valid SSL certificate
- Update PHP to a recent version with current SSL support
- Contact hosting provider if SSL errors persist

### Plugin Conflicts

**JavaScript Conflicts**:
1. Test with default WordPress theme
2. Disable other plugins temporarily
3. Check browser console for JavaScript errors
4. Update all plugins and themes

**CSS Styling Issues**:
1. Check theme compatibility
2. Review custom CSS modifications
3. Use browser developer tools to identify conflicts
4. Consider adding custom CSS to override theme styles

## Advanced Configuration

### Custom Default Settings

While the plugin doesn't currently support custom default shortcode parameters, you can achieve similar results by:

1. **Standardizing Shortcodes**: Use consistent parameters across your site
2. **Documentation**: Maintain internal documentation of your preferred settings
3. **Template Modifications**: Customize your theme to include standard shortcodes

### Multi-Site Considerations

For WordPress multisite installations:
- Each site has independent plugin settings
- Configure each site's Mixcloud account separately
- Network-wide activation shares the plugin code but not settings

### Backup and Migration

**Backing Up Settings**:
- Plugin settings are stored in WordPress options table
- Include in regular WordPress database backups
- Export settings using WordPress export tools

**Migrating Settings**:
- Settings transfer with WordPress database migration
- Manually recreate settings on new installations
- Use WordPress import/export for site migrations

## Security Headers

The plugin automatically adds security headers to admin pages:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`

These headers help protect against common web vulnerabilities.

## Support and Updates

### Staying Updated
1. **Plugin Updates**: Update through WordPress admin when available
2. **WordPress Core**: Keep WordPress updated for security and compatibility
3. **PHP Version**: Maintain current PHP version for optimal performance

### Getting Help
1. **Documentation**: Review all documentation files
2. **WordPress Support**: Use WordPress.org support forums
3. **Hosting Provider**: Contact for server-related issues
4. **Community**: Engage with WordPress community for general questions