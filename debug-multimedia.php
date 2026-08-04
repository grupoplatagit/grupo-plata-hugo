<?php
require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "=== DIAGNÓSTICO: MULTIMEDIA EN wa_messages ===\n\n";

// Últimos 10 mensajes multimedia (no texto)
$msgs = $db->query("
    SELECT
        id, from_phone, wa_msg_id, message_type, body,
        media_id, mime_type, file_name, caption, wa_status, created_at
    FROM wa_messages
    WHERE message_type != 'text'
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

if (empty($msgs)) {
    echo "❌ No hay mensajes multimedia en la BD\n";
    exit;
}

echo "✅ Encontrados " . count($msgs) . " mensajes multimedia\n\n";

foreach ($msgs as $i => $m) {
    echo str_repeat("─", 100) . "\n";
    printf("[%d] %s\n", $i + 1, $m['created_at']);
    printf("    ID: %d | WAMID: %s\n", $m['id'], substr($m['wa_msg_id'], 0, 40));
    printf("    Tipo: %s | Desde: %s\n", $m['message_type'], $m['from_phone']);
    printf("    Body: %s\n", $m['body']);
    printf("    media_id: %s\n", $m['media_id'] ?: '❌ VACÍO');
    printf("    mime_type: %s\n", $m['mime_type'] ?: '❌ VACÍO');
    printf("    file_name: %s\n", $m['file_name'] ?: 'N/A');
    printf("    caption: %s\n", $m['caption'] ?: 'N/A');
    printf("    Status: %s\n", $m['wa_status']);

    // Validación
    echo "\n    ✓ Validación:\n";
    if (!$m['media_id']) {
        echo "    ❌ media_id VACÍO — problema en webhook parsing\n";
    } else {
        echo "    ✅ media_id presente\n";
    }

    if (!$m['mime_type']) {
        echo "    ❌ mime_type VACÍO — problema en webhook parsing\n";
    } else {
        echo "    ✅ mime_type presente: " . $m['mime_type'] . "\n";
    }
    echo "\n";
}

echo "\n=== VERIFICACIÓN DE BD ===\n";

// Verificar estructura
$cols = $db->query("PRAGMA table_info(wa_messages)")->fetchAll();
echo "\nColumnas en wa_messages:\n";
foreach ($cols as $col) {
    $marker = in_array($col['name'], ['message_type', 'media_id', 'mime_type', 'file_name', 'caption'])
        ? '✅'
        : '  ';
    printf("%s %s (%s)\n", $marker, $col['name'], $col['type']);
}

echo "\n=== CONCLUSIÓN ===\n";

$hasMedia = !empty($msgs);
$hasMediaId = $hasMedia && !empty($msgs[0]['media_id']);
$hasMimeType = $hasMedia && !empty($msgs[0]['mime_type']);

if (!$hasMedia) {
    echo "❌ No hay registros multimedia. Problema: ¿webhook llegando?\n";
} elseif (!$hasMediaId) {
    echo "❌ media_id está VACÍO. Problema: Webhook no parsea correctamente.\n";
    echo "   Verificar: /public/api-whatsapp.php línea ~30-40\n";
} elseif (!$hasMimeType) {
    echo "⚠️  mime_type está VACÍO. Problema: Webhook no extrae mime_type.\n";
} else {
    echo "✅ BD está OK con media_id y mime_type.\n";
    echo "   Problema probablemente en endpoint /admin/api/wa-media.php\n";
}
?>
