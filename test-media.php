<?php
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/functions.php';

$db = getDB();

echo "<h2>Test Media Download</h2>";

// Obtener un media_id de imágenes
$msg = $db->prepare("SELECT * FROM wa_messages WHERE message_type = 'image' LIMIT 1")
    ->execute()
    ->fetch();

if (!$msg) {
    echo "❌ No hay imágenes en la BD";
    exit;
}

$media_id = $msg['media_id'];
echo "<p>Testeando con media_id: <b>$media_id</b></p>";
echo "<hr>";

// Obtener token
$token = getSetting($db, 'wa_token');
echo "<p>✓ Token obtenido: " . substr($token, 0, 20) . "...</p>";

// PASO 1: Obtener info de Meta
echo "<p><b>PASO 1: Solicitar info a Meta</b></p>";
$meta_url = "https://graph.facebook.com/v23.0/{$media_id}";
echo "URL: $meta_url<br>";

$ch = curl_init($meta_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
    ],
]);
$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: <b>$http_code</b><br>";
if ($curl_error) echo "CURL Error: <b>$curl_error</b><br>";

if ($http_code !== 200) {
    echo "<p style='color:red'><b>❌ Error en Meta API (HTTP $http_code)</b></p>";
    echo "<pre>" . htmlspecialchars($resp) . "</pre>";
    exit;
}

echo "<p style='color:green'>✓ Meta respondió OK</p>";

$meta_data = json_decode($resp, true);
echo "<pre>";
print_r($meta_data);
echo "</pre>";

if (!isset($meta_data['url'])) {
    echo "<p style='color:red'><b>❌ Meta no devolvió URL</b></p>";
    exit;
}

// PASO 2: Descargar archivo
echo "<p><b>PASO 2: Descargar archivo de Meta</b></p>";
$file_url = $meta_data['url'];
echo "URL: " . substr($file_url, 0, 100) . "...<br>";

$ch = curl_init($file_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$file_content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: <b>$http_code</b><br>";
if ($curl_error) echo "CURL Error: <b>$curl_error</b><br>";

if ($http_code !== 200) {
    echo "<p style='color:red'><b>❌ Error descargando archivo (HTTP $http_code)</b></p>";
    exit;
}

echo "<p style='color:green'>✓ Archivo descargado: " . strlen($file_content) . " bytes</p>";

// Mostrar imagen
echo "<hr>";
echo "<p><b>IMAGEN:</b></p>";
$base64 = base64_encode($file_content);
$mime = $msg['mime_type'] ?: 'image/jpeg';
echo "<img src='data:$mime;base64,$base64' style='max-width:300px;border:1px solid #ccc;'>";

echo "<p style='color:green'><b>✅ TODO FUNCIONA OK</b></p>";
?>
