<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/functions.php';

$db = getDB();

echo "<h2>WA Media Debug</h2>";

// Get la imagen descargada
$msg = $db->query("SELECT id, media_id, media_url, media_status, mime_type FROM wa_messages WHERE media_id IS NOT NULL AND media_url IS NOT NULL ORDER BY created_at DESC LIMIT 1")->fetch();

if (!$msg) {
    echo "❌ No hay mensajes con media_url<br>";
    exit;
}

echo "✅ Media encontrada en BD:<br>";
echo "  ID: " . $msg['id'] . "<br>";
echo "  Media ID: " . $msg['media_id'] . "<br>";
echo "  Media URL: " . $msg['media_url'] . "<br>";
echo "  Status: " . $msg['media_status'] . "<br>";
echo "  MIME Type: " . $msg['mime_type'] . "<br>";

// Verificar que el archivo existe
$filepath = __DIR__ . '/' . ltrim($msg['media_url'], '/');
echo "<br>Verificando archivo:<br>";
echo "  Ruta calculada: $filepath<br>";
echo "  Existe: " . (file_exists($filepath) ? "✅ SÍ" : "❌ NO") . "<br>";

if (file_exists($filepath)) {
    echo "  Tamaño: " . filesize($filepath) . " bytes<br>";
    echo "  Permisos: " . substr(sprintf('%o', fileperms($filepath)), -4) . "<br>";
}

echo "<br>Test de URL:<br>";
$media_id = $msg['media_id'];
$testUrl = BASE_URL . "/admin/api/wa-media.php?media_id=$media_id";
echo "  URL: <a href='$testUrl' target='_blank'>$testUrl</a><br>";
echo "  <img src='$testUrl' style='max-width:200px;border:1px solid #ccc;margin-top:10px' /><br>";
?>
