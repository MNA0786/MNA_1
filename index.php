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

// Private Channels
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
define('HIDE_PRIVATE_CHANNELS', true);
define('MAX_MOVIES_PER_BATCH', 10);

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

// ==================== AUTO-CORRECT SYSTEM ====================
function auto_correct_suggestion($query) {
    $common_typos = [
        // KGF Series
        'kgf' => ['kfg', 'kgff', 'k.g.f', 'k g f', 'kgf2', 'kgf 2'],
        'kgf chapter 1' => ['kgf1', 'kgf 1', 'kgf part 1'],
        'kgf chapter 2' => ['kgf2', 'kgf 2', 'kgf part 2', 'kgfchapter2'],
        
        // Pushpa Series
        'pushpa' => ['pushpaa', 'pushpah', 'pushpa 1', 'pushpa1'],
        'pushpa 2' => ['pushpa2', 'pushpa the rule', 'pushpa 2 the rule'],
        
        // Avengers Series
        'avengers' => ['avangers', 'avnegers', 'avenegers', 'avenger'],
        'avengers endgame' => ['endgame', 'avengers end game', 'avenger endgame'],
        'avengers infinity war' => ['infinity war', 'avenger infinity war'],
        
        // Spider-Man Series
        'spider-man' => ['spiderman', 'spider man', 'spider man'],
        'spider-man no way home' => ['no way home', 'spiderman no way home'],
        
        // Bollywood Movies
        'animal' => ['anymal', 'animaal', 'anima'],
        'jawan' => ['jwaan', 'jvan', 'jawaan'],
        'pathaan' => ['patan', 'pathan', 'pathaan'],
        'dunki' => ['dunkki', 'dunkie', 'dunkee'],
        
        // South Indian Movies
        'salaar' => ['salaaar', 'salaar part 1', 'salaar1'],
        'rrr' => ['rr', 'r r r', 'rrr movie'],
        'baahubali' => ['bahubali', 'baahubali 1', 'baahubali1'],
        'baahubali 2' => ['bahubali 2', 'baahubali2', 'bahubali2'],
        
        // Common Hindi Words
        'hindi' => ['hindhi', 'hindi movie', 'hindimovie'],
        'english' => ['englis', 'inglish', 'english movie'],
        'tamil' => ['tamill', 'thamizh', 'tamil movie'],
        'telugu' => ['telugoo', 'telugu movie'],
        
        // Quality Terms
        'hd' => ['high definition', 'h d', 'hd movie'],
        '720p' => ['720', '720 p', '720p hd'],
        '1080p' => ['1080', '1080 p', '1080p full hd'],
        '4k' => ['4 k', '4k ultra hd', 'four k'],
        
        // Year Terms
        '2025' => ['2024', '2026', '2023'],
        '2024' => ['2023', '2025', '2024 movie'],
        
        // Theater Prints
        'theater print' => ['theatre print', 'theaterprint', 'theatreprint'],
        'bluray' => ['blue ray', 'blu-ray', 'blueray'],
    ];
    
    $query_lower = strtolower(trim($query));
    
    // Check for exact typos
    foreach ($common_typos as $correct => $typos) {
        if (in_array($query_lower, $typos)) {
            return $correct;
        }
    }
    
    // Check for partial matches
    foreach ($common_typos as $correct => $typos) {
        foreach ($typos as $typo) {
            if (strpos($query_lower, $typo) !== false) {
                return $correct;
            }
        }
    }
    
    // Check for similar words using levenshtein distance
    $all_words = array_keys($common_typos);
    $best_match = '';
    $best_distance = PHP_INT_MAX;
    
    foreach ($all_words as $word) {
        $distance = levenshtein($query_lower, $word);
        if ($distance < 3 && $distance < $best_distance) {
            $best_distance = $distance;
            $best_match = $word;
        }
    }
    
    if ($best_match && $best_distance <= 2) {
        return $best_match;
    }
    
    return false;
}

