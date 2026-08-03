<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$errors  = [];
$data    = ['nombre'=>'','empresa'=>'','email'=>'','telefono'=>'','ciudad'=>'','servicio'=>'','monto'=>'','notas'=>''];
$servicios = ['Marketing Digital','Gestión de Redes Sociales','Consultoría','Email Marketing','Automatización','Soporte'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($data) as $k) $data[$k] = trim($_POST[$k] ?? '');

    if (empty($data['nombre'])) $errors[] = 'El nombre es obligatorio.';

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare(
            "INSERT INTO clientes (nombre, empresa, email, telefono, ciudad, servicio, monto, notas)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $monto = is_numeric($data['monto']) ? (float)$data['monto'] : 0;
        $stmt->execute([$data['nombre'],$data['empresa'],$data['email'],$data['telefono'],$data['ciudad'],$data['servicio'],$monto,$data['notas']]);
        flashSet('success', 'Cliente creado correctamente.');
        redirect(ADMIN_URL . '/pages/clientes.php');
    }
}

$pageTitle = 'Nuevo cliente';
include __DIR__ . '/../../views/admin/header.php';
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card" style="max-width:680px">
    <div class="card-header">
        <h2>Nuevo cliente</h2>
        <a href="<?= ADMIN_URL ?>/pages/clientes.php" class="btn btn-sm" style="background:var(--surface2)">&#8592; Volver</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($data['nombre']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Empresa</label>
                    <input type="text" name="empresa" value="<?= htmlspecialchars($data['empresa']) ?>" placeholder="Grupo Plata, Soy Bonanza...">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>">
                </div>
                <div class="form-group">
                    <label>Teléfono / WhatsApp</label>
                    <input type="text" name="telefono" value="<?= htmlspecialchars($data['telefono']) ?>" placeholder="+54 9 11...">
                </div>
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" name="ciudad" value="<?= htmlspecialchars($data['ciudad']) ?>" placeholder="Santa Fe, Bs. As...">
                </div>
                <div class="form-group">
                    <label>Monto mensual ($)</label>
                    <input type="number" name="monto" value="<?= htmlspecialchars($data['monto']) ?>" placeholder="Ej: 400000" min="0" step="1000">
                </div>
                <div class="form-group">
                    <label>Servicio</label>
                    <select name="servicio">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($servicios as $s): ?>
                            <option value="<?= $s ?>" <?= $data['servicio']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Notas</label>
                <textarea name="notas" rows="3"><?= htmlspecialchars($data['notas']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-accent">Guardar cliente</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
