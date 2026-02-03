<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==================== ENVIRONMENT CONFIG LOADER ====================
function loadEnv($filePath = '.env') {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
    return true;
}

// Load environment
loadEnv();

// ==================== CONFIG FROM ENVIRONMENT ====================
// Telegram Bot Configuration
define('BOT_TOKEN', $_ENV['BOT_TOKEN'] ?? '');
define('BOT_USERNAME', $_ENV['BOT_USERNAME'] ?? '@EntertainmentTadkaBot');
define('OWNER_ID', (int) ($_ENV['OWNER_ID'] ?? '1080317415'));

// Channels Configuration
define('MAIN_CHANNEL_ID', $_ENV['MAIN_CHANNEL_ID'] ?? '-1003181705395');
define('THEATER_CHANNEL_ID', $_ENV['THEATER_CHANNEL_ID'] ?? '-1002831605258');
define('BACKUP_CHANNEL_ID', $_ENV['BACKUP_CHANNEL_ID'] ?? '-1002964109368');
define('REQUEST_GROUP_ID', $_ENV['REQUEST_GROUP_ID'] ?? '-1003083386043');

// Private Channels (will be OFF as per requirement)
define('PRIVATE_CHANNEL_1', $_ENV['PRIVATE_CHANNEL_1'] ?? '-1003251791991');
define('PRIVATE_CHANNEL_2', $_ENV['PRIVATE_CHANNEL_2'] ?? '-1002337293281');
define('PRIVATE_CHANNEL_3', $_ENV['PRIVATE_CHANNEL_3'] ?? '-1003614546520');

