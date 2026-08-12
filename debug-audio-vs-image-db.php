<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "<h2>ÚLTIMA IMAGEN ENVIADA</h2>";
$image = $db->query("
    SELECT * FROM wa_messages
    WHERE direction = 'out' AND message_type = 'image'
    ORDER BY id DESC LIMIT 1
")->fetch();

if ($image) {
    echo "<pre>";
    print_r($image);
    echo "</pre>";
} else {
    echo "No hay imágenes";
}

echo "<h2>ÚLTIMO AUDIO ENVIADO</h2>";
$audio = $db->query("
    SELECT * FROM wa_messages
    WHERE direction = 'out' AND message_type = 'audio'
    ORDER BY id DESC LIMIT 1
")->fetch();

if ($audio) {
    echo "<pre>";
    print_r($audio);
    echo "</pre>";
} else {
    echo "No hay audios";
}
?>
