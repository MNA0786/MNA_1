<?php
set_time_limit(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require __DIR__ . '/vendor/autoload.php';

use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;

/* ================== ENV ================== */
$API_ID   = (int) getenv('API_ID');
$API_HASH = getenv('API_HASH');

$OWNER_ID = 1080317415;
$REQUEST_GROUP_ID = -1003083386043;

/* ================== USERS FILE ================== */
$USERS_FILE = __DIR__ . '/users.json';
if (!file_exists($USERS_FILE)) {
    file_put_contents($USERS_FILE, '{}');
}
chmod($USERS_FILE, 0777);

/* ================== CHANNELS ================== */
$SOURCE_CHANNELS = [
    -1003181705395 => 'public',
    -1002831605258 => 'public',
    -1002964109368 => 'public',
    -1003251791991 => 'private',
    -1002337293281 => 'private',
    -1003614546520 => 'private',
];

class UserBotHandler extends EventHandler
{
    public function onUpdateNewMessage($update)
    {
        if (!isset($update['message']['message'])) return;

        $chat_id = $update['message']['peer_id']['chat_id'] ?? null;
        $user_id = $update['message']['from_id']['user_id'] ?? null;
        $text    = trim($update['message']['message']);

        global $REQUEST_GROUP_ID, $OWNER_ID, $SOURCE_CHANNELS;

        if ($chat_id !== abs($REQUEST_GROUP_ID) && $user_id !== $OWNER_ID) return;
        if (strlen($text) < 3) return;

        if ($text === '/start' || $text === '/help') {
            $this->messages->sendMessage([
                'peer' => $chat_id,
                'message' => "🎬 Entertainment Tadka\nMovie ya series ka naam likho",
            ]);
            return;
        }

        $query = strtolower(preg_replace('/[^a-z0-9 ]/i', '', $text));
        $results = [];

        foreach ($SOURCE_CHANNELS as $channel => $type) {
            try {
                $history = $this->messages->getHistory([
                    'peer' => $channel,
                    'limit' => 30
                ]);

                foreach ($history['messages'] as $m) {
                    if (!isset($m['message'])) continue;
                    similar_text($query, strtolower($m['message']), $p);

                    if ($p > 40 || str_contains(strtolower($m['message']), $query)) {
                        $results[] = [
                            'msg' => $m,
                            'channel' => $channel,
                            'type' => $type,
                            'score' => $p
                        ];
                    }
                }
            } catch (\Throwable $e) {}
        }

        if (!$results) {
            $this->messages->sendMessage([
                'peer' => $chat_id,
                'message' => "❌ No result found"
            ]);
            return;
        }

        usort($results, fn($a,$b)=>$b['score']<=>$a['score']);
        $r = $results[0];

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

/* ================== START BOT ================== */
$settings = [
    'app_info' => [
        'api_id' => $API_ID,
        'api_hash' => $API_HASH
    ]
];

$Madeline = new API(__DIR__ . '/userbot.session', $settings);
$Madeline->start();
$Madeline->setEventHandler(UserBotHandler::class);

/* ================== MAIN LOOP ================== */
while (true) {
    $Madeline->loop();
    sleep(1);
}
