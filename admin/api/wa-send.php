<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';

header('Content-Type: application/json');

$db = getDB();

// Allow CLI cron or web requests with a valid cron token
$isCli     = php_sapi_name() === 'cli';
$cronToken = getSetting($db, 'wa_cron_token');
$isCronWeb = !empty($_GET['cron_token']) && $cronToken && hash_equals($cronToken, $_GET['cron_token']);
if (!$isCli && !$isCronWeb) {
    requireLogin();
}

// ── Cron auto-send mode ───────────────────────────────────────────────────────
if ($isCli || $isCronWeb) {
    $token   = getSetting($db, 'wa_token');
    $phoneId = getSetting($db, 'wa_phone_id');
    $enabled = getSetting($db, 'wa_auto_enabled') === '1';
    $minutes = (int)(getSetting($db, 'wa_auto_minutes') ?: 10);
    $tpl     = getSetting($db, 'wa_auto_message');

    if (!$enabled || empty($token) || empty($phoneId)) {
        echo json_encode(['ok' => false, 'msg' => 'Auto-send disabled or not configured']);
        exit;
    }

    // Leads pending, created >= $minutes ago, that have a whatsapp number
    $pending = $db->prepare("
        SELECT * FROM leads
        WHERE wa_status = 'pending'
          AND whatsapp IS NOT NULL AND whatsapp != ''
          AND created_at <= datetime('now', 'localtime', ? || ' minutes')
    ");
    $pending->execute(['-' . $minutes]);
    $rows = $pending->fetchAll();

    $sent = 0;
    foreach ($rows as $lead) {
        $msg = str_replace(
            ['{nombre}', '{nicho}', '{pais}'],
            [
                $lead['nombre'],
                $lead['nicho']  ?: 'tu negocio',
                $lead['pais']   ?: 'tu país',
            ],
            $tpl
        );
        $result = sendWAMessage($token, $phoneId, $lead['whatsapp'], $msg);
        $status = $result['ok'] ? 'sent' : 'failed';
        $db->prepare("UPDATE leads SET wa_status=?, wa_sent_at=datetime('now','localtime') WHERE id=?")
           ->execute([$status, $lead['id']]);
        if ($result['ok']) {
            $sent++;
            try {
                $db->prepare("INSERT INTO wa_messages (lead_id, from_phone, wa_msg_id, direction, body, leido, created_at)
                              VALUES (?, 'me', ?, 'out', ?, 1, datetime('now','localtime'))")
                   ->execute([$lead['id'], null, $msg]);
            } catch (Exception $e) {}
        }
    }
    echo json_encode(['ok' => true, 'sent' => $sent, 'processed' => count($rows)]);
    exit;
}

// ── Manual send (AJAX from admin panel) ──────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$phone   = trim($input['phone']   ?? '');
$message = trim($input['message'] ?? '');
$leadId  = isset($input['lead_id']) ? (int)$input['lead_id'] : null;

if (empty($phone) || empty($message)) {
    echo json_encode(['ok' => false, 'msg' => 'Teléfono y mensaje requeridos']);
    exit;
}

$token   = getSetting($db, 'wa_token');
$phoneId = getSetting($db, 'wa_phone_id');

if (empty($token) || empty($phoneId)) {
    echo json_encode(['ok' => false, 'msg' => 'API no configurada. Completá el token y Phone ID en la sección WhatsApp.']);
    exit;
}

// If message has template variables and a lead_id, resolve them
if ($leadId) {
    $stmt = $db->prepare("SELECT * FROM leads WHERE id = ?");
    $stmt->execute([$leadId]);
    $lead = $stmt->fetch();
    if ($lead) {
        $message = str_replace(
            ['{nombre}', '{nicho}', '{pais}'],
            [$lead['nombre'], $lead['nicho'] ?: 'tu negocio', $lead['pais'] ?: 'tu país'],
            $message
        );
    }
}

$result = sendWAMessage($token, $phoneId, $phone, $message);

// Update lead status and log message
if ($leadId && $result['ok']) {
    $db->prepare("UPDATE leads SET wa_status='sent', wa_sent_at=datetime('now','localtime') WHERE id=?")
       ->execute([$leadId]);
    try {
        $db->prepare("INSERT INTO wa_messages (lead_id, from_phone, wa_msg_id, direction, body, leido, created_at)
                      VALUES (?, 'me', ?, 'out', ?, 1, datetime('now','localtime'))")
           ->execute([$leadId, null, $message]);
    } catch (Exception $e) {}
}

echo json_encode($result);
