<?php
/**
 * Link Custom Post Type
 *
 * @package ElyseVIP\LinkHub
 */

namespace ElyseVIP\LinkHub\PostTypes;

/**
 * Link Post Type Class
 */
class LinkPostType {
    
    /**
     * Post type slug
     */
    const POST_TYPE = 'LH_link';
    
    /**
     * Meta key for URL
     */
    const META_URL = '_LH_url';
    
    /**
     * Meta key for icon
     */
    const META_ICON = '_LH_icon';
    
    /**
     * Meta key for image ID
     */
    const META_IMAGE = '_LH_image_id';
    
    /**
     * Meta key for click count
     */
    const META_CLICK_COUNT = '_LH_click_count';
    
    /**
     * Meta key for last clicked timestamp
     */
    const META_LAST_CLICKED = '_LH_last_clicked';
    
    /**
     * Meta key for display style (card or bar)
     */
    const META_DISPLAY_STYLE = '_LH_display_style';

    /**
     * Meta key for link description
     */
    const META_DESCRIPTION = '_LH_description';

    /**
     * Meta key for background color (legacy styles)
     */
    const META_BACKGROUND_COLOR = '_LH_background_color';

    /**
     * Meta key for text color (legacy styles)
     */
    const META_TEXT_COLOR = '_LH_text_color';

    /**
     * Meta key for heading size (small/medium/large)
     */
    const META_HEADING_SIZE = '_LH_heading_size';

    /**
     * Register the Link custom post type
     */
    public static function register() {
        $labels = [
            'name'                  => _x('Links', 'Post Type General Name', 'linkhub'),
            'singular_name'         => _x('Link', 'Post Type Singular Name', 'linkhub'),
            'menu_name'             => __('Links', 'linkhub'),
            'name_admin_bar'        => __('Link', 'linkhub'),
            'archives'              => __('Link Archives', 'linkhub'),
            'attributes'            => __('Link Attributes', 'linkhub'),
            'parent_item_colon'     => __('Parent Link:', 'linkhub'),
            'all_items'             => __('All Links', 'linkhub'),
            'add_new_item'          => __('Add New Link', 'linkhub'),
            'add_new'               => __('Add New', 'linkhub'),
            'new_item'              => __('New Link', 'linkhub'),
            'edit_item'             => __('Edit Link', 'linkhub'),
            'update_item'           => __('Update Link', 'linkhub'),
            'view_item'             => __('View Link', 'linkhub'),
            'view_items'            => __('View Links', 'linkhub'),
            'search_items'          => __('Search Link', 'linkhub'),
            'not_found'             => __('Not found', 'linkhub'),
            'not_found_in_trash'    => __('Not found in Trash', 'linkhub'),
        ];
        
        $args = [
            'label'                 => __('Link', 'linkhub'),
            'description'           => __('Individual links for Link Trees', 'linkhub'),
            'labels'                => $labels,
            'supports'              => ['title', 'custom-fields'],
            'taxonomies'            => ['link_category'],
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=' . TreePostType::POST_TYPE,
            'menu_position'         => 21,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'dtol-links',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];
        
        register_post_type(self::POST_TYPE, $args);
        
        // Register taxonomy
        self::register_taxonomy();
        
        // Register meta fields
        self::register_meta_fields();
    }
    
    /**
     * Register link category taxonomy
     */
    private static function register_taxonomy() {
        $labels = [
            'name'                       => _x('Link Categories', 'Taxonomy General Name', 'linkhub'),
            'singular_name'              => _x('Link Category', 'Taxonomy Singular Name', 'linkhub'),
            'menu_name'                  => __('Categories', 'linkhub'),
            'all_items'                  => __('All Categories', 'linkhub'),
            'parent_item'                => __('Parent Category', 'linkhub'),
            'parent_item_colon'          => __('Parent Category:', 'linkhub'),
            'new_item_name'              => __('New Category Name', 'linkhub'),
            'add_new_item'               => __('Add New Category', 'linkhub'),
            'edit_item'                  => __('Edit Category', 'linkhub'),
            'update_item'                => __('Update Category', 'linkhub'),
            'view_item'                  => __('View Category', 'linkhub'),
            'separate_items_with_commas' => __('Separate categories with commas', 'linkhub'),
            'add_or_remove_items'        => __('Add or remove categories', 'linkhub'),
            'choose_from_most_used'      => __('Choose from the most used', 'linkhub'),
            'popular_items'              => __('Popular Categories', 'linkhub'),
            'search_items'               => __('Search Categories', 'linkhub'),
            'not_found'                  => __('Not Found', 'linkhub'),
        ];
        
        $args = [
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => false,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => false,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
        ];
        
        register_taxonomy('link_category', [self::POST_TYPE], $args);
    }
    
    /**
     * Register meta fields for REST API and queries
     */
    private static function register_meta_fields() {
        // URL field
        register_post_meta(self::POST_TYPE, self::META_URL, [
            'type'              => 'string',
            'description'       => __('Destination URL', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'esc_url_raw',
        ]);
        
        // Icon field
        register_post_meta(self::POST_TYPE, self::META_ICON, [
            'type'              => 'string',
            'description'       => __('Icon class or name', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        // Image ID field
        register_post_meta(self::POST_TYPE, self::META_IMAGE, [
            'type'              => 'integer',
            'description'       => __('Image attachment ID', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
        ]);
        
        // Click count field
        register_post_meta(self::POST_TYPE, self::META_CLICK_COUNT, [
            'type'              => 'integer',
            'description'       => __('Number of clicks', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ]);
        
        // Last clicked field
        register_post_meta(self::POST_TYPE, self::META_LAST_CLICKED, [
            'type'              => 'string',
            'description'       => __('Last clicked timestamp', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        // Display style field
        register_post_meta(self::POST_TYPE, self::META_DISPLAY_STYLE, [
            'type'              => 'string',
            'description'       => __('Display style (bar or card)', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'bar',
        ]);

        // Description field
        register_post_meta(self::POST_TYPE, self::META_DESCRIPTION, [
            'type'              => 'string',
            'description'       => __('Link description or subtitle', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'wp_kses_post',
        ]);

        // Background color field (for legacy styles)
        register_post_meta(self::POST_TYPE, self::META_BACKGROUND_COLOR, [
            'type'              => 'string',
            'description'       => __('Background color for legacy display styles', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#f8a4c8', // Pink like Linktree
        ]);

        // Text color field (for legacy styles)
        register_post_meta(self::POST_TYPE, self::META_TEXT_COLOR, [
            'type'              => 'string',
            'description'       => __('Text color for legacy display styles', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#000000',
        ]);

        // Heading size field (for heading display style)
        register_post_meta(self::POST_TYPE, self::META_HEADING_SIZE, [
            'type'              => 'string',
            'description'       => __('Heading size (small, medium, large)', 'linkhub'),
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'medium',
        ]);
    }
    
    /**
     * Get link URL
     *
     * @param int $post_id Post ID
     * @return string
     */
    public static function get_url($post_id) {
        return get_post_meta($post_id, self::META_URL, true);
    }
    
    /**
     * Get click count
     *
     * @param int $post_id Post ID
     * @return int
     */
    public static function get_click_count($post_id) {
        return (int) get_post_meta($post_id, self::META_CLICK_COUNT, true);
    }
}
