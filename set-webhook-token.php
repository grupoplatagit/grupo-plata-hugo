<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/functions.php';

$db = getDB();

// Crear tabla settings si no existe
$db->exec("
    CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )
");

// Guardar el webhook token
setSetting($db, 'wa_webhook_token', 'grupo_plata_webhook_2024');

echo "✅ Webhook token configurado correctamente<br>";
echo "Token: <strong>grupo_plata_webhook_2024</strong><br>";
echo "<a href='/admin/pages/whatsapp.php'>Ir a configuración de WhatsApp</a>";

function setSetting(PDO $db, string $key, string $value): void {
    $db->prepare("INSERT INTO settings (key,value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value")
       ->execute([$key, $value]);
}
?>
