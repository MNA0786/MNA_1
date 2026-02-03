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
define('STATS_FILE', $_ENV['STATS_FILE'] ?? 'bot_stats.json');
define('BACKUP_DIR', $_ENV['BACKUP_DIR'] ?? 'backups/');
define('CACHE_EXPIRY', (int) ($_ENV['CACHE_EXPIRY'] ?? '300'));
define('ITEMS_PER_PAGE', (int) ($_ENV['ITEMS_PER_PAGE'] ?? '5'));
define('ENABLE_TYPING_INDICATOR', filter_var($_ENV['ENABLE_TYPING_INDICATOR'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('ENABLE_PUBLIC_CHANNELS', filter_var($_ENV['ENABLE_PUBLIC_CHANNELS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('ENABLE_PRIVATE_CHANNELS', filter_var($_ENV['ENABLE_PRIVATE_CHANNELS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
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

function sendUploadPhotoAction($chat_id) {
    if (!ENABLE_TYPING_INDICATOR) {
        return;
    }
    
    $result = apiRequest('sendChatAction', [
        'chat_id' => $chat_id,
        'action' => 'upload_photo'
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
            'text' => $new_text
        ];
        if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
        apiRequest('editMessageText', $data);
    }
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

// ==================== FORWARDING FUNCTIONS ====================
function deliver_item_to_chat($chat_id, $item) {
    // Show typing indicator
    sendTypingAction($chat_id);
    
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        $channel_id = $item['channel_id'] ?? MAIN_CHANNEL_ID;
        
        // Check if channel is active
        if (!is_channel_active($channel_id)) {
            $channel_id = find_active_channel($channel_id);
        }
        
        // Forward the message
        $result = json_decode(forwardMessage($chat_id, $channel_id, $item['message_id']), true);
        
        if ($result && isset($result['ok']) && $result['ok']) {
            if (LOG_FORWARDS) {
                log_forward($chat_id, $channel_id, $item['movie_name'], true);
            }
            return true;
        } else {
            // Fallback: copy message
            copyMessage($chat_id, $channel_id, $item['message_id']);
            if (LOG_FORWARDS) {
                log_forward($chat_id, $channel_id, $item['movie_name'], false);
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
        if ($movie == $query_lower) $score = 100;
        elseif (strpos($movie, $query_lower) !== false) $score = 80 - (strlen($movie) - strlen($query_lower));
        else {
            similar_text($movie, $query_lower, $similarity);
            if ($similarity > 60) $score = $similarity;
        }
        if ($score > 0) {
            $results[$movie] = [
                'score' => $score,
                'count' => count($entries),
                'entries' => $entries
            ];
        }
    }
    
    uasort($results, function($a,$b){
        return $b['score'] - $a['score'];
    });
    
    return array_slice($results, 0, 10);
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
        $first_movie = array_key_first($found);
        $entries = $found[$first_movie]['entries'];
        
        // Send first result
        if (!empty($entries)) {
            deliver_item_to_chat($chat_id, $entries[0]);
            
            // Show additional matches if any
            if (count($found) > 1) {
                $msg = "🔍 Found " . count($found) . " matches:\n\n";
                $i = 1;
                foreach (array_slice($found, 0, 5) as $movie => $data) {
                    $msg .= "$i. $movie (" . $data['count'] . ")\n";
                    $i++;
                }
                
                if (count($found) > 5) {
                    $msg .= "... and " . (count($found) - 5) . " more\n";
                }
                
                sendMessage($chat_id, $msg);
            }
        }
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
                'total_searches' => 0
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
                $welcome .= "🔍 <b>How to use:</b>\n";
                $welcome .= "• Just type movie name\n";
                $welcome .= "• Use English or Hindi\n";
                $welcome .= "• Partial names work too\n\n";
                $welcome .= "📢 <b>Join our channels:</b>\n";
                $welcome .= "• @EntertainmentTadka786\n";
                $welcome .= "• @threater_print_movies\n";
                $welcome .= "• @ETBackup\n\n";
                $welcome .= "💬 <b>Request movies:</b>\n";
                $welcome .= "• @EntertainmentTadka7860\n\n";
                $welcome .= "🔍 <b>Start by typing a movie name!</b>";
                
                sendMessage($chat_id, $welcome, null, 'HTML');
            }
            elseif ($command == '/help') {
                $help = "🤖 <b>Entertainment Tadka Bot</b>\n\n";
                $help .= "📢 <b>Channels:</b>\n";
                $help .= "@EntertainmentTadka786\n";
                $help .= "@threater_print_movies\n";
                $help .= "@ETBackup\n\n";
                $help .= "💬 <b>Request Group:</b>\n";
                $help .= "@EntertainmentTadka7860\n\n";
                $help .= "🔍 <b>Commands:</b>\n";
                $help .= "/start - Welcome message\n";
                $help .= "/help - This message\n";
                $help .= "/stats - Bot statistics\n\n";
                $help .= "🔍 <b>Just type any movie name to search!</b>";
                
                sendMessage($chat_id, $help, null, 'HTML');
            }
            elseif ($command == '/stats' && $user_id == OWNER_ID) {
                $stats = json_decode(file_get_contents(STATS_FILE), true);
                $users_data = json_decode(file_get_contents(USERS_FILE), true);
                
                $msg = "📊 <b>Bot Statistics</b>\n\n";
                $msg .= "🎬 <b>Total Movies:</b> " . ($stats['total_movies'] ?? 0) . "\n";
                $msg .= "👥 <b>Total Users:</b> " . ($users_data['total_users'] ?? 0) . "\n";
                $msg .= "🔍 <b>Total Searches:</b> " . ($stats['total_searches'] ?? 0) . "\n";
                $msg .= "⏰ <b>Last Updated:</b> " . ($stats['last_updated'] ?? 'N/A') . "\n\n";
                $msg .= "📢 <b>Active Channels:</b> " . count($ACTIVE_CHANNELS);
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            else {
                $msg = "❌ <b>Unknown Command</b>\n\n";
                $msg .= "🔍 <b>Available Commands:</b>\n";
                $msg .= "/start - Welcome message\n";
                $msg .= "/help - Help information\n";
                $msg .= "\n💡 <b>Just type a movie name to search!</b>";
                
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
        
        answerCallbackQuery($query['id'], "Processing...");
        
        // Handle callback data
        if (strpos($data, 'movie_') === 0) {
            $movie_name = str_replace('movie_', '', $data);
            $found = smart_search($movie_name);
            
            if (!empty($found)) {
                $entries = $found[$movie_name]['entries'] ?? [];
                if (!empty($entries)) {
                    deliver_item_to_chat($chat_id, $entries[0]);
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
