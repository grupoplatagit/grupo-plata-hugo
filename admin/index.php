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

// WhatsApp conversations this month
$waConversacionesMes = $db->query("SELECT COUNT(DISTINCT phone) FROM wa_contacts WHERE strftime('%Y-%m', updated_at) = strftime('%Y-%m', 'now','localtime')")->fetchColumn();
$waConversacionesTotales = $db->query('SELECT COUNT(DISTINCT phone) FROM wa_contacts')->fetchColumn();

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
    <div class="stat-card" style="border-color:rgba(0,168,232,0.35)">
        <div class="val" style="color:#00A8E8"><?= $leadsNuevos ?></div>
        <div class="lbl">Leads sin leer &#128276;</div>
    </div>
    <div class="stat-card">
        <div class="val"><?= $totalLeads ?></div>
        <div class="lbl">Total leads</div>
    </div>
    <div class="stat-card" style="border-color:rgba(37,211,102,0.35)">
        <div class="val" style="color:#25d366"><?= $waConversacionesMes ?></div>
        <div class="lbl">Conversaciones WA este mes &#128383;</div>
    </div>
    <div class="stat-card">
        <div class="val"><?= $waConversacionesTotales ?></div>
        <div class="lbl">Total conversaciones WA</div>
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
                    <div style="font-size:1.2rem;font-weight:800;color:<?= $tipo==='blue'?'#f59e0b':($tipo==='bolsa'?'#a78bfa':'#00A8E8') ?>">
                        $<?= number_format($d['venta'], 0, ',', '.') ?>
                    </div>
                    <div style="font-size:.72rem;color:var(--muted)">Venta</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>


</div>

<?php include __DIR__ . '/../views/admin/footer.php'; ?>
