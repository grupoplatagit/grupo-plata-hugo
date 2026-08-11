<?php
require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "<h2>Verificar Media IDs</h2>";

// Últimos 10 mensajes con imágenes
$msgs = $db->query("
    SELECT id, created_at, message_type, body, media_id, mime_type
    FROM wa_messages
    WHERE message_type IN ('image', 'audio', 'sticker', 'video', 'document')
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

echo "<p>Últimos mensajes con multimedia:</p>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Tipo</th><th>Body</th><th>Media ID</th><th>Mime Type</th><th>Fecha</th></tr>";

foreach ($msgs as $m) {
    $media_status = $m['media_id'] ? '✅ ' . substr($m['media_id'], 0, 30) . '...' : '❌ VACÍO';
    echo "<tr>";
    echo "<td>" . $m['id'] . "</td>";
    echo "<td>" . $m['message_type'] . "</td>";
    echo "<td>" . $m['body'] . "</td>";
    echo "<td style='color:" . ($m['media_id'] ? 'green' : 'red') . "'>" . $media_status . "</td>";
    echo "<td>" . $m['mime_type'] . "</td>";
    echo "<td>" . $m['created_at'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<p><b>Resumen:</b></p>";
$total = count($msgs);
$with_media = count(array_filter($msgs, fn($m) => !empty($m['media_id'])));
echo "Total: $total mensajes | Con media_id: $with_media | Sin media_id: " . ($total - $with_media);

if ($with_media == 0) {
    echo "<br><br><b style='color:red'>❌ PROBLEMA: Los media_id NO se están guardando</b>";
    echo "<br>Revisar: /public/api-whatsapp.php línea donde extrae media_id";
} else {
    echo "<br><br><b style='color:green'>✅ Media IDs se guardan OK</b>";
    echo "<br>Problema está en el endpoint /admin/api/wa-media.php";
}
?>
