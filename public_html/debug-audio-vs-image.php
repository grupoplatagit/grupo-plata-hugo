<?php
// No requiere auth
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

$db = getDB();

echo "<h2>COMPARAR AUDIO vs IMAGEN EN DB</h2>";
echo "<hr>";

// Última imagen enviada
echo "<h3>ÚLTIMA IMAGEN ENVIADA</h3>";
$image = $db->query("
    SELECT id, lead_id, from_phone, wa_msg_id, direction, message_type, body, media_id, mime_type, caption, wa_status, created_at
    FROM wa_messages
    WHERE direction = 'out' AND message_type = 'image'
    ORDER BY id DESC
    LIMIT 1
")->fetch();

if ($image) {
    echo "<pre>";
    print_r($image);
    echo "</pre>";
} else {
    echo "❌ No hay imágenes enviadas";
}

echo "<hr>";

// Último audio enviado
echo "<h3>ÚLTIMO AUDIO ENVIADO</h3>";
$audio = $db->query("
    SELECT id, lead_id, from_phone, wa_msg_id, direction, message_type, body, media_id, mime_type, caption, wa_status, created_at
    FROM wa_messages
    WHERE direction = 'out' AND message_type = 'audio'
    ORDER BY id DESC
    LIMIT 1
")->fetch();

if ($audio) {
    echo "<pre>";
    print_r($audio);
    echo "</pre>";
} else {
    echo "❌ No hay audios enviados";
}

?>