// Bot Settings
define('CSV_FILE', $_ENV['CSV_FILE'] ?? 'movies.csv');
define('CSV_FORMAT', 'movie_name,message_id,channel_id');
define('USERS_FILE', $_ENV['USERS_FILE'] ?? 'users.json');
define('REQUESTS_FILE', $_ENV['REQUESTS_FILE'] ?? 'requests.json');
define('STATS_FILE', $_ENV['STATS_FILE'] ?? 'bot_stats.json');
define('BACKUP_DIR', $_ENV['BACKUP_DIR'] ?? 'backups/');
define('CACHE_EXPIRY', (int) ($_ENV['CACHE_EXPIRY'] ?? '300'));
define('ITEMS_PER_PAGE', (int) ($_ENV['ITEMS_PER_PAGE'] ?? '5'));
define('ENABLE_TYPING_INDICATOR', filter_var($_ENV['ENABLE_TYPING_INDICATOR'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('ENABLE_PUBLIC_CHANNELS', filter_var($_ENV['ENABLE_PUBLIC_CHANNELS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('ENABLE_PRIVATE_CHANNELS', filter_var($_ENV['ENABLE_PRIVATE_CHANNELS'] ?? 'false', FILTER_VALIDATE_BOOLEAN));
define('LOG_FORWARDS', filter_var($_ENV['LOG_FORWARDS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

// Maintenance
$MAINTENANCE_MODE = filter_var($_ENV['MAINTENANCE_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

// ==================== CHANNELS CONFIGURATION ====================
$PUBLIC_CHANNELS = [];
$PRIVATE_CHANNELS = [];

// Only add public channels if enabled
if (ENABLE_PUBLIC_CHANNELS) {
    $PUBLIC_CHANNELS = [
        [
            'id' => MAIN_CHANNEL_ID,
            'name' => '@EntertainmentTadka786',
            'type' => 'main',
            'active' => true
        ],
        [
            'id' => THEATER_CHANNEL_ID,
            'name' => '@threater_print_movies',
            'type' => 'theater',
            'active' => true
        ],
        [
            'id' => BACKUP_CHANNEL_ID,
            'name' => '@ETBackup',
            'type' => 'backup',
            'active' => true
        ]
    ];
}

// Only add private channels if enabled (OFF by default)
if (ENABLE_PRIVATE_CHANNELS) {
    $PRIVATE_CHANNELS = [
        [
            'id' => PRIVATE_CHANNEL_1,
            'name' => 'Private Channel 1',
            'type' => 'private_1',
            'active' => true
        ],
        [
            'id' => PRIVATE_CHANNEL_2,
            'name' => 'Private Channel 2',
            'type' => 'private_2',
            'active' => true
        ],
        [
            'id' => PRIVATE_CHANNEL_3,
            'name' => 'Private Channel 3',
            'type' => 'private_3',
            'active' => true
        ]
    ];
}

// All active channels
$ALL_CHANNELS = array_merge($PUBLIC_CHANNELS, $PRIVATE_CHANNELS);
$ACTIVE_CHANNELS = array_filter($ALL_CHANNELS, function($ch) {
    return $ch['active'] === true;
});

// ==================== TYPING INDICATOR ====================
function sendTypingAction($chat_id) {
    if (!ENABLE_TYPING_INDICATOR) {
        return;
    }
    
    $result = apiRequest('sendChatAction', [
        'chat_id' => $chat_id,
        'action' => 'typing'
    ]);
    
    return $result;
}

// ==================== TELEGRAM API FUNCTIONS ====================
function apiRequest($method, $params = array(), $is_multipart = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        if ($res === false) {
            error_log("CURL ERROR: " . curl_error($ch));
        }
        curl_close($ch);
        return $res;
    } else {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query($params),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
            )
        );
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            error_log("apiRequest failed for method $method");
        }
        return $result;
    }
}

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'disable_web_page_preview' => true
    ];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    $result = apiRequest('sendMessage', $data);
    return json_decode($result, true);
}

function copyMessage($chat_id, $from_chat_id, $message_id) {
    $result = apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
    return json_decode($result, true);
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    $result = apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
    return $result;
}

function answerCallbackQuery($callback_query_id, $text = null) {
    $data = ['callback_query_id' => $callback_query_id];
    if ($text) $data['text'] = $text;
    apiRequest('answerCallbackQuery', $data);
}

function editMessage($chat_id, $message_obj, $new_text, $reply_markup = null) {
    if (is_array($message_obj) && isset($message_obj['message_id'])) {
        $data = [
            'chat_id' => $chat_id,
            'message_id' => $message_obj['message_id'],
            'text' => $new_text,
            'parse_mode' => 'HTML'
        ];
        if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
        $result = apiRequest('editMessageText', $data);
        return json_decode($result, true);
    }
    return false;
}

function deleteMessage($chat_id, $message_id) {
    $result = apiRequest('deleteMessage', [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ]);
    return json_decode($result, true);
}

// ==================== CSV FUNCTIONS (LOCKED FORMAT) ====================
function init_csv_file() {
    if (!file_exists(CSV_FILE)) {
        $header = explode(',', CSV_FORMAT);
        $handle = fopen(CSV_FILE, 'w');
        fputcsv($handle, $header);
        fclose($handle);
        @chmod(CSV_FILE, 0666);
        return true;
    }
    return false;
}

// Initialize CSV
init_csv_file();

function load_and_clean_csv($filename = CSV_FILE) {
    global $movie_messages;
    
    if (!file_exists($filename)) {
        init_csv_file();
        return [];
    }

    $data = [];
    $handle = fopen($filename, "r");
    if ($handle !== FALSE) {
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) >= 2) {
                $movie_name = trim($row[0]);
                $message_id = isset($row[1]) ? trim($row[1]) : '';
                $channel_id = isset($row[2]) ? trim($row[2]) : MAIN_CHANNEL_ID;
                
                if (!empty($movie_name)) {
                    $entry = [
                        'movie_name' => $movie_name,
                        'message_id' => is_numeric($message_id) ? intval($message_id) : null,
                        'message_id_raw' => $message_id,
                        'channel_id' => $channel_id
                    ];
                    
                    $data[] = $entry;
                    
                    // Index by movie name
                    $movie_key = strtolower($movie_name);
                    if (!isset($movie_messages[$movie_key])) {
                        $movie_messages[$movie_key] = [];
                    }
                    $movie_messages[$movie_key][] = $entry;
                }
            }
        }
        fclose($handle);
    }
    
    return $data;
}

function get_cached_movies() {
    global $movie_cache;
    if (!empty($movie_cache) && (time() - $movie_cache['timestamp']) < CACHE_EXPIRY) {
        return $movie_cache['data'];
    }
    $movie_cache = [
        'data' => load_and_clean_csv(),
        'timestamp' => time()
    ];
    return $movie_cache['data'];
}

function load_movies_from_csv() {
    return get_cached_movies();
}

function append_movie($movie_name, $message_id, $channel_id = null) {
    if (empty(trim($movie_name))) {
        return false;
    }
    
    if ($channel_id === null) {
        $channel_id = MAIN_CHANNEL_ID;
    }
    
    $entry = [$movie_name, $message_id, $channel_id];
    $handle = fopen(CSV_FILE, "a");
    fputcsv($handle, $entry);
    fclose($handle);
    
    // Update cache
    global $movie_messages, $movie_cache;
    $movie_key = strtolower(trim($movie_name));
    $item = [
        'movie_name' => $movie_name,
        'message_id' => is_numeric($message_id) ? intval($message_id) : null,
        'message_id_raw' => $message_id,
        'channel_id' => $channel_id
    ];
    
    if (!isset($movie_messages[$movie_key])) {
        $movie_messages[$movie_key] = [];
    }
    $movie_messages[$movie_key][] = $item;
    $movie_cache = [];
    
    return true;
}

// ==================== REQUEST SYSTEM FUNCTIONS ====================
function init_requests_file() {
    if (!file_exists(REQUESTS_FILE)) {
        $initial_data = [
            'requests' => [],
            'total_requests' => 0,
            'pending' => 0,
            'approved' => 0,
            'completed' => 0,
            'rejected' => 0,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        file_put_contents(REQUESTS_FILE, json_encode($initial_data, JSON_PRETTY_PRINT));
        @chmod(REQUESTS_FILE, 0666);
    }
}

function save_movie_request($user_id, $user_name, $movie_name) {
    init_requests_file();
    
    $data = json_decode(file_get_contents(REQUESTS_FILE), true);
    
    $request_id = 'REQ_' . time() . '_' . $user_id;
    
    $new_request = [
        'id' => $request_id,
        'movie_name' => $movie_name,
        'user_id' => (string)$user_id,
        'user_name' => $user_name,
        'status' => 'pending',
        'requested_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'notes' => '',
        'completed_at' => null,
        'completed_by' => null
    ];
    
    $data['requests'][] = $new_request;
    $data['total_requests']++;
    $data['pending']++;
    $data['last_updated'] = date('Y-m-d H:i:s');
    
    file_put_contents(REQUESTS_FILE, json_encode($data, JSON_PRETTY_PRINT));
    
    return $request_id;
}

function get_user_requests($user_id) {
    if (!file_exists(REQUESTS_FILE)) {
        return [];
    }
    
    $data = json_decode(file_get_contents(REQUESTS_FILE), true);
    $user_requests = [];
    
    foreach ($data['requests'] as $request) {
        if ($request['user_id'] == (string)$user_id) {
            $user_requests[] = $request;
        }
    }
    
    // Sort by date (newest first)
    usort($user_requests, function($a, $b) {
        return strtotime($b['requested_at']) <=> strtotime($a['requested_at']);
    });
    
    return $user_requests;
}

function get_pending_count() {
    if (!file_exists(REQUESTS_FILE)) {
        return 0;
    }
    
    $data = json_decode(file_get_contents(REQUESTS_FILE), true);
    return $data['pending'] ?? 0;
}

function get_all_requests_stats() {
    if (!file_exists(REQUESTS_FILE)) {
        return [
            'total' => 0,
            'pending' => 0,
            'completed' => 0,
            'rejected' => 0
        ];
    }
    
    $data = json_decode(file_get_contents(REQUESTS_FILE), true);
    return [
        'total' => $data['total_requests'] ?? 0,
        'pending' => $data['pending'] ?? 0,
        'completed' => $data['completed'] ?? 0,
        'rejected' => $data['rejected'] ?? 0
    ];
}

// Initialize requests file
init_requests_file();

// ==================== FORWARDING FUNCTIONS ====================
function deliver_item_to_chat($chat_id, $item) {
    // Show typing indicator
    sendTypingAction($chat_id);
    
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        $channel_id = $item['channel_id'] ?? MAIN_CHANNEL_ID;
        $movie_name = $item['movie_name'] ?? 'Unknown';
        
        // Check if channel is active
        if (!is_channel_active($channel_id)) {
            $channel_id = find_active_channel($channel_id);
        }
        
        // Forward the message
        $result = json_decode(forwardMessage($chat_id, $channel_id, $item['message_id']), true);
        
        if ($result && isset($result['ok']) && $result['ok']) {
            if (LOG_FORWARDS) {
                log_forward($chat_id, $channel_id, $movie_name, true);
            }
            return true;
        } else {
            // Fallback: copy message
            copyMessage($chat_id, $channel_id, $item['message_id']);
            if (LOG_FORWARDS) {
                log_forward($chat_id, $channel_id, $movie_name, false);
            }
            return true;
        }
    }
    
    // Fallback text message
    $text = "🎬 " . ($item['movie_name'] ?? 'Unknown') . "\n";
    $text .= "📢 Join: @EntertainmentTadka786\n";
    $text .= "💬 Requests: @EntertainmentTadka7860";
    sendMessage($chat_id, $text, null, 'HTML');
    return false;
}

function is_channel_active($channel_id) {
    global $ACTIVE_CHANNELS;
    
    foreach ($ACTIVE_CHANNELS as $channel) {
        if ($channel['id'] == $channel_id) {
            return true;
        }
    }
    
    return false;
}

function find_active_channel($original_channel_id) {
    global $ACTIVE_CHANNELS;
    
    // Try main channel first
    foreach ($ACTIVE_CHANNELS as $channel) {
        if ($channel['id'] == MAIN_CHANNEL_ID) {
            return MAIN_CHANNEL_ID;
        }
    }
    
    // Try any active channel
    foreach ($ACTIVE_CHANNELS as $channel) {
        if ($channel['id'] != $original_channel_id) {
            return $channel['id'];
        }
    }
    
    // Default to main channel
    return MAIN_CHANNEL_ID;
}

function log_forward($user_id, $channel_id, $movie_name, $success) {
    $log_file = 'forward_logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FALLBACK';
    $log_entry = "$timestamp | User: $user_id | Channel: $channel_id | Movie: $movie_name | Status: $status\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// ==================== SEARCH FUNCTIONS ====================
function smart_search($query) {
    global $movie_messages;
    $query_lower = strtolower(trim($query));
    $results = array();
    
    if (empty($movie_messages)) {
        get_cached_movies();
    }
    
    foreach ($movie_messages as $movie => $entries) {
        $score = 0;
        
        // Exact match
        if ($movie == $query_lower) {
            $score = 100;
        }
        // Partial match
        elseif (strpos($movie, $query_lower) !== false) {
            $score = 80 - (strlen($movie) - strlen($query_lower));
        }
        // Similar text match
        else {
            similar_text($movie, $query_lower, $similarity);
            if ($similarity > 60) {
                $score = $similarity;
            }
        }
        
        if ($score > 0) {
            // Sort entries by message_id (newest first)
            usort($entries, function($a, $b) {
                return ($b['message_id'] ?? 0) <=> ($a['message_id'] ?? 0);
            });
            
            $results[$movie] = [
                'score' => $score,
                'count' => count($entries),
                'entries' => $entries
            ];
        }
    }
    
    // Sort by score (highest first)
    uasort($results, function($a, $b) {
        if ($b['score'] == $a['score']) {
            return $b['count'] <=> $a['count']; // More entries first if score same
        }
        return $b['score'] <=> $a['score'];
    });
    
    return $results;
}

function advanced_search($chat_id, $query, $user_id = null) {
    global $movie_messages;
    
    sendTypingAction($chat_id);
    
    $q = strtolower(trim($query));
    
    // Minimum length check
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters for search");
        return;
    }
    
    // Invalid keywords filter
    $invalid_keywords = [
        'vlc', 'audio', 'track', 'change', 'open', 'kar', 'me', 'hai',
        'how', 'what', 'problem', 'issue', 'help', 'solution', 'fix',
        'error', 'not working', 'download', 'play', 'video', 'sound',
        'subtitle', 'quality', 'hd', 'full', 'part', 'scene'
    ];
    
    $query_words = explode(' ', $q);
    $invalid_count = 0;
    foreach ($query_words as $word) {
        if (in_array($word, $invalid_keywords)) {
            $invalid_count++;
        }
    }
    
    if ($invalid_count > 0 && ($invalid_count / count($query_words)) > 0.5) {
        $help_msg = "🎬 Please enter a movie name!\n\n";
        $help_msg .= "🔍 Examples:\n";
        $help_msg .= "• kgf\n• pushpa\n• avengers\n• spider-man\n\n";
        $help_msg .= "❌ Don't type technical queries\n\n";
        $help_msg .= "📢 Join: @EntertainmentTadka786";
        sendMessage($chat_id, $help_msg, null, 'HTML');
        return;
    }
    
    $found = smart_search($q);
    
    if (!empty($found)) {
        $total_movies = 0;
        $forwarded_count = 0;
        
        // Count total movies
        foreach ($found as $movie_data) {
            $total_movies += $movie_data['count'];
        }
        
        $first_movie = array_key_first($found);
        $entries = $found[$first_movie]['entries'];
        
        // Show searching message
        $search_msg = "🔍 <b>Found " . count($found) . " matches</b>\n";
        $search_msg .= "🎬 <b>Total videos:</b> $total_movies\n";
        $search_msg .= "⏳ <b>Forwarding...</b>\n\n";
        $search_msg .= "📢 <b>Join:</b> @EntertainmentTadka786";
        
        $search_message = sendMessage($chat_id, $search_msg, null, 'HTML');
        
        // Forward ALL movies from first match
        if (!empty($entries)) {
            $i = 1;
            foreach ($entries as $entry) {
                // Forward each movie
                deliver_item_to_chat($chat_id, $entry);
                $forwarded_count++;
                
                // Small delay to avoid rate limits
                usleep(300000); // 0.3 seconds
                $i++;
            }
        }
        
        // Show summary
        $summary_msg = "✅ <b>Search Complete!</b>\n\n";
        $summary_msg .= "🔍 <b>Search:</b> " . htmlspecialchars($query) . "\n";
        $summary_msg .= "🎬 <b>Matches found:</b> " . count($found) . "\n";
        $summary_msg .= "📹 <b>Videos forwarded:</b> $forwarded_count\n\n";
        
        // Show match list
        if (count($found) > 1) {
            $summary_msg .= "📋 <b>All matches:</b>\n";
            $match_num = 1;
            foreach ($found as $movie_name => $data) {
                $summary_msg .= "$match_num. $movie_name (" . $data['count'] . " videos)\n";
                $match_num++;
                if ($match_num > 10) {
                    $summary_msg .= "... and " . (count($found) - 10) . " more\n";
                    break;
                }
            }
        }
        
        $summary_msg .= "\n📢 <b>Join:</b> @EntertainmentTadka786";
        
        // Edit the original search message
        editMessage($chat_id, $search_message, $summary_msg, null, 'HTML');
        
    } else {
        $msg = "😔 <b>Movie Not Found!</b>\n\n";
        $msg .= "🎬 <b>Requested:</b> " . htmlspecialchars($query) . "\n\n";
        $msg .= "📝 <b>Request it here:</b>\n";
        $msg .= "@EntertainmentTadka7860\n\n";
        $msg .= "🔔 <b>I'll notify you when it's added!</b>\n\n";
        $msg .= "📢 <b>Join:</b> @EntertainmentTadka786";
        
        sendMessage($chat_id, $msg, null, 'HTML');
    }
}

// ==================== REQUEST GROUP HANDLER ====================
function handle_request_group($message) {
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    
    if ($chat_id == REQUEST_GROUP_ID && !empty($text) && strpos($text, '/') !== 0) {
        sendTypingAction($chat_id);
        
        $query = trim($text);
        if (is_movie_request($query)) {
            $user_name = $message['from']['first_name'] ?? 'User';
            $user_id = $message['from']['id'] ?? 0;
            
            $reply = "✅ <b>Request Received!</b>\n\n";
            $reply .= "🎬 <b>Movie:</b> " . htmlspecialchars($query) . "\n";
            $reply .= "👤 <b>By:</b> $user_name\n";
            $reply .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n\n";
            $reply .= "We'll add it soon!\n";
            $reply .= "Check: @EntertainmentTadka786";
            
            sendMessage($chat_id, $reply, null, 'HTML');
            
            // Log request
            $log = date('Y-m-d H:i:s') . " | User: $user_id ($user_name) | Request: $query\n";
            file_put_contents('request_logs.txt', $log, FILE_APPEND);
            
            // Notify owner
            if (OWNER_ID) {
                $admin_msg = "📥 <b>New Movie Request</b>\n\n";
                $admin_msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($query) . "\n";
                $admin_msg .= "👤 <b>User:</b> $user_name\n";
                $admin_msg .= "🆔 <b>User ID:</b> $user_id\n";
                $admin_msg .= "💬 <b>From:</b> Request Group\n";
                $admin_msg .= "⏰ <b>Time:</b> " . date('H:i:s');
                
                sendMessage(OWNER_ID, $admin_msg, null, 'HTML');
            }
            
            return true;
        }
    }
    
    return false;
}

function is_movie_request($text) {
    $text_lower = strtolower(trim($text));
    
    // Skip common non-movie messages
    $skip_patterns = ['hi', 'hello', 'hey', 'thanks', 'thank', 'please', 'help', 'ok', 'yes', 'no'];
    foreach ($skip_patterns as $pattern) {
        if (strpos($text_lower, $pattern) === 0) {
            return false;
        }
    }
    
    // Movie indicators
    $movie_indicators = ['movie', 'film', 'download', 'watch', 'hd', '720p', '1080p'];
    foreach ($movie_indicators as $indicator) {
        if (strpos($text_lower, $indicator) !== false) {
            return true;
        }
    }
    
    // Check if it looks like a movie name
    if (strlen($text) >= 3 && preg_match('/^[a-zA-Z0-9\s\-\.\,\(\)\&\'\"]+$/', $text)) {
        return true;
    }
    
    return false;
}

// ==================== PAGINATION FUNCTIONS ====================
function get_all_movies_list() {
    $movies = load_movies_from_csv();
    $list = [];
    
    foreach ($movies as $movie) {
        $list[] = [
            'name' => $movie['movie_name'],
            'message_id' => $movie['message_id'],
            'channel_id' => $movie['channel_id']
        ];
    }
    
    // Sort by name
    usort($list, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $list;
}

function paginate_movies($all_movies, $page = 1) {
    $per_page = ITEMS_PER_PAGE;
    $total = count($all_movies);
    $total_pages = ceil($total / $per_page);
    
    // Validate page number
    if ($page < 1) $page = 1;
    if ($page > $total_pages) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    $slice = array_slice($all_movies, $offset, $per_page);
    
    return [
        'slice' => $slice,
        'page' => $page,
        'total_pages' => $total_pages,
        'total' => $total,
        'per_page' => $per_page
    ];
}

function build_totalupload_keyboard($current_page, $total_pages) {
    $keyboard = [];
    
    if ($total_pages <= 1) {
        return null;
    }
    
    $row = [];
    
    if ($current_page > 1) {
        $row[] = ['text' => '◀️ Previous', 'callback_data' => 'tu_prev_' . ($current_page - 1)];
    }
    
    $row[] = ['text' => "📄 $current_page/$total_pages", 'callback_data' => 'current_page'];
    
    if ($current_page < $total_pages) {
        $row[] = ['text' => 'Next ▶️', 'callback_data' => 'tu_next_' . ($current_page + 1)];
    }
    
    $keyboard[] = $row;
    
    // View current page button
    $keyboard[] = [
        ['text' => '📤 Send This Page', 'callback_data' => 'tu_view_' . $current_page],
        ['text' => '❌ Stop', 'callback_data' => 'tu_stop']
    ];
    
    return ['inline_keyboard' => $keyboard];
}

function forward_page_movies($chat_id, $movies) {
    foreach ($movies as $movie) {
        deliver_item_to_chat($chat_id, [
            'movie_name' => $movie['name'],
            'message_id' => $movie['message_id'],
            'channel_id' => $movie['channel_id']
        ]);
        usleep(200000); // 0.2 second delay
    }
}

// ==================== DATE STATISTICS ====================
function get_movies_by_date() {
    $movies = load_movies_from_csv();
    $date_counts = [];
    
    // Since CSV doesn't store dates, we'll use a placeholder
    // In real implementation, you'd need to store dates in CSV
    $date_counts['Unknown'] = count($movies);
    
    return $date_counts;
}

// ==================== FILE INITIALIZATION ====================
if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode(['users' => [], 'total_requests' => 0]));
    @chmod(USERS_FILE, 0666);
}

if (!file_exists(STATS_FILE)) {
    file_put_contents(STATS_FILE, json_encode([
        'total_movies' => 0,
        'total_users' => 0,
        'total_searches' => 0,
        'last_updated' => date('Y-m-d H:i:s')
    ]));
    @chmod(STATS_FILE, 0666);
}

if (!file_exists(BACKUP_DIR)) {
    @mkdir(BACKUP_DIR, 0777, true);
}

// Memory caches
$movie_messages = array();
$movie_cache = array();

// ==================== MAIN UPDATE PROCESSING ====================
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    // Initialize CSV cache
    get_cached_movies();
    
    // Maintenance mode check
    if ($MAINTENANCE_MODE && isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $msg = "🛠️ <b>Bot Under Maintenance</b>\n\n";
        $msg .= "We're temporarily unavailable.\n";
        $msg .= "Will be back soon!\n\n";
        $msg .= "Thanks for patience 🙏";
        sendMessage($chat_id, $msg, null, 'HTML');
        exit;
    }
    
    // Channel post handler
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        
        // Check if from any active channel
        foreach ($ACTIVE_CHANNELS as $channel) {
            if ($channel['id'] == $chat_id) {
                $text = '';
                if (isset($message['caption'])) {
                    $text = $message['caption'];
                } elseif (isset($message['text'])) {
                    $text = $message['text'];
                } elseif (isset($message['document'])) {
                    $text = $message['document']['file_name'];
                } else {
                    $text = 'Media - ' . date('d-m-Y H:i');
                }
                
                if (!empty(trim($text))) {
                    append_movie($text, $message_id, $chat_id);
                    
                    // Log the save
                    error_log("✅ Movie saved: '$text' from channel: " . $channel['name'] . " ($chat_id)");
                }
                break;
            }
        }
    }
    
    // Message handler
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user_id = $message['from']['id'] ?? 0;
        $user_name = $message['from']['first_name'] ?? 'User';
        
        // Show typing indicator
        sendTypingAction($chat_id);
        
        // Handle request group first
        if (handle_request_group($message)) {
            exit;
        }
        
        // Update user stats
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = [
                'first_name' => $message['from']['first_name'] ?? '',
                'username' => $message['from']['username'] ?? '',
                'joined' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s'),
                'total_searches' => 0,
                'total_requests' => 0
            ];
            $users_data['total_users'] = count($users_data['users']);
        }
        $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        
        // Process commands
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = $parts[0];
            
            if ($command == '/start') {
                $welcome = "🎬 <b>Welcome to Entertainment Tadka!</b>\n\n";
                $welcome .= "📢 <b>How to use this bot:</b>\n";
                $welcome .= "• Simply type any movie name\n";
                $welcome .= "• Use English or Hindi\n";
                $welcome .= "• Partial names also work\n\n";
                $welcome .= "🔍 <b>Examples:</b>\n";
                $welcome .= "• Mandala Murders 2025\n";
                $welcome .= "• Lokah Chapter 1 Chandra 2025\n";
                $welcome .= "• Idli Kadai (2025)\n";
                $welcome .= "• IT - Welcome to Derry (2025) S01\n";
                $welcome .= "• hindi movie\n";
                $welcome .= "• kgf\n\n";
                $welcome .= "❌ <b>Don't type:</b>\n";
                $welcome .= "• Technical questions\n";
                $welcome .= "• Player instructions\n";
                $welcome .= "• Non-movie queries\n\n";
                $welcome .= "📢 <b>Join Our Channels:</b>\n";
                $welcome .= "🍿 Main: @EntertainmentTadka786\n";
                $welcome .= "📥 Requests: @EntertainmentTadka7860\n";
                $welcome .= "🎭 Theater Prints: @threater_print_movies\n";
                $welcome .= "🔒 Backup: @ETBackup\n\n";
                $welcome .= "💬 <b>Need help?</b> Use /help for all commands\n\n";
                $welcome .= "🔍 <b>Start by typing a movie name!</b>";
                
                sendMessage($chat_id, $welcome, null, 'HTML');
            }
            elseif ($command == '/help') {
                $help = "🤖 <b>Entertainment Tadka Bot - Complete Guide</b>\n\n";
                $help .= "📢 <b>Our Channels:</b>\n";
                $help .= "🍿 Main: @EntertainmentTadka786 - Latest movies\n";
                $help .= "📥 Requests: @EntertainmentTadka7860 - Support & requests\n";
                $help .= "🎭 Theater: @threater_print_movies - HD prints\n";
                $help .= "🔒 Backup: @ETBackup - Data protection\n\n";
                $help .= "🎯 <b>Search Commands:</b>\n";
                $help .= "• Just type movie name - Smart search\n\n";
                $help .= "📁 <b>Browse Commands:</b>\n";
                $help .= "• /totalupload - All movies\n\n";
                $help .= "📝 <b>Request Commands:</b>\n";
                $help .= "• /request movie - Request movie\n";
                $help .= "• /myrequests - Request status\n";
                $help .= "• Join @EntertainmentTadka7860 for support\n\n";
                $help .= "🔗 <b>Channel Commands:</b>\n";
                $help .= "• /mainchannel - Main channel\n";
                $help .= "• /requestchannel - Requests\n";
                $help .= "• /theaterchannel - Theater prints\n";
                $help .= "• /backupchannel - Backup info\n\n";
                $help .= "💡 <b>Tip:</b> Just type any movie name to search!";
                
                sendMessage($chat_id, $help, null, 'HTML');
            }
            elseif ($command == '/totalupload') {
                $page = isset($parts[1]) ? (int)$parts[1] : 1;
                totalupload_controller($chat_id, $page);
            }
            elseif ($command == '/checkdate') {
                $date_counts = get_movies_by_date();
                $msg = "📅 <b>Movies Statistics</b>\n\n";
                
                if (!empty($date_counts)) {
                    foreach ($date_counts as $date => $count) {
                        $msg .= "• $date: <b>$count movies</b>\n";
                    }
                } else {
                    $msg .= "No date information available.\n";
                }
                
                $msg .= "\n📢 <b>Total Movies:</b> " . count(load_movies_from_csv()) . "\n";
                $msg .= "📊 <b>Last Updated:</b> " . date('d-m-Y H:i:s') . "\n\n";
                $msg .= "🎬 Use /totalupload to browse all movies";
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/testcsv') {
                $movies = load_movies_from_csv();
                $msg = "📊 <b>CSV Data Test</b>\n\n";
                $msg .= "📁 <b>Total Movies:</b> " . count($movies) . "\n";
                $msg .= "📄 <b>CSV Format:</b> " . CSV_FORMAT . "\n\n";
                
                if (count($movies) > 0) {
                    $msg .= "🎬 <b>First 5 movies:</b>\n";
                    for ($i = 0; $i < min(5, count($movies)); $i++) {
                        $msg .= ($i + 1) . ". " . $movies[$i]['movie_name'] . "\n";
                    }
                    
                    if (count($movies) > 5) {
                        $msg .= "... and " . (count($movies) - 5) . " more\n";
                    }
                } else {
                    $msg .= "❌ <b>No movies found in CSV!</b>\n";
                }
                
                $msg .= "\n📢 Use /checkcsv all for complete list";
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/checkcsv') {
                $show_all = isset($parts[1]) && $parts[1] == 'all';
                $movies = load_movies_from_csv();
                
                if ($show_all) {
                    $msg = "📋 <b>All Movies in CSV</b>\n\n";
                    $msg .= "📊 <b>Total:</b> " . count($movies) . " movies\n\n";
                    
                    foreach ($movies as $index => $movie) {
                        $msg .= ($index + 1) . ". " . $movie['movie_name'] . "\n";
                        
                        // Break if message gets too long
                        if (strlen($msg) > 3500) {
                            $msg .= "... and " . (count($movies) - $index - 1) . " more";
                            break;
                        }
                    }
                } else {
                    $msg = "✅ <b>CSV Status</b>\n\n";
                    $msg .= "📊 <b>Total Movies:</b> " . count($movies) . "\n";
                    $msg .= "📄 <b>File:</b> " . CSV_FILE . "\n";
                    $msg .= "🔧 <b>Format:</b> " . CSV_FORMAT . "\n";
                    $msg .= "⏰ <b>Last Modified:</b> " . date('d-m-Y H:i:s', filemtime(CSV_FILE)) . "\n\n";
                    $msg .= "📝 Use <code>/checkcsv all</code> to see all movies";
                }
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/request') {
                $movie_name = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
                
                if (empty($movie_name)) {
                    $msg = "📝 <b>How to request a movie:</b>\n\n";
                    $msg .= "Usage: <code>/request Movie Name</code>\n\n";
                    $msg .= "Example: <code>/request KGF 3 hindi movie</code>\n\n";
                    $msg .= "📢 Join: @EntertainmentTadka7860\n";
                    $msg .= "🔔 We'll notify when it's added!";
                    
                    sendMessage($chat_id, $msg, null, 'HTML');
                } else {
                    // Save request to database
                    $request_id = save_movie_request($user_id, $user_name, $movie_name);
                    
                    $msg = "✅ <b>Request Submitted Successfully!</b>\n\n";
                    $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
                    $msg .= "📋 <b>Request ID:</b> <code>$request_id</code>\n";
                    $msg .= "👤 <b>Requested by:</b> $user_name\n";
                    $msg .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n";
                    $msg .= "📊 <b>Status:</b> ⏳ Pending\n\n";
                    $msg .= "📢 We'll add it soon!\n";
                    $msg .= "💬 Join: @EntertainmentTadka7860 for updates\n";
                    $msg .= "🔍 Check status: /myrequests";
                    
                    sendMessage($chat_id, $msg, null, 'HTML');
                    
                    // Notify owner
                    $admin_msg = "📥 <b>New Movie Request</b>\n\n";
                    $admin_msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
                    $admin_msg .= "📋 <b>Request ID:</b> $request_id\n";
                    $admin_msg .= "👤 <b>From:</b> $user_name\n";
                    $admin_msg .= "🆔 <b>User ID:</b> $user_id\n";
                    $admin_msg .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n\n";
                    $admin_msg .= "📊 <b>Total pending requests:</b> " . get_pending_count();
                    
                    sendMessage(OWNER_ID, $admin_msg, null, 'HTML');
                    
                    // Update user stats
                    $users_data = json_decode(file_get_contents(USERS_FILE), true);
                    if (isset($users_data['users'][$user_id])) {
                        $users_data['users'][$user_id]['total_requests'] = 
                            ($users_data['users'][$user_id]['total_requests'] ?? 0) + 1;
                        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
                    }
                }
            }
            elseif ($command == '/myrequests') {
                $user_requests = get_user_requests($user_id);
                
                if (empty($user_requests)) {
                    $msg = "📭 <b>No Requests Found</b>\n\n";
                    $msg .= "You haven't made any requests yet.\n\n";
                    $msg .= "🎬 <b>To request a movie:</b>\n";
                    $msg .= "Use: <code>/request Movie Name</code>\n\n";
                    $msg .= "Example: <code>/request Avengers Endgame hindi</code>\n\n";
                    $msg .= "📢 Join: @EntertainmentTadka7860";
                    
                    sendMessage($chat_id, $msg, null, 'HTML');
                    return;
                }
                
                $msg = "📋 <b>Your Movie Requests</b>\n\n";
                
                $pending_count = 0;
                $completed_count = 0;
                
                foreach ($user_requests as $index => $request) {
                    $status_emoji = [
                        'pending' => '⏳',
                        'approved' => '✅',
                        'completed' => '🎬',
                        'rejected' => '❌'
                    ];
                    
                    $status_text = ucfirst($request['status']);
                    $emoji = $status_emoji[$request['status']] ?? '📝';
                    
                    if ($request['status'] == 'pending') $pending_count++;
                    if ($request['status'] == 'completed') $completed_count++;
                    
                    $msg .= ($index + 1) . ". $emoji <b>" . htmlspecialchars($request['movie_name']) . "</b>\n";
                    $msg .= "   📅 " . $request['requested_at'] . "\n";
                    $msg .= "   📊 <b>Status:</b> $status_text\n";
                    
                    if ($request['status'] == 'completed' && $request['completed_at']) {
                        $msg .= "   ✅ Completed: " . $request['completed_at'] . "\n";
                    }
                    
                    $msg .= "\n";
                }
                
                $msg .= "📊 <b>Summary:</b>\n";
                $msg .= "⏳ Pending: $pending_count\n";
                $msg .= "🎬 Completed: $completed_count\n";
                $msg .= "📋 Total: " . count($user_requests) . "\n\n";
                $msg .= "📢 Join: @EntertainmentTadka7860 for updates";
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/mainchannel') {
                $msg = "🍿 <b>Main Channel</b>\n\n";
                $msg .= "📢 <b>@EntertainmentTadka786</b>\n";
                $msg .= "• Latest movies & series\n";
                $msg .= "• Daily updates\n";
                $msg .= "• Multiple qualities\n";
                $msg .= "• Hindi/English content\n\n";
                $msg .= "🔗 Link: https://t.me/EntertainmentTadka786\n";
                $msg .= "👥 Members: 1000+\n";
                $msg .= "📅 Updated: Daily";
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/requestchannel' || $command == '/requestgroup') {
                $msg = "📥 <b>Request Channel</b>\n\n";
                $msg .= "📢 <b>@EntertainmentTadka7860</b>\n";
                $msg .= "• Request movies\n";
                $msg .= "• Get support\n";
                $msg .= "• Report issues\n";
                $msg .= "• Suggest improvements\n\n";
                $msg .= "🔗 Link: https://t.me/EntertainmentTadka7860\n";
                $msg .= "💬 Active community\n";
                $msg .= "⚡ Quick responses";
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/theaterchannel' || $command == '/theaterprints') {
                $msg = "🎭 <b>Theater Prints Channel</b>\n\n";
                $msg .= "📢 <b>@threater_print_movies</b>\n";
                $msg .= "• HD theater prints\n";
                $msg .= "• Blu-ray quality\n";
                $msg .= "• Best audio/video\n";
                $msg .= "• Exclusive releases\n\n";
                $msg .= "🔗 Link: https://t.me/threater_print_movies\n";
                $msg .= "🌟 Premium content\n";
                $msg .= "🎬 Cinema experience";
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/backupchannel') {
                $msg = "🔒 <b>Backup Channel</b>\n\n";
                $msg .= "📢 <b>@ETBackup</b>\n";
                $msg .= "• Backup of all movies\n";
                $msg .= "• Data protection\n";
                $msg .= "• Emergency access\n";
                $msg .= "• Redundant storage\n\n";
                $msg .= "🔗 Link: https://t.me/ETBackup\n";
                $msg .= "💾 Secure backup\n";
                $msg .= "🛡️ Data safety";
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/stats' && $user_id == OWNER_ID) {
                $stats = json_decode(file_get_contents(STATS_FILE), true);
                $users_data = json_decode(file_get_contents(USERS_FILE), true);
                $movie_count = count(load_movies_from_csv());
                $request_stats = get_all_requests_stats();
                
                $msg = "📊 <b>Bot Statistics</b>\n\n";
                $msg .= "🎬 <b>Movies Database:</b>\n";
                $msg .= "• Total Movies: $movie_count\n";
                $msg .= "• CSV File: " . CSV_FILE . "\n";
                $msg .= "• Format: " . CSV_FORMAT . "\n\n";
                
                $msg .= "👥 <b>Users:</b>\n";
                $msg .= "• Total Users: " . ($users_data['total_users'] ?? 0) . "\n";
                $msg .= "• Active Today: " . count(array_filter($users_data['users'] ?? [], function($user) {
                    return date('Y-m-d') == date('Y-m-d', strtotime($user['last_active'] ?? ''));
                })) . "\n\n";
                
                $msg .= "📝 <b>Requests:</b>\n";
                $msg .= "• Total: " . $request_stats['total'] . "\n";
                $msg .= "• Pending: " . $request_stats['pending'] . "\n";
                $msg .= "• Completed: " . $request_stats['completed'] . "\n";
                $msg .= "• Rejected: " . $request_stats['rejected'] . "\n\n";
                
                $msg .= "📢 <b>Active Channels:</b> " . count($ACTIVE_CHANNELS) . "\n";
                $msg .= "⏰ <b>Last Updated:</b> " . ($stats['last_updated'] ?? 'N/A');
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            else {
                $msg = "❌ <b>Unknown Command</b>\n\n";
                $msg .= "🔍 <b>Available Commands:</b>\n";
                $msg .= "/start - Welcome message\n";
                $msg .= "/help - Help information\n";
                $msg .= "/totalupload - Browse all movies\n";
                $msg .= "/checkdate - Date statistics\n";
                $msg .= "/request - Request movie\n";
                $msg .= "/myrequests - Your requests\n";
                $msg .= "/mainchannel - Main channel info\n";
                $msg .= "/requestchannel - Request channel\n";
                $msg .= "/theaterchannel - Theater prints\n";
                $msg .= "/backupchannel - Backup channel\n\n";
                $msg .= "💡 <b>Just type a movie name to search!</b>";
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
        }
        elseif (!empty(trim($text))) {
            // Search for movie
            advanced_search($chat_id, $text, $user_id);
            
            // Update stats
            $stats = json_decode(file_get_contents(STATS_FILE), true);
            $stats['total_searches'] = ($stats['total_searches'] ?? 0) + 1;
            $stats['last_updated'] = date('Y-m-d H:i:s');
            file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
            
            // Update user stats
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            if (isset($users_data['users'][$user_id])) {
                $users_data['users'][$user_id]['total_searches'] = 
                    ($users_data['users'][$user_id]['total_searches'] ?? 0) + 1;
                file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            }
        }
    }
    
    // Callback query handler
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $data = $query['data'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $message_id = $message['message_id'];
        
        answerCallbackQuery($query['id'], "Processing...");
        
        // Handle pagination
        if (strpos($data, 'tu_prev_') === 0) {
            $page = (int) str_replace('tu_prev_', '', $data);
            totalupload_controller($chat_id, $page);
        }
        elseif (strpos($data, 'tu_next_') === 0) {
            $page = (int) str_replace('tu_next_', '', $data);
            totalupload_controller($chat_id, $page);
        }
        elseif (strpos($data, 'tu_view_') === 0) {
            $page = (int) str_replace('tu_view_', '', $data);
            $all_movies = get_all_movies_list();
            $pg = paginate_movies($all_movies, $page);
            
            // Send current page movies
            forward_page_movies($chat_id, $pg['slice']);
            
            // Update message
            $msg = "✅ <b>Sent page $page</b>\n\n";
            $msg .= "🎬 <b>Movies sent:</b> " . count($pg['slice']) . "\n";
            $msg .= "📄 <b>Page:</b> $page/{$pg['total_pages']}\n\n";
            $msg .= "📢 Join: @EntertainmentTadka786";
            
            editMessage($chat_id, $message, $msg, null, 'HTML');
        }
        elseif ($data === 'tu_stop') {
            deleteMessage($chat_id, $message_id);
        }
        elseif (strpos($data, 'movie_') === 0) {
            $movie_name = str_replace('movie_', '', $data);
            $found = smart_search($movie_name);
            
            if (!empty($found)) {
                $entries = $found[$movie_name]['entries'] ?? [];
                if (!empty($entries)) {
                    foreach ($entries as $entry) {
                        deliver_item_to_chat($chat_id, $entry);
                        usleep(200000);
                    }
                    
                    $msg = "✅ <b>Sent all videos for:</b> " . htmlspecialchars($movie_name) . "\n";
                    $msg .= "🎬 <b>Total videos:</b> " . count($entries) . "\n\n";
                    $msg .= "📢 Join: @EntertainmentTadka786";
                    
                    editMessage($chat_id, $message, $msg, null, 'HTML');
                }
            }
        }
    }
    
    // Auto-backup at midnight
    if (date('H:i') == '00:00') {
        $backup_file = BACKUP_DIR . 'movies_backup_' . date('Y-m-d') . '.csv';
        if (file_exists(CSV_FILE)) {
            copy(CSV_FILE, $backup_file);
        }
    }
}

