<?php
// ============================================
// 🎬 ENTERTAINMENT TADKA BOT - LATEST VERSION
// ============================================
// Developer: Entertainment Tadka Team
// Contact: @EntertainmentTadka7860
// ============================================

// ERROR SHOW KARO - DEBUGGING KE LIYE
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ============================================
// 🚀 ENVIRONMENT CONFIG LOADER
// ============================================
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

// Environment load karo
loadEnv();

// ============================================
// ⚙️ CONFIGURATION SETTINGS
// ============================================

// 🤖 TELEGRAM BOT CONFIG
define('BOT_TOKEN', $_ENV['BOT_TOKEN'] ?? '');
define('BOT_USERNAME', $_ENV['BOT_USERNAME'] ?? '@EntertainmentTadkaBot');
define('OWNER_ID', (int) ($_ENV['OWNER_ID'] ?? '1080317415'));

// 📢 CHANNELS CONFIG
define('MAIN_CHANNEL_ID', $_ENV['MAIN_CHANNEL_ID'] ?? '-1003181705395');
define('THEATER_CHANNEL_ID', $_ENV['THEATER_CHANNEL_ID'] ?? '-1002831605258');
define('BACKUP_CHANNEL_ID', $_ENV['BACKUP_CHANNEL_ID'] ?? '-1002964109368');
define('REQUEST_GROUP_ID', $_ENV['REQUEST_GROUP_ID'] ?? '-1003083386043');

// 🔒 PRIVATE CHANNELS (Optional)
define('PRIVATE_CHANNEL_1', $_ENV['PRIVATE_CHANNEL_1'] ?? '-1003251791991');
define('PRIVATE_CHANNEL_2', $_ENV['PRIVATE_CHANNEL_2'] ?? '-1002337293281');
define('PRIVATE_CHANNEL_3', $_ENV['PRIVATE_CHANNEL_3'] ?? '-1003614546520');

// 📁 FILE CONFIG
define('CSV_FILE', $_ENV['CSV_FILE'] ?? 'movies.csv');
define('CSV_FORMAT', 'movie_name,message_id,channel_id'); // FORMAT LOCKED - CHANGE MAT KARNA!
define('USERS_FILE', $_ENV['USERS_FILE'] ?? 'users.json');
define('REQUESTS_FILE', $_ENV['REQUESTS_FILE'] ?? 'requests.json');
define('STATS_FILE', $_ENV['STATS_FILE'] ?? 'bot_stats.json');
define('BACKUP_DIR', $_ENV['BACKUP_DIR'] ?? 'backups/');

// ⚡ PERFORMANCE SETTINGS
define('CACHE_EXPIRY', (int) ($_ENV['CACHE_EXPIRY'] ?? '300')); // 5 minutes
define('ITEMS_PER_PAGE', (int) ($_ENV['ITEMS_PER_PAGE'] ?? '5'));
define('MAX_SEARCH_RESULTS', (int) ($_ENV['MAX_SEARCH_RESULTS'] ?? '10'));

