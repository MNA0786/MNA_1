<?php
/* ============================================ */
/* 🎬 ENTERTAINMENT TADKA BOT - COMPLETE INDEX.PHP */
/* ============================================ */
/* Developer: Entertainment Tadka Team */
/* Contact: @EntertainmentTadka7860 */
/* Language: HINGLISH (Hindi + English) */
/* CSV Format: movie_name,message_id,channel_id (LOCKED) */
/* ============================================ */

// ✅ ERROR SHOW KARO DEBUGGING KE LIYE
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ TIMEZONE SET KARO
date_default_timezone_set('Asia/Kolkata');

// ✅ BOT TOKEN YAHAN DEFINE KARO
$BOT_TOKEN = "YOUR_BOT_TOKEN_HERE"; // 👈 YAHAN APNA BOT TOKEN DALO

// ✅ TELEGRAM API URL
$API_URL = "https://api.telegram.org/bot" . $BOT_TOKEN . "/";

// ✅ CSV FILE NAME (LOCKED FORMAT)
$CSV_FILE = "movies.csv";
$CSV_FORMAT = "movie_name,message_id,channel_id"; // FORMAT CHANGE MAT KARNA!

// ✅ CHANNEL IDs (APNE CHANNEL IDs SE REPLACE KARO)
$MAIN_CHANNEL = "-1003181705395";    // @EntertainmentTadka786
$THEATER_CHANNEL = "-1002831605258"; // @threater_print_movies
$BACKUP_CHANNEL = "-1002964109368";  // @ETBackup
$REQUEST_GROUP = "-1003083386043";   // @EntertainmentTadka7860

// ✅ OWNER ID (TUMHARA TELEGRAM ID)
$OWNER_ID = "1080317415"; // 👈 YAHAN APNA TELEGRAM ID DALO

// ✅ LOG FILE
$LOG_FILE = "bot_log.txt";

/* ============================================ */
/* 🚀 MAIN UPDATE HANDLER */
/* ============================================ */

// ✅ TELEGRAM SE UPDATE LETEY HAI
$update = json_decode(file_get_contents('php://input'), true);

// ✅ AGAR UPDATE HAI TOH PROCESS KARO
if ($update) {
    
    // ✅ LOG KARO (DEBUGGING KE LIYE)
    logUpdate($update);
    
    // ✅ AGAR MESSAGE HAI
    if (isset($update['message'])) {
        handleMessage($update['message']);
    }
    
    // ✅ AGAR CALLBACK QUERY HAI
    elseif (isset($update['callback_query'])) {
        handleCallbackQuery($update['callback_query']);
    }
    
    // ✅ AGAR CHANNEL POST HAI
    elseif (isset($update['channel_post'])) {
        handleChannelPost($update['channel_post']);
    }
    
} else {
    // ✅ AGAR DIRECT BROWSER SE ACCESS KIYA TOH WELCOME PAGE DIKHAO
    showWelcomePage();
}

/* ============================================ */
/* 📱 MESSAGE HANDLING FUNCTION */
/* ============================================ */

function handleMessage($message) {
    
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'] ?? 0;
    $user_name = $message['from']['first_name'] ?? 'User';
    $text = $message['text'] ?? '';
    
    // ✅ TYPING ACTION SHOW KARO
    sendTypingAction($chat_id);
    
    // ✅ AGAR /start COMMAND HAI
    if (strpos($text, '/start') === 0) {
        sendStartMessage($chat_id, $user_name);
    }
    
    // ✅ AGAR /help COMMAND HAI
    elseif (strpos($text, '/help') === 0) {
        sendHelpMessage($chat_id);
    }
    
    // ✅ AGAR /totalupload COMMAND HAI
    elseif (strpos($text, '/totalupload') === 0) {
        handleTotalUpload($chat_id, $text);
    }
    
    // ✅ AGAR /request COMMAND HAI
    elseif (strpos($text, '/request') === 0) {
        handleRequestCommand($chat_id, $user_id, $user_name, $text);
    }
    
    // ✅ AGAR /myrequests COMMAND HAI
    elseif (strpos($text, '/myrequests') === 0) {
        handleMyRequests($chat_id, $user_id);
    }
    
    // ✅ AGAR /stats COMMAND HAI (OWNER ONLY)
    elseif (strpos($text, '/stats') === 0) {
        if ($user_id == $GLOBALS['OWNER_ID']) {
            handleStatsCommand($chat_id);
        }
    }
    
    // ✅ AGAR /checkcsv COMMAND HAI
    elseif (strpos($text, '/checkcsv') === 0) {
        handleCheckCSV($chat_id, $text);
    }
    
    // ✅ AGAR CHANNEL INFO COMMANDS HAIN
    elseif (strpos($text, '/mainchannel') === 0) {
        sendChannelInfo($chat_id, 'main');
    }
    elseif (strpos($text, '/theaterchannel') === 0) {
        sendChannelInfo($chat_id, 'theater');
    }
    elseif (strpos($text, '/backupchannel') === 0) {
        sendChannelInfo($chat_id, 'backup');
    }
    elseif (strpos($text, '/requestchannel') === 0) {
        sendChannelInfo($chat_id, 'request');
    }
    
    // ✅ AGAR KOI AUR COMMAND HAI
    elseif (strpos($text, '/') === 0) {
        sendUnknownCommand($chat_id);
    }
    
    // ✅ AGAR PLAIN TEXT HAI (MOVIE SEARCH)
    elseif (!empty(trim($text))) {
        handleMovieSearch($chat_id, $user_id, $text);
    }
}

/* ============================================ */
/* 🎯 MOVIE SEARCH HANDLER */
/* ============================================ */

function handleMovieSearch($chat_id, $user_id, $query) {
    
    $query = trim($query);
    $query_lower = strtolower($query);
    
    // ✅ VALIDATION CHECK
    if (strlen($query) < 2) {
        sendMessage($chat_id, "❌ Kam se kam 2 characters enter karein!");
        return;
    }
    
    // ✅ TECHNICAL QUERIES BLOCK KARO
    if (isTechnicalQuery($query_lower)) {
        sendMessage($chat_id, 
            "🎬 Kripya movie ka naam enter karein!\n\n" .
            "🔍 Examples:\n" .
            "• <code>kgf</code>\n" .
            "• <code>pushpa hindi</code>\n" .
            "• <code>avengers english</code>\n\n" .
            "❌ Technical queries mat likhein"
        );
        return;
    }
    
    // ✅ SEARCH START MESSAGE
    sendMessage($chat_id, 
        "🔍 <b>Searching...</b>\n\n" .
        "<code>" . htmlspecialchars($query) . "</code>"
    );
    
    // ✅ MOVIES LOAD KARO
    $movies = loadMoviesCSV();
    
    if (empty($movies)) {
        sendMessage($chat_id, 
            "❌ Database mein koi movies nahi hain!\n\n" .
            "📢 Admin se contact karein: @EntertainmentTadka7860"
        );
        return;
    }
    
    // ✅ SMART FUZZY SEARCH KARO
    $results = fuzzySearch($query_lower, $movies);
    
    // ✅ AGAR KOI RESULT NAHI MILA
    if (empty($results)) {
        showSmartSuggestions($chat_id, $query_lower, $movies);
        return;
    }
    
    // ✅ RESULTS COUNT KARO
    $total_files = 0;
    foreach ($results as $result) {
        $total_files += $result['entries'];
    }
    
    // ✅ SEARCH RESULTS MESSAGE
    $search_msg = sendMessage($chat_id,
        "✅ <b>" . count($results) . " movies mil gayi!</b>\n\n" .
        "🔍 <b>Search:</b> <code>" . htmlspecialchars($query) . "</code>\n" .
        "📦 <b>Total files:</b> $total_files\n" .
        "⏳ <b>Forwarding start ho raha hai...</b>"
    );
    
    // ✅ SABHI FILES FORWARD KARO
    $forwarded_count = 0;
    $failed_count = 0;
    
    foreach ($results as $result) {
        $movie_files = getMovieFiles($result['key'], $movies);
        
        foreach ($movie_files as $file) {
            if (forwardMovieFile($chat_id, $file)) {
                $forwarded_count++;
                usleep(200000); // 0.2 second delay
            } else {
                $failed_count++;
            }
        }
    }
    
    // ✅ SEARCH COMPLETE MESSAGE
    $summary = "✅ <b>Search Complete!</b>\n\n";
    $summary .= "🔍 <b>Search:</b> <code>" . htmlspecialchars($query) . "</code>\n";
    $summary .= "🎬 <b>Movies found:</b> " . count($results) . "\n";
    $summary .= "📤 <b>Files forwarded:</b> $forwarded_count\n";
    
    if ($failed_count > 0) {
        $summary .= "❌ <b>Failed:</b> $failed_count\n";
    }
    
    $summary .= "\n💡 <b>Aur movies chahiye?</b> Koi aur naam type karein!";
    
    // ✅ AGAR SEARCH MESSAGE KA ID HAI TOH EDIT KARO
    if (isset($search_msg['message_id'])) {
        editMessage($chat_id, $search_msg['message_id'], $summary);
    } else {
        sendMessage($chat_id, $summary);
    }
    
    // ✅ STATS UPDATE KARO
    updateSearchStats($user_id);
}

