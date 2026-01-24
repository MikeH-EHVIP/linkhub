# LinkHub

Create beautiful link-in-bio pages for WordPress with CPT-based link management and click tracking.

## Features

- **Custom Post Types**: Separate Trees and Links for maximum flexibility
- **Click Tracking**: Built-in redirection engine with click count analytics
- **Display Styles**: Bar (button), Card (image card), and Heading (text divider) styles
- **Tree Customization**: Profile header image, about text, and social links bar
- **Performance Optimized**: Clean, efficient code with minimal overhead
- **Admin UI**: Intuitive drag-and-drop interface for managing link order

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
2. Create a new design
3. Use the Divi Visual Builder with special DTOL modules:
   - **Link Title** - Displays the link's title
   - **Link Image** - Displays the link's image
   - **Link Icon** - Displays the link's icon
4. Style these modules using Divi's design controls
5. Save the design and assign it to links

### Creating a Tree

1. Go to **Link Trees → Add New**
2. Configure **Tree Settings**:
   - **Header Image**: Profile picture or logo (recommended 400x400px)
   - **About Text**: Short bio or description
   - **Social Links**: Add social media icons with URLs
3. Use the **Link Tree Items** meta box to select and order links
4. Optionally override the Link Type Design per-link
5. Drag and drop to reorder
6. Publish the tree

### Displaying Trees

Trees are displayed at their permalink URL (e.g., `/link-tree/my-tree/`). For custom page layouts, use Divi Theme Builder to create a template for the Tree post type.

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
├── linkhub.php      # Main plugin file
├── composer.json                # Autoloader configuration
├── includes/                    # PHP classes (PSR-4)
│   ├── PostTypes/              # CPT registration
│   │   ├── TreePostType.php    # Link Trees
│   │   ├── LinkPostType.php    # Individual Links
│   │   └── LinkTypeDesignPostType.php  # Link Templates
│   ├── Tracking/               # Redirect handler
│   ├── Admin/                  # Meta boxes and admin UI
│   ├── Rendering/              # Frontend rendering
│   │   ├── TreeRenderer.php    # Renders Tree pages
│   │   └── LinkTypeRenderer.php # Renders individual links with designs
│   └── Modules/                # Divi 5 module registration
├── visual-builder/             # Divi 5 Visual Builder components
│   ├── src/                    # React/JSX source files
│   │   ├── index.jsx           # Module registration
│   │   ├── link-title/         # Link Title module
│   │   ├── link-image/         # Link Image module
│   │   └── link-icon/          # Link Icon module
│   └── build/                  # Compiled JavaScript
├── assets/                     # Frontend assets
│   └── css/
│       └── modules.css         # Frontend styles
└── README.md
```

## Architecture

### Post Types

| Post Type | Slug | Purpose |
|-----------|------|---------|
| Link Tree | `LH_tree` | Collections of links with profile settings |
| Link | `LH_link` | Individual link with URL, icon, image |
| Link Type Design | `LH_link_type_design` | Divi Builder template for link appearance |

### Divi 5 Modules

The following modules are available in the Divi Visual Builder when editing Link Type Designs:

| Module | Purpose |
|--------|---------|
| Link Title | Displays the link's title text |
| Link Image | Displays the link's associated image |
| Link Icon | Displays the link's icon/emoji |

These are **design-only** modules - they show placeholder content in the builder but render actual link data on the frontend.

## Hooks & Filters

### Actions

- `LH_init` - Fired after plugin initialization
- `LH_activated` - Fired on plugin activation
- `LH_deactivated` - Fired on plugin deactivation
- `LH_link_clicked` - Fired when a link is clicked (params: `$link_id`, `$new_count`)

### Filters

- `et_builder_post_types` - Enables Divi Builder on Tree CPT

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