function get_did_you_mean_message($original, $corrected) {
    $messages = [
        "🤔 <b>Did you mean:</b> <code>$corrected</code>?",
        "💡 <b>Auto-correct:</b> <code>$corrected</code>",
        "🔍 <b>Searching for:</b> <code>$corrected</code> (instead of '$original')",
        "🎯 <b>Suggested:</b> <code>$corrected</code>",
        "✨ <b>Corrected to:</b> <code>$corrected</code>"
    ];
    
    return $messages[array_rand($messages)];
}

// ==================== RELATED MOVIES SYSTEM ====================
function get_related_movies($movie_name) {
    $related_map = [
        // KGF Series
        'kgf' => ['KGF Chapter 1', 'KGF Chapter 2', 'Salaar', 'Ugramm', 'Mufti'],
        'kgf chapter 1' => ['KGF Chapter 2', 'Salaar', 'Ugramm'],
        'kgf chapter 2' => ['KGF Chapter 1', 'Salaar', 'Kabzaa'],
        
        // Pushpa Series
        'pushpa' => ['Pushpa 2: The Rule', 'KGF', 'RRR', 'Baahubali'],
        'pushpa 2' => ['Pushpa', 'RRR', 'KGF'],
        
        // Avengers Series
        'avengers' => ['Avengers: Age of Ultron', 'Avengers: Infinity War', 'Avengers: Endgame', 
                      'Iron Man', 'Captain America', 'Thor'],
        'avengers endgame' => ['Avengers: Infinity War', 'Spider-Man: No Way Home', 'Captain Marvel'],
        'avengers infinity war' => ['Avengers: Endgame', 'Black Panther', 'Doctor Strange'],
        
        // Spider-Man Series
        'spider-man' => ['Spider-Man 2', 'Spider-Man 3', 'Spider-Man: No Way Home', 
                        'The Amazing Spider-Man', 'Avengers'],
        'spider-man no way home' => ['Spider-Man: Far From Home', 'Doctor Strange', 'Avengers'],
        
        // Bollywood Movies
        'animal' => ['Kabir Singh', 'Arjun Reddy', 'Sultan', 'Dangal'],
        'jawan' => ['Pathaan', 'Dunki', 'Fighter', 'Tiger Zinda Hai'],
        'pathaan' => ['Jawan', 'Tiger Zinda Hai', 'War', 'Dhoom 3'],
        'dunki' => ['PK', '3 Idiots', 'Sanju', 'Bajrangi Bhaijaan'],
        
        // South Indian Movies
        'salaar' => ['KGF', 'Ugramm', 'Vikrant Rona', 'Kabzaa'],
        'rrr' => ['Baahubali', 'Magadheera', 'Eega', 'Bahubali'],
        'baahubali' => ['RRR', 'Magadheera', 'Eega', 'Bahubali 2'],
        'baahubali 2' => ['Baahubali', 'RRR', 'Kantara'],
        
        // Hollywood Series
        'fast and furious' => ['Fast X', 'Fast & Furious 9', 'Hobbs & Shaw', 'Transformers'],
        'mission impossible' => ['Mission: Impossible 7', 'James Bond', 'Bourne Identity', 'Jack Reacher'],
        'john wick' => ['John Wick 4', 'Extraction', 'The Equalizer', 'Nobody'],
        
        // Horror Movies
        'conjuring' => ['Annabelle', 'The Nun', 'Insidious', 'Sinister'],
        'it' => ['It Chapter Two', 'The Shining', 'Carrie', 'Pet Sematary'],
        
        // Animated Movies
        'frozen' => ['Frozen 2', 'Moana', 'Tangled', 'Zootopia'],
        'avatar' => ['Avatar 2', 'Avengers', 'Guardians of the Galaxy', 'Star Wars'],
        
        // By Actor/Director
        'prabhas' => ['Salaar', 'Baahubali', 'Radhe Shyam', 'Adipurush'],
        'yash' => ['KGF', 'Googly', 'Mr. and Mrs. Ramachari', 'Masterpiece'],
        'allu arjun' => ['Pushpa', 'Ala Vaikunthapurramuloo', 'DJ', 'Julayi'],
        'rajamouli' => ['RRR', 'Baahubali', 'Magadheera', 'Eega'],
        'srk' => ['Jawan', 'Pathaan', 'Dunki', 'Chennai Express'],
        'salman khan' => ['Tiger Zinda Hai', 'Bajrangi Bhaijaan', 'Sultan', 'Dabangg'],
        
        // By Genre
        'comedy' => ['Golmaal', 'Hera Pheri', 'Dhamaal', 'Welcome'],
        'action' => ['War', 'Tiger Zinda Hai', 'Singham', 'Dabangg'],
        'romance' => ['Love Aaj Kal', 'Jab We Met', 'Hum Tum', 'Dilwale Dulhania Le Jayenge'],
        'thriller' => ['Drishyam', 'Kahaani', 'Andhadhun', 'Badla'],
    ];
    
    $movie_lower = strtolower(trim($movie_name));
    
    // Check exact match
    if (isset($related_map[$movie_lower])) {
        return $related_map[$movie_lower];
    }
    
    // Check partial match
    foreach ($related_map as $key => $movies) {
        if (strpos($movie_lower, $key) !== false || strpos($key, $movie_lower) !== false) {
            return $movies;
        }
    }
    
    // If no specific match, return popular movies
    return ['KGF Chapter 2', 'Pushpa 2', 'Jawan', 'Animal', 'Salaar'];
}

