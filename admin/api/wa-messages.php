<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';

requireLogin();

$db = getDB();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ── Get leads with phone for new conversation ─────────────────────────────────
if ($action === 'leads_with_phone') {
    $rows = $db->query("SELECT id, nombre, whatsapp, nicho FROM leads WHERE whatsapp != '' AND whatsapp IS NOT NULL ORDER BY nombre ASC")->fetchAll();
    echo json_encode(['ok' => true, 'leads' => $rows]);
    exit;
}

// ── Get conversations list ────────────────────────────────────────────────────
if ($action === 'conversations') {
    $adminId = $_SESSION['admin_id'] ?? 0;
    $sql = "
        SELECT
            l.id        AS lead_id,
            l.nombre    AS nombre,
            l.whatsapp  AS phone,
            l.nicho     AS nicho,
            (SELECT body FROM wa_messages WHERE lead_id = l.id AND (admin_id IS NULL OR admin_id = $adminId) ORDER BY created_at DESC LIMIT 1) AS last_msg,
            (SELECT created_at FROM wa_messages WHERE lead_id = l.id AND (admin_id IS NULL OR admin_id = $adminId) ORDER BY created_at DESC LIMIT 1) AS last_ts,
            (SELECT COUNT(*) FROM wa_messages WHERE lead_id = l.id AND direction = 'in' AND leido = 0 AND (admin_id IS NULL OR admin_id = $adminId)) AS unread,
            COALESCE((SELECT label FROM wa_contacts WHERE phone = l.whatsapp), 'nuevo') AS label,
            COALESCE((SELECT wa_name FROM wa_contacts WHERE phone = l.whatsapp), '') AS wa_name
        FROM leads l
        WHERE EXISTS (SELECT 1 FROM wa_messages WHERE lead_id = l.id AND (admin_id IS NULL OR admin_id = $adminId))
        UNION ALL
        SELECT
            NULL          AS lead_id,
            COALESCE((SELECT wa_name FROM wa_contacts WHERE phone = from_phone), from_phone) AS nombre,
            from_phone    AS phone,
            'Desconocido' AS nicho,
            (SELECT body FROM wa_messages m2 WHERE m2.from_phone = m.from_phone AND m2.lead_id IS NULL AND (m2.admin_id IS NULL OR m2.admin_id = $adminId) ORDER BY m2.created_at DESC LIMIT 1) AS last_msg,
            MAX(created_at) AS last_ts,
            SUM(CASE WHEN direction='in' AND leido=0 THEN 1 ELSE 0 END) AS unread,
            COALESCE((SELECT label FROM wa_contacts WHERE phone = from_phone), 'nuevo') AS label,
            COALESCE((SELECT wa_name FROM wa_contacts WHERE phone = from_phone), '') AS wa_name
        FROM wa_messages m
        WHERE lead_id IS NULL AND (admin_id IS NULL OR admin_id = $adminId)
        GROUP BY from_phone
        ORDER BY last_ts DESC
    ";
    $rows = $db->query($sql)->fetchAll();
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

// ── Get messages for a lead or phone ─────────────────────────────────────────
if ($action === 'messages') {
    $since = (int)($_GET['since'] ?? 0);
    $lead  = null;

    if (!empty($_GET['lead_id'])) {
        $leadId = (int)$_GET['lead_id'];
        $stmt = $db->prepare("
            SELECT id, lead_id, from_phone, wa_msg_id, direction, body, leido, wa_status, created_at,
                   message_type, media_id, mime_type, file_name, caption
            FROM wa_messages
            WHERE lead_id = ? AND id > ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$leadId, $since]);
        $db->prepare("UPDATE wa_messages SET leido = 1 WHERE lead_id = ? AND direction = 'in' AND leido = 0")
           ->execute([$leadId]);
        $stmt2 = $db->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt2->execute([$leadId]);
        $lead = $stmt2->fetch();
    } elseif (!empty($_GET['phone'])) {
        $phone = $_GET['phone'];
        $stmt = $db->prepare("
            SELECT id, lead_id, from_phone, wa_msg_id, direction, body, leido, wa_status, created_at,
                   message_type, media_id, mime_type, file_name, caption
            FROM wa_messages
            WHERE lead_id IS NULL AND from_phone = ? AND id > ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$phone, $since]);
        $db->prepare("UPDATE wa_messages SET leido = 1 WHERE lead_id IS NULL AND from_phone = ? AND direction = 'in' AND leido = 0")
           ->execute([$phone]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'lead_id o phone requerido']);
        exit;
    }

    $msgs = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'messages' => $msgs, 'lead' => $lead]);
    exit;
}

// ── Send message or template ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $leadId  = isset($input['lead_id']) && $input['lead_id'] ? (int)$input['lead_id'] : null;
    $phone   = trim($input['phone'] ?? '');
    $token   = getSetting($db, 'wa_token');
    $phoneId = getSetting($db, 'wa_phone_id');

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
        echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
        exit;
    }
    // Normalizar al formato WhatsApp para que coincida con mensajes entrantes
    if (!$leadId) $toPhone = normalizeWAPhone($toPhone);

    // ── Template send ─────────────────────────────────────────────────────────
    if (!empty($input['template_name'])) {
        $tplName   = trim($input['template_name']);
        $tplLang   = trim($input['template_language'] ?? 'es');
        $tplParams = $input['template_params'] ?? [];
        $tplBody   = trim($input['template_body'] ?? "[Plantilla: $tplName]");

        $result = sendWATemplate($token, $phoneId, $toPhone, $tplName, $tplLang, $tplParams);
        if ($result['ok']) {
            $outPhone = $leadId ? 'me' : $toPhone;
            $db->prepare("INSERT INTO wa_messages (lead_id, from_phone, wa_msg_id, direction, message_type, body, leido, wa_status, created_at)
                          VALUES (?, ?, ?, 'out', 'text', ?, 1, 'sent', datetime('now','localtime'))")
               ->execute([$leadId ?: null, $outPhone, $result['wa_msg_id'] ?? null, $tplBody]);
            $stmt = $db->prepare("SELECT * FROM wa_messages WHERE id = ?");
            $stmt->execute([$db->lastInsertId()]);
            echo json_encode(['ok' => true, 'message' => $stmt->fetch()]);
        } else {
            echo json_encode($result);
        }
        exit;
    }

    // ── Free-form text send ───────────────────────────────────────────────────
    $body = trim($input['body'] ?? '');
    if (!$body) {
        echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
        exit;
    }

    $result = sendWAMessage($token, $phoneId, $toPhone, $body);
    if ($result['ok']) {
        $outPhone = $leadId ? 'me' : $toPhone;
        $db->prepare("INSERT INTO wa_messages (lead_id, from_phone, wa_msg_id, direction, message_type, body, leido, wa_status, created_at)
                      VALUES (?, ?, ?, 'out', 'text', ?, 1, 'sent', datetime('now','localtime'))")
           ->execute([$leadId ?: null, $outPhone, $result['wa_msg_id'] ?? null, $body]);
        $stmt = $db->prepare("SELECT * FROM wa_messages WHERE id = ?");
        $stmt->execute([$db->lastInsertId()]);
        echo json_encode(['ok' => true, 'message' => $stmt->fetch()]);
    } else {
        echo json_encode($result);
    }
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
