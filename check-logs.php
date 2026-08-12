<?php
echo "<h2>Últimos errores de audio</h2>";
echo "<hr>";

$logFile = __DIR__ . '/logs/wa-send-media.log';

if (!file_exists($logFile)) {
    echo "No hay logs aún";
    exit;
}

$lines = file($logFile);
$lines = array_slice($lines, -30); // Últimas 30 líneas

foreach ($lines as $line) {
    echo "<pre>" . htmlspecialchars($line) . "</pre>";
}
?>
