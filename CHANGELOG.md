# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Tree header image field for profile picture/logo
- Tree about text field for bio/description
- Tree social links bar with platform icons (Twitter/X, Facebook, Instagram, LinkedIn, YouTube, TikTok, Pinterest, GitHub, Email, Website)
- Link Type Design post type for custom link templates
- Design-only Divi 5 modules: Link Title, Link Image, Link Icon
- LinkTypeRenderer for rendering links with custom designs
- TreeRenderer for frontend tree display
- Frontend CSS styles for tree pages

### Changed
- Trees now display via their permalink URL using TreeRenderer
- Links can be assigned a Link Type Design for custom appearance
- Trees can override Link Type Design per-link

### Removed
- Tree Display Divi module (use Theme Builder templates instead)

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
