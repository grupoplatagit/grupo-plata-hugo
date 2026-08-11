<?php
echo "1. Test básico iniciado<br>";

try {
    echo "2. Cargando config...<br>";
    require_once __DIR__ . '/app/config.php';
    echo "3. Config cargada OK<br>";

    echo "4. BASE_URL: " . BASE_URL . "<br>";
    echo "5. MEDIA_UPLOAD_DIR: " . MEDIA_UPLOAD_DIR . "<br>";

    echo "6. Cargando db...<br>";
    require_once __DIR__ . '/app/db.php';
    echo "7. DB cargada OK<br>";

    echo "8. Obteniendo conexión...<br>";
    $db = getDB();
    echo "9. Conexión obtenida OK<br>";

    echo "10. ✅ TODO FUNCIONA!<br>";
} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
