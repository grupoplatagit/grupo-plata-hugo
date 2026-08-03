<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db = getDB();

// Marcar como leído
if (isset($_GET['leer']) && is_numeric($_GET['leer'])) {
    $db->prepare('UPDATE leads SET leido = 1 WHERE id = ?')->execute([$_GET['leer']]);
    redirect(ADMIN_URL . '/pages/leads.php');
}

// Eliminar
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $delId = (int)$_GET['eliminar'];
    $db->prepare('UPDATE wa_messages SET lead_id = NULL WHERE lead_id = ?')->execute([$delId]);
    $db->prepare('DELETE FROM leads WHERE id = ?')->execute([$delId]);
    redirect(ADMIN_URL . '/pages/leads.php');
}

// Filtros
$filtro      = trim($_GET['q'] ?? '');
$presupuesto = trim($_GET['presupuesto'] ?? '');
$soloNuevos  = isset($_GET['nuevos']);

$where  = [];
$params = [];

if ($filtro) {
    $where[]  = '(nombre LIKE ? OR email LIKE ? OR nicho LIKE ? OR pais LIKE ?)';
    $params   = array_merge($params, ["%$filtro%", "%$filtro%", "%$filtro%", "%$filtro%"]);
}
if ($presupuesto) {
    $where[]  = 'presupuesto = ?';
    $params[] = $presupuesto;
}
if ($soloNuevos) {
    $where[] = 'leido = 0';
}

$sql  = 'SELECT * FROM leads' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

$total  = count($leads);
$nuevos = $db->query('SELECT COUNT(*) FROM leads WHERE leido = 0')->fetchColumn();

// WA settings for JS
$waMinutes = (int)($db->query("SELECT value FROM settings WHERE key='wa_auto_minutes'")->fetchColumn() ?: 10);
$waEnabled = $db->query("SELECT value FROM settings WHERE key='wa_auto_enabled'")->fetchColumn() === '1';
$waReady   = !empty($db->query("SELECT value FROM settings WHERE key='wa_token'")->fetchColumn())
          && !empty($db->query("SELECT value FROM settings WHERE key='wa_phone_id'")->fetchColumn());

$pageTitle = 'Leads';
include __DIR__ . '/../../views/admin/header.php';
?>

