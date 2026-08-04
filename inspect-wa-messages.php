<?php
require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "=== INSPECCIONAR ESTRUCTURA wa_messages ===\n\n";

// Ver estructura de la tabla
$columns = $db->query("PRAGMA table_info(wa_messages)")->fetchAll(PDO::FETCH_ASSOC);

if (empty($columns)) {
    echo "❌ Tabla wa_messages NO existe\n";
} else {
    echo "✅ Tabla wa_messages EXISTE\n";
    echo "\nColumnas actuales:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-15s %-15s %-15s %-15s %-15s\n", "Name", "Type", "NotNull", "DefaultVal", "PrimaryKey");
    echo str_repeat("-", 80) . "\n";

    foreach ($columns as $col) {
        printf("%-15s %-15s %-15s %-15s %-15s\n",
            $col['name'],
            $col['type'],
            $col['notnull'] ? 'YES' : 'NO',
            $col['dflt_value'] ?? 'NULL',
            $col['pk'] ? 'YES' : 'NO'
        );
    }
    echo str_repeat("-", 80) . "\n";

    // Mostrar un ejemplo de fila si existe
    $example = $db->query("SELECT * FROM wa_messages LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($example) {
        echo "\nEjemplo de fila:\n";
        foreach ($example as $k => $v) {
            echo "  $k: " . substr($v, 0, 50) . "\n";
        }
    }
}

echo "\n";
echo "=== BÚSQUEDA DE REFERENCIAS A msg_id EN CÓDIGO ===\n\n";

// Buscar referencias a msg_id en los archivos PHP
$files = [
    'public/api-whatsapp.php',
    'admin/api/wa-messages.php',
    'admin/pages/inbox.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $matches = [];

        foreach ($lines as $num => $line) {
            if (stripos($line, 'msg_id') !== false || stripos($line, 'wamid') !== false) {
                $matches[] = ($num + 1) . ": " . trim($line);
            }
        }

        if (!empty($matches)) {
            echo "📄 $file:\n";
            foreach ($matches as $match) {
                echo "   $match\n";
            }
            echo "\n";
        }
    }
}
?>
