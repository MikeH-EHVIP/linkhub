<?php
/**
 * REST API Controller for Tree Builder
 *
 * @package LinkHub
 */

namespace LinkHub\Admin;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use LinkHub\PostTypes\TreePostType;
use LinkHub\PostTypes\LinkPostType;

/**
 * REST Controller for Tree Builder Admin UI
 */
class RestController extends WP_REST_Controller {

    /**
     * API namespace
     */
    protected $namespace = 'linkhub/v1';

    /**
     * Initialize the controller
     */
    public static function init() {
        add_action('rest_api_init', [new self(), 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // GET /trees - List all trees
        register_rest_route($this->namespace, '/trees', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_trees'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // POST /trees - Create new tree
        register_rest_route($this->namespace, '/trees', [
            'methods'             => 'POST',
            'callback'            => [$this, 'create_tree'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // GET /trees/{id} - Get single tree with links and settings
        register_rest_route($this->namespace, '/trees/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_tree'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // PUT /trees/{id} - Update tree settings
        register_rest_route($this->namespace, '/trees/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'update_tree'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // PUT /trees/{id}/links - Update tree links array (reorder)
        register_rest_route($this->namespace, '/trees/(?P<id>\d+)/links', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'update_tree_links'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // GET /links - List all available links
        register_rest_route($this->namespace, '/links', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_links'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // POST /links - Create new link
        register_rest_route($this->namespace, '/links', [
            'methods'             => 'POST',
            'callback'            => [$this, 'create_link'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // PUT /links/{id} - Update link
        register_rest_route($this->namespace, '/links/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'update_link'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // DELETE /links/{id} - Delete link
        register_rest_route($this->namespace, '/links/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'delete_link'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
    }

    /**
     * Permission check for all endpoints
     */
    public function permissions_check() {
        return current_user_can('edit_posts');
    }

    /**
     * GET /trees - List all trees
     */
    public function get_trees(WP_REST_Request $request) {
        $trees = get_posts([
            'post_type'      => TreePostType::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => ['publish', 'draft', 'pending'],
        ]);

        $data = [];
        foreach ($trees as $tree) {
            $data[] = [
                'id'        => $tree->ID,
                'title'     => $tree->post_title,
                'status'    => $tree->post_status,
                'permalink' => get_permalink($tree->ID),
                'edit_url'  => get_edit_post_link($tree->ID, 'raw'),
            ];
        }

        return new WP_REST_Response($data);
    }

    /**
     * POST /trees - Create new tree
     */
    public function create_tree(WP_REST_Request $request) {
        $title = $request->get_param('title');

        if (empty($title)) {
            return new WP_Error('missing_title', __('Tree title is required', 'linkhub'), ['status' => 400]);
        }

        $tree_id = wp_insert_post([
            'post_type'   => TreePostType::POST_TYPE,
            'post_title'  => sanitize_text_field($title),
            'post_status' => 'draft',
        ]);

        if (is_wp_error($tree_id)) {
            return $tree_id;
        }

        // Initialize empty links array
        update_post_meta($tree_id, TreePostType::META_TREE_LINKS, []);

        return new WP_REST_Response([
            'id'        => $tree_id,
            'title'     => $title,
            'status'    => 'draft',
            'permalink' => get_permalink($tree_id),
            'edit_url'  => get_edit_post_link($tree_id, 'raw'),
        ], 201);
    }

    /**
     * GET /trees/{id} - Get single tree with all data
     */
    public function get_tree(WP_REST_Request $request) {
        $tree_id = $request->get_param('id');
        $post = get_post($tree_id);

        if (!$post || $post->post_type !== TreePostType::POST_TYPE) {
            return new WP_Error('not_found', __('Tree not found', 'linkhub'), ['status' => 404]);
        }

        return $this->prepare_tree_response($post);
    }

    /**
     * PUT /trees/{id} - Update tree settings
     */
    public function update_tree(WP_REST_Request $request) {
        $tree_id = $request->get_param('id');
        $post = get_post($tree_id);

        if (!$post || $post->post_type !== TreePostType::POST_TYPE) {
            return new WP_Error('not_found', __('Tree not found', 'linkhub'), ['status' => 404]);
        }

        if (!current_user_can('edit_post', $tree_id)) {
            return new WP_Error('forbidden', __('Cannot edit this tree', 'linkhub'), ['status' => 403]);
        }

        $settings = $request->get_param('settings');

        if (is_array($settings)) {
            // Update title if provided
            if (isset($settings['title'])) {
                wp_update_post([
                    'ID'         => $tree_id,
                    'post_title' => sanitize_text_field($settings['title']),
                ]);
            }

            // Update meta fields
            $meta_mappings = [
                'header_image_id'      => TreePostType::META_HEADER_IMAGE,
                'about_text'           => TreePostType::META_ABOUT_TEXT,
                'social_links'         => TreePostType::META_SOCIAL_LINKS,
                'background_color'     => TreePostType::META_BACKGROUND_COLOR,
                'tree_background_color'=> TreePostType::META_TREE_BACKGROUND_COLOR,
                'background_image_id'  => TreePostType::META_BACKGROUND_IMAGE,
                'hero_shape'           => TreePostType::META_HERO_SHAPE,
                'hero_fade'            => TreePostType::META_HERO_FADE,
                'title_color'          => TreePostType::META_TITLE_COLOR,
                'bio_color'            => TreePostType::META_BIO_COLOR,
                'social_style'         => TreePostType::META_SOCIAL_STYLE,
                'social_color'         => TreePostType::META_SOCIAL_COLOR,
                'link_background_color'=> TreePostType::META_LINK_BACKGROUND_COLOR,
                'link_text_color'      => TreePostType::META_LINK_TEXT_COLOR,
                'title_font'           => TreePostType::META_TITLE_FONT,
                'body_font'            => TreePostType::META_BODY_FONT,
                'heading_size'         => TreePostType::META_HEADING_SIZE,
                'hide_header_footer'   => TreePostType::META_HIDE_HEADER_FOOTER,
            ];

            foreach ($meta_mappings as $key => $meta_key) {
                if (isset($settings[$key])) {
                    $value = $this->sanitize_setting($key, $settings[$key]);
                    update_post_meta($tree_id, $meta_key, $value);
                }
            }
        }

        return $this->prepare_tree_response(get_post($tree_id));
    }

    /**
     * PUT /trees/{id}/links - Update tree links array
     */
    public function update_tree_links(WP_REST_Request $request) {
        $tree_id = $request->get_param('id');
        $post = get_post($tree_id);

        if (!$post || $post->post_type !== TreePostType::POST_TYPE) {
            return new WP_Error('not_found', __('Tree not found', 'linkhub'), ['status' => 404]);
        }

        if (!current_user_can('edit_post', $tree_id)) {
            return new WP_Error('forbidden', __('Cannot edit this tree', 'linkhub'), ['status' => 403]);
        }

        $items = $request->get_param('items');

        if (!is_array($items)) {
            return new WP_Error('invalid_items', __('Items must be an array', 'linkhub'), ['status' => 400]);
        }

        // Sanitize items
        $sanitized = [];
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['type'])) {
                continue;
            }

            if ($item['type'] === 'link' && isset($item['link_id'])) {
                $sanitized[] = [
                    'type'    => 'link',
                    'link_id' => absint($item['link_id']),
                ];
            } elseif ($item['type'] === 'heading') {
                $size = isset($item['size']) && in_array($item['size'], ['small', 'medium', 'large'])
                    ? $item['size']
                    : 'medium';
                $sanitized[] = [
                    'type' => 'heading',
                    'text' => isset($item['text']) ? sanitize_text_field($item['text']) : '',
                    'size' => $size,
                ];
            }
        }

        update_post_meta($tree_id, TreePostType::META_TREE_LINKS, $sanitized);

        return new WP_REST_Response(['success' => true, 'items' => $sanitized]);
    }

    /**
     * GET /links - List all available links
     */
    public function get_links(WP_REST_Request $request) {
        $links = get_posts([
            'post_type'      => LinkPostType::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => ['publish', 'draft'],
        ]);

        $data = [];
        foreach ($links as $link) {
            $data[] = $this->prepare_link_item($link);
        }

        return new WP_REST_Response($data);
    }

    /**
     * POST /links - Create new link
     */
    public function create_link(WP_REST_Request $request) {
        $title = $request->get_param('title');
        $url = $request->get_param('url');

        if (empty($title)) {
            return new WP_Error('missing_title', __('Link title is required', 'linkhub'), ['status' => 400]);
        }

        if (empty($url)) {
            return new WP_Error('missing_url', __('Link URL is required', 'linkhub'), ['status' => 400]);
        }

        $link_id = wp_insert_post([
            'post_type'   => LinkPostType::POST_TYPE,
            'post_title'  => sanitize_text_field($title),
            'post_status' => 'publish',
        ]);

        if (is_wp_error($link_id)) {
            return $link_id;
        }

        // Set meta fields
        update_post_meta($link_id, LinkPostType::META_URL, esc_url_raw($url));

        if ($request->has_param('description')) {
            update_post_meta($link_id, LinkPostType::META_DESCRIPTION, wp_kses_post($request->get_param('description')));
        }

        if ($request->has_param('display_style')) {
            $style = $request->get_param('display_style');
            if (in_array($style, ['bar', 'card', 'heading'])) {
                update_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, $style);
            }
        }

        if ($request->has_param('image_id')) {
            update_post_meta($link_id, LinkPostType::META_IMAGE, absint($request->get_param('image_id')));
        }

        if ($request->has_param('icon')) {
            update_post_meta($link_id, LinkPostType::META_ICON, sanitize_text_field($request->get_param('icon')));
        }

        // Initialize click count
        update_post_meta($link_id, LinkPostType::META_CLICK_COUNT, 0);

        return new WP_REST_Response($this->prepare_link_item(get_post($link_id)), 201);
    }

    /**
     * PUT /links/{id} - Update link
     */
    public function update_link(WP_REST_Request $request) {
        $link_id = $request->get_param('id');
        $post = get_post($link_id);

        if (!$post || $post->post_type !== LinkPostType::POST_TYPE) {
            return new WP_Error('not_found', __('Link not found', 'linkhub'), ['status' => 404]);
        }

        if (!current_user_can('edit_post', $link_id)) {
            return new WP_Error('forbidden', __('Cannot edit this link', 'linkhub'), ['status' => 403]);
        }

        // Update title if provided
        if ($request->has_param('title')) {
            wp_update_post([
                'ID'         => $link_id,
                'post_title' => sanitize_text_field($request->get_param('title')),
            ]);
        }

        // Update meta fields
        if ($request->has_param('url')) {
            update_post_meta($link_id, LinkPostType::META_URL, esc_url_raw($request->get_param('url')));
        }

        if ($request->has_param('description')) {
            update_post_meta($link_id, LinkPostType::META_DESCRIPTION, wp_kses_post($request->get_param('description')));
        }

        if ($request->has_param('display_style')) {
            $style = $request->get_param('display_style');
            if (in_array($style, ['bar', 'card', 'heading'])) {
                update_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, $style);
            }
        }

        if ($request->has_param('image_id')) {
            $image_id = $request->get_param('image_id');
            if ($image_id) {
                update_post_meta($link_id, LinkPostType::META_IMAGE, absint($image_id));
            } else {
                delete_post_meta($link_id, LinkPostType::META_IMAGE);
            }
        }

        if ($request->has_param('icon')) {
            update_post_meta($link_id, LinkPostType::META_ICON, sanitize_text_field($request->get_param('icon')));
        }

        return new WP_REST_Response($this->prepare_link_item(get_post($link_id)));
    }

    /**
     * DELETE /links/{id} - Delete link
     */
    public function delete_link(WP_REST_Request $request) {
        $link_id = $request->get_param('id');
        $post = get_post($link_id);

        if (!$post || $post->post_type !== LinkPostType::POST_TYPE) {
            return new WP_Error('not_found', __('Link not found', 'linkhub'), ['status' => 404]);
        }

        if (!current_user_can('delete_post', $link_id)) {
            return new WP_Error('forbidden', __('Cannot delete this link', 'linkhub'), ['status' => 403]);
        }

        $result = wp_delete_post($link_id, true);

        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete link', 'linkhub'), ['status' => 500]);
        }

        return new WP_REST_Response(['success' => true, 'deleted' => $link_id]);
    }

    /**
     * Prepare full tree response with all data
     */
    private function prepare_tree_response($post) {
        $tree_links = get_post_meta($post->ID, TreePostType::META_TREE_LINKS, true) ?: [];

        $items = [];
        foreach ($tree_links as $item) {
            if (is_array($item) && isset($item['type'])) {
                if ($item['type'] === 'heading') {
                    $items[] = [
                        'type' => 'heading',
                        'text' => $item['text'] ?? '',
                        'size' => $item['size'] ?? 'medium',
                    ];
                } elseif ($item['type'] === 'link' && isset($item['link_id'])) {
                    $link = get_post($item['link_id']);
                    if ($link && $link->post_type === LinkPostType::POST_TYPE) {
                        $items[] = $this->prepare_link_item($link);
                    }
                }
            } elseif (is_numeric($item)) {
                // Legacy format: just link ID
                $link = get_post($item);
                if ($link && $link->post_type === LinkPostType::POST_TYPE) {
                    $items[] = $this->prepare_link_item($link);
                }
            }
        }

        return new WP_REST_Response([
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'status'      => $post->post_status,
            'permalink'   => get_permalink($post->ID),
            'preview_url' => add_query_arg('lh_preview', '1', get_permalink($post->ID)),
            'edit_url'    => get_edit_post_link($post->ID, 'raw'),
            'settings'    => $this->get_tree_settings($post->ID),
            'items'       => $items,
        ]);
    }

    /**
     * Prepare link item for response
     */
    private function prepare_link_item($link) {
        $image_id = get_post_meta($link->ID, LinkPostType::META_IMAGE, true);

        return [
            'type'          => 'link',
            'link_id'       => $link->ID,
            'title'         => $link->post_title,
            'url'           => get_post_meta($link->ID, LinkPostType::META_URL, true),
            'description'   => get_post_meta($link->ID, LinkPostType::META_DESCRIPTION, true),
            'display_style' => get_post_meta($link->ID, LinkPostType::META_DISPLAY_STYLE, true) ?: 'bar',
            'icon'          => get_post_meta($link->ID, LinkPostType::META_ICON, true),
            'click_count'   => (int) get_post_meta($link->ID, LinkPostType::META_CLICK_COUNT, true),
            'image_id'      => $image_id ? (int) $image_id : null,
            'image_url'     => $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : null,
            'edit_url'      => get_edit_post_link($link->ID, 'raw'),
        ];
    }

    /**
     * Get tree settings as array
     */
    private function get_tree_settings($tree_id) {
        $header_image_id = get_post_meta($tree_id, TreePostType::META_HEADER_IMAGE, true);
        $bg_image_id = get_post_meta($tree_id, TreePostType::META_BACKGROUND_IMAGE, true);

        return [
            'header_image_id'       => $header_image_id ? (int) $header_image_id : null,
            'header_image_url'      => $header_image_id ? wp_get_attachment_image_url($header_image_id, 'medium') : null,
            'about_text'            => get_post_meta($tree_id, TreePostType::META_ABOUT_TEXT, true),
            'social_links'          => get_post_meta($tree_id, TreePostType::META_SOCIAL_LINKS, true) ?: [],
            'background_color'      => get_post_meta($tree_id, TreePostType::META_BACKGROUND_COLOR, true) ?: '#8b8178',
            'tree_background_color' => get_post_meta($tree_id, TreePostType::META_TREE_BACKGROUND_COLOR, true) ?: '#f5f5f5',
            'background_image_id'   => $bg_image_id ? (int) $bg_image_id : null,
            'background_image_url'  => $bg_image_id ? wp_get_attachment_image_url($bg_image_id, 'medium') : null,
            'hero_shape'            => get_post_meta($tree_id, TreePostType::META_HERO_SHAPE, true) ?: 'round',
            'hero_fade'             => get_post_meta($tree_id, TreePostType::META_HERO_FADE, true) === '1',
            'title_color'           => get_post_meta($tree_id, TreePostType::META_TITLE_COLOR, true) ?: '#1a1a1a',
            'bio_color'             => get_post_meta($tree_id, TreePostType::META_BIO_COLOR, true) ?: '#555555',
            'social_style'          => get_post_meta($tree_id, TreePostType::META_SOCIAL_STYLE, true) ?: 'circle',
            'social_color'          => get_post_meta($tree_id, TreePostType::META_SOCIAL_COLOR, true) ?: '#333333',
            'link_background_color' => get_post_meta($tree_id, TreePostType::META_LINK_BACKGROUND_COLOR, true) ?: '#eeeeee',
            'link_text_color'       => get_post_meta($tree_id, TreePostType::META_LINK_TEXT_COLOR, true) ?: '#000000',
            'title_font'            => get_post_meta($tree_id, TreePostType::META_TITLE_FONT, true) ?: 'system',
            'body_font'             => get_post_meta($tree_id, TreePostType::META_BODY_FONT, true) ?: 'system',
            'heading_size'          => get_post_meta($tree_id, TreePostType::META_HEADING_SIZE, true) ?: 'medium',
            'hide_header_footer'    => get_post_meta($tree_id, TreePostType::META_HIDE_HEADER_FOOTER, true) === '1',
        ];
    }

    /**
     * Sanitize setting value based on key
     */
    private function sanitize_setting($key, $value) {
        switch ($key) {
            case 'header_image_id':
            case 'background_image_id':
                return absint($value);

            case 'about_text':
                return wp_kses_post($value);

            case 'social_links':
                if (!is_array($value)) {
                    return [];
                }
                return array_map(function($item) {
                    return [
                        'platform' => sanitize_text_field($item['platform'] ?? ''),
                        'url'      => esc_url_raw($item['url'] ?? ''),
                    ];
                }, $value);

            case 'background_color':
            case 'tree_background_color':
            case 'title_color':
            case 'bio_color':
            case 'social_color':
            case 'link_background_color':
            case 'link_text_color':
                return sanitize_hex_color($value) ?: '';

            case 'hero_shape':
                return in_array($value, ['round', 'rounded', 'square']) ? $value : 'round';

            case 'social_style':
                return in_array($value, ['circle', 'rounded', 'square', 'minimal']) ? $value : 'circle';

            case 'hero_fade':
            case 'hide_header_footer':
                return $value ? '1' : '';

            case 'title_font':
            case 'body_font':
                $valid_fonts = ['system', 'serif', 'sans', 'mono', 'poppins', 'montserrat', 'playfair', 'raleway', 'open-sans', 'roboto', 'lato', 'merriweather'];
                return in_array($value, $valid_fonts) ? $value : 'system';

            case 'heading_size':
                return in_array($value, ['small', 'medium', 'large']) ? $value : 'medium';

            default:
                return sanitize_text_field($value);
        }
    }
}
