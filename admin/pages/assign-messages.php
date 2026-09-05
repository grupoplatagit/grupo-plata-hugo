<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db = getDB();
$message = '';

// Obtener admins con phone_number_id
$admins = $db->query("
    SELECT id, nombre, wa_phone_id
    FROM admins
    WHERE activo = 1 AND wa_phone_id IS NOT NULL
    ORDER BY id
")->fetchAll(\PDO::FETCH_ASSOC);

// Obtener estadísticas de mensajes sin asignar por phone_number_id
$unassigned_msgs = $db->query("
    SELECT
        COALESCE(phone_number_id, '') as phone_number_id,
        COUNT(*) as count,
        (SELECT nombre FROM admins WHERE wa_phone_id = phone_number_id LIMIT 1) as admin_name
    FROM wa_messages
    WHERE admin_id IS NULL AND phone_number_id IS NOT NULL
    GROUP BY phone_number_id
    ORDER BY count DESC
")->fetchAll(\PDO::FETCH_ASSOC);

$total_unassigned = 0;
foreach ($unassigned_msgs as $msg) {
    $total_unassigned += $msg['count'];
}

// Procesar auto-asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign'])) {
    $assigned_count = 0;

    foreach ($admins as $admin) {
        if (!empty($admin['wa_phone_id'])) {
            $stmt = $db->prepare("
                UPDATE wa_messages
                SET admin_id = ?
                WHERE admin_id IS NULL AND phone_number_id = ?
            ");
            $stmt->execute([$admin['id'], $admin['wa_phone_id']]);
            $assigned_count += $stmt->rowCount();
        }
    }

    if ($assigned_count > 0) {
        $message = "✅ Auto-asignados $assigned_count mensajes correctamente";
        $total_unassigned = 0;
        $unassigned_msgs = [];
    } else {
        $message = "⚠️ No hay mensajes para asignar";
    }
}

$pageTitle = 'Asignar Mensajes';
include __DIR__ . '/../../views/admin/header.php';
?>

<style>
.assign-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 28px;
    max-width: 700px;
}

.stat-box {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    margin-bottom: 24px;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    color: #25d366;
    margin-bottom: 8px;
}

.stat-label {
    color: var(--muted);
    font-size: 0.9rem;
}

.msg-group {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.msg-info {
    flex: 1;
}

.msg-phone {
    font-family: monospace;
    font-weight: 700;
    color: #25d366;
    margin-bottom: 4px;
}

.msg-count {
    font-size: 0.85rem;
    color: var(--muted);
}

.msg-admin {
    background: var(--surface);
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    white-space: nowrap;
}

.btn-assign {
    width: 100%;
    background: #25d366;
    color: #000;
    border: none;
    padding: 14px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 20px;
    font-size: 1rem;
    transition: all 0.2s;
}

.btn-assign:hover {
    background: #22c55e;
    box-shadow: 0 0 16px rgba(37, 211, 102, 0.3);
}

.btn-assign:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.alert {
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.25);
}

.alert-warning {
    background: rgba(251, 146, 60, 0.1);
    color: #fbbf24;
    border: 1px solid rgba(251, 146, 60, 0.25);
}
</style>

<div class="assign-card">
    <h2 style="margin-bottom: 24px; font-size: 1.3rem; font-weight: 800">
        🤖 Auto-Asignar Mensajes
    </h2>

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-warning' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($total_unassigned > 0): ?>
        <div class="stat-box">
            <div class="stat-number"><?= $total_unassigned ?></div>
            <div class="stat-label">Mensajes sin asignar</div>
        </div>

        <p style="color: var(--muted); margin-bottom: 20px; font-size: 0.9rem;">
            Se asignarán automáticamente según el phone_number_id configurado de cada admin:
        </p>

        <div style="margin-bottom: 20px;">
            <?php foreach ($unassigned_msgs as $msg):
                $assigned_admin = null;
                foreach ($admins as $admin) {
                    if ($admin['wa_phone_id'] === $msg['phone_number_id']) {
                        $assigned_admin = $admin;
                        break;
                    }
                }
            ?>
                <div class="msg-group">
                    <div class="msg-info">
                        <div class="msg-phone"><?= htmlspecialchars($msg['phone_number_id']) ?></div>
                        <div class="msg-count"><?= $msg['count'] ?> mensajes</div>
                    </div>
                    <div class="msg-admin">
                        <?= $assigned_admin ? htmlspecialchars($assigned_admin['nombre']) : '⚠️ Sin mapeo' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="auto_assign" value="1">
            <button type="submit" class="btn-assign">
                ✅ Auto-Asignar <?= $total_unassigned ?> Mensajes
            </button>
        </form>
    <?php else: ?>
        <div class="stat-box">
            <div style="font-size: 2rem; margin-bottom: 12px;">✅</div>
            <div class="stat-label">Todos los mensajes están asignados</div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
