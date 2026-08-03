<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
header('Content-Type: application/json');
requireLogin();

$db    = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($_POST['action'] ?? ($input['action'] ?? ''));

$etapas = ['nuevo','contactado','reunion','propuesta','ganado','perdido'];

// ── GET all ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $rows = $db->query("
        SELECT o.*, l.whatsapp, l.email AS lead_email
        FROM oportunidades o
        LEFT JOIN leads l ON l.id = o.lead_id
        ORDER BY o.updated_at DESC
    ")->fetchAll();

    $grouped = array_fill_keys($etapas, []);
    foreach ($rows as $r) {
        $etapa = in_array($r['etapa'], $etapas) ? $r['etapa'] : 'nuevo';
        $grouped[$etapa][] = $r;
    }

    $totals = [];
    foreach ($grouped as $e => $cards) {
        $totals[$e] = ['count' => count($cards), 'valor' => array_sum(array_column($cards, 'valor'))];
    }

    echo json_encode(['ok' => true, 'data' => $grouped, 'totals' => $totals]);
    exit;
}

// ── GET single with activities + tasks ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'detail') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT o.*, l.whatsapp, l.email AS lead_email FROM oportunidades o LEFT JOIN leads l ON l.id=o.lead_id WHERE o.id=?");
    $stmt->execute([$id]);
    $op = $stmt->fetch();
    if (!$op) { echo json_encode(['ok'=>false,'msg'=>'No encontrado']); exit; }

    $acts  = $db->prepare("SELECT * FROM actividades WHERE oportunidad_id=? ORDER BY created_at DESC");
    $acts->execute([$id]);

    $tasks = $db->prepare("SELECT * FROM tareas WHERE oportunidad_id=? ORDER BY completada ASC, fecha_limite ASC");
    $tasks->execute([$id]);

    echo json_encode(['ok'=>true,'op'=>$op,'actividades'=>$acts->fetchAll(),'tareas'=>$tasks->fetchAll()]);
    exit;
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Create opportunity
    if ($action === 'create') {
        $titulo = trim($input['titulo'] ?? '');
        if (!$titulo) { echo json_encode(['ok'=>false,'msg'=>'Título requerido']); exit; }

        $db->prepare("INSERT INTO oportunidades (titulo,cliente,lead_id,etapa,valor,moneda,probabilidad,notas)
                      VALUES (?,?,?,?,?,?,?,?)")
           ->execute([
               $titulo,
               trim($input['cliente'] ?? ''),
               $input['lead_id'] ?: null,
               in_array($input['etapa'] ?? '', $etapas) ? $input['etapa'] : 'nuevo',
               (float)($input['valor'] ?? 0),
               $input['moneda'] ?? 'USD',
               (int)($input['probabilidad'] ?? 0),
               trim($input['notas'] ?? ''),
           ]);
        echo json_encode(['ok'=>true,'id'=>$db->lastInsertId()]);
        exit;
    }

    // Update opportunity
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }

        $fields = [];
        $params = [];
        $allowed = ['titulo','cliente','etapa','valor','moneda','probabilidad','notas','motivo_perdida'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $input)) {
                $fields[] = "$f=?";
                $params[] = $f === 'etapa' && !in_array($input[$f], $etapas) ? 'nuevo' : $input[$f];
            }
        }
        if ($fields) {
            $params[] = date('Y-m-d H:i:s');
            $params[] = $id;
            $db->prepare("UPDATE oportunidades SET ".implode(',',$fields).",updated_at=? WHERE id=?")->execute($params);
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    // Move stage (drag & drop)
    if ($action === 'move') {
        $id    = (int)($input['id'] ?? 0);
        $etapa = $input['etapa'] ?? '';
        if (!$id || !in_array($etapa, $etapas)) { echo json_encode(['ok'=>false]); exit; }
        $db->prepare("UPDATE oportunidades SET etapa=?,updated_at=datetime('now','localtime') WHERE id=?")->execute([$etapa,$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    // Delete
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $db->prepare("DELETE FROM actividades WHERE oportunidad_id=?")->execute([$id]);
        $db->prepare("DELETE FROM tareas WHERE oportunidad_id=?")->execute([$id]);
        $db->prepare("DELETE FROM oportunidades WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    // Add activity
    if ($action === 'add_actividad') {
        $opId = (int)($input['oportunidad_id'] ?? 0);
        $desc = trim($input['descripcion'] ?? '');
        if (!$opId || !$desc) { echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }
        $tipos = ['llamada','reunion','nota','email','whatsapp'];
        $tipo  = in_array($input['tipo'] ?? '', $tipos) ? $input['tipo'] : 'nota';
        $db->prepare("INSERT INTO actividades (oportunidad_id,tipo,descripcion,fecha) VALUES (?,?,?,?)")
           ->execute([$opId, $tipo, $desc, $input['fecha'] ?? date('Y-m-d H:i:s')]);
        $lastId = $db->lastInsertId();
        $row = $db->prepare("SELECT * FROM actividades WHERE id=?");
        $row->execute([$lastId]);
        echo json_encode(['ok'=>true,'actividad'=>$row->fetch()]);
        exit;
    }

    // Add task
    if ($action === 'add_tarea') {
        $opId   = (int)($input['oportunidad_id'] ?? 0);
        $titulo = trim($input['titulo'] ?? '');
        if (!$opId || !$titulo) { echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }
        $db->prepare("INSERT INTO tareas (oportunidad_id,titulo,fecha_limite) VALUES (?,?,?)")
           ->execute([$opId, $titulo, $input['fecha_limite'] ?? null]);
        $lastId = $db->lastInsertId();
        $row = $db->prepare("SELECT * FROM tareas WHERE id=?");
        $row->execute([$lastId]);
        echo json_encode(['ok'=>true,'tarea'=>$row->fetch()]);
        exit;
    }

    // Toggle task
    if ($action === 'toggle_tarea') {
        $id = (int)($input['id'] ?? 0);
        $db->prepare("UPDATE tareas SET completada = 1 - completada WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }
}

echo json_encode(['ok'=>false,'msg'=>'Acción no válida']);