/* ============================================ */
/* 🔍 SMART SEARCH FUNCTIONS */
/* ============================================ */

function loadMoviesCSV() {
    global $CSV_FILE;
    
    if (!file_exists($CSV_FILE)) {
        // ✅ AGAR CSV FILE NAHI HAI TOH BANAO
        createCSVFile();
        return [];
    }
    
    $movies = [];
    $handle = fopen($CSV_FILE, "r");
    
    if ($handle !== FALSE) {
        // ✅ HEADER READ KARO (FIRST LINE)
        $header = fgetcsv($handle);
        
        // ✅ AGAR HEADER SAHI NAHI HAI
        if ($header !== ['movie_name', 'message_id', 'channel_id']) {
            fclose($handle);
            fixCSVFormat();
            return loadMoviesCSV(); // RECURSIVE CALL
        }
        
        // ✅ DATA READ KARO
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) >= 3) {
                $movies[] = [
                    'movie_name' => trim($row[0]),
                    'message_id' => trim($row[1]),
                    'channel_id' => trim($row[2]),
                    'key' => strtolower(trim($row[0]))
                ];
            }
        }
        fclose($handle);
    }
    
    return $movies;
}

function createCSVFile() {
    global $CSV_FILE, $CSV_FORMAT;
    
    $header = explode(',', $CSV_FORMAT);
    $handle = fopen($CSV_FILE, 'w');
    fputcsv($handle, $header);
    fclose($handle);
    
    @chmod($CSV_FILE, 0666);
    
    logEvent("CSV file created: " . $CSV_FILE);
}

function fixCSVFormat() {
    global $CSV_FILE;
    
    // ✅ PURANI FILE BACKUP KARO
    $backup_file = "backup_" . date('Y-m-d_H-i-s') . ".csv";
    copy($CSV_FILE, $backup_file);
    
    // ✅ NAYI FILE BANAO SAHI FORMAT MEIN
    $old_data = file($CSV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_data = ["movie_name,message_id,channel_id"];
    
    foreach ($old_data as $line) {
        $line = trim($line);
        if (!empty($line) && strpos($line, 'movie_name') === false) {
            $parts = explode(',', $line);
            if (count($parts) >= 3) {
                $new_data[] = implode(',', [$parts[0], $parts[1], $parts[2]]);
            }
        }
    }
    
    file_put_contents($CSV_FILE, implode("\n", $new_data));
    
    logEvent("CSV format fixed and backed up to: " . $backup_file);
}

function fuzzySearch($query, $movies) {
    $matches = [];
    $query_norm = normalizeMovieName($query);
    
    if (empty($query_norm) || empty($movies)) {
        return [];
    }
    
    foreach ($movies as $movie) {
        $movie_key = $movie['key'] ?? '';
        if (empty($movie_key)) continue;
        
        $movie_norm = normalizeMovieName($movie_key);
        
        // ✅ SCORING SYSTEM
        $score = 0;
        
        // 1. EXACT MATCH (100 POINTS)
        if ($movie_norm === $query_norm) {
            $score = 100;
        }
        // 2. CONTAINS MATCH (60-90 POINTS)
        elseif (strpos($movie_norm, $query_norm) !== false) {
            $score = 90 - (strlen($movie_norm) - strlen($query_norm));
        }
        // 3. SIMILAR TEXT (40-80 POINTS)
        else {
            similar_text($movie_norm, $query_norm, $percent);
            if ($percent >= 40) {
                $score = $percent;
            }
        }
        
        // ✅ AGAR SCORE 40 SE ZYADA HAI
        if ($score > 40) {
            $found = false;
            
            // ✅ GROUP BY MOVIE NAME
            foreach ($matches as &$match) {
                if ($match['key'] === $movie_key) {
                    $match['entries']++;
                    $match['score'] = max($match['score'], $score);
                    $found = true;
                    break;
                }
            }
            
            // ✅ NAYI MOVIE ADD KARO
            if (!$found) {
                $matches[] = [
                    'key' => $movie_key,
                    'title' => $movie['movie_name'],
                    'score' => $score,
                    'entries' => 1,
                    'channel_id' => $movie['channel_id'],
                    'message_id' => $movie['message_id']
                ];
            }
        }
    }
    
    // ✅ SCORE KE HISAAB SE SORT KARO (HIGHEST FIRST)
    usort($matches, function($a, $b) {
        if ($b['score'] == $a['score']) {
            return $b['entries'] <=> $a['entries'];
        }
        return $b['score'] <=> $a['score'];
    });
    
    // ✅ TOP 5 RESULTS RETURN KARO
    return array_slice($matches, 0, 5);
}

function normalizeMovieName($name) {
    $name = strtolower($name);
    $name = preg_replace('/\([^)]*\)/', '', $name); // YEARS HATAO
    $name = preg_replace('/[^a-z0-9 ]/', ' ', $name); // SPECIAL CHARACTERS HATAO
    $name = preg_replace('/\s+/', ' ', $name); // EXTRA SPACES HATAO
    return trim($name);
}

function isTechnicalQuery($query) {
    $technical_words = [
        'vlc', 'audio', 'track', 'change', 'kar', 'me', 'hai',
        'how', 'what', 'problem', 'issue', 'help', 'solution', 'fix',
        'error', 'not working', 'download', 'play', 'video', 'sound',
        'subtitle', 'quality', 'hd', 'full', 'part', 'scene', 'bhai'
    ];
    
    $query_words = explode(' ', $query);
    $tech_count = 0;
    
    foreach ($query_words as $word) {
        if (in_array($word, $technical_words)) {
            $tech_count++;
        }
    }
    
    // ✅ AGAR 50% SE ZYADA WORDS TECHNICAL HAIN TOH BLOCK KARO
    return ($tech_count / count($query_words)) > 0.5;
}

/* ============================================ */
/* 💡 SMART SUGGESTIONS SYSTEM */
/* ============================================ */

function showSmartSuggestions($chat_id, $query, $movies) {
    
    $suggestions = getSmartSuggestions($query, $movies);
    
    $msg = "❌ <b>Koi movie nahi mili!</b>\n\n";
    $msg .= "🔍 <b>Search:</b> <code>" . htmlspecialchars($query) . "</code>\n\n";
    
    if (!empty($suggestions)) {
        $msg .= "💡 <b>Kya yeh dhoond rahe the?</b>\n";
        $buttons = [];
        
        foreach ($suggestions as $index => $suggestion) {
            $short_name = (strlen($suggestion) > 35) 
                ? substr($suggestion, 0, 32) . '...' 
                : $suggestion;
            
            $msg .= ($index + 1) . ". $suggestion\n";
            
            $buttons[] = [[
                "text" => "🎬 " . $short_name,
                "callback_data" => "movie|" . base64_encode($suggestion)
            ]];
        }
        
        sendMessage($chat_id, $msg);
        
        // ✅ SUGGESTION BUTTONS
        if (!empty($buttons)) {
            $buttons[] = [
                ["text" => "🔍 Dubara Search", "callback_data" => "search_again"],
                ["text" => "❌ Cancel", "callback_data" => "cancel_search"]
            ];
            
            sendMessage($chat_id,
                "👇 Inmein se koi movie chunein:",
                ["inline_keyboard" => $buttons]
            );
        }
    } else {
        $msg .= "💡 <b>Suggestions:</b>\n";
        $msg .= "1. Spelling check karein\n";
        $msg .= "2. Short naam try karein\n";
        $msg .= "3. Year hataein (2024, 2025)\n";
        $msg .= "4. Language specify karein\n\n";
        $msg .= "📢 <b>Examples:</b>\n";
        $msg .= "• <code>kgf</code>\n";
        $msg .= "• <code>pushpa hindi</code>\n";
        $msg .= "• <code>avengers english</code>";
        
        sendMessage($chat_id, $msg);
    }
}

function getSmartSuggestions($query, $movies) {
    $suggestions = [];
    $query_norm = normalizeMovieName($query);
    
    if (empty($query_norm)) return [];
    
    // ✅ UNIQUE MOVIE NAMES COLLECT KARO
    $unique_movies = [];
    foreach ($movies as $movie) {
        $movie_key = $movie['key'] ?? '';
        if (!empty($movie_key) && !isset($unique_movies[$movie_key])) {
            $unique_movies[$movie_key] = $movie['movie_name'];
        }
    }
    
    // ✅ HAR MOVIE KO SCORE KARO
    foreach ($unique_movies as $movie_key => $movie_name) {
        $movie_norm = normalizeMovieName($movie_key);
        
        // ✅ KAM SE KAM EK WORD MATCH HONA CHAHIYE
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
            if ($percent >= 30) { // ✅ LOWER THRESHOLD FOR SUGGESTIONS
                $suggestions[$movie_name] = [
                    'score' => $percent + ($match_count * 10),
                    'match_count' => $match_count
                ];
            }
        }
    }
    
    // ✅ SCORE KE HISAAB SE SORT KARO
    uasort($suggestions, function($a, $b) {
        if ($b['score'] == $a['score']) {
            return $b['match_count'] <=> $a['match_count'];
        }
        return $b['score'] <=> $a['score'];
    });
    
    // ✅ TOP 4 SUGGESTIONS RETURN KARO
    return array_slice(array_keys($suggestions), 0, 4);
}

