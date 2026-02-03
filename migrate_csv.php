<?php
require_once 'index.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔄 CSV Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px; 
               background: #0088cc; color: white; text-decoration: none; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>🔄 CSV Format Migration</h1>";

if (!file_exists(CSV_FILE)) {
    echo "<p class='error'>❌ CSV file not found: " . CSV_FILE . "</p>";
    echo "<p><a href='check_config.php' class='btn'>Back to Config</a></p>";
    exit;
}

// Read current CSV
$old_data = [];
$handle = fopen(CSV_FILE, 'r');
if ($handle !== FALSE) {
    $header = fgetcsv($handle);
    $old_format = implode(',', $header);
    
    echo "<p>Current format: <code>$old_format</code></p>";
    echo "<p>Target format: <code>" . CSV_FORMAT . "</code></p>";
    
    while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) >= 2) {
            $old_data[] = [
                'movie_name' => $row[0],
                'message_id' => $row[1],
                'channel_id' => isset($row[2]) ? $row[2] : MAIN_CHANNEL_ID
            ];
        }
    }
    fclose($handle);
}

$total_movies = count($old_data);
echo "<p>Found $total_movies movies in current format</p>";

// Check if migration is needed
$target_format = CSV_FORMAT;
if ($old_format == $target_format) {
    echo "<p class='success'>✅ CSV is already in correct format!</p>";
    echo "<p><a href='check_config.php' class='btn'>Back to Config</a></p>";
    exit;
}

// Create backup
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0777, true);
}

$backup_file = BACKUP_DIR . 'movies_backup_' . date('Y-m-d_His') . '.csv';
if (copy(CSV_FILE, $backup_file)) {
    echo "<p>✅ Backup created: $backup_file</p>";
} else {
    echo "<p class='error'>❌ Failed to create backup</p>";
}

// Write new CSV
$handle = fopen(CSV_FILE, 'w');
fputcsv($handle, explode(',', CSV_FORMAT));

$migrated = 0;
foreach ($old_data as $movie) {
    fputcsv($handle, [
        $movie['movie_name'],
        $movie['message_id'],
        $movie['channel_id']
    ]);
    $migrated++;
}
fclose($handle);

echo "<p class='success'>✅ Successfully migrated $migrated movies to new format!</p>";
echo "<p>New format: <code>" . CSV_FORMAT . "</code></p>";

// Verify
$handle = fopen(CSV_FILE, 'r');
$new_header = fgetcsv($handle);
fclose($handle);

if (implode(',', $new_header) == CSV_FORMAT) {
    echo "<p class='success'>✅ Verification passed! CSV format is correct.</p>";
} else {
    echo "<p class='error'>❌ Verification failed!</p>";
}

echo "<p>
        <a href='check_config.php' class='btn'>🔍 Check Config</a>
        <a href='index.php' class='btn'>🏠 Home</a>
      </p>";

echo "</body></html>";
?>
