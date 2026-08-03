<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';

$db = getDB();
$dir = __DIR__ . '/../../logs';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// GET - Webhook verification (Meta sends this to verify)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub.mode'] ?? '';
    $token = $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub.challenge'] ?? '';

    file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " [GET] mode=$mode token=$token\n", FILE_APPEND);

    if ($mode === 'subscribe') {
        http_response_code(200);
        echo $challenge;
        exit;
    }

    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// POST - Incoming messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " [POST] Received webhook\n", FILE_APPEND);

    if (($data['object'] ?? '') === 'whatsapp_business_account') {
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $msg) {
                    $from = $msg['from'] ?? '';
                    $body = $msg['text']['body'] ?? '';
                    file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " Message from $from: $body\n", FILE_APPEND);
                }
            }
        }
    }

    http_response_code(200);
    echo 'OK';
    exit;
}

http_response_code(405);
exit;
?>
