<?php
// Ultra simple webhook - no dependencies

// Log to file
$log_file = __DIR__ . '/../../logs/webhook.log';
@mkdir(dirname($log_file), 0755, true);

$method = $_SERVER['REQUEST_METHOD'];
$time = date('Y-m-d H:i:s');

// GET - Webhook verification
if ($method === 'GET') {
    $mode = $_GET['hub.mode'] ?? '';
    $token = $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub.challenge'] ?? '';

    file_put_contents($log_file, "$time [GET] mode=$mode token=$token\n", FILE_APPEND);

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
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    file_put_contents($log_file, "$time [POST] " . substr($raw, 0, 300) . "\n", FILE_APPEND);

    http_response_code(200);
    echo 'OK';
    exit;
}

http_response_code(405);
echo 'Method not allowed';
exit;
?>
