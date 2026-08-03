<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db     = getDB();
$errors = [];
$ok     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfInput = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfInput)) {
        $errors[] = 'Solicitud inválida. Recargá la página.';
    } else {
        $actual  = $_POST['actual']   ?? '';
        $nueva   = $_POST['nueva']    ?? '';
        $repetir = $_POST['repetir']  ?? '';

        $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($actual, $admin['password'])) {
            $errors[] = 'La contraseña actual es incorrecta.';
        } elseif (strlen($nueva) < 8) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif ($nueva !== $repetir) {
            $errors[] = 'Las contraseñas nuevas no coinciden.';
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $db->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hash, $admin['id']]);
            $ok = true;
        }
    }
}

$pageTitle = 'Cambiar contraseña';
include __DIR__ . '/../../views/admin/header.php';
?>

<div class="card" style="max-width:480px">
    <div class="card-header">
        <h2>&#128274; Cambiar contraseña</h2>
    </div>
    <div class="card-body">
        <?php if ($ok): ?>
            <div class="alert alert-success" style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#86efac;padding:14px 18px;border-radius:10px;margin-bottom:20px">
                Contraseña actualizada correctamente.
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label>Contraseña actual</label>
                <input type="password" name="actual" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label>Nueva contraseña</label>
                <input type="password" name="nueva" required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Repetir nueva contraseña</label>
                <input type="password" name="repetir" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-accent">Actualizar contraseña</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
