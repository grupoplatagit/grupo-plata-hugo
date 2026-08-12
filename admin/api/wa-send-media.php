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
    $detectedMime = mime_content_type($tmpPath);

    // Usar MIME type basado en media_type del formulario (más confiable que mime_content_type)
    $mimeType = $detectedMime;

    if ($mediaType === 'audio') {
        // Meta acepta para envío: audio/aac, audio/amr, audio/mpeg, audio/mp4, audio/ogg
        $acceptedAudioMimes = ['audio/aac', 'audio/amr', 'audio/mpeg', 'audio/mp4', 'audio/ogg'];

        // El navegador debe haber grabado REALMENTE en un formato compatible
        // NO hacer conversiones artificiales de formato
        if (!in_array($mimeType, $acceptedAudioMimes)) {
            @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' AUDIO ERROR: MIME ' . $mimeType . ' no compatible. Requiere: ' . implode(', ', $acceptedAudioMimes) . "\n", FILE_APPEND);
            @unlink($tmpPath);
            echo json_encode([
                'ok' => false,
                'msg' => 'Formato de audio no compatible. El navegador no grabó en OGG/Opus.',
                'debug' => [
                    'received_mime' => $mimeType,
                    'accepted_mimes' => $acceptedAudioMimes
                ]
            ]);
            exit;
        }

        @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' AUDIO: MIME compatible: ' . $mimeType . "\n", FILE_APPEND);
    }

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
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log detailed error info
    $debugInfo = [
        'step' => 'upload_to_meta',
        'filename' => $_FILES['file']['name'],
        'mime_type' => $mimeType,
        'file_size' => $_FILES['file']['size'],
        'http_code' => $httpCode,
        'curl_error' => $curlError ?: 'none',
        'curl_errno' => $curlErrno,
        'response_length' => strlen($uploadResp),
    ];

    if ($httpCode !== 200) {
        $debugInfo['response_body'] = substr($uploadResp, 0, 1000);
        @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' UPLOAD FAILED: ' . json_encode($debugInfo) . "\n", FILE_APPEND);
        @unlink($tmpPath);
        echo json_encode([
            'ok' => false,
            'msg' => 'Error subiendo archivo a Meta',
            'debug' => [
                'http_code' => $httpCode,
                'error' => $curlError ?: json_decode($uploadResp, true)
            ]
        ]);
        exit;
    }

    $uploadData = json_decode($uploadResp, true);
    if (!isset($uploadData['id'])) {
        @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' NO MEDIA_ID: ' . json_encode(['response' => $uploadData]) . "\n", FILE_APPEND);
        @unlink($tmpPath);
        echo json_encode([
            'ok' => false,
            'msg' => 'Meta no devolvió media_id',
            'debug' => $uploadData
        ]);
        exit;
    }

    @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' UPLOAD SUCCESS: ' . json_encode(['filename' => $_FILES['file']['name'], 'mime' => $mimeType, 'media_id' => $uploadData['id']]) . "\n", FILE_APPEND);

    $mediaId = $uploadData['id'];

    // PASO 2: Send message with media
    // Para audio, intentar como document si falla como audio
    $sendType = $mediaType;

    $messageBody = [
        'messaging_product' => 'whatsapp',
        'to' => $toPhone,
        'type' => $sendType,
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
    $sendError = curl_error($ch);
    $sendErrno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    @unlink($tmpPath);

    // Log mensaje enviado
    @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' SEND MESSAGE: ' . json_encode([
        'to' => $toPhone,
        'type' => $mediaType,
        'media_id' => $mediaId,
        'http_code' => $httpCode,
    ]) . "\n", FILE_APPEND);

    if ($httpCode !== 200) {
        $errorData = json_decode($sendResp, true);
        @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' SEND FAILED: ' . json_encode([
            'http_code' => $httpCode,
            'curl_error' => $sendError,
            'response' => $errorData ?: substr($sendResp, 0, 500),
        ]) . "\n", FILE_APPEND);
        echo json_encode([
            'ok' => false,
            'msg' => 'Error enviando mensaje a WhatsApp',
            'debug' => [
                'http_code' => $httpCode,
                'error' => $errorData
            ]
        ]);
        exit;
    }

    $sendData = json_decode($sendResp, true);
    if (!isset($sendData['messages'][0]['id'])) {
        @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' NO MESSAGE ID: ' . json_encode(['response' => $sendData]) . "\n", FILE_APPEND);
        echo json_encode([
            'ok' => false,
            'msg' => 'Error al enviar',
            'debug' => $sendData
        ]);
        exit;
    }

    @file_put_contents(__DIR__ . '/../../logs/wa-send-media.log', date('Y-m-d H:i:s') . ' SEND SUCCESS: ' . json_encode(['message_id' => $sendData['messages'][0]['id']]) . "\n", FILE_APPEND);

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
