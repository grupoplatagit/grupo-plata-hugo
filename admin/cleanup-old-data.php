<?php
require_once __DIR__ . '/../app/db.php';

$db = getDB();

echo "=== LIMPIANDO DATOS ANTIGUOS ===\n\n";

// Tablas a limpiar
$tables = ['leads', 'prospectos', 'clientes', 'propuestas'];

foreach ($tables as $table) {
    try {
        $result = $db->exec("DELETE FROM $table");
        echo "✅ Tabla '$table' limpiada\n";
    } catch (Exception $e) {
        echo "⚠️ Error en '$table': " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Base de datos lista para nuevos datos de Grupo Plata\n";
?>
