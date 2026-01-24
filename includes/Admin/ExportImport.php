<?php
/**
 * Export/Import Admin Page
 *
 * @package ElyseVIP\LinkHub
 */

namespace ElyseVIP\LinkHub\Admin;

use ElyseVIP\LinkHub\PostTypes\TreePostType;
use ElyseVIP\LinkHub\Export\TreeExporter;

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
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'handle_export']);
    }

    /**
     * Add submenu page under Trees
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=' . TreePostType::POST_TYPE,
            __('Export / Import', 'linkhub'),
            __('Export / Import', 'linkhub'),
            'manage_options',
            'dtol-export-import',
            [$this, 'render_page']
        );
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
     * Render the admin page
     */
    public function render_page() {
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

            <div class="dtol-export-import-container" style="display: flex; gap: 30px; margin-top: 20px;">
                <!-- Export Section -->
                <div class="dtol-export-section" style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
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
                                                        <input type="checkbox" name="LH_export_trees[]" value="<?php echo esc_attr($tree->ID); ?>" class="dtol-tree-checkbox">
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
                <div class="dtol-import-section" style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h2><?php _e('Import Trees', 'linkhub'); ?></h2>
                    <p class="description">
                        <?php _e('Import trees and links from a previously exported JSON file.', 'linkhub'); ?>
                    </p>

                    <div class="notice notice-info inline" style="margin: 15px 0;">
                        <p>
                            <span class="dashicons dashicons-info" style="color: #0073aa;"></span>
                            <?php _e('Import functionality coming soon. For now, use this tool to export your data for backup.', 'linkhub'); ?>
                        </p>
                    </div>

                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('LH_import', 'LH_import_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="LH_import_file"><?php _e('Import File', 'linkhub'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="LH_import_file" name="LH_import_file" accept=".json" disabled>
                                    <p class="description"><?php _e('Select a .json export file', 'linkhub'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-secondary" disabled>
                                <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 5px;"></span>
                                <?php _e('Import Trees', 'linkhub'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Export Format Info -->
            <div class="dtol-format-info" style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
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
}
