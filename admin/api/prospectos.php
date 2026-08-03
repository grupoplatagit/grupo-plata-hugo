<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
header('Content-Type: application/json');
requireLogin();

$db     = getDB();
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

$estados = ['nuevo','contactado','respondio','demo_enviada','ganado','descartado'];

// ── Enriquecer con web e instagram desde prospectos_enriched.json ─────────────
if ($action === 'enrich') {
    $path = __DIR__ . '/../../resultados/prospectos_enriched.json';
    if (!file_exists($path)) { echo json_encode(['ok'=>false,'msg'=>'Archivo enriched no encontrado']); exit; }
    $data = json_decode(file_get_contents($path), true) ?? [];
    $updated = 0;
    foreach ($data as $p) {
        if (!$p['web'] && !$p['instagram']) continue;
        $tel = trim($p['tel'] ?? '');
        if (!$tel) continue;
        $telDigits = substr(preg_replace('/\D/', '', $tel), -8);
        $stmt = $db->prepare("SELECT id FROM prospectos WHERE REPLACE(REPLACE(REPLACE(telefono,'+',''),' ',''),'-','') LIKE ?");
        $stmt->execute(['%' . $telDigits . '%']);
        $row = $stmt->fetch();
        if (!$row) continue;
        $db->prepare("UPDATE prospectos SET web=COALESCE(?,web), instagram=COALESCE(?,instagram), updated_at=datetime('now','localtime') WHERE id=?")
           ->execute([$p['web'] ?: null, $p['instagram'] ?: null, $row['id']]);
        $updated++;
    }
    echo json_encode(['ok'=>true,'updated'=>$updated,'msg'=>"$updated prospectos enriquecidos con web/instagram"]);
    exit;
}

// ── Importar desde JSON (existente) ──────────────────────────────────────────
if ($action === 'import') {
    $jsonPath = __DIR__ . '/../../resultados/prospectos_contables_caba.json';
    if (!file_exists($jsonPath)) { echo json_encode(['ok'=>false,'msg'=>'Archivo JSON no encontrado']); exit; }
    $data = json_decode(file_get_contents($jsonPath), true) ?? [];
    $imported = 0;
    foreach ($data as $p) {
        $row = $db->prepare("SELECT id FROM prospectos WHERE nombre=? AND zona=?");
        $row->execute([$p['nombre'], $p['zona'] ?? '']);
        if ($row->fetch()) continue;
        $db->prepare("INSERT INTO prospectos (nombre,zona,telefono,web,categoria) VALUES (?,?,?,?,?)")
           ->execute([$p['nombre'], $p['zona'] ?? '', $p['tel'] ?? '', $p['web'] ?? '', $p['categoria'] ?? 'SIN WEB ★★★']);
        $imported++;
    }
    echo json_encode(['ok'=>true,'imported'=>$imported,'msg'=>"$imported prospectos importados"]);
    exit;
}

// ── Importar desde prospectos_200_completo.js ─────────────────────────────────
if ($action === 'import_200') {
    $jsPath = __DIR__ . '/../../prospectos_200_completo.js';
    if (!file_exists($jsPath)) { echo json_encode(['ok'=>false,'msg'=>'Archivo JS no encontrado']); exit; }

    $content = file_get_contents($jsPath);
    // Extraer cada objeto { nombre: "...", dir: "...", zona: "...", tel: "..." }
    preg_match_all('/\{[^}]+\}/s', $content, $matches);
    $imported = $skipped_dup = $skipped_nophone = 0;

    foreach ($matches[0] as $block) {
        preg_match('/nombre:\s*"([^"]*)"/', $block, $mn);
        preg_match('/dir:\s*"([^"]*)"/',    $block, $md);
        preg_match('/zona:\s*"([^"]*)"/',   $block, $mz);
        preg_match('/tel:\s*"([^"]*)"/',    $block, $mt);

        $nombre = trim($mn[1] ?? '');
        $dir    = trim($md[1] ?? '');
        $zona   = trim($mz[1] ?? '');
        $tel    = trim($mt[1] ?? '');

        if (!$nombre) continue;
        // Saltar sin teléfono
        if (!$tel || strtolower($tel) === 'sin teléfono' || strtolower($tel) === 'sin telefono') {
            $skipped_nophone++; continue;
        }
        // Saltar duplicados por teléfono normalizado
        $telNorm = preg_replace('/\D/', '', $tel);
        $dup = $db->prepare("SELECT id FROM prospectos WHERE REPLACE(REPLACE(REPLACE(REPLACE(telefono,'+',''),' ',''),'-',''),'(','') LIKE ?");
        $dup->execute(['%' . substr($telNorm, -8) . '%']);
        if ($dup->fetch()) { $skipped_dup++; continue; }

        $db->prepare("INSERT INTO prospectos (nombre,zona,telefono,direccion,categoria,estado) VALUES (?,?,?,?,'SIN WEB ★★★','nuevo')")
           ->execute([$nombre, $zona, $tel, $dir]);
        $imported++;
    }
    echo json_encode(['ok'=>true,'imported'=>$imported,'skipped_dup'=>$skipped_dup,'skipped_nophone'=>$skipped_nophone,
        'msg'=>"$imported importados, $skipped_dup duplicados saltados, $skipped_nophone sin teléfono saltados"]);
    exit;
}

