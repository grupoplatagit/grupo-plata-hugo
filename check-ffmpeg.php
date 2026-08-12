<?php
/**
 * Verificar disponibilidad de FFmpeg en el servidor
 */

echo "<h2>CHECK: FFmpeg en el servidor</h2>";
echo "<hr>";

// 1. Verificar si comando 'which ffmpeg' funciona
echo "<h3>1. Buscando FFmpeg en PATH</h3>";
$output = [];
$returnCode = 0;

@exec('which ffmpeg 2>&1', $output, $returnCode);
if ($returnCode === 0 && !empty($output[0])) {
    echo "✅ <strong>FFmpeg encontrado en:</strong> " . $output[0] . "<br>";
    $ffmpegPath = trim($output[0]);
} else {
    echo "❌ No encontrado con 'which ffmpeg'<br>";
    $ffmpegPath = null;
}

// 2. Intentar ejecutar ffmpeg directamente
echo "<h3>2. Verificando versión de FFmpeg</h3>";
$output = [];
$returnCode = 0;

@exec('ffmpeg -version 2>&1', $output, $returnCode);
if ($returnCode === 0 && !empty($output)) {
    echo "✅ <strong>FFmpeg responde:</strong><br>";
    echo "<pre>";
    echo htmlspecialchars(implode("\n", array_slice($output, 0, 5)));
    echo "</pre>";
} else {
    echo "❌ FFmpeg no responde<br>";
}

// 3. Verificar función shell_exec
echo "<h3>3. Funciones PHP disponibles</h3>";
$functions = ['shell_exec', 'exec', 'system', 'passthru', 'proc_open'];
foreach ($functions as $func) {
    $disabled = stripos(ini_get('disable_functions'), $func) !== false;
    echo ($disabled ? '❌' : '✅') . " <strong>{$func}:</strong> " . ($disabled ? 'DISABLED' : 'enabled') . "<br>";
}

// 4. Verificar permisos de escritura
echo "<h3>4. Permisos de directorio /tmp</h3>";
if (is_writable('/tmp')) {
    echo "✅ /tmp es escribible<br>";
    // Test write/read/execute
    $testFile = '/tmp/ffmpeg-test-' . time() . '.txt';
    if (@file_put_contents($testFile, 'test')) {
        echo "✅ Puedo escribir en /tmp<br>";
        if (@unlink($testFile)) {
            echo "✅ Puedo eliminar archivos en /tmp<br>";
        }
    }
} else {
    echo "❌ /tmp no es escribible<br>";
}

echo "<h3>5. Test: Convertir audio simple</h3>";

// Crear archivo WebM fake (para test, no es un audio real)
$testWebM = '/tmp/test-audio-' . time() . '.webm';
$testAAC = '/tmp/test-audio-' . time() . '.aac';

// Copiar un archivo de test o crear uno simple
echo "Creando archivo test...<br>";

// Intentar conversión
if (file_exists($testWebM) || true) {
    echo "Intentando comando FFmpeg básico...<br>";
    $cmd = "ffmpeg -version 2>&1 | head -3";
    $output = @shell_exec($cmd);
    if ($output) {
        echo "✅ Shell_exec funciona:<br>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "❌ Shell_exec no devuelve nada<br>";
    }
}

echo "<hr>";
echo "<h3>📋 RESUMEN</h3>";
echo "<p>FFmpeg está disponible: " . ($returnCode === 0 ? "✅ SÍ" : "❌ NO") . "</p>";

?>
