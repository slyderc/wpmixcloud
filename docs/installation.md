# Installation Guide

## Requirements

Before installing WP Mixcloud Archives, ensure your server meets these requirements:

### WordPress Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active internet connection for API calls

### Recommended
- WordPress 6.0+ 
- PHP 8.0+
- SSL certificate (HTTPS)
- Caching plugin for optimal performance

## Installation Methods

### Method 1: WordPress Admin Dashboard (Recommended)

1. **Access WordPress Admin**
   - Log in to your WordPress admin dashboard
   - Navigate to **Plugins > Add New**

2. **Upload Plugin**
   - Click **Upload Plugin** at the top of the page
   - Click **Choose File** and select the `wp-mixcloud-archives.zip` file
   - Click **Install Now**

3. **Activate Plugin**
   - Once installation is complete, click **Activate Plugin**
   - You'll see a success message confirming activation

### Method 2: FTP/Manual Installation

1. **Extract Plugin Files**
   - Extract the `wp-mixcloud-archives.zip` file to your computer
   - This will create a `wp-mixcloud-archives` folder

2. **Upload via FTP**
   - Connect to your website via FTP client
   - Navigate to `/wp-content/plugins/` directory
   - Upload the entire `wp-mixcloud-archives` folder

3. **Activate Plugin**
   - Go to your WordPress admin dashboard
   - Navigate to **Plugins > Installed Plugins**
   - Find "WP Mixcloud Archives" and click **Activate**

### Method 3: WP-CLI (Advanced Users)

```bash
# Install plugin
wp plugin install wp-mixcloud-archives.zip

# Activate plugin
wp plugin activate wp-mixcloud-archives
```

## Post-Installation Setup

### 1. Configure Plugin Settings

1. **Access Settings**
   - Go to **Settings > Mixcloud Archives** in WordPress admin
   - You'll see the plugin configuration page

2. **Enter Mixcloud Account**
   - Enter your Mixcloud username in the "Mixcloud Account" field
   - Example: If your Mixcloud profile is `https://www.mixcloud.com/NowWaveRadio/`, enter `NowWaveRadio`

3. **Configure Display Options**
   - Set "Default Days to Show" (recommended: 30-90 days)
   - Review other available settings

4. **Save Settings**
   - Click **Save Changes** to store your configuration

### 2. Test Installation

1. **Create Test Page**
   - Go to **Pages > Add New**
   - Enter a title like "My Mixcloud Archives"

2. **Add Shortcode**
   - In the content area, add: `[mixcloud_archives account="your-username"]`
   - Replace `your-username` with your actual Mixcloud username

3. **Preview/Publish**
   - Click **Preview** to test the display
   - If everything looks good, click **Publish**

## Verification Checklist

After installation, verify these items work correctly:

- [ ] Plugin appears in **Plugins > Installed Plugins**
- [ ] Settings page accessible at **Settings > Mixcloud Archives**
- [ ] Shortcode displays your Mixcloud shows
- [ ] Players load and are functional
- [ ] Date filtering works (if enabled)
- [ ] Pagination functions properly (if you have many shows)
- [ ] No PHP errors in your error logs

## Troubleshooting Installation Issues

### Plugin Won't Activate
- **Check PHP Version**: Ensure you're running PHP 7.4+
- **Check File Permissions**: Ensure WordPress can write to the plugins directory
- **Review Error Logs**: Check your WordPress debug logs for specific errors

### Settings Page Not Accessible
- **Clear Cache**: If using a caching plugin, clear all caches
- **Check User Permissions**: Ensure your user has `manage_options` capability
- **Deactivate/Reactivate**: Try deactivating and reactivating the plugin

### Shortcode Not Working
- **Check Shortcode Syntax**: Ensure you're using the correct format
- **Verify Account Name**: Confirm your Mixcloud username is correct
- **Check API Connectivity**: Ensure your server can connect to external APIs

### Mixcloud Shows Not Loading
- **Verify Account Exists**: Check that the Mixcloud account is public and has shows
- **Check Internet Connection**: Ensure your server has internet access
- **Review API Status**: The Mixcloud API may occasionally be down

## Next Steps

Once installation is complete:

1. Review the [Shortcode Usage Guide](shortcode-usage.md) for advanced configuration
2. Read the [Admin Configuration Guide](admin-configuration.md) for detailed settings
3. Check [Limitations & Compatibility](limitations.md) for known issues

## Uninstallation

To remove the plugin:

1. **Deactivate Plugin**
   - Go to **Plugins > Installed Plugins**
   - Click **Deactivate** under WP Mixcloud Archives

2. **Delete Plugin**
   - After deactivation, click **Delete**
   - Confirm deletion when prompted

3. **Clean Up (Optional)**
   - Plugin settings will remain in database unless manually removed
   - Cached data will be automatically cleaned up over time