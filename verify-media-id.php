<?php
/**
 * Verificación rápida: ¿Se está guardando media_id?
 */

require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "=== VERIFICAR ÚLTIMOS MENSAJES ===\n\n";

$msgs = $db->query("
    SELECT
        id, created_at, message_type, body, media_id, mime_type
    FROM wa_messages
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

echo "Últimos 10 mensajes en wa_messages:\n\n";

foreach ($msgs as $m) {
    $media_status = $m['media_id'] ? '✅ ' . substr($m['media_id'], 0, 30) : '❌ VACÍO';
    printf("[%s] %s | media_id: %s\n",
        $m['created_at'],
        str_pad($m['message_type'], 10),
        $media_status
    );
}

echo "\n=== CONCLUSIÓN ===\n";

$msg = $msgs[0];
if ($msg['message_type'] !== 'text' && !$msg['media_id']) {
    echo "❌ PROBLEMA: media_id está VACÍO para mensajes multimedia\n";
    echo "   → El webhook NO está extrayendo media_id correctamente\n";
    echo "   → Revisar: /public/api-whatsapp.php línea ~30-40\n";
} elseif ($msg['message_type'] !== 'text' && $msg['media_id']) {
    echo "✅ media_id se está guardando correctamente\n";
    echo "   → El problema está en el endpoint de media\n";
} else {
    echo "✅ Últimos mensajes son texto\n";
}
?>
