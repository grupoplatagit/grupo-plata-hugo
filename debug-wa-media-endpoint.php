<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/app/config.php';
    require_once __DIR__ . '/app/db.php';
    require_once __DIR__ . '/app/functions.php';

    $db = getDB();
    $media_id = $_GET['media_id'] ?? '';

    if (!$media_id) {
        echo "❌ No se proporcionó media_id. Usa: ?media_id=xxx";
        exit;
    }

    echo "<h2>Debug wa-media.php para media_id: $media_id</h2>";

    // Buscar en BD
    $msg = $db->prepare("SELECT * FROM wa_messages WHERE media_id = ? LIMIT 1")
        ->execute([$media_id])
        ->fetch();
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

if (!$msg) {
    echo "❌ Media NO encontrada en BD<br>";
    exit;
}

echo "✅ Media encontrada en BD<br>";
echo "  media_id: " . $msg['media_id'] . "<br>";
echo "  media_url: " . ($msg['media_url'] ?: "NULL") . "<br>";
echo "  media_status: " . $msg['media_status'] . "<br>";
echo "  mime_type: " . $msg['mime_type'] . "<br>";

echo "<br><h3>Verificar archivo</h3>";
if ($msg['media_url']) {
    $filepath = __DIR__ . '/' . ltrim($msg['media_url'], '/');
    echo "Ruta calculada: $filepath<br>";
    echo "Existe: " . (file_exists($filepath) ? "✅ SÍ" : "❌ NO") . "<br>";

    if (file_exists($filepath)) {
        echo "Tamaño: " . filesize($filepath) . " bytes<br>";
        echo "MIME: " . $msg['mime_type'] . "<br>";
        echo "<a href='/admin/api/wa-media.php?media_id=$media_id' target='_blank'>👉 Abrir wa-media.php</a>";
    }
} else {
    echo "❌ No hay media_url en BD<br>";
}
?>
