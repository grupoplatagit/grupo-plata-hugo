<?php
require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "=== VERIFICAR FORMATO DE WAMID EN BD ===\n\n";

// Obtener últimos mensajes enviados
$msgs = $db->query("
    SELECT id, wa_msg_id, direction, body, wa_status, created_at
    FROM wa_messages
    WHERE direction = 'out'
    AND wa_msg_id IS NOT NULL
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

echo "Últimos 5 mensajes enviados:\n";
echo str_repeat("-", 100) . "\n";

foreach ($msgs as $m) {
    printf("ID: %3d | Status: %-10s | WAMID: %s\n",
        $m['id'],
        $m['wa_status'],
        substr($m['wa_msg_id'], 0, 60) . (strlen($m['wa_msg_id']) > 60 ? '...' : '')
    );
}

echo str_repeat("-", 100) . "\n";

// Ver un ejemplo completo
if (!empty($msgs)) {
    echo "\nDetalle del último mensaje:\n";
    $msg = $msgs[0];
    echo "  wa_msg_id: " . $msg['wa_msg_id'] . "\n";
    echo "  Largo: " . strlen($msg['wa_msg_id']) . " caracteres\n";
    echo "  Comienza con 'wamid'?: " . (strpos($msg['wa_msg_id'], 'wamid') === 0 ? 'SÍ' : 'NO') . "\n";
}
?>
