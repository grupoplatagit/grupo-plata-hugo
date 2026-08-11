<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/functions.php';

try {
    $db = getDB();
    echo "<h2>Test Media Download</h2>";

    // Obtener un media_id
    $msg = $db->query("SELECT * FROM wa_messages WHERE message_type = 'image' LIMIT 1")->fetch();

    if (!$msg) {
        echo "<p style='color:red'>❌ No hay imágenes en la BD</p>";

        // Mostrar qué hay
        $all = $db->query("SELECT COUNT(*) as cnt FROM wa_messages")->fetch();
        echo "<p>Total mensajes: " . $all['cnt'] . "</p>";

        $types = $db->query("SELECT DISTINCT message_type FROM wa_messages")->fetchAll();
        echo "<p>Tipos: ";
        foreach ($types as $t) echo $t['message_type'] . ", ";
        echo "</p>";

        exit;
    }

    $media_id = $msg['media_id'];
    echo "<p>Media ID: <b>$media_id</b></p>";
    echo "<p>Mime Type: <b>" . $msg['mime_type'] . "</b></p>";

    // Obtener token
    $token = getSetting($db, 'wa_token');
    if (!$token) {
        echo "<p style='color:red'>❌ No hay token configurado</p>";
        exit;
    }

    echo "<p>Token: " . substr($token, 0, 20) . "...</p>";

    // Solicitar a Meta
    $url = "https://graph.facebook.com/v23.0/{$media_id}";
    echo "<p>URL: $url</p>";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p>HTTP Code: <b style='color:" . ($code === 200 ? 'green' : 'red') . "'>$code</b></p>";

    if ($code !== 200) {
        echo "<p style='color:red'>❌ Meta API error:</p>";
        echo "<pre>" . htmlspecialchars($resp) . "</pre>";
        exit;
    }

    $data = json_decode($resp, true);
    if (!isset($data['url'])) {
        echo "<p style='color:red'>❌ No URL en respuesta</p>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        exit;
    }

    echo "<p style='color:green'>✅ Todo OK - URL obtenida de Meta</p>";
    echo "<p>File URL (primeros 100 chars): " . substr($data['url'], 0, 100) . "</p>";

} catch (Exception $e) {
    echo "<p style='color:red'><b>ERROR:</b> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
