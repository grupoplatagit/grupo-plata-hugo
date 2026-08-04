<?php
/**
 * Endpoint seguro para servir multimedia de WhatsApp
 * - Autenticación requerida
 * - Access Token se mantiene en backend
 * - Validación de media_id en BD
 */

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';

requireLogin();

$db = getDB();
$media_id = $_GET['media_id'] ?? '';
$type = $_GET['type'] ?? '';
$download = isset($_GET['download']);

// Validar media_id
if (!$media_id || strlen($media_id) < 5) {
    http_response_code(400);
    exit('Invalid media_id');
}

// Buscar media_id en BD y validar que existe
$msg = $db->prepare("SELECT * FROM wa_messages WHERE media_id = ? LIMIT 1")
    ->execute([$media_id])
    ->fetch();

if (!$msg) {
    http_response_code(404);
    exit('Media not found');
}

// Obtener Access Token de BD (NUNCA del frontend)
$token = getSetting($db, 'wa_token');
if (!$token) {
    http_response_code(500);
    exit('WhatsApp not configured');
}

// Solicitar información del media a Meta
$meta_url = "https://graph.instagram.com/v18.0/{$media_id}?access_token={$token}";
$ch = curl_init($meta_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$resp) {
    http_response_code(502);
    exit('Failed to get media from Meta');
}

$meta_data = json_decode($resp, true);
if (!isset($meta_data['url'])) {
    http_response_code(502);
    exit('Invalid Meta response');
}

// Descargar archivo desde URL de Meta
$file_url = $meta_data['url'];
$ch = curl_init($file_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$file_content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($file_content)) {
    http_response_code(502);
    exit('Failed to download media');
}

// Servir al navegador
header('Content-Type: ' . ($msg['mime_type'] ?: 'application/octet-stream'));
header('Content-Length: ' . strlen($file_content));

if ($download) {
    $filename = $msg['file_name'] ?: 'media';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Disposition: inline');
}

echo $file_content;
exit;
?>
