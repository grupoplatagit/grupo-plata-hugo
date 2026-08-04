<?php
// WhatsApp Webhook - PÚBLICO (sin autenticación)
// Meta enviará parámetros con puntos: hub.mode → PHP los convierte a guiones bajos
// Este archivo NO requiere login

// Cargar credenciales
$env_file = __DIR__ . '/../.env.whatsapp';
if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
    define('WHATSAPP_TOKEN', $env['WHATSAPP_VERIFY_TOKEN'] ?? 'grupo_plata_verify_2024');
    define('WHATSAPP_ACCESS_TOKEN', $env['WHATSAPP_ACCESS_TOKEN'] ?? '');
    define('WHATSAPP_PHONE_NUMBER_ID', $env['WHATSAPP_PHONE_NUMBER_ID'] ?? '');
} else {
    define('WHATSAPP_TOKEN', 'grupo_plata_verify_2024');
    define('WHATSAPP_ACCESS_TOKEN', '');
    define('WHATSAPP_PHONE_NUMBER_ID', '');
}

// Responder a solicitudes de Meta
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Los parámetros con puntos se convierten a guiones bajos en PHP
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    // Log para debug
    $log_dir = __DIR__ . '/../logs';
    @mkdir($log_dir, 0755, true);
    @file_put_contents($log_dir . '/whatsapp-webhook.log',
        date('Y-m-d H:i:s') . " GET\n" .
        "  mode=$mode\n" .
        "  token=$token\n" .
        "  challenge=$challenge\n" .
        "  REQUEST: " . json_encode($_GET) . "\n\n",
        FILE_APPEND
    );

    // Verificar Meta
    if ($mode === 'subscribe' && $token === WHATSAPP_TOKEN && $challenge) {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }

    // Token inválido
    http_response_code(403);
    exit;
}

// POST - Recibir mensajes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_dir = __DIR__ . '/../logs';
    @mkdir($log_dir, 0755, true);

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    @file_put_contents($log_dir . '/whatsapp-webhook.log',
        date('Y-m-d H:i:s') . " POST\n" .
        substr($raw, 0, 500) . "\n\n",
        FILE_APPEND
    );

    // Procesar mensaje
    if ($data && isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Mensajes
                foreach ($value['messages'] ?? [] as $msg) {
                    $from = $msg['from'] ?? '';
                    $body = $msg['text']['body'] ?? '';
                    @file_put_contents($log_dir . '/whatsapp-webhook.log',
                        "Mensaje: $from - $body\n",
                        FILE_APPEND
                    );
                }
            }
        }
    }

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(405);
exit;
?>
