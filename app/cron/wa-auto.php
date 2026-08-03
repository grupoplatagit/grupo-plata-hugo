<?php
/**
 * Cron: WhatsApp auto-send
 * Hostinger cron command: php /home/u.../public_html/app/cron/wa-auto.php
 * Recommended interval: every 2 minutes
 */
define('RUNNING_AS_CRON', true);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

$db = getDB();

$token   = getSetting($db, 'wa_token');
$phoneId = getSetting($db, 'wa_phone_id');
$enabled = getSetting($db, 'wa_auto_enabled') === '1';
$minutes = (int)(getSetting($db, 'wa_auto_minutes') ?: 10);
$tpl     = getSetting($db, 'wa_auto_message');

if (!$enabled || empty($token) || empty($phoneId)) {
    echo "[" . date('Y-m-d H:i:s') . "] Auto-send skipped (disabled or not configured)\n";
    exit;
}

$stmt = $db->prepare("
    SELECT * FROM leads
    WHERE wa_status = 'pending'
      AND whatsapp IS NOT NULL AND whatsapp != ''
      AND created_at <= datetime('now', 'localtime', ? || ' minutes')
");
$stmt->execute(['-' . $minutes]);
$leads = $stmt->fetchAll();

foreach ($leads as $lead) {
    $msg = str_replace(
        ['{nombre}', '{nicho}', '{pais}'],
        [
            $lead['nombre'],
            $lead['nicho']  ?: 'tu negocio',
            $lead['pais']   ?: 'tu país',
        ],
        $tpl
    );
    $result = sendWAMessage($token, $phoneId, $lead['whatsapp'], $msg);
    $status = $result['ok'] ? 'sent' : 'failed';
    $db->prepare("UPDATE leads SET wa_status=?, wa_sent_at=datetime('now','localtime') WHERE id=?")
       ->execute([$status, $lead['id']]);
    $errDetail = $result['ok'] ? '' : ' — ' . ($result['msg'] ?? 'unknown');
    echo "[" . date('Y-m-d H:i:s') . "] Lead #{$lead['id']} ({$lead['nombre']}): $status$errDetail\n";
}

if (empty($leads)) {
    echo "[" . date('Y-m-d H:i:s') . "] No pending leads to send.\n";
}
