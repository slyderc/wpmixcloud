# Shortcode Parameters Analysis

## Documentation vs Implementation Comparison

### ❌ Parameters Documented but NOT Implemented

1. **`limit`** - Documented as "Number of shows to fetch (1-100, default: 10)"
   - **Reality**: Implementation hardcodes `limit` to 0 (unlimited) on line 120 of `class-shortcode-handler.php`
   - **Impact**: Users cannot limit the number of shows displayed

2. **`per_page`** - Documented as "Items per page (1-50, default: 10)"
   - **Reality**: Not implemented anywhere in the codebase
   - **Impact**: No pagination system exists

3. **`page`** - Documented as "Starting page number (default: 1)"
   - **Reality**: Not implemented anywhere in the codebase  
   - **Impact**: No pagination system exists

4. **`show_pagination`** - Documented as "Show pagination controls (yes/no, default: yes)"
   - **Reality**: Not implemented anywhere in the codebase
   - **Impact**: No pagination controls exist

### ⚠️ Parameters with Incorrect Documentation

1. **`days`** - Documented as "default: 30"
   - **Reality**: Default is 0 (show all cloudcasts)
   - **Impact**: Misleading default behavior

### ✅ Parameters Correctly Documented and Implemented

1. **`account`** (required) - Mixcloud username ✓
2. **`start_date`** - Show archives from date (YYYY-MM-DD) ✓
3. **`end_date`** - Show archives until date (YYYY-MM-DD) ✓
4. **`mini_player`** - Use compact players (yes/no, default: yes) ✓
5. **`lazy_load`** - Enable lazy loading (yes/no, default: yes) ✓
6. **`show_date_filter`** - Show date filter controls (yes/no, default: yes) ✓
7. **`show_social`** - Show social sharing buttons (yes/no, default: yes) ✓

## Current Implementation Reality

### Actual Parameters Supported:
```php
$parsed_atts = shortcode_atts(array(
    'account'          => '',           // Required
    'days'             => 0,            // 0 = show all shows (no date filtering)
    'limit'            => 10,           // Ignored - hardcoded to 0 (unlimited)
    'lazy_load'        => 'yes',        // Enable image lazy loading
    'mini_player'      => 'yes',        // Use compact Mixcloud players
    'show_date_filter' => 'yes',        // Show date filtering controls
    'start_date'       => '',           // YYYY-MM-DD format
    'end_date'         => '',           // YYYY-MM-DD format
    'show_social'      => 'yes',        // Show social sharing buttons
), $atts, 'mixcloud_archives');
```

### What Actually Works:
- **Infinite scroll approach**: Loads ALL cloudcasts from account (no pagination)
- **Date filtering**: Filter by days back, or specific date range
- **UI controls**: Toggle date filter, social buttons, player size, lazy loading
- **Performance**: Client-side filtering and infinite scroll

## Recommendations

### Option 1: Implement Missing Features
- Add pagination system with `per_page`, `page`, `show_pagination`
- Implement proper `limit` parameter functionality
- Update infinite scroll to work with pagination

### Option 2: Update Documentation (DONE)
- Remove references to unimplemented pagination features
- Correct default values and descriptions
- Focus on actual functionality (infinite scroll + filtering)

### Option 3: Hybrid Approach
- Keep current infinite scroll for simplicity
- Add optional `limit` parameter for performance control
- Document as "load-all with optional limit" rather than pagination

## Files Updated

1. **`admin/class-wp-mixcloud-archives-admin.php`**
   - Removed documentation for `limit`, `per_page`, `page`, `show_pagination`
   - Corrected `days` default value documentation
   - Updated example usage

2. **`CLAUDE.md`**
   - Updated shortcode parameters list
   - Removed pagination references
   - Corrected default values

## Current Accurate Shortcode Examples

```php
// Basic usage (shows all cloudcasts)
[mixcloud_archives account="NowWaveRadio"]

// Recent shows only
[mixcloud_archives account="username" days="7"]

// Date range
[mixcloud_archives account="username" start_date="2024-01-01" end_date="2024-01-31"]

// Minimal interface
[mixcloud_archives account="username" show_date_filter="no" show_social="no"]

// Full-size players
[mixcloud_archives account="username" mini_player="no"]
```