=== LinkHub ===
Contributors: elysevipatd
Tags: linktree, links, social-media, click-tracking, link-in-bio
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create beautiful link-in-bio pages with CPT-based link management and built-in click tracking.

== Description ==

**LinkHub** is a professional link management solution that brings LinkTree and Clickwhale functionality to your WordPress site.

= Key Features =

* **Custom Post Types** - Separate Trees and Links for maximum flexibility and reusability
* **Click Tracking** - Built-in redirection engine with analytics (click counts)
* **Three Display Styles** - Bar (button), Card (image with title), and Heading (text divider)
* **Tree Customization** - Profile header image, about text, and social links bar
* **Performance Optimized** - Clean, efficient code with minimal overhead
* **Drag & Drop UI** - Intuitive interface for managing link order
* **Icon Support** - Font Awesome icons or emojis
* **Image Support** - Custom images for each link
* **Link Reusability** - Use the same links across multiple trees

= Perfect For =

* Social media influencers managing multiple platform links
* Content creators sharing resources
* Businesses organizing product/service links
* E-commerce stores with multiple storefronts
* Podcasters sharing episode resources
* Anyone needing a clean, professional link landing page

= How It Works =

1. Create individual links with titles, URLs, icons, and images
2. Build Link Trees by selecting and ordering your links
3. Add the tree to any page using the shortcode `[linkhub_tree id="123"]`
4. Customize appearance with built-in display styles (bar, card, heading)
5. Track performance with built-in click analytics

= Developer Friendly =

* PSR-4 autoloading with Composer support
* Modern PHP namespaces
* Extensible via WordPress hooks and filters
* Well-documented codebase
* Follows Divi 5 SSR/CSR patterns

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/linkhub/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Link Trees → Links to create your first link
4. Create a Link Tree and add your links
5. Use the Divi Builder to add the Tree of Links module to any page

For detailed installation and testing instructions, see `INSTALL.md` included with the plugin.

== Frequently Asked Questions ==

= Does this require Divi 5? =

Yes, this plugin requires the Divi theme (version 5 or higher) or the Extra theme with Divi Builder.

= Can I use the same links in multiple trees? =

Absolutely! Links are stored as a Custom Post Type, so you can reuse them across as many Link Trees as you need.

= How accurate is the click tracking? =

Click tracking uses server-side redirects, so it's very accurate. Every click is counted before the user is redirected to the destination URL.

= Can I customize the appearance? =

Yes! The module exposes Divi's native design controls (spacing, colors, borders, shadows, etc.). You can also add custom CSS.

= Does this work with page caching? =

Yes. The plugin uses WordPress object cache and is compatible with page caching plugins. Click tracking works regardless of cache status.

= Can I export/import my links? =

Yes, since links are Custom Post Types, you can use standard WordPress import/export tools.

== Screenshots ==

1. Link Tree admin interface with drag-and-drop ordering
2. List mode display on frontend
3. Card mode display with images
4. Individual link edit screen with click statistics
5. Divi module settings in Visual Builder

== Changelog ==

= 0.1.0 =
* Initial development release (alpha)
* Custom Post Types for Trees and Links
* Click tracking with redirect engine
* Divi 5 native module with SSR/CSR support
* List and Card display modes
* Drag-and-drop link ordering
* WordPress object cache integration
* Icon and image support
* Click count analytics

== Upgrade Notice ==

= 0.1.0 =
Initial development release. Not recommended for production use.

== Developer Notes ==

This plugin follows WordPress and Divi coding standards:

* PSR-4 autoloading
* Modern PHP namespaces (PHP 7.4+)
* Divi 5 JSON-based module system
* Server-side and client-side rendering parity
* Extensive inline documentation
* Action and filter hooks for extensibility

For code examples and API documentation, see `README.md`.
