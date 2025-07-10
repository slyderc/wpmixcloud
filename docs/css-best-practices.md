# CSS Best Practices for WP Mixcloud Archives

## Namespace Convention
All CSS classes must use the `.mixcloud-` prefix for frontend and `.wp-mixcloud-admin-` prefix for admin styles to avoid conflicts with themes and other plugins.

### Frontend Classes
```css
.mixcloud-archives-container { }
.mixcloud-list-item { }
.mixcloud-play-button { }
```

### Admin Classes  
```css
.wp-mixcloud-admin-info-box { }
.wp-mixcloud-admin-spinner { }
.wp-mixcloud-admin-message { }
```

## Enqueue Best Practices

### Frontend Styles
```php
// Only load when shortcode is present
if (has_shortcode($post->post_content, 'mixcloud_archives')) {
    wp_enqueue_style(
        'wp-mixcloud-archives-style',
        plugin_dir_url(__FILE__) . 'assets/css/style.min.css',
        array('dashicons'), // Dependencies
        WP_MIXCLOUD_ARCHIVES_VERSION
    );
}
```

### Admin Styles
```php
add_action('admin_enqueue_scripts', function($hook) {
    // Only load on plugin settings page
    if ($hook !== 'settings_page_wp-mixcloud-archives') {
        return;
    }
    
    wp_enqueue_style(
        'wp-mixcloud-archives-admin',
        plugin_dir_url(__FILE__) . 'admin/css/admin.css',
        array(),
        WP_MIXCLOUD_ARCHIVES_VERSION
    );
});
```

## Inline Styles Guidelines

### ❌ Avoid
```php
echo '<div style="margin: 10px; padding: 15px;">Content</div>';
```

### ✅ Preferred
```php
echo '<div class="wp-mixcloud-admin-box">Content</div>';
```

### ✅ Acceptable (Dynamic Styles)
```javascript
// Animation states
element.style.opacity = '0';
element.style.transition = 'opacity 0.3s ease';

// Temporary positioning
textarea.style.position = 'fixed';
textarea.style.left = '-9999px';
```

## CSS Organization

### File Structure
```
assets/
├── css/
│   ├── style.css        # Frontend styles
│   └── style.min.css    # Minified version
admin/
├── css/
│   ├── admin.css        # Admin styles
│   └── admin.min.css    # Minified version
```

### Style Categories
1. **Layout & Structure** - Grid, flexbox, positioning
2. **Components** - Buttons, dropdowns, players
3. **States** - Hover, active, loading, disabled
4. **Responsive** - Media queries for mobile/tablet
5. **Animations** - Transitions and keyframes

## Performance Tips

1. **Use CSS for animations** instead of JavaScript when possible
2. **Minimize specificity** - Avoid deep nesting
3. **Use CSS custom properties** for theme customization:
   ```css
   :root {
       --mixcloud-primary: #f1356d;
       --mixcloud-hover: #e91e63;
   }
   ```

4. **Lazy load non-critical styles** for below-the-fold content

## Theme Compatibility

1. **Avoid generic selectors** like `.container`, `.wrapper`
2. **Use data attributes** for JavaScript hooks:
   ```html
   <button class="mixcloud-play-button" data-mixcloud-action="play">
   ```

3. **Test with popular themes** (OnAir2, Astra, GeneratePress, etc.)