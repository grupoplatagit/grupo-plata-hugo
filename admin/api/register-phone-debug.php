<?php
// Debug: verificar qué está fallando
header('Content-Type: application/json');

try {
    // Cargar config
    require_once __DIR__ . '/../../app/config.php';
    require_once __DIR__ . '/../../app/auth.php';
    require_once __DIR__ . '/../../app/db.php';

    echo json_encode(['step' => '1_loaded_files', 'ok' => true]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}
?>
