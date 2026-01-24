# LinkHub

A WordPress plugin that creates Linktree-style link pages with Divi 5 Visual Builder integration.

## Overview

This plugin provides a "freemium" model for creating link tree pages:
- **Free Tier**: Basic link tree pages with customizable styling options (colors, hero image shapes, social icons)
- **Premium Tier**: Full Divi 5 Visual Builder customization through Link Type Designs

## Post Types

### Link Tree (`LH_tree`)
The main container for a collection of links. Each Tree is a standalone page with:
- Header/profile image with shape options (round, rounded, square)
- Optional fade effect on hero image
- Title and bio/about text
- Social media links bar
- List of links

**Location**: `includes/PostTypes/TreePostType.php`

### Link (`LH_link`)
Individual link items that can be added to Trees. Each Link has:
- Destination URL
- Title (post title)
- Optional description/subtitle
- Optional icon or image
- Display style (bar or card) for legacy rendering
- Background and text color customization
- Optional Link Type Design assignment

**Location**: `includes/PostTypes/LinkPostType.php`

### Link Type Design (`LH_link_type_design`)
Custom link layouts created with Divi 5 Visual Builder. These templates define how links appear and can use special modules to display link data dynamically.

**Location**: `includes/PostTypes/LinkTypeDesignPostType.php`

## Divi 5 Modules

Located in `visual-builder/src/`:

### Link Title (`dtol/link-title`)
Displays the link's title with font styling options.
- Files: `link-title/module.json`, `link-title/styles.jsx`

### Link Image (`dtol/link-image`)
Displays the link's featured image with border-radius and sizing options.
- Files: `link-image/module.json`, `link-image/styles.jsx`

### Link Icon (`dtol/link-icon`)
Displays the link's icon.
- Files: `link-icon/module.json`, `link-icon/styles.jsx`

### Link Description (`dtol/link-description`)
Displays optional description/subtitle text below the link title.
- Files: `link-description/module.json`, `link-description/styles.jsx`

## Directory Structure

```
linkhub/
├── assets/
│   └── css/
│       └── modules.css          # Frontend styles
├── includes/
│   ├── Admin/
│   │   └── MetaBoxes.php        # Admin meta boxes for all post types
│   ├── Modules/
│   │   └── TreeOfLinksModules.php  # Divi module registration & rendering
│   ├── PostTypes/
│   │   ├── LinkPostType.php     # Link CPT registration
│   │   ├── LinkTypeDesignPostType.php  # Link Type Design CPT
│   │   └── TreePostType.php     # Tree CPT registration
│   ├── Rendering/
│   │   ├── LinkTypeRenderer.php # Renders individual links
│   │   └── TreeRenderer.php     # Renders tree pages on frontend
│   └── Tracking/
│       └── RedirectHandler.php  # Click tracking via /go/ID/ URLs
├── visual-builder/
│   ├── src/
│   │   ├── index.jsx            # Module registration entry point
│   │   ├── link-title/          # Link Title module
│   │   ├── link-image/          # Link Image module
│   │   ├── link-icon/           # Link Icon module
│   │   ├── link-description/    # Link Description module
│   │   └── tree-display/        # Tree Display module (container)
│   └── build/
│       └── linkhub.js  # Compiled bundle
├── linkhub.php       # Main plugin file
├── CHANGELOG.md
└── PROJECT.md                   # This file
```

## Key Features

### Click Tracking
All links use tracking URLs (`/go/{link_id}/`) that record clicks before redirecting to the destination URL.
- **Important**: After plugin activation or permalink changes, visit Settings > Permalinks to flush rewrite rules.

### Legacy Display Styles
For users without Divi or for free-tier usage:
- **Bar Style**: Linktree-style horizontal button with optional thumbnail
- **Card Style**: Image card with colored banner below

Both styles support customizable background and text colors per link.

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

### Divi 5 Integration
- Modules use Divi 5's Module API with `ModuleRegistration`
- PHP rendering uses `Module::render()` and `Style::add()`
- JSX preview uses `elements.style()` and `elements.styleComponents()`
- Link Type Designs leverage Divi's full styling capabilities

## Meta Keys Reference

### Tree Post Type (`_LH_*`)
| Key | Description |
|-----|-------------|
| `_LH_tree_links` | Array of link IDs with optional design overrides |
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
| `_LH_image` | Featured image attachment ID |
| `_LH_display_style` | Legacy style (bar/card) |
| `_LH_link_type_design` | Assigned Link Type Design ID |
| `_LH_background_color` | Link background color |
| `_LH_text_color` | Link text color |
| `_LH_click_count` | Total click count |
| `_LH_last_clicked` | Last click timestamp |

## Development

### Building Visual Builder Assets
```bash
cd visual-builder
npm install
npm run build
```

### Module JSON Structure
Each module has a `module.json` defining:
- `name`: Module identifier (e.g., `dtol/link-title`)
- `d5Support`: Set to `true` for Divi 5
- `attributes`: Styling options mapped to selectors

### Rendering Flow
1. User visits Tree page (`/link-tree/slug/`)
2. `TreeRenderer::render_tree_content()` intercepts content
3. For each link, `LinkTypeRenderer::render()` determines rendering method:
   - If Link Type Design assigned: Uses Divi's `et_builder_render_layout()`
   - Otherwise: Uses `render_legacy_bar()` or `render_legacy_card()`
4. Inline styles applied for page-level customizations

## Hooks & Filters

### Actions
- `LH_before_link_render` - Before rendering individual link
- `LH_after_link_render` - After rendering individual link

### Filters
- `LH_link_tracking_url` - Modify tracking URL format
- `LH_tree_links` - Filter links before rendering
- `LH_social_platforms` - Add/modify available social platforms

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

GPL v2 or later
