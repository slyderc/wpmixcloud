# Shortcode Usage Guide

## Basic Usage

The WP Mixcloud Archives plugin provides a powerful `[mixcloud_archives]` shortcode to display Mixcloud shows on your WordPress site.

### Minimum Required Shortcode

```
[mixcloud_archives account="your-username"]
```

Replace `your-username` with your actual Mixcloud username.

## Complete Parameter Reference

### Required Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `account` | Mixcloud username (required) | `account="NowWaveRadio"` |

### Display Options

| Parameter | Default | Description | Valid Values |
|-----------|---------|-------------|--------------|
| `limit` | `10` | Number of shows to fetch from API | `1-50` |
| `days` | `30` | Show archives from last X days | Any positive number |
| `start_date` | _(empty)_ | Show archives from specific date | `YYYY-MM-DD` format |
| `end_date` | _(empty)_ | Show archives until specific date | `YYYY-MM-DD` format |

### Player Options

| Parameter | Default | Description | Valid Values |
|-----------|---------|-------------|--------------|
| `mini_player` | `yes` | Use compact mini players | `yes`, `no` |
| `lazy_load` | `yes` | Enable lazy loading for performance | `yes`, `no` |

### Interface Options

| Parameter | Default | Description | Valid Values |
|-----------|---------|-------------|--------------|
| `show_date_filter` | `yes` | Display date filter controls | `yes`, `no` |
| `show_pagination` | `yes` | Display pagination controls | `yes`, `no` |
| `show_social` | `yes` | Display social sharing buttons | `yes`, `no` |
| `per_page` | `10` | Items displayed per page | `1-50` |
| `page` | `1` | Starting page number | Any positive number |

## Usage Examples

### Basic Examples

**Simple display with default settings:**
```
[mixcloud_archives account="NowWaveRadio"]
```

**Show more items:**
```
[mixcloud_archives account="username" limit="20"]
```

**Disable mini players for full-size players:**
```
[mixcloud_archives account="username" mini_player="no"]
```

### Date Filtering Examples

**Show only recent content:**
```
[mixcloud_archives account="username" days="7"]
```

**Show content from specific date range:**
```
[mixcloud_archives account="username" start_date="2024-01-01" end_date="2024-01-31"]
```

**Disable date filter interface:**
```
[mixcloud_archives account="username" show_date_filter="no"]
```

### Interface Customization Examples

**Minimal interface (no extra controls):**
```
[mixcloud_archives account="username" show_date_filter="no" show_pagination="no" show_social="no"]
```

**Custom pagination settings:**
```
[mixcloud_archives account="username" per_page="5" page="2"]
```

**Performance-focused (no lazy loading):**
```
[mixcloud_archives account="username" lazy_load="no"]
```

### Advanced Examples

**Complete custom configuration:**
```
[mixcloud_archives 
    account="NowWaveRadio" 
    limit="25" 
    days="14" 
    mini_player="no" 
    show_date_filter="yes" 
    show_pagination="yes" 
    show_social="yes" 
    per_page="5"
]
```

**Date range with custom interface:**
```
[mixcloud_archives 
    account="username" 
    start_date="2024-01-01" 
    end_date="2024-03-31" 
    mini_player="yes" 
    show_date_filter="no" 
    per_page="15"
]
```

## Parameter Details

### Account Parameter

The `account` parameter is the only required parameter. It should match exactly with your Mixcloud username:

- **Correct**: `account="NowWaveRadio"` (for https://www.mixcloud.com/NowWaveRadio/)
- **Incorrect**: `account="https://www.mixcloud.com/NowWaveRadio/"` (don't include full URL)

### Date Parameters

When using date parameters, follow these guidelines:

**Date Format**: Always use `YYYY-MM-DD` format
- **Correct**: `start_date="2024-01-15"`
- **Incorrect**: `start_date="01/15/2024"` or `start_date="15-01-2024"`

**Date Logic**:
- If both `start_date` and `end_date` are provided, shows between these dates will be displayed
- If only `start_date` is provided, shows from that date forward will be displayed
- If only `end_date` is provided, shows up to that date will be displayed
- The `days` parameter is ignored when date range parameters are used

### Limit and Pagination

**Understanding Limits**:
- `limit`: Total number of shows to fetch from the Mixcloud API
- `per_page`: Number of shows to display per page on your website

**Example Scenario**:
```
[mixcloud_archives account="username" limit="50" per_page="10"]
```
This will:
1. Fetch 50 shows from Mixcloud API
2. Display them in chunks of 10 per page
3. Create 5 pages of content with pagination controls

### Performance Considerations

**Lazy Loading** (`lazy_load="yes"`):
- Players load only when scrolled into view
- Improves page load time
- Recommended for pages with many shows

**Mini Players** (`mini_player="yes"`):
- Smaller player interface
- Better for mobile devices
- Faster loading than full players

## Error Handling

### Common Shortcode Errors

**Missing Account Parameter**:
```
[mixcloud_archives]
```
**Result**: Error message displayed instead of content

**Invalid Account Name**:
```
[mixcloud_archives account="non-existent-user"]
```
**Result**: No shows found message or error

**Invalid Date Format**:
```
[mixcloud_archives account="username" start_date="01/15/2024"]
```
**Result**: Date validation error

### Troubleshooting Tips

1. **No Shows Displayed**:
   - Verify the Mixcloud account exists and is public
   - Check that the account has published shows
   - Ensure your server can connect to the Mixcloud API

2. **Players Not Loading**:
   - Check if JavaScript is enabled in the browser
   - Verify there are no JavaScript errors in browser console
   - Try disabling lazy loading: `lazy_load="no"`

3. **Date Filter Not Working**:
   - Ensure date format is correct (YYYY-MM-DD)
   - Check that the date range contains shows
   - Verify JavaScript is functioning properly

## CSS Styling

The shortcode generates HTML with specific CSS classes that you can style:

### Main Container Classes
- `.mixcloud-archives-container` - Main wrapper
- `.mixcloud-archives-table` - Table container
- `.mixcloud-date-filter` - Date filter controls
- `.mixcloud-pagination` - Pagination controls

### Individual Item Classes
- `.mixcloud-cloudcast-row` - Individual show row
- `.mixcloud-player-wrapper` - Player container
- `.mixcloud-social-share` - Social sharing buttons

### Example Custom CSS
```css
.mixcloud-archives-container {
    max-width: 100%;
    margin: 20px 0;
}

.mixcloud-cloudcast-row {
    border-bottom: 1px solid #eee;
    padding: 15px 0;
}

.mixcloud-player-wrapper {
    border-radius: 8px;
    overflow: hidden;
}
```

## Multiple Shortcodes

You can use multiple shortcodes on the same page:

```
<h2>Recent Shows</h2>
[mixcloud_archives account="username" days="7" per_page="5"]

<h2>January Archives</h2>
[mixcloud_archives account="username" start_date="2024-01-01" end_date="2024-01-31"]
```

Each shortcode instance operates independently with its own settings and pagination.