<style>
.wa-badge { display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:700; }
.wa-pending  { background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.25);color:#fbbf24; }
.wa-sent     { background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.25);color:#25d366; }
.wa-failed   { background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171; }
.wa-nophone  { background:rgba(107,107,107,.1);border:1px solid rgba(107,107,107,.2);color:#6b6b6b; }
.countdown   { font-size:.78rem;font-family:monospace;font-weight:700; }
.countdown.urgent { color:#f87171; }
.countdown.ready  { color:#fbbf24; }
.countdown.done   { color:#25d366; }
.btn-wa { background:rgba(37,211,102,.12);color:#25d366;border:1px solid rgba(37,211,102,.3); }
.btn-wa:hover { background:rgba(37,211,102,.25); }
.btn-wa:disabled { opacity:.4;cursor:not-allowed; }
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div>
        <h1 style="font-size:1.4rem;font-weight:800">Leads potenciales</h1>
        <p style="color:var(--muted);font-size:.9rem;margin-top:4px"><?= $total ?> resultado(s)<?php if($nuevos): ?> — <span style="color:#06b6d4;font-weight:600"><?= $nuevos ?> sin leer</span><?php endif; ?></p>
    </div>
    <?php if (!$waReady): ?>
    <a href="<?= ADMIN_URL ?>/pages/whatsapp.php" class="btn" style="background:rgba(37,211,102,.1);color:#25d366;border:1px solid rgba(37,211,102,.25);font-size:.82rem">
        &#128383; Configurar WhatsApp API
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px">
    <input type="text" name="q" value="<?= htmlspecialchars($filtro) ?>"
        placeholder="Buscar por nombre, email, nicho, país..."
        style="flex:1;min-width:220px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--text);font-size:.9rem;">

    <select name="presupuesto" style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--text);font-size:.9rem;">
        <option value="">Todos los presupuestos</option>
        <option value="$500 - $1,000 USD"   <?= $presupuesto === '$500 - $1,000 USD'   ? 'selected':'' ?>>$500 – $1K</option>
        <option value="$1,000 - $3,000 USD" <?= $presupuesto === '$1,000 - $3,000 USD' ? 'selected':'' ?>>$1K – $3K</option>
        <option value="$3,000 - $5,000 USD" <?= $presupuesto === '$3,000 - $5,000 USD' ? 'selected':'' ?>>$3K – $5K</option>
        <option value="$5,000+ USD"         <?= $presupuesto === '$5,000+ USD'         ? 'selected':'' ?>>$5K+</option>
    </select>

    <label style="display:flex;align-items:center;gap:8px;color:var(--muted);font-size:.9rem;cursor:pointer">
        <input type="checkbox" name="nuevos" <?= $soloNuevos ? 'checked' : '' ?>> Solo sin leer
    </label>

    <button type="submit" class="btn btn-accent">Filtrar</button>
    <a href="<?= ADMIN_URL ?>/pages/leads.php" class="btn" style="background:var(--surface2);color:var(--text)">Limpiar</a>
</form>

<!-- Tabla -->
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Nombre</th>
                    <th>Email / WhatsApp</th>
                    <th>Nicho</th>
                    <th>Ciudad / País</th>
                    <th>Presupuesto</th>
                    <th>Objetivo</th>
                    <th>Fecha</th>
                    <th style="color:#25d366">&#128383; WhatsApp</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($leads)): ?>
                <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:48px">Sin leads que coincidan.</td></tr>
            <?php else: foreach ($leads as $l):
                $createdTs  = (new DateTimeImmutable($l['created_at'], new DateTimeZone('UTC')))->getTimestamp();
                $sendAt     = $createdTs + ($waMinutes * 60);
                $nowTs      = time();
                $secsLeft   = $sendAt - $nowTs;
                $hasPhone   = !empty($l['whatsapp']);
                $waStatus   = $l['wa_status'] ?? 'pending';
            ?>
                <tr style="<?= !$l['leido'] ? 'background:rgba(6,182,212,0.04)' : '' ?>">
                    <td>
                        <?php if (!$l['leido']): ?>
                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#06b6d4"></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:<?= !$l['leido'] ? '700' : '400' ?>">
                        <?= htmlspecialchars($l['nombre']) ?>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($l['email']) ?></div>
                        <?php if ($l['whatsapp']): ?>
                            <div style="font-size:.8rem;color:#25d366"><?= htmlspecialchars($l['whatsapp']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($l['nicho']) ?></td>
                    <td>
                        <div><?= htmlspecialchars($l['ciudad']) ?></div>
                        <div style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($l['pais']) ?></div>
                    </td>
                    <td><span style="color:#06b6d4;font-weight:600;font-size:.85rem"><?= htmlspecialchars($l['presupuesto']) ?></span></td>
                    <td style="font-size:.85rem;color:var(--muted)"><?= htmlspecialchars($l['objetivo'] ?: '—') ?></td>
                    <td style="font-size:.82rem;color:var(--muted)"><?= formatDate($l['created_at']) ?></td>

                    <!-- WhatsApp column -->
                    <td>
                        <?php if (!$hasPhone): ?>
                            <span class="wa-badge wa-nophone">Sin número</span>
                        <?php elseif ($waStatus === 'sent'): ?>
                            <span class="wa-badge wa-sent">✓ Enviado</span>
                            <?php if ($l['wa_sent_at']): ?>
                                <div style="font-size:.72rem;color:var(--muted);margin-top:3px"><?= htmlspecialchars($l['wa_sent_at']) ?></div>
                            <?php endif; ?>
                        <?php elseif ($waStatus === 'failed'): ?>
                            <span class="wa-badge wa-failed">✗ Falló</span>
                            <button class="btn btn-sm btn-wa" style="margin-top:5px;display:block"
                                onclick="sendWA(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['whatsapp'])) ?>', this)">
                                ↺ Reintentar
                            </button>
                        <?php else: ?>
                            <?php if ($waEnabled && $secsLeft > 0): ?>
                                <span class="wa-badge wa-pending">
                                    &#9201;
                                    <span class="countdown"
                                          data-send-at="<?= $sendAt ?>"
                                          id="cd-<?= $l['id'] ?>">
                                        <?= gmdate('i:s', max(0, $secsLeft)) ?>
                                    </span>
                                </span>
                                <div style="font-size:.7rem;color:var(--muted);margin-top:3px">Auto-envío</div>
                            <?php elseif ($waEnabled && $secsLeft <= 0): ?>
                                <span class="wa-badge wa-pending" style="color:#fbbf24">Enviando...</span>
                            <?php else: ?>
                                <span class="wa-badge wa-pending">Pendiente</span>
                            <?php endif; ?>
                            <?php if ($waReady): ?>
                                <button class="btn btn-sm btn-wa" style="margin-top:5px;display:block"
                                    onclick="sendWA(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['whatsapp'])) ?>', this)">
                                    &#128383; Enviar ya
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <?php if (!$l['leido']): ?>
                                <a href="?leer=<?= $l['id'] ?>" class="btn btn-sm" style="background:rgba(6,182,212,0.1);color:#06b6d4;border:1px solid rgba(6,182,212,0.3)">&#10003; Leído</a>
                            <?php endif; ?>
                            <a href="mailto:<?= htmlspecialchars($l['email']) ?>" class="btn btn-sm btn-accent">Contactar</a>
                            <?php if (!empty($l['whatsapp'])): ?>
                            <a href="<?= ADMIN_URL ?>/pages/inbox.php?open_lead=<?= $l['id'] ?>" class="btn btn-sm btn-wa" title="Abrir chat WhatsApp">&#128383;</a>
                            <?php endif; ?>
                            <a href="?eliminar=<?= $l['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar este lead?')">&#128465;</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// ── Countdown timers ──────────────────────────────────────────────────────────
(function() {
    const els = document.querySelectorAll('.countdown[data-send-at]');
    if (!els.length) return;

    function tick() {
        const now = Math.floor(Date.now() / 1000);
        els.forEach(el => {
            const sendAt = parseInt(el.dataset.sendAt, 10);
            const left   = sendAt - now;
            if (left <= 0) {
                el.closest('.wa-badge').innerHTML = '&#9201; 00:00';
                el.closest('.wa-badge').classList.add('ready');
                return;
            }
            const mins = Math.floor(left / 60);
            const secs = left % 60;
            el.textContent = String(mins).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
            el.className = 'countdown' + (left < 60 ? ' urgent' : '');
        });
    }
    tick();
    setInterval(tick, 1000);
})();

// ── Manual WA send ────────────────────────────────────────────────────────────
function sendWA(leadId, phone, btn) {
    if (!confirm('¿Enviar mensaje de WhatsApp a este lead ahora?')) return;
    btn.disabled = true;
    btn.textContent = 'Enviando...';

    fetch('<?= ADMIN_URL ?>/api/wa-send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ lead_id: leadId, phone: phone, message: '<?= addslashes(getSetting($db, 'wa_auto_message')) ?>' })
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            const cell = btn.closest('td');
            cell.innerHTML = '<span class="wa-badge wa-sent">✓ Enviado</span>';
        } else {
            btn.disabled = false;
            btn.textContent = '↺ Reintentar';
            alert('Error: ' + d.msg);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = '↺ Reintentar';
        alert('Error de conexión');
    });
}
</script>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
