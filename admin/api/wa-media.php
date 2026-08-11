<?php
/**
 * Endpoint para servir multimedia de WhatsApp
 * - Archivo almacenado localmente (descargado durante webhook)
 * - NO requiere acceso a Meta
 * - El Access Token nunca se expone al frontend
 */

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/functions.php';

$db = getDB();
$media_id = $_GET['media_id'] ?? '';

// Validar media_id
if (!$media_id || strlen($media_id) < 5) {
    http_response_code(400);
    exit('Invalid media_id');
}

// Buscar en BD
$msg = $db->prepare("SELECT * FROM wa_messages WHERE media_id = ? LIMIT 1")
    ->execute([$media_id])
    ->fetch();

if (!$msg) {
    http_response_code(404);
    exit('Media not found');
}

// Caso 1: Archivo ya descargado
if ($msg['media_url'] && $msg['media_status'] === 'downloaded') {
    $filepath = __DIR__ . '/../../' . ltrim($msg['media_url'], '/');
    if (file_exists($filepath)) {
        serveLocalFile($filepath, $msg['mime_type']);
        exit;
    }
}

// Caso 2: Archivo no descargado aún - intentar descargarlo ahora
if ($msg['media_status'] === 'pending' || $msg['media_status'] === 'error') {
    $token = getSetting($db, 'wa_token');
    if ($token) {
        $downloadResult = downloadWAMedia($media_id, $token, $db);
        if ($downloadResult['ok']) {
            $filepath = __DIR__ . '/../../' . ltrim($downloadResult['url'], '/');
            if (file_exists($filepath)) {
                $db->prepare("UPDATE wa_messages SET media_url = ?, media_status = 'downloaded' WHERE media_id = ?")
                   ->execute([$downloadResult['url'], $media_id]);
                serveLocalFile($filepath, $downloadResult['mime_type']);
                exit;
            }
        }
    }
}

// Si llegamos aquí, no pudimos servir el archivo
http_response_code(503);
exit('Media unavailable');

function serveLocalFile(string $filepath, string $mimeType): void {
    if (ob_get_level()) ob_end_clean();

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filepath));
    header('Content-Disposition: inline');
    header('Cache-Control: public, max-age=86400');

    readfile($filepath);
}
?>
