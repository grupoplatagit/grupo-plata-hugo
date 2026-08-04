<?php
/**
 * Endpoint para archivar/desarchivar conversaciones
 */

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';

requireLogin();

header('Content-Type: application/json');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'POST required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$from_phone = $input['from_phone'] ?? '';
$action = $input['action'] ?? ''; // 'archive' o 'unarchive'

if (!$from_phone || !in_array($action, ['archive', 'unarchive'])) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid parameters']);
    exit;
}

$is_archived = $action === 'archive' ? 1 : 0;

try {
    $db->prepare("
        UPDATE wa_conversations
        SET is_archived = ?, updated_at = datetime('now', 'localtime')
        WHERE from_phone = ?
    ")->execute([$is_archived, $from_phone]);

    echo json_encode([
        'ok' => true,
        'msg' => $action === 'archive' ? 'Conversación archivada' : 'Conversación desarchivada',
        'is_archived' => $is_archived
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