// ==================== PAGINATION CONTROLLER ====================
function totalupload_controller($chat_id, $page = 1) {
    $all = get_all_movies_list();
    if (empty($all)) {
        $msg = "📭 <b>No Movies Found!</b>\n\n";
        $msg .= "🎬 Database is empty\n";
        $msg .= "📢 Add movies to channels\n";
        $msg .= "💬 Join: @EntertainmentTadka7860";
        sendMessage($chat_id, $msg, null, 'HTML');
        return;
    }
    
    $pg = paginate_movies($all, (int)$page);
    
    // Forward current page movies
    forward_page_movies($chat_id, $pg['slice']);
    
    // Better formatted message
    $title = "🎬 <b>Total Uploads</b>\n\n";
    $title .= "📊 <b>Statistics:</b>\n";
    $title .= "• Total Movies: <b>{$pg['total']}</b>\n";
    $title .= "• Current Page: <b>{$pg['page']}/{$pg['total_pages']}</b>\n";
    $title .= "• Showing: <b>" . count($pg['slice']) . " movies</b>\n\n";
    
    // Current page movies list
    $title .= "📋 <b>Current Page Movies:</b>\n";
    $i = 1;
    foreach ($pg['slice'] as $movie) {
        $movie_name = htmlspecialchars($movie['name'] ?? 'Unknown');
        $title .= "$i. {$movie_name}\n";
        $i++;
    }
    
    $title .= "\n📍 <b>Navigation:</b> Use buttons below";
    $title .= "\n📢 <b>Join:</b> @EntertainmentTadka786";
    
    $kb = build_totalupload_keyboard($pg['page'], $pg['total_pages']);
    sendMessage($chat_id, $title, $kb, 'HTML');
}

