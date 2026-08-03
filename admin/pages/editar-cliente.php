<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM clientes WHERE id = ?');
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    flashSet('error', 'Cliente no encontrado.');
    redirect(ADMIN_URL . '/pages/clientes.php');
}

$errors    = [];
$servicios = ['Marketing Digital','Gestión de Redes Sociales','Consultoría','Email Marketing','Automatización','Soporte'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $empresa  = trim($_POST['empresa']  ?? '');
    $email    = trim($_POST['email']    ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $ciudad   = trim($_POST['ciudad']   ?? '');
    $servicio = trim($_POST['servicio'] ?? '');
    $monto    = is_numeric($_POST['monto'] ?? '') ? (float)$_POST['monto'] : 0;
    $notas    = trim($_POST['notas']    ?? '');
    $activo   = isset($_POST['activo']) ? 1 : 0;

    if (empty($nombre)) $errors[] = 'El nombre es obligatorio.';

    if (empty($errors)) {
        $db->prepare(
            "UPDATE clientes SET nombre=?,empresa=?,email=?,telefono=?,ciudad=?,servicio=?,monto=?,notas=?,activo=?,
             updated_at=datetime('now','localtime') WHERE id=?"
        )->execute([$nombre,$empresa,$email,$telefono,$ciudad,$servicio,$monto,$notas,$activo,$id]);
        flashSet('success', 'Cliente actualizado.');
        redirect(ADMIN_URL . '/pages/clientes.php');
    }
    $cliente = array_merge($cliente, compact('nombre','empresa','email','telefono','ciudad','servicio','notas','activo'));
}

$pageTitle = 'Editar cliente';
include __DIR__ . '/../../views/admin/header.php';
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card" style="max-width:680px">
    <div class="card-header">
        <h2>Editar: <?= htmlspecialchars($cliente['nombre']) ?></h2>
        <a href="<?= ADMIN_URL ?>/pages/clientes.php" class="btn btn-sm" style="background:var(--surface2)">&#8592; Volver</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Empresa</label>
                    <input type="text" name="empresa" value="<?= htmlspecialchars($cliente['empresa'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Teléfono / WhatsApp</label>
                    <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" name="ciudad" value="<?= htmlspecialchars($cliente['ciudad'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Monto mensual ($)</label>
                    <input type="number" name="monto" value="<?= htmlspecialchars($cliente['monto'] ?? 0) ?>" placeholder="Ej: 400000" min="0" step="1000">
                </div>
                <div class="form-group">
                    <label>Servicio</label>
                    <select name="servicio">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($servicios as $s): ?>
                            <option value="<?= $s ?>" <?= ($cliente['servicio']??'')===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Notas</label>
                <textarea name="notas" rows="3"><?= htmlspecialchars($cliente['notas'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:10px">
                <input type="checkbox" id="activo" name="activo" <?= $cliente['activo'] ? 'checked' : '' ?>>
                <label for="activo" style="text-transform:none;letter-spacing:0;font-size:.9rem">Cliente activo</label>
            </div>
            <button type="submit" class="btn btn-accent">Guardar cambios</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
