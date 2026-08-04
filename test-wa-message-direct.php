<?php
// Insertar mensaje directamente en la BD para probar el Inbox

require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "=== INSERTAR MENSAJE DE PRUEBA EN BD ===\n\n";

// Crear tabla si no existe
$db->exec("
    CREATE TABLE IF NOT EXISTS wa_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER,
        from_phone TEXT NOT NULL,
        body TEXT,
        msg_id TEXT UNIQUE,
        direction TEXT DEFAULT 'in',
        leido INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

echo "✅ Tabla wa_messages verificada\n\n";

// Insertar mensaje de prueba
try {
    $stmt = $db->prepare("
        INSERT INTO wa_messages
        (from_phone, body, msg_id, direction, leido, created_at)
        VALUES (?, ?, ?, ?, ?, datetime('now', 'localtime'))
    ");

    $stmt->execute([
        '+5491234567890',
        '¡Hola! Este es un mensaje de prueba desde Grupo Plata 🎉',
        'msg_test_' . time(),
        'in',
        0
    ]);

    echo "✅ Mensaje insertado correctamente\n";
    echo "   Desde: +5491234567890\n";
    echo "   Mensaje: ¡Hola! Este es un mensaje de prueba desde Grupo Plata 🎉\n\n";

    // Verificar que se insertó
    $result = $db->query("SELECT * FROM wa_messages ORDER BY created_at DESC LIMIT 1")->fetch();

    if ($result) {
        echo "✅ VERIFICACIÓN - Mensaje en BD:\n";
        echo "   ID: {$result['id']}\n";
        echo "   From: {$result['from_phone']}\n";
        echo "   Body: {$result['body']}\n";
        echo "   Direction: {$result['direction']}\n";
        echo "   Created: {$result['created_at']}\n\n";

        echo "🎉 ¡LISTO! Recargá el Inbox WhatsApp para ver el mensaje:\n";
        echo "https://grupoplatasf.com/admin/pages/inbox.php\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
