<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
header('Content-Type: application/json');
requireLogin();

$db     = getDB();
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

// ── Listar ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $rows = $db->query("SELECT * FROM propuestas ORDER BY created_at DESC")->fetchAll();
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

// ── Crear ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear') {
    $empresa = trim($input['cliente_empresa'] ?? '');
    $nombre  = trim($input['cliente_nombre']  ?? '');
    if (!$empresa || !$nombre) { echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }

    // Generar código único: JP-YYMM-XXX
    $base = 'JP-' . date('ym') . '-';
    $last = $db->query("SELECT codigo FROM propuestas WHERE codigo LIKE '{$base}%' ORDER BY codigo DESC LIMIT 1")->fetchColumn();
    $seq  = $last ? ((int)substr($last, -3) + 1) : 1;
    $codigo = $base . str_pad($seq, 3, '0', STR_PAD_LEFT);

    $clave   = strtoupper(trim($input['clave'] ?? ''));
    if (!$clave) {
        $words = ['ESTUDIO','DIGITAL','PREMIA','NEXO','ELITE','SMART','PLUS','VANTA'];
        $clave = $words[array_rand($words)] . rand(10, 99);
    }

    $lead_id = !empty($input['lead_id']) ? (int)$input['lead_id'] : null;
    $notas   = trim($input['notas'] ?? '');

    $db->prepare("INSERT INTO propuestas (codigo,clave,cliente_nombre,cliente_empresa,lead_id,notas) VALUES (?,?,?,?,?,?)")
       ->execute([$codigo, $clave, $nombre, $empresa, $lead_id, $notas]);

    $id = $db->lastInsertId();
    $prop = $db->prepare("SELECT * FROM propuestas WHERE id=?");
    $prop->execute([$id]);
    echo json_encode(['ok'=>true, 'propuesta'=>$prop->fetch()]);
    exit;
}

// ── Actualizar estado ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'estado') {
    $id     = (int)($input['id'] ?? 0);
    $estado = $input['estado'] ?? '';
    $valid  = ['borrador','enviada','vista','aceptada','rechazada'];
    if (!$id || !in_array($estado, $valid)) { echo json_encode(['ok'=>false]); exit; }
    $db->prepare("UPDATE propuestas SET estado=?,updated_at=datetime('now','localtime') WHERE id=?")->execute([$estado,$id]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── Eliminar ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id) $db->prepare("DELETE FROM propuestas WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── Listar leads con teléfono (para selector) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'leads') {
    $rows = $db->query("SELECT id, nombre, email FROM leads ORDER BY nombre ASC")->fetchAll();
    echo json_encode(['ok'=>true, 'leads'=>$rows]);
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'Acción no válida']);
