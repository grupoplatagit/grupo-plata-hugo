<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/functions.php';
requireLogin();

try {
    $db = getDB();
    $db->prepare("
        INSERT INTO leads (nombre, email, whatsapp, nicho, ciudad, pais, presupuesto, objetivo)
        VALUES (?,?,?,?,?,?,?,?)
    ")->execute([
        'Juan Test',
        'test@jpmarketpro.com',
        '+54 11 9999-0000',
        'E-commerce / Tienda online',
        'Buenos Aires',
        'Argentina',
        '$1,000 - $3,000 USD',
        'Conseguir más clientes'
    ]);
    echo '<p style="font-family:sans-serif;padding:20px;color:green">✅ Lead de prueba insertado correctamente. <a href="' . ADMIN_URL . '/pages/leads.php">Ver en el panel →</a></p>';
} catch (Exception $e) {
    echo '<p style="font-family:sans-serif;padding:20px;color:red">❌ Error: ' . $e->getMessage() . '</p>';
}
