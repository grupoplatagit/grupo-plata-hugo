<?php
// Webhook ultra simple - sin carpetas

$method = $_SERVER['REQUEST_METHOD'];
$time = date('Y-m-d H:i:s');

// GET - Meta verification
if ($method === 'GET') {
    $mode = $_GET['hub.mode'] ?? '';
    $challenge = $_GET['hub.challenge'] ?? '';

    if ($mode === 'subscribe') {
        http_response_code(200);
        echo $challenge;
        exit;
    }
    http_response_code(403);
    exit;
}

// POST - Incoming webhook
if ($method === 'POST') {
    file_put_contents(__DIR__ . '/../logs/webhook.log', "$time [POST] OK\n", FILE_APPEND);
    http_response_code(200);
    echo 'OK';
    exit;
}

http_response_code(405);
exit;
?>
