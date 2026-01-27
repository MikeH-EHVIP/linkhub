<?php
/**
 * Redirect and Click Tracking Handler
 *
 * @package LinkHub
 */

namespace LinkHub\Tracking;

use LinkHub\PostTypes\LinkPostType;

/**
 * Redirect Handler Class
 */
class RedirectHandler {
    
    /**
     * Singleton instance
     *
     * @var RedirectHandler
     */
    private static $instance = null;
    
    /**
     * Redirect endpoint
     */
    const ENDPOINT = 'go';
    
    /**
     * Cache group
     */
    const CACHE_GROUP = 'LH_redirects';
    
    /**
     * Cache expiration (12 hours)
     */
    const CACHE_EXPIRATION = 43200;
    
    /**
     * Get singleton instance
     *
     * @return RedirectHandler
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
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_redirect'], 1);
    }
    
    /**
     * Add rewrite rules for redirect endpoint
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^' . self::ENDPOINT . '/([0-9]+)/?$',
            'index.php?LH_redirect=$matches[1]',
            'top'
        );
        
        add_rewrite_tag('%LH_redirect%', '([0-9]+)');
    }
    
    /**
     * Handle redirect request
     */
    public function handle_redirect() {
        $link_id = get_query_var('LH_redirect');
        
        if (!$link_id) {
            return;
        }
        
        $link_id = absint($link_id);
        
        // Get redirect URL from cache or database
        $url = $this->get_redirect_url($link_id);
        
        if (!$url) {
            wp_die(
                __('Link not found or invalid.', 'linkhub'),
                __('Link Not Found', 'linkhub'),
                ['response' => 404]
            );
        }
        
        // Track the click
        $this->track_click($link_id);
        
        // Perform redirect
        wp_redirect($url, 301);
        exit;
    }
    
    /**
     * Get redirect URL with caching
     *
     * @param int $link_id Link post ID
     * @return string|false
     */
    private function get_redirect_url($link_id) {
        // Try to get from cache
        $cache_key = 'url_' . $link_id;
        $cached_url = wp_cache_get($cache_key, self::CACHE_GROUP);
        
        if (false !== $cached_url) {
            return $cached_url;
        }
        
        // Verify post exists and is correct type
        $post = get_post($link_id);
        if (!$post || $post->post_type !== LinkPostType::POST_TYPE) {
            return false;
        }
        
        // Get URL from meta
        $url = LinkPostType::get_url($link_id);
        
        if (!$url) {
            return false;
        }
        
        // Cache the URL
        wp_cache_set($cache_key, $url, self::CACHE_GROUP, self::CACHE_EXPIRATION);
        
        return $url;
    }
    
    /**
     * Track click with optimized caching
     *
     * @param int $link_id Link post ID
     */
    private function track_click($link_id) {
        // Get current count from cache
        $count_cache_key = 'count_' . $link_id;
        $current_count = wp_cache_get($count_cache_key, self::CACHE_GROUP);
        
        if (false === $current_count) {
            $current_count = LinkPostType::get_click_count($link_id);
        }
        
        // Increment count
        $new_count = $current_count + 1;
        
        // Update database
        update_post_meta($link_id, LinkPostType::META_CLICK_COUNT, $new_count);
        update_post_meta($link_id, LinkPostType::META_LAST_CLICKED, current_time('mysql'));

        // Log to analytics table
        global $wpdb;
        if (class_exists('LinkHub\Analytics\Database')) {
            $table_name = \LinkHub\Analytics\Database::get_table_name();
            
            $ip = $this->get_client_ip();
            $ip_hash = hash('sha256', $ip); // Anonymize
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
            $referrer = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 255) : '';
            
            $wpdb->insert(
                $table_name,
                [
                    'link_id' => $link_id,
                    'tree_id' => 0, // Future improvement: Pass tree context
                    'clicked_at' => current_time('mysql'),
                    'ip_hash' => $ip_hash,
                    'user_agent' => $user_agent,
                    'referrer' => $referrer
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s']
            );
        }
        
        // Update cache
        wp_cache_set($count_cache_key, $new_count, self::CACHE_GROUP, self::CACHE_EXPIRATION);
        
        // Fire action for extensibility
        do_action('LH_link_clicked', $link_id, $new_count);
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
    }
    
    /**
     * Get tracking URL for a link
     *
     * @param int $link_id Link post ID
     * @return string
     */
    public static function get_tracking_url($link_id) {
        return home_url(self::ENDPOINT . '/' . $link_id . '/');
    }
    
    /**
     * Invalidate cache for a link
     *
     * @param int $link_id Link post ID
     */
    public static function invalidate_cache($link_id) {
        wp_cache_delete('url_' . $link_id, self::CACHE_GROUP);
        wp_cache_delete('count_' . $link_id, self::CACHE_GROUP);
    }
}

