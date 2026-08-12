<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';

header('Content-Type: application/json');
requireLogin();

$db    = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

// ── Save contact (simple, no lead) ────────────────────────────────────────────
if ($action === 'save_contact') {
    $phone = trim($input['phone'] ?? '');
    $wa_name = trim($input['wa_name'] ?? '');
    if (!$phone) {
        echo json_encode(['ok'=>false,'msg'=>'phone requerido']);
        exit;
    }
    try {
        $db->prepare("INSERT INTO wa_contacts (phone,wa_name,updated_at) VALUES (?,?,datetime('now','localtime'))
                      ON CONFLICT(phone) DO UPDATE SET wa_name=excluded.wa_name, updated_at=excluded.updated_at")
           ->execute([$phone, $wa_name]);
        echo json_encode(['ok'=>true,'msg'=>'Contacto guardado']);
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
    exit;
}

// ── Get contact info ──────────────────────────────────────────────────────────
if ($action === 'get') {
    $phone = trim($input['phone'] ?? $_GET['phone'] ?? '');
    if (!$phone) { echo json_encode(['ok'=>false,'msg'=>'phone requerido']); exit; }

    $stmt = $db->prepare("SELECT * FROM wa_contacts WHERE phone = ?");
    $stmt->execute([$phone]);
    $contact = $stmt->fetch() ?: ['phone'=>$phone,'label'=>'nuevo','notes'=>'','lead_id'=>null];
    echo json_encode(['ok'=>true,'contact'=>$contact]);
    exit;
}

// ── Set label ─────────────────────────────────────────────────────────────────
if ($action === 'label') {
    $phone = trim($input['phone'] ?? '');
    $allowedLabels = ['nuevo','contactado','datos_solicitados','documentacion','en_analisis','preaprobado','aprobado','desembolsado','rechazado','descartado'];
    $raw   = trim($input['label'] ?? '');
    $label = in_array($raw, $allowedLabels) ? $raw : 'nuevo';
    if (!$phone) { echo json_encode(['ok'=>false,'msg'=>'phone requerido']); exit; }

    $db->prepare("INSERT INTO wa_contacts (phone,label,updated_at) VALUES (?,?,datetime('now','localtime'))
                  ON CONFLICT(phone) DO UPDATE SET label=excluded.label, updated_at=excluded.updated_at")
       ->execute([$phone, $label]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── Save notes ────────────────────────────────────────────────────────────────
if ($action === 'notes') {
    $phone = trim($input['phone'] ?? '');
    $notes = trim($input['notes'] ?? '');
    if (!$phone) { echo json_encode(['ok'=>false,'msg'=>'phone requerido']); exit; }

    $db->prepare("INSERT INTO wa_contacts (phone,notes,updated_at) VALUES (?,?,datetime('now','localtime'))
                  ON CONFLICT(phone) DO UPDATE SET notes=excluded.notes, updated_at=excluded.updated_at")
       ->execute([$phone, $notes]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── Convert to lead ───────────────────────────────────────────────────────────
if ($action === 'to_lead') {
    $phone                = trim($input['phone']                ?? '');
    $nombre               = trim($input['nombre']               ?? '');
    $email                = trim($input['email']                ?? '');
    $dni                  = trim($input['dni']                  ?? '');
    $ciudad               = trim($input['ciudad']               ?? '');
    $provincia            = trim($input['provincia']            ?? '');
    $tipo_credito         = trim($input['tipo_credito']         ?? '');
    $monto_solicitado     = trim($input['monto_solicitado']     ?? '');
    $destino_credito      = trim($input['destino_credito']      ?? '');
    $ingresos_mensuales   = trim($input['ingresos_mensuales']   ?? '');
    $situacion_laboral    = trim($input['situacion_laboral']    ?? '');
    $antiguedad_laboral   = trim($input['antiguedad_laboral']   ?? '');
    $cuotas_deseadas      = trim($input['cuotas_deseadas']      ?? '');
    $asesor_asignado      = trim($input['asesor_asignado']      ?? '');
    $prioridad            = trim($input['prioridad']            ?? 'media');

    if (!$nombre || !$phone) {
        echo json_encode(['ok'=>false,'msg'=>'Nombre y teléfono requeridos']);
        exit;
    }

    // Check if lead already exists with this phone
    $exists = $db->prepare("SELECT id FROM leads WHERE whatsapp = ?");
    $exists->execute([$phone]);
    $existingId = $exists->fetchColumn();

    if ($existingId) {
        echo json_encode(['ok'=>false,'msg'=>'Ya existe un lead con este número','lead_id'=>$existingId]);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO leads (nombre,email,whatsapp,dni,ciudad,provincia,tipo_credito,monto_solicitado,destino_credito,ingresos_mensuales,situacion_laboral,antiguedad_laboral,cuotas_deseadas,asesor_asignado,prioridad,leido,wa_status)
                              VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,'sent')");
        $stmt->execute([
            $nombre,
            $email ?: $phone.'@wa.contact',
            $phone,
            $dni,
            $ciudad,
            $provincia,
            $tipo_credito,
            $monto_solicitado,
            $destino_credito,
            $ingresos_mensuales,
            $situacion_laboral,
            $antiguedad_laboral,
            $cuotas_deseadas,
            $asesor_asignado,
            $prioridad
        ]);
        $leadId = $db->lastInsertId();
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'msg'=>'Error al guardar: '.$e->getMessage()]);
        exit;
    }

    // Link contact
    $db->prepare("INSERT INTO wa_contacts (phone,lead_id,label,updated_at) VALUES (?,?,'contactado',datetime('now','localtime'))
                  ON CONFLICT(phone) DO UPDATE SET lead_id=excluded.lead_id, label='contactado', updated_at=excluded.updated_at")
       ->execute([$phone, $leadId]);

    // Re-link existing messages
    $db->prepare("UPDATE wa_messages SET lead_id=? WHERE lead_id IS NULL AND from_phone=?")
       ->execute([$leadId, $phone]);

    echo json_encode(['ok'=>true,'lead_id'=>$leadId,'msg'=>'Solicitud de crédito creada correctamente']);
    exit;
}

// ── Delete chat (all messages) ────────────────────────────────────────────────
if ($action === 'delete_chat') {
    $phone = trim($input['phone'] ?? '');
    if (!$phone) {
        echo json_encode(['ok'=>false,'msg'=>'phone requerido']);
        exit;
    }

    try {
        // Eliminar todos los mensajes de este contacto
        $db->prepare("DELETE FROM wa_messages WHERE from_phone = ?")->execute([$phone]);

        // Opcionalmente, también eliminar el contacto
        $db->prepare("DELETE FROM wa_contacts WHERE phone = ?")->execute([$phone]);

        echo json_encode(['ok'=>true,'msg'=>'Chat eliminado']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
        exit;
    }
}

echo json_encode(['ok'=>false,'msg'=>'Acción no válida']);
