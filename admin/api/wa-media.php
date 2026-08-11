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

// Solicitar información del media a Meta (PASO 1)
$meta_url = "https://graph.facebook.com/v23.0/{$media_id}";
$ch = curl_init($meta_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
    ],
]);
$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code !== 200) {
    http_response_code(502);
    error_log("[wa-media] Meta API error: HTTP $http_code, curl_error: $curl_error, media_id: $media_id");
    exit('Failed to get media info from Meta (HTTP ' . $http_code . ')');
}

if (!$resp) {
    http_response_code(502);
    error_log("[wa-media] Empty response from Meta, media_id: $media_id");
    exit('Empty response from Meta');
}

$meta_data = json_decode($resp, true);
if (!$meta_data || !isset($meta_data['url'])) {
    http_response_code(502);
    error_log("[wa-media] Invalid Meta response, media_id: $media_id, response: " . substr($resp, 0, 200));
    exit('Invalid response from Meta');
}

// Descargar archivo desde URL de Meta (PASO 2)
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
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code !== 200) {
    http_response_code(502);
    error_log("[wa-media] Download error: HTTP $http_code, curl_error: $curl_error, media_id: $media_id");
    exit('Failed to download media (HTTP ' . $http_code . ')');
}

if (empty($file_content)) {
    http_response_code(502);
    error_log("[wa-media] Empty file content, media_id: $media_id");
    exit('Empty file from Meta');
}

// Limpiar output buffer antes de servir binario
if (ob_get_level()) {
    ob_end_clean();
}

// Servir al navegador
$content_type = $msg['mime_type'] ?: 'application/octet-stream';
header('Content-Type: ' . $content_type);
header('Content-Length: ' . strlen($file_content));
header('Cache-Control: public, max-age=3600');

if ($download) {
    $filename = $msg['file_name'] ?: 'media';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Disposition: inline');
}

// Enviar binario
echo $file_content;
exit;
?>
