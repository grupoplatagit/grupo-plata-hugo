<?php
/**
 * Cron: Secuencia automática de prospectos
 * Hostinger: php /home/u.../public_html/app/cron/prospecto-secuencia.php
 * Horario recomendado: diario a las 9:30 hs
 *
 * Qué hace cada día:
 *   1. Marca como 'descartado' los contactados hace 7+ días sin respuesta
 *   2. Envía 'seguimiento' a los contactados hace 3 días sin respuesta
 *   3. Envía 'apertura' a los 5 prospectos más antiguos en estado 'nuevo'
 *      con 3 minutos de pausa entre cada uno
 */
define('RUNNING_AS_CRON', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

$db      = getDB();
$token   = getSetting($db, 'wa_token');
$phoneId = getSetting($db, 'wa_phone_id');

$DELAY_MIN   = 60;  // segundos mínimo entre envíos
$DELAY_MAX   = 180; // segundos máximo entre envíos
$MAX_NUEVOS  = 5;   // prospectos nuevos por día

// ── Logger ────────────────────────────────────────────────────────────────────
function logSeq(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    echo $line;
    $dir = __DIR__ . '/../../logs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . '/prospecto-secuencia.log', $line, FILE_APPEND);
}

if (!$token || !$phoneId) {
    logSeq('ERROR: WA API no configurada. Abortando.');
    exit(1);
}

logSeq('═══ Iniciando secuencia de prospectos ═══');

// ── Paso 1: Descartar sin respuesta después de 7 días ─────────────────────────
$stmt = $db->prepare("
    SELECT id, nombre FROM prospectos
    WHERE estado = 'contactado'
      AND secuencia_dia >= 1
      AND ultimo_contacto <= datetime('now','localtime','-7 days')
");
$stmt->execute();
foreach ($stmt->fetchAll() as $p) {
    $db->prepare("UPDATE prospectos SET estado='descartado', updated_at=datetime('now','localtime') WHERE id=?")
       ->execute([$p['id']]);
    logSeq("Descartado (7 días sin respuesta): {$p['nombre']}");
}

// ── Paso 2: Seguimiento (día 3, solo una vez) ─────────────────────────────────
$stmt = $db->prepare("
    SELECT * FROM prospectos
    WHERE estado = 'contactado'
      AND secuencia_dia = 0
      AND ultimo_contacto <= datetime('now','localtime','-3 days')
");
$stmt->execute();
$seguimientos = $stmt->fetchAll();

if (count($seguimientos)) {
    logSeq('--- Enviando seguimientos ---');
}
foreach ($seguimientos as $i => $p) {
    if ($i > 0) {
        $wait = rand($DELAY_MIN, $DELAY_MAX);
        logSeq("Esperando {$wait}s antes del siguiente...");
        sleep($wait);
    }
    $hora   = (int)date('H');
    $nombre = $hora < 12 ? 'buenos días' : ($hora < 20 ? 'buenas tardes' : 'buenas noches');
    $result = sendWATemplate($token, $phoneId, $p['telefono'], 'seguimiento', 'es_AR', [$nombre]);
    if ($result['ok']) {
        $db->prepare("UPDATE prospectos SET secuencia_dia=1, ultimo_contacto=datetime('now','localtime'), updated_at=datetime('now','localtime') WHERE id=?")
           ->execute([$p['id']]);
        logSeq("Seguimiento enviado: {$p['nombre']} ({$p['telefono']})");
    } else {
        logSeq("Error seguimiento {$p['nombre']}: " . ($result['msg'] ?? 'desconocido'));
    }
}

// ── Paso 3: Apertura a nuevos prospectos (máx. 5/día) ────────────────────────
$stmt = $db->prepare("
    SELECT * FROM prospectos
    WHERE estado = 'nuevo'
    ORDER BY created_at ASC
    LIMIT ?
");
$stmt->execute([$MAX_NUEVOS]);
$nuevos = $stmt->fetchAll();

if (count($nuevos)) {
    logSeq('--- Enviando aperturas ---');
}
$enviados = 0;
foreach ($nuevos as $i => $p) {
    if ($i > 0) {
        $wait = rand($DELAY_MIN, $DELAY_MAX);
        logSeq("Esperando {$wait}s antes del siguiente...");
        sleep($wait);
    }
    $hora   = (int)date('H');
    $nombre = $hora < 12 ? 'buenos días' : ($hora < 20 ? 'buenas tardes' : 'buenas noches');
    $tel    = $p['telefono'];
    $bodyApertura = "Hola, {$nombre}! 👋 Soy Juan Pablo, desarrollo webs para estudios contables en Buenos Aires. ¿Tienen sitio web propio actualmente?";
    $bodyDemo     = "Armé algo específico para estudios contables 📊\n\n✅ Web profesional con tu marca\n✅ Panel del cliente: sube documentos y consulta vencimientos\n✅ Panel del contador: gestiona toda la cartera desde un solo lugar\n\nTengo una demo lista. ¿Se las puedo mostrar sin compromiso? 🙌";

    $telNorm = normalizeWAPhone($tel);
    $r1 = sendWATemplate($token, $phoneId, $tel, 'apertura', 'es_AR', [$nombre]);
    if ($r1['ok']) {
        logSeq("Apertura enviada: {$p['nombre']} ({$telNorm})");

        // Log to inbox
        $db->prepare("INSERT OR IGNORE INTO wa_messages (lead_id,from_phone,wa_msg_id,direction,body,leido,wa_status,created_at) VALUES (NULL,?,?,'out',?,1,'sent',datetime('now','localtime'))")
           ->execute([$telNorm, $r1['wa_msg_id']??null, $bodyApertura]);
        $db->prepare("INSERT INTO wa_contacts (phone,wa_name,label,updated_at) VALUES (?,?,'prospecto',datetime('now','localtime')) ON CONFLICT(phone) DO UPDATE SET wa_name=excluded.wa_name,updated_at=excluded.updated_at")
           ->execute([$telNorm, $p['nombre']]);

        $wait = rand($DELAY_MIN, $DELAY_MAX);
        logSeq("Esperando {$wait}s antes del siguiente...");
        sleep(10);

        $r2 = sendWATemplate($token, $phoneId, $tel, 'demo_web', 'es_AR', []);
        if ($r2['ok']) {
            logSeq("Demo web enviada: {$p['nombre']}");
            $db->prepare("INSERT OR IGNORE INTO wa_messages (lead_id,from_phone,wa_msg_id,direction,body,leido,wa_status,created_at) VALUES (NULL,?,?,'out',?,1,'sent',datetime('now','localtime'))")
               ->execute([$telNorm, $r2['wa_msg_id']??null, $bodyDemo]);
        } else {
            logSeq("Error demo_web {$p['nombre']}: " . ($r2['msg'] ?? 'desconocido'));
        }

        $db->prepare("UPDATE prospectos SET estado='contactado', secuencia_dia=0, ultimo_contacto=datetime('now','localtime'), updated_at=datetime('now','localtime') WHERE id=?")
           ->execute([$p['id']]);
        $enviados++;
    } else {
        logSeq("Error apertura {$p['nombre']}: " . ($r1['msg'] ?? 'desconocido'));
    }
}

logSeq("═══ Secuencia completada — {$enviados} aperturas + " . count($seguimientos) . " seguimientos ═══");
