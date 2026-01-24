# LinkHub - Technical Documentation

A standalone WordPress plugin that creates Linktree-style link pages with built-in click tracking and flexible styling options.

## Overview

LinkHub is a free WordPress plugin for creating link-in-bio pages. It requires no theme dependencies and provides a complete solution for managing and displaying link collections with analytics.

## Post Types

### Link Tree (`LH_tree`)
The main container for a collection of links. Each Tree is a standalone page with:
- Header/profile image with shape options (round, rounded, square)
- Optional fade effect on hero image
- Title and bio/about text
- Social media links bar
- List of links with drag-and-drop ordering

**Location**: `includes/PostTypes/TreePostType.php`

### Link (`LH_link`)
Individual link items that can be added to Trees. Each Link has:
- Destination URL
- Title (post title)
- Optional description/subtitle
- Optional icon (emoji or icon class)
- Optional image
- Display style (bar, card, or heading)
- Background and text color customization
- Click tracking analytics

**Location**: `includes/PostTypes/LinkPostType.php`

## Directory Structure

```
linkhub/
├── assets/
│   ├── css/
│   │   └── modules.css          # Frontend styles
│   └── js/
│       └── modules.js           # Frontend JavaScript
├── includes/
│   ├── Admin/
│   │   ├── MetaBoxes.php        # Admin meta boxes for post types
│   │   ├── ClickwhaleImporter.php  # CSV import from Clickwhale
│   │   └── ExportImport.php     # Tree export/import functionality
│   ├── Export/
│   │   └── TreeExporter.php     # Export tree data to JSON
│   ├── Modules/
│   │   └── TreeOfLinksModules.php  # Module-related utilities
│   ├── PostTypes/
│   │   ├── LinkPostType.php     # Link CPT registration
│   │   └── TreePostType.php     # Tree CPT registration
│   ├── Rendering/
│   │   ├── LinkTypeRenderer.php # Renders individual links
│   │   └── TreeRenderer.php     # Renders tree pages on frontend
│   ├── Shortcodes/              # Shortcode handlers (if any)
│   └── Tracking/
│       └── RedirectHandler.php  # Click tracking via /go/ID/ URLs
├── scripts/
│   ├── build-plugin-zip.ps1     # PowerShell build script
│   ├── build-plugin-zip.sh      # Bash build script
│   └── rebrand-to-linkhub.ps1   # Rebranding utility
├── linkhub.php                  # Main plugin file
├── autoload.php                 # PSR-4 autoloader
├── composer.json
├── package.json
├── CHANGELOG.md
├── INSTALL.md
├── PLAN.md
└── PROJECT.md                   # This file
```

## Key Features

### Click Tracking
All links use tracking URLs (`/go/{link_id}/`) that record clicks before redirecting to the destination URL.
- **Important**: After plugin activation or permalink changes, visit Settings > Permalinks to flush rewrite rules.
- Tracks click count and last clicked timestamp
- Cached for 12 hours for optimal performance

### Display Styles
Three built-in styles for rendering links:
- **Bar Style**: Linktree-style horizontal button with optional thumbnail
- **Card Style**: Image card with colored banner below
- **Heading Style**: Text divider for organizing link sections (small, medium, large)

All styles support customizable background and text colors per link.

### Inline Headings
Add section headings directly in the Tree builder without creating separate Link CPT items:
- Three sizes: small, medium, large
- Edit text and size inline
- Drag-and-drop ordering with links
- Insert headings at specific positions

### Tree Builder Interface
- Image thumbnails for all links
- "Insert here" buttons for adding items at specific positions
- Drag-and-drop reordering
- Visual distinction between links and headings

### Tree Page Styling Options
Each Tree can be customized with:
- **Background Color**: Solid color page background
- **Background Image**: Optional repeating pattern/image
- **Hero Image Shape**: Round (circle), rounded corners, or square
- **Hero Fade Effect**: Gradient overlay that fades image into background
- **Title Color**: Custom color for the tree name
- **Bio Color**: Custom color for about/description text
- **Social Icon Style**: Circle, rounded, square, or minimal (no background)
- **Social Icon Color**: Default icon color (platform colors on hover)

### Import/Export
- **Export**: Export entire trees to JSON format with all settings and links
- **Import**: Import trees from JSON
- **Clickwhale CSV**: Import links from Clickwhale CSV exports

## Meta Keys Reference

### Tree Post Type (`_LH_*`)
| Key | Description |
|-----|-------------|
| `_LH_tree_links` | Serialized array of link IDs for ordering |
| `_LH_header_image` | Header image attachment ID |
| `_LH_about_text` | Bio/description text |
| `_LH_social_links` | Array of social platform/URL pairs |
| `_LH_background_color` | Page background color |
| `_LH_background_image` | Background pattern attachment ID |
| `_LH_hero_shape` | Hero image shape (round/rounded/square) |
| `_LH_hero_fade` | Enable fade effect (1/0) |
| `_LH_title_color` | Title text color |
| `_LH_bio_color` | Bio text color |
| `_LH_social_style` | Social icon style (circle/rounded/square/minimal) |
| `_LH_social_color` | Social icon color |

### Link Post Type (`_LH_*`)
| Key | Description |
|-----|-------------|
| `_LH_url` | Destination URL |
| `_LH_description` | Optional subtitle/description |
| `_LH_icon` | Icon class or emoji |
| `_LH_image_id` | Featured image attachment ID |
| `_LH_display_style` | Display style (bar/card/heading) |
| `_LH_background_color` | Link background color |
| `_LH_text_color` | Link text color |
| `_LH_heading_size` | Heading size for heading style (small/medium/large) |
| `_LH_click_count` | Total click count |
| `_LH_last_clicked` | Last click timestamp |

## Rendering Flow

1. User visits Tree page (`/link-tree/slug/`)
2. `TreeRenderer::render_tree_content()` intercepts content filter
3. Fetches tree metadata and associated links
4. For each link, `LinkTypeRenderer::render()` determines rendering method:
   - `render_legacy_bar()` for bar style
   - `render_legacy_card()` for card style
   - `render_heading()` for heading style
5. Inline styles applied for tree-level and link-level customizations
6. Social links rendered if configured
7. Complete HTML output returned

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

### Requirements
- WordPress 6.0+
- PHP 7.4+
- Composer for dependency management

### Building
```bash
# Install PHP dependencies
composer install

# Install npm dependencies
npm install

# Build assets
npm run build

# Create distribution zip
./scripts/build-plugin-zip.ps1  # Windows
./scripts/build-plugin-zip.sh   # Linux/Mac
```

### Cache Invalidation
Manually invalidate link cache:
```php
use ElyseVIP\LinkHub\Tracking\RedirectHandler;
RedirectHandler::invalidate_cache($link_id);
```

### Extending Click Tracking
Hook into link clicks for custom analytics:
```php
add_action('LH_link_clicked', function($link_id, $click_count) {
    // Send to Google Analytics, custom tracking, etc.
}, 10, 2);
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

GPL-2.0-or-later
