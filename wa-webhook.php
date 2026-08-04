<?php
// WhatsApp Webhook - Raíz
header('Content-Type: application/json');

// Crear tabla de mensajes
$db_file = __DIR__ . '/chats.db';
$db = new PDO('sqlite:' . $db_file);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

$db->exec("
    CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY,
        from_number TEXT,
        body TEXT,
        msg_id TEXT UNIQUE,
        status TEXT DEFAULT 'received',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$dir = __DIR__ . '/logs';
@mkdir($dir, 0755, true);

// GET - Meta verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub.mode'] ?? '';
    $challenge = $_GET['hub.challenge'] ?? '';

    @file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " GET: mode=$mode challenge=$challenge\n", FILE_APPEND);

    if ($mode === 'subscribe' && $challenge) {
        http_response_code(200);
        echo $challenge;
        exit;
    }

    http_response_code(200);
    echo 'ok';
    exit;
}

// POST - Incoming messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    @file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " POST received\n", FILE_APPEND);

    if ($data && isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Mensajes
                foreach ($value['messages'] ?? [] as $msg) {
                    $from = $msg['from'] ?? '';
                    $body = $msg['text']['body'] ?? '';
                    $msg_id = $msg['id'] ?? '';

                    $db->prepare("
                        INSERT OR IGNORE INTO messages (from_number, body, msg_id)
                        VALUES (?, ?, ?)
                    ")->execute([$from, $body, $msg_id]);

                    @file_put_contents($dir . '/webhook.log', date('Y-m-d H:i:s') . " Mensaje: $from - $body\n", FILE_APPEND);
                }

                // Status
                foreach ($value['statuses'] ?? [] as $st) {
                    $msg_id = $st['id'] ?? '';
                    $status = $st['status'] ?? '';

                    $db->prepare("
                        UPDATE messages SET status = ? WHERE msg_id = ?
                    ")->execute([$status, $msg_id]);
                }
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(405);
echo 'Not allowed';
exit;
?>
