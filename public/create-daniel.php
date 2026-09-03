<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

$db = getDB();

try {
    // Check if Daniel exists
    $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute(['daniel@grupoplata.com']);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "⚠️ Daniel ya existe en el sistema\n";
        exit(0);
    }

    // Create Daniel
    $nombre = 'Daniel';
    $email = 'daniel@grupoplata.com';
    $password = 'Plata2026';
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare("INSERT INTO admins (nombre, email, password, activo) VALUES (?, ?, ?, 1)");
    $stmt->execute([$nombre, $email, $hashed]);

    echo "✅ Usuario creado:\n";
    echo "  Nombre: $nombre\n";
    echo "  Email: $email\n";
    echo "  Contraseña: $password\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