// ── Listar ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $estado = $_GET['estado'] ?? '';
    $web    = $_GET['web']    ?? '';
    $q      = trim($_GET['q'] ?? '');
    $where  = []; $params = [];
    if ($estado && in_array($estado, $estados)) { $where[] = 'estado=?'; $params[] = $estado; }
    if ($web === 'sin') { $where[] = "(web IS NULL OR web = '')"; }
    if ($web === 'con') { $where[] = "(web IS NOT NULL AND web != '')"; }
    if ($q) { $where[] = '(nombre LIKE ? OR zona LIKE ? OR telefono LIKE ?)'; $params = array_merge($params, ["%$q%","%$q%","%$q%"]); }
    $sql  = "SELECT * FROM prospectos" . ($where ? ' WHERE '.implode(' AND ',$where) : '') . " ORDER BY CASE estado WHEN 'nuevo' THEN 0 WHEN 'contactado' THEN 1 WHEN 'respondio' THEN 2 WHEN 'demo_enviada' THEN 3 WHEN 'ganado' THEN 4 ELSE 5 END, updated_at DESC";
    $stmt = $db->prepare($sql); $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $totals = [];
    foreach ($estados as $e) {
        $s = $db->prepare("SELECT COUNT(*) FROM prospectos WHERE estado=?");
        $s->execute([$e]); $totals[$e] = (int)$s->fetchColumn();
    }
    $totals['total'] = (int)$db->query("SELECT COUNT(*) FROM prospectos")->fetchColumn();

    echo json_encode(['ok'=>true,'data'=>$rows,'totals'=>$totals]);
    exit;
}