function get_related_movies_message($movie_name, $related_movies) {
    if (empty($related_movies)) {
        return '';
    }
    
    $messages = [
        "🎬 <b>You searched \"$movie_name\", you might also like:</b>",
        "🔥 <b>Related to \"$movie_name\":</b>",
        "📚 <b>If you liked \"$movie_name\", try these:</b>",
        "💡 <b>Similar to \"$movie_name\":</b>",
        "🌟 <b>Recommended with \"$movie_name\":</b>"
    ];
    
    $message = $messages[array_rand($messages)] . "\n\n";
    
    $shuffled = $related_movies;
    shuffle($shuffled);
    $selected = array_slice($shuffled, 0, min(5, count($shuffled)));
    
    foreach ($selected as $index => $movie) {
        $message .= ($index + 1) . ". <code>$movie</code>\n";
    }
    
    $message .= "\n💡 <b>Tip:</b> Just type any movie name to search!";
    
    return $message;
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

// ==================== CHANNEL HELPER FUNCTIONS ====================
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
        // Show as main channel to user
        return '@EntertainmentTadka786';
    }
    
    // Map channel IDs to display names
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

// ==================== FORWARDING FUNCTIONS ====================
function deliver_item_to_chat($chat_id, $item) {
    // Show typing indicator
    sendTypingAction($chat_id);
    
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        $channel_id = $item['channel_id'] ?? MAIN_CHANNEL_ID;
        $movie_name = $item['movie_name'] ?? 'Unknown';
        
        // DEBUG: Log which channel we're trying to forward from
        error_log("DEBUG: Attempting to forward movie '$movie_name' from channel: $channel_id");
        
        // Check if it's a private channel
        $is_private_channel = is_private_channel_id($channel_id);
        
        // If private channel and we want to hide it, use main channel for forwarding
        if ($is_private_channel && HIDE_PRIVATE_CHANNELS) {
            // Log for admin
            error_log("SECRET: Forwarding '$movie_name' from private channel $channel_id " . 
                     "but showing as from main channel");
            
            // Forward from private channel but user won't know
            $actual_result = json_decode(forwardMessage($chat_id, $channel_id, $item['message_id']), true);
            
            if ($actual_result && isset($actual_result['ok']) && $actual_result['ok']) {
                // Successfully forwarded from private channel
                // User thinks it's from main channel
                error_log("SUCCESS: Secret forward from private channel $channel_id");
                return true;
            } else {
                // Private channel failed, try main channel
                $channel_id = MAIN_CHANNEL_ID;
                error_log("FALLBACK: Private channel failed, trying main channel");
            }
        }
        
        // Check if channel exists and bot has access
        $channel_check = json_decode(apiRequest('getChat', ['chat_id' => $channel_id]), true);
        
        if (!$channel_check || !$channel_check['ok']) {
            error_log("ERROR: Cannot access channel $channel_id. Error: " . 
                     ($channel_check['description'] ?? 'Unknown'));
            
            // Try alternative channels
            if ($channel_id == THEATER_CHANNEL_ID) {
                error_log("INFO: Theater channel failed, trying main channel");
                $channel_id = MAIN_CHANNEL_ID;
            } elseif ($channel_id == BACKUP_CHANNEL_ID) {
                error_log("INFO: Backup channel failed, trying main channel");
                $channel_id = MAIN_CHANNEL_ID;
            }
        }
        
        // Forward the message
        $result = json_decode(forwardMessage($chat_id, $channel_id, $item['message_id']), true);
        
        if ($result && isset($result['ok']) && $result['ok']) {
            error_log("SUCCESS: Forwarded '$movie_name' from channel $channel_id to user $chat_id");
            
            if (LOG_FORWARDS) {
                log_forward($chat_id, $channel_id, $movie_name, true);
            }
            return true;
        } else {
            error_log("ERROR: Forward failed for '$movie_name' from $channel_id. Result: " . 
                     json_encode($result));
            
            // Try copyMessage as fallback
            $copy_result = json_decode(copyMessage($chat_id, $channel_id, $item['message_id']), true);
            
            if ($copy_result && isset($copy_result['ok']) && $copy_result['ok']) {
                error_log("SUCCESS: Used copyMessage for '$movie_name' from $channel_id");
                
                if (LOG_FORWARDS) {
                    log_forward($chat_id, $channel_id, $movie_name, false);
                }
                return true;
            } else {
                error_log("CRITICAL: Both forward and copy failed for '$movie_name'");
                
                // Ultimate fallback - send channel links
                $fallback_msg = "🎬 <b>" . htmlspecialchars($movie_name) . "</b>\n\n";
                $fallback_msg .= "❌ <b>Could not forward from channel!</b>\n\n";
                $fallback_msg .= "📢 <b>Join our channels to access:</b>\n";
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
    $text .= "📢 Join our channels:\n";
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
    
    $original_query = trim($query);
    $q = strtolower($original_query);
    
    // Minimum length check
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters for search");
        return;
    }
    
    // ========== AUTO-CORRECT SYSTEM ==========
    $corrected_query = auto_correct_suggestion($q);
    $was_corrected = false;
    
    if ($corrected_query && $corrected_query != $q) {
        $was_corrected = true;
        $correction_msg = get_did_you_mean_message($original_query, $corrected_query);
        $search_msg = "$correction_msg\n\n";
        $search_msg .= "⏳ <b>Searching...</b>";
        
        $search_message = sendMessage($chat_id, $search_msg, null, 'HTML');
        $q = $corrected_query;
    }
    
    // ========== SMART SEARCH ==========
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
        
        // Show searching message with ALL public channels
        if (!$was_corrected) {
            $search_msg = "✅ <b>'" . htmlspecialchars($original_query) . "' ke $total_movies items ka info mil gaya!</b>\n\n";
            $search_msg .= "📢 <b>Join our channels:</b>\n";
            $search_msg .= "🍿 Main: @EntertainmentTadka786\n";
            $search_msg .= "🎭 Theater: @threater_print_movies\n";
            $search_msg .= "🔒 Backup: @ETBackup\n\n";
            $search_msg .= "⏳ <b>Forwarding...</b>";
            
            $search_message = sendMessage($chat_id, $search_msg, null, 'HTML');
        }
        
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
        
        // ========== RELATED MOVIES SUGGESTION ==========
        $related_movies = get_related_movies($q);
        $related_message = '';
        
        if (count($related_movies) > 0 && $forwarded_count > 0) {
            $related_message = "\n\n" . get_related_movies_message($original_query, $related_movies);
        }
        
        // Show summary with ALL channels
        $summary_msg = "✅ <b>Search Complete!</b>\n\n";
        
        if ($was_corrected) {
            $summary_msg .= "💡 <b>Auto-corrected from:</b> '$original_query'\n";
            $summary_msg .= "🔍 <b>Searched for:</b> '$corrected_query'\n";
        } else {
            $summary_msg .= "🔍 <b>Search:</b> " . htmlspecialchars($original_query) . "\n";
        }
        
        $summary_msg .= "🎬 <b>Matches found:</b> " . count($found) . "\n";
        $summary_msg .= "📹 <b>Videos forwarded:</b> $forwarded_count\n";
        
        // Show match list
        if (count($found) > 1) {
            $summary_msg .= "\n📋 <b>All matches:</b>\n";
            $match_num = 1;
            foreach ($found as $movie_name => $data) {
                $summary_msg .= "$match_num. $movie_name (" . $data['count'] . " videos)\n";
                $match_num++;
                if ($match_num > 5) {
                    $summary_msg .= "... and " . (count($found) - 5) . " more\n";
                    break;
                }
            }
        }
        
        // Add ALL public channels in summary
        $summary_msg .= "\n📢 <b>Join our channels:</b>\n";
        $summary_msg .= "🍿 Main: @EntertainmentTadka786\n";
        $summary_msg .= "🎭 Theater: @threater_print_movies\n";
        $summary_msg .= "🔒 Backup: @ETBackup\n";
        $summary_msg .= "📥 Requests: @EntertainmentTadka7860";
        
        // Add related movies if any
        $summary_msg .= $related_message;
        
        // Edit the original search message
        editMessage($chat_id, $search_message, $summary_msg, null, 'HTML');
        
    } else {
        // ========== NO RESULTS - SUGGEST ALTERNATIVES ==========
        $related_movies = get_related_movies($q);
        $suggestion_msg = '';
        
        if (count($related_movies) > 0) {
            $suggestion_msg = "\n\n" . get_related_movies_message($original_query, $related_movies);
        }
        
        $msg = "😔 <b>Movie Not Found!</b>\n\n";
        $msg .= "🎬 <b>Requested:</b> " . htmlspecialchars($original_query) . "\n\n";
        
        // Check for auto-correct even in not found case
        $corrected = auto_correct_suggestion($q);
        if ($corrected && $corrected != $q) {
            $msg .= "💡 <b>Did you mean:</b> <code>$corrected</code>?\n\n";
        }
        
        $msg .= "📝 <b>Request it here:</b>\n";
        $msg .= "@EntertainmentTadka7860\n\n";
        $msg .= "🔔 <b>I'll notify you when it's added!</b>\n\n";
        $msg .= "📢 <b>Join our channels:</b>\n";
        $msg .= "🍿 Main: @EntertainmentTadka786\n";
        $msg .= "🎭 Theater: @threater_print_movies\n";
        $msg .= "🔒 Backup: @ETBackup";
        
        // Add suggestions
        $msg .= $suggestion_msg;
        
        sendMessage($chat_id, $msg, null, 'HTML');
    }
}

