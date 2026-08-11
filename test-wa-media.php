<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/functions.php';

$db = getDB();
$media_id = '1729369971703727';

echo "<h1>TEST AISLADO: WhatsApp Cloud API Media</h1>";
echo "<hr>";

// ============================================================
// PRUEBA 1: Cargar configuración
// ============================================================
echo "<h2>PRUEBA 1: Verificar configuración</h2>";

$token = getSetting($db, 'wa_token');
$phone_id = getSetting($db, 'wa_phone_id');
$api_version = 'v18.0'; // Meta Graph API version

echo "TOKEN: " . ($token ? "✅ CARGADO" : "❌ NO CARGADO") . "<br>";
echo "PHONE_NUMBER_ID: " . ($phone_id ?: "❌ NO CONFIGURADO") . "<br>";
echo "API_VERSION: " . $api_version . "<br>";
echo "<hr>";

if (!$token || !$phone_id) {
    echo "❌ CONFIGURACIÓN INCOMPLETA. No se pueden continuar las pruebas.";
    exit;
}

// ============================================================
// PRUEBA 2: GET Meta Graph API para obtener URL de media
// ============================================================
echo "<h2>PRUEBA 2: Obtener URL de Media desde Meta</h2>";

$metaUrl = "https://graph.facebook.com/{$api_version}/{$media_id}";
echo "URL: https://graph.facebook.com/{$api_version}/{$media_id}<br>";
echo "METHOD: GET<br>";
echo "HEADER: Authorization: Bearer [TOKEN_OCULTO]<br><br>";

$ch = curl_init($metaUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$metaResp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

echo "HTTP STATUS: " . $httpCode . "<br>";

if ($httpCode === 200) {
    $metaData = json_decode($metaResp, true);
    echo "✅ Respuesta Meta JSON:<br>";

    // Mostrar JSON ocultando datos sensibles
    $safeData = $metaData;
    if (isset($safeData['url'])) {
        $safeData['url'] = '[URL_OCULTA]';
    }
    echo "<pre>" . json_encode($safeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";

    echo "<br>Campos esperados:<br>";
    echo "  'url': " . (isset($metaData['url']) ? "✅ PRESENTE" : "❌ FALTANTE") . "<br>";
    echo "  'mime_type': " . (isset($metaData['mime_type']) ? "✅ PRESENTE" : "❌ FALTANTE") . "<br>";
    echo "  'file_size': " . (isset($metaData['file_size']) ? "✅ PRESENTE" : "❌ FALTANTE") . "<br>";
    echo "  'id': " . (isset($metaData['id']) ? "✅ PRESENTE" : "❌ FALTANTE") . "<br>";
} else {
    echo "❌ Error Meta API<br>";
    echo "Response: " . substr($metaResp, 0, 200) . "<br>";
    echo "cURL Error (" . $curlErrno . "): " . $curlError . "<br>";
}

echo "<hr>";

// ============================================================
// PRUEBA 3: Descargar desde URL con Authorization
// ============================================================
echo "<h2>PRUEBA 3: Descargar archivo desde URL de Meta</h2>";

if ($httpCode !== 200 || !isset($metaData['url'])) {
    echo "❌ No se pudo obtener URL de Meta. Saltando Prueba 3.<br>";
    echo "<hr>";
} else {
    $fileUrl = $metaData['url'];
    $mimeType = $metaData['mime_type'] ?? 'unknown';

    echo "URL: [OCULTADA POR SEGURIDAD]<br>";
    echo "METHOD: GET<br>";
    echo "HEADER: Authorization: Bearer [TOKEN_OCULTO]<br><br>";

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
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlError2 = curl_error($ch);
    $curlErrno2 = curl_errno($ch);
    curl_close($ch);

    echo "MEDIA DOWNLOAD STATUS: HTTP " . $httpCode2 . "<br>";
    echo "CONTENT TYPE: " . ($contentType ?: "no especificado") . "<br>";
    echo "FILE SIZE: " . strlen($fileContent) . " bytes<br>";
    echo "CURL ERROR (" . $curlErrno2 . "): " . ($curlError2 ?: "NINGUNO") . "<br>";

    echo "<hr>";

    // ============================================================
    // PRUEBA 4: Resumen de descarga
    // ============================================================
    echo "<h2>PRUEBA 4: Resumen de descarga</h2>";

    if ($httpCode2 === 200 && strlen($fileContent) > 0) {
        echo "✅ MEDIA DESCARGADA CORRECTAMENTE<br>";
        echo "  Tamaño: " . strlen($fileContent) . " bytes<br>";
        echo "  MIME Type: " . $mimeType . "<br>";
        echo "  HTTP Status: " . $httpCode2 . "<br>";
    } else {
        echo "❌ MEDIA DOWNLOAD FAILED<br>";
        echo "  HTTP Status: " . $httpCode2 . "<br>";
        echo "  Bytes recibidos: " . strlen($fileContent) . "<br>";
        echo "  cURL errno: " . $curlErrno2 . "<br>";
        echo "  cURL error: " . ($curlError2 ?: "ninguno") . "<br>";
        echo "  Content-Type: " . ($contentType ?: "no especificado") . "<br>";
    }

    echo "<hr>";
}

// ============================================================
// INFO: CURL y SSL
// ============================================================
echo "<h2>INFO: Sistema cURL/SSL</h2>";
echo "CURL VERSION: " . curl_version()['version'] . "<br>";
echo "SSL VERSION: " . (defined('CURL_VERSION_SSL_VERSION_NUMBER') ? 'OpenSSL/TLS detectado' : curl_version()['ssl_version']) . "<br>";
echo "Protocolos soportados: " . implode(", ", curl_version()['protocols']) . "<br>";

?>
