<?php
/**
 * Export/Import Admin Page
 *
 * @package LinkHub
 */

namespace LinkHub\Admin;

use LinkHub\PostTypes\TreePostType;
use LinkHub\PostTypes\LinkPostType;
use LinkHub\Export\TreeExporter;

/**
 * Export/Import Admin Class
 *
 * Handles the admin page for exporting and importing trees/links
 */
class ExportImport {

    /**
     * Singleton instance
     *
     * @var ExportImport
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return ExportImport
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Menu removed - functionality available in Tree Builder UI
        // add_action('admin_menu', [$this, 'add_menu_page'], 20);
        add_action('admin_init', [$this, 'handle_export']);
        add_action('admin_init', [$this, 'handle_import']);
        add_action('admin_init', [$this, 'handle_cleanup_duplicates']);
        add_action('admin_notices', [$this, 'show_import_notices']);
    }

    /**
     * Add submenu page under LinkHub
     */
    public function add_menu_page() {
        add_submenu_page(
            'lh-tree-builder',
            __('Export/Import', 'linkhub'),
            __('Export/Import', 'linkhub'),
            'manage_options',
            'lh-export-import',
            [$this, 'render_page']
        );
    }

    /**
     * Show import notices
     */
    public function show_import_notices() {
        // Check for import message from URL parameter
        if (isset($_GET['imported'])) {
            $count = intval($_GET['imported']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(_n('%d tree imported successfully.', '%d trees imported successfully.', $count, 'linkhub'), $count)
            );
        }
        
        // Check for cleanup message
        if (isset($_GET['cleaned'])) {
            $count = intval($_GET['cleaned']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(_n('%d duplicate link removed.', '%d duplicate links removed.', $count, 'linkhub'), $count)
            );
        }

        // Check for single tree import message
        if (isset($_GET['message']) && $_GET['message'] === 'imported' && isset($_GET['post'])) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                __('Tree imported successfully.', 'linkhub')
            );
        }

        // Check for transient message
        $message = get_transient('LH_import_message');
        if ($message) {
            delete_transient('LH_import_message');
            $class = $message['type'] === 'success' ? 'notice-success' : 'notice-warning';
            printf(
                '<div class="notice %s is-dismissible"><p>%s</p></div>',
                esc_attr($class),
                esc_html($message['message'])
            );
        }
    }

    /**
     * Handle export form submission
     */
    public function handle_export() {
        if (!isset($_POST['LH_export_nonce']) || !wp_verify_nonce($_POST['LH_export_nonce'], 'LH_export')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Get selected trees (empty = all)
        $tree_ids = isset($_POST['LH_export_trees']) ? array_map('intval', $_POST['LH_export_trees']) : [];

        // Generate export data
        $export_data = TreeExporter::export($tree_ids);
        $json = TreeExporter::to_json($export_data);
        $filename = TreeExporter::get_filename();

        // Send as download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $json;
        exit;
    }

    /**
     * Handle import form submission
     */
    public function handle_import() {
        if (!isset($_POST['LH_import_nonce']) || !wp_verify_nonce($_POST['LH_import_nonce'], 'LH_import')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'linkhub'));
        }

        if (empty($_FILES['LH_import_file']['tmp_name'])) {
            add_settings_error('LH_import', 'no-file', __('Please select a file to import.', 'linkhub'), 'error');
            return;
        }

        $file = $_FILES['LH_import_file'];

        // Validate file type
        if ($file['type'] !== 'application/json' && !str_ends_with($file['name'], '.json')) {
            add_settings_error('LH_import', 'invalid-type', __('Please upload a valid JSON file.', 'linkhub'), 'error');
            return;
        }

        // Read and decode JSON
        $json = file_get_contents($file['tmp_name']);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            add_settings_error('LH_import', 'invalid-json', __('Invalid JSON file format.', 'linkhub'), 'error');
            return;
        }

        // Validate export format
        if (empty($data['trees'])) {
            add_settings_error('LH_import', 'invalid-format', __('Invalid export file format. No trees found.', 'linkhub'), 'error');
            return;
        }

        // Import trees
        $imported = 0;
        $errors = 0;
        $first_tree_id = null;

        foreach ($data['trees'] as $tree_data) {
            error_log('Importing tree: ' . ($tree_data['title'] ?? 'unknown'));
            $result = $this->import_tree($tree_data);
            if ($result) {
                if ($first_tree_id === null) {
                    $first_tree_id = $result;
                }
                $imported++;
            } else {
                $errors++;
            }
        }

        // Show success message
        $message = sprintf(
            _n('%d tree imported successfully.', '%d trees imported successfully.', $imported, 'linkhub'),
            $imported
        );
        
        if ($errors > 0) {
            $message .= ' ' . sprintf(
                _n('%d tree failed to import.', '%d trees failed to import.', $errors, 'linkhub'),
                $errors
            );
        }

        set_transient('LH_import_message', [
            'type' => $errors > 0 ? 'warning' : 'success',
            'message' => $message,
        ], 30);

        // Redirect to imported tree edit page if single tree, otherwise to tree list
        if ($imported === 1 && $first_tree_id) {
            wp_redirect(admin_url('post.php?post=' . $first_tree_id . '&action=edit&message=imported'));
        } else {
            wp_redirect(admin_url('edit.php?post_type=' . TreePostType::POST_TYPE . '&imported=' . $imported));
        }
        exit;
    }

    /**
     * Handle cleanup duplicates action
     */
    public function handle_cleanup_duplicates() {
        if (!isset($_POST['LH_cleanup_duplicates_nonce']) || !wp_verify_nonce($_POST['LH_cleanup_duplicates_nonce'], 'LH_cleanup_duplicates')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'linkhub'));
        }

        $deleted = 0;
        
        // Get all links
        $all_links = get_posts([
            'post_type' => LinkPostType::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        $seen_urls = [];
        
        foreach ($all_links as $link) {
            $url = get_post_meta($link->ID, LinkPostType::META_URL, true);
            
            if (empty($url)) {
                continue;
            }
            
            if (isset($seen_urls[$url])) {
                // Duplicate found - delete this one
                wp_delete_post($link->ID, true);
                $deleted++;
            } else {
                // First occurrence - keep it
                $seen_urls[$url] = $link->ID;
            }
        }

        $message = sprintf(
            _n('%d duplicate link removed.', '%d duplicate links removed.', $deleted, 'linkhub'),
            $deleted
        );

        set_transient('LH_import_message', [
            'type' => 'success',
            'message' => $message,
        ], 30);

        wp_redirect(admin_url('admin.php?page=lh-export-import&cleaned=' . $deleted));
        exit;
    }

    /**
     * Import a single tree
     *
     * @param array $tree_data Tree data from export
     * @return int|false New post ID or false on failure
     */
    private function import_tree($tree_data) {
        // Create the tree post
        $post_data = [
            'post_title'   => $tree_data['title'],
            'post_name'    => $tree_data['slug'],
            'post_type'    => TreePostType::POST_TYPE,
            'post_status'  => $tree_data['status'] ?? 'publish',
            'post_content' => $tree_data['description'] ?? '',
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return false;
        }

        // Import tree meta data
        if (!empty($tree_data['meta'])) {
            $this->import_tree_meta($post_id, $tree_data['meta']);
        }

        // Import links and create Link CPT posts
        $tree_links = [];
        if (!empty($tree_data['links'])) {
            foreach ($tree_data['links'] as $link_data) {
                // Check if this is an old-style heading (display_style: "heading")
                $is_heading = !empty($link_data['meta']['display_style']) && $link_data['meta']['display_style'] === 'heading';
                
                if ($is_heading) {
                    // Convert to new heading format
                    $tree_links[] = [
                        'type' => 'heading',
                        'text' => $link_data['title'],
                        'size' => 'medium', // Default size
                    ];
                } else {
                    // Regular link - create Link CPT post
                    $link_id = $this->import_link($link_data);
                    if ($link_id) {
                        $tree_links[] = [
                            'link_id' => $link_id,
                            'design_id' => $link_data['design_override'] ?? 0,
                        ];
                    }
                }
            }
        }

        // Save the tree links array
        if (!empty($tree_links)) {
            update_post_meta($post_id, TreePostType::META_TREE_LINKS, $tree_links);
        }

        return $post_id;
    }

    /**
     * Import tree meta data
     *
     * @param int $tree_id Tree post ID
     * @param array $meta Meta data array
     */
    private function import_tree_meta($tree_id, $meta) {
        // Handle header image - download from URL
        if (!empty($meta['header_image']['url'])) {
            $image_id = $this->import_image_from_url($meta['header_image']['url'], $meta['header_image']['alt'] ?? '');
            if ($image_id) {
                update_post_meta($tree_id, TreePostType::META_HEADER_IMAGE, $image_id);
            }
        }

        // Simple text fields
        if (isset($meta['about_text'])) {
            update_post_meta($tree_id, TreePostType::META_ABOUT_TEXT, $meta['about_text']);
        }

        // Social links
        if (!empty($meta['social_links'])) {
            update_post_meta($tree_id, TreePostType::META_SOCIAL_LINKS, $meta['social_links']);
        }

        // Styling options
        $style_fields = [
            'background_color',
            'tree_background_color',
            'hero_shape',
            'hero_fade',
            'title_color',
            'bio_color',
            'social_style',
            'social_color',
            'heading_size',
        ];

        foreach ($style_fields as $field) {
            if (isset($meta[$field])) {
                update_post_meta($tree_id, 'LH_tree_' . $field, $meta[$field]);
            }
        }

        // Handle background image - download from URL
        if (!empty($meta['background_image']['url'])) {
            $image_id = $this->import_image_from_url($meta['background_image']['url']);
            if ($image_id) {
                update_post_meta($tree_id, TreePostType::META_BACKGROUND_IMAGE, $image_id);
            }
        }
    }

    /**
     * Import a single link
     *
     * @param array $link_data Link data from export
     * @return int|false New link post ID or false on failure
     */
    private function import_link($link_data) {
        // Check for duplicate URL if URL is provided
        if (!empty($link_data['meta']['url'])) {
            $existing = get_posts([
                'post_type' => LinkPostType::POST_TYPE,
                'meta_query' => [
                    [
                        'key' => LinkPostType::META_URL,
                        'value' => $link_data['meta']['url'],
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            
            if (!empty($existing)) {
                // Return existing link ID instead of creating duplicate
                return $existing[0];
            }
        }
        
        // Create the link post
        $post_data = [
            'post_title'  => $link_data['title'],
            'post_type'   => LinkPostType::POST_TYPE,
            'post_status' => $link_data['status'] ?? 'publish',
        ];

        $link_id = wp_insert_post($post_data);

        if (is_wp_error($link_id)) {
            return false;
        }

        // Import link meta
        if (!empty($link_data['meta'])) {
            $meta = $link_data['meta'];

            // URL
            if (isset($meta['url'])) {
                update_post_meta($link_id, LinkPostType::META_URL, $meta['url']);
            }

            // Icon
            if (isset($meta['icon'])) {
                update_post_meta($link_id, LinkPostType::META_ICON, $meta['icon']);
            }

            // Image - download from URL
            if (!empty($meta['image']['url'])) {
                $image_id = $this->import_image_from_url($meta['image']['url'], $meta['image']['alt'] ?? '');
                if ($image_id) {
                    update_post_meta($link_id, LinkPostType::META_IMAGE, $image_id);
                }
            }

            // Display style
            if (isset($meta['display_style'])) {
                update_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, $meta['display_style']);
            }

            // Description
            if (isset($meta['description'])) {
                update_post_meta($link_id, LinkPostType::META_DESCRIPTION, $meta['description']);
            }

            // Colors
            if (isset($meta['background_color'])) {
                update_post_meta($link_id, LinkPostType::META_BACKGROUND_COLOR, $meta['background_color']);
            }
            if (isset($meta['text_color'])) {
                update_post_meta($link_id, LinkPostType::META_TEXT_COLOR, $meta['text_color']);
            }

            // Stats (optional - can reset if desired)
            if (isset($meta['click_count']) && $meta['click_count']) {
                update_post_meta($link_id, LinkPostType::META_CLICK_COUNT, intval($meta['click_count']));
            }
            if (!empty($meta['last_clicked'])) {
                update_post_meta($link_id, LinkPostType::META_LAST_CLICKED, $meta['last_clicked']);
            }
        }

        return $link_id;
    }

    /**
     * Render the admin page
     */
    public function render_page() {
        // Display any import messages
        $messages = get_transient('LH_import_message');
        if ($messages) {
            delete_transient('LH_import_message');
            settings_errors('LH_import');
        }

        // Get all trees for the selection list
        $trees = get_posts([
            'post_type' => TreePostType::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        ?>
        <div class="wrap">
            <h1><?php _e('Export / Import Trees', 'linkhub'); ?></h1>

            <div class="lh-export-import-container" style="display: flex; gap: 30px; margin-top: 20px;">
                <!-- Export Section -->
                <div class="lh-export-section" style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h2><?php _e('Export Trees', 'linkhub'); ?></h2>
                    <p class="description">
                        <?php _e('Export your trees and links to a JSON file. This can be used for backup or migration to another site.', 'linkhub'); ?>
                    </p>

                    <form method="post" action="">
                        <?php wp_nonce_field('LH_export', 'LH_export_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Select Trees to Export', 'linkhub'); ?></label>
                                </th>
                                <td>
                                    <?php if (empty($trees)): ?>
                                        <p class="description"><?php _e('No trees found.', 'linkhub'); ?></p>
                                    <?php else: ?>
                                        <fieldset>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="checkbox" id="LH_export_all" checked>
                                                <strong><?php _e('Export All Trees', 'linkhub'); ?></strong>
                                            </label>
                                            <div id="LH_tree_list" style="margin-left: 20px; display: none;">
                                                <?php foreach ($trees as $tree): ?>
                                                    <?php
                                                    $link_count = 0;
                                                    $tree_links = get_post_meta($tree->ID, TreePostType::META_TREE_LINKS, true);
                                                    if (is_array($tree_links)) {
                                                        $link_count = count($tree_links);
                                                    }
                                                    ?>
                                                    <label style="display: block; margin-bottom: 5px;">
                                                        <input type="checkbox" name="LH_export_trees[]" value="<?php echo esc_attr($tree->ID); ?>" class="lh-tree-checkbox">
                                                        <?php echo esc_html($tree->post_title); ?>
                                                        <span class="description">(<?php printf(_n('%d link', '%d links', $link_count, 'linkhub'), $link_count); ?>)</span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </fieldset>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>

                        <?php if (!empty($trees)): ?>
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                                    <?php _e('Download Export File', 'linkhub'); ?>
                                </button>
                            </p>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Import Section -->
                <div class="lh-import-section" style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h2><?php _e('Import Trees', 'linkhub'); ?></h2>
                    <p class="description">
                        <?php _e('Import trees and links from a previously exported JSON file.', 'linkhub'); ?>
                    </p>

                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('LH_import', 'LH_import_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="LH_import_file"><?php _e('Import File', 'linkhub'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="LH_import_file" name="LH_import_file" accept=".json" required>
                                    <p class="description"><?php _e('Select a .json export file', 'linkhub'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 5px;"></span>
                                <?php _e('Import Trees', 'linkhub'); ?>
                            </button>
                        </p>
                    </form>
                    
                    <hr style="margin: 30px 0;">
                    
                    <h3><?php _e('Remove Duplicate Links', 'linkhub'); ?></h3>
                    <p class="description">
                        <?php _e('If you have duplicate links with the same URL, use this tool to remove them. The first occurrence of each URL will be kept.', 'linkhub'); ?>
                    </p>
                    
                    <form method="post" action="" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to remove duplicate links? This action cannot be undone.', 'linkhub')); ?>');">
                        <?php wp_nonce_field('LH_cleanup_duplicates', 'LH_cleanup_duplicates_nonce'); ?>
                        
                        <p class="submit">
                            <button type="submit" class="button button-secondary">
                                <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-right: 5px;"></span>
                                <?php _e('Remove Duplicate Links', 'linkhub'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Export Format Info -->
            <div class="lh-format-info" style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3><?php _e('Export Format Information', 'linkhub'); ?></h3>
                <p><?php _e('The export file contains:', 'linkhub'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('All tree settings (title, slug, styling options)', 'linkhub'); ?></li>
                    <li><?php _e('All links with their order and settings', 'linkhub'); ?></li>
                    <li><?php _e('Image URLs (images will need to be re-uploaded or remain accessible)', 'linkhub'); ?></li>
                    <li><?php _e('Social links configuration', 'linkhub'); ?></li>
                    <li><?php _e('Click statistics (optional - can be reset on import)', 'linkhub'); ?></li>
                </ul>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Toggle individual tree selection
            $('#LH_export_all').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#LH_tree_list').hide();
                    $('.lh-tree-checkbox').prop('checked', false);
                } else {
                    $('#LH_tree_list').show();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Download and import an image from URL
     *
     * @param string $url Image URL
     * @param string $alt_text Alt text for image
     * @return int|false Attachment ID or false on failure
     */
    private function import_image_from_url($url, $alt_text = '') {
        if (empty($url)) {
            return false;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download image to temp file
        $temp_file = download_url($url);
        
        if (is_wp_error($temp_file)) {
            return false;
        }

        // Prepare file array
        $file_array = [
            'name' => basename($url),
            'tmp_name' => $temp_file,
        ];

        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, 0);

        // Clean up temp file
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }

        if (is_wp_error($attachment_id)) {
            return false;
        }

        // Set alt text if provided
        if (!empty($alt_text)) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        }

        return $attachment_id;
    }
}