function getMovieFiles($movie_key, $movies) {
    $files = [];
    
    foreach ($movies as $movie) {
        $current_key = $movie['key'] ?? '';
        if (normalizeMovieName($current_key) === normalizeMovieName($movie_key)) {
            $files[] = [
                'channel_id' => $movie['channel_id'],
                'message_id' => $movie['message_id'],
                'movie_name' => $movie['movie_name']
            ];
        }
    }
    
    return $files;
}

/* ============================================ */
/* 📤 FORWARDING SYSTEM */
/* ============================================ */

function forwardMovieFile($chat_id, $file) {
    global $BOT_TOKEN;
    
    if (empty($file['channel_id']) || empty($file['message_id'])) {
        logEvent("Forward failed: Missing channel_id or message_id");
        return false;
    }
    
    // ✅ FORWARD MESSAGE API CALL
    $api_url = "https://api.telegram.org/bot$BOT_TOKEN/forwardMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'from_chat_id' => $file['channel_id'],
        'message_id' => $file['message_id']
    ];
    
    $result = @file_get_contents($api_url . '?' . http_build_query($data));
    $result_data = @json_decode($result, true);
    
    if ($result_data && isset($result_data['ok']) && $result_data['ok']) {
        logEvent("Forward successful: " . $file['movie_name'] . " to $chat_id");
        return true;
    }
    
    // ✅ AGAR FORWARD FAIL HUA TOH COPY TRY KARO
    logEvent("Forward failed, trying copy: " . $file['movie_name']);
    
    $copy_url = "https://api.telegram.org/bot$BOT_TOKEN/copyMessage";
    $copy_data = [
        'chat_id' => $chat_id,
        'from_chat_id' => $file['channel_id'],
        'message_id' => $file['message_id']
    ];
    
    $copy_result = @file_get_contents($copy_url . '?' . http_build_query($copy_data));
    $copy_data = @json_decode($copy_result, true);
    
    if ($copy_data && isset($copy_data['ok']) && $copy_data['ok']) {
        logEvent("Copy successful: " . $file['movie_name']);
        return true;
    }
    
    // ✅ DONO METHODS FAIL HO GAYE
    logEvent("Both forward and copy failed: " . $file['movie_name']);
    return false;
}

/* ============================================ */
/* 📋 CALLBACK QUERY HANDLER */
/* ============================================ */

function handleCallbackQuery($callback_query) {
    
    $chat_id = $callback_query['message']['chat']['id'];
    $message_id = $callback_query['message']['message_id'];
    $callback_id = $callback_query['id'];
    $data = $callback_query['data'];
    
    // ✅ ANSWER CALLBACK (LOADING MESSAGE)
    answerCallbackQuery($callback_id, "Processing...");
    
    // ✅ MOVIE SELECTION
    if (strpos($data, "movie|") === 0) {
        $movie_key = base64_decode(explode("|", $data)[1]);
        
        // ✅ DELETE SUGGESTION MESSAGE
        deleteMessage($chat_id, $message_id);
        
        // ✅ PROCESSING MESSAGE
        sendMessage($chat_id, 
            "📥 <b>Sending files for:</b>\n" .
            "<code>" . htmlspecialchars($movie_key) . "</code>\n\n" .
            "⏳ Please wait..."
        );
        
        // ✅ MOVIES LOAD KARO
        $movies = loadMoviesCSV();
        $movie_files = getMovieFiles($movie_key, $movies);
        
        if (empty($movie_files)) {
            sendMessage($chat_id, "❌ <b>Error:</b> Files nahi mil sake!");
            return;
        }
        
        // ✅ SABHI FILES FORWARD KARO
        $sent_count = 0;
        foreach ($movie_files as $file) {
            if (forwardMovieFile($chat_id, $file)) {
                $sent_count++;
                usleep(200000); // 0.2 second delay
            }
        }
        
        // ✅ SUMMARY
        $summary = "✅ <b>Completed!</b>\n\n";
        $summary .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_key) . "\n";
        $summary .= "📦 <b>Files sent:</b> $sent_count\n";
        $summary .= "📊 <b>Total available:</b> " . count($movie_files) . "\n\n";
        $summary .= "🔍 <b>Aur movies chahiye?</b> Simply type another name!";
        
        sendMessage($chat_id, $summary);
    }
    
    // ✅ SEARCH AGAIN
    elseif ($data === "search_again") {
        deleteMessage($chat_id, $message_id);
        sendMessage($chat_id, 
            "🔍 <b>Movie ka naam type karein:</b>\n\n" .
            "Example: <code>kgf hindi</code>\n" .
            "<code>spider-man english</code>"
        );
    }
    
    // ✅ CANCEL SEARCH
    elseif ($data === "cancel_search") {
        deleteMessage($chat_id, $message_id);
    }
    
    // ✅ TOTAL UPLOAD PAGINATION
    elseif (strpos($data, "page_") === 0) {
        $page = intval(str_replace("page_", "", $data));
        handleTotalUpload($chat_id, "/totalupload $page", true);
    }
}

