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
        add_filter('post_row_actions', [self::class, 'add_row_action'], 10, 2);
    }

    /**
     * Add submenu page under Link Trees
     */
    public static function add_submenu_page() {
        add_submenu_page(
            'edit.php?post_type=' . TreePostType::POST_TYPE,
            __('Tree Builder', 'linkhub'),
            __('Tree Builder', 'linkhub'),
            'edit_posts',
            'lh-tree-builder',
            [self::class, 'render_page']
        );
    }

    /**
     * Enqueue assets for the Tree Builder page
     */
    public static function enqueue_assets($hook) {
        // Only load on our page
        if ($hook !== 'lh_tree_page_lh-tree-builder') {
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
            'lh-tree-builder',
            LH_PLUGIN_URL . 'assets/js/tree-builder.js',
            ['jquery', 'wp-color-picker', 'wp-api-fetch'],
            LH_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('lh-tree-builder', 'lhTreeBuilder', [
            'apiBase'      => rest_url('linkhub/v1'),
            'nonce'        => wp_create_nonce('wp_rest'),
            'treeId'       => isset($_GET['tree_id']) ? absint($_GET['tree_id']) : 0,
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
     * Add Tree Builder link to post row actions
     */
    public static function add_row_action($actions, $post) {
        if ($post->post_type === TreePostType::POST_TYPE) {
            $builder_url = admin_url('edit.php?post_type=' . TreePostType::POST_TYPE . '&page=lh-tree-builder&tree_id=' . $post->ID);
            $actions['tree_builder'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($builder_url),
                __('Tree Builder', 'linkhub')
            );
        }
        return $actions;
    }

    /**
     * Render the Tree Builder page
     */
    public static function render_page() {
        // Get tree_id from URL if provided
        $tree_id = isset($_GET['tree_id']) ? absint($_GET['tree_id']) : 0;

        // Include the template
        include LH_PLUGIN_DIR . 'includes/templates/admin/tree-builder-page.php';
    }
}
