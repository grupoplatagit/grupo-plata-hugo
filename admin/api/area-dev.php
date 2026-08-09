<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/db.php';

requireLogin();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$db = getDB();

// ── Get all user WhatsApp configs ──────────────────────────────────
if ($action === 'get_all') {
    $rows = $db->query("
        SELECT id, admin_email, waba_id, phone_number_id, updated_at
        FROM user_whatsapp_config
        ORDER BY admin_email ASC
    ")->fetchAll();

    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

// ── Get single user config ────────────────────────────────────────
if ($action === 'get' && !empty($_GET['email'])) {
    $email = $_GET['email'];
    $row = $db->prepare("SELECT * FROM user_whatsapp_config WHERE admin_email = ?")
        ->execute([$email])
        ->fetch();

    echo json_encode(['ok' => true, 'data' => $row ?: null]);
    exit;
}

// ── Update user config (POST) ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = $input['admin_email'] ?? '';
    $waba_id = trim($input['waba_id'] ?? '');
    $phone_number_id = trim($input['phone_number_id'] ?? '');

    if (!$email) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'admin_email requerido']);
        exit;
    }

    try {
        // Check if exists
        $exists = $db->prepare("SELECT id FROM user_whatsapp_config WHERE admin_email = ?")
            ->execute([$email])
            ->fetch();

        if ($exists) {
            // Update
            $db->prepare("
                UPDATE user_whatsapp_config
                SET waba_id = ?, phone_number_id = ?, updated_at = datetime('now','localtime')
                WHERE admin_email = ?
            ")->execute([$waba_id ?: null, $phone_number_id ?: null, $email]);
        } else {
            // Insert
            $db->prepare("
                INSERT INTO user_whatsapp_config (admin_email, waba_id, phone_number_id)
                VALUES (?, ?, ?)
            ")->execute([$email, $waba_id ?: null, $phone_number_id ?: null]);
        }

        echo json_encode([
            'ok' => true,
            'msg' => 'Configuración guardada',
            'data' => [
                'admin_email' => $email,
                'waba_id' => $waba_id ?: null,
                'phone_number_id' => $phone_number_id ?: null
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
?>
