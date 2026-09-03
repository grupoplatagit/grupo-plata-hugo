<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

$db = getDB();
$newPassword = 'Grupoplata26';
$hashed = password_hash($newPassword, PASSWORD_BCRYPT);

try {
    // Update all admins
    $stmt = $db->prepare("UPDATE admins SET password = ? WHERE activo = 1");
    $stmt->execute([$hashed]);

    // List all admins
    $stmt = $db->prepare("SELECT nombre, email FROM admins WHERE activo = 1");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "✅ Contraseña actualizada para todos:\n\n";
    foreach ($admins as $admin) {
        echo "  - {$admin['nombre']} ({$admin['email']})\n";
    }
    echo "\n🔑 Nueva contraseña: $newPassword\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
