<?php
/**
 * Analytics Database Handler
 *
 * @package LinkHub
 */

namespace LinkHub\Analytics;

/**
 * Database Class
 * 
 * Handles database table creation and updates for analytics
 */
class Database {

    /**
     * Table name without prefix
     */
    const TABLE_NAME = 'lh_analytics';

    /**
     * Get full table name with prefix
     * 
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Create or update the database table
     */
    public static function create_table() {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            link_id bigint(20) NOT NULL,
            tree_id bigint(20) NOT NULL DEFAULT 0,
            clicked_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            ip_hash varchar(64) DEFAULT '' NOT NULL,
            user_agent text DEFAULT '' NOT NULL,
            referrer text DEFAULT '' NOT NULL,
            PRIMARY KEY  (id),
            KEY link_id (link_id),
            KEY tree_id (tree_id),
            KEY clicked_at (clicked_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add database version option for future comparisons
        add_option('lh_db_version', '1.0.0');
    }
}
