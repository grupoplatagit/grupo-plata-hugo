<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db  = getDB();
$ok  = false;
$err = '';

function setSetting(PDO $db, string $key, string $value): void {
    $db->prepare("INSERT INTO settings (key,value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value")
       ->execute([$key, $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    setSetting($db, 'wa_token',        trim($_POST['wa_token']        ?? ''));
    setSetting($db, 'wa_phone_id',     trim($_POST['wa_phone_id']     ?? ''));
    setSetting($db, 'wa_phone_number', trim($_POST['wa_phone_number'] ?? ''));
    setSetting($db, 'wa_waba_id',      trim($_POST['wa_waba_id']      ?? ''));
    setSetting($db, 'wa_auto_enabled', isset($_POST['wa_auto_enabled']) ? '1' : '0');
    setSetting($db, 'wa_auto_minutes', (string)(int)($_POST['wa_auto_minutes'] ?? 10));
    setSetting($db, 'wa_auto_message', trim($_POST['wa_auto_message'] ?? ''));
    setSetting($db, 'wa_bot_enabled',  isset($_POST['wa_bot_enabled'])  ? '1' : '0');
    $ok = true;
}

$waToken       = getSetting($db, 'wa_token');
$waPhoneId     = getSetting($db, 'wa_phone_id');
$waPhoneNumber = getSetting($db, 'wa_phone_number');
$waWabaId      = getSetting($db, 'wa_waba_id');
$waAutoEnabled = getSetting($db, 'wa_auto_enabled') === '1';
$waMinutes     = getSetting($db, 'wa_auto_minutes') ?: '10';
$waMessage     = getSetting($db, 'wa_auto_message');
$waBotEnabled  = getSetting($db, 'wa_bot_enabled')  === '1';
$connected     = !empty($waToken) && !empty($waPhoneId);

$pageTitle = 'WhatsApp API';
include __DIR__ . '/../../views/admin/header.php';
?>

<style>
.wa-status-badge { display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:50px;font-size:.82rem;font-weight:700; }
.wa-connected    { background:rgba(37,211,102,.12);border:1px solid rgba(37,211,102,.3);color:#25d366; }
.wa-disconnected { background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171; }
.wa-hero { background:linear-gradient(135deg,rgba(37,211,102,.08),rgba(37,211,102,.02));border:1px solid rgba(37,211,102,.2);border-radius:14px;padding:28px;margin-bottom:28px;display:flex;align-items:center;gap:20px; }
.wa-hero-icon { font-size:2.8rem;flex-shrink:0; }
.step-list { counter-reset:steps; }
.step-item { counter-increment:steps;display:flex;gap:16px;margin-bottom:20px;align-items:flex-start; }
.step-num  { background:rgba(37,211,102,.15);border:1px solid rgba(37,211,102,.3);color:#25d366;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0;margin-top:2px; }
.step-item p { margin:4px 0 0;font-size:.85rem;color:var(--muted); }
.step-item h4 { font-size:.9rem;font-weight:700;color:var(--text); }
.token-field { font-family:monospace;font-size:.82rem;letter-spacing:.5px; }
.preview-box { background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:16px;font-size:.88rem;color:var(--text);line-height:1.6;margin-top:10px;white-space:pre-wrap; }
</style>

<?php if ($ok): ?>
<div class="alert alert-success" style="margin-bottom:20px">Configuración guardada correctamente.</div>
<?php endif; ?>

<!-- Hero status -->
<div class="wa-hero">
    <div class="wa-hero-icon">&#128383;</div>
    <div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
            <h2 style="font-size:1.1rem;font-weight:800">WhatsApp Business API</h2>
            <span class="wa-status-badge <?= $connected ? 'wa-connected' : 'wa-disconnected' ?>">
                <?= $connected ? '● Conectado' : '○ Sin configurar' ?>
            </span>
        </div>
        <p style="font-size:.88rem;color:var(--muted)">Enviá mensajes automáticos y manuales a tus leads directamente desde el panel.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

    <!-- Configuración -->
    <div class="card">
        <div class="card-header">
            <h2>&#9881; Configuración</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Token de acceso (permanente)</label>
                    <input type="password" name="wa_token" class="token-field"
                        value="<?= htmlspecialchars($waToken) ?>"
                        placeholder="EAAG... (desde Meta Business Manager)">
                    <small style="color:var(--muted);font-size:.75rem;margin-top:6px;display:block">
                        Token permanente del System User de tu BM
                    </small>
                </div>
                <div class="form-group">
                    <label>Phone Number ID</label>
                    <input type="text" name="wa_phone_id" class="token-field"
                        value="<?= htmlspecialchars($waPhoneId) ?>"
                        placeholder="1234567890123456">
                    <small style="color:var(--muted);font-size:.75rem;margin-top:6px;display:block">
                        ID del número en WhatsApp Manager → Números de teléfono
                    </small>
                </div>
                <div class="form-group">
                    <label>Mi número de teléfono WhatsApp</label>
                    <input type="text" name="wa_phone_number"
                        value="<?= htmlspecialchars($waPhoneNumber) ?>"
                        placeholder="+549115551234">
                    <small style="color:var(--muted);font-size:.75rem;margin-top:6px;display:block">
                        Número que aparece en el Inbox (ej: +549115551234)
                    </small>
                </div>
                <div class="form-group">
                    <label>WhatsApp Business Account ID (WABA ID)</label>
                    <input type="text" name="wa_waba_id" class="token-field"
                        value="<?= htmlspecialchars($waWabaId) ?>"
                        placeholder="1531261228693248">
                    <small style="color:var(--muted);font-size:.75rem;margin-top:6px;display:block">
                        Necesario para cargar plantillas aprobadas. Lo ves en Configuración de la API junto al Phone Number ID.
                    </small>
                </div>

                <div style="background:var(--surface2);border-radius:12px;padding:18px;margin-bottom:18px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                        <div>
                            <div style="font-weight:700;font-size:.9rem">Envío automático</div>
                            <div style="font-size:.78rem;color:var(--muted);margin-top:2px">Mensaje automático al llegar un nuevo lead</div>
                        </div>
                        <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer">
                            <input type="checkbox" name="wa_auto_enabled" <?= $waAutoEnabled ? 'checked' : '' ?> style="opacity:0;width:0;height:0">
                            <span style="position:absolute;inset:0;background:<?= $waAutoEnabled ? '#25d366' : 'var(--border)' ?>;border-radius:24px;transition:.3s" id="toggleBg"></span>
                            <span style="position:absolute;left:<?= $waAutoEnabled ? '22px' : '2px' ?>;top:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:.3s" id="toggleDot"></span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>Minutos hasta envío</label>
                        <input type="number" name="wa_auto_minutes" value="<?= htmlspecialchars($waMinutes) ?>" min="1" max="60" style="width:100px">
                    </div>
                </div>

                <!-- Bot toggle -->
                <div style="background:var(--surface2);border-radius:12px;padding:18px;margin-bottom:18px;border:1px solid rgba(37,211,102,.15)">
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="font-weight:700;font-size:.9rem">🤖 Bot de calificación</div>
                            <div style="font-size:.78rem;color:var(--muted);margin-top:2px">Responde automáticamente a mensajes entrantes y califica leads</div>
                        </div>
                        <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer">
                            <input type="checkbox" name="wa_bot_enabled" <?= $waBotEnabled ? 'checked' : '' ?> style="opacity:0;width:0;height:0" id="botToggleInput">
                            <span style="position:absolute;inset:0;background:<?= $waBotEnabled ? '#25d366' : 'var(--border)' ?>;border-radius:24px;transition:.3s" id="botToggleBg"></span>
                            <span style="position:absolute;left:<?= $waBotEnabled ? '22px' : '2px' ?>;top:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:.3s" id="botToggleDot"></span>
                        </label>
                    </div>
                    <div style="margin-top:10px;font-size:.75rem;color:var(--muted);line-height:1.6">
                        Flujo: <strong style="color:var(--text)">Bienvenida → Servicio → Presencia online → Presupuesto → Nombre → Email → Calificación</strong><br>
                        Crea el lead automáticamente como 🔴 frío / 🟡 tibio / 🔥 caliente
                    </div>
                </div>

                <div class="form-group">
                    <label>Mensaje automático</label>
                    <textarea name="wa_auto_message" rows="5" id="msgTemplate"><?= htmlspecialchars($waMessage) ?></textarea>
                    <small style="color:var(--muted);font-size:.75rem;margin-top:4px;display:block">
                        Usá <code style="color:var(--accent)">{nombre}</code>, <code style="color:var(--accent)">{nicho}</code>, <code style="color:var(--accent)">{pais}</code> como variables
                    </small>
                    <div class="preview-box" id="msgPreview"><?= htmlspecialchars($waMessage) ?></div>
                </div>

                <button type="submit" name="save_config" class="btn btn-accent" style="background:#25d366;color:#000">
                    &#128190; Guardar configuración
                </button>
            </form>
        </div>
    </div>

    <!-- Instrucciones -->
    <div>
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h2>&#128196; Cómo conectar tu BM</h2></div>
            <div class="card-body">
                <div class="step-list">
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div>
                            <h4>Accedé a Meta Business Manager</h4>
                            <p>Entrá a <strong>business.facebook.com</strong> con tu cuenta verificada</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div>
                            <h4>Creá un System User permanente</h4>
                            <p>Configuración → Usuarios del sistema → Agregar → Rol: Administrador</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div>
                            <h4>Generá el token permanente</h4>
                            <p>En el System User → Generar token → Seleccioná tu app → Permisos: <code style="color:var(--accent)">whatsapp_business_messaging</code></p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">4</div>
                        <div>
                            <h4>Copiá el Phone Number ID</h4>
                            <p>WhatsApp Manager → Números de teléfono → copiá el ID debajo de tu número</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">5</div>
                        <div>
                            <h4>Pegá ambos datos acá y guardá</h4>
                            <p>El sistema queda conectado y listo para enviar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test de conexión -->
        <?php if ($connected): ?>
        <div class="card">
            <div class="card-header"><h2>&#127381; Test de envío</h2></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Número de prueba (con código de país)</label>
                    <input type="text" id="testPhone" placeholder="+54 9 11 1234-5678">
                </div>
                <button class="btn btn-accent" onclick="testWA()" style="background:#25d366;color:#000">
                    Enviar mensaje de prueba
                </button>
                <div id="testResult" style="margin-top:12px;font-size:.85rem"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
// Toggle visual
document.querySelector('[name=wa_auto_enabled]')?.addEventListener('change', function() {
    document.getElementById('toggleBg').style.background  = this.checked ? '#25d366' : 'var(--border)';
    document.getElementById('toggleDot').style.left       = this.checked ? '22px' : '2px';
});
document.getElementById('botToggleInput')?.addEventListener('change', function() {
    document.getElementById('botToggleBg').style.background  = this.checked ? '#25d366' : 'var(--border)';
    document.getElementById('botToggleDot').style.left       = this.checked ? '22px' : '2px';
});

// Preview del mensaje
document.getElementById('msgTemplate')?.addEventListener('input', function() {
    document.getElementById('msgPreview').textContent = this.value;
});

// Test WA
function testWA() {
    const phone = document.getElementById('testPhone').value.trim();
    if (!phone) return;
    const res = document.getElementById('testResult');
    res.innerHTML = '<span style="color:var(--muted)">Enviando...</span>';
    fetch('<?= ADMIN_URL ?>/api/wa-send.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({phone, message: 'Mensaje de prueba desde JP MARKET Panel ✅'})
    })
    .then(r => r.json())
    .then(d => {
        res.innerHTML = d.ok
            ? '<span style="color:#25d366">✅ ' + d.msg + '</span>'
            : '<span style="color:#f87171">❌ ' + d.msg + '</span>';
    })
    .catch(() => res.innerHTML = '<span style="color:#f87171">❌ Error de conexión</span>');
}
</script>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
