<?php
// Script de prueba - simula un mensaje de Meta

$webhook_url = 'https://grupoplatasf.com/public/api-whatsapp.php';

// Simular un mensaje de WhatsApp
$payload = [
    'object' => 'whatsapp_business_account',
    'entry' => [
        [
            'id' => '123456789',
            'changes' => [
                [
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'messages' => [
                            [
                                'from' => '5491234567890',
                                'id' => 'wamid.test123',
                                'timestamp' => time(),
                                'type' => 'text',
                                'text' => [
                                    'body' => 'Hola! Este es un mensaje de prueba 🎉'
                                ]
                            ]
                        ]
                    ],
                    'field' => 'messages'
                ]
            ]
        ]
    ]
];

echo "=== TEST WHATSAPP WEBHOOK ===\n\n";
echo "URL: $webhook_url\n";
echo "Método: POST\n";
echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Enviar al webhook
$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== RESPUESTA ===\n";
echo "HTTP Code: $http_code\n";
echo "Response: $response\n";
if ($error) {
    echo "Error: $error\n";
}

echo "\n=== VERIFICAR LOG ===\n";
$log_file = __DIR__ . '/logs/whatsapp-webhook.log';
if (file_exists($log_file)) {
    echo "Log encontrado. Últimas 20 líneas:\n";
    $lines = array_slice(file($log_file), -20);
    echo implode('', $lines);
} else {
    echo "Log no encontrado en: $log_file\n";
}
?>