/* ============================================ */
/* 📁 TOTAL UPLOAD SYSTEM */
/* ============================================ */

function handleTotalUpload($chat_id, $command, $is_callback = false) {
    
    $parts = explode(' ', $command);
    $page = isset($parts[1]) ? intval($parts[1]) : 1;
    
    // ✅ MOVIES LOAD KARO
    $movies = loadMoviesCSV();
    
    if (empty($movies)) {
        sendMessage($chat_id, 
            "📭 <b>Koi Movies Nahin Mili!</b>\n\n" .
            "🎬 Database empty hai\n" .
            "📢 Channels mein movies add karein\n" .
            "💬 Join: @EntertainmentTadka7860"
        );
        return;
    }
    
    // ✅ MOVIE NAMES SORT KARO
    $movie_names = [];
    foreach ($movies as $movie) {
        if (!empty($movie['movie_name'])) {
            $movie_names[] = $movie['movie_name'];
        }
    }
    
    $movie_names = array_unique($movie_names);
    sort($movie_names);
    
    // ✅ PAGINATION CALCULATE KARO
    $per_page = 10;
    $total_movies = count($movie_names);
    $total_pages = ceil($total_movies / $per_page);
    
    // ✅ PAGE VALIDATION
    if ($page < 1) $page = 1;
    if ($page > $total_pages) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    $page_movies = array_slice($movie_names, $offset, $per_page);
    
    // ✅ MESSAGE BANAO
    $msg = "🎬 <b>Total Uploads</b>\n\n";
    $msg .= "📊 <b>Statistics:</b>\n";
    $msg .= "• Total Movies: <b>$total_movies</b>\n";
    $msg .= "• Current Page: <b>$page/$total_pages</b>\n";
    $msg .= "• Showing: <b>" . count($page_movies) . " movies</b>\n\n";
    
    $msg .= "📋 <b>Page $page Movies:</b>\n";
    $i = $offset + 1;
    foreach ($page_movies as $movie) {
        $msg .= "$i. " . htmlspecialchars($movie) . "\n";
        $i++;
    }
    
    $msg .= "\n📍 <b>Navigation:</b> Neeche ke buttons use karein";
    $msg .= "\n📢 <b>Join:</b> @EntertainmentTadka786";
    
    // ✅ PAGINATION BUTTONS
    $buttons = [];
    
    if ($total_pages > 1) {
        $row = [];
        
        if ($page > 1) {
            $row[] = ["text" => "◀️ Previous", "callback_data" => "page_" . ($page - 1)];
        }
        
        $row[] = ["text" => "📄 $page/$total_pages", "callback_data" => "current"];
        
        if ($page < $total_pages) {
            $row[] = ["text" => "Next ▶️", "callback_data" => "page_" . ($page + 1)];
        }
        
        $buttons[] = $row;
    }
    
    // ✅ AGAR CALLBACK SE AAYA HAI TOH EDIT KARO
    if ($is_callback) {
        editMessage($chat_id, $callback_query['message']['message_id'], $msg, $buttons);
    } else {
        sendMessage($chat_id, $msg, ["inline_keyboard" => $buttons]);
    }
}

/* ============================================ */
/* 📝 REQUEST SYSTEM */
/* ============================================ */

function handleRequestCommand($chat_id, $user_id, $user_name, $command) {
    
    $parts = explode(' ', $command);
    $movie_name = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
    
    if (empty($movie_name)) {
        sendMessage($chat_id,
            "📝 <b>/request kaise use karein:</b>\n\n" .
            "Usage: <code>/request Movie Name</code>\n\n" .
            "Example: <code>/request KGF 3 hindi movie</code>\n\n" .
            "📢 Join: @EntertainmentTadka7860\n" .
            "🔔 Hum notify kar denge jab add ho jayegi!"
        );
        return;
    }
    
    // ✅ REQUEST VALIDATION
    if (strlen($movie_name) < 3) {
        sendMessage($chat_id, "❌ Movie name kam se kam 3 characters ka hona chahiye!");
        return;
    }
    
    // ✅ REQUEST SAVE KARO
    $request_id = saveMovieRequest($user_id, $user_name, $movie_name);
    
    $msg = "✅ <b>Request Successfully Submit Ho Gayi!</b>\n\n";
    $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
    $msg .= "📋 <b>Request ID:</b> <code>$request_id</code>\n";
    $msg .= "👤 <b>Requested by:</b> $user_name\n";
    $msg .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n";
    $msg .= "📊 <b>Status:</b> ⏳ Pending\n\n";
    $msg .= "📢 Hum jaldi add kar denge!\n";
    $msg .= "💬 Updates ke liye: @EntertainmentTadka7860\n";
    $msg .= "🔍 Status check: /myrequests";
    
    sendMessage($chat_id, $msg);
    
    // ✅ OWNER KO NOTIFY KARO
    notifyOwnerNewRequest($movie_name, $user_name, $user_id, $request_id);
}