// ==================== WEBHOOK SETUP ====================
if (php_sapi_name() === 'cli' || isset($_GET['setwebhook'])) {
    $webhook_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                   "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    
    echo "<h1>🚀 Webhook Setup</h1>";
    echo "<h2>📊 Configuration Status:</h2>";
    
    echo "<h3>✅ Active Channels (" . count($ACTIVE_CHANNELS) . "):</h3>";
    echo "<ul>";
    foreach ($ACTIVE_CHANNELS as $channel) {
        echo "<li>{$channel['name']} ({$channel['id']})</li>";
    }
    echo "</ul>";
    
    echo "<h3>❌ Inactive Channels (" . count($PRIVATE_CHANNELS) . "):</h3>";
    echo "<p><em>Private channels are disabled in configuration</em></p>";
    
    echo "<h3>👥 Request Group:</h3>";
    echo "<p>@EntertainmentTadka7860 ($REQUEST_GROUP_ID)</p>";
    
    echo "<h3>📁 CSV Format:</h3>";
    echo "<p><code>" . CSV_FORMAT . "</code></p>";
    
    echo "<h3>🔧 Webhook Result:</h3>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    
    echo "<h3>🎯 Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Add bot to all channels as admin</li>";
    echo "<li>Test by searching a movie</li>";
    echo "<li>Check request group functionality</li>";
    echo "</ol>";
    
    exit;
}

