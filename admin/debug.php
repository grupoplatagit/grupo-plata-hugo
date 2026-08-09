<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Area Dev</h2>";

try {
    echo "1. Cargar config... ";
    require_once __DIR__ . '/../app/config.php';
    echo "OK<br>";

    echo "2. Cargar auth... ";
    require_once __DIR__ . '/../app/auth.php';
    echo "OK<br>";

    echo "3. Cargar db... ";
    require_once __DIR__ . '/../app/db.php';
    echo "OK<br>";

    echo "4. Verificar login... ";
    requireLogin();
    echo "OK<br>";

    echo "5. Conectar a BD... ";
    $db = getDB();
    echo "OK<br>";

    echo "6. Crear tabla... ";
    $db->exec("CREATE TABLE IF NOT EXISTS user_whatsapp_config (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_email     TEXT NOT NULL UNIQUE,
        waba_id         TEXT,
        phone_number_id TEXT,
        created_at      TEXT DEFAULT (datetime('now','localtime')),
        updated_at      TEXT DEFAULT (datetime('now','localtime'))
    )");
    echo "OK<br>";

    echo "7. Obtener admins... ";
    $admins = $db->query("SELECT email, nombre FROM admins WHERE activo = 1")->fetchAll();
    echo "OK (" . count($admins) . " admins)<br>";

    echo "<br><pre>";
    print_r($admins);
    echo "</pre>";

} catch (Exception $e) {
    echo "<br><b style='color:red'>ERROR:</b> " . $e->getMessage();
    echo "<br><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