function saveMovieRequest($user_id, $user_name, $movie_name) {
    $requests_file = "requests.json";
    
    // ✅ AGAR FILE NAHI HAI TOH BANAO
    if (!file_exists($requests_file)) {
        $initial_data = [
            'requests' => [],
            'total' => 0,
            'pending' => 0,
            'completed' => 0,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        file_put_contents($requests_file, json_encode($initial_data, JSON_PRETTY_PRINT));
    }
    
    // ✅ DATA LOAD KARO
    $data = json_decode(file_get_contents($requests_file), true);
    
    // ✅ REQUEST ID GENERATE KARO
    $request_id = "REQ_" . time() . "_" . $user_id;
    
    // ✅ NEW REQUEST ADD KARO
    $new_request = [
        'id' => $request_id,
        'movie_name' => $movie_name,
        'user_id' => (string)$user_id,
        'user_name' => $user_name,
        'status' => 'pending',
        'requested_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $data['requests'][] = $new_request;
    $data['total']++;
    $data['pending']++;
    $data['last_updated'] = date('Y-m-d H:i:s');
    
    // ✅ SAVE KARO
    file_put_contents($requests_file, json_encode($data, JSON_PRETTY_PRINT));
    
    // ✅ LOG KARO
    logEvent("New request: $movie_name by $user_name ($user_id)");
    
    return $request_id;
}

function handleMyRequests($chat_id, $user_id) {
    $requests_file = "requests.json";
    
    if (!file_exists($requests_file)) {
        sendMessage($chat_id,
            "📭 <b>Koi Requests Nahin Mili</b>\n\n" .
            "Aapne abhi tak koi request nahin ki hai.\n\n" .
            "🎬 <b>Movie request karne ke liye:</b>\n" .
            "Use: <code>/request Movie Name</code>\n\n" .
            "Example: <code>/request Avengers Endgame hindi</code>\n\n" .
            "📢 Join: @EntertainmentTadka7860"
        );
        return;
    }
    
    // ✅ DATA LOAD KARO
    $data = json_decode(file_get_contents($requests_file), true);
    
    // ✅ USER KI REQUESTS FILTER KARO
    $user_requests = [];
    foreach ($data['requests'] as $request) {
        if ($request['user_id'] == (string)$user_id) {
            $user_requests[] = $request;
        }
    }
    
    if (empty($user_requests)) {
        sendMessage($chat_id,
            "📭 <b>Koi Requests Nahin Mili</b>\n\n" .
            "Aapne abhi tak koi request nahin ki hai.\n\n" .
            "🎬 <b>Movie request karne ke liye:</b>\n" .
            "Use: <code>/request Movie Name</code>\n\n" .
            "Example: <code>/request Avengers Endgame hindi</code>\n\n" .
            "📢 Join: @EntertainmentTadka7860"
        );
        return;
    }
    
    // ✅ REQUESTS SORT KARO (NEWEST FIRST)
    usort($user_requests, function($a, $b) {
        return strtotime($b['requested_at']) <=> strtotime($a['requested_at']);
    });
    
    // ✅ MESSAGE BANAO
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
        $msg .= "   📊 <b>Status:</b> $status_text\n\n";
    }
    
    $msg .= "📊 <b>Summary:</b>\n";
    $msg .= "⏳ Pending: $pending_count\n";
    $msg .= "🎬 Completed: $completed_count\n";
    $msg .= "📋 Total: " . count($user_requests) . "\n\n";
    $msg .= "📢 Updates ke liye: @EntertainmentTadka7860";
    
    sendMessage($chat_id, $msg);
}

function notifyOwnerNewRequest($movie_name, $user_name, $user_id, $request_id) {
    global $OWNER_ID, $BOT_TOKEN;
    
    if (empty($OWNER_ID) || $OWNER_ID == "1080317415") {
                return; // DEFAULT OWNER ID HAI, CHANGE KARNA HOGA
    }
    
    $msg = "📥 <b>Nayi Movie Request</b>\n\n";
    $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie_name) . "\n";
    $msg .= "📋 <b>Request ID:</b> $request_id\n";
    $msg .= "👤 <b>User:</b> $user_name\n";
    $msg .= "🆔 <b>User ID:</b> $user_id\n";
    $msg .= "⏰ <b>Time:</b> " . date('H:i:s') . "\n\n";
    
    // ✅ PENDING COUNT ADD KARO
    $pending_count = getPendingRequestsCount();
    $msg .= "📊 <b>Total pending requests:</b> $pending_count";
    
    sendMessage($OWNER_ID, $msg);
}

function getPendingRequestsCount() {
    $requests_file = "requests.json";
    
    if (!file_exists($requests_file)) {
        return 0;
    }
    
    $data = json_decode(file_get_contents($requests_file), true);
    return $data['pending'] ?? 0;
}

/* ============================================ */
/* 📊 STATS & ADMIN FUNCTIONS */
/* ============================================ */

function handleStatsCommand($chat_id) {
    global $CSV_FILE;
    
    // ✅ MOVIES COUNT
    $movies = loadMoviesCSV();
    $movie_count = count($movies);
    
    // ✅ UNIQUE MOVIES COUNT
    $unique_movies = [];
    foreach ($movies as $movie) {
        $key = $movie['key'] ?? '';
        if (!empty($key)) {
            $unique_movies[$key] = true;
        }
    }
    $unique_count = count($unique_movies);
    
    // ✅ REQUESTS STATS
    $requests_stats = getRequestsStats();
    
    // ✅ CSV FILE INFO
    $csv_size = file_exists($CSV_FILE) ? formatBytes(filesize($CSV_FILE)) : "0 KB";
    $csv_modified = file_exists($CSV_FILE) ? date('d-m-Y H:i:s', filemtime($CSV_FILE)) : "N/A";
    
    // ✅ LOG FILE INFO
    $log_size = file_exists('bot_log.txt') ? formatBytes(filesize('bot_log.txt')) : "0 KB";
    
    $msg = "📊 <b>Bot Statistics</b>\n\n";
    
    $msg .= "🎬 <b>Movies Database:</b>\n";
    $msg .= "• Total Entries: $movie_count\n";
    $msg .= "• Unique Movies: $unique_count\n";
    $msg .= "• CSV Size: $csv_size\n";
    $msg .= "• Last Updated: $csv_modified\n\n";
    
    $msg .= "📝 <b>Requests:</b>\n";
    $msg .= "• Total: " . ($requests_stats['total'] ?? 0) . "\n";
    $msg .= "• Pending: " . ($requests_stats['pending'] ?? 0) . "\n";
    $msg .= "• Completed: " . ($requests_stats['completed'] ?? 0) . "\n\n";
    
    $msg .= "📁 <b>System:</b>\n";
    $msg .= "• Log Size: $log_size\n";
    $msg .= "• Server Time: " . date('H:i:s') . "\n";
    $msg .= "• Memory Usage: " . formatBytes(memory_get_usage()) . "\n";
    $msg .= "• PHP Version: " . PHP_VERSION;
    
    sendMessage($chat_id, $msg);
}

function getRequestsStats() {
    $requests_file = "requests.json";
    
    if (!file_exists($requests_file)) {
        return ['total' => 0, 'pending' => 0, 'completed' => 0];
    }
    
    $data = json_decode(file_get_contents($requests_file), true);
    
    $pending = 0;
    $completed = 0;
    
    foreach ($data['requests'] ?? [] as $request) {
        if ($request['status'] == 'pending') {
            $pending++;
        } elseif ($request['status'] == 'completed') {
            $completed++;
        }
    }
    
    return [
        'total' => $data['total'] ?? 0,
        'pending' => $pending,
        'completed' => $completed
    ];
}

function handleCheckCSV($chat_id, $command) {
    global $CSV_FILE;
    
    $parts = explode(' ', $command);
    $show_all = isset($parts[1]) && $parts[1] == 'all';
    
    $movies = loadMoviesCSV();
    
    if ($show_all) {
        $msg = "📋 <b>CSV Mein Saari Movies</b>\n\n";
        $msg .= "📊 <b>Total:</b> " . count($movies) . " entries\n\n";
        
        $i = 1;
        foreach ($movies as $movie) {
            $msg .= "$i. " . htmlspecialchars($movie['movie_name']) . "\n";
            $i++;
            
            // ✅ AGAR MESSAGE LAMBA HO RAHA HAI TOH BREAK KARO
            if (strlen($msg) > 3500) {
                $msg .= "... aur " . (count($movies) - $i + 1) . " aur";
                break;
            }
        }
    } else {
        $msg = "✅ <b>CSV Status</b>\n\n";
        $msg .= "📊 <b>Total Movies:</b> " . count($movies) . "\n";
        $msg .= "📄 <b>File:</b> $CSV_FILE\n";
        $msg .= "🔧 <b>Format:</b> movie_name,message_id,channel_id\n";
        $msg .= "📏 <b>Size:</b> " . formatBytes(filesize($CSV_FILE)) . "\n";
        $msg .= "⏰ <b>Last Modified:</b> " . date('d-m-Y H:i:s', filemtime($CSV_FILE)) . "\n\n";
        $msg .= "📝 <code>/checkcsv all</code> use karein saari movies dekhne ke liye";
    }
    
    sendMessage($chat_id, $msg);
}

/* ============================================ */
/* 📢 CHANNEL INFO FUNCTIONS */
/* ============================================ */

