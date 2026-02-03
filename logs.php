<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>📝 Logs Viewer</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .log-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .log-title { font-weight: bold; margin-bottom: 10px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .btn { display: inline-block; padding: 8px 15px; margin: 5px; 
               background: #0088cc; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>📝 Logs Viewer</h1>
    
    <div>
        <a href='check_config.php' class='btn'>🔍 Config</a>
        <a href='index.php' class='btn'>🏠 Home</a>
        <a href='logs.php?clear=1' class='btn'>🗑️ Clear Logs</a>
    </div>";

// Clear logs if requested
if (isset($_GET['clear'])) {
    $files = ['forward_logs.txt', 'request_logs.txt', 'error.log'];
    foreach ($files as $file) {
        if (file_exists($file)) {
            file_put_contents($file, '');
        }
    }
    echo "<p style='color: green;'>✅ Logs cleared!</p>";
}

// Display logs
$log_files = [
    'Forward Logs' => 'forward_logs.txt',
    'Request Logs' => 'request_logs.txt',
    'Error Log' => 'error.log'
];

foreach ($log_files as $title => $file) {
    echo "<div class='log-section'>
            <div class='log-title'>$title ($file)</div>";
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (!empty(trim($content))) {
            echo "<pre>" . htmlspecialchars($content) . "</pre>";
        } else {
            echo "<p><em>Log file is empty</em></p>";
        }
    } else {
        echo "<p><em>Log file not found</em></p>";
    }
    
    echo "</div>";
}

echo "</body></html>";
?>
