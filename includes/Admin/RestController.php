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

        // GET /trees/{id}/export - Export tree data
        register_rest_route($this->namespace, '/trees/(?P<id>\d+)/export', [
            'methods'             => 'GET',
            'callback'            => [$this, 'export_tree'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // POST /trees/{id}/import - Import tree data
        register_rest_route($this->namespace, '/trees/(?P<id>\d+)/import', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_tree'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // POST /import/clickwhale - Import from Clickwhale database
        register_rest_route($this->namespace, '/import/clickwhale', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_clickwhale'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // POST /import/clickwhale-csv - Import from Clickwhale CSV file
        register_rest_route($this->namespace, '/import/clickwhale-csv', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_clickwhale_csv'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // DELETE /trees/{id}/reset - Reset tree (delete all links and settings)
        register_rest_route($this->namespace, '/trees/(?P<id>\d+)/reset', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'reset_tree'],
            'permission_callback' => [$this, 'permissions_check_admin'],
        ]);
    }

    /**
     * Permission check for all endpoints
     */
    public function permissions_check() {
        return current_user_can('edit_posts');
    }

    /**
     * Permission check for admin-only endpoints
     */
    public function permissions_check_admin() {
        return current_user_can('manage_options');
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

        // Update title if provided
        $title = $request->get_param('title');
        if ($title !== null) {
            wp_update_post([
                'ID'         => $tree_id,
                'post_title' => sanitize_text_field($title),
            ]);
        }

        // Update slug if provided
        $slug = $request->get_param('slug');
        if ($slug !== null) {
            wp_update_post([
                'ID'         => $tree_id,
                'post_name'  => sanitize_title($slug),
            ]);
        }

        // Update status if provided
        $status = $request->get_param('status');
        if ($status !== null && in_array($status, ['publish', 'draft'])) {
            wp_update_post([
                'ID'          => $tree_id,
                'post_status' => $status,
            ]);
        }

        $settings = $request->get_param('settings');

        if (is_array($settings)) {
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
            'slug'        => $post->post_name,
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

    /**
     * Export tree data as JSON
     */
    public function export_tree(WP_REST_Request $request) {
        $tree_id = (int) $request->get_param('id');
        $tree = get_post($tree_id);

        if (!$tree || $tree->post_type !== TreePostType::POST_TYPE) {
            return new WP_Error('not_found', 'Tree not found', ['status' => 404]);
        }

        // Get tree settings
        $settings = $this->get_tree_settings($tree_id);

        // Get tree items (links and headings)
        $items = get_post_meta($tree_id, TreePostType::META_TREE_LINKS, true) ?: [];

        // Prepare links data
        $links = [];
        foreach ($items as $item) {
            if ($item['type'] === 'link' && !empty($item['link_id'])) {
                $link_post = get_post($item['link_id']);
                if ($link_post) {
                    $image_id = get_post_meta($item['link_id'], LinkPostType::META_IMAGE, true);
                    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
                    
                    $links[] = [
                        'title'         => $link_post->post_title,
                        'url'           => get_post_meta($item['link_id'], LinkPostType::META_URL, true),
                        'display_style' => get_post_meta($item['link_id'], LinkPostType::META_DISPLAY_STYLE, true) ?: 'bar',
                        'icon'          => get_post_meta($item['link_id'], LinkPostType::META_ICON, true),
                        'image_url'     => $image_url,
                    ];
                }
            } elseif ($item['type'] === 'heading') {
                $links[] = [
                    'type' => 'heading',
                    'text' => $item['text'] ?? '',
                    'size' => $item['size'] ?? 'medium',
                ];
            }
        }

        return new WP_REST_Response([
            'version'  => '1.0',
            'exported' => current_time('mysql'),
            'tree'     => [
                'title'    => $tree->post_title,
                'settings' => $settings,
                'items'    => $links,
            ],
        ]);
    }

    /**
     * Import tree data from JSON
     */
    public function import_tree(WP_REST_Request $request) {
        $tree_id = (int) $request->get_param('id');
        $tree = get_post($tree_id);

        if (!$tree || $tree->post_type !== TreePostType::POST_TYPE) {
            return new WP_Error('not_found', 'Tree not found', ['status' => 404]);
        }

        $data = $request->get_json_params();

        if (empty($data['tree'])) {
            return new WP_Error('invalid_data', 'Invalid import data', ['status' => 400]);
        }

        $import_tree = $data['tree'];

        // Update tree title if provided
        if (!empty($import_tree['title'])) {
            wp_update_post([
                'ID'         => $tree_id,
                'post_title' => sanitize_text_field($import_tree['title']),
            ]);
        }

        // Update settings if provided
        if (!empty($import_tree['settings'])) {
            foreach ($import_tree['settings'] as $key => $value) {
                $sanitized = $this->sanitize_setting($key, $value);
                $this->save_tree_setting($tree_id, $key, $sanitized);
            }
        }

        // Import items (create links as needed)
        if (!empty($import_tree['items'])) {
            $items = [];
            foreach ($import_tree['items'] as $item) {
                if (isset($item['type']) && $item['type'] === 'heading') {
                    $items[] = [
                        'type' => 'heading',
                        'text' => sanitize_text_field($item['text'] ?? ''),
                        'size' => in_array($item['size'] ?? '', ['small', 'medium', 'large']) ? $item['size'] : 'medium',
                    ];
                } else {
                    // Check if link already exists by URL
                    $link_id = null;
                    if (!empty($item['url'])) {
                        $link_id = $this->find_link_by_url($item['url']);
                    }
                    
                    // If link doesn't exist, create it
                    if (!$link_id) {
                        $link_id = wp_insert_post([
                            'post_type'   => LinkPostType::POST_TYPE,
                            'post_title'  => sanitize_text_field($item['title'] ?? 'Imported Link'),
                            'post_status' => 'publish',
                        ]);
                    }

                    if (!is_wp_error($link_id) && $link_id) {
                        if (!empty($item['url'])) {
                            update_post_meta($link_id, LinkPostType::META_URL, esc_url_raw($item['url']));
                        }
                        if (!empty($item['display_style'])) {
                            update_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, sanitize_text_field($item['display_style']));
                        }
                        if (!empty($item['icon'])) {
                            update_post_meta($link_id, LinkPostType::META_ICON, sanitize_text_field($item['icon']));
                        }
                        // Import image from URL if provided
                        if (!empty($item['image_url'])) {
                            $image_id = $this->import_image_from_url($item['image_url'], $link_id);
                            if ($image_id) {
                                update_post_meta($link_id, LinkPostType::META_IMAGE, $image_id);
                            }
                        }

                        $items[] = [
                            'type'    => 'link',
                            'link_id' => $link_id,
                        ];
                    }
                }
            }

            update_post_meta($tree_id, TreePostType::META_TREE_LINKS, $items);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Import completed',
        ]);
    }

    /**
     * Import links from Clickwhale plugin
     */
    public function import_clickwhale(WP_REST_Request $request) {
        global $wpdb;

        $tree_id = (int) $request->get_param('tree_id');

        // Check if Clickwhale table exists
        $table = $wpdb->prefix . 'clickwhale_links';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;

        if (!$table_exists) {
            return new WP_Error('not_found', 'Clickwhale not installed or no links found', ['status' => 404]);
        }

        // Get Clickwhale links
        $cw_links = $wpdb->get_results("SELECT * FROM $table ORDER BY id ASC");

        if (empty($cw_links)) {
            return new WP_Error('not_found', 'No Clickwhale links found', ['status' => 404]);
        }

        $imported = 0;
        $items = [];

        foreach ($cw_links as $cw_link) {
            // Create new link post
            $link_id = wp_insert_post([
                'post_type'   => LinkPostType::POST_TYPE,
                'post_title'  => sanitize_text_field($cw_link->title ?: 'Imported Link'),
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($link_id)) {
                update_post_meta($link_id, LinkPostType::META_URL, esc_url_raw($cw_link->url));
                update_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, 'bar');

                $items[] = [
                    'type'    => 'link',
                    'link_id' => $link_id,
                ];
                $imported++;
            }
        }

        // Add to tree if tree_id provided
        if ($tree_id) {
            $existing_items = get_post_meta($tree_id, TreePostType::META_TREE_LINKS, true) ?: [];
            $merged_items = array_merge($existing_items, $items);
            update_post_meta($tree_id, TreePostType::META_TREE_LINKS, $merged_items);
        }

        return new WP_REST_Response([
            'success' => true,
            'count'   => $imported,
        ]);
    }

    /**
     * Import links from Clickwhale CSV file
     */
    public function import_clickwhale_csv(WP_REST_Request $request) {
        $files = $request->get_file_params();
        $tree_id = (int) $request->get_param('tree_id');

        if (empty($files['csv_file'])) {
            return new WP_Error('no_file', 'No CSV file uploaded', ['status' => 400]);
        }

        $file = $files['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload failed', ['status' => 400]);
        }

        $result = $this->parse_clickwhale_csv($file['tmp_name'], $tree_id);

        return new WP_REST_Response([
            'success' => true,
            'count'   => $result['count'],
            'errors'  => $result['errors'],
        ]);
    }

    /**
     * Parse Clickwhale CSV and import links
     */
    private function parse_clickwhale_csv($file_path, $tree_id) {
        $result = [
            'count'  => 0,
            'errors' => [],
        ];

        if (!file_exists($file_path)) {
            $result['errors'][] = 'File does not exist';
            return $result;
        }

        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $result['errors'][] = 'Could not read CSV headers';
            fclose($handle);
            return $result;
        }

        // Normalize headers (remove BOM, quotes, trim, lowercase)
        $headers = array_map(function($h) {
            $h = str_replace("\xEF\xBB\xBF", '', $h);
            return strtolower(trim(trim($h), '"'));
        }, $headers);

        // Find column indices
        $title_col = $this->find_csv_column($headers, ['title', 'name', 'label']);
        $url_col = $this->find_csv_column($headers, ['url', 'destination', 'link']);
        $clicks_col = $this->find_csv_column($headers, ['clicks', 'click_count', 'count']);
        $icon_col = $this->find_csv_column($headers, ['icon', 'emoji']);

        if ($title_col === false || $url_col === false) {
            $result['errors'][] = 'Required columns (title/name, url/destination) not found. Headers: ' . implode(', ', $headers);
            fclose($handle);
            return $result;
        }

        $items = [];
        $row_number = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;

            $title = isset($row[$title_col]) ? trim(trim($row[$title_col]), '"') : '';
            $url = isset($row[$url_col]) ? trim(trim($row[$url_col]), '"') : '';

            if (empty($title) || empty($url)) {
                $result['errors'][] = "Row {$row_number}: Missing title or URL";
                continue;
            }

            // Create link post
            $link_id = wp_insert_post([
                'post_type'   => LinkPostType::POST_TYPE,
                'post_title'  => sanitize_text_field($title),
                'post_status' => 'publish',
            ]);

            if ($link_id && !is_wp_error($link_id)) {
                update_post_meta($link_id, LinkPostType::META_URL, esc_url_raw($url));
                update_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, 'bar');

                // Save click count if available
                if ($clicks_col !== false && isset($row[$clicks_col])) {
                    $clicks = absint(trim($row[$clicks_col]));
                    if ($clicks > 0) {
                        update_post_meta($link_id, LinkPostType::META_CLICK_COUNT, $clicks);
                    }
                }

                // Save icon if available
                if ($icon_col !== false && isset($row[$icon_col])) {
                    $icon = trim(trim($row[$icon_col]), '"');
                    if (!empty($icon)) {
                        update_post_meta($link_id, LinkPostType::META_ICON, sanitize_text_field($icon));
                    }
                }

                $items[] = [
                    'type'    => 'link',
                    'link_id' => $link_id,
                ];
                $result['count']++;
            } else {
                $result['errors'][] = "Row {$row_number}: Failed to create link";
            }
        }

        fclose($handle);

        // Add to tree if tree_id provided
        if ($tree_id && !empty($items)) {
            $existing_items = get_post_meta($tree_id, TreePostType::META_TREE_LINKS, true) ?: [];
            $merged_items = array_merge($existing_items, $items);
            update_post_meta($tree_id, TreePostType::META_TREE_LINKS, $merged_items);
        }

        return $result;
    }

    /**
     * Find column index by possible names
     */
    private function find_csv_column($headers, $possible_names) {
        foreach ($possible_names as $name) {
            $index = array_search($name, $headers, true);
            if ($index !== false) {
                return $index;
            }
        }
        return false;
    }

    /**
     * Save a single tree setting
     */
    private function save_tree_setting($tree_id, $key, $value) {
        $meta_map = [
            'header_image_id'       => TreePostType::META_HEADER_IMAGE,
            'about_text'            => TreePostType::META_ABOUT_TEXT,
            'hero_shape'            => TreePostType::META_HERO_SHAPE,
            'hero_fade'             => TreePostType::META_HERO_FADE,
            'social_style'          => TreePostType::META_SOCIAL_STYLE,
            'social_links'          => TreePostType::META_SOCIAL_LINKS,
            'background_color'      => TreePostType::META_BACKGROUND_COLOR,
            'tree_background_color' => TreePostType::META_TREE_BACKGROUND_COLOR,
            'background_image_id'   => TreePostType::META_BACKGROUND_IMAGE,
            'title_color'           => TreePostType::META_TITLE_COLOR,
            'bio_color'             => TreePostType::META_BIO_COLOR,
            'social_color'          => TreePostType::META_SOCIAL_COLOR,
            'link_background_color' => TreePostType::META_LINK_BACKGROUND_COLOR,
            'link_text_color'       => TreePostType::META_LINK_TEXT_COLOR,
            'title_font'            => TreePostType::META_TITLE_FONT,
            'body_font'             => TreePostType::META_BODY_FONT,
            'heading_size'          => TreePostType::META_HEADING_SIZE,
            'hide_header_footer'    => TreePostType::META_HIDE_HEADER_FOOTER,
        ];

        if (isset($meta_map[$key])) {
            update_post_meta($tree_id, $meta_map[$key], $value);
        }
    }

    /**
     * Reset tree - delete all links and reset settings
     */
    public function reset_tree(WP_REST_Request $request) {
        $tree_id = (int) $request->get_param('id');
        $tree = get_post($tree_id);

        if (!$tree || $tree->post_type !== TreePostType::POST_TYPE) {
            return new WP_Error('not_found', 'Tree not found', ['status' => 404]);
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission to reset data', ['status' => 403]);
        }

        // Get all link IDs from tree
        $tree_links = get_post_meta($tree_id, TreePostType::META_TREE_LINKS, true) ?: [];
        $deleted_count = 0;

        foreach ($tree_links as $item) {
            if (isset($item['type']) && $item['type'] === 'link' && !empty($item['link_id'])) {
                $deleted = wp_delete_post($item['link_id'], true);
                if ($deleted) {
                    $deleted_count++;
                }
            }
        }

        // Clear all tree meta
        delete_post_meta($tree_id, TreePostType::META_TREE_LINKS);
        delete_post_meta($tree_id, TreePostType::META_HEADER_IMAGE);
        delete_post_meta($tree_id, TreePostType::META_ABOUT_TEXT);
        delete_post_meta($tree_id, TreePostType::META_SOCIAL_LINKS);
        delete_post_meta($tree_id, TreePostType::META_BACKGROUND_COLOR);
        delete_post_meta($tree_id, TreePostType::META_TREE_BACKGROUND_COLOR);
        delete_post_meta($tree_id, TreePostType::META_BACKGROUND_IMAGE);
        delete_post_meta($tree_id, TreePostType::META_HERO_SHAPE);
        delete_post_meta($tree_id, TreePostType::META_HERO_FADE);
        delete_post_meta($tree_id, TreePostType::META_TITLE_COLOR);
        delete_post_meta($tree_id, TreePostType::META_BIO_COLOR);
        delete_post_meta($tree_id, TreePostType::META_SOCIAL_STYLE);
        delete_post_meta($tree_id, TreePostType::META_SOCIAL_COLOR);
        delete_post_meta($tree_id, TreePostType::META_LINK_BACKGROUND_COLOR);
        delete_post_meta($tree_id, TreePostType::META_LINK_TEXT_COLOR);
        delete_post_meta($tree_id, TreePostType::META_TITLE_FONT);
        delete_post_meta($tree_id, TreePostType::META_BODY_FONT);
        delete_post_meta($tree_id, TreePostType::META_HEADING_SIZE);
        delete_post_meta($tree_id, TreePostType::META_HIDE_HEADER_FOOTER);

        // Initialize empty links array
        update_post_meta($tree_id, TreePostType::META_TREE_LINKS, []);

        return new WP_REST_Response([
            'success' => true,
            'deleted_links' => $deleted_count,
            'message' => sprintf('Reset complete. Deleted %d links and cleared all settings.', $deleted_count),
        ]);
    }

    /**
     * Import image from URL and attach to post
     * Checks if image already exists in media library before downloading
     *
     * @param string $image_url Image URL
     * @param int $post_id Post to attach to
     * @return int|false Attachment ID on success, false on failure
     */
    private function import_image_from_url($image_url, $post_id) {
        // First, check if this image already exists in the media library
        $existing_attachment = $this->find_attachment_by_url($image_url);
        if ($existing_attachment) {
            return $existing_attachment;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download image to temp file
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            return false;
        }

        // Prepare file array
        $file_array = [
            'name'     => basename($image_url),
            'tmp_name' => $temp_file,
        ];

        // Import as attachment
        $attachment_id = media_handle_sideload($file_array, $post_id);

        // Clean up temp file
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }

        if (is_wp_error($attachment_id)) {
            return false;
        }

        return $attachment_id;
    }

    /**
     * Find existing attachment by URL
     * Checks both full URL and filename
     *
     * @param string $image_url Image URL to search for
     * @return int|false Attachment ID if found, false otherwise
     */
    private function find_attachment_by_url($image_url) {
        global $wpdb;

        // Try to find by exact URL match in guid or meta
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment'",
            $image_url
        ));

        if ($attachment_id) {
            return intval($attachment_id);
        }

        // Try to find by filename (basename of URL)
        $filename = basename($image_url);
        $filename = preg_replace('/\?.*/', '', $filename); // Remove query string

        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND guid LIKE %s",
            '%' . $wpdb->esc_like($filename)
        ));

        if ($attachment_id) {
            return intval($attachment_id);
        }

        return false;
    }

    /**
     * Find existing link by URL
     *
     * @param string $url Link URL to search for
     * @return int|false Link post ID if found, false otherwise
     */
    private function find_link_by_url($url) {
        global $wpdb;

        // Normalize URL for comparison
        $url = esc_url_raw($url);

        // Search for existing link with matching URL in post meta
        $link_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND pm.meta_key = %s
            AND pm.meta_value = %s
            LIMIT 1",
            LinkPostType::POST_TYPE,
            LinkPostType::META_URL,
            $url
        ));

        if ($link_id) {
            return intval($link_id);
        }

        return false;
    }
}

