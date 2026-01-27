<?php
/**
 * Analytics REST Controller
 *
 * @package LinkHub
 */

namespace LinkHub\Analytics;

use WP_REST_Controller;
use WP_REST_Response;
use LinkHub\PostTypes\LinkPostType;

/**
 * Analytics Data API
 */
class RestController extends WP_REST_Controller {

    /**
     * API namespace
     */
    protected $namespace = 'linkhub/v1';

    /**
     * Initialize
     */
    public static function init() {
        add_action('rest_api_init', [new self(), 'register_routes']);
    }

    /**
     * Register routes
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/analytics/overview', [
            'methods' => 'GET',
            'callback' => [$this, 'get_overview'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
    }

    /**
     * Check permissions (Admins/Editors)
     */
    public function permissions_check() {
        return current_user_can('edit_posts');
    }

    /**
     * Get analytics overview data
     */
    public function get_overview( \WP_REST_Request $request ) {
        if (!class_exists('LinkHub\Analytics\Database')) {
            return new \WP_Error('db_error', 'Analytics database not initialized', ['status' => 500]);
        }
        
        global $wpdb;
        $table = Database::get_table_name();

        $link_ids = $request->get_param('link_ids');
        $filter_ids = [];
        if (!empty($link_ids)) {
            $filter_ids = array_map('intval', explode(',', $link_ids));
        }
        
        // Base Query Condition
        $where_sql = "WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        if (!empty($filter_ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($filter_ids), '%d'));
            $where_sql .= $wpdb->prepare(" AND link_id IN ($ids_placeholder)", ...$filter_ids);
        }

        // Total clicks (All time - filtered if needed)
        $total_where = "1=1";
        if (!empty($filter_ids)) {
            $total_where = $wpdb->prepare("link_id IN ($ids_placeholder)", ...$filter_ids);
        }
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $total_where");
        
        // Clicks last 30 days (Filtered)
        $last_30 = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_sql");
        
        // Generate Labels (Last 30 days)
        $labels = [];
        $date_range = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $date_range[] = $d;
            $labels[] = date('M j', strtotime("-$i days"));
        }

        $datasets = [];

        // Chart Data Query
        if (!empty($filter_ids)) {
            // Compare mode: Get data per link
            // Limit to top 5 if too many selected to prevent chaos? user asked for multiple. Let's do all selected.
            
            // Get data grouped by link_id and date
            $query = "
                SELECT link_id, DATE(clicked_at) as date, COUNT(*) as count 
                FROM $table 
                $where_sql
                GROUP BY link_id, date
                ORDER BY date ASC
            ";
            $raw_data = $wpdb->get_results($query);
            
            // Group by Link ID
            $link_data_map = [];
            foreach ($raw_data as $row) {
                $link_data_map[$row->link_id][$row->date] = (int)$row->count;
            }

            // Build Datasets
            foreach ($filter_ids as $fid) {
                $link_title = get_the_title($fid) ?: "Link #$fid";
                // Decode entities
                $link_title = html_entity_decode($link_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                $values = [];
                foreach ($date_range as $date) {
                    $values[] = isset($link_data_map[$fid][$date]) ? $link_data_map[$fid][$date] : 0;
                }

                $datasets[] = [
                    'label' => $link_title,
                    'data'  => $values,
                    'link_id' => $fid
                ];
            }

        } else {
            // Aggregate mode (All links) - ORIGINAL LOGIC
            $query = "
                SELECT DATE(clicked_at) as date, COUNT(*) as count 
                FROM $table 
                WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(clicked_at)
                ORDER BY date ASC
            ";
            $raw_chart_data = $wpdb->get_results($query);

            $data_map = [];
            foreach ($raw_chart_data as $row) {
                $data_map[$row->date] = (int)$row->count;
            }

            $values = [];
            foreach ($date_range as $date) {
                $values[] = isset($data_map[$date]) ? $data_map[$date] : 0;
            }

            $datasets[] = [
                'label' => __('Total Clicks', 'linkhub'),
                'data' => $values,
                'is_aggregate' => true
            ];
        }

        $formatted_chart_data = [
            'labels' => $labels,
            'datasets' => $datasets
        ];

        // Top links (All time) logic remains same to show global context? 
        // User might want top links WITHIN the filter? 
        // Actually top links usually implies "Top of all time". If filtering, maybe we hide it or show stats for selected.
        // Let's keep Top Links as "Global Leaderboard" for now unless user asks to filter that too.
        
        $top_links_sql = "SELECT link_id, COUNT(*) as count FROM $table WHERE $total_where GROUP BY link_id ORDER BY count DESC LIMIT 10";
        $top_links = $wpdb->get_results($top_links_sql);

        // Enrich top links with titles
        $enriched_links = [];
        foreach ($top_links as $link) {
            $title = get_the_title($link->link_id);
            if (!$title) continue; // Skip deleted links
            
            // Decode entities
            $link->title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Get URL from meta
            $link->url = get_post_meta($link->link_id, LinkPostType::META_URL, true);

            // Get Image
            $image_id = get_post_meta($link->link_id, LinkPostType::META_IMAGE, true);
            $link->image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : null;

            $enriched_links[] = $link;
        }

        return new WP_REST_Response([
            'total_clicks' => (int)$total,
            'last_30_days' => (int)$last_30,
            'chart_data' => $formatted_chart_data,
            'top_links' => $enriched_links
        ], 200);
    }
}
