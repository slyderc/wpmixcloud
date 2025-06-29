# WP Mixcloud Archives - Quick Start

## Shortcode Options

Use the `[mixcloud_archives]` shortcode with these options:

### Required Options

- **`account`** - The Mixcloud username to display archives for. Required parameter that specifies which account's cloudcasts to fetch and display.

### Display Options

- **`limit`** - Maximum number of cloudcasts to fetch from API (1-100, default: 10). Controls how many tracks are available for pagination and filtering.

- **`per_page`** - Number of cloudcasts to show per page (1-50, default: 10). Determines pagination size for better performance on large collections.

- **`mini_player`** - Use compact player layout (`yes`/`no`, default: `yes`). Mini players take less space and load faster than full-size embedded players.

- **`lazy_load`** - Enable lazy loading for players and images (`yes`/`no`, default: `yes`). Improves page load speed by loading content as users scroll.

- **`show_social`** - Display social sharing buttons (`yes`/`no`, default: `yes`). Includes Facebook, Twitter, Bluesky, and copy link options for each track.

### Filtering Options

- **`days`** - Show cloudcasts from the last N days (1-365, default: 30). Filters tracks by creation date to show recent content only.

- **`start_date`** - Filter from specific date in YYYY-MM-DD format. Overrides the `days` parameter when used with custom date ranges.

- **`end_date`** - Filter to specific date in YYYY-MM-DD format. Works with `start_date` to create custom date ranges for historical archives.

- **`show_date_filter`** - Show interactive date range picker (`yes`/`no`, default: `yes`). Allows users to dynamically filter archives without page reloads.

### Navigation Options

- **`page`** - Starting page number (default: 1). Useful for deep-linking to specific pages in paginated results.

- **`show_pagination`** - Display pagination controls (`yes`/`no`, default: `yes`). Shows page numbers and navigation for collections larger than `per_page`.

## Example Usage

```
[mixcloud_archives account="NowWaveRadio" limit="20" per_page="5" days="7"]
```

This displays the last 7 days of tracks from NowWaveRadio, showing 5 tracks per page with up to 20 total tracks available.