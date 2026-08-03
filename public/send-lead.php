<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/functions.php';
require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

$nombre      = trim($_POST['nombre']      ?? '');
$email       = trim($_POST['email']       ?? '');
$whatsapp    = trim($_POST['whatsapp']    ?? '');
$nicho       = trim($_POST['nicho']       ?? '');
$ciudad      = trim($_POST['ciudad']      ?? '');
$pais        = trim($_POST['pais']        ?? '');
$presupuesto = trim($_POST['presupuesto'] ?? '');
$objetivo    = trim($_POST['objetivo']    ?? '');

if (!$nombre || !$email || !$nicho || !$ciudad || !$pais || !$presupuesto) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'Completá todos los campos obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El email no es válido.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO leads (nombre, email, whatsapp, nicho, ciudad, pais, presupuesto, objetivo)
        VALUES (:nombre, :email, :whatsapp, :nicho, :ciudad, :pais, :presupuesto, :objetivo)
    ");
    $stmt->execute([
        ':nombre'      => sanitize($nombre),
        ':email'       => $email,
        ':whatsapp'    => sanitize($whatsapp),
        ':nicho'       => sanitize($nicho),
        ':ciudad'      => sanitize($ciudad),
        ':pais'        => sanitize($pais),
        ':presupuesto' => sanitize($presupuesto),
        ':objetivo'    => sanitize($objetivo),
    ]);
    echo json_encode(['ok' => true, 'msg' => '¡Recibido! Te contactamos en menos de 24 hs.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar. Intentá de nuevo.']);
}
