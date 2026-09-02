<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db = getDB();
$adminId = $_SESSION['admin_id'];
$ok = false;
$err = '';

// Obtener credenciales del usuario actual
$admin = $db->prepare("SELECT id, nombre, wa_token, wa_phone_id FROM admins WHERE id = ?")->fetchAll(PDO::FETCH_ASSOC);
$admin = $admin[0] ?? null;

// Guardar credenciales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_whatsapp'])) {
    $token = trim($_POST['wa_token'] ?? '');
    $phoneId = trim($_POST['wa_phone_id'] ?? '');

    try {
        $db->prepare("UPDATE admins SET wa_token = ?, wa_phone_id = ? WHERE id = ?")
            ->execute([$token, $phoneId, $adminId]);
        $ok = true;
        $admin['wa_token'] = $token;
        $admin['wa_phone_id'] = $phoneId;
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

$pageTitle = 'Mi WhatsApp API';
include __DIR__ . '/../../views/admin/header.php';
?>

<style>
.wa-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; }
.wa-section { margin-bottom: 28px; }
.wa-section h3 { font-size: .95rem; font-weight: 700; margin-bottom: 16px; color: var(--text); }
.token-input { font-family: monospace; font-size: .85rem; letter-spacing: 0.5px; }
</style>

<div class="wa-card">
    <h2 style="margin-bottom: 6px; font-size: 1.2rem">WhatsApp API - <?= htmlspecialchars($admin['nombre'] ?? '') ?></h2>
    <p style="color: var(--muted); font-size: .9rem; margin-bottom: 24px">Configura aquí tus credenciales personales de WhatsApp Business.</p>

    <?php if ($ok): ?>
    <div class="alert alert-success" style="margin-bottom: 20px">✅ Credenciales guardadas correctamente.</div>
    <?php endif; ?>

    <?php if ($err): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px">❌ Error: <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="POST" style="max-width: 600px">
        <div class="wa-section">
            <h3>🔐 Token de Acceso</h3>
            <div class="form-group">
                <label>Token Permanente (System User)</label>
                <textarea name="wa_token" class="token-input" rows="3" placeholder="EAAG..." style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font-size: 0.85rem; font-family: monospace;"><?= htmlspecialchars($admin['wa_token'] ?? '') ?></textarea>
                <small style="color: var(--muted); display: block; margin-top: 6px">Obtenlo desde Meta Business Manager → System Users</small>
            </div>
        </div>

        <div class="wa-section">
            <h3>📱 Phone Number ID</h3>
            <div class="form-group">
                <label>ID del Número de Teléfono</label>
                <input type="text" name="wa_phone_id" class="token-input" placeholder="1234567890123456" value="<?= htmlspecialchars($admin['wa_phone_id'] ?? '') ?>" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text);">
                <small style="color: var(--muted); display: block; margin-top: 6px">Lo encuentras en WhatsApp Manager → Números de Teléfono</small>
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 28px">
            <button type="submit" name="save_whatsapp" class="btn btn-accent" style="flex: 1">
                💾 Guardar Credenciales
            </button>
            <a href="<?= ADMIN_URL ?>/index.php" class="btn" style="flex: 1; background: var(--surface2); color: var(--text); text-align: center; text-decoration: none; border: 1px solid var(--border)">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
