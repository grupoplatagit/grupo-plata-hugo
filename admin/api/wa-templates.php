<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';

header('Content-Type: application/json');
requireLogin();

$db = getDB();

$token  = getSetting($db, 'wa_token');
$wabaId = getSetting($db, 'wa_waba_id');

if (empty($token) || empty($wabaId)) {
    echo json_encode(['ok' => false, 'msg' => 'Token o WABA ID no configurado', 'templates' => []]);
    exit;
}

$url = "https://graph.facebook.com/v18.0/{$wabaId}/message_templates?fields=name,status,language,components&limit=50";
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    CURLOPT_TIMEOUT        => 10,
]);
$resp   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = json_decode($resp, true);

if ($status !== 200) {
    echo json_encode(['ok' => false, 'msg' => $json['error']['message'] ?? 'Error', 'templates' => []]);
    exit;
}

// Filter only APPROVED templates
$templates = array_filter($json['data'] ?? [], fn($t) => $t['status'] === 'APPROVED');
$templates = array_values($templates);

// Extract preview text from components
foreach ($templates as &$t) {
    $preview = '';
    foreach ($t['components'] ?? [] as $comp) {
        if ($comp['type'] === 'BODY') {
            $preview = $comp['text'] ?? '';
            break;
        }
    }
    $t['preview'] = $preview;
}

echo json_encode(['ok' => true, 'templates' => $templates]);
