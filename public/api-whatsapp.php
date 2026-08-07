<?php
// WhatsApp Webhook - PÚBLICO (sin autenticación)
// Meta enviará parámetros con puntos: hub.mode → PHP los convierte a guiones bajos
// Este archivo NO requiere login

// Cargar credenciales
$env_file = __DIR__ . '/../.env.whatsapp';
if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
    define('WHATSAPP_TOKEN', $env['WHATSAPP_VERIFY_TOKEN'] ?? 'grupo_plata_verify_2024');
    define('WHATSAPP_ACCESS_TOKEN', $env['WHATSAPP_ACCESS_TOKEN'] ?? '');
    define('WHATSAPP_PHONE_NUMBER_ID', $env['WHATSAPP_PHONE_NUMBER_ID'] ?? '');
} else {
    define('WHATSAPP_TOKEN', 'grupo_plata_verify_2024');
    define('WHATSAPP_ACCESS_TOKEN', '');
    define('WHATSAPP_PHONE_NUMBER_ID', '');
}

// Responder a solicitudes de Meta
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Los parámetros con puntos se convierten a guiones bajos en PHP
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    // Log para debug
    $log_dir = __DIR__ . '/../logs';
    @mkdir($log_dir, 0755, true);
    @file_put_contents($log_dir . '/whatsapp-webhook.log',
        date('Y-m-d H:i:s') . " GET\n" .
        "  mode=$mode\n" .
        "  token=$token\n" .
        "  challenge=$challenge\n" .
        "  REQUEST: " . json_encode($_GET) . "\n\n",
        FILE_APPEND
    );

    // Verificar Meta
    if ($mode === 'subscribe' && $token === WHATSAPP_TOKEN && $challenge) {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }

    // Token inválido
    http_response_code(403);
    exit;
}

// POST - Recibir mensajes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_dir = __DIR__ . '/../logs';
    @mkdir($log_dir, 0755, true);

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    @file_put_contents($log_dir . '/whatsapp-webhook.log',
        date('Y-m-d H:i:s') . " POST\n" .
        substr($raw, 0, 500) . "\n\n",
        FILE_APPEND
    );

    // Procesar mensaje y guardar en BD
    require_once __DIR__ . '/../app/db.php';
    $db = getDB();

    // Detectar WABA ID del payload de Meta (para soportar múltiples WABAs)
    $waba_id = $data['entry'][0]['id'] ?? null;
    if ($waba_id) {
        @file_put_contents($log_dir . '/whatsapp-webhook.log',
            date('Y-m-d H:i:s') . " WABA ID detectado: $waba_id\n",
            FILE_APPEND
        );
    }

    if ($data && isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // ── Mensajes entrantes (texto y multimedia) ──────────────────────────────
                foreach ($value['messages'] ?? [] as $msg) {
                    $from = $msg['from'] ?? '';
                    $wa_msg_id = $msg['id'] ?? '';
                    $msg_type = $msg['type'] ?? 'text';

                    if (!$wa_msg_id) continue;

                    // Buscar si existe un lead con este teléfono
                    // Esto permite que si un contacto sin lead se convierte después,
                    // los nuevos mensajes se vinculen automáticamente
                    $lead_id = null;
                    $lead_stmt = $db->prepare("SELECT id FROM leads WHERE whatsapp = ? LIMIT 1");
                    $lead_stmt->execute([$from]);
                    $lead_row = $lead_stmt->fetch();
                    if ($lead_row) {
                        $lead_id = $lead_row['id'];
                    }

                    $body = '';
                    $media_id = null;
                    $mime_type = null;
                    $file_name = null;
                    $caption = null;

                    try {
                        switch ($msg_type) {
                            case 'text':
                                $body = $msg['text']['body'] ?? '';
                                break;

                            case 'audio':
                                $body = '[Nota de voz]';
                                $media_id = $msg['audio']['id'] ?? '';
                                $mime_type = $msg['audio']['mime_type'] ?? 'audio/ogg';
                                break;

                            case 'image':
                                $body = '[Imagen]';
                                $media_id = $msg['image']['id'] ?? '';
                                $mime_type = $msg['image']['mime_type'] ?? 'image/jpeg';
                                $caption = $msg['image']['caption'] ?? null;
                                break;

                            case 'sticker':
                                $body = '[Sticker]';
                                $media_id = $msg['sticker']['id'] ?? '';
                                $mime_type = $msg['sticker']['mime_type'] ?? 'image/webp';
                                break;

                            case 'document':
                                $file_name = $msg['document']['filename'] ?? null;
                                $body = '[Documento: ' . ($file_name ?: 'sin nombre') . ']';
                                $media_id = $msg['document']['id'] ?? '';
                                $mime_type = $msg['document']['mime_type'] ?? 'application/octet-stream';
                                $caption = $msg['document']['caption'] ?? null;
                                break;

                            case 'video':
                                $body = '[Video]';
                                $media_id = $msg['video']['id'] ?? '';
                                $mime_type = $msg['video']['mime_type'] ?? 'video/mp4';
                                $caption = $msg['video']['caption'] ?? null;
                                break;

                            default:
                                $body = '[Tipo de mensaje no soportado: ' . $msg_type . ']';
                        }

                        $db->prepare("
                            INSERT INTO wa_messages
                            (lead_id, from_phone, wa_msg_id, direction, message_type, body, media_id, mime_type, file_name, caption, leido, wa_status, created_at)
                            VALUES (?, ?, ?, 'in', ?, ?, ?, ?, ?, ?, 0, 'received', datetime('now', 'localtime'))
                        ")->execute([$lead_id, $from, $wa_msg_id, $msg_type, $body, $media_id, $mime_type, $file_name, $caption]);

                        $log_msg = date('Y-m-d H:i:s') . " MSG IN [$msg_type]: $from - $body (wamid: $wa_msg_id)";
                        if ($media_id) {
                            $log_msg .= " | media_id: " . substr($media_id, 0, 30);
                        } else if ($msg_type !== 'text') {
                            $log_msg .= " | ⚠️ media_id VACÍO";
                        }
                        @file_put_contents($log_dir . '/whatsapp-webhook.log', $log_msg . "\n", FILE_APPEND);
                    } catch (Exception $e) {
                        @file_put_contents($log_dir . '/whatsapp-webhook.log',
                            date('Y-m-d H:i:s') . " Error inserting message: " . $e->getMessage() . "\n",
                            FILE_APPEND
                        );
                    }
                }

                // ── Status updates (sent, delivered, read, failed) ──────────────
                foreach ($value['statuses'] ?? [] as $status) {
                    $wa_msg_id = $status['id'] ?? '';
                    $new_status = $status['status'] ?? '';
                    $timestamp = $status['timestamp'] ?? '';

                    if ($wa_msg_id && $new_status) {
                        try {
                            // Actualizar estado del mensaje
                            $db->prepare("
                                UPDATE wa_messages
                                SET wa_status = ?, created_at = datetime('now', 'localtime')
                                WHERE wa_msg_id = ?
                            ")->execute([$new_status, $wa_msg_id]);

                            @file_put_contents($log_dir . '/whatsapp-webhook.log',
                                date('Y-m-d H:i:s') . " STATUS: $wa_msg_id → $new_status\n",
                                FILE_APPEND
                            );
                        } catch (Exception $e) {
                            @file_put_contents($log_dir . '/whatsapp-webhook.log',
                                date('Y-m-d H:i:s') . " Error updating status: " . $e->getMessage() . "\n",
                                FILE_APPEND
                            );
                        }
                    }
                }
            }
        }
    }

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(405);
exit;
?>
