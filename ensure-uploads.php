<?php
require_once __DIR__ . '/app/config.php';

echo "<h2>Asegurar que directorio de uploads existe</h2>";
echo "MEDIA_UPLOAD_DIR = " . MEDIA_UPLOAD_DIR . "<br>";

if (!is_dir(MEDIA_UPLOAD_DIR)) {
    echo "Creando directorio...<br>";
    if (@mkdir(MEDIA_UPLOAD_DIR, 0755, true)) {
        echo "✅ Directorio creado correctamente<br>";
    } else {
        echo "❌ Error creando directorio<br>";
    }
} else {
    echo "✅ Directorio ya existe<br>";
}

echo "<br>Estado actual:<br>";
echo "Existe: " . (is_dir(MEDIA_UPLOAD_DIR) ? "✅ SÍ" : "❌ NO") . "<br>";
echo "Es escribible: " . (is_writable(MEDIA_UPLOAD_DIR) ? "✅ SÍ" : "❌ NO") . "<br>";

if (is_dir(MEDIA_UPLOAD_DIR)) {
    $files = scandir(MEDIA_UPLOAD_DIR);
    $count = count($files) - 2;
    echo "Archivos: $count<br>";
    if ($count > 0) {
        echo "Primeros: " . implode(", ", array_slice($files, 2, 5)) . "<br>";
    }
}
?>
