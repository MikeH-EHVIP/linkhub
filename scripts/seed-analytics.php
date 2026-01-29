<?php
/**
 * Developer Script: Seed Analytics Data
 * Usage: Place in WordPress root and run with PHP
 */

// Load WordPress
if (file_exists('wp-load.php')) {
    require_once('wp-load.php');
} else {
    die("Error: Could not find wp-load.php. Please run this script from the WordPress root directory.\n");
}

global $wpdb;
$table_name = $wpdb->prefix . 'lh_analytics';

// Check if table exists
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    die("Error: Analytics table '$table_name' does not exist.\n");
}

// Get all Links
$links = get_posts([
    'post_type' => 'lh_link',
    'numberposts' => -1,
    'fields' => 'ids',
    'post_status' => 'publish'
]);

if (empty($links)) {
    die("Error: No LH_Link posts found. Create some links in the admin first.\n");
}

echo "Found " . count($links) . " links. Generating fake traffic...\n";

// Configuration
$total_clicks = 200;
$days_history = 30;

$success_count = 0;

for ($i = 0; $i < $total_clicks; $i++) {
    // Pick a random link (some links more popular than others)
    // 80% chance to pick from top 20% of links
    if (rand(1, 100) <= 80 && count($links) > 1) {
        $link_index = rand(0, ceil(count($links) * 0.2));
        $link_id = $links[$link_index];
    } else {
        $link_id = $links[array_rand($links)];
    }

    // Random Date Distribution (Linear-ish, maybe slightly more recent)
    $days_ago = rand(0, $days_history);
    $hours = rand(0, 23);
    $minutes = rand(0, 59);
    $seconds = rand(0, 59);
    
    $date = date('Y-m-d H:i:s', strtotime("-$days_ago days $hours:$minutes:$seconds"));

    // Fake Data
    $ip = long2ip(rand(0, "4294967295"));
    $ua_list = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) viewBox/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36'
    ];
    $ua = $ua_list[array_rand($ua_list)];

    // Insert
    $result = $wpdb->insert(
        $table_name,
        [
            'link_id' => $link_id,
            'tree_id' => 0,
            'clicked_at' => $date,
            'ip_hash' => hash('sha256', $ip),
            'user_agent' => $ua,
            'referrer' => 'http://t.co/fake'
        ],
        ['%d', '%d', '%s', '%s', '%s', '%s']
    );

    // Update Post Meta (keeping it synced with the redirect handler logic)
    $current_count = (int) get_post_meta($link_id, '_lh_click_count', true);
    update_post_meta($link_id, '_lh_click_count', $current_count + 1);
    update_post_meta($link_id, '_lh_last_clicked', $date);

    if ($result) $success_count++;
}

echo "Success! Generated $success_count fake clicks across " . count($links) . " links.\n";