function sendChannelInfo($chat_id, $channel_type) {
    
    switch ($channel_type) {
        case 'main':
            $msg = "🍿 <b>Main Channel</b>\n\n";
            $msg .= "📢 <b>@EntertainmentTadka786</b>\n";
            $msg .= "• Latest movies & series\n";
            $msg .= "• Daily updates\n";
            $msg .= "• Multiple qualities\n";
            $msg .= "• Hindi/English content\n\n";
            $msg .= "🔗 Link: https://t.me/EntertainmentTadka786\n";
            $msg .= "👥 Members: 1000+\n";
            $msg .= "📅 Updated: Daily";
            break;
            
        case 'theater':
            $msg = "🎭 <b>Theater Prints Channel</b>\n\n";
            $msg .= "📢 <b>@threater_print_movies</b>\n";
            $msg .= "• HD theater prints\n";
            $msg .= "• Blu-ray quality\n";
            $msg .= "• Best audio/video\n";
            $msg .= "• Exclusive releases\n\n";
            $msg .= "🔗 Link: https://t.me/threater_print_movies\n";
            $msg .= "🌟 Premium content\n";
            $msg .= "🎬 Cinema experience";
            break;
            
        case 'backup':
            $msg = "🔒 <b>Backup Channel</b>\n\n";
            $msg .= "📢 <b>@ETBackup</b>\n";
            $msg .= "• Sabhi movies ka backup\n";
            $msg .= "• Data protection\n";
            $msg .= "• Emergency access\n";
            $msg .= "• Redundant storage\n\n";
            $msg .= "🔗 Link: https://t.me/ETBackup\n";
            $msg .= "💾 Secure backup\n";
            $msg .= "🛡️ Data safety";
            break;
            
        case 'request':
            $msg = "📥 <b>Request Channel</b>\n\n";
            $msg .= "📢 <b>@EntertainmentTadka7860</b>\n";
            $msg .= "• Request movies\n";
            $msg .= "• Get support\n";
            $msg .= "• Report issues\n";
            $msg .= "• Suggest improvements\n\n";
            $msg .= "🔗 Link: https://t.me/EntertainmentTadka7860\n";
            $msg .= "💬 Active community\n";
            $msg .= "⚡ Quick responses";
            break;
            
        default:
            $msg = "❌ Invalid channel type";
    }
    
    sendMessage($chat_id, $msg);
}

/* ============================================ */
/* 📡 CHANNEL POST HANDLER */
/* ============================================ */

function handleChannelPost($channel_post) {
    global $CSV_FILE, $MAIN_CHANNEL, $THEATER_CHANNEL, $BACKUP_CHANNEL;
    
    $chat_id = $channel_post['chat']['id'];
    $message_id = $channel_post['message_id'];
    
    // ✅ CHECK IF FROM OUR CHANNELS
    $is_our_channel = in_array($chat_id, [$MAIN_CHANNEL, $THEATER_CHANNEL, $BACKUP_CHANNEL]);
    
    if (!$is_our_channel) {
        return; // ✅ HAMARE CHANNEL SE NAHI HAI TOH IGNORE KARO
    }
    
    // ✅ GET MOVIE NAME FROM CAPTION OR TEXT
    $movie_name = '';
    
    if (isset($channel_post['caption'])) {
        $movie_name = trim($channel_post['caption']);
    } elseif (isset($channel_post['text'])) {
        $movie_name = trim($channel_post['text']);
    } elseif (isset($channel_post['document'])) {
        $movie_name = $channel_post['document']['file_name'] ?? '';
    } else {
        $movie_name = 'Media - ' . date('d-m-Y H:i');
    }
    
    // ✅ AGAR MOVIE NAME EMPTY HAI
    if (empty($movie_name)) {
        $movie_name = 'Untitled - ' . date('d-m-Y H:i');
    }
    
    // ✅ CSV MEIN ADD KARO
    $handle = fopen($CSV_FILE, 'a');
    if ($handle) {
        $entry = [$movie_name, $message_id, $chat_id];
        fputcsv($handle, $entry);
        fclose($handle);
        
        // ✅ LOG KARO
        logEvent("Movie added: '$movie_name' to CSV from channel $chat_id");
    }
}

/* ============================================ */
/* 📞 START & HELP MESSAGES */
/* ============================================ */

function sendStartMessage($chat_id, $user_name) {
    $msg = "🎬 <b>Namaste $user_name!</b>\n\n";
    $msg .= "🫡 <b>Entertainment Tadka mein aapka swagat hai!</b>\n\n";
    $msg .= "📢 <b>Bot kaise use karein:</b>\n";
    $msg .= "• Kisi bhi movie ka naam type karein\n";
    $msg .= "• English ya Hindi mein likh sakte hain\n";
    $msg .= "• Partial names bhi kaam karte hain\n\n";
    $msg .= "🔍 <b>Examples:</b>\n";
    $msg .= "• <code>kgf</code>\n";
    $msg .= "• <code>pushpa hindi</code>\n";
    $msg .= "• <code>avengers english</code>\n";
    $msg .= "• <code>spider-man</code>\n\n";
    $msg .= "❌ <b>Na likhein:</b>\n";
    $msg .= "• Technical questions\n";
    $msg .= "• Player instructions\n";
    $msg .= "• Non-movie queries\n\n";
    $msg .= "📢 <b>Hamare Channels Join Karein:</b>\n";
    $msg .= "🍿 Main: @EntertainmentTadka786\n";
    $msg .= "📥 Requests: @EntertainmentTadka7860\n";
    $msg .= "🎭 Theater Prints: @threater_print_movies\n";
    $msg .= "🔒 Backup: @ETBackup\n\n";
    $msg .= "💬 <b>Help chahiye?</b> /help use karein\n\n";
    $msg .= "🔍 <b>Movie ka naam type karke start karein!</b>";
    
    sendMessage($chat_id, $msg);
}

function sendHelpMessage($chat_id) {
    $msg = "🤖 <b>Entertainment Tadka Bot - Complete Guide</b>\n\n";
    $msg .= "📢 <b>Hamare Channels:</b>\n";
    $msg .= "🍿 Main: @EntertainmentTadka786 - Latest movies\n";
    $msg .= "📥 Requests: @EntertainmentTadka7860 - Support & requests\n";
    $msg .= "🎭 Theater: @threater_print_movies - HD prints\n";
    $msg .= "🔒 Backup: @ETBackup - Data protection\n\n";
    $msg .= "🎯 <b>Search Commands:</b>\n";
    $msg .= "• Bas movie ka naam type karein - Smart search\n\n";
    $msg .= "📁 <b>Browse Commands:</b>\n";
    $msg .= "• /totalupload - Saari movies dekhein\n";
    $msg .= "• /checkcsv - CSV database check karein\n\n";
    $msg .= "📝 <b>Request Commands:</b>\n";
    $msg .= "• /request movie - Movie request karein\n";
    $msg .= "• /myrequests - Request status dekhein\n";
    $msg .= "• @EntertainmentTadka7860 join karein support ke liye\n\n";
    $msg .= "🔗 <b>Channel Commands:</b>\n";
    $msg .= "• /mainchannel - Main channel info\n";
    $msg .= "• /requestchannel - Requests channel\n";
    $msg .= "• /theaterchannel - Theater prints\n";
    $msg .= "• /backupchannel - Backup info\n\n";
    $msg .= "🔧 <b>Admin Commands:</b>\n";
    $msg .= "• /stats - Bot statistics (Owner only)\n\n";
    $msg .= "💡 <b>Tip:</b> Bas koi bhi movie ka naam type karein search ke liye!";
    
    sendMessage($chat_id, $msg);
}

function sendUnknownCommand($chat_id) {
    $msg = "❌ <b>Unknown Command</b>\n\n";
    $msg .= "🔍 <b>Available Commands:</b>\n";
    $msg .= "/start - Welcome message\n";
    $msg .= "/help - Help information\n";
    $msg .= "/totalupload - Browse all movies\n";
    $msg .= "/checkcsv - Check CSV database\n";
    $msg .= "/request - Request movie\n";
    $msg .= "/myrequests - Your requests\n";
    $msg .= "/mainchannel - Main channel info\n";
    $msg .= "/requestchannel - Request channel\n";
    $msg .= "/theaterchannel - Theater prints\n";
    $msg .= "/backupchannel - Backup channel\n\n";
    $msg .= "💡 <b>Movie ka naam type karke search karein!</b>";
    
    sendMessage($chat_id, $msg);
}

