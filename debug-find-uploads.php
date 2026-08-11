<?php
echo "<h2>Buscando directorio /uploads/whatsapp en todas las rutas posibles</h2>";

$paths = [
    'Raíz proyecto' => __DIR__ . '/uploads/whatsapp',
    'public_html' => __DIR__ . '/public_html/uploads/whatsapp',
    'Padre del proyecto' => dirname(__DIR__) . '/uploads/whatsapp',
];

foreach ($paths as $name => $path) {
    echo "<h3>$name</h3>";
    echo "Ruta: $path<br>";
    echo "Existe: " . (is_dir($path) ? "✅ SÍ" : "❌ NO") . "<br>";

    if (is_dir($path)) {
        $files = scandir($path);
        $count = count($files) - 2;
        echo "Archivos: $count<br>";
        if ($count > 0) {
            echo "Primeros: " . implode(", ", array_slice($files, 2, 5)) . "<br>";
        }
    }
    echo "<br>";
}

// También buscar por patrones
echo "<h3>Búsqueda recursiva por nombre</h3>";
echo "Buscando directorios llamados 'whatsapp':<br>";

function findDir($pattern, $startPath = '.') {
    $results = [];
    try {
        $dirs = new RecursiveDirectoryIterator($startPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($dirs, function($current) use ($pattern) {
            return $current->isDir() && strpos($current->getFilename(), $pattern) !== false;
        });
        $iter = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iter as $path => $dir) {
            $results[] = $path;
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
    return $results;
}

$found = findDir('whatsapp', __DIR__);
if ($found) {
    foreach ($found as $path) {
        echo "✅ Encontrado: $path<br>";
        $count = count(scandir($path)) - 2;
        echo "   Archivos: $count<br>";
    }
} else {
    echo "❌ No se encontraron directorios<br>";
}
?>
