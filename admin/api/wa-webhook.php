<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/functions.php';
require_once __DIR__ . '/../../app/bot.php';

$db = getDB();

function logWebhook(string $msg): void {
    $dir  = __DIR__ . '/../../logs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = $dir . '/webhook.log';
    if (file_exists($file) && filesize($file) > 5 * 1024 * 1024) {
        rename($file, $file . '.' . date('Ymd_His') . '.bak');
    }
    file_put_contents($file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

// ── Webhook verification (GET) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode']         ?? $_GET['hub.mode']         ?? '';
    $token     = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge']    ?? $_GET['hub.challenge']    ?? '';

    $myToken = getSetting($db, 'wa_webhook_token');

    if ($mode === 'subscribe' && $token === $myToken) {
        http_response_code(200);
        echo $challenge;
    } else {
        http_response_code(403);
        logWebhook("VERIFY FAILED — token mismatch");
        echo 'Forbidden';
    }
    exit;
}

// ── Incoming messages (POST) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');

    // Verify X-Hub-Signature-256 if app secret is configured
    $appSecret = getSetting($db, 'wa_app_secret');
    if ($appSecret) {
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        $expected  = 'sha256=' . hash_hmac('sha256', $raw, $appSecret);
        if (!hash_equals($expected, $signature)) {
            http_response_code(403);
            logWebhook("Signature verification failed");
            exit;
        }
    }

    $data = json_decode($raw, true);
    logWebhook("POST received — entries: " . count($data['entry'] ?? []));

    if (($data['object'] ?? '') !== 'whatsapp_business_account') {
        http_response_code(200);
        exit;
    }

    foreach ($data['entry'] ?? [] as $entry) {
        foreach ($entry['changes'] ?? [] as $change) {
            $value = $change['value'] ?? [];

            // ── Delivery/read status updates ──────────────────────────
            if (!empty($value['statuses'])) {
                foreach ($value['statuses'] as $st) {
                    $stId  = $st['id']     ?? '';
                    $stVal = $st['status'] ?? '';
                    if ($stId && in_array($stVal, ['sent','delivered','read','failed'])) {
                        $db->prepare("UPDATE wa_messages SET wa_status=? WHERE wa_msg_id=?")
                           ->execute([$stVal, $stId]);
                        logWebhook("Status update: $stId → $stVal");
                    }
                }
                continue;
            }

            // Extract contact name from contacts array (parallel to messages)
            $contactNames = [];
            foreach ($value['contacts'] ?? [] as $c) {
                $waId = $c['wa_id'] ?? '';
                $profileName = $c['profile']['name'] ?? '';
                if ($waId && $profileName) $contactNames[$waId] = $profileName;
            }

            foreach ($value['messages'] ?? [] as $msg) {
                $type      = $msg['type'] ?? '';
                $fromPhone = normalizeWAPhone($msg['from'] ?? '');
                $waMsgId   = $msg['id']   ?? '';
                $ts        = $msg['timestamp'] ?? time();
                $createdAt = date('Y-m-d H:i:s', (int)$ts);
                $waName    = $contactNames[$fromPhone] ?? null;

                // Support text and other types (store body as description)
                if ($type === 'text') {
                    $body = $msg['text']['body'] ?? '';
                } elseif ($type === 'reaction') {
                    $emoji = $msg['reaction']['emoji'] ?? '👍';
                    $body  = "Reaccionó con $emoji";
                } elseif ($type === 'image') {
                    $body = '[Imagen 🖼️]';
                } elseif ($type === 'audio') {
                    $body = '[Audio 🎵]';
                } elseif ($type === 'video') {
                    $body = '[Video 🎥]';
                } elseif ($type === 'document') {
                    $body = '[Documento 📄]';
                } elseif ($type === 'sticker') {
                    $body = '[Sticker]';
                } elseif ($type === 'location') {
                    $body = '[Ubicación 📍]';
                } else {
                    $body = "[Mensaje: $type]";
                }

                // Match to a lead by phone number
                $leadId = null;
                $clean  = preg_replace('/\D/', '', $fromPhone);
                $stmt   = $db->query("SELECT id, whatsapp FROM leads");
                foreach ($stmt->fetchAll() as $lead) {
                    $leadPhone = preg_replace('/\D/', '', $lead['whatsapp'] ?? '');
                    if ($leadPhone && (str_ends_with($clean, $leadPhone) || str_ends_with($leadPhone, $clean))) {
                        $leadId = $lead['id'];
                        break;
                    }
                }

                // If no lead, check prospectos table — show name in inbox + update state
                $prospectoNombre = null;
                if (!$leadId) {
                    $pRows = $db->query("SELECT id, nombre, telefono FROM prospectos")->fetchAll();
                    foreach ($pRows as $pro) {
                        $proPhone = preg_replace('/\D/', '', $pro['telefono'] ?? '');
                        if ($proPhone && (str_ends_with($clean, $proPhone) || str_ends_with($proPhone, $clean))) {
                            $prospectoNombre = $pro['nombre'];
                            // Auto-advance state to 'respondio' if was 'contactado'
                            $db->prepare("UPDATE prospectos SET estado='respondio', updated_at=datetime('now','localtime') WHERE id=? AND estado IN ('contactado','nuevo')")
                               ->execute([$pro['id']]);
                            break;
                        }
                    }
                }

                logWebhook("Message from $fromPhone " . ($waName ? "($waName) " : "") . "(lead_id=$leadId" . ($prospectoNombre ? ", prospecto=$prospectoNombre" : "") . "): $body");

                // Save/update contact name — prefer WhatsApp name, fallback to prospecto name
                $displayName = $waName ?: $prospectoNombre;
                if ($displayName) {
                    $label = $prospectoNombre && !$waName ? 'prospecto' : 'nuevo';
                    $db->prepare("INSERT INTO wa_contacts (phone, wa_name, label, updated_at) VALUES (?, ?, ?, datetime('now','localtime'))
                                  ON CONFLICT(phone) DO UPDATE SET wa_name=COALESCE(excluded.wa_name, wa_name), updated_at=excluded.updated_at")
                       ->execute([$fromPhone, $displayName, $label]);
                }

                try {
                    $db->prepare("INSERT INTO wa_messages (lead_id, from_phone, wa_msg_id, direction, body, leido, created_at)
                                  VALUES (?, ?, ?, 'in', ?, 0, ?)")
                       ->execute([$leadId, $fromPhone, $waMsgId, $body, $createdAt]);
                } catch (Exception $e) {
                    logWebhook("DB error: " . $e->getMessage());
                }

                // ── Bot qualification (only text messages) ────────────────
                if ($type === 'text' && $body) {
                    $token   = getSetting($db, 'wa_token');
                    $phoneId = getSetting($db, 'wa_phone_id');
                    if ($token && $phoneId) {
                        try {
                            $handled = handleBotMessage($db, $fromPhone, $body, $token, $phoneId);
                            if ($handled) logWebhook("Bot handled message from $fromPhone");
                        } catch (Throwable $e) {
                            logWebhook("Bot error: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }

    http_response_code(200);
    echo 'OK';
    exit;
}

http_response_code(405);