// ==================== DEFAULT PAGE ====================
if (!$update) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>🎬 Entertainment Tadka Bot</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .status { padding: 10px; border-radius: 5px; margin: 10px 0; }
            .success { background: #d4edda; color: #155724; }
            .warning { background: #fff3cd; color: #856404; }
            .info { background: #d1ecf1; color: #0c5460; }
            .btn { display: inline-block; padding: 10px 20px; background: #0088cc; color: white; 
                   text-decoration: none; border-radius: 5px; margin: 5px; }
        </style>
    </head>
    <body>
        <h1>🎬 Entertainment Tadka Bot</h1>
        
        <div class='status success'>
            <h3>✅ Bot Status: Running</h3>
            <p>Environment loaded from .env file</p>
        </div>
        
        <div class='status info'>
            <h3>📊 System Info</h3>
            <p><strong>Public Channels:</strong> " . count($PUBLIC_CHANNELS) . " (Active)</p>
            <p><strong>Private Channels:</strong> " . count($PRIVATE_CHANNELS) . " (Inactive)</p>
            <p><strong>Request Group:</strong> Active</p>
            <p><strong>CSV Format:</strong> " . CSV_FORMAT . "</p>
            <p><strong>Typing Indicator:</strong> " . (ENABLE_TYPING_INDICATOR ? 'ON' : 'OFF') . "</p>
        </div>
        
        <div class='status warning'>
            <h3>🔧 Configuration Required</h3>
            <p>1. Edit .env file and add BOT_TOKEN</p>
            <p>2. Set webhook using button below</p>
            <p>3. Add bot to channels as admin</p>
        </div>
        
        <div>
            <a href='?setwebhook=1' class='btn'>🚀 Set Webhook</a>
            <a href='check_config.php' class='btn'>🔍 Check Config</a>
            <a href='migrate_csv.php' class='btn'>🔄 Migrate CSV</a>
            <a href='logs.php' class='btn'>📝 View Logs</a>
        </div>
        
        <h3>📢 Channels Network:</h3>
        <ul>";
    
    foreach ($ACTIVE_CHANNELS as $channel) {
        echo "<li><strong>{$channel['name']}:</strong> {$channel['id']}</li>";
    }
    
    echo "</ul>
        <p><em>Private channels are disabled in configuration</em></p>
        
        <h3>💬 Request Group:</h3>
        <p>@EntertainmentTadka7860 ($REQUEST_GROUP_ID)</p>
    </body>
    </html>";
}
?>