/* ============================================ */
/* 🔧 UTILITY FUNCTIONS */
/* ============================================ */

function sendMessage($chat_id, $text, $reply_markup = null) {
    global $BOT_TOKEN;
    
    $api_url = "https://api.telegram.org/bot$BOT_TOKEN/sendMessage";
    
    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "HTML",
        "disable_web_page_preview" => true
    ];
    
    if ($reply_markup) {
        $data["reply_markup"] = json_encode($reply_markup);
    }
    
    $result = @file_get_contents($api_url . '?' . http_build_query($data));
    
    // ✅ LOG ERROR AGAR HUA TOH
    if ($result === false) {
        logEvent("Failed to send message to $chat_id");
    }
    
    return @json_decode($result, true);
}

function editMessage($chat_id, $message_id, $text, $reply_markup = null) {
    global $BOT_TOKEN;
    
    $api_url = "https://api.telegram.org/bot$BOT_TOKEN/editMessageText";
    
    $data = [
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $text,
        "parse_mode" => "HTML",
        "disable_web_page_preview" => true
    ];
    
    if ($reply_markup) {
        $data["reply_markup"] = json_encode($reply_markup);
    }
    
    @file_get_contents($api_url . '?' . http_build_query($data));
}

function sendTypingAction($chat_id) {
    global $BOT_TOKEN;
    
    $api_url = "https://api.telegram.org/bot$BOT_TOKEN/sendChatAction";
    $data = [
        'chat_id' => $chat_id,
        'action' => 'typing'
    ];
    
    @file_get_contents($api_url . '?' . http_build_query($data));
}

function answerCallbackQuery($callback_id, $text = "") {
    global $BOT_TOKEN;
    
    $api_url = "https://api.telegram.org/bot$BOT_TOKEN/answerCallbackQuery";
    $data = [
        'callback_query_id' => $callback_id
    ];
    
    if (!empty($text)) {
        $data['text'] = $text;
        $data['show_alert'] = false;
    }
    
    @file_get_contents($api_url . '?' . http_build_query($data));
}

function deleteMessage($chat_id, $message_id) {
    global $BOT_TOKEN;
    
    $api_url = "https://api.telegram.org/bot$BOT_TOKEN/deleteMessage";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];
    
    @file_get_contents($api_url . '?' . http_build_query($data));
}