// ==================== PAGINATION FUNCTIONS ====================
function get_all_movies_list() {
    $movies = load_movies_from_csv();
    $list = [];
    
    if (empty($movies)) {
        error_log("ERROR: No movies found in CSV!");
        return [];
    }
    
    foreach ($movies as $movie) {
        // Validate required fields
        if (empty($movie['movie_name']) || empty($movie['message_id'])) {
            error_log("WARNING: Skipping movie with missing fields: " . json_encode($movie));
            continue;
        }
        
        $list[] = [
            'name' => $movie['movie_name'],
            'message_id' => $movie['message_id'],
            'channel_id' => $movie['channel_id'] ?? MAIN_CHANNEL_ID
        ];
    }
    
    // Sort by name (case-insensitive)
    usort($list, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    error_log("DEBUG: get_all_movies_list() returned " . count($list) . " movies");
    return $list;
}

function paginate_movies($all_movies, $page = 1) {
    $per_page = ITEMS_PER_PAGE;
    $total = count($all_movies);
    
    if ($total === 0) {
        error_log("ERROR: paginate_movies() received empty array!");
        return [
            'slice' => [],
            'page' => 1,
            'total_pages' => 1,
            'total' => 0,
            'per_page' => $per_page
        ];
    }
    
    $total_pages = ceil($total / $per_page);
    
    // Validate page number
    if ($page < 1) $page = 1;
    if ($page > $total_pages) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    $slice = array_slice($all_movies, $offset, $per_page);
    
    error_log("DEBUG: Pagination - Page: $page, Total: $total, Pages: $total_pages, Showing: " . count($slice));
    
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
        // Single page - no navigation needed
        $keyboard[] = [
            ['text' => '📤 Send This Page', 'callback_data' => 'tu_view_' . $current_page],
            ['text' => '❌ Close', 'callback_data' => 'tu_stop']
        ];
        
        return ['inline_keyboard' => $keyboard];
    }
    
    // Navigation row
    $nav_row = [];
    
    if ($current_page > 1) {
        $nav_row[] = ['text' => '◀️ Previous', 'callback_data' => 'tu_prev_' . ($current_page - 1)];
    }
    
    $nav_row[] = ['text' => "📄 $current_page/$total_pages", 'callback_data' => 'current_page'];
    
    if ($current_page < $total_pages) {
        $nav_row[] = ['text' => 'Next ▶️', 'callback_data' => 'tu_next_' . ($current_page + 1)];
    }
    
    $keyboard[] = $nav_row;
    
    // Action row
    $action_row = [
        ['text' => '📤 Send This Page', 'callback_data' => 'tu_view_' . $current_page]
    ];
    
    if ($current_page > 1) {
        $action_row[] = ['text' => '⏮️ First Page', 'callback_data' => 'tu_prev_1'];
    }
    
    $action_row[] = ['text' => '❌ Close', 'callback_data' => 'tu_stop'];
    
    $keyboard[] = $action_row;
    
    // Jump to page row (for many pages)
    if ($total_pages > 5) {
        $jump_row = [];
        $steps = [1, round($total_pages/4), round($total_pages/2), round($total_pages*3/4), $total_pages];
        $steps = array_unique($steps);
        
        foreach ($steps as $step) {
            if ($step != $current_page && $step >= 1 && $step <= $total_pages) {
                $jump_row[] = ['text' => "📄 $step", 'callback_data' => 'tu_prev_' . $step];
            }
        }
        
        if (!empty($jump_row)) {
            $keyboard[] = $jump_row;
        }
    }
    
    return ['inline_keyboard' => $keyboard];
}

