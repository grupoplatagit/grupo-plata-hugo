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

// Usuarios a crear
$usuarios = [
    ['nombre' => 'Hugo', 'email' => 'hugo@grupoplata.com', 'password' => 'plata26'],
    ['nombre' => 'Roxana', 'email' => 'roxana@grupoplata.com', 'password' => 'plata26'],
    ['nombre' => 'Daniel', 'email' => 'daniel@grupoplata.com', 'password' => 'plata26'],
];

$passwordHash = password_hash('plata26', PASSWORD_BCRYPT);

foreach ($usuarios as $usuario) {
    try {
        $db->prepare("INSERT INTO admins (nombre, email, password, activo) VALUES (?, ?, ?, 1)")
           ->execute([$usuario['nombre'], $usuario['email'], $passwordHash]);
        echo "✅ Usuario creado: {$usuario['email']}\n";
    } catch (Exception $e) {
        echo "⚠️ {$usuario['email']}: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Usuarios listos para usar:\n";
echo "Hugo:   hugo@grupoplata.com / plata26\n";
echo "Roxana: roxana@grupoplata.com / plata26\n";
echo "Daniel: daniel@grupoplata.com / plata26\n";
?>
