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
        .success { color: green; background: #d4ffd4; }
        .error { color: red; background: #ffd4d4; }
        .warning { color: orange; background: #fff3cd; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; 
               background: #0088cc; color: white; text-decoration: none; border-radius: 5px; }
        .info-box { padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Entertainment Tadka Bot - Configuration Status</h1>";

// Check environment
echo "<div class='info-box' style='background: #e7f3ff;'>
        <h3>🤖 Bot Info</h3>
        <p><strong>Version:</strong> 3.2.0</p>
        <p><strong>Features:</strong> Smart Search, Did You Mean?, Multi-Channel, Request System</p>
        <p><strong>Last Updated:</strong> " . date('Y-m-d H:i:s') . "</p>
      </div>";

echo "<h2>✅ Environment Status</h2>";
echo "<table>
        <tr><th>Setting</th><th>Value</th><th>Status</th><th>Description</th></tr>";

$env_checks = [
    'BOT_TOKEN' => [
        'value' => defined('BOT_TOKEN') ? (BOT_TOKEN ? '***' . substr(BOT_TOKEN, -10) : 'Empty'),
        'status' => BOT_TOKEN ? 'success' : 'error',
        'desc' => 'Telegram Bot Token from @BotFather'
    ],
    'MAIN_CHANNEL_ID' => [
        'value' => MAIN_CHANNEL_ID,
        'status' => 'success',
        'desc' => 'Main channel for movies'
    ],
    'REQUEST_GROUP_ID' => [
        'value' => REQUEST_GROUP_ID,
        'status' => 'success',
        'desc' => 'Group for movie requests'
    ],
    'ENABLE_TYPING_INDICATOR' => [
        'value' => ENABLE_TYPING_INDICATOR ? '✅ ON' : '❌ OFF',
        'status' => 'success',
        'desc' => 'Show typing indicator'
    ],
    'ENABLE_PUBLIC_CHANNELS' => [
        'value' => ENABLE_PUBLIC_CHANNELS ? '✅ ON' : '❌ OFF',
        'status' => 'success',
        'desc' => 'Enable public channels'
    ],
    'ENABLE_PRIVATE_CHANNELS' => [
        'value' => ENABLE_PRIVATE_CHANNELS ? '✅ ON' : '❌ OFF',
        'status' => ENABLE_PRIVATE_CHANNELS ? 'warning' : 'success',
        'desc' => 'Enable private channels'
    ],
    'HIDE_PRIVATE_CHANNELS' => [
        'value' => HIDE_PRIVATE_CHANNELS ? '✅ ON' : '❌ OFF',
        'status' => 'success',
        'desc' => 'Hide private channel names'
    ],
    'CSV_FORMAT' => [
        'value' => CSV_FORMAT,
        'status' => 'success',
        'desc' => 'CSV database format'
    ],
    'DID_YOU_MEAN_FEATURE' => [
        'value' => '✅ ACTIVE',
        'status' => 'success',
        'desc' => 'Smart suggestion feature'
    ]
];

foreach ($env_checks as $key => $check) {
    $status_class = $check['status'];
    $status_icon = $check['status'] == 'success' ? '✅' : 
                  ($check['status'] == 'warning' ? '⚠️' : '❌');
    
    echo "<tr>
            <td><strong>$key</strong></td>
            <td>{$check['value']}</td>
            <td class='$status_class'>$status_icon</td>
            <td>{$check['desc']}</td>
          </tr>";
}

echo "</table>";

// Check channels
echo "<h2>📢 Channels Status</h2>";
echo "<table>
        <tr><th>Channel</th><th>ID</th><th>Type</th><th>Status</th><th>Movies</th></tr>";

foreach ($ALL_CHANNELS as $channel) {
    $is_active = $channel['active'] ?? false;
    $status = $is_active ? '✅ Active' : '❌ Inactive';
    $type = $channel['public'] ?? true ? 'Public' : 'Private';
    
    // Count movies in this channel
    $movie_count = 0;
    if (file_exists(CSV_FILE)) {
        $handle = fopen(CSV_FILE, 'r');
        if ($handle !== FALSE) {
            fgetcsv($handle); // Skip header
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (isset($row[2]) && $row[2] == $channel['id']) {
                    $movie_count++;
                }
            }
            fclose($handle);
        }
    }
    
    echo "<tr>
            <td>{$channel['name']}</td>
            <td>{$channel['id']}</td>
            <td>$type</td>
            <td>$status</td>
            <td>$movie_count movies</td>
          </tr>";
}

echo "</table>";

// Check files
echo "<h2>📁 Files Status</h2>";
echo "<table>
        <tr><th>File</th><th>Exists</th><th>Size</th><th>Writable</th><th>Last Modified</th></tr>";

$files_to_check = [
    CSV_FILE => 'Movie Database',
    USERS_FILE => 'User Statistics',
    STATS_FILE => 'Bot Statistics',
    REQUESTS_FILE => 'Request Database',
    '.env' => 'Environment Config',
    'index.php' => 'Main Bot Script'
];

foreach ($files_to_check as $file => $description) {
    $exists = file_exists($file);
    $size = $exists ? format_bytes(filesize($file)) : 'N/A';
    $writable = $exists ? (is_writable($file) ? '✅' : '❌') : 'N/A';
    $modified = $exists ? date('Y-m-d H:i:s', filemtime($file)) : 'N/A';
    
    echo "<tr>
            <td>$file<br><small>$description</small></td>
            <td>" . ($exists ? '✅' : '❌') . "</td>
            <td>$size</td>
            <td>$writable</td>
            <td>$modified</td>
          </tr>";
}

echo "</table>";

// CSV format check
echo "<h2>📊 CSV Database Check</h2>";
if (file_exists(CSV_FILE)) {
    $handle = fopen(CSV_FILE, 'r');
    $header = fgetcsv($handle);
    fclose($handle);
    
    $expected = explode(',', CSV_FORMAT);
    $is_correct = $header == $expected;
    
    // Count movies
    $total_movies = 0;
    $handle = fopen(CSV_FILE, 'r');
    fgetcsv($handle); // Skip header
    while (fgetcsv($handle) !== FALSE) {
        $total_movies++;
    }
    fclose($handle);
    
    echo "<div class='info-box' style='background: " . ($is_correct ? '#d4ffd4' : '#ffd4d4') . ";'>
            <p><strong>Format:</strong> <code>" . CSV_FORMAT . "</code></p>
            <p><strong>Actual:</strong> <code>" . implode(',', $header) . "</code></p>
            <p><strong>Status:</strong> " . ($is_correct ? '✅ Correct' : '❌ Incorrect') . "</p>
            <p><strong>Total Movies:</strong> $total_movies</p>
          </div>";
    
    if (!$is_correct) {
        echo "<p><a href='migrate_csv.php' class='btn'>🔄 Migrate CSV Format</a></p>";
    }
} else {
    echo "<p class='error'>❌ CSV file not found</p>";
}

// Bot features check
echo "<h2>🚀 Bot Features Status</h2>";
echo "<table>
        <tr><th>Feature</th><th>Status</th><th>Description</th></tr>";

$features = [
    'Smart Search' => ['✅ Active', 'Fuzzy matching & similarity search'],
    'Did You Mean?' => ['✅ Active', 'Spelling correction suggestions'],
    'Multi-Channel' => ['✅ Active', 'Support for multiple channels'],
    'Request System' => ['✅ Active', 'Movie request tracking'],
    'User Analytics' => ['✅ Active', 'User statistics & tracking'],
    'Auto-Backup' => ['✅ Active', 'Daily CSV backups'],
    'Typing Indicator' => [ENABLE_TYPING_INDICATOR ? '✅ ON' : '❌ OFF', 'User experience'],
    'Private Channels' => [ENABLE_PRIVATE_CHANNELS ? '✅ ENABLED' : '❌ DISABLED', 'Hidden channels support']
];

foreach ($features as $feature => $data) {
    echo "<tr>
            <td><strong>$feature</strong></td>
            <td>$data[0]</td>
            <td>$data[1]</td>
          </tr>";
}

echo "</table>";

// Quick actions
echo "<h2>🎯 Quick Actions</h2>";
echo "<p>
        <a href='?setwebhook=1' class='btn'>🚀 Set Webhook</a>
        <a href='migrate_csv.php' class='btn'>🔄 Migrate CSV</a>
        <a href='logs.php' class='btn'>📝 View Logs</a>
        <a href='index.php' class='btn'>🏠 Home</a>
        <a href='index.php?test=1' class='btn'>🧪 Test Bot</a>
      </p>";

// Test bot functionality
if (isset($_GET['test'])) {
    echo "<div class='info-box' style='background: #fff3cd;'>
            <h3>🧪 Bot Test Results</h3>";
    
    // Test CSV loading
    $movies = load_movies_from_csv();
    echo "<p>📊 <strong>Movies Loaded:</strong> " . count($movies) . "</p>";
    
    // Test smart search
    $test_query = "kgf";
    $results = smart_search($test_query);
    echo "<p>🔍 <strong>Smart Search Test ('$test_query'):</strong> " . count($results) . " matches</p>";
    
    // Test did you mean
    $suggestion = did_you_mean_suggestion("kfg");
    if ($suggestion) {
        echo "<p>💡 <strong>Did You Mean Test ('kfg'):</strong> Suggests '" . $suggestion['suggestion'] . "' (" . round($suggestion['score']) . "%)</p>";
    }
    
    echo "</div>";
}

echo "</body></html>";

// Helper function
function format_bytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
?>
