<?php
echo "<h2>Media System Debug</h2>";

// 1. Verificar funciones
echo "<h3>1. Funciones cargadas</h3>";
require_once __DIR__ . '/app/functions.php';
echo (function_exists('downloadWAMedia') ? "✅ downloadWAMedia existe\n" : "❌ downloadWAMedia NO existe\n");
echo (function_exists('extensionFromMime') ? "✅ extensionFromMime existe\n" : "❌ extensionFromMime NO existe\n");

// 2. Verificar BD
echo "<h3>2. Tabla wa_messages columnas</h3>";
require_once __DIR__ . '/app/db.php';
$db = getDB();
$cols = $db->query("PRAGMA table_info(wa_messages)")->fetchAll();
$col_names = array_column($cols, 'name');
echo "media_url: " . (in_array('media_url', $col_names) ? "✅" : "❌") . "\n";
echo "media_status: " . (in_array('media_status', $col_names) ? "✅" : "❌") . "\n";

// 3. Verificar directorio de uploads
echo "<h3>3. Directorio /uploads/whatsapp</h3>";
$uploadDir = __DIR__ . '/uploads/whatsapp';
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    echo "✅ Directorio existe\n";
    echo "Archivos: " . (count($files) - 2) . "\n";
    if (count($files) > 2) {
        echo "Primeros: " . implode(", ", array_slice($files, 2, 3)) . "\n";
    }
} else {
    echo "❌ Directorio NO existe\n";
}

// 4. Verificar último mensaje con media
echo "<h3>4. Último mensaje con media_id</h3>";
$msg = $db->query("SELECT id, message_type, media_id, media_url, media_status FROM wa_messages WHERE media_id IS NOT NULL ORDER BY created_at DESC LIMIT 1")->fetch();
if ($msg) {
    echo "ID: " . $msg['id'] . "\n";
    echo "Tipo: " . $msg['message_type'] . "\n";
    echo "Media ID: " . substr($msg['media_id'], 0, 30) . "...\n";
    echo "Media URL: " . ($msg['media_url'] ?: "NULL") . "\n";
    echo "Status: " . $msg['media_status'] . "\n";
} else {
    echo "❌ No hay mensajes con media\n";
}

// 5. Ver logs
echo "<h3>5. Logs recientes</h3>";
$logFile = __DIR__ . '/logs/whatsapp-media.log';
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -5);
    foreach ($lines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "❌ No hay logs de media\n";
}
?>
