<?php
require_once __DIR__ . '/app/db.php';

$db = getDB();

try {
    $db->exec("ALTER TABLE leads ADD COLUMN tipo_credito TEXT");
    echo "✅ Columna tipo_credito agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna tipo_credito ya existe\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

try {
    $db->exec("ALTER TABLE leads ADD COLUMN monto_solicitado TEXT");
    echo "✅ Columna monto_solicitado agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna monto_solicitado ya existe\n";
    }
}

try {
    $db->exec("ALTER TABLE leads ADD COLUMN destino_credito TEXT");
    echo "✅ Columna destino_credito agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna destino_credito ya existe\n";
    }
}

try {
    $db->exec("ALTER TABLE leads ADD COLUMN ingresos_mensuales TEXT");
    echo "✅ Columna ingresos_mensuales agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna ingresos_mensuales ya existe\n";
    }
}

try {
    $db->exec("ALTER TABLE leads ADD COLUMN situacion_laboral TEXT");
    echo "✅ Columna situacion_laboral agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna situacion_laboral ya existe\n";
    }
}

try {
    $db->exec("ALTER TABLE leads ADD COLUMN antiguedad_laboral TEXT");
    echo "✅ Columna antiguedad_laboral agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna antiguedad_laboral ya existe\n";
    }
}

try {
    $db->exec("ALTER TABLE leads ADD COLUMN cuotas_deseadas TEXT");
    echo "✅ Columna cuotas_deseadas agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna cuotas_deseadas ya existe\n";
    }
}

try {
    $db->exec("ALTER TABLE leads ADD COLUMN asesor_asignado TEXT");
    echo "✅ Columna asesor_asignado agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna asesor_asignado ya existe\n";
    }
}

try {
    $db->exec("ALTER TABLE leads ADD COLUMN prioridad TEXT");
    echo "✅ Columna prioridad agregada a leads\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✅ Columna prioridad ya existe\n";
    }
}

echo "\n✅ Schema actualizado correctamente\n";
?>
