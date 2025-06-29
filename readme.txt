=== WP Mixcloud Archives ===
Contributors: yourname
Donate link: https://yourwebsite.com/donate
Tags: mixcloud, audio, player, archive, shortcode, embed
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display Mixcloud archives with embedded players, date filtering, pagination, and social sharing functionality.

== Description ==

WP Mixcloud Archives is a powerful WordPress plugin that integrates with the Mixcloud API to display your Mixcloud shows in a beautiful, organized table format. Perfect for radio stations, podcasters, and DJs who want to showcase their Mixcloud content on their WordPress website.

**Key Features:**

* **Mixcloud API Integration** - Automatically fetches your latest shows from Mixcloud
* **Embedded Player** - Built-in Mixcloud players for each show
* **Date Filtering** - Custom date range selection with date pickers
* **Pagination** - Navigate through multiple pages of shows
* **Social Sharing** - Share shows on Facebook, Twitter, or copy direct links
* **Responsive Design** - Optimized for all device sizes
* **Shortcode Support** - Easy embedding with `[mixcloud_archives]` shortcode
* **Admin Settings** - Configure your Mixcloud username and display options
* **Performance Optimized** - Efficient API calls and caching
* **Security Enhanced** - Input sanitization and output escaping

**Perfect for:**
* Radio stations
* Podcasters
* DJs and musicians
* Music bloggers
* Audio content creators

**Shortcode Usage:**
`[mixcloud_archives account="your-username"]` - Display shows for specific Mixcloud account
`[mixcloud_archives account="NowWaveRadio" limit="20"]` - Display 20 shows
`[mixcloud_archives account="username" show_date_filter="no"]` - Disable date filtering
`[mixcloud_archives account="username" mini_player="no"]` - Use full-size players

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-mixcloud-archives` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->WP Mixcloud Archives screen to configure the plugin
4. Enter your Mixcloud username in the settings
5. Use the `[mixcloud_archives]` shortcode in any post or page to display your archives

== Shortcode Parameters ==

The `[mixcloud_archives]` shortcode supports the following parameters:

**Required Parameters:**
* `account` - Mixcloud username (required)

**Display Options:**
* `limit` - Number of shows to fetch from API (default: 10, max: 50)
* `days` - Show archives from last X days (default: 30)
* `start_date` - Show archives from specific date (YYYY-MM-DD format)
* `end_date` - Show archives until specific date (YYYY-MM-DD format)

**Player Options:**
* `mini_player` - Use mini players: yes/no (default: yes)
* `lazy_load` - Enable lazy loading: yes/no (default: yes)

**Interface Options:**
* `show_date_filter` - Show date filter controls: yes/no (default: yes)
* `show_pagination` - Show pagination controls: yes/no (default: yes)
* `show_social` - Show social sharing buttons: yes/no (default: yes)
* `per_page` - Items per page (default: 10, max: 50)
* `page` - Starting page number (default: 1)

**Usage Examples:**
```
[mixcloud_archives account="NowWaveRadio"]
[mixcloud_archives account="username" limit="20" mini_player="no"]
[mixcloud_archives account="username" days="7" show_date_filter="no"]
[mixcloud_archives account="username" start_date="2024-01-01" end_date="2024-01-31"]
```

== Frequently Asked Questions ==

= Do I need a Mixcloud account? =

Yes, you need a Mixcloud account with published shows to use this plugin.

= Is the Mixcloud API free? =

Yes, the Mixcloud API v1 used by this plugin is free for public content.

= Can I customize the appearance? =

Yes, the plugin includes CSS classes for custom styling, and you can override the default styles in your theme.

= Does it work with caching plugins? =

Yes, the plugin is compatible with popular caching plugins and includes its own smart caching mechanism.

= Can I display shows from multiple Mixcloud users? =

Currently, the plugin supports one Mixcloud user per installation. Multiple user support may be added in future versions.

== Screenshots ==

1. Archive display with embedded players
2. Admin settings page
3. Date range selector
4. Social sharing buttons
5. Mobile responsive view

== Changelog ==

= 1.0.0 =
* Initial release
* Mixcloud API v1 integration
* Basic archive display with table layout
* Embedded Mixcloud players
* Date range filtering
* Pagination system
* Social sharing buttons
* Admin settings page
* Shortcode functionality
* Responsive design
* Performance optimizations
* Security enhancements

== Upgrade Notice ==

= 1.0.0 =
Initial release of WP Mixcloud Archives plugin.

== Third Party Services ==

This plugin connects to Mixcloud's API (https://www.mixcloud.com/developers/) to retrieve show information and embed players. By using this plugin, you agree to Mixcloud's Terms of Service and Privacy Policy:

* Mixcloud Terms of Service: https://www.mixcloud.com/terms/
* Mixcloud Privacy Policy: https://www.mixcloud.com/privacy/

No personal data is sent to Mixcloud beyond what is necessary to retrieve public show information.