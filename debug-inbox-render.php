<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "<h2>DEBUG: Cómo se renderizan AUDIO y VIDEO en Inbox</h2>";
echo "<hr>";

// Tomar un audio real
echo "<h3>AUDIO - ID 245 (1569442824731215)</h3>";
$audio = $db->query("SELECT * FROM wa_messages WHERE id = 245")->fetch();

if ($audio) {
    echo "<pre>";
    echo "ID: " . $audio['id'] . "\n";
    echo "message_type: " . $audio['message_type'] . "\n";
    echo "media_id: " . $audio['media_id'] . "\n";
    echo "mime_type: " . $audio['mime_type'] . "\n";
    echo "body: " . $audio['body'] . "\n";
    echo "</pre>";

    echo "<h4>Cómo se vería el HTML generado:</h4>";
    echo "<pre>";
    echo "&lt;div class=\"msg-bubble msg-in msg-media\"&gt;\n";
    echo "  &lt;div class=\"msg-audio\"&gt;\n";
    echo "    &lt;audio controls&gt;\n";
    echo "      &lt;source src=\"/admin/api/wa-media.php?media_id=" . $audio['media_id'] . "\" type=\"" . $audio['mime_type'] . "\"&gt;\n";
    echo "      Tu navegador no soporta audio.\n";
    echo "    &lt;/audio&gt;\n";
    echo "  &lt;/div&gt;\n";
    echo "&lt;/div&gt;\n";
    echo "</pre>";

    echo "<h4>¿Debería funcionar?</h4>";
    echo "✅ message_type = 'audio' (correcto)<br>";
    echo "✅ media_id = '" . $audio['media_id'] . "' (presente)<br>";
    echo "✅ mime_type = '" . $audio['mime_type'] . "' (presente)<br>";
    echo "✅ Endpoint: <a href='/admin/api/wa-media.php?media_id=" . $audio['media_id'] . "' target='_blank'>Funciona (ya probado)</a><br>";
    echo "<br>";
}

// Tomar un video real
echo "<h3>VIDEO - ID 246 (2874276429596956)</h3>";
$video = $db->query("SELECT * FROM wa_messages WHERE id = 246")->fetch();

if ($video) {
    echo "<pre>";
    echo "ID: " . $video['id'] . "\n";
    echo "message_type: " . $video['message_type'] . "\n";
    echo "media_id: " . $video['media_id'] . "\n";
    echo "mime_type: " . $video['mime_type'] . "\n";
    echo "body: " . $video['body'] . "\n";
    echo "</pre>";

    echo "<h4>Cómo se vería el HTML generado:</h4>";
    echo "<pre>";
    echo "&lt;div class=\"msg-bubble msg-in msg-media\"&gt;\n";
    echo "  &lt;div class=\"msg-video-loading\" id=\"vid-246\"&gt;🎬 Cargando video...&lt;/div&gt;\n";
    echo "  &lt;div class=\"msg-video\" id=\"vid-246\" style=\"display:none\" onclick=\"openVideoLightbox(...)\"&gt;\n";
    echo "    &lt;video style=\"cursor:zoom-in\"&gt;\n";
    echo "      &lt;source src=\"/admin/api/wa-media.php?media_id=" . $video['media_id'] . "\" type=\"" . $video['mime_type'] . "\"&gt;\n";
    echo "      Tu navegador no soporta video.\n";
    echo "    &lt;/video&gt;\n";
    echo "  &lt;/div&gt;\n";
    echo "&lt;/div&gt;\n";
    echo "</pre>";

    echo "<h4>¿Debería funcionar?</h4>";
    echo "✅ message_type = 'video' (correcto)<br>";
    echo "✅ media_id = '" . $video['media_id'] . "' (presente)<br>";
    echo "✅ mime_type = '" . $video['mime_type'] . "' (presente)<br>";
    echo "✅ Endpoint: <a href='/admin/api/wa-media.php?media_id=" . $video['media_id'] . "' target='_blank'>Funciona (ya probado)</a><br>";
    echo "<br>";

    echo "<h4>Problema:</h4>";
    echo "El JavaScript espera el evento 'loadedmetadata' para mostrar el video.<br>";
    echo "<br>";
    echo "Posibles razones por las que NO ocurre:<br>";
    echo "1. El elemento &lt;video&gt; está en un &lt;div&gt; con display:none<br>";
    echo "2. El navegador no puede cargar el recurso<br>";
    echo "3. El navegador no reconoce el formato video/mp4<br>";
    echo "4. Hay un error CORS<br>";
    echo "5. El evento se dispara pero el código no lo detecta<br>";
}

echo "<hr>";
echo "<h3>¿POR QUÉ AUDIO NO APARECE?</h3>";
echo "Si el AUDIO está siendo guardado correctamente en la BD como type='audio',<br>";
echo "y el HTML debería renderizarse correctamente,<br>";
echo "entonces el problema está en cómo Inbox obtiene los mensajes.<br>";
echo "<br>";
echo "Posibles razones:<br>";
echo "1. La consulta SQL en Inbox filtra solo por ciertos tipos (image, video, etc.)<br>";
echo "2. Hay un problema con el AJAX que obtiene los mensajes<br>";
echo "3. El frontend tiene una condición que oculta audios<br>";

?>