function updateSearchStats($user_id) {
    $stats_file = "search_stats.json";
    
    if (!file_exists($stats_file)) {
        $initial_data = [
            'total_searches' => 0,
            'users' => [],
            'last_updated' => date('Y-m-d H:i:s')
        ];
        file_put_contents($stats_file, json_encode($initial_data, JSON_PRETTY_PRINT));
    }
    
    $data = json_decode(file_get_contents($stats_file), true);
    
    // ✅ TOTAL SEARCHES UPDATE
    $data['total_searches'] = ($data['total_searches'] ?? 0) + 1;
    
    // ✅ USER SPECIFIC STATS UPDATE
    if (!isset($data['users'][$user_id])) {
        $data['users'][$user_id] = [
            'searches' => 0,
            'first_search' => date('Y-m-d H:i:s'),
            'last_search' => date('Y-m-d H:i:s')
        ];
    }
    
    $data['users'][$user_id]['searches']++;
    $data['users'][$user_id]['last_search'] = date('Y-m-d H:i:s');
    
    $data['last_updated'] = date('Y-m-d H:i:s');
    
    file_put_contents($stats_file, json_encode($data, JSON_PRETTY_PRINT));
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function logEvent($message) {
    global $LOG_FILE;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    file_put_contents($LOG_FILE, $log_entry, FILE_APPEND);
}

function logUpdate($update) {
    // ✅ ONLY LOG IMPORTANT EVENTS
    if (isset($update['message']['text'])) {
        $text = $update['message']['text'];
        $user_id = $update['message']['from']['id'] ?? 'unknown';
        
        // ✅ DON'T LOG COMMANDS IN DETAIL
        if (strpos($text, '/') !== 0) {
            logEvent("Search from $user_id: " . substr($text, 0, 50));
        }
    }
}

/* ============================================ */
/* 🏠 WELCOME PAGE (DIRECT BROWSER ACCESS) */
/* ============================================ */

function showWelcomePage() {
    global $CSV_FILE, $BOT_TOKEN;
    
    // ✅ CHECK BOT TOKEN
    $bot_token_set = !empty($BOT_TOKEN) && $BOT_TOKEN != "YOUR_BOT_TOKEN_HERE";
    
    // ✅ CHECK CSV FILE
    $csv_exists = file_exists($CSV_FILE);
    $csv_count = 0;
    
    if ($csv_exists) {
        $movies = loadMoviesCSV();
        $csv_count = count($movies);
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🎬 Entertainment Tadka Bot</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: Arial, sans-serif;
            }
            
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
                color: #333;
            }
            
            .container {
                max-width: 1000px;
                margin: 0 auto;
                background: white;
                border-radius: 20px;
                padding: 30px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #667eea;
            }
            
            .logo {
                font-size: 48px;
                margin-bottom: 10px;
            }
            
            h1 {
                color: #333;
                font-size: 32px;
                margin-bottom: 10px;
            }
            
            .tagline {
                color: #666;
                font-size: 18px;
                margin-bottom: 20px;
            }
            
            .status-box {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 20px;
                border-left: 5px solid #28a745;
            }
            
            .status-box.warning {
                border-left-color: #ffc107;
                background: #fff3cd;
            }
            
            .status-box.danger {
                border-left-color: #dc3545;
                background: #f8d7da;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            
            .stat-card {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                text-align: center;
                transition: transform 0.3s;
            }
            
            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            
            .stat-number {
                font-size: 36px;
                font-weight: bold;
                color: #667eea;
                margin: 10px 0;
            }
            
            .stat-label {
                color: #666;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            .channel-list {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .channel-item {
                display: flex;
                align-items: center;
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            
            .channel-item:last-child {
                border-bottom: none;
            }
            
            .channel-icon {
                font-size: 24px;
                margin-right: 15px;
                width: 40px;
                text-align: center;
            }
            
            .btn {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 12px 30px;
                border-radius: 50px;
                text-decoration: none;
                font-weight: bold;
                margin: 10px 5px;
                transition: all 0.3s;
                border: none;
                cursor: pointer;
            }
            
            .btn:hover {
                background: #764ba2;
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            }
            
            .btn-telegram {
                background: #0088cc;
            }
            
            .footer {
                text-align: center;
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                color: #666;
                font-size: 14px;
            }
            
            .instructions {
                background: #e9f7fe;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .instruction-step {
                margin: 15px 0;
                padding-left: 30px;
                position: relative;
            }
            
            .instruction-step:before {
                content: "✓";
                position: absolute;
                left: 0;
                color: #28a745;
                font-weight: bold;
            }
            
            @media (max-width: 768px) {
                .container {
                    padding: 15px;
                }
                
                .stats-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">🎬</div>
                <h1>Entertainment Tadka Bot</h1>
                <p class="tagline">Smart Movie Search & Forwarding System</p>
            </div>';
    
    // ✅ STATUS BOX
    echo '<div class="status-box ' . ($bot_token_set ? '' : 'danger') . '">';
    echo '<h3>📊 Bot Status</h3>';
    
    if ($bot_token_set) {
        echo '<p>✅ Bot Token: Configured</p>';
    } else {
        echo '<p>❌ Bot Token: NOT CONFIGURED</p>';
        echo '<p>Please set $BOT_TOKEN variable in index.php</p>';
    }
    
    if ($csv_exists) {
        echo '<p>✅ CSV Database: ' . $csv_count . ' movies loaded</p>';
    } else {
        echo '<p>❌ CSV Database: File not found</p>';
    }
    
    echo '</div>';
    
    // ✅ QUICK STATS
    echo '<div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎬</div>
                <div class="stat-number">' . $csv_count . '</div>
                <div class="stat-label">Total Movies</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🤖</div>
                <div class="stat-number">' . ($bot_token_set ? 'Active' : 'Inactive') . '</div>
                <div class="stat-label">Bot Status</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🚀</div>
                <div class="stat-number">v2.0</div>
                <div class="stat-label">Version</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-number">' . date('d/m/Y') . '</div>
                <div class="stat-label">Last Updated</div>
            </div>
        </div>';
    
    // ✅ CHANNEL LIST
    echo '<div class="channel-list">
            <h3>📢 Our Channels Network</h3>
            
            <div class="channel-item">
                <div class="channel-icon">🍿</div>
                <div>
                    <strong>@EntertainmentTadka786</strong><br>
                    <small>Main Channel - Latest Movies & Series</small>
                </div>
            </div>
            
            <div class="channel-item">
                <div class="channel-icon">📥</div>
                <div>
                    <strong>@EntertainmentTadka7860</strong><br>
                    <small>Requests & Support Group</small>
                </div>
            </div>
            
            <div class="channel-item">
                <div class="channel-icon">🎭</div>
                <div>
                    <strong>@threater_print_movies</strong><br>
                    <small>Theater Prints & HD Quality</small>
                </div>
            </div>
            
            <div class="channel-item">
                <div class="channel-icon">🔒</div>
                <div>
                    <strong>@ETBackup</strong><br>
                    <small>Backup Channel - Data Protection</small>
                </div>
            </div>
        </div>';
    
    // ✅ SETUP INSTRUCTIONS
    echo '<div class="instructions">
            <h3>⚡ Quick Setup Guide</h3>
            
            <div class="instruction-step">
                Replace <code>$BOT_TOKEN</code> with your actual bot token
            </div>
            
            <div class="instruction-step">
                Replace <code>$OWNER_ID</code> with your Telegram ID
            </div>
            
            <div class="instruction-step">
                Upload <code>index.php</code> to your web server
            </div>
            
            <div class="instruction-step">
                Create <code>movies.csv</code> with correct format
            </div>
            
            <div class="instruction-step">
                Set webhook: <code>https://yourdomain.com/index.php?setwebhook=1</code>
            </div>
        </div>';
    
    // ✅ ACTION BUTTONS
    echo '<div style="text-align: center; margin: 30px 0;">
            <a href="?setwebhook=1" class="btn">🚀 Set Webhook</a>
            <a href="check_config.php" class="btn">🔧 Check Config</a>
            <a href="' . $CSV_FILE . '" class="btn" download>📥 Download CSV</a>
            <a href="https://t.me/EntertainmentTadkaBot" target="_blank" class="btn btn-telegram">💬 Test Bot</a>
        </div>';
    
    // ✅ BOT FEATURES
    echo '<div style="background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>🌟 Bot Features</h3>
            <ul style="line-height: 2; margin-left: 20px;">
                <li>🎯 Smart Fuzzy Search with suggestions</li>
                <li>📤 Automatic forwarding from channels</li>
                <li>💡 Smart suggestions for related movies</li>
                <li>📝 Movie request system</li>
                <li>📊 Pagination for browsing all movies</li>
                <li>🔒 Private channel support</li>
                <li>📈 Statistics and logging</li>
                <li>📱 Responsive web interface</li>
            </ul>
        </div>';
    
    // ✅ CSV FORMAT INFO
    echo '<div style="background: #fff3e0; padding: 15px; border-radius: 10px; margin: 20px 0;">
            <h4>📁 CSV Format (LOCKED)</h4>
            <code style="background: #333; color: #fff; padding: 10px; border-radius: 5px; display: block; margin: 10px 0;">
                movie_name,message_id,channel_id
            </code>
            <p><strong>Example:</strong></p>
            <code style="background: #f5f5f5; padding: 5px 10px; border-radius: 3px; display: block;">
                KGF Chapter 1,123,-1003181705395<br>
                Pushpa Hindi,124,-1003181705395<br>
                Avengers Endgame,125,-1002831605258
            </code>
        </div>';
    
    // ✅ FOOTER
    echo '<div class="footer">
            <p>🎬 <strong>Entertainment Tadka Bot</strong> - Version 2.0</p>
            <p>📞 Support: @EntertainmentTadka7860</p>
            <p>📢 Main Channel: @EntertainmentTadka786</p>
            <p>© ' . date('Y') . ' Entertainment Tadka Team. All rights reserved.</p>
        </div>';
    
    echo '</div>
    </body>
    </html>';
}

/* ============================================ */
/* 🌐 WEBHOOK SETUP FUNCTION */
/* ============================================ */

// ✅ AGAR SETWEBHOOK PARAMETER HAI TOH SETUP KARO
if (isset($_GET['setwebhook'])) {
    setupWebhook();
}

function setupWebhook() {
    global $BOT_TOKEN;
    
    // ✅ CURRENT URL PAKDO
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $current_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $webhook_url = str_replace('?setwebhook=1', '', $current_url);
    
    // ✅ WEBHOOK SET KARO
    $api_url = "https://api.telegram.org/bot$BOT_TOKEN/setWebhook";
    $data = ['url' => $webhook_url];
    
    $result = @file_get_contents($api_url . '?' . http_build_query($data));
    $result_data = @json_decode($result, true);
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>🚀 Webhook Setup</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin: 20px 0; }
            .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px 0; }
            .info { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 10px; margin: 20px 0; }
            .btn { display: inline-block; padding: 10px 20px; background: #0088cc; color: white; 
                   text-decoration: none; border-radius: 5px; margin: 5px; }
        </style>
    </head>
    <body>
        <h1>🚀 Webhook Setup - Entertainment Tadka Bot</h1>';
    
    if ($result_data && isset($result_data['ok']) && $result_data['ok']) {
        echo '<div class="success">
                <h3>✅ Webhook Successfully Set!</h3>
                <p><strong>URL:</strong> ' . $webhook_url . '</p>
                <p><strong>Status:</strong> Active</p>
            </div>';
    } else {
        echo '<div class="error">
                <h3>❌ Webhook Setup Failed!</h3>
                <p><strong>Error:</strong> ' . ($result_data['description'] ?? 'Unknown error') . '</p>
            </div>';
    }
    
    echo '<div class="info">
            <h3>📋 Next Steps:</h3>
            <ol>
                <li>Bot ko channels mein admin banao</li>
                <li>Movie search test karo: @EntertainmentTadkaBot</li>
                <li>Channels mein movies add karo</li>
                <li>Check CSV format: movies.csv</li>
            </ol>
        </div>
        
        <div>
            <a href="' . str_replace('?setwebhook=1', '', $current_url) . '" class="btn">🏠 Home</a>
            <a href="https://t.me/EntertainmentTadkaBot" target="_blank" class="btn">💬 Test Bot</a>
            <a href="check_config.php" class="btn">🔧 Check Config</a>
        </div>
    </body>
    </html>';
    
    exit;
}

/* ============================================ */
/* ✅ END OF FILE - ENTERTAINMENT TADKA BOT */
/* ============================================ */
?>
