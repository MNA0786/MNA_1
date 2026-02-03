<?php
/* =========================================================
   RENDER.COM + MADELINEPROTO USERBOT
   FULL & FINAL INDEX.PHP
   ========================================================= */

set_time_limit(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

/* ================== AUTOLOAD ================== */
require __DIR__ . '/vendor/autoload.php';

use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;

/* ================== ENV CONFIG ================== */
$API_ID   = (int) getenv('API_ID');        // Telegram API ID
$API_HASH = getenv('API_HASH');             // Telegram API HASH

$OWNER_ID = 1080317415;
$REQUEST_GROUP_ID = -1003083386043;

/* ================== STORAGE FILE ================== */
$USERS_FILE = __DIR__ . '/users.json';
if (!file_exists($USERS_FILE)) {
    file_put_contents($USERS_FILE, json_encode([]));
}
chmod($USERS_FILE, 0777);

/* ================== SOURCE CHANNELS ================== */
$SOURCE_CHANNELS = [
    // public
    -1003181705395 => 'public', // @EntertainmentTadka786
    -1002831605258 => 'public', // @threater_print_movies
    -1002964109368 => 'public', // @ETBackup

    // private
    -1003251791991 => 'private',
    -1002337293281 => 'private',
    -1003614546520 => 'private',
];

/* ================== EVENT HANDLER ================== */
class UserBotHandler extends EventHandler
{
    public static array $cache = [];

    public function onUpdateNewMessage($update)
    {
        if (!isset($update['message']['message'])) return;

        $chat_id = $update['message']['peer_id']['chat_id'] ?? null;
        $user_id = $update['message']['from_id']['user_id'] ?? null;
        $text    = trim($update['message']['message']);

        global $REQUEST_GROUP_ID, $OWNER_ID, $SOURCE_CHANNELS, $USERS_FILE;

        /* Only Request Group OR Owner PM */
        if ($chat_id !== abs($REQUEST_GROUP_ID) && $user_id !== $OWNER_ID) {
            return;
        }

        /* Save user */
        $users = json_decode(file_get_contents($USERS_FILE), true);
        if (!isset($users[$user_id])) {
            $users[$user_id] = time();
            file_put_contents($USERS_FILE, json_encode($users));
        }

        /* Typing */
        $this->messages->setTyping([
            'peer' => $chat_id,
            'action' => ['_' => 'sendMessageTypingAction']
        ]);

        /* ================== /START ================== */
        if ($text === '/start') {
            $msg  = "🎬 *Welcome to Entertainment Tadka!*\n\n";
            $msg .= "📢 *How to use:*\n";
            $msg .= "• Movie / series ka naam likho\n";
            $msg .= "• English ya Hindi dono chalega\n";
            $msg .= "• `theater` add karo theater prints ke liye\n";
            $msg .= "• Partial names bhi work karte hain\n\n";
            $msg .= "🔍 *Examples:*\n";
            $msg .= "• KGF 2 Hindi\n";
            $msg .= "• Pushpa theater\n";
            $msg .= "• IT Welcome to Derry S01\n\n";
            $msg .= "📢 *Channels:*\n";
            $msg .= "🍿 @EntertainmentTadka786\n";
            $msg .= "🎭 @threater_print_movies\n";
            $msg .= "🔒 @ETBackup\n\n";
            $msg .= "💬 Help ke liye `/help` likho";

            $this->messages->sendMessage([
                'peer' => $chat_id,
                'message' => $msg,
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        /* ================== /HELP ================== */
        if ($text === '/help' || $text === '/commands') {
            $msg  = "🤖 *Entertainment Tadka – Help*\n\n";
            $msg .= "🎯 Movie / series ka naam likho\n";
            $msg .= "🧠 Smart spelling support\n";
            $msg .= "🔁 Public = Forward\n";
            $msg .= "🔒 Private = Copy\n";
            $msg .= "💡 Tip: year + language add karo";

            $this->messages->sendMessage([
                'peer' => $chat_id,
                'message' => $msg,
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        /* ================== SHORT TEXT BLOCK ================== */
        if (strlen($text) < 3) return;

        /* ================== SEARCH ================== */
        $query = strtolower(preg_replace('/[^a-z0-9 ]/i', '', $text));
        $results = [];

        foreach ($SOURCE_CHANNELS as $channel_id => $type) {
            try {
                $history = $this->messages->getHistory([
                    'peer' => $channel_id,
                    'limit' => 30
                ]);

                foreach ($history['messages'] as $msg) {
                    if (!isset($msg['message'])) continue;

                    $caption = strtolower($msg['message']);
                    similar_text($query, $caption, $percent);

                    if ($percent > 40 || str_contains($caption, $query)) {
                        $results[] = [
                            'msg' => $msg,
                            'channel' => $channel_id,
                            'type' => $type,
                            'score' => $percent
                        ];
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        /* ================== NO RESULT ================== */
        if (empty($results)) {
            $this->messages->sendMessage([
                'peer' => $chat_id,
                'message' => "❌ No exact match mila.\nSpelling check karo ya short name try karo."
            ]);
            return;
        }

        /* ================== SORT ================== */
        usort($results, fn($a,$b) => $b['score'] <=> $a['score']);
        $results = array_slice($results, 0, 3);

        /* ================== MULTIPLE RESULTS ================== */
        if (count($results) > 1) {
            $list = "🔎 *Multiple results found:*\n\n";
            foreach ($results as $i => $r) {
                $list .= ($i+1)."️⃣ ".$r['msg']['message']."\n\n";
            }
            $list .= "Reply with number (1 / 2 / 3)";

            $this->messages->sendMessage([
                'peer' => $chat_id,
                'message' => $list,
                'parse_mode' => 'Markdown'
            ]);

            self::$cache[$chat_id] = $results;
            return;
        }

        /* ================== DIRECT SEND ================== */
        $this->sendResult($chat_id, $results[0]);
    }

    /* ================== SEND RESULT ================== */
    private function sendResult($chat_id, $r)
    {
        if ($r['type'] === 'public') {
            $this->messages->forwardMessages([
                'to_peer' => $chat_id,
                'from_peer' => $r['channel'],
                'id' => [$r['msg']['id']]
            ]);
        } else {
            $this->messages->copyMessages([
                'to_peer' => $chat_id,
                'from_peer' => $r['channel'],
                'id' => [$r['msg']['id']]
            ]);
        }
    }
}

/* ================== START MADELINE ================== */
$settings = [
    'app_info' => [
        'api_id' => $API_ID,
        'api_hash' => $API_HASH
    ],
];

$Madeline = new API(__DIR__ . '/userbot.session', $settings);
$Madeline->start();
$Madeline->setEventHandler(UserBotHandler::class);

/* ================== KEEP ALIVE HTTP SERVER ================== */
$pid = pcntl_fork();
if ($pid === 0) {
    // Render health check server
    exec("php -S 0.0.0.0:10000");
    exit;
}

/* ================== MAIN LOOP ================== */
while (true) {
    $Madeline->loop();
    sleep(1);
}
