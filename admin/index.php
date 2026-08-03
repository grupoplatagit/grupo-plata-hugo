<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/functions.php';
requireLogin();

$db = getDB();

$totalClientes   = $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
$clientesActivos = $db->query('SELECT COUNT(*) FROM clientes WHERE activo = 1')->fetchColumn();
$totalLeads      = $db->query('SELECT COUNT(*) FROM leads')->fetchColumn();
$leadsNuevos     = $db->query('SELECT COUNT(*) FROM leads WHERE leido = 0')->fetchColumn();
$facturacionMes  = $db->query('SELECT COALESCE(SUM(monto),0) FROM clientes WHERE activo = 1')->fetchColumn();
$clientesConMonto = $db->query('SELECT * FROM clientes WHERE monto > 0 AND activo = 1 ORDER BY monto DESC')->fetchAll();
$ultimosLeads    = $db->query('SELECT * FROM leads ORDER BY created_at DESC LIMIT 5')->fetchAll();

$dolar        = getDolarRate();
$dolarOficial = $dolar['oficial']['venta'] ?? null;
$dolarBlue    = $dolar['blue']['venta']    ?? null;
$dolarMep     = $dolar['bolsa']['venta']   ?? null;

$facturacionUSD   = $dolarOficial ? round($facturacionMes / $dolarOficial) : null;
$campanaUSD       = 100;
$campanaARS       = $dolarOficial ? round($campanaUSD * $dolarOficial) : null;

// Ganancia: monto - (100 USD * tipo_oficial) por cada cliente con campaña activa
$totalInversion   = 0;
$totalGanancia    = 0;
if ($dolarOficial) {
    foreach ($clientesConMonto as $c) {
        $inv = $c['campana'] ? round(100 * $dolarOficial) : 0;
        $totalInversion += $inv;
        $totalGanancia  += $c['monto'] - $inv;
    }
    // Sumar clientes con monto > 0 que no están en el top 8
    $todosConMonto = $db->query('SELECT monto, campana FROM clientes WHERE monto > 0 AND activo = 1')->fetchAll();
    $totalInversion = 0; $totalGanancia = 0;
    foreach ($todosConMonto as $c) {
        $inv = $c['campana'] ? round(100 * $dolarOficial) : 0;
        $totalInversion += $inv;
        $totalGanancia  += $c['monto'] - $inv;
    }
}

$pageTitle = 'Dashboard';
include __DIR__ . '/../views/admin/header.php';
?>

