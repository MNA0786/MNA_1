<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>📝 Logs Viewer</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .log-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: white; border-radius: 5px; }
        .log-title { font-weight: bold; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 2px solid #0088cc; color: #333; }
        pre { background: #f8f9fa; padding: 15px; overflow: auto; border-radius: 3px; border: 1px solid #dee2e6; max-height: 500px; }
        .btn { display: inline-block; padding: 8px 15px; margin: 5px; 
               background: #0088cc; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .stats { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .timestamp { color: #6c757d; font-size: 0.9em; }
        .log-entry { margin: 5px 0; padding: 5px; border-left: 3px solid #0088cc; }
        .success-log { border-left-color: #28a745; background: #d4edda; }
        .error-log { border-left-color: #dc3545; background: #f8d7da; }
        .warning-log { border-left-color: #ffc107; background: #fff3cd; }
    </style>
</head>
<body>
    <h1>📝 Logs Viewer - Entertainment Tadka Bot</h1>
    
    <div class='stats'>
        <p><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>PHP Version:</strong> " . phpversion() . "</p>
        <p><strong>Memory Usage:</strong> " . round(memory_get_usage() / 1024 / 1024, 2) . " MB</p>
    </div>
    
    <div>
        <a href='check_config.php' class='btn'>🔍 Config Check</a>
        <a href='index.php' class='btn'>🏠 Home</a>
        <button onclick='location.reload()' class='btn'>🔄 Refresh</button>
        <a href='logs.php?clear=1' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to clear all logs?\")'>🗑️ Clear All Logs</a>
        <a href='logs.php?download=1' class='btn btn-success'>📥 Download Logs</a>
    </div>";

// Clear logs if requested
if (isset($_GET['clear'])) {
    $files = ['forward_logs.txt', 'request_logs.txt', 'error.log'];
    $cleared = [];
    foreach ($files as $file) {
        if (file_exists($file)) {
            file_put_contents($file, '');
            $cleared[] = $file;
        }
    }
    echo "<div class='log-section' style='background: #d4edda;'>
            <h3>✅ Logs Cleared Successfully!</h3>
            <p>Cleared files: " . implode(', ', $cleared) . "</p>
          </div>";
}

// Download logs if requested
if (isset($_GET['download'])) {
    $zip = new ZipArchive();
    $zip_file = 'logs_backup_' . date('Y-m-d_His') . '.zip';
    
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        $log_files = ['forward_logs.txt', 'request_logs.txt', 'error.log'];
        foreach ($log_files as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, $file);
            }
        }
        $zip->close();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_file . '"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);
        unlink($zip_file);
        exit;
    }
}

// Display logs
$log_files = [
    '📤 Forward Logs' => [
        'file' => 'forward_logs.txt',
        'desc' => 'Tracks all movie forwarding activities'
    ],
    '📝 Request Logs' => [
        'file' => 'request_logs.txt',
        'desc' => 'Records all movie requests'
    ],
    '❌ Error Log' => [
        'file' => 'error.log',
        'desc' => 'System errors and warnings'
    ]
];

foreach ($log_files as $title => $info) {
    echo "<div class='log-section'>
            <div class='log-title'>$title <small>(" . $info['file'] . ")</small></div>
            <p><em>" . $info['desc'] . "</em></p>";
    
    if (file_exists($info['file'])) {
        $content = file_get_contents($info['file']);
        $size = filesize($info['file']);
        $lines = $content ? substr_count($content, "\n") : 0;
        
        echo "<div class='stats'>
                <p><strong>File Size:</strong> " . format_bytes($size) . " | 
                   <strong>Lines:</strong> $lines | 
                   <strong>Last Modified:</strong> " . date('Y-m-d H:i:s', filemtime($info['file'])) . "</p>
              </div>";
        
        if (!empty(trim($content))) {
            // Parse and display logs with formatting
            $lines = explode("\n", $content);
            echo "<div style='max-height: 400px; overflow-y: auto;'>";
            foreach ($lines as $line) {
                if (trim($line)) {
                    $class = '';
                    if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                        $class = 'error-log';
                    } elseif (strpos($line, 'SUCCESS') !== false || strpos($line, '✅') !== false) {
                        $class = 'success-log';
                    } elseif (strpos($line, 'WARNING') !== false || strpos($line, '⚠️') !== false) {
                        $class = 'warning-log';
                    }
                    
                    // Extract timestamp
                    $timestamp = '';
                    if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                        $timestamp = $matches[1];
                        $line = substr($line, strlen($timestamp));
                    }
                    
                    echo "<div class='log-entry $class'>
                            <span class='timestamp'>$timestamp</span> 
                            " . htmlspecialchars($line) . "
                          </div>";
                }
            }
            echo "</div>";
        } else {
            echo "<p><em>Log file is empty</em></p>";
        }
    } else {
        echo "<p class='error-log'><em>Log file not found</em></p>";
    }
    
    // Action buttons for each log
    echo "<div style='margin-top: 10px;'>
            <a href='logs.php?clear_" . basename($info['file'], '.txt') . "=1' class='btn btn-warning' 
               onclick='return confirm(\"Clear " . $info['file'] . "?\")'>Clear This Log</a>
            <a href='logs.php?view=" . urlencode($info['file']) . "' class='btn'>View Raw</a>
          </div>";
    
    echo "</div>";
}

// View raw log file
if (isset($_GET['view'])) {
    $file = $_GET['view'];
    if (file_exists($file) && in_array($file, ['forward_logs.txt', 'request_logs.txt', 'error.log'])) {
        echo "<div class='log-section'>
                <h3>📄 Raw View: $file</h3>
                <pre>" . htmlspecialchars(file_get_contents($file)) . "</pre>
              </div>";
    }
}

// Clear individual log files
foreach (['forward_logs', 'request_logs', 'error'] as $log_type) {
    if (isset($_GET["clear_$log_type"])) {
        $file = $log_type . '.txt';
        if (file_exists($file)) {
            file_put_contents($file, '');
            echo "<script>alert('$file cleared successfully!'); window.location.href='logs.php';</script>";
        }
    }
}

// Recent activity summary
echo "<div class='log-section'>
        <div class='log-title'>📈 Recent Activity Summary</div>";
        
$recent_activity = [];
$log_files = ['forward_logs.txt', 'request_logs.txt'];
$today = date('Y-m-d');
$today_count = 0;
$week_count = 0;

foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        $content = file_get_contents($log_file);
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                $log_date = $matches[1];
                if ($log_date == $today) {
                    $today_count++;
                }
                if (strtotime($log_date) >= strtotime('-7 days')) {
                    $week_count++;
                }
            }
        }
    }
}

echo "<p><strong>Today's Activity:</strong> $today_count log entries</p>";
echo "<p><strong>Last 7 Days:</strong> $week_count log entries</p>";
echo "<p><strong>Disk Usage:</strong> " . format_bytes(disk_free_space('.')) . " free of " . format_bytes(disk_total_space('.')) . "</p>";
echo "</div>";

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
