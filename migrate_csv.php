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
        .success { color: green; background: #d4ffd4; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #ffd4d4; padding: 10px; border-radius: 5px; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; }
        .info-box { background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔄 CSV Format Migration Tool</h1>";

if (!file_exists(CSV_FILE)) {
    echo "<div class='error'>
            <h3>❌ CSV File Not Found</h3>
            <p>File: " . CSV_FILE . " does not exist.</p>
            <p>Creating new CSV file with correct format...</p>
          </div>";
    
    init_csv_file();
    
    if (file_exists(CSV_FILE)) {
        echo "<div class='success'>
                <p>✅ New CSV file created successfully!</p>
                <p>Format: <code>" . CSV_FORMAT . "</code></p>
              </div>";
    }
}

// Read current CSV
$old_data = [];
$handle = fopen(CSV_FILE, 'r');
if ($handle !== FALSE) {
    $header = fgetcsv($handle);
    $old_format = implode(',', $header);
    
    echo "<div class='info-box'>
            <h3>📊 Current Status</h3>
            <p><strong>Current Format:</strong> <code>$old_format</code></p>
            <p><strong>Target Format:</strong> <code>" . CSV_FORMAT . "</code></p>
          </div>";
    
    while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) >= 2) {
            $movie_name = $row[0] ?? '';
            $message_id = $row[1] ?? '';
            $channel_id = $row[2] ?? MAIN_CHANNEL_ID;
            
            // Clean up data
            $movie_name = trim($movie_name);
            $message_id = trim($message_id);
            $channel_id = trim($channel_id);
            
            if (!empty($movie_name)) {
                $old_data[] = [
                    'movie_name' => $movie_name,
                    'message_id' => $message_id,
                    'channel_id' => $channel_id,
                    'valid' => !empty($movie_name) && !empty($message_id)
                ];
            }
        }
    }
    fclose($handle);
}

$total_movies = count($old_data);
$valid_movies = count(array_filter($old_data, function($item) {
    return $item['valid'];
}));
$invalid_movies = $total_movies - $valid_movies;

echo "<div class='info-box'>
        <h3>📈 Database Statistics</h3>
        <p><strong>Total Movies Found:</strong> $total_movies</p>
        <p><strong>Valid Movies:</strong> $valid_movies</p>
        <p><strong>Invalid Entries:</strong> $invalid_movies</p>
      </div>";

// Show sample data
if ($total_movies > 0) {
    echo "<h3>🎬 Sample Data (First 10 entries)</h3>";
    echo "<table>
            <tr>
                <th>#</th>
                <th>Movie Name</th>
                <th>Message ID</th>
                <th>Channel ID</th>
                <th>Status</th>
            </tr>";
    
    for ($i = 0; $i < min(10, $total_movies); $i++) {
        $movie = $old_data[$i];
        $status = $movie['valid'] ? '✅ Valid' : '❌ Invalid';
        $channel_name = get_channel_display_name($movie['channel_id']);
        
        echo "<tr>
                <td>" . ($i + 1) . "</td>
                <td>" . htmlspecialchars(substr($movie['movie_name'], 0, 50)) . "</td>
                <td>{$movie['message_id']}</td>
                <td>$channel_name</td>
                <td>$status</td>
              </tr>";
    }
    
    if ($total_movies > 10) {
        echo "<tr><td colspan='5' style='text-align: center;'>... and " . ($total_movies - 10) . " more movies</td></tr>";
    }
    
    echo "</table>";
}

// Check if migration is needed
$target_format = CSV_FORMAT;
if ($old_format == $target_format && $invalid_movies == 0) {
    echo "<div class='success'>
            <h3>✅ No Migration Needed!</h3>
            <p>CSV is already in correct format and all data is valid.</p>
            <p><a href='check_config.php' class='btn'>Back to Config</a></p>
          </div>";
    exit;
}

// Show issues if any
if ($invalid_movies > 0) {
    echo "<div class='warning'>
            <h3>⚠️ Data Issues Found</h3>
            <p>Found $invalid_movies invalid entries (missing movie name or message ID).</p>
            <p>These will be skipped during migration.</p>
          </div>";
}

// Create backup
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0777, true);
}

$backup_file = BACKUP_DIR . 'movies_backup_' . date('Y-m-d_His') . '.csv';
if (file_exists(CSV_FILE)) {
    if (copy(CSV_FILE, $backup_file)) {
        echo "<div class='success'>
                <p>✅ Backup created: <code>$backup_file</code></p>
              </div>";
    } else {
        echo "<div class='error'>
                <p>❌ Failed to create backup!</p>
              </div>";
    }
}