<!-- Stats principales -->
<div class="stats-row">
    <div class="stat-card" style="border-color:rgba(6,182,212,0.35)">
        <div class="val" style="color:#06b6d4"><?= $leadsNuevos ?></div>
        <div class="lbl">Leads sin leer &#128276;</div>
    </div>
    <div class="stat-card">
        <div class="val"><?= $totalLeads ?></div>
        <div class="lbl">Total leads</div>
    </div>
    <div class="stat-card">
        <div class="val"><?= $clientesActivos ?></div>
        <div class="lbl">Clientes activos</div>
    </div>
    <div class="stat-card" style="border-color:rgba(34,197,94,0.35)">
        <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Facturación mensual</div>
        <div style="font-size:1.5rem;font-weight:800;color:#22c55e">$<?= number_format($facturacionMes, 0, ',', '.') ?></div>
        <?php if ($facturacionUSD): ?>
            <div style="font-size:.8rem;color:#86efac;margin-top:4px">≈ USD <?= number_format($facturacionUSD, 0, ',', '.') ?></div>
        <?php endif; ?>
    </div>
    <?php if ($dolarOficial && $totalGanancia > 0): ?>
    <div class="stat-card" style="border-color:rgba(250,204,21,0.4)">
        <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Ganancia neta / mes &#127881;</div>
        <div style="font-size:1.5rem;font-weight:800;color:#facc15">$<?= number_format($totalGanancia, 0, ',', '.') ?></div>
        <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
            Inversión campañas: <span style="color:#f87171">-$<?= number_format($totalInversion, 0, ',', '.') ?></span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Widget Dólar -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

    <!-- Cotizaciones -->
    <div class="card">
        <div class="card-header">
            <h2>&#128178; Cotización del dólar</h2>
            <?php if (!empty($dolar)): ?>
                <span style="font-size:.72rem;color:var(--muted)">Actualizado hace &lt;30 min</span>
            <?php endif; ?>
        </div>
        <?php if (empty($dolar)): ?>
            <div style="padding:24px;color:var(--muted);font-size:.9rem">No se pudo obtener la cotización. Verificá tu conexión.</div>
        <?php else: ?>
        <div style="padding:0">
            <?php
            $iconos = ['oficial'=>'🏦','blue'=>'💵','bolsa'=>'📈'];
            $labels = ['oficial'=>'Oficial','blue'=>'Blue','bolsa'=>'MEP / Bolsa'];
            foreach ($dolar as $tipo => $d):
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid var(--border)">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:1.2rem"><?= $iconos[$tipo] ?? '💰' ?></span>
                    <div>
                        <div style="font-weight:600;font-size:.9rem"><?= $labels[$tipo] ?? $d['nombre'] ?></div>
                        <div style="font-size:.75rem;color:var(--muted)">Compra $<?= number_format($d['compra'], 0, ',', '.') ?></div>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1.2rem;font-weight:800;color:<?= $tipo==='blue'?'#f59e0b':($tipo==='bolsa'?'#a78bfa':'#06b6d4') ?>">
                        $<?= number_format($d['venta'], 0, ',', '.') ?>
                    </div>
                    <div style="font-size:.72rem;color:var(--muted)">Venta</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Calculadora de campaña -->
    <div class="card">
        <div class="card-header">
            <h2>&#127919; Inversión en campañas</h2>
            <span style="font-size:.72rem;color:var(--muted)">Dólar oficial</span>
        </div>
        <div style="padding:24px">
            <?php if ($dolarOficial): ?>

            <!-- Fila: 100 USD en ARS -->
            <div style="background:var(--surface2);border-radius:12px;padding:18px;margin-bottom:16px">
                <div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">100 USD en pesos</div>
                <div style="font-size:2rem;font-weight:800;color:#f59e0b">$<?= number_format($campanaARS, 0, ',', '.') ?></div>
                <div style="font-size:.8rem;color:var(--muted);margin-top:4px">al tipo oficial $<?= number_format($dolarOficial, 0, ',', '.') ?> / USD</div>
            </div>

            <!-- Calculadora dinámica -->
            <div style="background:var(--surface2);border-radius:12px;padding:18px">
                <div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Calculá tu inversión</div>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <span style="color:var(--muted);font-size:.9rem">USD</span>
                    <input type="number" id="usdInput" value="100" min="0"
                        style="width:100px;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:1rem;font-weight:700;font-family:inherit">
                    <span style="color:var(--muted);font-size:.9rem">=</span>
                    <span id="arsOutput" style="font-size:1.1rem;font-weight:800;color:#22c55e">
                        $<?= number_format($campanaARS, 0, ',', '.') ?>
                    </span>
                </div>
                <div style="font-size:.78rem;color:var(--muted)">Tu facturación mensual equivale a <strong style="color:#86efac">USD <?= number_format($facturacionUSD ?? 0, 0, ',', '.') ?></strong></div>
            </div>

            <?php else: ?>
                <div style="color:var(--muted);font-size:.9rem;padding:12px 0">Sin cotización disponible.</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Clientes con mayor facturación -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h2>&#128200; Clientes por facturación</h2>
        <a href="<?= ADMIN_URL ?>/pages/clientes.php" class="btn btn-accent btn-sm">Ver todos</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Nombre</th><th>Empresa</th><th>Monto</th><th>Campaña</th><th>Inversión</th><th>Ganancia</th><th>En USD</th></tr>
            </thead>
            <tbody>
                <?php if (empty($clientesConMonto)): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:32px">Sin clientes con monto cargado.</td></tr>
                <?php else: foreach ($clientesConMonto as $c):
                    $inv      = ($c['campana'] && $dolarOficial) ? round(100 * $dolarOficial) : 0;
                    $ganancia = $c['monto'] - $inv;
                ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($c['nombre']) ?></td>
                    <td style="color:var(--muted);font-size:.85rem"><?= htmlspecialchars($c['empresa'] ?? '—') ?></td>
                    <td style="font-weight:700;color:#22c55e">$<?= number_format($c['monto'], 0, ',', '.') ?></td>
                    <td style="text-align:center">
                        <?php if ($c['campana']): ?>
                            <span title="Invierte 100 USD en campaña" style="font-size:1rem">📢</span>
                        <?php else: ?>
                            <span title="Sin inversión en campaña" style="font-size:.75rem;color:var(--muted)">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#f87171;font-size:.85rem">
                        <?= $inv > 0 ? '-$' . number_format($inv, 0, ',', '.') : '<span style="color:var(--muted)">$0</span>' ?>
                    </td>
                    <td style="font-weight:700;color:#facc15">$<?= number_format($ganancia, 0, ',', '.') ?></td>
                    <td style="color:#86efac;font-size:.82rem">
                        <?= $dolarOficial ? 'USD ' . number_format($ganancia / $dolarOficial, 0, ',', '.') : '—' ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Últimos leads -->
<div class="card">
    <div class="card-header">
        <h2>&#127919; Últimos leads</h2>
        <a href="<?= ADMIN_URL ?>/pages/leads.php" class="btn btn-accent btn-sm">Ver todos</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Nombre</th><th>Nicho</th><th>País</th><th>Presupuesto</th><th>Fecha</th></tr>
            </thead>
            <tbody>
                <?php if (empty($ultimosLeads)): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:32px">Sin leads aún.</td></tr>
                <?php else: foreach ($ultimosLeads as $l): ?>
                <tr>
                    <td><?= htmlspecialchars($l['nombre']) ?></td>
                    <td><?= htmlspecialchars($l['nicho']) ?></td>
                    <td><?= htmlspecialchars($l['pais']) ?></td>
                    <td><span style="color:#06b6d4;font-weight:600"><?= htmlspecialchars($l['presupuesto']) ?></span></td>
                    <td><?= formatDate($l['created_at']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const rate = <?= $dolarOficial ?? 0 ?>;
const input = document.getElementById('usdInput');
const output = document.getElementById('arsOutput');
if (input && rate) {
    input.addEventListener('input', () => {
        const usd = parseFloat(input.value) || 0;
        const ars = Math.round(usd * rate);
        output.textContent = '$' + ars.toLocaleString('es-AR');
    });
}
</script>

<?php include __DIR__ . '/../views/admin/footer.php'; ?>
