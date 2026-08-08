<?php
// Registrar nuevo Phone Number ID en Meta WhatsApp Cloud API
// Uso: POST /admin/api/register-phone.php
// Body: { "phone_number_id": "1328325270355430" }

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/db.php';

requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Solo POST permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$phoneNumberId = trim($input['phone_number_id'] ?? '');

if (!$phoneNumberId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'phone_number_id requerido']);
    exit;
}

$db = getDB();
$token = getSetting($db, 'wa_token');
$pin = getSetting($db, 'wa_pin') ?? '123456';

if (!$token) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Access Token no configurado']);
    exit;
}

// Registrar en Meta
$url = "https://graph.facebook.com/v23.0/{$phoneNumberId}/register";
$payload = [
    'messaging_product' => 'whatsapp',
    'pin' => $pin
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error CURL: ' . $curl_error]);
    exit;
}

$result = json_decode($response, true);

// Log del registro
@file_put_contents(__DIR__ . '/../../logs/phone-register.log',
    date('Y-m-d H:i:s') . " Intento registro: Phone ID=$phoneNumberId, HTTP=$http_code\n" .
    "Response: " . substr($response, 0, 200) . "\n\n",
    FILE_APPEND
);

if ($http_code === 200 && isset($result['success']) && $result['success']) {
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'msg' => 'Número registrado exitosamente en Meta',
        'phone_number_id' => $phoneNumberId,
        'meta_response' => $result
    ]);
} else {
    http_response_code($http_code);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error registrando número en Meta',
        'phone_number_id' => $phoneNumberId,
        'http_code' => $http_code,
        'meta_response' => $result
    ]);
}
?>
