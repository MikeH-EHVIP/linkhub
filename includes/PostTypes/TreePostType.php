<?php
/**
 * Tree Custom Post Type
 *
 * @package ElyseVIP\LinkHub
 */

namespace ElyseVIP\LinkHub\PostTypes;

/**
 * Tree Post Type Class
 */
class TreePostType {
    
    /**
     * Post type slug
     */
    const POST_TYPE = 'LH_tree';
    
    /**
     * Meta key for tree links with design overrides
     * Format: [['link_id' => 123, 'design_id' => 456], ...]
     */
    const META_TREE_LINKS = '_LH_tree_links';

    /**
     * Meta key for header image (attachment ID)
     */
    const META_HEADER_IMAGE = '_LH_header_image';

    /**
     * Meta key for about text (profile bio/description)
     */
    const META_ABOUT_TEXT = '_LH_about_text';

    /**
     * Meta key for social links
     * Format: [['platform' => 'twitter', 'url' => 'https://...'], ...]
     */
    const META_SOCIAL_LINKS = '_LH_social_links';

    /**
     * Meta key for page background color (outer area on desktop)
     */
    const META_BACKGROUND_COLOR = '_LH_background_color';

    /**
     * Meta key for tree/content area background color
     */
    const META_TREE_BACKGROUND_COLOR = '_LH_tree_background_color';

    /**
     * Meta key for page background image (attachment ID)
     */
    const META_BACKGROUND_IMAGE = '_LH_background_image';

    /**
     * Meta key for hero image shape (round, rounded, square)
     */
    const META_HERO_SHAPE = '_LH_hero_shape';

    /**
     * Meta key for hero image fade effect (boolean)
     */
    const META_HERO_FADE = '_LH_hero_fade';

    /**
     * Meta key for title text color
     */
    const META_TITLE_COLOR = '_LH_title_color';

    /**
     * Meta key for bio/about text color
     */
    const META_BIO_COLOR = '_LH_bio_color';

    /**
     * Meta key for social icon style (circle, rounded, square, minimal)
     */
    const META_SOCIAL_STYLE = '_LH_social_style';

    /**
     * Meta key for social icon color
     */
    const META_SOCIAL_COLOR = '_LH_social_color';

    /**
     * Meta key for heading/divider text size (small, medium, large)
     */
    const META_HEADING_SIZE = '_LH_heading_size';

    /**
     * Register the Tree custom post type
     */
    public static function register() {
        $labels = [
            'name'                  => _x('Link Trees', 'Post Type General Name', 'linkhub'),
            'singular_name'         => _x('Link Tree', 'Post Type Singular Name', 'linkhub'),
            'menu_name'             => __('Link Trees', 'linkhub'),
            'name_admin_bar'        => __('Link Tree', 'linkhub'),
            'archives'              => __('Link Tree Archives', 'linkhub'),
            'attributes'            => __('Link Tree Attributes', 'linkhub'),
            'parent_item_colon'     => __('Parent Link Tree:', 'linkhub'),
            'all_items'             => __('All Link Trees', 'linkhub'),
            'add_new_item'          => __('Add New Link Tree', 'linkhub'),
            'add_new'               => __('Add New', 'linkhub'),
            'new_item'              => __('New Link Tree', 'linkhub'),
            'edit_item'             => __('Edit Link Tree', 'linkhub'),
            'update_item'           => __('Update Link Tree', 'linkhub'),
            'view_item'             => __('View Link Tree', 'linkhub'),
            'view_items'            => __('View Link Trees', 'linkhub'),
            'search_items'          => __('Search Link Tree', 'linkhub'),
            'not_found'             => __('Not found', 'linkhub'),
            'not_found_in_trash'    => __('Not found in Trash', 'linkhub'),
            'featured_image'        => __('Featured Image', 'linkhub'),
            'set_featured_image'    => __('Set featured image', 'linkhub'),
            'remove_featured_image' => __('Remove featured image', 'linkhub'),
            'use_featured_image'    => __('Use as featured image', 'linkhub'),
            'insert_into_item'      => __('Insert into link tree', 'linkhub'),
            'uploaded_to_this_item' => __('Uploaded to this link tree', 'linkhub'),
            'items_list'            => __('Link Trees list', 'linkhub'),
            'items_list_navigation' => __('Link Trees list navigation', 'linkhub'),
            'filter_items_list'     => __('Filter link trees list', 'linkhub'),
        ];
        
        $args = [
            'label'                 => __('Link Tree', 'linkhub'),
            'description'           => __('Collection of links for display', 'linkhub'),
            'labels'                => $labels,
            'supports'              => ['title', 'slug'],
            'taxonomies'            => [],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-networking',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => 'link-trees',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rewrite'               => [
                'slug'       => 'links',
                'with_front' => false,
            ],
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
}
