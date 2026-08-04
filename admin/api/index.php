<?php
// Webhook for WhatsApp Business API

$dir = __DIR__ . '/logs';
if (!is_dir($dir)) @mkdir($dir, 0755, true);

// GET - Webhook verification (Meta's challenge)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub.mode'] ?? '';
    $token = $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub.challenge'] ?? '';

    @file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " GET verification\n  mode=$mode\n  token=$token\n  challenge=$challenge\n", FILE_APPEND);

    // Meta verification: check mode AND token, respond with challenge
    if ($mode === 'subscribe' && $token === 'grupo_plata_verify_2024' && $challenge) {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }

    // Token or mode mismatch
    http_response_code(403);
    exit;
}

// POST - Webhook for incoming messages
header('Content-Type: application/json');

// POST - Incoming messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    @file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " POST: " . substr(json_encode($data), 0, 200) . "\n", FILE_APPEND);

    if (($data['object'] ?? '') === 'whatsapp_business_account') {
        // Process messages
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Handle incoming messages
                foreach ($value['messages'] ?? [] as $msg) {
                    $from = $msg['from'] ?? '';
                    $body = $msg['text']['body'] ?? '';
                    $type = $msg['type'] ?? '';

                    @file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " Message from $from: $body ($type)\n", FILE_APPEND);
                }

                // Handle status updates
                foreach ($value['statuses'] ?? [] as $st) {
                    $status = $st['status'] ?? '';
                    @file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " Status: $status\n", FILE_APPEND);
                }
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(405);
echo 'Method Not Allowed';
exit;
?>
