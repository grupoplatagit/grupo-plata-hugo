<?php
/**
 * Test: Obtener media de Meta Graph API
 * Simula lo que hace wa-media.php pero con más logging
 */

require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/functions.php';

$db = getDB();

echo "=== TEST: OBTENER MEDIA DE META ===\n\n";

// Obtener último mensaje multimedia de la BD
$msg = $db->query("
    SELECT *
    FROM wa_messages
    WHERE message_type IN ('audio', 'image', 'sticker', 'document', 'video')
    AND media_id IS NOT NULL
    AND media_id != ''
    ORDER BY created_at DESC
    LIMIT 1
")->fetch();

if (!$msg) {
    echo "❌ No hay mensajes multimedia con media_id en la BD\n";
    exit;
}

echo "✅ Mensaje multimedia encontrado:\n";
echo "   ID: {$msg['id']}\n";
echo "   Tipo: {$msg['message_type']}\n";
echo "   media_id: {$msg['media_id']}\n";
echo "   mime_type: {$msg['mime_type']}\n\n";

// Obtener token
$token = getSetting($db, 'wa_token');
if (!$token) {
    echo "❌ No hay Access Token configurado\n";
    exit;
}

echo "Token presente: " . (strlen($token) > 20 ? substr($token, 0, 20) . "..." : "CORTO") . "\n\n";

// PASO 1: Solicitar metadata del media a Meta
echo "--- PASO 1: Obtener metadata de Meta ---\n";
echo "GET https://graph.instagram.com/v18.0/{$msg['media_id']}\n";
echo "Headers: Authorization: Bearer [token]\n\n";

$meta_url = "https://graph.instagram.com/v18.0/{$msg['media_id']}";
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

echo "HTTP Code: $http_code\n";
echo "cURL Error: " . ($curl_error ?: 'ninguno') . "\n";
echo "Response (primeros 500 chars):\n";
echo substr($resp, 0, 500) . "\n\n";

if ($http_code !== 200) {
    echo "❌ Meta devolvió error. Problema en PASO 1.\n";
    exit;
}

$meta_data = json_decode($resp, true);
if (!$meta_data) {
    echo "❌ Response no es JSON válido\n";
    exit;
}

echo "JSON decodificado:\n";
echo json_encode($meta_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

if (!isset($meta_data['url'])) {
    echo "❌ Meta no devolvió 'url' en la respuesta\n";
    exit;
}

$file_url = $meta_data['url'];
echo "✅ URL obtenida de Meta:\n";
echo substr($file_url, 0, 100) . "...\n\n";

// PASO 2: Descargar archivo desde la URL
echo "--- PASO 2: Descargar archivo ---\n";
echo "GET " . substr($file_url, 0, 60) . "...\n\n";

$ch = curl_init($file_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$file_content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Content-Type: $content_type\n";
echo "cURL Error: " . ($curl_error ?: 'ninguno') . "\n";
echo "Bytes recibidos: " . strlen($file_content) . "\n\n";

if ($http_code !== 200) {
    echo "❌ Error descargando archivo. HTTP $http_code\n";
    exit;
}

if (empty($file_content)) {
    echo "❌ Archivo vacío\n";
    exit;
}

echo "✅ Archivo descargado correctamente\n";
echo "   Tamaño: " . strlen($file_content) . " bytes\n";
echo "   Primeros 20 bytes (hex): " . bin2hex(substr($file_content, 0, 20)) . "\n\n";

// Validar tipo
echo "--- VALIDACIÓN ---\n";
echo "mime_type en BD: {$msg['mime_type']}\n";
echo "Content-Type real: $content_type\n";

if ($msg['mime_type'] && $content_type && $msg['mime_type'] !== $content_type) {
    echo "⚠️  Mismatch entre BD y Meta\n";
}

// Guardar archivo para análisis
$test_file = __DIR__ . '/test-output.' . ($msg['message_type'] === 'audio' ? 'ogg' : ($msg['message_type'] === 'sticker' ? 'webp' : 'bin'));
file_put_contents($test_file, $file_content);
echo "✅ Archivo guardado en: $test_file\n";
echo "\nPuedes verificar si es válido en tu computadora.\n";
?>
