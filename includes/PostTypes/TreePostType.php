<?php
/**
 * Tree Custom Post Type
 *
 * @package LinkHub
 */

namespace LinkHub\PostTypes;

/**
 * Tree Post Type Class
 */
class TreePostType {
    
    /**
     * Post type slug
     */
    const POST_TYPE = 'lh_tree';
    
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
     * Meta key for link button background color
     */
    const META_LINK_BACKGROUND_COLOR = '_LH_link_background_color';

    /**
     * Meta key for link button text color
     */
    const META_LINK_TEXT_COLOR = '_LH_link_text_color';

    /**
     * Meta key for title font family
     */
    const META_TITLE_FONT = '_LH_title_font';

    /**
     * Meta key for body/content font family
     */
    const META_BODY_FONT = '_LH_body_font';

    /**
     * Meta key for hiding site header/footer (boolean)
     */
    const META_HIDE_HEADER_FOOTER = '_LH_hide_header_footer';

    /**
     * Meta key for heading/divider text size (small, medium, large)
     */
    const META_HEADING_SIZE = '_LH_heading_size';

    /**
     * Initialize the post type
     */
    public static function init() {
        \add_action('init', [self::class, 'register']);
        \add_filter('template_include', [self::class, 'maybe_use_blank_template'], 999);
        \add_filter('body_class', [self::class, 'add_body_class']);
        \add_filter('get_edit_post_link', [self::class, 'custom_edit_link'], 10, 2);
        \add_action('admin_bar_menu', [self::class, 'customize_admin_bar_edit_link'], 80);
        \add_action('pre_get_posts', [self::class, 'limit_archive_posts']);
    }

    /**
     * Limit archive to 1 post to simulate single page
     */
    public static function limit_archive_posts($query) {
        if (!is_admin() && $query->is_main_query() && is_post_type_archive(self::POST_TYPE)) {
            $query->set('posts_per_page', 1);
        }
    }

    /**
     * Customize edit link to point to Tree Builder instead of classic editor
     */
    public static function custom_edit_link($link, $post_id) {
        $post = \get_post($post_id);
        if ($post && $post->post_type === self::POST_TYPE) {
            return \admin_url('admin.php?page=lh-tree-builder');
        }
        return $link;
    }

    /**
     * Update admin bar edit link to go to Tree Builder
     */
    public static function customize_admin_bar_edit_link($wp_admin_bar) {
        if (!\is_singular(self::POST_TYPE)) {
            return;
        }

        $edit_node = $wp_admin_bar->get_node('edit');
        if ($edit_node) {
            $edit_node->href = \admin_url('admin.php?page=lh-tree-builder');
            $wp_admin_bar->add_node($edit_node);
        }
    }

    /**
     * Add body class to tree pages for CSS targeting
     */
    public static function add_body_class($classes) {
        if (is_singular(self::POST_TYPE) || is_post_type_archive(self::POST_TYPE)) {
            $classes[] = 'lh-tree-page';
            
            global $post;
            // On archive, get the first post if global isn't set
            if (is_post_type_archive(self::POST_TYPE) && empty($post)) {
                $posts = get_posts([
                    'post_type' => self::POST_TYPE,
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                ]);
                $post = !empty($posts) ? $posts[0] : null;
            }

            if ($post) {
                $hide_header_footer = get_post_meta($post->ID, self::META_HIDE_HEADER_FOOTER, true);
                
                if ($hide_header_footer === '1') {
                    $classes[] = 'lh-blank-template';
                }
            }
        }
        return $classes;
    }

    /**
     * Use blank template if option is enabled
     */
    public static function maybe_use_blank_template($template) {
        if (!is_singular(self::POST_TYPE) && !is_post_type_archive(self::POST_TYPE)) {
            return $template;
        }

        global $post;
        
        // On archive, get the first post if global isn't set
        if (is_post_type_archive(self::POST_TYPE) && empty($post)) {
            // We can't rely on the global query yet inside template_include sometimes, 
            // but usually it's safe. However, let's fetch manual to be sure.
            $posts = get_posts([
                'post_type' => self::POST_TYPE,
                'posts_per_page' => 1,
                'post_status' => 'publish',
            ]);
            $post_obj = !empty($posts) ? $posts[0] : null;
        } else {
            $post_obj = $post;
        }

        if (!$post_obj) {
            return $template;
        }

        $hide_header_footer = get_post_meta($post_obj->ID, self::META_HIDE_HEADER_FOOTER, true);
        
        if ($hide_header_footer === '1') {
            $blank_template = LH_PLUGIN_DIR . 'includes/templates/blank-tree-template.php';
            
            if (file_exists($blank_template)) {
                return $blank_template;
            }
        }
        
        return $template;
    }

    /**
     * Register the Tree custom post type
     */
    public static function register() {
        $labels = [
            'name'                  => _x('Link Trees', 'Post Type General Name', 'linkhub'),
            'singular_name'         => _x('Link Tree', 'Post Type Singular Name', 'linkhub'),
            'menu_name'             => __('LinkHub', 'linkhub'),
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
            'show_in_menu'          => false,
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
            'has_archive'           => true,
        ];
        
        \register_post_type(self::POST_TYPE, $args);
    }
}