// Start migration if requested
if (isset($_GET['migrate'])) {
    echo "<div class='info-box'>
            <h3>🔄 Starting Migration...</h3>
          </div>";
    
    // Filter valid movies
    $valid_data = array_filter($old_data, function($item) {
        return $item['valid'];
    });
    
    // Write new CSV
    $handle = fopen(CSV_FILE, 'w');
    fputcsv($handle, explode(',', CSV_FORMAT));
    
    $migrated = 0;
    $skipped = 0;
    
    foreach ($valid_data as $movie) {
        fputcsv($handle, [
            $movie['movie_name'],
            $movie['message_id'],
            $movie['channel_id']
        ]);
        $migrated++;
    }
    fclose($handle);
    
    // Clear cache
    global $movie_cache;
    $movie_cache = [];
    
    echo "<div class='success'>
            <h3>✅ Migration Completed Successfully!</h3>
            <p><strong>Migrated:</strong> $migrated movies</p>
            <p><strong>Skipped:</strong> $invalid_movies invalid entries</p>
            <p><strong>New Format:</strong> <code>" . CSV_FORMAT . "</code></p>
          </div>";
    
    // Verify
    $handle = fopen(CSV_FILE, 'r');
    $new_header = fgetcsv($handle);
    fclose($handle);
    
    if (implode(',', $new_header) == CSV_FORMAT) {
        echo "<div class='success'>
                <p>✅ Verification passed! CSV format is correct.</p>
              </div>";
    } else {
        echo "<div class='error'>
                <p>❌ Verification failed! Something went wrong.</p>
              </div>";
    }
    
    // Show migration report
    echo "<h3>📋 Migration Report</h3>";
    echo "<table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Total Movies Processed</td>
                <td>$total_movies</td>
            </tr>
            <tr>
                <td>Successfully Migrated</td>
                <td>$migrated</td>
            </tr>
            <tr>
                <td>Skipped (Invalid)</td>
                <td>$invalid_movies</td>
            </tr>
            <tr>
                <td>Backup File</td>
                <td>$backup_file</td>
            </tr>
            <tr>
                <td>New File Size</td>
                <td>" . format_bytes(filesize(CSV_FILE)) . "</td>
            </tr>
          </table>";
    
} else {
    // Show migration confirmation
    echo "<div class='warning'>
            <h3>⚠️ Migration Required</h3>";
    
    if ($old_format != $target_format) {
        echo "<p>❌ Format mismatch detected!</p>";
        echo "<p>Current: <code>$old_format</code></p>";
        echo "<p>Target: <code>$target_format</code></p>";
    }
    
    if ($invalid_movies > 0) {
        echo "<p>⚠️ Found $invalid_movies invalid entries that will be skipped.</p>";
    }
    
    echo "<p><strong>Total movies to migrate:</strong> $valid_movies</p>
          <p><strong>Backup will be created automatically.</strong></p>
          </div>";
    
    echo "<div style='margin: 20px 0;'>
            <a href='migrate_csv.php?migrate=1' class='btn' 
               onclick='return confirm(\"Are you sure you want to migrate $valid_movies movies?\\n\\nA backup will be created at:\\n$backup_file\")'>
               🚀 Start Migration
            </a>
            <a href='check_config.php' class='btn'>🔍 Check Config</a>
            <a href='index.php' class='btn'>🏠 Home</a>
          </div>";
}

// Data cleanup suggestions
if ($invalid_movies > 0) {
    echo "<div class='warning'>
            <h3>🧹 Data Cleanup Suggestions</h3>
            <p>The following issues were found in your CSV file:</p>
            <ul>";
    
    $issues = [];
    foreach ($old_data as $index => $movie) {
        if (!$movie['valid']) {
            $issue = "Row " . ($index + 2) . ": ";
            if (empty($movie['movie_name'])) {
                $issue .= "Missing movie name";
            }
            if (empty($movie['message_id'])) {
                $issue .= empty($movie['movie_name']) ? " and " : "";
                $issue .= "Missing message ID";
            }
            $issues[] = $issue;
        }
    }
    
    foreach (array_slice($issues, 0, 5) as $issue) {
        echo "<li>$issue</li>";
    }
    
    if (count($issues) > 5) {
        echo "<li>... and " . (count($issues) - 5) . " more issues</li>";
    }
    
    echo "</ul>
          <p>These rows will be skipped during migration.</p>
          </div>";
}

echo "<p style='margin-top: 30px;'>
        <a href='check_config.php' class='btn'>🔍 Check Config</a>
        <a href='index.php' class='btn'>🏠 Home</a>
        <a href='logs.php' class='btn'>📝 View Logs</a>
      </p>";

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
