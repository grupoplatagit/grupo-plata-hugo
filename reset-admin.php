<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

$db = getDB();

// Crear tabla admins si no existe
$db->exec("
    CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        activo INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Hash de la contraseña admin123
$passwordHash = password_hash('admin123', PASSWORD_BCRYPT);

// Eliminar admin anterior si existe
$db->prepare("DELETE FROM admins WHERE email = ?")->execute(['admin@jpmarket.com']);

// Crear nuevo admin
$db->prepare("INSERT INTO admins (nombre, email, password, activo) VALUES (?, ?, ?, 1)")
   ->execute(['Administrador', 'admin@jpmarket.com', $passwordHash]);

echo "✅ Admin creado/reseteado correctamente<br>";
echo "Email: <strong>admin@jpmarket.com</strong><br>";
echo "Contraseña: <strong>admin123</strong><br>";
echo "<a href='/admin/login.php'>Ir al login</a>";
?>
