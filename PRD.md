# Product Requirements Document (PRD)
## WordPress Mixcloud Integration Plugin

### Project Overview
**Product Name:** WP Mixcloud Archives  
**Version:** 1.0  
**Date:** June 29, 2025  
**Target Platform:** WordPress 6.8+  
**Site:** Now Wave Radio (nowwave.radio)

### Executive Summary
Create a WordPress plugin that integrates with the Mixcloud API to dynamically display radio show archives. The plugin will fetch and display shows from the "NowWaveRadio" Mixcloud account with a date picker for custom ranges, embedded players, and social sharing capabilities.

### Core Requirements

#### 1. Mixcloud API Integration
- **Primary Account:** NowWaveRadio (configurable via admin panel)
- **API Endpoint:** Mixcloud API v1 for user cloudcasts
- **No Caching:** Real-time API calls (suitable for ~200 hits/day traffic)
- **Error Handling:** Graceful fallbacks for API failures

#### 2. Display Features
**Default View:**
- Show previous 7 days of shows on page load
- Table format with columns: Artwork, Show Title, Player, Show Notes, Date Posted
- Embedded Mixcloud players (click-to-play, no auto-start)

**Date Range Selection:**
- Simple date picker at top of list
- Custom date range selection capability
- Refresh content based on selected dates

**Pagination:**
- Bottom pagination controls: [Back] 1 ... 7 [Next]
- Clickable page numbers and navigation buttons
- Unified styling matching site theme

#### 3. Content Display
- **Artwork:** Show thumbnails from Mixcloud
- **Show Title:** Clickable titles linking to original Mixcloud page
- **Player:** Embedded Mixcloud player widget
- **Show Notes:** Description/tags from Mixcloud
- **Date Posted:** Formatted publication date
- **Social Sharing:** Buttons for Facebook, Twitter, direct link sharing

#### 4. WordPress Integration
**Shortcode Implementation:**
```
[mixcloud_archives account="NowWaveRadio" days="7"]
```

**Admin Panel Configuration:**
- Settings page under WordPress admin
- Configurable Mixcloud account name
- Basic styling options
- API status monitoring

#### 5. Theme Compatibility
**Target Theme:** OnAir2  
**Styling Requirements:**
- Dark mode aesthetic (black/dark gray backgrounds)
- Blue accent color (#1863dc) integration
- High contrast design
- Modern sans-serif typography
- Responsive design for mobile/tablet

### Technical Specifications

#### API Requirements
- **Mixcloud API v1:** `https://api.mixcloud.com/{username}/cloudcasts/`
- **Rate Limits:** Standard Mixcloud API limits
- **Authentication:** Public API calls (no OAuth required for public shows)
- **Data Format:** JSON response parsing

#### WordPress Requirements
- **Minimum Version:** WordPress 6.8+
- **PHP Version:** 7.4+ (preferably 8.0+)
- **Dependencies:** WordPress HTTP API, shortcode functionality
- **Database:** WordPress options table for settings storage

#### Frontend Technologies
- **JavaScript:** Vanilla JS or jQuery (WordPress standard)
- **CSS:** Custom stylesheet with theme compatibility
- **Date Picker:** Lightweight date picker library
- **Social Sharing:** Open Graph meta tags + share buttons

### User Stories

#### As a Site Visitor:
1. I want to view recent radio shows with artwork and descriptions
2. I want to play shows directly on the Archives page
3. I want to select custom date ranges to find specific shows
4. I want to navigate through multiple pages of shows easily
5. I want to share shows on social media

#### As a Site Administrator:
1. I want to configure which Mixcloud account to display
2. I want to embed the show list on any page using a shortcode
3. I want the plugin to match my existing site design
4. I want to monitor if the API connection is working

### Success Metrics
- **Functionality:** All shows load correctly from Mixcloud API
- **Performance:** Page load time under 3 seconds
- **Usability:** Intuitive date picker and pagination
- **Design:** Seamless integration with OnAir2 theme
- **Engagement:** Social sharing buttons generate clicks

### Implementation Priority

#### Phase 1 (MVP):
1. Basic Mixcloud API integration
2. Simple table display with artwork, title, player, date
3. Shortcode functionality
4. Admin panel for account configuration

#### Phase 2 (Enhanced):
1. Date picker with custom ranges
2. Pagination system
3. Show notes display
4. Basic styling for OnAir2 theme

#### Phase 3 (Complete):
1. Social sharing buttons
2. Responsive design optimization
3. Error handling and fallbacks
4. Performance optimization

### Technical Considerations

#### Security:
- Sanitize all API responses
- Validate date picker inputs
- Escape output for XSS prevention

#### Performance:
- Optimize API calls with reasonable timeouts
- Lazy load embedded players
- Minify CSS/JS assets

#### Hosting Environment:
- Linux VPS with full shell access
- No shared hosting limitations
- Standard WordPress hosting requirements

### Acceptance Criteria
- [ ] Plugin installs and activates without errors
- [ ] Shows from NowWaveRadio account display correctly
- [ ] Date picker allows custom range selection
- [ ] Pagination works with unified styling
- [ ] Embedded players require click to play
- [ ] Social sharing buttons function correctly
- [ ] Admin panel allows account configuration
- [ ] Shortcode can be used on any page/post
- [ ] Design matches OnAir2 theme aesthetic
- [ ] Mobile responsive design

### Future Enhancements (Out of Scope v1.0)
- Multiple account support in single shortcode
- Advanced filtering (genre, tags, duration)
- Favorites/playlist functionality
- User comments on shows
- Advanced caching mechanisms
- Analytics integration
- Custom player skin options

---
*This PRD serves as the foundation for development planning and stakeholder alignment for the WP Mixcloud Archives plugin.*