// ── Update estado ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'estado') {
    $id     = (int)($input['id'] ?? 0);
    $estado = $input['estado'] ?? '';
    if (!$id || !in_array($estado, $estados)) { echo json_encode(['ok'=>false]); exit; }
    $db->prepare("UPDATE prospectos SET estado=?,updated_at=datetime('now','localtime') WHERE id=?")->execute([$estado,$id]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── Guardar notas ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'notas') {
    $id    = (int)($input['id'] ?? 0);
    $notas = trim($input['notas'] ?? '');
    if (!$id) { echo json_encode(['ok'=>false]); exit; }
    $db->prepare("UPDATE prospectos SET notas=?,updated_at=datetime('now','localtime') WHERE id=?")->execute([$notas,$id]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── Registrar contacto ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'contactar') {
    $id  = (int)($input['id'] ?? 0);
    $dia = (int)($input['dia'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false]); exit; }
    $db->prepare("UPDATE prospectos SET estado='contactado',secuencia_dia=?,ultimo_contacto=datetime('now','localtime'),updated_at=datetime('now','localtime') WHERE id=?")
       ->execute([$dia,$id]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── Eliminar ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id) $db->prepare("DELETE FROM prospectos WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── Agregar manualmente ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'agregar') {
    $nombre   = trim($input['nombre']   ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $zona     = trim($input['zona']     ?? '');
    if (!$nombre || !$telefono) { echo json_encode(['ok'=>false,'msg'=>'Nombre y teléfono requeridos']); exit; }
    $existe = $db->prepare("SELECT id FROM prospectos WHERE telefono=?");
    $existe->execute([$telefono]);
    if ($existe->fetch()) { echo json_encode(['ok'=>false,'msg'=>'Ya existe un prospecto con ese teléfono']); exit; }
    $db->prepare("INSERT INTO prospectos (nombre,zona,telefono,categoria) VALUES (?,?,?,'MANUAL')")
       ->execute([$nombre, $zona, $telefono]);
    echo json_encode(['ok'=>true,'id'=>$db->lastInsertId()]);
    exit;
}

// ── Enviar secuencia ahora (prueba / manual) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enviar_ahora') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }
    $stmt = $db->prepare("SELECT * FROM prospectos WHERE id=?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) { echo json_encode(['ok'=>false,'msg'=>'Prospecto no encontrado']); exit; }

    $token   = getSetting($db, 'wa_token');
    $phoneId = getSetting($db, 'wa_phone_id');
    if (!$token || !$phoneId) { echo json_encode(['ok'=>false,'msg'=>'WA API no configurada']); exit; }

    $hora   = (int)date('H');
    $nombre = $hora < 12 ? 'buenos días' : ($hora < 20 ? 'buenas tardes' : 'buenas noches');
    $tel    = $p['telefono'];

    // Texts for inbox logging
    $bodyApertura = "Hola, {$nombre}! 👋 Soy Juan Pablo, desarrollo webs para estudios contables en Buenos Aires. ¿Tienen sitio web propio actualmente?";
    $bodyDemo     = "Armé algo específico para estudios contables 📊\n\n✅ Web profesional con tu marca\n✅ Panel del cliente: sube documentos y consulta vencimientos\n✅ Panel del contador: gestiona toda la cartera desde un solo lugar\n\nTengo una demo lista. ¿Se las puedo mostrar sin compromiso? 🙌";

    $r1 = sendWATemplate($token, $phoneId, $tel, 'apertura', 'es_AR', [$nombre]);
    if (!$r1['ok']) { echo json_encode(['ok'=>false,'msg'=>'Error apertura: '.($r1['msg']??'')]); exit; }

    // Log apertura to inbox
    $db->prepare("INSERT OR IGNORE INTO wa_messages (lead_id, from_phone, wa_msg_id, direction, body, leido, wa_status, created_at) VALUES (NULL,?,?,'out',?,1,'sent',datetime('now','localtime'))")
       ->execute([$tel, $r1['wa_msg_id']??null, $bodyApertura]);

    // Register prospect name in wa_contacts so inbox shows their name
    $db->prepare("INSERT INTO wa_contacts (phone, wa_name, label, updated_at) VALUES (?,?,'prospecto',datetime('now','localtime'))
                  ON CONFLICT(phone) DO UPDATE SET wa_name=excluded.wa_name, updated_at=excluded.updated_at")
       ->execute([$tel, $p['nombre']]);

    $r2 = sendWATemplate($token, $phoneId, $tel, 'demo_web', 'es_AR', []);
    if ($r2['ok']) {
        $db->prepare("INSERT OR IGNORE INTO wa_messages (lead_id, from_phone, wa_msg_id, direction, body, leido, wa_status, created_at) VALUES (NULL,?,?,'out',?,1,'sent',datetime('now','localtime'))")
           ->execute([$tel, $r2['wa_msg_id']??null, $bodyDemo]);
    }

    $db->prepare("UPDATE prospectos SET estado='contactado', secuencia_dia=0, ultimo_contacto=datetime('now','localtime'), updated_at=datetime('now','localtime') WHERE id=?")
       ->execute([$id]);

    $msg = 'Secuencia enviada' . (!$r2['ok'] ? ' (demo_web: '.($r2['msg']??'error').')' : '');
    echo json_encode(['ok'=>true,'msg'=>$msg]);
    exit;
}

// ── Enviar secuencia a UN prospecto nuevo (llamada desde JS en loop) ──────────
// ── Enviar apertura + demo_web (cierra conexión antes del sleep) ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enviar_apertura') {
    $token   = getSetting($db, 'wa_token');
    $phoneId = getSetting($db, 'wa_phone_id');
    if (!$token || !$phoneId) { echo json_encode(['ok'=>false,'msg'=>'API no configurada']); exit; }

    // Marcar como 'contactado' ANTES de enviar para evitar race condition
    $upd = $db->prepare("UPDATE prospectos SET estado='contactado',secuencia_dia=0,ultimo_contacto=datetime('now','localtime'),updated_at=datetime('now','localtime') WHERE estado='nuevo' AND id=(SELECT id FROM prospectos WHERE estado='nuevo' ORDER BY created_at ASC LIMIT 1)");
    $upd->execute();
    if ($upd->rowCount() === 0) { echo json_encode(['ok'=>false,'msg'=>'no_more']); exit; }

    $p = $db->query("SELECT * FROM prospectos WHERE estado='contactado' ORDER BY updated_at DESC LIMIT 1")->fetch();
    if (!$p) { echo json_encode(['ok'=>false,'msg'=>'no_more']); exit; }

    $hora   = (int)date('H');
    $saludo = $hora < 12 ? 'buenos días' : ($hora < 20 ? 'buenas tardes' : 'buenas noches');
    $tel    = $p['telefono'];
    $telNorm = normalizeWAPhone($tel);
    $bodyApertura = "Hola, {$saludo}! 👋 Soy Juan Pablo, desarrollo webs para estudios contables en Buenos Aires. ¿Tienen sitio web propio actualmente?";
    $bodyDemo     = "Armé algo específico para estudios contables 📊\n\n✅ Web profesional con tu marca\n✅ Panel del cliente: sube documentos y consulta vencimientos\n✅ Panel del contador: gestiona toda la cartera desde un solo lugar\n\nTengo una demo lista. ¿Se las puedo mostrar sin compromiso? 🙌";

    $r1 = sendWATemplate($token, $phoneId, $tel, 'apertura', 'es_AR', [$saludo]);
    if (!$r1['ok']) {
        $db->prepare("UPDATE prospectos SET estado='nuevo',updated_at=datetime('now','localtime') WHERE id=?")->execute([$p['id']]);
        echo json_encode(['ok'=>false,'msg'=>'Error apertura: '.($r1['msg']??'')]); exit;
    }

    $db->prepare("INSERT OR IGNORE INTO wa_messages (lead_id,from_phone,wa_msg_id,direction,body,leido,wa_status,created_at) VALUES (NULL,?,?,'out',?,1,'sent',datetime('now','localtime'))")
       ->execute([$telNorm, $r1['wa_msg_id']??null, $bodyApertura]);
    $db->prepare("INSERT INTO wa_contacts (phone,wa_name,label,updated_at) VALUES (?,?,'prospecto',datetime('now','localtime')) ON CONFLICT(phone) DO UPDATE SET wa_name=excluded.wa_name,updated_at=excluded.updated_at")
       ->execute([$telNorm, $p['nombre']]);

    $remaining = (int)$db->query("SELECT COUNT(*) FROM prospectos WHERE estado='nuevo'")->fetchColumn();

    $r2 = sendWATemplate($token, $phoneId, $tel, 'demo_web', 'es_AR', []);
    if ($r2['ok']) {
        $db->prepare("INSERT OR IGNORE INTO wa_messages (lead_id,from_phone,wa_msg_id,direction,body,leido,wa_status,created_at) VALUES (NULL,?,?,'out',?,1,'sent',datetime('now','localtime'))")
           ->execute([$telNorm, $r2['wa_msg_id']??null, $bodyDemo]);
    } else {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/demo_web_error.log', date('Y-m-d H:i:s') . " demo_web falló para $tel: " . ($r2['msg']??'') . "\n", FILE_APPEND);
    }

    echo json_encode(['ok'=>true,'nombre'=>$p['nombre'],'tel'=>$tel,'remaining'=>$remaining]);
    exit;
}

// ── Paso 2: enviar demo_web a un teléfono ya contactado ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enviar_demo') {
    $token   = getSetting($db, 'wa_token');
    $phoneId = getSetting($db, 'wa_phone_id');
    $tel     = trim($input['tel'] ?? '');
    if (!$token || !$phoneId || !$tel) { echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }

    $telNorm  = normalizeWAPhone($tel);
    $bodyDemo = "Armé algo específico para estudios contables 📊\n\n✅ Web profesional con tu marca\n✅ Panel del cliente: sube documentos y consulta vencimientos\n✅ Panel del contador: gestiona toda la cartera desde un solo lugar\n\nTengo una demo lista. ¿Se las puedo mostrar sin compromiso? 🙌";

    $r2 = sendWATemplate($token, $phoneId, $tel, 'demo_web', 'es_AR', []);
    if ($r2['ok']) {
        $db->prepare("INSERT OR IGNORE INTO wa_messages (lead_id,from_phone,wa_msg_id,direction,body,leido,wa_status,created_at) VALUES (NULL,?,?,'out',?,1,'sent',datetime('now','localtime'))")
           ->execute([$telNorm, $r2['wa_msg_id']??null, $bodyDemo]);
        echo json_encode(['ok'=>true]);
    } else {
        $err = $r2['msg'] ?? 'Error desconocido';
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/demo_web_error.log', date('Y-m-d H:i:s') . " demo_web falló para $tel: $err\n", FILE_APPEND);
        echo json_encode(['ok'=>false,'msg'=>$err]);
    }
    exit;
}

// ── Compatibilidad: enviar_uno = apertura + demo sin sleep (legado) ───────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enviar_uno') {
    $token   = getSetting($db, 'wa_token');
    $phoneId = getSetting($db, 'wa_phone_id');
    if (!$token || !$phoneId) { echo json_encode(['ok'=>false,'msg'=>'API no configurada']); exit; }

    $stmt = $db->prepare("SELECT * FROM prospectos WHERE estado='nuevo' ORDER BY created_at ASC LIMIT 1");
    $stmt->execute();
    $p = $stmt->fetch();
    if (!$p) { echo json_encode(['ok'=>false,'msg'=>'no_more']); exit; }

    $hora   = (int)date('H');
    $saludo = $hora < 12 ? 'buenos días' : ($hora < 20 ? 'buenas tardes' : 'buenas noches');
    $tel    = $p['telefono'];
    $bodyApertura = "Hola, {$saludo}! 👋 Soy Juan Pablo, desarrollo webs para estudios contables en Buenos Aires. ¿Tienen sitio web propio actualmente?";

    $r1 = sendWATemplate($token, $phoneId, $tel, 'apertura', 'es_AR', [$saludo]);
    if (!$r1['ok']) { echo json_encode(['ok'=>false,'msg'=>'Error apertura: '.($r1['msg']??'')]); exit; }

    $db->prepare("INSERT OR IGNORE INTO wa_messages (lead_id,from_phone,wa_msg_id,direction,body,leido,wa_status,created_at) VALUES (NULL,?,?,'out',?,1,'sent',datetime('now','localtime'))")
       ->execute([$tel, $r1['wa_msg_id']??null, $bodyApertura]);
    $db->prepare("INSERT INTO wa_contacts (phone,wa_name,label,updated_at) VALUES (?,?,'prospecto',datetime('now','localtime')) ON CONFLICT(phone) DO UPDATE SET wa_name=excluded.wa_name,updated_at=excluded.updated_at")
       ->execute([$tel, $p['nombre']]);
    $db->prepare("UPDATE prospectos SET estado='contactado',secuencia_dia=0,ultimo_contacto=datetime('now','localtime'),updated_at=datetime('now','localtime') WHERE id=?")
       ->execute([$p['id']]);

    $remaining = (int)$db->query("SELECT COUNT(*) FROM prospectos WHERE estado='nuevo'")->fetchColumn();
    echo json_encode(['ok'=>true,'nombre'=>$p['nombre'],'tel'=>$tel,'remaining'=>$remaining]);
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'Acción no válida']);
