<?php
/**
 * Proxy de multimedia WhatsApp
 * - Recibe media_id del navegador
 * - Actúa como proxy entre navegador y Meta Graph API
 * - Obtiene URL temporal de Meta
 * - Descarga el archivo con Bearer token
 * - Envía directamente al navegador sin guardar en disco
 * - El Access Token nunca se expone al frontend
 */

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';

// Validar sesión del usuario
requireLogin();

$db = getDB();
$media_id = $_GET['media_id'] ?? '';
$api_version = 'v18.0';
$log_prefix = '[WA-MEDIA]';

// Log helper
function logMedia($msg) {
    global $log_prefix;
    $logFile = __DIR__ . '/../../logs/wa-media-proxy.log';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " $log_prefix $msg\n", FILE_APPEND);
}

logMedia("Inicio - media_id=$media_id");

// Validar media_id
if (!$media_id || strlen($media_id) < 5) {
    logMedia("ERROR: media_id inválido");
    http_response_code(400);
    exit('Invalid media_id');
}

// Obtener token de configuración
$token = getSetting($db, 'wa_token');
if (!$token) {
    logMedia("ERROR: Access token no configurado");
    http_response_code(500);
    exit('Token not configured');
}

// PASO 1: Obtener URL de Media desde Meta Graph API
logMedia("PASO1: GET https://graph.facebook.com/$api_version/$media_id");
$metaUrl = "https://graph.facebook.com/{$api_version}/{$media_id}";
$ch = curl_init($metaUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$metaResp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErrno = curl_errno($ch);
$curlError = curl_error($ch);
curl_close($ch);

logMedia("Meta HTTP: $httpCode, curl_errno: $curlErrno");

if ($httpCode !== 200) {
    logMedia("ERROR: Meta API returned $httpCode");
    http_response_code(502);
    exit('Failed to get media from Meta');
}

$metaData = json_decode($metaResp, true);
if (!$metaData || !isset($metaData['url'])) {
    logMedia("ERROR: Meta no devolvió URL");
    http_response_code(502);
    exit('Invalid response from Meta');
}

$fileUrl = $metaData['url'];
$mimeType = $metaData['mime_type'] ?? 'application/octet-stream';
$fileSize = $metaData['file_size'] ?? 0;

logMedia("Meta OK - mime_type=$mimeType, size=$fileSize");

// PASO 2: Descargar archivo desde URL temporal de Meta
logMedia("PASO2: Descargando archivo (size=$fileSize bytes)");
$ch = curl_init($fileUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
]);
$fileContent = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErrno2 = curl_errno($ch);
$curlError2 = curl_error($ch);
curl_close($ch);

logMedia("Download HTTP: $httpCode2, curl_errno: $curlErrno2, received: " . strlen($fileContent) . " bytes");

if ($httpCode2 !== 200 || empty($fileContent)) {
    logMedia("ERROR: Descarga falló - HTTP $httpCode2, curl_errno: $curlErrno2");
    http_response_code(502);
    exit('Failed to download media');
}

// SUCCESS: Enviar archivo al navegador
logMedia("OK: Enviando " . strlen($fileContent) . " bytes de tipo $mimeType al navegador");

// Limpiar cualquier output buffer accidental
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Headers para servir la imagen
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . strlen($fileContent));
header('Content-Disposition: inline');
header('Cache-Control: private, max-age=300');

echo $fileContent;
exit;
?>
