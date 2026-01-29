<?php
// debug-chart.php
require_once('wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'lh_analytics';

echo "<h2>Debug Chart Data</h2>";

// 1. Check Table Exists
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
echo "Table exists: " . ($exists ? "YES ($exists)" : "NO") . "<br>";

// 2. Check Total Rows
$count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
echo "Total Rows: $count <br>";

// 3. Check Recent Rows (raw dump of last 5)
$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY clicked_at DESC LIMIT 5");
echo "<h3>Last 5 Rows:</h3><pre>" . print_r($rows, true) . "</pre>";

// 4. Run the exact query from RestController
$query = "
    SELECT DATE(clicked_at) as date, COUNT(*) as count 
    FROM $table 
    WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(clicked_at)
    ORDER BY date ASC
";

echo "<h3>Query:</h3><pre>$query</pre>";

$raw_chart_data = $wpdb->get_results($query);
echo "<h3>Raw Chart Data (DB Result):</h3><pre>" . print_r($raw_chart_data, true) . "</pre>";

// 5. Simulate PHP Loop
$labels = [];
$values = [];
$data_map = [];

// Map DB results
foreach ($raw_chart_data as $row) {
    if (is_object($row)) {
        $data_map[$row->date] = (int)$row->count;
    } else {
        $data_map[$row['date']] = (int)$row['count'];
    }
}

// Generate last 30 days
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M j', strtotime("-$i days"));
    $values[] = isset($data_map[$date]) ? $data_map[$date] : 0;
    
    // Debug specific dates
    // echo "Date: $date - Count: " . (isset($data_map[$date]) ? $data_map[$date] : 0) . "<br>";
}

echo "<h3>Final JSON (What frontend sees):</h3>";
echo json_encode(['labels' => $labels, 'values' => $values]);
