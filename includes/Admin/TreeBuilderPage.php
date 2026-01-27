<?php
/**
 * Tree Builder Admin Page
 *
 * @package LinkHub
 */

namespace LinkHub\Admin;

use LinkHub\PostTypes\TreePostType;

/**
 * Tree Builder Page Class
 */
class TreeBuilderPage {

    /**
     * Initialize the page
     */
    public static function init() {
        add_action('admin_menu', [self::class, 'add_submenu_page']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Add LinkHub as main menu item
     */
    public static function add_submenu_page() {
        // Add as top-level menu item
        add_menu_page(
            __('LinkHub', 'linkhub'),
            __('LinkHub', 'linkhub'),
            'edit_posts',
            'lh-tree-builder',
            [self::class, 'render_page'],
            'dashicons-networking',
            26
        );

        // Add submenu items
        add_submenu_page(
            'lh-tree-builder',
            __('Settings', 'linkhub'),
            __('Settings', 'linkhub'),
            'edit_posts',
            'lh-tree-builder'
        );
    }

    /**
     * Enqueue assets for the Tree Builder page
     */
    public static function enqueue_assets($hook) {
        // Only load on our page (toplevel_page_lh-tree-builder)
        if ($hook !== 'toplevel_page_lh-tree-builder') {
            return;
        }

        // Enqueue WordPress dependencies
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();

        // Enqueue our CSS
        wp_enqueue_style(
            'lh-tree-builder',
            LH_PLUGIN_URL . 'assets/css/tree-builder.css',
            [],
            LH_VERSION
        );

        // Enqueue our JS
        wp_enqueue_script(
            'sortable-js',
            LH_PLUGIN_URL . 'assets/js/libs/Sortable.min.js',
            [],
            '1.15.0',
            true
        );

        wp_enqueue_script(
            'lh-tree-builder',
            LH_PLUGIN_URL . 'assets/js/tree-builder.js',
            ['jquery', 'wp-color-picker', 'wp-api-fetch', 'sortable-js'],
            LH_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('lh-tree-builder', 'lhTreeBuilder', [
            'apiBase'      => rest_url('linkhub/v1'),
            'nonce'        => wp_create_nonce('wp_rest'),
            'adminUrl'     => admin_url(),
            'editPostBase' => admin_url('post.php'),
            'strings'      => [
                'confirmDelete'    => __('Are you sure you want to remove this item?', 'linkhub'),
                'confirmDeleteLink'=> __('Are you sure you want to permanently delete this link?', 'linkhub'),
                'enterTitle'       => __('Enter link title', 'linkhub'),
                'enterUrl'         => __('Enter URL', 'linkhub'),
                'enterHeading'     => __('Enter heading text', 'linkhub'),
                'saving'           => __('Saving...', 'linkhub'),
                'saved'            => __('Saved', 'linkhub'),
                'error'            => __('Error saving', 'linkhub'),
                'addLink'          => __('Add Link', 'linkhub'),
                'addHeading'       => __('Add Heading', 'linkhub'),
                'createLink'       => __('Create Link', 'linkhub'),
                'selectImage'      => __('Select Image', 'linkhub'),
                'useImage'         => __('Use this image', 'linkhub'),
                'noTrees'          => __('No trees yet. Create one to get started.', 'linkhub'),
                'loading'          => __('Loading...', 'linkhub'),
            ],
            'socialPlatforms' => [
                'twitter'   => __('Twitter / X', 'linkhub'),
                'facebook'  => __('Facebook', 'linkhub'),
                'instagram' => __('Instagram', 'linkhub'),
                'linkedin'  => __('LinkedIn', 'linkhub'),
                'youtube'   => __('YouTube', 'linkhub'),
                'tiktok'    => __('TikTok', 'linkhub'),
                'pinterest' => __('Pinterest', 'linkhub'),
                'github'    => __('GitHub', 'linkhub'),
                'email'     => __('Email', 'linkhub'),
                'website'   => __('Website', 'linkhub'),
            ],
            'fonts' => [
                'system'      => __('System Default', 'linkhub'),
                'serif'       => __('Serif', 'linkhub'),
                'sans'        => __('Sans-Serif', 'linkhub'),
                'mono'        => __('Monospace', 'linkhub'),
                'poppins'     => 'Poppins',
                'montserrat'  => 'Montserrat',
                'playfair'    => 'Playfair Display',
                'raleway'     => 'Raleway',
                'open-sans'   => 'Open Sans',
                'roboto'      => 'Roboto',
                'lato'        => 'Lato',
                'merriweather'=> 'Merriweather',
            ],
        ]);
    }

    /**
     * Render the Tree Builder page
     */
    public static function render_page() {
        // Get the tree (auto-create if none exists)
        $tree_id = self::get_or_create_tree();

        // Include the template
        include LH_PLUGIN_DIR . 'includes/templates/admin/tree-builder-page.php';
    }

    /**
     * Get existing tree or create one
     *
     * @return int Tree ID
     */
    private static function get_or_create_tree() {
        // Check for existing tree
        $trees = get_posts([
            'post_type'      => TreePostType::POST_TYPE,
            'posts_per_page' => 1,
            'post_status'    => ['publish', 'draft'],
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);

        if (!empty($trees)) {
            return $trees[0];
        }

        // Create default tree
        $tree_id = wp_insert_post([
            'post_type'   => TreePostType::POST_TYPE,
            'post_title'  => __('My Link Tree', 'linkhub'),
            'post_status' => 'draft',
        ]);

        if (is_wp_error($tree_id)) {
            return 0;
        }

        // Set default settings
        update_post_meta($tree_id, TreePostType::META_SETTINGS, [
            'background_color'      => '#8b8178',
            'tree_background_color' => '#f5f5f5',
            'title_color'           => '#1a1a1a',
            'bio_color'             => '#555555',
            'link_background_color' => '#eeeeee',
            'link_text_color'       => '#000000',
            'social_color'          => '#333333',
            'hero_shape'            => 'round',
            'social_style'          => 'circle',
            'title_font'            => 'system',
            'body_font'             => 'system',
        ]);

        return $tree_id;
    }
}
