<?php
/**
 * Clickwhale Importer
 *
 * @package LinkHub
 */

namespace LinkHub\Admin;

use LinkHub\PostTypes\LinkPostType;
use LinkHub\PostTypes\TreePostType;

/**
 * Clickwhale Importer Class
 */
class ClickwhaleImporter {
    
    /**
     * Singleton instance
     *
     * @var ClickwhaleImporter
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return ClickwhaleImporter
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
        add_action('admin_menu', [$this, 'add_menu_page'], 20);
        add_action('admin_post_LH_import_clickwhale', [$this, 'handle_import']);
    }
    
    /**
     * Add admin menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=' . TreePostType::POST_TYPE,
            __('Import from Clickwhale', 'linkhub'),
            __('Import', 'linkhub'),
            'manage_options',
            'lh-import-clickwhale',
            [$this, 'render_import_page']
        );
    }
    
    /**
     * Render import page
     */
    public function render_import_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Import Links from Clickwhale', 'linkhub'); ?></h1>
            
            <?php if (isset($_GET['imported'])): ?>
                <div class="notice notice-success">
                    <p>
                        <?php 
                        printf(
                            __('Successfully imported %d links!', 'linkhub'), 
                            absint($_GET['imported'])
                        ); 
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['errors'])): ?>
                <div class="notice notice-warning">
                    <p><strong><?php _e('Import completed with warnings:', 'linkhub'); ?></strong></p>
                    <p><?php echo esc_html(urldecode($_GET['errors'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Upload Clickwhale CSV Export', 'linkhub'); ?></h2>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('LH_import_clickwhale', 'LH_import_nonce'); ?>
                    <input type="hidden" name="action" value="LH_import_clickwhale">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="csv_file"><?php _e('CSV File', 'linkhub'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                                <p class="description">
                                    <?php _e('Upload your Clickwhale CSV export file.', 'linkhub'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="create_tree"><?php _e('Create Link Tree', 'linkhub'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="create_tree" id="create_tree" value="1" checked>
                                    <?php _e('Create a new Link Tree containing all imported links', 'linkhub'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr id="tree_name_row">
                            <th scope="row">
                                <label for="tree_name"><?php _e('Tree Name', 'linkhub'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="tree_name" id="tree_name" 
                                       value="<?php echo esc_attr(sprintf(__('Imported Links - %s', 'linkhub'), date('Y-m-d'))); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Import Links', 'linkhub'); ?>
                        </button>
                    </p>
                </form>
                
                <hr>
                
                <h3><?php _e('Expected CSV Format', 'linkhub'); ?></h3>
                <p><?php _e('Supports Clickwhale CSV exports and custom formats with these columns:', 'linkhub'); ?></p>
                <ul>
                    <li><code>title</code> or <code>name</code> - Link title/label <strong>(required)</strong></li>
                    <li><code>url</code> or <code>destination</code> - Destination URL <strong>(required)</strong></li>
                    <li><code>clicks</code> or <code>click_count</code> - Number of clicks (optional)</li>
                    <li><code>icon</code> - Icon class or emoji (optional)</li>
                </ul>
                
                <p><strong><?php _e('Clickwhale Export Example:', 'linkhub'); ?></strong></p>
                <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px;">title,slug,url,redirection,link_target,nofollow,sponsored
"My Website",my-website,https://example.com,301,blank,1,1
"Twitter",twitter,https://twitter.com/handle,302,,0,0</pre>
                
                <p><strong><?php _e('Simple Format Example:', 'linkhub'); ?></strong></p>
                <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px;">title,url,clicks,icon
My Website,https://example.com,150,??
Twitter,https://twitter.com/myhandle,89,fa-brands fa-twitter
Instagram,https://instagram.com/myhandle,203,??</pre>
                
                <p class="description">
                    <strong><?php _e('Note:', 'linkhub'); ?></strong>
                    <?php _e('Clickwhale-specific fields (slug, redirection, link_target, nofollow, sponsored) will be ignored. Click counts are preserved if present.', 'linkhub'); ?>
                </p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#create_tree').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#tree_name_row').show();
                } else {
                    $('#tree_name_row').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle CSV import
     */
    public function handle_import() {
        // Verify nonce
        if (!isset($_POST['LH_import_nonce']) || 
            !wp_verify_nonce($_POST['LH_import_nonce'], 'LH_import_clickwhale')) {
            wp_die(__('Security check failed', 'linkhub'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to import links.', 'linkhub'));
        }
        
        // Check file upload
        if (!isset($_FILES['csv_file'])) {
            wp_die(__('No file uploaded.', 'linkhub'));
        }
        
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = __('File upload failed: ', 'linkhub');
            switch ($_FILES['csv_file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_msg .= __('File too large.', 'linkhub');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_msg .= __('No file selected.', 'linkhub');
                    break;
                default:
                    $error_msg .= sprintf(__('Error code: %d', 'linkhub'), $_FILES['csv_file']['error']);
            }
            wp_die($error_msg);
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $create_tree = isset($_POST['create_tree']) && $_POST['create_tree'] === '1';
        $tree_name = isset($_POST['tree_name']) ? sanitize_text_field($_POST['tree_name']) : '';
        
        // Parse CSV and import
        $result = $this->import_csv($file);
        $imported_ids = $result['imported'];
        $errors = $result['errors'];
        
        // Create tree if requested
        if ($create_tree && !empty($imported_ids) && $tree_name) {
            $tree_id = wp_insert_post([
                'post_type'   => TreePostType::POST_TYPE,
                'post_title'  => $tree_name,
                'post_status' => 'publish',
            ]);
            
            if ($tree_id && !is_wp_error($tree_id)) {
                update_post_meta($tree_id, MetaBoxes::META_TREE_LINKS, $imported_ids);
            }
        }
        
        // Redirect with success message
        $redirect_args = [
            'page'     => 'lh-import-clickwhale',
            'imported' => count($imported_ids),
        ];
        
        if (!empty($errors)) {
            $redirect_args['errors'] = urlencode(implode('; ', $errors));
        }
        
        wp_redirect(add_query_arg($redirect_args, admin_url('edit.php?post_type=' . TreePostType::POST_TYPE)));
        exit;
    }
    
    /**
     * Import CSV file
     *
     * @param string $file_path Path to CSV file
     * @return array Array with 'imported' IDs and 'errors' messages
     */
    private function import_csv($file_path) {
        $result = [
            'imported' => [],
            'errors'   => [],
        ];
        
        if (!file_exists($file_path)) {
            $result['errors'][] = __('File does not exist', 'linkhub');
            return $result;
        }
        
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            $result['errors'][] = __('Could not open file', 'linkhub');
            return $result;
        }
        
        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $result['errors'][] = __('Could not read CSV headers', 'linkhub');
            fclose($handle);
            return $result;
        }
        
        // Normalize headers (remove BOM, quotes, trim, lowercase)
        $headers = array_map(function($h) {
            // Remove UTF-8 BOM if present
            $h = str_replace("\xEF\xBB\xBF", '', $h);
            return strtolower(trim(trim($h), '"'));
        }, $headers);
        
        // Find column indices
        $title_col = $this->find_column($headers, ['title', 'name', 'label']);
        $url_col = $this->find_column($headers, ['url', 'destination', 'link']);
        $clicks_col = $this->find_column($headers, ['clicks', 'click_count', 'count']);
        $icon_col = $this->find_column($headers, ['icon', 'emoji']);
        
        if ($title_col === false || $url_col === false) {
            $result['errors'][] = sprintf(
                __('Required columns not found. CSV headers: %s', 'linkhub'),
                implode(', ', $headers)
            );
            fclose($handle);
            return $result;
        }
        
        // Process each row
        $row_number = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            $title = isset($row[$title_col]) ? trim(trim($row[$title_col]), '"') : '';
            $url = isset($row[$url_col]) ? trim(trim($row[$url_col]), '"') : '';
            
            if (empty($title) || empty($url)) {
                $result['errors'][] = sprintf(__('Row %d: Missing title or URL', 'linkhub'), $row_number);
                continue;
            }
            
            // Check for duplicate URL
            $existing = get_posts([
                'post_type' => LinkPostType::POST_TYPE,
                'meta_query' => [
                    [
                        'key' => LinkPostType::META_URL,
                        'value' => esc_url_raw($url),
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            
            if (!empty($existing)) {
                $result['errors'][] = sprintf(__('Row %d: Duplicate URL already exists (Link ID: %d)', 'linkhub'), $row_number, $existing[0]);
                continue;
            }
            
            // Create link post
            $post_id = wp_insert_post([
                'post_type'   => LinkPostType::POST_TYPE,
                'post_title'  => $title,
                'post_status' => 'publish',
            ]);
            
            if ($post_id && !is_wp_error($post_id)) {
                // Save URL
                update_post_meta($post_id, LinkPostType::META_URL, esc_url_raw($url));
                
                // Save click count if available
                if ($clicks_col !== false && isset($row[$clicks_col])) {
                    $clicks = absint(trim($row[$clicks_col]));
                    if ($clicks > 0) {
                        update_post_meta($post_id, LinkPostType::META_CLICK_COUNT, $clicks);
                    }
                }
                
                // Save icon if available
                if ($icon_col !== false && isset($row[$icon_col])) {
                    $icon = trim(trim($row[$icon_col]), '"');
                    if (!empty($icon)) {
                        update_post_meta($post_id, LinkPostType::META_ICON, sanitize_text_field($icon));
                    }
                }
                
                $result['imported'][] = $post_id;
            } else {
                $result['errors'][] = sprintf(__('Row %d: Failed to create link', 'linkhub'), $row_number);
            }
        }
        
        fclose($handle);
        
        return $result;
    }
    
    /**
     * Find column index by possible names
     *
     * @param array $headers Header row
     * @param array $possible_names Possible column names
     * @return int|false Column index or false if not found
     */
    private function find_column($headers, $possible_names) {
        foreach ($possible_names as $name) {
            $index = array_search($name, $headers, true);
            if ($index !== false) {
                return $index;
            }
        }
        return false;
    }
}

