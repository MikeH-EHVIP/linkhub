# LinkHub

Create beautiful link-in-bio pages for WordPress with CPT-based link management and click tracking.

## Features

- **Tree Builder UI**: Modern three-panel admin interface with sidebar, editor, and live preview
- **Custom Post Types**: Separate Trees and Links for maximum flexibility
- **Click Tracking**: Built-in redirection engine with click count analytics
- **Display Styles**: Bar (button), Card (image card), and Heading (text divider) styles
- **Inline Headings**: Add section headings directly in the tree builder
- **Tree Customization**: Profile header image, about text, and social links bar
- **Live Preview**: Real-time iframe preview updates as you edit
- **Drag-and-Drop**: Reorder links and headings with visual feedback
- **Inline Editing**: Edit links and headings via modal popups
- **Auto-save**: Changes automatically saved with status indicator
- **Performance Optimized**: Clean, efficient code with minimal overhead

## Installation

1. Clone or download this plugin to your WordPress plugins directory:
   ```
   wp-content/plugins/linkhub/
   ```

2. Install Composer dependencies:
   ```bash
   composer install
   ```

3. Activate the plugin through WordPress admin

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Usage

### Creating Links

1. Go to **Link Trees → Links** in WordPress admin
2. Add a new link with:
   - Title (link text)
   - Destination URL
   - Icon (emoji or icon class)
   - Optional image
   - Display style (bar, card, or heading)
   - Background and text colors
3. Save the link

### Creating a Tree

#### Using Tree Builder (Recommended)

1. Go to **Link Trees → Tree Builder**
2. Click **+ New Tree** in the sidebar or select an existing tree
3. Use the sidebar navigation to configure:
   - **Links**: Add, reorder, and edit links with drag-and-drop
   - **Profile**: Set header image and about text
   - **Social Links**: Add social media icons with URLs
   - **Appearance**: Configure colors and typography
   - **Display**: Set hero shape, fade effects, and visibility options
4. Changes auto-save and preview updates in real-time

#### Using Classic Editor

1. Go to **Link Trees → Add New**
2. Configure **Tree Settings** in the meta boxes
3. Use the **Link Tree Items** meta box to add and reorder links
4. Publish the tree

### Displaying Trees

Trees are displayed at their permalink URL (e.g., `/link-tree/my-tree/`).

## Click Tracking

All links automatically use tracking URLs in the format:
```
https://yoursite.com/go/{link-id}/
```

View click statistics in the **Click Statistics** meta box when editing a link.

## Caching

The plugin uses WordPress object cache for:
- Redirect URL lookups (12-hour expiration)
- Click count caching
- Rendered output caching (1-hour expiration)

For high-traffic sites, consider using Redis or Memcached.

## File Structure

```
linkhub/
├── linkhub.php                  # Main plugin file
├── composer.json                # Composer configuration
├── package.json                 # NPM configuration
├── autoload.php                 # PSR-4 autoloader
├── includes/                    # PHP classes (PSR-4)
│   ├── PostTypes/              # CPT registration
│   │   ├── TreePostType.php    # Link Trees
│   │   └── LinkPostType.php    # Individual Links
│   ├── Tracking/               # Click tracking and redirects
│   │   └── RedirectHandler.php
│   ├── Admin/                  # Meta boxes and admin UI
│   │   ├── MetaBoxes.php       # Classic editor meta boxes
│   │   ├── TreeBuilderPage.php # Tree Builder admin page
│   │   ├── RestController.php  # REST API endpoints
│   │   ├── ClickwhaleImporter.php
│   │   └── ExportImport.php
│   ├── Rendering/              # Frontend rendering
│   │   ├── TreeRenderer.php    # Renders Tree pages
│   │   └── LinkTypeRenderer.php # Renders individual links
│   ├── Export/                 # Export functionality
│   │   └── TreeExporter.php
│   ├── Modules/                # Module utilities
│   │   └── TreeOfLinksModules.php
│   └── templates/admin/        # Admin templates
│       └── tree-builder-page.php
├── assets/                     # Frontend assets
│   ├── css/
│   │   ├── modules.css         # Frontend styles
│   │   └── tree-builder.css    # Tree Builder admin styles
│   └── js/
│       ├── modules.js          # Frontend JavaScript
│       └── tree-builder.js     # Tree Builder admin JavaScript
├── scripts/                    # Build scripts
│   ├── build-plugin-zip.ps1
│   └── build-plugin-zip.sh
└── README.md
```

## Architecture

### Post Types

| Post Type | Slug | Purpose |
|-----------|------|---------|
| Link Tree | `LH_tree` | Collections of links with profile settings |
| Link | `LH_link` | Individual link with URL, icon, image, and tracking |

### Display Styles

Three built-in styles for rendering links:
- **Bar**: Linktree-style button with optional thumbnail
- **Card**: Image card with colored banner
- **Heading**: Text divider for organizing sections (small/medium/large sizes)

## Hooks & Filters

### Actions

- `LH_init` - Fired after plugin initialization
- `LH_activated` - Fired on plugin activation
- `LH_deactivated` - Fired on plugin deactivation
- `LH_link_clicked` - Fired when a link is clicked (params: `$link_id`, `$new_count`)

### Filters

- `LH_link_tracking_url` - Modify tracking URL format
- `LH_tree_links` - Filter links before rendering
- `LH_social_platforms` - Add/modify available social platforms

## Development

### Extending Click Tracking

Hook into link clicks to add custom analytics:

```php
add_action('LH_link_clicked', function($link_id, $click_count) {
    // Send to Google Analytics, Mixpanel, etc.
}, 10, 2);
```

### Cache Invalidation

Manually invalidate link cache:

```php
use ElyseVIP\LinkHub\Tracking\RedirectHandler;

RedirectHandler::invalidate_cache($link_id);
```

## License

GPL-2.0-or-later

## Author

ElyseVIP - https://elysevipatd.com
