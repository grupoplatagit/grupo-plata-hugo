<?php
require_once __DIR__ . '/../app/db.php';

$db = getDB();

echo "=== PREPARANDO BD PARA GRUPO PLATA ===\n\n";

// 1. Limpiar tablas antiguas
$tables = ['leads', 'prospectos', 'clientes', 'propuestas'];

foreach ($tables as $table) {
    try {
        $db->exec("DELETE FROM $table");
        echo "✅ Tabla '$table' limpiada\n";
    } catch (Exception $e) {
        echo "⚠️ Tabla '$table' no existe o error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 2. Crear tabla de mensajes WhatsApp si no existe
$db->exec("
    CREATE TABLE IF NOT EXISTS whatsapp_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_number TEXT NOT NULL,
        message_text TEXT,
        message_type TEXT DEFAULT 'text',
        message_id TEXT UNIQUE,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'received'
    )
");
echo "✅ Tabla 'whatsapp_messages' creada/verificada\n";

// 3. Limpiar mensajes antiguos
$db->exec("DELETE FROM whatsapp_messages");
echo "✅ Tabla 'whatsapp_messages' limpiada\n";

echo "\n✅ Base de datos lista para Grupo Plata\n";
echo "Los mensajes de WhatsApp se guardarán en 'whatsapp_messages'\n";
?>
