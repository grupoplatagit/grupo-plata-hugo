<?php
/**
 * FASE 1 - Test: Status Updates
 * Simula webhooks de status desde Meta
 */

require_once __DIR__ . '/app/db.php';

$db = getDB();

echo "=== FASE 1: TEST DE STATUS UPDATES ===\n\n";

// 1. Obtener un mensaje enviado reciente
$msg = $db->query("
    SELECT * FROM wa_messages
    WHERE direction = 'out'
    AND wa_msg_id IS NOT NULL
    ORDER BY created_at DESC
    LIMIT 1
")->fetch();

if (!$msg) {
    echo "❌ No hay mensajes salientes para probar\n";
    echo "   Primero envía un mensaje desde el Inbox.\n";
    exit;
}

echo "✅ Mensaje encontrado:\n";
echo "   ID: {$msg['id']}\n";
echo "   WAMID: {$msg['wa_msg_id']}\n";
echo "   Estado actual: {$msg['wa_status']}\n";
echo "   Body: " . substr($msg['body'], 0, 50) . "...\n\n";

// 2. Simular webhook de "delivered"
echo "--- Simulando status DELIVERED ---\n";
$db->prepare("UPDATE wa_messages SET wa_status = ? WHERE wa_msg_id = ?")
   ->execute(['delivered', $msg['wa_msg_id']]);
$updated = $db->query("SELECT wa_status FROM wa_messages WHERE id = ?")->fetch();
echo "✓ Actualizado a: {$updated['wa_status']}\n\n";

// 3. Simular webhook de "read"
echo "--- Simulando status READ ---\n";
$db->prepare("UPDATE wa_messages SET wa_status = ? WHERE wa_msg_id = ?")
   ->execute(['read', $msg['wa_msg_id']]);
$updated = $db->query("SELECT wa_status FROM wa_messages WHERE id = ?")->fetch();
echo "✓ Actualizado a: {$updated['wa_status']}\n\n";

// 4. Simular webhook de "failed"
echo "--- Simulando status FAILED ---\n";
$db->prepare("UPDATE wa_messages SET wa_status = ? WHERE wa_msg_id = ?")
   ->execute(['failed', $msg['wa_msg_id']]);
$updated = $db->query("SELECT wa_status FROM wa_messages WHERE id = ?")->fetch();
echo "✓ Actualizado a: {$updated['wa_status']}\n\n";

// 5. Volver a "sent"
echo "--- Restaurando a SENT ---\n";
$db->prepare("UPDATE wa_messages SET wa_status = ? WHERE wa_msg_id = ?")
   ->execute(['sent', $msg['wa_msg_id']]);
$updated = $db->query("SELECT wa_status FROM wa_messages WHERE id = ?")->fetch();
echo "✓ Actualizado a: {$updated['wa_status']}\n\n";

echo "✅ TEST COMPLETADO\n";
echo "\n📝 PRÓXIMOS PASOS:\n";
echo "1. Enviá un mensaje real desde el Inbox\n";
echo "2. Verificá que el webhook recibe status updates de Meta\n";
echo "3. Revisá /logs/whatsapp-webhook.log para ver los cambios\n";
echo "4. El Inbox debe mostrar ✓, ✓✓, ✓✓ (azul) automáticamente\n";
echo "\n🔗 Ver: http://localhost:8082/admin/pages/inbox.php\n";
?>