function forward_page_movies($chat_id, $movies) {
    if (empty($movies)) {
        error_log("ERROR: forward_page_movies() received empty movies array!");
        return 0;
    }
    
    // Limit batch size
    if (count($movies) > MAX_MOVIES_PER_BATCH) {
        $movies = array_slice($movies, 0, MAX_MOVIES_PER_BATCH);
        error_log("WARNING: Limiting batch to " . MAX_MOVIES_PER_BATCH . " movies");
    }
    
    $forwarded_count = 0;
    $total_movies = count($movies);
    
    foreach ($movies as $index => $movie) {
        if (empty($movie['name']) || empty($movie['message_id'])) {
            error_log("WARNING: Skipping invalid movie at index $index: " . json_encode($movie));
            continue;
        }
        
        // Prepare item for deliver_item_to_chat
        $item = [
            'movie_name' => $movie['name'],
            'message_id' => $movie['message_id'],
            'channel_id' => $movie['channel_id'] ?? MAIN_CHANNEL_ID
        ];
        
        // Forward the movie
        $success = deliver_item_to_chat($chat_id, $item);
        
        if ($success) {
            $forwarded_count++;
            error_log("SUCCESS: Forwarded movie '{$movie['name']}' to user $chat_id");
        } else {
            error_log("ERROR: Failed to forward movie '{$movie['name']}' to user $chat_id");
        }
        
        // Progress indicator for large batches
        if ($total_movies > 5 && $index % 3 === 0) {
            $progress = round(($index + 1) / $total_movies * 100);
            error_log("PROGRESS: $progress% - $forwarded_count/$total_movies forwarded");
        }
        
        // Rate limiting
        usleep(500000); // 0.5 second delay between forwards
    }
    
    error_log("DEBUG: forward_page_movies() forwarded $forwarded_count out of " . count($movies) . " movies");
    return $forwarded_count;
}

