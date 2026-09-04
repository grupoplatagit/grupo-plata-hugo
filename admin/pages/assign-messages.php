<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db = getDB();
$message = '';
$unassigned_count = 0;

// Obtener admins con phone_number_id
$admins = $db->query("
    SELECT id, nombre, wa_phone_id
    FROM admins
    WHERE activo = 1 AND wa_phone_id IS NOT NULL
    ORDER BY id
")->fetchAll(\PDO::FETCH_ASSOC);

// Contar mensajes sin asignar
$unassigned_count = $db->query("SELECT COUNT(*) FROM wa_messages WHERE admin_id IS NULL")->fetchColumn();

// Procesar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id']) && isset($_POST['confirm'])) {
    $admin_id = (int)$_POST['admin_id'];

    // Validar que el admin existe
    $stmt = $db->prepare("SELECT id, nombre FROM admins WHERE id = ? AND activo = 1");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();

    if ($admin) {
        $update = $db->prepare("UPDATE wa_messages SET admin_id = ? WHERE admin_id IS NULL");
        $update->execute([$admin_id]);

        $message = "✅ Asignados " . $unassigned_count . " mensajes a " . $admin['nombre'];
        $unassigned_count = 0;
    } else {
        $message = "❌ Admin no válido";
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
    max-width: 600px;
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

.admin-option {
    background: var(--bg);
    border: 2px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.admin-option:hover {
    border-color: #25d366;
    background: rgba(37, 211, 102, 0.05);
}

.admin-option input[type="radio"] {
    margin-right: 12px;
}

.admin-option label {
    cursor: pointer;
    display: flex;
    align-items: center;
    margin: 0;
}

.btn-assign {
    width: 100%;
    background: #25d366;
    color: #000;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 20px;
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

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.25);
}
</style>

<div class="assign-card">
    <h2 style="margin-bottom: 24px; font-size: 1.3rem; font-weight: 800">
        📋 Asignar Mensajes Históricos
    </h2>

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($unassigned_count > 0): ?>
        <div class="stat-box">
            <div class="stat-number"><?= $unassigned_count ?></div>
            <div class="stat-label">Mensajes sin asignar</div>
        </div>

        <form method="POST">
            <input type="hidden" name="confirm" value="1">

            <p style="color: var(--muted); margin-bottom: 16px; font-size: 0.9rem;">
                Selecciona a quién pertenecen estos mensajes:
            </p>

            <div>
                <?php foreach ($admins as $admin): ?>
                    <div class="admin-option">
                        <label>
                            <input type="radio" name="admin_id" value="<?= $admin['id'] ?>" required>
                            <strong><?= htmlspecialchars($admin['nombre']) ?></strong>
                            <span style="color: var(--muted); font-size: 0.85rem;">
                                (<?= htmlspecialchars($admin['wa_phone_id']) ?>)
                            </span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn-assign">
                ✅ Asignar <?= $unassigned_count ?> Mensajes
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
