<?php
// API para recibir mensajes de WhatsApp desde Vercel
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/db.php';

$db = getDB();

// Crear tabla de mensajes si no existe
$db->exec("
    CREATE TABLE IF NOT EXISTS whatsapp_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_number TEXT NOT NULL,
        message_text TEXT,
        message_type TEXT DEFAULT 'text',
        message_id TEXT UNIQUE,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'received'
    )
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Log todo
    @file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . " POST: " . json_encode($data) . "\n", FILE_APPEND);

    if ($data && isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Procesar mensajes entrantes
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $msg) {
                        $from = $msg['from'] ?? '';
                        $body = $msg['text']['body'] ?? '';
                        $msg_id = $msg['id'] ?? '';
                        $type = $msg['type'] ?? 'text';

                        // Guardar en BD
                        try {
                            $db->prepare("
                                INSERT INTO whatsapp_messages
                                (from_number, message_text, message_type, message_id)
                                VALUES (?, ?, ?, ?)
                            ")->execute([$from, $body, $type, $msg_id]);

                            @file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . " Mensaje guardado: $from - $body\n", FILE_APPEND);
                        } catch (Exception $e) {
                            @file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . " Error: " . $e->getMessage() . "\n", FILE_APPEND);
                        }
                    }
                }

                // Procesar cambios de estado
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $msg_id = $status['id'] ?? '';
                        $status_val = $status['status'] ?? '';

                        try {
                            $db->prepare("
                                UPDATE whatsapp_messages
                                SET status = ?
                                WHERE message_id = ?
                            ")->execute([$status_val, $msg_id]);
                        } catch (Exception $e) {}
                    }
                }
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
?>
