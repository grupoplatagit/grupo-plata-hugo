<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "<h2>DEBUG: AUDIO Y VIDEO EN LA BD</h2>";
echo "<hr>";

// Mensajes tipo AUDIO
echo "<h3>Mensajes tipo AUDIO</h3>";
$audios = $db->query("
    SELECT id, message_type, media_id, mime_type, body, created_at
    FROM wa_messages
    WHERE message_type = 'audio'
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

if ($audios) {
    echo "Encontrados: " . count($audios) . "<br><br>";
    foreach ($audios as $a) {
        echo "ID: " . $a['id'] . "<br>";
        echo "Type: " . $a['message_type'] . "<br>";
        echo "Media ID: " . $a['media_id'] . "<br>";
        echo "MIME: " . $a['mime_type'] . "<br>";
        echo "Body: " . $a['body'] . "<br>";
        echo "Created: " . $a['created_at'] . "<br>";
        echo "---<br>";
    }
} else {
    echo "❌ NO HAY MENSAJES TIPO AUDIO<br>";
}

echo "<br><hr><br>";

// Mensajes tipo VIDEO
echo "<h3>Mensajes tipo VIDEO</h3>";
$videos = $db->query("
    SELECT id, message_type, media_id, mime_type, body, created_at
    FROM wa_messages
    WHERE message_type = 'video'
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

if ($videos) {
    echo "Encontrados: " . count($videos) . "<br><br>";
    foreach ($videos as $v) {
        echo "ID: " . $v['id'] . "<br>";
        echo "Type: " . $v['message_type'] . "<br>";
        echo "Media ID: " . $v['media_id'] . "<br>";
        echo "MIME: " . $v['mime_type'] . "<br>";
        echo "Body: " . $v['body'] . "<br>";
        echo "Created: " . $v['created_at'] . "<br>";
        echo "<strong>Test endpoint:</strong><br>";
        echo "  Audio: <a href='/admin/api/wa-media.php?media_id=" . $v['media_id'] . "' target='_blank'>/admin/api/wa-media.php?media_id=" . substr($v['media_id'], 0, 20) . "...</a><br>";
        echo "---<br>";
    }
} else {
    echo "❌ NO HAY MENSAJES TIPO VIDEO<br>";
}

echo "<br><hr><br>";

// Resumen general de tipos
echo "<h3>Resumen de TODOS los tipos de mensaje</h3>";
$types = $db->query("
    SELECT message_type, COUNT(*) as count
    FROM wa_messages
    GROUP BY message_type
    ORDER BY count DESC
")->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Type</th><th>Count</th></tr>";
foreach ($types as $t) {
    echo "<tr><td>" . $t['message_type'] . "</td><td>" . $t['count'] . "</td></tr>";
}
echo "</table>";

?>
