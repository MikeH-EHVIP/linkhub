# Installation & Testing Guide

## Prerequisites

Before installing this plugin, ensure you have:

1. **WordPress 6.0 or higher**
2. **PHP 7.4 or higher**
3. **Divi Theme or Extra Theme** with Divi 5

## Installation Steps

### 1. Upload Plugin Files

Copy the entire `linkhub` folder to:
```
wp-content/plugins/linkhub/
```

### 2. Install Dependencies (Optional)

If you have Composer installed:
```bash
cd wp-content/plugins/linkhub
composer install --no-dev --optimize-autoloader
```

**Note:** The plugin includes a fallback autoloader, so Composer is not strictly required.

### 3. Build Frontend Assets (Optional for Development)

If you want to modify the React components:

```bash
npm install
npm run build
```

For development with hot reload:
```bash
npm run dev
```

### 4. Activate Plugin

1. Go to **WordPress Admin → Plugins**
2. Find **LinkHub**
3. Click **Activate**

Upon activation, the plugin will:
- Register custom post types (`LH_tree` and `LH_link`)
- Add rewrite rules for tracking (`/go/{id}/`)
- Flush rewrite rules automatically

## Testing Checklist

### ✓ Step 1: Create Your First Link

1. Go to **Link Trees → Links → Add New**
2. Add a test link:
   - **Title:** "Visit Google"
   - **Destination URL:** `https://google.com`
   - **Icon:** `🔗` (emoji) or `fa-solid fa-link` (Font Awesome)
3. Click **Publish**

### ✓ Step 2: Create More Links

Repeat the above to create 3-5 links for testing different display modes.

### ✓ Step 3: Create a Link Tree

1. Go to **Link Trees → Add New**
2. Give it a title: "My Link Tree"
3. In the **Link Tree Items** meta box:
   - Select a link from the dropdown
   - Click **Add Link**
   - Repeat for all your test links
   - Drag to reorder them
4. Click **Publish**

### ✓ Step 4: Add Module to a Page

1. Edit any page with **Divi Builder**
2. Click **Add Module**
3. Search for **Tree of Links**
4. In the module settings:
   - **Select Link Tree:** Choose your tree
   - **Display Mode:** Try "List" first
5. Save and preview

### ✓ Step 5: Test Display Modes

Switch between **List** and **Card** modes to see the different layouts:
- **List Mode:** Button-style links in a vertical list
- **Card Mode:** Visual grid with icons/images

### ✓ Step 6: Test Click Tracking

1. On the frontend, click one of your links
2. Verify you're redirected to the destination URL
3. Go back to **Link Trees → Links**
4. Edit the link you clicked
5. Check the **Click Statistics** sidebar - it should show 1 click

### ✓ Step 7: Test Reordering Links

1. Edit your Link Tree
2. Drag links to reorder them
3. **Update** the page
4. Refresh the frontend to see the new order

### ✓ Step 8: Test Caching

If you have object caching enabled (Redis/Memcached):
1. Click a link multiple times
2. Verify clicks are counted correctly
3. Edit the link's URL
4. Verify the new URL is used immediately (cache invalidation works)

## Common Issues & Solutions

### Issue: "Permalink returns 404"

**Solution:** Go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules.

### Issue: "Module doesn't appear in Visual Builder"

**Solution:** 
1. Verify Divi 5 is active
2. Clear browser cache
3. Check that `modules-json/tree-of-links/module.json` exists

### Issue: "Autoload errors"

**Solution:** 
1. Run `composer install` to generate proper autoloader
2. Or verify `autoload.php` fallback exists in plugin root

### Issue: "Links don't track clicks"

**Solution:**
1. Check that rewrite rules are flushed
2. Verify `wp_cache` is working (test with Redis/Memcached if available)
3. Check browser console for JavaScript errors

## Performance Optimization

### Enable Object Caching

For high-traffic sites:

1. Install Redis or Memcached
2. Install object cache drop-in plugin
3. Verify caching is working:
   ```php
   var_dump(wp_cache_get('test'));
   ```

### Enable Fragment Caching

The plugin automatically caches rendered output for 1 hour. To adjust:

Edit `includes/Modules/TreeOfLinks/TreeOfLinksTrait/RenderCallbackTrait.php`:

```php
wp_cache_set($cache_key, $html, 'LH_renders', 3600); // Change 3600 to desired seconds
```

## Next Steps

### Customize Styles

Edit `assets/css/modules.css` to match your brand:
- Colors
- Spacing
- Hover effects
- Responsive breakpoints

### Add Analytics Integration

Hook into clicks:

```php
add_action('LH_link_clicked', function($link_id, $click_count) {
    // Send to Google Analytics
    // Send to Mixpanel
    // Log to database
}, 10, 2);
```

### Extend with Custom Fields

Add more meta fields to links:
- Description
- Custom colors
- Display settings
- A/B testing variants

## Support

For issues or questions:
- Check `README.md` for API documentation
- Review code comments in PHP files
- Check browser console for JavaScript errors

Happy linking! 🔗
