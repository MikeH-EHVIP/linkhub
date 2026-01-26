# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Import/Export Functionality**: Export tree data as JSON and import from JSON files
- **Clickwhale CSV Migration**: Import links from Clickwhale CSV export files via REST API
- **Import/Export View**: New dedicated settings view for import/export operations
- **Danger Zone Reset**: Admin-level option to delete all tree data with safety confirmations
- **Image Export/Import**: Link images are now included in exports (as URLs) and automatically downloaded on import
- **Smart Image Handling**: Import checks for existing images in media library before downloading (by URL and filename)
- **Duplicate Link Prevention**: Import checks for existing links by URL to avoid creating duplicates
- **Frontend Edit Button**: Inline pencil icon next to tree title for authorized users to access Tree Builder

### Changed
- **Tree Builder Simplified**: Switched from multi-tree to single-tree mode with auto-creation
- **Settings Reorganized**: Default view changed from "Links" to "Profile" on Tree Builder load
- **Menu Structure**: "Link Trees" renamed to "LinkHub" with "Settings" submenu, removed redundant menu items
- **Appearance Panel Enhanced**: Background image and display options integrated into Appearance view
- **Form Descriptions Added**: Improved UX with helper text for import/export functions
- **Edit Links Redirected**: Admin bar and frontend edit links now point to Tree Builder UI instead of classic editor
- **Build System**: ZIP creation now uses forward slashes for cross-platform (Linux server) compatibility

### Removed
- **Multi-Tree Support**: Tree list sidebar removed in favor of single auto-managed tree
- **Display View**: Merged into Appearance view for simpler settings navigation
- **"Create Tree" Modal**: No longer needed with auto-creation functionality
- **Separate Menu Items**: "Tree Settings" and "Import/Export" menus consolidated into main LinkHub menu

## [0.3.0] - 2026-01-25

### Added
- **New Tree Builder Admin UI**: Complete redesign with modern three-panel layout
  - Left sidebar with tree list and settings navigation
  - Main editor panel with draggable link cards
  - Live preview panel with iPhone-style frame
- **Sidebar Navigation**: Settings organized into separate views (Links, Profile, Social Links, Appearance, Display)
- **REST API**: Full CRUD endpoints for trees and links (`/linkhub/v1/trees`, `/linkhub/v1/links`)
- **Inline Edit Modals**: Edit links and headings via modal popups without leaving the page
- **Auto-save**: Changes automatically saved with visual status indicator
- **Media Library Integration**: Upload images directly from Tree Builder
- **Create Links Inline**: Add new links without leaving Tree Builder
- **Live Preview**: Real-time iframe preview updates on every change

### Changed
- Settings panel restructured from accordion to sidebar navigation
- Improved drag-and-drop with better visual feedback
- Tree Builder accessible via "Link Trees → Tree Builder" submenu

### Removed
- Divi-specific rendering code from TreeRenderer.php

## [0.2.0] - 2026-01-23

### Added
- Inline heading support in Tree builder (no separate Link CPT needed)
- Image thumbnails for links in Tree builder interface
- "Insert here" functionality to add links/headings at specific positions
- Three heading sizes: small, medium, large
- Improved admin UI with visual distinction for headings
- Hover-activated insert buttons for better UX

### Changed
- Tree items now support mixed array of links and headings
- Updated save/render logic to handle inline headings
- Enhanced drag-and-drop interface with better visual feedback

### Removed
- All Divi 5 dependencies and modules
- Link Type Design post type (free version only)
- Visual Builder integration

## [0.1.0] - 2026-01-19

### Added
* Initial development release (alpha)
* Not production-ready - requires testing
- Custom Post Type for Link Trees (`LH_tree`)
- Custom Post Type for Links (`LH_link`)
- Link category taxonomy for organizing links
- Redirect handler with `/go/{id}/` tracking URLs
- Click tracking with count and timestamp
- WordPress object cache integration (Redis/Memcached support)
- Admin meta box for tree link management with drag-and-drop
- Admin meta box for link details (URL, icon, image)
- Admin meta box for click statistics display
- Divi 5 module registration via JSON schema
- Server-side rendering (SSR) implementation
- Client-side rendering (CSR) React components
- List display mode (vertical button list)
- Card display mode (visual grid with images)
- Responsive CSS styling
- Icon support (Font Awesome classes and emojis)
- Image support via WordPress Media Library
- PSR-4 autoloader with Composer
- Fallback autoloader for non-Composer environments
- Comprehensive documentation (README, INSTALL, CHANGELOG)
- TypeScript definitions for Divi module
- Webpack build configuration
- ESLint configuration for code quality

### Security
- Nonce verification for all meta saves
- Capability checks for admin actions
- URL sanitization for link destinations
- Input sanitization throughout

### Performance
- 12-hour cache for redirect URL lookups
- 1-hour cache for rendered output
- Optimized database queries with post meta registration
- Cache invalidation on link URL changes

[0.1.0]: https://github.com/elysevipatd/linkhub/releases/tag/v0.1.0