function totalupload_controller($chat_id, $page = 1) {
    // Show typing indicator
    sendTypingAction($chat_id);
    
    $all = get_all_movies_list();
    
    if (empty($all)) {
        $msg = "📭 <b>No Movies Found!</b>\n\n";
        $msg .= "🎬 Database is currently empty\n";
        $msg .= "📢 Add movies to channels\n";
        $msg .= "💬 Join: @EntertainmentTadka7860 for requests\n\n";
        $msg .= "🔧 <b>Check CSV file:</b> " . CSV_FILE;
        
        sendMessage($chat_id, $msg, null, 'HTML');
        
        error_log("ERROR: totalupload_controller() - No movies found in database");
        return;
    }
    
    $pg = paginate_movies($all, (int)$page);
    
    // Send initial message
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
    $message = sendMessage($chat_id, $title, $kb, 'HTML');
    
    // Store message ID for callback handling
    if ($message && isset($message['result']['message_id'])) {
        $message_id = $message['result']['message_id'];
        error_log("DEBUG: Sent totalupload message ID: $message_id for page $page");
    }
    
    // Don't auto-forward movies - let user click "Send This Page"
    // This prevents flooding and Telegram rate limits
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
        if ($chat_id == REQUEST_GROUP_ID && !empty($text) && strpos($text, '/') !== 0) {
            $query = trim($text);
            if (is_movie_request($query)) {
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
                
                exit;
            }
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
            elseif ($command == '/debugcsv') {
                $movies = load_movies_from_csv();
                $msg = "🔧 <b>CSV Debug Info</b>\n\n";
                $msg .= "📁 <b>File:</b> " . CSV_FILE . "\n";
                $msg .= "📊 <b>Total Rows:</b> " . count($movies) . "\n";
                $msg .= "📄 <b>Format:</b> " . CSV_FORMAT . "\n\n";
                
                // Check first few rows
                if (count($movies) > 0) {
                    $msg .= "🎬 <b>Sample Data (first 3):</b>\n";
                    for ($i = 0; $i < min(3, count($movies)); $i++) {
                        $msg .= ($i + 1) . ". " . $movies[$i]['movie_name'] . 
                               " (ID: " . ($movies[$i]['message_id'] ?? 'N/A') . ")\n";
                    }
                } else {
                    $msg .= "❌ <b>CSV is empty or not found!</b>\n";
                }
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/testpagination') {
                // Test pagination with sample data
                $all_movies = get_all_movies_list();
                $msg = "🧪 <b>Pagination Test</b>\n\n";
                $msg .= "📊 <b>Total Movies:</b> " . count($all_movies) . "\n";
                
                if (count($all_movies) > 0) {
                    $test_page = 1;
                    $pg = paginate_movies($all_movies, $test_page);
                    
                    $msg .= "📄 <b>Page $test_page:</b> " . count($pg['slice']) . " movies\n";
                    $msg .= "🔢 <b>Total Pages:</b> {$pg['total_pages']}\n\n";
                    
                    $msg .= "🎬 <b>First 3 movies on page $test_page:</b>\n";
                    for ($i = 0; $i < min(3, count($pg['slice'])); $i++) {
                        $msg .= ($i + 1) . ". " . $pg['slice'][$i]['name'] . "\n";
                    }
                }
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/testautocorrect') {
                $test_queries = [
                    'kfg' => 'kgf',
                    'avangers' => 'avengers',
                    'pushpa 1' => 'pushpa',
                    'spider man' => 'spider-man'
                ];
                
                $msg = "🤖 <b>Auto-Correct Test</b>\n\n";
                foreach ($test_queries as $typo => $expected) {
                    $corrected = auto_correct_suggestion($typo);
                    $msg .= "<b>$typo</b> → <code>" . ($corrected ?: 'No correction') . "</code>\n";
                }
                
                sendMessage($chat_id, $msg, null, 'HTML');
            }
            elseif ($command == '/testrelated') {
                $test_movies = ['kgf', 'avengers', 'pushpa', 'jawan'];
                
                $msg = "🎬 <b>Related Movies Test</b>\n\n";
                foreach ($test_movies as $movie) {
                    $related = get_related_movies($movie);
                    $msg .= "<b>$movie</b> → " . implode(', ', array_slice($related, 0, 3)) . "\n\n";
                }
                
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
        $callback_query_id = $query['id'];
        
        error_log("DEBUG: Callback query received - Data: $data, Chat: $chat_id, Message: $message_id");
        
        // Acknowledge callback immediately
        answerCallbackQuery($callback_query_id, "Processing...");
        
        // Handle pagination
        if (strpos($data, 'tu_prev_') === 0 || strpos($data, 'tu_next_') === 0) {
            $page = (int) str_replace(['tu_prev_', 'tu_next_'], '', $data);
            totalupload_controller($chat_id, $page);
            
            // Delete old message
            deleteMessage($chat_id, $message_id);
        }
        elseif (strpos($data, 'tu_view_') === 0) {
            $page = (int) str_replace('tu_view_', '', $data);
            
            // Send "Processing" message
            $processing_msg = sendMessage($chat_id, "⏳ <b>Sending page $page movies...</b>", null, 'HTML');
            
            $all_movies = get_all_movies_list();
            $pg = paginate_movies($all_movies, $page);
            
            if (!empty($pg['slice'])) {
                // Forward movies
                $forwarded = forward_page_movies($chat_id, $pg['slice']);
                
                // Update processing message
                $msg = "✅ <b>Sent page $page</b>\n\n";
                $msg .= "🎬 <b>Movies sent:</b> $forwarded\n";
                $msg .= "📄 <b>Page:</b> $page/{$pg['total_pages']}\n";
                $msg .= "📊 <b>Total:</b> {$pg['total']} movies\n\n";
                $msg .= "📢 Join: @EntertainmentTadka786";
                
                if (isset($processing_msg['result']['message_id'])) {
                    editMessage($chat_id, $processing_msg, $msg, null, 'HTML');
                }
            } else {
                $error_msg = "❌ <b>No movies found on page $page</b>\n\n";
                $error_msg .= "Please try another page.";
                
                if (isset($processing_msg['result']['message_id'])) {
                    editMessage($chat_id, $processing_msg, $error_msg, null, 'HTML');
                }
            }
        }
        elseif ($data === 'tu_stop') {
            deleteMessage($chat_id, $message_id);
            answerCallbackQuery($callback_query_id, "Closed!");
        }
        elseif ($data === 'current_page') {
            // Just show current page - no action needed
            answerCallbackQuery($callback_query_id, "Current page");
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

// ==================== HELPER FUNCTIONS ====================
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
