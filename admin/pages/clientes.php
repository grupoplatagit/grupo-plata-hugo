<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $db->prepare('DELETE FROM clientes WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    flashSet('success', 'Cliente eliminado.');
    redirect(ADMIN_URL . '/pages/clientes.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $db->prepare('UPDATE clientes SET activo = CASE WHEN activo=1 THEN 0 ELSE 1 END WHERE id = ?')->execute([(int)$_POST['toggle_id']]);
    redirect(ADMIN_URL . '/pages/clientes.php');
}

$search  = trim($_GET['q'] ?? '');
$servFil = trim($_GET['servicio'] ?? '');
$perPage = 15;
$currentPage = max(1, (int)($_GET['page'] ?? 1));

$where  = [];
$params = [];
if ($search) {
    $where[]  = '(nombre LIKE ? OR empresa LIKE ? OR email LIKE ? OR ciudad LIKE ?)';
    $params   = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]);
}
if ($servFil) {
    $where[]  = 'servicio = ?';
    $params[] = $servFil;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $db->prepare("SELECT COUNT(*) FROM clientes $whereSQL");
$cnt->execute($params);
$pag = paginate($cnt->fetchColumn(), $perPage, $currentPage);

$stmt = $db->prepare("SELECT * FROM clientes $whereSQL ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $pag['offset']]));
$clientes = $stmt->fetchAll();

$flash = flashGet();
$pageTitle = 'Clientes';
include __DIR__ . '/../../views/admin/header.php';

$servicios = ['Marketing Digital','Gestión de Redes Sociales','Consultoría','Email Marketing','Automatización','Soporte'];
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <h1 style="font-size:1.3rem;font-weight:800">Clientes <span style="color:var(--muted);font-weight:400;font-size:1rem">(<?= $pag['total'] ?>)</span></h1>
    <a href="<?= ADMIN_URL ?>/pages/nuevo-cliente.php" class="btn btn-accent">+ Nuevo cliente</a>
</div>

<!-- Filtros -->
<form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
        placeholder="Buscar nombre, empresa, ciudad..."
        style="flex:1;min-width:200px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:9px 14px;color:var(--text);font-size:.9rem;">
    <select name="servicio" style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:9px 14px;color:var(--text);font-size:.9rem;">
        <option value="">Todos los servicios</option>
        <?php foreach ($servicios as $s): ?>
            <option value="<?= $s ?>" <?= $servFil === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-accent">Filtrar</button>
    <a href="<?= ADMIN_URL ?>/pages/clientes.php" class="btn" style="background:var(--surface2);color:var(--text)">Limpiar</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Empresa</th>
                    <th>Ciudad</th>
                    <th>Servicio</th>
                    <th>Monto / mes</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clientes)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px">Sin clientes.</td></tr>
            <?php else: foreach ($clientes as $c): ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($c['nombre']) ?></td>
                    <td style="color:var(--muted)"><?= htmlspecialchars($c['empresa'] ?? '—') ?></td>
                    <td style="color:var(--muted);font-size:.85rem"><?= htmlspecialchars($c['ciudad'] ?? '—') ?></td>
                    <td>
                        <span style="background:rgba(6,182,212,0.1);color:#06b6d4;border:1px solid rgba(6,182,212,0.25);padding:3px 10px;border-radius:50px;font-size:.78rem;font-weight:600;white-space:nowrap">
                            <?= htmlspecialchars($c['servicio'] ?? '—') ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($c['monto'] > 0): ?>
                            <span style="color:#22c55e;font-weight:700;font-size:.9rem">$<?= number_format($c['monto'], 0, ',', '.') ?></span>
                        <?php else: ?>
                            <span style="color:var(--muted);font-size:.85rem">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.85rem">
                        <?php if ($c['email']): ?><div><?= htmlspecialchars($c['email']) ?></div><?php endif; ?>
                        <?php if ($c['telefono']): ?><div style="color:var(--muted)"><?= htmlspecialchars($c['telefono']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="toggle_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="badge <?= $c['activo'] ? 'badge-active' : 'badge-inactive' ?>" style="cursor:pointer;border:none;background:inherit">
                                <?= $c['activo'] ? 'Activo' : 'Inactivo' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div style="display:flex;gap:8px">
                            <a href="<?= ADMIN_URL ?>/pages/editar-cliente.php?id=<?= $c['id'] ?>" class="btn btn-sm" style="background:var(--surface2)">Editar</a>
                            <form method="POST" onsubmit="return confirm('¿Eliminar?')">
                                <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">&#128465;</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['pages'] > 1): ?>
    <div style="padding:20px 24px;display:flex;gap:8px">
        <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
            <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&servicio=<?= urlencode($servFil) ?>"
               class="btn btn-sm" style="background:<?= $i===$currentPage?'var(--accent)':'var(--surface2)' ?>;color:<?= $i===$currentPage?'#000':'var(--text)' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
