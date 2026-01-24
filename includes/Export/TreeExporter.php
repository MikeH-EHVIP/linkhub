<?php
/**
 * Tree Exporter
 *
 * @package ElyseVIP\LinkHub
 */

namespace ElyseVIP\LinkHub\Export;

use ElyseVIP\LinkHub\PostTypes\TreePostType;
use ElyseVIP\LinkHub\PostTypes\LinkPostType;

/**
 * Tree Exporter Class
 *
 * Handles exporting trees and links to JSON format for backup/migration
 */
class TreeExporter {

    /**
     * Export format version
     */
    const EXPORT_VERSION = '1.0';

    /**
     * Export all trees or specific trees
     *
     * @param array $tree_ids Optional array of tree IDs to export. Empty = all trees.
     * @return array Export data structure
     */
    public static function export($tree_ids = []) {
        $export_data = [
            'version' => self::EXPORT_VERSION,
            'plugin' => 'linkhub',
            'exported_at' => current_time('c'),
            'site_url' => get_site_url(),
            'trees' => [],
        ];

        // Get trees to export
        $args = [
            'post_type' => TreePostType::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        if (!empty($tree_ids)) {
            $args['post__in'] = array_map('intval', $tree_ids);
        }

        $trees = get_posts($args);

        foreach ($trees as $tree) {
            $export_data['trees'][] = self::export_tree($tree);
        }

        return $export_data;
    }

    /**
     * Export a single tree with all its data
     *
     * @param \WP_Post $tree Tree post object
     * @return array Tree export data
     */
    private static function export_tree($tree) {
        $tree_data = [
            'title' => $tree->post_title,
            'slug' => $tree->post_name,
            'status' => $tree->post_status,
            'meta' => self::export_tree_meta($tree->ID),
            'links' => [],
        ];

        // Get tree's links in order
        $tree_links = get_post_meta($tree->ID, TreePostType::META_TREE_LINKS, true);

        if (is_array($tree_links)) {
            $order = 0;
            foreach ($tree_links as $tree_link) {
                // Handle both old format (just ID) and new format (array with link_id and design_id)
                if (is_array($tree_link)) {
                    $link_id = $tree_link['link_id'] ?? 0;
                    $design_override = $tree_link['design_id'] ?? 0;
                } else {
                    $link_id = $tree_link;
                    $design_override = 0;
                }

                if (!$link_id) {
                    continue;
                }

                $link = get_post($link_id);
                if ($link && $link->post_type === LinkPostType::POST_TYPE) {
                    $link_data = self::export_link($link);
                    $link_data['order'] = $order;
                    $link_data['design_override'] = $design_override;
                    $tree_data['links'][] = $link_data;
                    $order++;
                }
            }
        }

        return $tree_data;
    }

    /**
     * Export tree meta data
     *
     * @param int $tree_id Tree post ID
     * @return array Tree meta data
     */
    private static function export_tree_meta($tree_id) {
        $meta = [];

        // Header image - export URL
        $header_image_id = get_post_meta($tree_id, TreePostType::META_HEADER_IMAGE, true);
        if ($header_image_id) {
            $meta['header_image'] = [
                'url' => wp_get_attachment_url($header_image_id),
                'alt' => get_post_meta($header_image_id, '_wp_attachment_image_alt', true),
            ];
        }

        // About text
        $meta['about_text'] = get_post_meta($tree_id, TreePostType::META_ABOUT_TEXT, true);

        // Social links
        $meta['social_links'] = get_post_meta($tree_id, TreePostType::META_SOCIAL_LINKS, true) ?: [];

        // Styling options
        $meta['background_color'] = get_post_meta($tree_id, TreePostType::META_BACKGROUND_COLOR, true);
        $meta['tree_background_color'] = get_post_meta($tree_id, TreePostType::META_TREE_BACKGROUND_COLOR, true);

        // Background image - export URL
        $bg_image_id = get_post_meta($tree_id, TreePostType::META_BACKGROUND_IMAGE, true);
        if ($bg_image_id) {
            $meta['background_image'] = [
                'url' => wp_get_attachment_url($bg_image_id),
            ];
        }

        $meta['hero_shape'] = get_post_meta($tree_id, TreePostType::META_HERO_SHAPE, true);
        $meta['hero_fade'] = get_post_meta($tree_id, TreePostType::META_HERO_FADE, true);
        $meta['title_color'] = get_post_meta($tree_id, TreePostType::META_TITLE_COLOR, true);
        $meta['bio_color'] = get_post_meta($tree_id, TreePostType::META_BIO_COLOR, true);
        $meta['social_style'] = get_post_meta($tree_id, TreePostType::META_SOCIAL_STYLE, true);
        $meta['social_color'] = get_post_meta($tree_id, TreePostType::META_SOCIAL_COLOR, true);
        $meta['heading_size'] = get_post_meta($tree_id, TreePostType::META_HEADING_SIZE, true);

        return $meta;
    }

    /**
     * Export a single link with all its data
     *
     * @param \WP_Post $link Link post object
     * @return array Link export data
     */
    private static function export_link($link) {
        $link_data = [
            'title' => $link->post_title,
            'status' => $link->post_status,
            'meta' => self::export_link_meta($link->ID),
        ];

        return $link_data;
    }

    /**
     * Export link meta data
     *
     * @param int $link_id Link post ID
     * @return array Link meta data
     */
    private static function export_link_meta($link_id) {
        $meta = [];

        $meta['url'] = get_post_meta($link_id, LinkPostType::META_URL, true);
        $meta['icon'] = get_post_meta($link_id, LinkPostType::META_ICON, true);

        // Link image - export URL
        $image_id = get_post_meta($link_id, LinkPostType::META_IMAGE, true);
        if ($image_id) {
            $meta['image'] = [
                'url' => wp_get_attachment_url($image_id),
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
            ];
        }

        $meta['display_style'] = get_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, true);
        $meta['description'] = get_post_meta($link_id, LinkPostType::META_DESCRIPTION, true);
        $meta['background_color'] = get_post_meta($link_id, LinkPostType::META_BACKGROUND_COLOR, true);
        $meta['text_color'] = get_post_meta($link_id, LinkPostType::META_TEXT_COLOR, true);

        // Stats (optional - may want to reset on import)
        $meta['click_count'] = get_post_meta($link_id, LinkPostType::META_CLICK_COUNT, true);
        $meta['last_clicked'] = get_post_meta($link_id, LinkPostType::META_LAST_CLICKED, true);

        return $meta;
    }

    /**
     * Generate JSON string from export data
     *
     * @param array $export_data Export data array
     * @return string JSON string
     */
    public static function to_json($export_data) {
        return wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get filename for export
     *
     * @return string Suggested filename
     */
    public static function get_filename() {
        $site_name = sanitize_title(get_bloginfo('name'));
        $date = date('Y-m-d');
        return "dtol-export-{$site_name}-{$date}.json";
    }
}
