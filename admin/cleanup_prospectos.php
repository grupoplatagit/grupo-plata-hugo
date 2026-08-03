<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
requireLogin();

$db = getDB();

$stmt = $db->query("SELECT id, nombre, telefono, zona, estado FROM prospectos ORDER BY created_at ASC LIMIT 5");
$rows = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirmar'] ?? '') === 'si') {
    $ids = array_column($rows, 'id');
    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        $db->exec("DELETE FROM prospectos WHERE id IN ($in)");
    }
    // Auto-delete this script
    @unlink(__FILE__);
    header('Location: ' . ADMIN_URL . '/pages/prospectos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Limpiar prospectos</title>
<style>
  body { font-family: sans-serif; background:#111; color:#eee; display:flex; justify-content:center; padding:60px 20px; }
  .box { background:#1a1a1a; border:1px solid #333; border-radius:12px; padding:32px; max-width:540px; width:100%; }
  h2 { margin:0 0 8px; color:#f87171; }
  p { color:#aaa; margin:0 0 24px; }
  table { width:100%; border-collapse:collapse; margin-bottom:24px; }
  th { text-align:left; color:#888; font-size:.75rem; padding:6px 8px; border-bottom:1px solid #2a2a2a; }
  td { padding:8px; font-size:.85rem; border-bottom:1px solid #222; }
  .btn { display:inline-block; padding:10px 24px; border-radius:8px; border:none; font-weight:600; cursor:pointer; font-size:.9rem; }
  .btn-danger { background:#ef4444; color:#fff; }
  .btn-cancel { background:#2a2a2a; color:#aaa; margin-left:10px; text-decoration:none; }
</style>
</head>
<body>
<div class="box">
  <h2>Eliminar primeros 5 prospectos</h2>
  <p>Estos registros se borrarán permanentemente:</p>
  <table>
    <tr><th>#</th><th>Nombre</th><th>Zona</th><th>Estado</th></tr>
    <?php foreach ($rows as $i => $r): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><?= htmlspecialchars($r['nombre']) ?></td>
      <td><?= htmlspecialchars($r['zona']) ?></td>
      <td><?= htmlspecialchars($r['estado']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <form method="POST">
    <input type="hidden" name="confirmar" value="si">
    <button type="submit" class="btn btn-danger">Sí, eliminar los <?= count($rows) ?></button>
    <a href="<?= ADMIN_URL ?>/pages/prospectos.php" class="btn btn-cancel">Cancelar</a>
  </form>
</div>
</body>
</html>
