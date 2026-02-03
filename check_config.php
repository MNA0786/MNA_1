<?php
require_once 'index.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Config Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; 
               background: #0088cc; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Configuration Status</h1>";

// Check environment
echo "<h2>✅ Environment Status</h2>";
echo "<table>
        <tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$env_checks = [
    'BOT_TOKEN' => [
        'value' => defined('BOT_TOKEN') ? (BOT_TOKEN ? '***' . substr(BOT_TOKEN, -10) : 'Empty'),
        'status' => BOT_TOKEN ? 'success' : 'error'
    ],
    'MAIN_CHANNEL_ID' => [
        'value' => MAIN_CHANNEL_ID,
        'status' => 'success'
    ],
    'REQUEST_GROUP_ID' => [
        'value' => REQUEST_GROUP_ID,
        'status' => 'success'
    ],
    'ENABLE_TYPING_INDICATOR' => [
        'value' => ENABLE_TYPING_INDICATOR ? 'ON' : 'OFF',
        'status' => 'success'
    ],
    'ENABLE_PUBLIC_CHANNELS' => [
        'value' => ENABLE_PUBLIC_CHANNELS ? 'ON' : 'OFF',
        'status' => 'success'
    ],
    'ENABLE_PRIVATE_CHANNELS' => [
        'value' => ENABLE_PRIVATE_CHANNELS ? 'ON' : 'OFF',
        'status' => ENABLE_PRIVATE_CHANNELS ? 'warning' : 'success'
    ],
    'CSV_FORMAT' => [
        'value' => CSV_FORMAT,
        'status' => 'success'
    ]
];

foreach ($env_checks as $key => $check) {
    $status_class = $check['status'];
    $status_icon = $check['status'] == 'success' ? '✅' : 
                  ($check['status'] == 'warning' ? '⚠️' : '❌');
    
    echo "<tr>
            <td>$key</td>
            <td>{$check['value']}</td>
            <td class='$status_class'>$status_icon</td>
          </tr>";
}

echo "</table>";

// Check channels
echo "<h2>📢 Channels Status</h2>";
echo "<table>
        <tr><th>Channel</th><th>ID</th><th>Type</th><th>Status</th></tr>";

foreach ($ALL_CHANNELS as $channel) {
    $is_active = $channel['active'] ?? false;
    $status = $is_active ? '✅ Active' : '❌ Inactive';
    $type = $channel['public'] ?? true ? 'Public' : 'Private';
    
    echo "<tr>
            <td>{$channel['name']}</td>
            <td>{$channel['id']}</td>
            <td>$type</td>
            <td>$status</td>
          </tr>";
}

echo "</table>";

// Check files
echo "<h2>📁 Files Status</h2>";
echo "<table>
        <tr><th>File</th><th>Exists</th><th>Size</th><th>Writable</th></tr>";

$files_to_check = [
    CSV_FILE,
    USERS_FILE,
    STATS_FILE,
    '.env',
    'index.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) . ' bytes' : 'N/A';
    $writable = $exists ? (is_writable($file) ? '✅' : '❌') : 'N/A';
    
    echo "<tr>
            <td>$file</td>
            <td>" . ($exists ? '✅' : '❌') . "</td>
            <td>$size</td>
            <td>$writable</td>
          </tr>";
}

echo "</table>";

// CSV format check
echo "<h2>📊 CSV Format Check</h2>";
if (file_exists(CSV_FILE)) {
    $handle = fopen(CSV_FILE, 'r');
    $header = fgetcsv($handle);
    fclose($handle);
    
    $expected = explode(',', CSV_FORMAT);
    $is_correct = $header == $expected;
    
    echo "<p>Expected: <code>" . CSV_FORMAT . "</code></p>";
    echo "<p>Actual: <code>" . implode(',', $header) . "</code></p>";
    echo "<p>Status: " . ($is_correct ? '✅ Correct' : '❌ Incorrect') . "</p>";
    
    if (!$is_correct) {
        echo "<p><a href='migrate_csv.php' class='btn'>🔄 Migrate CSV Format</a></p>";
    }
} else {
    echo "<p>❌ CSV file not found</p>";
}

// Quick actions
echo "<h2>🎯 Quick Actions</h2>";
echo "<p>
        <a href='?setwebhook=1' class='btn'>🚀 Set Webhook</a>
        <a href='migrate_csv.php' class='btn'>🔄 Migrate CSV</a>
        <a href='logs.php' class='btn'>📝 View Logs</a>
        <a href='index.php' class='btn'>🏠 Home</a>
      </p>";

echo "</body></html>";
?>