// ✅ FEATURE TOGGLES
define('ENABLE_TYPING_INDICATOR', filter_var($_ENV['ENABLE_TYPING_INDICATOR'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('ENABLE_PUBLIC_CHANNELS', filter_var($_ENV['ENABLE_PUBLIC_CHANNELS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('ENABLE_PRIVATE_CHANNELS', filter_var($_ENV['ENABLE_PRIVATE_CHANNELS'] ?? 'false', FILTER_VALIDATE_BOOLEAN));
define('ENABLE_SMART_SUGGESTIONS', filter_var($_ENV['ENABLE_SMART_SUGGESTIONS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('LOG_FORWARDS', filter_var($_ENV['LOG_FORWARDS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('HIDE_PRIVATE_CHANNELS', filter_var($_ENV['HIDE_PRIVATE_CHANNELS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

// 🛠️ MAINTENANCE MODE
$MAINTENANCE_MODE = filter_var($_ENV['MAINTENANCE_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

// ============================================
// 📢 CHANNELS CONFIGURATION
// ============================================
$PUBLIC_CHANNELS = [];
$PRIVATE_CHANNELS = [];

// Sirf public channels add karo agar enable hai
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

// Sirf private channels add karo agar enable hai (OFF by default)
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

// Sabhi active channels
$ALL_CHANNELS = array_merge($PUBLIC_CHANNELS, $PRIVATE_CHANNELS);
$ACTIVE_CHANNELS = array_filter($ALL_CHANNELS, function($ch) {
    return $ch['active'] === true;
});

// ============================================
// 👥 GROUP CONFIGURATION
// ============================================
$GROUPS_CONFIG = [
    [
        'id' => REQUEST_GROUP_ID,
        'name' => '@EntertainmentTadka7860',
        'type' => 'request_only',  // Sirf /request commands handle karega
        'allow_searches' => false  // Yahan movies search nahi honge
    ]
];

// ============================================
// 💬 TYPING INDICATOR
// ============================================
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

// ============================================
// 📡 TELEGRAM API FUNCTIONS
// ============================================
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

function editMessage($chat_id, $message_obj, $new_text, $reply_markup = null, $parse_mode = 'HTML') {
    if (is_array($message_obj) && isset($message_obj['message_id'])) {
        $data = [
            'chat_id' => $chat_id,
            'message_id' => $message_obj['message_id'],
            'text' => $new_text,
            'disable_web_page_preview' => true
        ];
        if ($parse_mode) $data['parse_mode'] = $parse_mode;
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

// ============================================
// 📁 CSV FUNCTIONS (FORMAT LOCKED: movie_name,message_id,channel_id)
// ============================================
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

// CSV initialize karo
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
                    
                    // Movie name se index karo
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

function append_movie_safe($movie_name, $message_id, $channel_id = null) {
    if (empty(trim($movie_name))) {
        return false;
    }
    
    if ($channel_id === null) {
        $channel_id = MAIN_CHANNEL_ID;
    }
    
    // FILE LOCKING use karo race condition se bachne ke liye
    $fp = fopen(CSV_FILE, 'a');
    if (flock($fp, LOCK_EX)) {
        $entry = [$movie_name, $message_id, $channel_id];
        fputcsv($fp, $entry);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    
    // Cache update karo
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

// ============================================
// 📝 REQUEST SYSTEM FUNCTIONS
// ============================================
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
    
    // Date ke hisaab se sort karo (newest first)
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

// Requests file initialize karo
init_requests_file();

// ============================================
// 🛠️ HELPER FUNCTIONS
// ============================================
function is_private_channel_id($channel_id) {
    $private_channels = [
        PRIVATE_CHANNEL_1,
        PRIVATE_CHANNEL_2,
        PRIVATE_CHANNEL_3
    ];
    
    return in_array($channel_id, $private_channels);
}

function get_channel_display_name($channel_id) {
    if (HIDE_PRIVATE_CHANNELS && is_private_channel_id($channel_id)) {
        return '@EntertainmentTadka786'; // Private channel ka naam hide karo
    }
    
    // Channel IDs ko display names se map karo
    $channel_map = [
        MAIN_CHANNEL_ID => '@EntertainmentTadka786',
        THEATER_CHANNEL_ID => '@threater_print_movies',
        BACKUP_CHANNEL_ID => '@ETBackup',
        PRIVATE_CHANNEL_1 => 'Private Channel 1',
        PRIVATE_CHANNEL_2 => 'Private Channel 2',
        PRIVATE_CHANNEL_3 => 'Private Channel 3'
    ];
    
    return $channel_map[$channel_id] ?? 'Unknown Channel';
}

function get_group_info($chat_id) {
    global $GROUPS_CONFIG;
    
    foreach ($GROUPS_CONFIG as $group) {
        if ($group['id'] == $chat_id) {
            return $group;
        }
    }
    
    return null;
}

// ============================================
// 📤 FORWARDING FUNCTIONS
// ============================================
function deliver_item_to_chat($chat_id, $item) {
    // Typing indicator show karo
    sendTypingAction($chat_id);
    
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        $channel_id = $item['channel_id'] ?? MAIN_CHANNEL_ID;
        $movie_name = $item['movie_name'] ?? 'Unknown';
        
        // Check karo agar private channel hai
        $is_private_channel = is_private_channel_id($channel_id);
        
        // Agar private channel hai aur hum hide karna chahte hain
        if ($is_private_channel && HIDE_PRIVATE_CHANNELS) {
            // Private channel se secretly forward karo
            $actual_result = json_decode(forwardMessage($chat_id, $channel_id, $item['message_id']), true);
            
            if ($actual_result && isset($actual_result['ok']) && $actual_result['ok']) {
                // Safely forwarded from private channel
                // User ko lagega ki main channel se aaya hai
                error_log("SECRET: Forwarded '$movie_name' from private channel $channel_id");
                return true;
            } else {
                // Private channel fail hua, main channel try karo
                $channel_id = MAIN_CHANNEL_ID;
                error_log("FALLBACK: Private channel failed, trying main channel");
            }
        }
        
        // Check karo agar channel active hai
        if (!is_channel_active($channel_id)) {
            $channel_id = find_active_channel($channel_id);
        }
        
        // DEBUG: Channel access check karo
        $channel_check = json_decode(apiRequest('getChat', ['chat_id' => $channel_id]), true);
        if (!$channel_check || !$channel_check['ok']) {
            error_log("ERROR: Cannot access channel $channel_id. Trying main channel...");
            $channel_id = MAIN_CHANNEL_ID;
        }
        
        // Message forward karo
        $result = json_decode(forwardMessage($chat_id, $channel_id, $item['message_id']), true);
        
        if ($result && isset($result['ok']) && $result['ok']) {
            if (LOG_FORWARDS) {
                // Admin ke liye actual channel log karo, lekin user ko nahi dikhega
                log_forward($chat_id, $channel_id, $movie_name, true);
            }
            return true;
        } else {
            error_log("ERROR: Forward failed for '$movie_name' from $channel_id");
            
            // Fallback: copy message
            $copy_result = json_decode(copyMessage($chat_id, $channel_id, $item['message_id']), true);
            
            if ($copy_result && isset($copy_result['ok']) && $copy_result['ok']) {
                if (LOG_FORWARDS) {
                    log_forward($chat_id, $channel_id, $movie_name, false);
                }
                return true;
            } else {
                error_log("CRITICAL: Both forward and copy failed for '$movie_name'");
                
                // Ultimate fallback - channel links bhejo
                $fallback_msg = "🎬 <b>" . htmlspecialchars($movie_name) . "</b>\n\n";
                $fallback_msg .= "❌ <b>Channel se forward nahi kar paye!</b>\n\n";
                $fallback_msg .= "📢 <b>Channels join karo:</b>\n";
                $fallback_msg .= "🍿 Main: @EntertainmentTadka786\n";
                $fallback_msg .= "🎭 Theater: @threater_print_movies\n";
                $fallback_msg .= "🔒 Backup: @ETBackup";
                
                sendMessage($chat_id, $fallback_msg, null, 'HTML');
                return false;
            }
        }
    }
    
    // Fallback text message
    $text = "🎬 " . ($item['movie_name'] ?? 'Unknown') . "\n";
    $text .= "📢 Hamare channels join karo:\n";
    $text .= "🍿 Main: @EntertainmentTadka786\n";
    $text .= "🎭 Theater: @threater_print_movies\n";
    $text .= "🔒 Backup: @ETBackup\n";
    $text .= "📥 Requests: @EntertainmentTadka7860";
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
    
    // Pehle main channel try karo
    foreach ($ACTIVE_CHANNELS as $channel) {
        if ($channel['id'] == MAIN_CHANNEL_ID) {
            return MAIN_CHANNEL_ID;
        }
    }
    
    // Koi bhi active channel try karo
    foreach ($ACTIVE_CHANNELS as $channel) {
        if ($channel['id'] != $original_channel_id) {
            return $channel['id'];
        }
    }
    
    // Default main channel
    return MAIN_CHANNEL_ID;
}

function log_forward($user_id, $channel_id, $movie_name, $success) {
    $log_file = 'forward_logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FALLBACK';
    $channel_display = get_channel_display_name($channel_id);
    
    $log_entry = "$timestamp | User: $user_id | Channel: $channel_display ($channel_id) | Movie: $movie_name | Status: $status\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// ============================================
// 🔍 SMART SEARCH + SMART SUGGESTIONS SYSTEM
// ============================================

function normalize_movie_name($name) {
    $name = strtolower($name);
    $name = preg_replace('/\([^)]*\)/', '', $name); // Year hatao
    $name = preg_replace('/[^a-z0-9 ]/', ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

function get_channel_type_by_id($channel_id) {
    global $PUBLIC_CHANNELS, $PRIVATE_CHANNELS;
    
    foreach ($PUBLIC_CHANNELS as $ch) {
        if ($ch['id'] == $channel_id) return $ch['type'];
    }
    foreach ($PRIVATE_CHANNELS as $ch) {
        if ($ch['id'] == $channel_id) return $ch['type'];
    }
    return 'unknown';
}

function smart_search_improved($query) {
    global $movie_messages;
    
    $query_original = trim($query);
    $query_lower = strtolower($query_original);
    
    // KEYWORDS EXTRACT KARO
    $theater_keywords = ['theater', 'theatre', 'print', 'hdcam', 'camrip', 'hdrip', 'bluray'];
    $language_keywords = ['hindi', 'english', 'tamil', 'telugu', 'malayalam', 'marathi'];
    
    $is_theater = false;
    $language = '';
    
    // Theater check karo
    foreach ($theater_keywords as $kw) {
        if (strpos($query_lower, $kw) !== false) {
            $is_theater = true;
            $query_lower = str_replace($kw, '', $query_lower);
        }
    }
    
    // Language check karo
    foreach ($language_keywords as $lang) {
        if (strpos($query_lower, $lang) !== false) {
            $language = $lang;
            $query_lower = str_replace($lang, '', $query_lower);
        }
    }
    
    // Query clean karo
    $query_clean = normalize_movie_name($query_lower);
    
    if (empty($query_clean)) {
        return [];
    }
    
    $results = [];
    
    // DATABASE MEIN SEARCH KARO
    foreach ($movie_messages as $movie_key => $entries) {
        foreach ($entries as $item) {
            $item_name = $item['movie_name'] ?? '';
            $item_name_norm = normalize_movie_name($item_name);
            
            // Agar empty hai toh skip karo
            if (empty($item_name_norm)) continue;
            
            // FILTERS APPLY KARO
            if ($is_theater) {
                // Channel type check karo
                $channel_id = $item['channel_id'] ?? MAIN_CHANNEL_ID;
                $channel_type = get_channel_type_by_id($channel_id);
                if ($channel_type !== 'theater') continue;
            }
            
            if (!empty($language)) {
                $item_lower = strtolower($item_name);
                if (strpos($item_lower, $language) === false) continue;
            }
            
            // SCORING SYSTEM
            $score = 0;
            
            // Exact match
            if ($item_name_norm === $query_clean) {
                $score = 100;
            }
            // Contains match
            elseif (strpos($item_name_norm, $query_clean) !== false) {
                $score = 85 - (strlen($item_name_norm) - strlen($query_clean));
            }
            // Similar text
            else {
                similar_text($item_name_norm, $query_clean, $percent);
                if ($percent >= 60) {
                    $score = $percent;
                }
            }
            
            if ($score > 0) {
                $results[] = [
                    'score' => $score,
                    'item' => $item,
                    'name' => $item_name,
                    'channel_id' => $item['channel_id'] ?? MAIN_CHANNEL_ID
                ];
            }
        }
    }
    
    // SORT KARO AUR RETURN KARO
    usort($results, function($a, $b) {
        if ($b['score'] == $a['score']) {
            // Naye items pehle (message ID ke hisaab se)
            return ($b['item']['message_id'] ?? 0) <=> ($a['item']['message_id'] ?? 0);
        }
        return $b['score'] <=> $a['score'];
    });
    
    return array_slice($results, 0, MAX_SEARCH_RESULTS);
}

function build_smart_suggestions($query) {
    global $movie_messages;
    
    if (empty($movie_messages)) {
        return [];
    }
    
    $query_norm = normalize_movie_name($query);
    $suggestions = [];
    
    // Unique movie names collect karo
    $unique_movies = [];
    foreach ($movie_messages as $movie_key => $entries) {
        if (!empty($entries[0]['movie_name'])) {
            $movie_name = $entries[0]['movie_name'];
            $unique_movies[$movie_name] = $entries[0];
        }
    }
    
    // Har movie ko score karo
    foreach ($unique_movies as $movie_name => $item) {
        $movie_norm = normalize_movie_name($movie_name);
        
        if (empty($movie_norm) || empty($query_norm)) continue;
        
        // Quick filter: kam se kam ek word match hona chahiye
        $query_words = explode(' ', $query_norm);
        $movie_words = explode(' ', $movie_norm);
        
        $match_count = 0;
        foreach ($query_words as $q_word) {
            if (in_array($q_word, $movie_words)) {
                $match_count++;
            }
        }
        
        if ($match_count > 0) {
            similar_text($movie_norm, $query_norm, $percent);
            if ($percent >= 40) { // Suggestions ke liye lower threshold
                $suggestions[$movie_name] = [
                    'score' => $percent + ($match_count * 10),
                    'item' => $item,
                    'match_count' => $match_count
                ];
            }
        }
    }
    
    // Score ke hisaab se sort karo
    uasort($suggestions, function($a, $b) {
        if ($b['score'] == $a['score']) {
            return $b['match_count'] <=> $a['match_count'];
        }
        return $b['score'] <=> $a['score'];
    });
    
    // Top 6 return karo
    return array_slice(array_keys($suggestions), 0, 6);
}

function send_smart_suggestions($chat_id, $query, $search_results = []) {
    $suggestions = build_smart_suggestions($query);
    
    if (empty($suggestions) && empty($search_results)) {
        $msg = "🎬 <b>Movie Nahi Mili!</b>\n\n";
        $msg .= "🔍 <b>Search Kiya:</b> <code>" . htmlspecialchars($query) . "</code>\n\n";
        $msg .= "💡 <b>Suggestions:</b>\n";
        $msg .= "1️⃣ Spelling check karo\n";
        $msg .= "2️⃣ Short naam try karo\n";
        $msg .= "3️⃣ Year hatao (2024, 2025)\n";
        $msg .= "4️⃣ Language specify karo\n\n";
        $msg .= "📢 <b>Example searches:</b>\n";
        $msg .= "• <code>kgf</code>\n";
        $msg .= "• <code>pushpa hindi</code>\n";
        $msg .= "• <code>avengers english</code>\n\n";
        $msg .= "📥 <b>Request karo:</b> @EntertainmentTadka7860";
        
        sendMessage($chat_id, $msg, null, 'HTML');
        return;
    }
    
    // Agar search mein kuch results mile hain lekin user ko aur chahiye
    if (!empty($search_results) && count($suggestions) > 0) {
        $msg = "🔍 <b>Related Movies Mili:</b>\n\n";
        $msg .= "📝 <b>Aapne search kiya:</b> <code>" . htmlspecialchars($query) . "</code>\n\n";
        $msg .= "👇 <b>Similar movies:</b>\n";
        
        $buttons = [];
        foreach ($suggestions as $movie_name) {
            $short_name = (strlen($movie_name) > 35) 
                ? substr($movie_name, 0, 32) . '...' 
                : $movie_name;
            
            $buttons[] = [
                ['text' => "🎬 $short_name", 'callback_data' => 'suggest:' . urlencode($movie_name)]
            ];
        }
        
        // "Search Again" button add karo
        $buttons[] = [
            ['text' => '🔍 Dubara Search Karein', 'callback_data' => 'search_again'],
            ['text' => '❌ Cancel', 'callback_data' => 'cancel_suggest']
        ];
        
        sendMessage($chat_id, $msg, ['inline_keyboard' => $buttons], 'HTML');
    }
}

// ============================================
// 🔍 ADVANCED SEARCH FUNCTION
// ============================================
function advanced_search($chat_id, $query, $user_id = null) {
    global $movie_messages;
    
    sendTypingAction($chat_id);
    
    $q = trim($query);
    
    // Minimum length check
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Kam se kam 2 characters enter karein");
        return;
    }
    
    // Invalid keywords filter
    $invalid_keywords = [
        'vlc', 'audio', 'track', 'change', 'open', 'kar', 'me', 'hai',
        'how', 'what', 'problem', 'issue', 'help', 'solution', 'fix',
        'error', 'not working', 'download', 'play', 'video', 'sound',
        'subtitle', 'quality', 'hd', 'full', 'part', 'scene'
    ];
    
    $query_words = explode(' ', strtolower($q));
    $invalid_count = 0;
    foreach ($query_words as $word) {
        if (in_array($word, $invalid_keywords)) {
            $invalid_count++;
        }
    }
    
    if ($invalid_count > 0 && ($invalid_count / count($query_words)) > 0.5) {
        $help_msg = "🎬 Kripya movie ka naam enter karein!\n\n";
        $help_msg .= "🔍 Examples:\n";
        $help_msg .= "• kgf\n• pushpa\n• avengers\n• spider-man\n\n";
        $help_msg .= "❌ Technical queries mat likhein\n\n";
        $help_msg .= "📢 Join: @EntertainmentTadka786";
        sendMessage($chat_id, $help_msg, null, 'HTML');
        return;
    }
    
    // Smart search karo
    $results = smart_search_improved($q);
    
    if (!empty($results)) {
        // Searching message show karo
        $search_msg = "✅ <b>" . count($results) . " matches mil gaye!</b>\n\n";
        $search_msg .= "🔍 <b>Search:</b> <code>" . htmlspecialchars($q) . "</code>\n";
        $search_msg .= "📦 <b>Items forward ho rahe hain...</b>\n\n";
        $search_msg .= "⏳ Please wait...";
        
        $search_message = sendMessage($chat_id, $search_msg, null, 'HTML');
        
        // Items forward karo
        $sent_count = 0;
        foreach ($results as $result) {
            if (deliver_item_to_chat($chat_id, $result['item'])) {
                $sent_count++;
                usleep(200000); // 0.2s delay
            }
        }
        
        // Summary show karo
        $summary = "✅ <b>Search Complete!</b>\n\n";
        $summary .= "🔍 <b>Search:</b> <code>" . htmlspecialchars($q) . "</code>\n";
        $summary .= "📊 <b>Total matches:</b> " . count($results) . "\n";
        $summary .= "📤 <b>Successfully sent:</b> $sent_count\n\n";
        
        // Smart suggestions show karo agar enable hai
        if (ENABLE_SMART_SUGGESTIONS && count($results) > 0) {
            $summary .= "💡 <b>Aur related movies chahiye?</b>\n";
            $summary .= "Koi aur naam type karein ya ye try karein:\n";
            
            // Suggestions get karo
            $suggestions = build_smart_suggestions($q);
            if (!empty($suggestions)) {
                foreach ($suggestions as $index => $suggestion) {
                    if ($index < 3) { // Top 3 dikhao
                        $summary .= ($index + 1) . ". <code>" . htmlspecialchars($suggestion) . "</code>\n";
                    }
                }
            }
        }
        
        $summary .= "\n📢 <b>Join:</b> @EntertainmentTadka786";
        
        editMessage($chat_id, $search_message, $summary, null, 'HTML');
        
    } else {
        // Koi result nahi mila - suggestions show karo
        send_smart_suggestions($chat_id, $q);
    }
}

// ============================================
// 📝 REQUEST GROUP HANDLER
// ============================================
function handle_request_group($message) {
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    
    // SIRF REQUEST GROUP ke messages handle karo jo "/request" se start hote hain
    if ($chat_id == REQUEST_GROUP_ID && !empty($text) && strpos($text, '/request') === 0) {
        sendTypingAction($chat_id);
        
        $parts = explode(' ', $text);
        $movie_name = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
        
        if (empty($movie_name)) {
            $reply = "📝 <b>/request kaise use karein:</b>\n\n";
            $reply .= "Usage: <code>/request Movie Name</code>\n\n";
            $reply .= "Example: <code>/request KGF 3 hindi movie</code>\n\n";
            $reply .= "📢 Hum jaldi add kar denge!\n";
            $reply .= "🔍 Check: @EntertainmentTadka786";
            
            sendMessage($chat_id, $reply, null, 'HTML');
            return true;
        }
        
        $user_name = $message['from']['first_name'] ?? 'User';
        $user_id = $message['from']['id'] ?? 0;
        
        // Database mein save karo
        $request_id = save_movie_request($user_id, $user_name, $movie_name);
        
        $reply = "✅ <b>Request Receive Ho Gayi!</b>\n\n";
        $reply .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
        $reply .= "👤 <b>By:</b> $user_name\n";
        $reply .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n\n";
        $reply .= "Hum jaldi add kar denge!\n";
        $reply .= "Check: @EntertainmentTadka786";
        
        sendMessage($chat_id, $reply, null, 'HTML');
        
        // Request log karo
        $log = date('Y-m-d H:i:s') . " | User: $user_id ($user_name) | Request: $movie_name | ID: $request_id\n";
        file_put_contents('request_logs.txt', $log, FILE_APPEND);
        
        // Owner ko notify karo
        if (OWNER_ID) {
            $admin_msg = "📥 <b>Nayi Movie Request</b>\n\n";
            $admin_msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
            $admin_msg .= "📋 <b>Request ID:</b> $request_id\n";
            $admin_msg .= "👤 <b>User:</b> $user_name\n";
            $admin_msg .= "🆔 <b>User ID:</b> $user_id\n";
            $admin_msg .= "💬 <b>From:</b> Request Group\n";
            $admin_msg .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n\n";
            $admin_msg .= "📊 <b>Total pending requests:</b> " . get_pending_count();
            
            sendMessage(OWNER_ID, $admin_msg, null, 'HTML');
        }
        
        return true;
    }
    
    // REQUEST GROUP ke sabhi dusre messages (including movie names) ko handle MAT karo
    return false;
}

// ============================================
// 📄 PAGINATION FUNCTIONS
// ============================================
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
    
    // Name ke hisaab se sort karo
    usort($list, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $list;
}

function paginate_movies($all_movies, $page = 1) {
    $per_page = ITEMS_PER_PAGE;
    $total = count($all_movies);
    $total_pages = ceil($total / $per_page);
    
    // Page number validate karo
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
    
    // Current page ka button
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

// ============================================
// 📊 DATE STATISTICS
// ============================================
function get_movies_by_date() {
    $movies = load_movies_from_csv();
    $date_counts = [];
    
    // CSV mein dates nahi hain, isliye placeholder use karo
    $date_counts['Unknown'] = count($movies);
    
    return $date_counts;
}

// ============================================
// 📁 FILE INITIALIZATION
// ============================================
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

// ============================================
// 🚀 MAIN UPDATE PROCESSING
// ============================================
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    // CSV cache initialize karo
    get_cached_movies();
    
    // Maintenance mode check
    if ($MAINTENANCE_MODE && isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $msg = "🛠️ <b>Bot Maintenance Mode</b>\n\n";
        $msg .= "Hum temporarily unavailable hain.\n";
        $msg .= "Jaldi wapas aa jayenge!\n\n";
        $msg .= "Shukriya patience ke liye 🙏";
        sendMessage($chat_id, $msg, null, 'HTML');
        exit;
    }
    
    // Channel post handler
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        
        // Check karo agar kisi active channel se hai
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
                    append_movie_safe($text, $message_id, $chat_id);
                    
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
        
        // Typing indicator show karo
        sendTypingAction($chat_id);
        
        // Group type check karo
        $group_info = get_group_info($chat_id);
        
        if ($group_info) {
            // Yeh configured group hai
            if ($group_info['type'] == 'request_only') {
                // Request group - sirf /request commands handle karo
                if (strpos($text, '/request') === 0) {
                    handle_request_group($message);
                    exit;
                } else {
                    // REQUEST GROUP ke sabhi dusre messages (including movie names) ko
                    // NORMAL SEARCH ki tarah process karo
                    if (!empty(trim($text)) && strpos($text, '/') !== 0) {
                        // Yahan request group ke movie names process honge
                        advanced_search($chat_id, $text, $user_id);
                        exit;
                    }
                    // Dusre messages ignore karo
                    exit;
                }
            }
        }
        
        // Default processing for private chats and unconfigured groups
        // User stats update karo
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
        
        // Commands process karo
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = $parts[0];
            
            if ($command == '/start') {
                $welcome = "🎬 <b>Entertainment Tadka mein aapka swagat hai!</b>\n\n";
                $welcome .= "📢 <b>Bot kaise use karein:</b>\n";
                $welcome .= "• Kisi bhi movie ka naam type karein\n";
                $welcome .= "• English ya Hindi mein likh sakte hain\n";
                $welcome .= "• Partial names bhi kaam karte hain\n\n";
                $welcome .= "🔍 <b>Examples:</b>\n";
                $welcome .= "• Mandala Murders 2025\n";
                $welcome .= "• Lokah Chapter 1 Chandra 2025\n";
                $welcome .= "• Idli Kadai (2025)\n";
                $welcome .= "• IT - Welcome to Derry (2025) S01\n";
                $welcome .= "• hindi movie\n";
                $welcome .= "• kgf\n\n";
                $welcome .= "❌ <b>Na likhein:</b>\n";
                $welcome .= "• Technical questions\n";
                $welcome .= "• Player instructions\n";
                $welcome .= "• Non-movie queries\n\n";
                $welcome .= "📢 <b>Hamare Channels Join Karein:</b>\n";
                $welcome .= "🍿 Main: @EntertainmentTadka786\n";
                $welcome .= "📥 Requests: @EntertainmentTadka7860\n";
                $welcome .= "🎭 Theater Prints: @threater_print_movies\n";
                $welcome .= "🔒 Backup: @ETBackup\n\n";
                $welcome .= "💬 <b>Help chahiye?</b> /help use karein\n\n";
                $welcome .= "🔍 <b>Movie ka naam type karke start karein!</b>";
                
                sendMessage($chat_id, $welcome, null, 'HTML');
            }
            elseif ($command == '/help') {
                $help = "🤖 <b>Entertainment Tadka Bot - Complete Guide</b>\n\n";
                $help .= "📢 <b>Hamare Channels:</b>\n";
                $help .= "🍿 Main: @EntertainmentTadka786 - Latest movies\n";
                $help .= "📥 Requests: @EntertainmentTadka7860 - Support & requests\n";
                $help .= "🎭 Theater: @threater_print_movies - HD prints\n";
                $help .= "🔒 Backup: @ETBackup - Data protection\n\n";
                $help .= "🎯 <b>Search Commands:</b>\n";
                $help .= "• Bas movie ka naam type karein - Smart search\n\n";
                $help .= "📁 <b>Browse Commands:</b>\n";
                $help .= "• /totalupload - Saari movies dekhein\n\n";
                $help .= "📝 <b>Request Commands:</b>\n";
                $help .= "• /request movie - Movie request karein\n";
                $help .= "• /myrequests - Request status dekhein\n";
                $help .= "• @EntertainmentTadka7860 join karein support ke liye\n\n";
                $help .= "🔗 <b>Channel Commands:</b>\n";
                $help .= "• /mainchannel - Main channel info\n";
                $help .= "• /requestchannel - Requests channel\n";
                $help .= "• /theaterchannel - Theater prints\n";
                $help .= "• /backupchannel - Backup info\n\n";
                $help .= "💡 <b>Tip:</b> Bas koi bhi movie ka naam type karein search ke liye!";
                
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
                    $msg .= "Date information available nahi hai.\n";
                }
                
                $msg .= "\n📢 <b>Total Movies:</b> " . count(load_movies_from_csv()) . "\n";
                $msg .= "📊 <b>Last Updated:</b> " . date('d-m-Y H:i:s') . "\n\n";
                $msg .= "🎬 /totalupload use karein saari movies browse karne ke liye";
                
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
                        $msg .= "... aur " . (count($movies) - 5) . " aur\n";
                    }
                } else {
                    $msg .= "❌ <b>CSV mein koi movies nahi hain!</b>\n";
                }
                
                $msg .= "\n📢 /checkcsv all use karein complete list ke liye";
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/checkcsv') {
                $show_all = isset($parts[1]) && $parts[1] == 'all';
                $movies = load_movies_from_csv();
                
                if ($show_all) {
                    $msg = "📋 <b>CSV Mein Saari Movies</b>\n\n";
                    $msg .= "📊 <b>Total:</b> " . count($movies) . " movies\n\n";
                    
                    foreach ($movies as $index => $movie) {
                        $msg .= ($index + 1) . ". " . $movie['movie_name'] . "\n";
                        
                        // Break agar message bahut lamba ho jaye
                        if (strlen($msg) > 3500) {
                            $msg .= "... aur " . (count($movies) - $index - 1) . " aur";
                            break;
                        }
                    }
                } else {
                    $msg = "✅ <b>CSV Status</b>\n\n";
                    $msg .= "📊 <b>Total Movies:</b> " . count($movies) . "\n";
                    $msg .= "📄 <b>File:</b> " . CSV_FILE . "\n";
                    $msg .= "🔧 <b>Format:</b> " . CSV_FORMAT . "\n";
                    $msg .= "⏰ <b>Last Modified:</b> " . date('d-m-Y H:i:s', filemtime(CSV_FILE)) . "\n\n";
                    $msg .= "📝 <code>/checkcsv all</code> use karein saari movies dekhne ke liye";
                }
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/request') {
                $movie_name = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
                
                if (empty($movie_name)) {
                    $msg = "📝 <b>Movie request kaise karein:</b>\n\n";
                    $msg .= "Usage: <code>/request Movie Name</code>\n\n";
                    $msg .= "Example: <code>/request KGF 3 hindi movie</code>\n\n";
                    $msg .= "📢 Join: @EntertainmentTadka7860\n";
                    $msg .= "🔔 Hum notify kar denge jab add ho jayegi!";
                    
                    sendMessage($chat_id, $msg, null, 'HTML');
                } else {
                    // Request database mein save karo
                    $request_id = save_movie_request($user_id, $user_name, $movie_name);
                    
                    $msg = "✅ <b>Request Successfully Submit Ho Gayi!</b>\n\n";
                    $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
                    $msg .= "📋 <b>Request ID:</b> <code>$request_id</code>\n";
                    $msg .= "👤 <b>Requested by:</b> $user_name\n";
                    $msg .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n";
                    $msg .= "📊 <b>Status:</b> ⏳ Pending\n\n";
                    $msg .= "📢 Hum jaldi add kar denge!\n";
                    $msg .= "💬 Updates ke liye: @EntertainmentTadka7860\n";
                    $msg .= "🔍 Status check: /myrequests";
                    
                    sendMessage($chat_id, $msg, null, 'HTML');
                    
                    // Owner ko notify karo
                    $admin_msg = "📥 <b>Nayi Movie Request</b>\n\n";
                    $admin_msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
                    $admin_msg .= "📋 <b>Request ID:</b> $request_id\n";
                    $admin_msg .= "👤 <b>From:</b> $user_name\n";
                    $admin_msg .= "🆔 <b>User ID:</b> $user_id\n";
                    $admin_msg .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n\n";
                    $admin_msg .= "📊 <b>Total pending requests:</b> " . get_pending_count();
                    
                    sendMessage(OWNER_ID, $admin_msg, null, 'HTML');
                    
                    // User stats update karo
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
                    $msg = "📭 <b>Koi Requests Nahin Mili</b>\n\n";
                    $msg .= "Aapne abhi tak koi request nahin ki hai.\n\n";
                    $msg .= "🎬 <b>Movie request karne ke liye:</b>\n";
                    $msg .= "Use: <code>/request Movie Name</code>\n\n";
                    $msg .= "Example: <code>/request Avengers Endgame hindi</code>\n\n";
                    $msg .= "📢 Join: @EntertainmentTadka7860";
                    
                    sendMessage($chat_id, $msg, null, 'HTML');
                    return;
                }
                
                $msg = "📋 <b>Aapki Movie Requests</b>\n\n";
                
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
                $msg .= "📢 Updates ke liye: @EntertainmentTadka7860";
                
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
                $msg .= "• Sabhi movies ka backup\n";
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
                $msg .= "💡 <b>Movie ka naam type karke search karein!</b>";
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
        }
        elseif (!empty(trim($text))) {
            // Movie search karo
            advanced_search($chat_id, $text, $user_id);
            
            // Stats update karo
            $stats = json_decode(file_get_contents(STATS_FILE), true);
            $stats['total_searches'] = ($stats['total_searches'] ?? 0) + 1;
            $stats['last_updated'] = date('Y-m-d H:i:s');
            file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
            
            // User stats update karo
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
        
        // Pagination handle karo
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
            
            // Current page ki movies send karo
            forward_page_movies($chat_id, $pg['slice']);
            
            // Message update karo
            $msg = "✅ <b>Page $page send ho gayi</b>\n\n";
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
            $found = smart_search_improved($movie_name);
            
            if (!empty($found)) {
                $entries = $found[$movie_name]['entries'] ?? [];
                if (!empty($entries)) {
                    foreach ($entries as $entry) {
                        deliver_item_to_chat($chat_id, $entry);
                        usleep(200000);
                    }
                    
                    $msg = "✅ <b>Saare videos send ho gaye:</b> " . htmlspecialchars($movie_name) . "\n";
                    $msg .= "🎬 <b>Total videos:</b> " . count($entries) . "\n\n";
                    $msg .= "📢 Join: @EntertainmentTadka786";
                    
                    editMessage($chat_id, $message, $msg, null, 'HTML');
                }
            }
        }
        // Smart suggestions handle karo
        elseif (strpos($data, 'suggest:') === 0) {
            $movie_name = urldecode(str_replace('suggest:', '', $data));
            
            // Is movie ko search karo aur send karo
            $results = smart_search_improved($movie_name);
            
            if (!empty($results)) {
                // Suggestion message delete karo
                deleteMessage($chat_id, $message_id);
                
                // Saare matches send karo
                $sent_count = 0;
                foreach ($results as $result) {
                    if (deliver_item_to_chat($chat_id, $result['item'])) {
                        $sent_count++;
                        usleep(200000); // 0.2s delay
                    }
                }
                
                // Summary show karo
                $summary = "✅ <b>$sent_count items send ho gaye</b>\n\n";
                $summary .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
                $summary .= "📊 <b>Total matches:</b> " . count($results) . "\n";
                $summary .= "📤 <b>Successfully sent:</b> $sent_count\n\n";
                $summary .= "🔍 <b>Search again?</b> Koi aur movie ka naam type karein!";
                
                sendMessage($chat_id, $summary, null, 'HTML');
            }
        }
        elseif ($data === 'search_again') {
            // Naye search ke liye puchho
            deleteMessage($chat_id, $message_id);
            sendMessage($chat_id, "🔍 <b>Movie ka naam type karein search karne ke liye:</b>\n\nExample: <code>kgf hindi</code>", null, 'HTML');
        }
        elseif ($data === 'cancel_suggest') {
            deleteMessage($chat_id, $message_id);
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

// ============================================
// 📄 PAGINATION CONTROLLER
// ============================================
function totalupload_controller($chat_id, $page = 1) {
    $all = get_all_movies_list();
    if (empty($all)) {
        $msg = "📭 <b>Koi Movies Nahin Mili!</b>\n\n";
        $msg .= "🎬 Database empty hai\n";
        $msg .= "📢 Channels mein movies add karein\n";
        $msg .= "💬 Join: @EntertainmentTadka7860";
        sendMessage($chat_id, $msg, null, 'HTML');
        return;
    }
    
    $pg = paginate_movies($all, (int)$page);
    
    // Current page ki movies forward karo
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
    
    $title .= "\n📍 <b>Navigation:</b> Neeche ke buttons use karein";
    $title .= "\n📢 <b>Join:</b> @EntertainmentTadka786";
    
    $kb = build_totalupload_keyboard($pg['page'], $pg['total_pages']);
    sendMessage($chat_id, $title, $kb, 'HTML');
}

// ============================================
// 🌐 WEBHOOK SETUP
// ============================================
if (php_sapi_name() === 'cli' || isset($_GET['setwebhook'])) {
    $webhook_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                   "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    $result_data = json_decode($result, true);
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>🚀 Webhook Setup - Entertainment Tadka</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .success { color: green; padding: 10px; background: #d4edda; border-radius: 5px; }
            .error { color: red; padding: 10px; background: #f8d7da; border-radius: 5px; }
            .info { color: blue; padding: 10px; background: #d1ecf1; border-radius: 5px; }
            ul { line-height: 1.8; }
            .btn { display: inline-block; padding: 10px 20px; background: #0088cc; color: white; 
                   text-decoration: none; border-radius: 5px; margin: 5px; }
        </style>
    </head>
    <body>
        <h1>🚀 Entertainment Tadka Bot Webhook Setup</h1>
        
        <h2>📊 Configuration Status:</h2>";
    
    if (isset($result_data['ok']) && $result_data['ok']) {
        echo "<div class='success'>✅ Webhook successfully set!</div>";
    } else {
        echo "<div class='error'>❌ Webhook setup failed</div>";
        echo "<p><em>Error details hidden for security</em></p>";
    }
    
    echo "<div class='info'>
            <h3>✅ Active Channels (" . count($ACTIVE_CHANNELS) . "):</h3>
            <ul>";
    
    foreach ($ACTIVE_CHANNELS as $channel) {
        echo "<li>{$channel['name']} ({$channel['id']})</li>";
    }
    
    echo "</ul>
        </div>
        
        <h3>❌ Inactive Channels (" . count($PRIVATE_CHANNELS) . "):</h3>
        <p><em>Private channels disabled hain configuration mein</em></p>
        
        <h3>👥 Request Group:</h3>
        <p>@EntertainmentTadka7860 ($REQUEST_GROUP_ID)</p>
        
        <h3>📁 CSV Format (LOCKED):</h3>
        <p><code>" . CSV_FORMAT . "</code></p>
        
        <h3>🎯 Next Steps:</h3>
        <ol>
            <li>Bot ko sabhi channels mein admin banao</li>
            <li>Movie search test karo</li>
            <li>Request group functionality test karo</li>
            <li>Theater channel forwarding test karo</li>
        </ol>
        
        <h3>🔍 Test Links:</h3>
        <a href='check_config.php' class='btn'>🔧 Check Configuration</a>
        <a href='migrate_csv.php' class='btn'>🔄 Migrate CSV</a>
        <a href='logs.php' class='btn'>📝 View Logs</a>
        
        <h3>📞 Support:</h3>
        <p>Contact: @EntertainmentTadka7860</p>
    </body>
    </html>";
    
    exit;
}

// ============================================
// 🏠 DEFAULT PAGE
// ============================================
if (php_sapi_name() !== 'cli' && !isset($_GET['setwebhook']) && empty($update)) {
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
            <p><strong>CSV Format:</strong> " . CSV_FORMAT . " (LOCKED)</p>
            <p><strong>Smart Suggestions:</strong> " . (ENABLE_SMART_SUGGESTIONS ? 'ON' : 'OFF') . "</p>
            <p><strong>Typing Indicator:</strong> " . (ENABLE_TYPING_INDICATOR ? 'ON' : 'OFF') . "</p>
            <p><strong>Hide Private Channels:</strong> " . (HIDE_PRIVATE_CHANNELS ? 'ON' : 'OFF') . "</p>
        </div>
        
        <div class='status warning'>
            <h3>🔧 Configuration Required</h3>
            <p>1. .env file edit karo aur BOT_TOKEN add karo</p>
            <p>2. Webhook set karo using button below</p>
            <p>3. Bot ko channels mein admin banao</p>
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
        <p><em>Private channels disabled hain configuration mein</em></p>
        
        <h3>💬 Request Group:</h3>
        <p>@EntertainmentTadka7860 ($REQUEST_GROUP_ID)</p>
        
        <h3>🔧 Group Behavior:</h3>
        <ul>
            <li><strong>Request Group:</strong> Sirf /request commands handle hongi</li>
            <li><strong>Other Groups:</strong> Movie names search & forwarding trigger karenge</li>
            <li><strong>Private Chats:</strong> Full functionality available hai</li>
        </ul>
        
        <h3>🎯 Features:</h3>
        <ul>
            <li>Smart Search with suggestions</li>
            <li>Theater prints filtering</li>
            <li>Language filtering (Hindi/English)</li>
            <li>Pagination for browsing</li>
            <li>Request system</li>
            <li>Auto-backup system</li>
        </ul>
        
        <hr>
        <p><strong>📞 Support:</strong> @EntertainmentTadka7860</p>
        <p><strong>📢 Main Channel:</strong> @EntertainmentTadka786</p>
        <p><em>Entertainment Tadka - Best Movie Collection</em></p>
    </body>
    </html>";
}
?>
