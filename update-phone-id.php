<?php
require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "=== ACTUALIZAR PHONE NUMBER ID ===\n\n";

// Leer valor actual
$stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
$stmt->execute(['wa_phone_id']);
$current = $stmt->fetchColumn();

echo "Phone ID ANTERIOR: " . ($current ?: 'no configurado') . "\n";

// Actualizar a nuevo ID
$new_id = '1252695447922656';

$stmt = $db->prepare("
    INSERT OR REPLACE INTO settings (key, value)
    VALUES (?, ?)
");

$stmt->execute(['wa_phone_id', $new_id]);

echo "Phone ID NUEVO: $new_id\n\n";

// Verificar que se actualizó
$stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
$stmt->execute(['wa_phone_id']);
$verify = $stmt->fetchColumn();

if ($verify === $new_id) {
    echo "✅ Configuración actualizada correctamente\n";
} else {
    echo "❌ Error al actualizar\n";
}
?>
