<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

$db = getDB();

echo "<h2>COMPARAR AUDIO ENVIADO vs IMAGEN ENVIADA</h2>";
echo "<hr>";

// Última imagen enviada
echo "<h3>ÚLTIMA IMAGEN ENVIADA</h3>";
$image = $db->query("
    SELECT id, lead_id, from_phone, wa_msg_id, direction, message_type, body, media_id, mime_type, caption, wa_status, created_at
    FROM wa_messages
    WHERE direction = 'out' AND message_type = 'image'
    ORDER BY created_at DESC
    LIMIT 1
")->fetch();

if ($image) {
    echo "<table border='1' cellpadding='10'>";
    foreach ($image as $key => $val) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($val) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ No hay imágenes enviadas en la DB";
}

echo "<hr>";

// Último audio enviado
echo "<h3>ÚLTIMO AUDIO ENVIADO</h3>";
$audio = $db->query("
    SELECT id, lead_id, from_phone, wa_msg_id, direction, message_type, body, media_id, mime_type, caption, wa_status, created_at
    FROM wa_messages
    WHERE direction = 'out' AND message_type = 'audio'
    ORDER BY created_at DESC
    LIMIT 1
")->fetch();

if ($audio) {
    echo "<table border='1' cellpadding='10'>";
    foreach ($audio as $key => $val) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($val) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ No hay audios enviados en la DB";
}

echo "<hr>";

// Comparación
if ($image && $audio) {
    echo "<h3>DIFERENCIAS</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>CAMPO</th><th>IMAGEN</th><th>AUDIO</th><th>IGUAL?</th></tr>";

    foreach ($image as $key => $imageVal) {
        $audioVal = $audio[$key] ?? '(no existe)';
        $igual = ($imageVal === $audioVal) ? '✓' : '✗';
        echo "<tr><td><strong>$key</strong></td>";
        echo "<td>" . htmlspecialchars($imageVal) . "</td>";
        echo "<td>" . htmlspecialchars($audioVal) . "</td>";
        echo "<td>$igual</td></tr>";
    }
    echo "</table>";
}

?>
