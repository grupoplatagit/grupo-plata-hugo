<?php
/**
 * Endpoint para enviar multimedia por WhatsApp
 *
 * Proceso:
 * 1. Recibir archivo
 * 2. Subir a Meta Cloud API
 * 3. Enviar mensaje con media_id
 * 4. Guardar en BD
 */

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';

requireLogin();

header('Content-Type: application/json');

$db = getDB();
$action = $_GET['action'] ?? '';

// DEBUG
$debug = [
    'action' => $action,
    'has_files' => isset($_FILES),
    'files_keys' => array_keys($_FILES ?? []),
    'post_keys' => array_keys($_POST ?? []),
];
@file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' DEBUG: ' . json_encode($debug) . "\n", FILE_APPEND);

// ── Send image, audio, video, or document ──────────────────────────────────
if ($action === 'send_media') {
    $leadId = isset($_POST['lead_id']) && $_POST['lead_id'] ? (int)$_POST['lead_id'] : null;
    $phone = trim($_POST['phone'] ?? '');
    $mediaType = $_POST['media_type'] ?? ''; // image, audio, video, document
    $caption = trim($_POST['caption'] ?? '');

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' ERROR: No file or error - ' . json_encode(['files' => $_FILES ?? 'empty', 'error' => $_FILES['file']['error'] ?? 'N/A']) . "\n", FILE_APPEND);
        echo json_encode(['ok' => false, 'msg' => 'Archivo requerido']);
        exit;
    }

    // Resolve destination phone
    if ($leadId) {
        $stmt = $db->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$leadId]);
        $lead = $stmt->fetch();
        if (!$lead || empty($lead['whatsapp'])) {
            echo json_encode(['ok' => false, 'msg' => 'Lead sin número de WhatsApp']);
            exit;
        }
        $toPhone = $lead['whatsapp'];
    } else {
        $toPhone = $phone;
    }
    if (!$toPhone) {
        echo json_encode(['ok' => false, 'msg' => 'Teléfono requerido']);
        exit;
    }
    if (!$leadId) $toPhone = normalizeWAPhone($toPhone);

    // Get config
    $token = getSetting($db, 'wa_token');
    $phoneId = getSetting($db, 'wa_phone_id');

    if (!$token || !$phoneId) {
        echo json_encode(['ok' => false, 'msg' => 'WhatsApp no configurado']);
        exit;
    }

    // File info
    $file = $_FILES['file'];
    $tmpPath = $file['tmp_name'];
    $mimeType = mime_content_type($tmpPath);

    // PASO 1: Upload file to Meta
    $ch = curl_init("https://graph.facebook.com/v18.0/{$phoneId}/media");

    $postFields = [
        'file' => new CURLFile($tmpPath, $mimeType),
        'type' => $mediaType,
        'messaging_product' => 'whatsapp',
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $uploadResp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        @unlink($tmpPath);
        echo json_encode(['ok' => false, 'msg' => 'Error subiendo archivo a Meta']);
        exit;
    }

    $uploadData = json_decode($uploadResp, true);
    if (!isset($uploadData['id'])) {
        @unlink($tmpPath);
        echo json_encode(['ok' => false, 'msg' => 'Meta no devolvió media_id']);
        exit;
    }

    $mediaId = $uploadData['id'];

    // PASO 2: Send message with media
    $messageBody = [
        'messaging_product' => 'whatsapp',
        'to' => $toPhone,
        'type' => $mediaType,
    ];

    // Build media object based on type
    $mediaObj = ['id' => $mediaId];
    if ($caption && in_array($mediaType, ['image', 'video', 'document'])) {
        $mediaObj['caption'] = $caption;
    }

    $messageBody[$mediaType] = $mediaObj;

    $ch = curl_init("https://graph.facebook.com/v18.0/{$phoneId}/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($messageBody),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $sendResp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    @unlink($tmpPath);

    if ($httpCode !== 200) {
        echo json_encode(['ok' => false, 'msg' => 'Error enviando mensaje a WhatsApp']);
        exit;
    }

    $sendData = json_decode($sendResp, true);
    if (!isset($sendData['messages'][0]['id'])) {
        echo json_encode(['ok' => false, 'msg' => 'Error al enviar']);
        exit;
    }

    $waMsgId = $sendData['messages'][0]['id'];

    // PASO 3: Save to BD
    $outPhone = $leadId ? 'me' : $toPhone;
    $msgBody = match($mediaType) {
        'image' => '[Imagen]',
        'audio' => '[Nota de voz]',
        'video' => '[Video]',
        'document' => '[Documento]',
        default => '[Media]',
    };

    $db->prepare("
        INSERT INTO wa_messages
        (lead_id, from_phone, wa_msg_id, direction, message_type, body, media_id, mime_type, caption, leido, wa_status, created_at)
        VALUES (?, ?, ?, 'out', ?, ?, ?, ?, ?, 1, 'sent', datetime('now','localtime'))
    ")->execute([
        $leadId ?: null,
        $outPhone,
        $waMsgId,
        $mediaType,
        $msgBody,
        $mediaId,
        $mimeType,
        $caption ?: null,
    ]);

    // Return message for display
    $stmt = $db->prepare("SELECT * FROM wa_messages WHERE id = ?");
    $stmt->execute([$db->lastInsertId()]);

    echo json_encode(['ok' => true, 'message' => $stmt->fetch()]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
