<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

$db = getDB();

// Resolver código desde URL (/propuesta/JP-2506-001) o query string (?c=...)
$uri    = $_SERVER['REQUEST_URI'] ?? '';
$parts  = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
$codigo = '';
foreach ($parts as $i => $part) {
    if ($part === 'propuesta' && isset($parts[$i+1])) {
        $codigo = strtoupper($parts[$i+1]);
        break;
    }
}
if (!$codigo) $codigo = strtoupper(trim($_GET['c'] ?? ''));

$prop = null;
$error = '';

if ($codigo) {
    $stmt = $db->prepare("SELECT * FROM propuestas WHERE codigo = ?");
    $stmt->execute([$codigo]);
    $prop = $stmt->fetch();
    if (!$prop) $error = 'Propuesta no encontrada.';
}

$autenticado = false;
$claveError  = '';

if ($prop && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $claveInput = strtoupper(trim($_POST['clave'] ?? ''));
    if ($claveInput === strtoupper($prop['clave'])) {
        $autenticado = true;
        // Marcar como vista si es la primera vez
        if ($prop['estado'] === 'enviada' || $prop['viewed_at'] === null) {
            $db->prepare("UPDATE propuestas SET estado='vista', viewed_at=COALESCE(viewed_at,datetime('now','localtime')), updated_at=datetime('now','localtime') WHERE id=?")
               ->execute([$prop['id']]);
        }
    } else {
        $claveError = 'Clave incorrecta. Verificá con quien te la envió.';
    }
}

$empresa = $prop ? htmlspecialchars($prop['cliente_empresa']) : '';
$contacto = $prop ? htmlspecialchars($prop['cliente_nombre']) : '';
$fecha = $prop ? date('F Y', strtotime($prop['created_at'])) : date('F Y');
$mesAno = $prop ? (function($d){ $meses=['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril','May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto','September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre']; return strtr(date('F Y', strtotime($d)), $meses); })($prop['created_at']) : date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Propuesta Comercial<?= $prop ? ' — '.$empresa : '' ?> | JP Market Pro</title>
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0a0f;--surface:#111118;--card:#16161e;--border:#1e1e2a;
  --accent:#6366f1;--accent2:#8b5cf6;--green:#22c55e;--yellow:#fbbf24;
  --text:#f1f5f9;--muted:#64748b;--font:'Inter',sans-serif;
}
body{background:var(--bg);color:var(--text);font-family:var(--font);line-height:1.6;min-height:100vh}

/* ─── GATE ─────────────────────────────────────────────────────────────── */
.gate-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:radial-gradient(ellipse at 50% 0%,rgba(99,102,241,.12) 0%,transparent 60%)}
.gate-box{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:48px 40px;max-width:420px;width:100%;text-align:center}
.gate-logo{font-size:1.1rem;font-weight:800;color:var(--accent);letter-spacing:-.5px;margin-bottom:32px;display:flex;align-items:center;justify-content:center;gap:8px}
.gate-logo span{background:var(--accent);color:#fff;padding:4px 10px;border-radius:8px;font-size:.85rem}
.gate-title{font-size:1.5rem;font-weight:700;margin-bottom:8px}
.gate-sub{color:var(--muted);font-size:.9rem;margin-bottom:32px}
.gate-empresa{color:var(--accent);font-weight:700}
.gate-input{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 16px;color:var(--text);font-family:var(--font);font-size:1rem;text-align:center;letter-spacing:.2em;text-transform:uppercase;outline:none;transition:border .2s}
.gate-input:focus{border-color:var(--accent)}
.gate-btn{width:100%;margin-top:16px;background:var(--accent);color:#fff;border:none;border-radius:10px;padding:14px;font-size:1rem;font-weight:700;cursor:pointer;font-family:var(--font);transition:opacity .2s}
.gate-btn:hover{opacity:.9}
.gate-error{color:#f87171;font-size:.83rem;margin-top:10px}
.gate-lock{font-size:3rem;margin-bottom:16px}
.gate-badge{display:inline-block;background:rgba(99,102,241,.15);color:var(--accent);border:1px solid rgba(99,102,241,.25);border-radius:20px;padding:4px 14px;font-size:.75rem;font-weight:600;margin-bottom:24px}

/* ─── PROPUESTA ─────────────────────────────────────────────────────────── */
.prop-page{width:100%}
.band{width:100%;position:relative;overflow:hidden}

/* Dot-grid pattern para bandas oscuras */
.band-dark{background:#0a0a0f}
.band-dark::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(99,102,241,.13) 1px,transparent 1px);background-size:28px 28px;pointer-events:none}

/* Hero con gradiente + dot grid más pronunciado */
.band-hero{background:radial-gradient(ellipse at 50% 0%,rgba(99,102,241,.22) 0%,rgba(10,10,20,.8) 60%),#0a0a0f;border-bottom:1px solid var(--border)}
.band-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(99,102,241,.18) 1px,transparent 1px);background-size:32px 32px;pointer-events:none}

/* Alt con tono diferente y grid diagonal */
.band-alt{background:#0d0d1b}
.band-alt::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(99,102,241,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(99,102,241,.04) 1px,transparent 1px);background-size:40px 40px;pointer-events:none}

.band-cta{background:linear-gradient(160deg,#0f0a1e,#0a0f1a);border-top:1px solid rgba(99,102,241,.15)}
.band-foot{background:#07070d;border-top:1px solid var(--border)}
.inner{max-width:860px;margin:0 auto;padding:60px 24px;position:relative;z-index:1}
.inner-hero{max-width:860px;margin:0 auto;padding:60px 24px;text-align:center;position:relative;z-index:1}

/* Símbolos flotantes contables */
.float-symbols{position:absolute;inset:0;pointer-events:none;overflow:hidden;z-index:0}
.float-symbols span{position:absolute;font-size:1.1rem;opacity:.06;animation:floatUp 18s linear infinite;user-select:none}
.float-symbols span:nth-child(1){left:4%;animation-delay:0s;animation-duration:20s}
.float-symbols span:nth-child(2){left:12%;animation-delay:3s;animation-duration:17s}
.float-symbols span:nth-child(3){left:22%;animation-delay:7s;animation-duration:22s}
.float-symbols span:nth-child(4){left:35%;animation-delay:1s;animation-duration:19s}
.float-symbols span:nth-child(5){left:50%;animation-delay:5s;animation-duration:16s}
.float-symbols span:nth-child(6){left:63%;animation-delay:9s;animation-duration:21s}
.float-symbols span:nth-child(7){left:75%;animation-delay:2s;animation-duration:18s}
.float-symbols span:nth-child(8){left:87%;animation-delay:6s;animation-duration:23s}
.float-symbols span:nth-child(9){left:93%;animation-delay:11s;animation-duration:15s}
.float-symbols span:nth-child(10){left:28%;animation-delay:14s;animation-duration:20s}
@keyframes floatUp{
  0%  {transform:translateY(110vh) rotate(0deg);  opacity:0}
  5%  {opacity:.07}
  90% {opacity:.06}
  100%{transform:translateY(-10vh) rotate(20deg); opacity:0}
}

/* Header */
.prop-logo{font-size:1rem;font-weight:800;color:var(--accent);margin-bottom:24px;display:inline-flex;align-items:center;gap:8px}
.prop-logo span{background:var(--accent);color:#fff;padding:3px 9px;border-radius:7px;font-size:.8rem}
.prop-badge{display:inline-block;background:rgba(99,102,241,.12);color:var(--accent);border:1px solid rgba(99,102,241,.2);border-radius:20px;padding:5px 18px;font-size:.78rem;font-weight:600;margin-bottom:20px;letter-spacing:.05em}
.prop-title{font-size:clamp(1.8rem,5vw,2.8rem);font-weight:800;line-height:1.2;margin-bottom:12px}
.prop-title .highlight{background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.prop-meta{color:var(--muted);font-size:.9rem}
.prop-meta strong{color:var(--text)}

/* Sections */
.section-label{font-size:.75rem;font-weight:700;color:var(--accent);letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px}
.section-title{font-size:1.4rem;font-weight:700;margin-bottom:6px}
.section-sub{color:var(--muted);font-size:.9rem;margin-bottom:28px}

/* Cards on alt band */
.band-alt .card-item{background:#13132200}
.band-alt .pilar{background:#131320;border-color:#232336}
.band-alt .pilar-feature{background:rgba(255,255,255,.04)}
.band-alt .problem-item{background:#131320;border-color:#232336}
.band-alt .roadmap-item{background:#131320;border-color:#232336}

/* Cards */
.grid-2{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.grid-3{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px}
.card-item{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px}
.card-item .icon{font-size:1.6rem;margin-bottom:10px}
.card-item h4{font-size:.95rem;font-weight:700;margin-bottom:6px}
.card-item p{font-size:.82rem;color:var(--muted);line-height:1.5}

/* Problem list */
.problem-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:14px}
.problem-item{display:flex;align-items:flex-start;gap:16px;background:#13132099;border:1px solid #2a1a1a;border-top:2px solid #ef4444;border-radius:12px;padding:20px;transition:border-color .2s,transform .2s}
.problem-item:hover{border-color:#f87171;transform:translateY(-2px)}
.problem-num{font-size:1.5rem;font-weight:900;color:#ef444433;min-width:36px;line-height:1;letter-spacing:-.03em;flex-shrink:0}
.problem-icon{font-size:1.4rem;flex-shrink:0;margin-top:1px}
.problem-text h4{font-size:.92rem;font-weight:700;margin-bottom:5px;color:var(--text)}
.problem-text p{font-size:.82rem;color:var(--muted);line-height:1.5}

/* Pilar tabs */
.pilar{background:var(--card);border:1px solid var(--border);border-radius:16px;margin-bottom:16px;overflow:hidden}
.pilar-header{padding:18px 20px;display:flex;align-items:center;gap:14px;cursor:pointer}
.pilar-icon{font-size:1.4rem}
.pilar-title{font-weight:700;font-size:1rem}
.pilar-sub{font-size:.8rem;color:var(--muted);margin-top:2px}
.pilar-body{padding:0 20px 20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px}
.pilar-feature{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:12px 14px;font-size:.83rem}
.pilar-feature::before{content:'✓ ';color:var(--green);font-weight:700}

/* Timeline */
.timeline{display:flex;flex-direction:column;gap:0}
.tl-item{display:flex;gap:16px}
.tl-left{display:flex;flex-direction:column;align-items:center;min-width:40px}
.tl-dot{width:36px;height:36px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0}
.tl-line{flex:1;width:2px;background:var(--border);margin:4px 0}
.tl-item:last-child .tl-line{display:none}
.tl-content{padding-bottom:24px}
.tl-content h4{font-weight:700;font-size:.95rem;margin-bottom:4px}
.tl-content p{font-size:.83rem;color:var(--muted)}

/* Pricing */
.pricing-box{background:var(--card);border:2px solid var(--accent);border-radius:20px;padding:32px;text-align:center;position:relative;overflow:hidden}
.pricing-box::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 0%,rgba(99,102,241,.08),transparent 60%);pointer-events:none}
.pricing-tag{display:inline-block;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border-radius:20px;padding:4px 16px;font-size:.75rem;font-weight:700;margin-bottom:16px}
.pricing-amount{font-size:3rem;font-weight:800;color:var(--accent)}
.pricing-amount sup{font-size:1.2rem;vertical-align:super}
.pricing-amount .currency{font-size:1.2rem}
.pricing-period{color:var(--muted);font-size:.85rem;margin-bottom:20px}
.pricing-includes{list-style:none;text-align:left;display:inline-block}
.pricing-includes li{font-size:.85rem;padding:5px 0;display:flex;gap:8px;align-items:flex-start}
.pricing-includes li::before{content:'✓';color:var(--green);font-weight:800;flex-shrink:0}

.pricing-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:24px}
.price-card{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;padding:18px;text-align:center}
.price-card .label{font-size:.75rem;color:var(--muted);margin-bottom:6px}
.price-card .amount{font-size:1.5rem;font-weight:800}
.price-card .sub{font-size:.75rem;color:var(--muted);margin-top:4px}
.price-card.highlight{border-color:var(--green);background:rgba(34,197,94,.05)}
.price-card.highlight .amount{color:var(--green)}

/* Roadmap */
.roadmap-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
.roadmap-item{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:10px}
.roadmap-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.roadmap-dot.soon{background:var(--yellow)}
.roadmap-dot.future{background:var(--muted)}
.roadmap-text{font-size:.83rem}
.roadmap-text small{color:var(--muted);font-size:.73rem;display:block}

/* CTA */
.cta-box{background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(139,92,246,.1));border:1px solid rgba(99,102,241,.3);border-radius:20px;padding:40px;text-align:center}
.cta-box h3{font-size:1.4rem;font-weight:800;margin-bottom:8px}
.cta-box p{color:var(--muted);font-size:.9rem;margin-bottom:28px}
.cta-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.cta-btn{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:12px;font-weight:700;font-size:.9rem;text-decoration:none;border:none;cursor:pointer;font-family:var(--font)}
.cta-btn.primary{background:var(--accent);color:#fff}
.cta-btn.secondary{background:rgba(255,255,255,.06);color:var(--text);border:1px solid var(--border)}

/* Confidential footer */
.conf-footer{text-align:center;margin-top:48px;color:var(--muted);font-size:.78rem;border-top:1px solid var(--border);padding-top:24px}

@media(max-width:600px){
  .inner,.inner-hero{padding:40px 16px}
  .pricing-grid{grid-template-columns:1fr}
  .cta-btns{flex-direction:column;align-items:stretch}
}
</style>
</head>
<body>

<?php if (!$prop): ?>
<!-- Error: propuesta no encontrada -->
<div class="gate-wrap">
    <div class="gate-box">
        <div class="gate-logo">JP <span>MARKET PRO</span></div>
        <div class="gate-lock">📄</div>
        <div class="gate-title">Propuesta no encontrada</div>
        <div class="gate-sub">El código <strong><?= htmlspecialchars($codigo) ?></strong> no existe o expiró.<br>Verificá el link con quien te lo envió.</div>
    </div>
</div>

<?php elseif (!$autenticado): ?>
<!-- Gate de acceso -->
<div class="gate-wrap">
    <div class="gate-box">
        <div class="gate-logo">JP <span>MARKET PRO</span></div>
        <div class="gate-badge">📄 Propuesta Comercial Confidencial</div>
        <div class="gate-lock">🔒</div>
        <div class="gate-title">Acceso privado</div>
        <div class="gate-sub">Esta propuesta fue preparada exclusivamente para<br><span class="gate-empresa"><?= $empresa ?></span></div>
        <form method="POST" style="margin-top:8px">
            <input type="text" name="clave" class="gate-input" placeholder="Ingresá tu clave de acceso" autocomplete="off" autofocus>
            <?php if ($claveError): ?>
            <div class="gate-error"><?= htmlspecialchars($claveError) ?></div>
            <?php endif; ?>
            <button type="submit" class="gate-btn">Ver mi propuesta →</button>
        </form>
        <div style="margin-top:20px;font-size:.75rem;color:var(--muted)">¿No tenés la clave? Escribinos por WhatsApp.</div>
    </div>
</div>

<?php else: ?>
<!-- Propuesta completa -->
<div class="prop-page">

<!-- ① HERO ─────────────────────────────────────────────── -->
<div class="band band-hero">
    <div class="float-symbols" aria-hidden="true">
        <span>📊</span><span>💹</span><span>🧾</span><span>📈</span>
        <span>💰</span><span>📋</span><span>🔢</span><span>📉</span>
        <span>🏦</span><span>📌</span>
    </div>
    <div class="inner-hero">
        <div class="prop-logo">JP <span>MARKET PRO</span></div>
        <div class="prop-badge">📄 Propuesta Comercial Confidencial</div>
        <h1 class="prop-title">Transformamos <span class="highlight"><?= $empresa ?></span> en el estudio más digital del barrio</h1>
        <div class="prop-meta">
            Preparada para <strong><?= $contacto ?></strong> &nbsp;·&nbsp; <?= $mesAno ?> &nbsp;·&nbsp; Código: <strong style="color:var(--accent);font-family:monospace"><?= htmlspecialchars($prop['codigo']) ?></strong>
        </div>
    </div>
</div>

<!-- ② PROBLEMA (tono alt) ───────────────────────────────── -->
<div class="band band-alt">
<div class="inner">
    <div class="section">
        <div class="section-label">El problema</div>
        <div class="section-title">¿Qué está limitando el crecimiento del estudio?</div>
        <div class="section-sub">Un diagnóstico honesto de la situación actual.</div>
        <div class="problem-list">
            <div class="problem-item">
                <div class="problem-num">01</div>
                <div class="problem-icon">💬</div>
                <div class="problem-text"><h4>Comunicación solo por WhatsApp</h4><p>Documentos, consultas y avisos mezclados en el chat personal. Sin trazabilidad ni organización.</p></div>
            </div>
            <div class="problem-item">
                <div class="problem-num">02</div>
                <div class="problem-icon">⏰</div>
                <div class="problem-text"><h4>Vencimientos manejados de forma manual</h4><p>Riesgo constante de olvidos y errores costosos para el cliente y para el estudio.</p></div>
            </div>
            <div class="problem-item">
                <div class="problem-num">03</div>
                <div class="problem-icon">👁️</div>
                <div class="problem-text"><h4>El cliente no ve el valor del servicio</h4><p>Sin visibilidad del trabajo que hacés, el cliente solo lo nota cuando algo sale mal.</p></div>
            </div>
            <div class="problem-item">
                <div class="problem-num">04</div>
                <div class="problem-icon">🌐</div>
                <div class="problem-text"><h4>Sin presencia digital</h4><p>Ausencia web = perder consultas orgánicas todos los días. Los competidores con web capturan esos clientes.</p></div>
            </div>
            <div class="problem-item">
                <div class="problem-num">05</div>
                <div class="problem-icon">💰</div>
                <div class="problem-text"><h4>Comparación solo por precio</h4><p>Sin diferenciación visible, la única variable que te comparan es la tarifa. Eso te pone en desventaja.</p></div>
            </div>
            <div class="problem-item">
                <div class="problem-num">06</div>
                <div class="problem-icon">📁</div>
                <div class="problem-text"><h4>Documentos desorganizados</h4><p>Tiempo perdido buscando archivos que deberían estar a un clic. Ineficiencia que escala con cada cliente nuevo.</p></div>
            </div>
        </div>
    </div>
</div></div>

<!-- ③ SOLUCIÓN (tono dark) ─────────────────────────────── -->
<div class="band band-dark">
<div class="inner">
    <div class="section">
        <div class="section-label">La solución</div>
        <div class="section-title">Una plataforma integrada, a medida del estudio</div>
        <div class="section-sub">Tres pilares que trabajan juntos desde el primer día.</div>

        <div class="pilar">
            <div class="pilar-header">
                <div class="pilar-icon">🌐</div>
                <div><div class="pilar-title">Sitio Web Profesional</div><div class="pilar-sub">La cara digital de <?= $empresa ?></div></div>
            </div>
            <div class="pilar-body">
                <div class="pilar-feature">Página de inicio con propuesta de valor</div>
                <div class="pilar-feature">Servicios detallados con CTA</div>
                <div class="pilar-feature">Perfiles del equipo con matrícula</div>
                <div class="pilar-feature">Blog de novedades fiscales y ARCA</div>
                <div class="pilar-feature">Formulario de contacto profesional</div>
                <div class="pilar-feature">Optimización SEO local</div>
            </div>
        </div>

        <div class="pilar">
            <div class="pilar-header">
                <div class="pilar-icon">👤</div>
                <div><div class="pilar-title">Portal del Cliente</div><div class="pilar-sub">Lo que verá cada uno de sus clientes</div></div>
            </div>
            <div class="pilar-body">
                <div class="pilar-feature">Dashboard con estado actual</div>
                <div class="pilar-feature">Calendario de vencimientos personal</div>
                <div class="pilar-feature">Carga de documentos desde celular</div>
                <div class="pilar-feature">Chat directo con el contador</div>
                <div class="pilar-feature">Descarga de declaraciones</div>
                <div class="pilar-feature">Historial de facturas</div>
            </div>
        </div>

        <div class="pilar">
            <div class="pilar-header">
                <div class="pilar-icon">🖥️</div>
                <div><div class="pilar-title">Panel del Contador</div><div class="pilar-sub">Tu centro de comando</div></div>
            </div>
            <div class="pilar-body">
                <div class="pilar-feature">Ficha completa por cliente</div>
                <div class="pilar-feature">Solicitud y seguimiento de documentos</div>
                <div class="pilar-feature">Gestión de tareas con prioridades</div>
                <div class="pilar-feature">Calendario global de vencimientos</div>
                <div class="pilar-feature">Facturación con estados de pago</div>
                <div class="pilar-feature">Reportes de cobranza</div>
            </div>
        </div>
    </div>
</div></div>

<!-- ④ DEMO (tono alt) ──────────────────────────────────── -->
<div class="band band-alt">
<div class="inner">
    <div class="section">
        <div class="section-label">Demo en vivo</div>
        <div class="section-title">Así se vería tu plataforma</div>
        <div class="section-sub">Dos vistas de ejemplo — totalmente funcionales, podés explorarlas ahora.</div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

            <!-- Panel del Contador -->
            <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden">
                <div style="background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(139,92,246,.1));padding:24px 24px 20px;border-bottom:1px solid var(--border)">
                    <div style="font-size:1.6rem;margin-bottom:8px">🖥️</div>
                    <div style="font-weight:700;font-size:1rem;margin-bottom:4px">Panel del Contador</div>
                    <div style="font-size:.8rem;color:var(--muted)">Tu centro de gestión de clientes, tareas y facturación</div>
                </div>
                <div style="padding:18px 24px">
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:7px;margin-bottom:20px">
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Dashboard con métricas clave</li>
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Ficha completa de cada cliente</li>
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Gestión de tareas y vencimientos</li>
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Facturación con estado de pago</li>
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Solicitud de documentos a clientes</li>
                    </ul>
                    <a href="https://contadores-asociados-silk.vercel.app/admin/dashboard" target="_blank"
                       style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;background:var(--accent);color:#fff;border-radius:10px;font-weight:700;font-size:.88rem;text-decoration:none;transition:opacity .2s"
                       onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        Ver demo del panel &nbsp;↗
                    </a>
                </div>
            </div>

            <!-- Portal del Cliente -->
            <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden">
                <div style="background:linear-gradient(135deg,rgba(34,197,94,.15),rgba(6,182,212,.08));padding:24px 24px 20px;border-bottom:1px solid var(--border)">
                    <div style="font-size:1.6rem;margin-bottom:8px">👤</div>
                    <div style="font-weight:700;font-size:1rem;margin-bottom:4px">Portal del Cliente</div>
                    <div style="font-size:.8rem;color:var(--muted)">Lo que verá cada cliente cuando ingrese a su cuenta</div>
                </div>
                <div style="padding:18px 24px">
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:7px;margin-bottom:20px">
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Estado y vencimientos personales</li>
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Subida de documentos desde el celular</li>
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Chat directo con el contador</li>
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Descarga de declaraciones</li>
                        <li style="font-size:.82rem;display:flex;gap:8px"><span style="color:var(--green);font-weight:700">✓</span> Historial de facturas</li>
                    </ul>
                    <a href="https://contadores-asociados-silk.vercel.app/portal/dashboard" target="_blank"
                       style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;background:rgba(34,197,94,.15);color:var(--green);border:1px solid rgba(34,197,94,.3);border-radius:10px;font-weight:700;font-size:.88rem;text-decoration:none;transition:opacity .2s"
                       onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                        Ver demo del portal &nbsp;↗
                    </a>
                </div>
            </div>

        </div>
    </div>
</div></div>

<!-- ⑤ CRONOGRAMA (tono dark) ────────────────────────────── -->
<div class="band band-dark">
<div class="inner">
    <div class="section">
        <div class="section-label">Cronograma</div>
        <div class="section-title">De cero a lanzado en 2 a 3 semanas</div>
        <div class="section-sub">Proceso claro, sin sorpresas.</div>
        <div class="timeline">
            <div class="tl-item">
                <div class="tl-left"><div class="tl-dot">1</div><div class="tl-line"></div></div>
                <div class="tl-content"><h4>Reunión de kickoff</h4><p>Definimos marca, colores, servicios y accesos. Duración: 30-45 min por video o presencial.</p></div>
            </div>
            <div class="tl-item">
                <div class="tl-left"><div class="tl-dot">2</div><div class="tl-line"></div></div>
                <div class="tl-content"><h4>Aprobación de diseño</h4><p>Presentamos mockup del sitio y el portal. Incorporamos correcciones antes del desarrollo.</p></div>
            </div>
            <div class="tl-item">
                <div class="tl-left"><div class="tl-dot">3</div><div class="tl-line"></div></div>
                <div class="tl-content"><h4>Desarrollo y testing</h4><p>Construimos sitio + portal + panel. Probamos en staging antes del lanzamiento.</p></div>
            </div>
            <div class="tl-item">
                <div class="tl-left"><div class="tl-dot">4</div><div class="tl-line"></div></div>
                <div class="tl-content"><h4>Lanzamiento</h4><p>Publicamos en producción, cargamos los primeros clientes y te capacitamos en el uso del panel.</p></div>
            </div>
        </div>
    </div>
</div></div>

<!-- ⑥ INVERSIÓN (tono alt) ─────────────────────────────── -->
<div class="band band-alt">
<div class="inner">
    <div class="section">
        <div class="section-label">Inversión</div>
        <div class="section-title">Plan único — todo incluido</div>
        <div class="section-sub">Sin sorpresas, sin extras ocultos.</div>
        <div class="pricing-box">
            <div class="pricing-tag">⭐ Plan Profesional</div>
            <div style="margin-bottom:6px;font-size:.85rem;color:var(--muted)">Implementación inicial (pago único)</div>
            <div class="pricing-amount"><span class="currency">$</span>200.000 <small style="font-size:1rem">ARS</small></div>
            <div class="pricing-grid">
                <div class="price-card">
                    <div class="label">Suscripción mensual</div>
                    <div class="amount">$80.000</div>
                    <div class="sub">ARS / mes</div>
                </div>
                <div class="price-card highlight">
                    <div class="label">Suscripción anual</div>
                    <div class="amount">$800.000</div>
                    <div class="sub">ARS / año — ahorrás 2 meses 🎉</div>
                </div>
            </div>
            <ul class="pricing-includes" style="margin-top:24px">
                <li>Hosting, dominio y backups incluidos</li>
                <li>Soporte técnico prioritario</li>
                <li>Todas las actualizaciones de la plataforma</li>
                <li>Ajuste semestral por IPC con 30 días de anticipación</li>
                <li>Clientes ilimitados desde el primer día</li>
            </ul>
        </div>
    </div>

    </div>
</div></div>

<!-- ⑦ ROADMAP (tono dark) ──────────────────────────────── -->
<div class="band band-dark">
<div class="inner">
    <div class="section">
        <div class="section-label">Módulos futuros</div>
        <div class="section-title">La plataforma crece con tu estudio</div>
        <div class="section-sub">Disponibles de forma modular sin migración ni cambios de plan.</div>
        <div class="roadmap-grid">
            <div class="roadmap-item"><div class="roadmap-dot soon"></div><div class="roadmap-text">Integración ARCA <small>Próximamente</small></div></div>
            <div class="roadmap-item"><div class="roadmap-dot soon"></div><div class="roadmap-text">Cobro online (MercadoPago) <small>Próximamente</small></div></div>
            <div class="roadmap-item"><div class="roadmap-dot future"></div><div class="roadmap-text">Firma digital de contratos <small>Módulo futuro</small></div></div>
            <div class="roadmap-item"><div class="roadmap-dot future"></div><div class="roadmap-text">Notificaciones automáticas <small>Módulo futuro</small></div></div>
            <div class="roadmap-item"><div class="roadmap-dot future"></div><div class="roadmap-text">App móvil nativa <small>Módulo futuro</small></div></div>
            <div class="roadmap-item"><div class="roadmap-dot future"></div><div class="roadmap-text">Multi-estudio <small>Módulo futuro</small></div></div>
        </div>
    </div>

    </div>
</div></div>

<!-- ⑧ CTA ──────────────────────────────────────────────── -->
<div class="band band-cta">
<div class="inner">
    <div class="cta-box">
        <h3>¿Listo para dar el siguiente paso?</h3>
        <p>Esta propuesta tiene una validez de 30 días desde su emisión.<br>Cualquier consulta estoy disponible por WhatsApp o email.</p>
        <div class="cta-btns">
            <a href="https://wa.me/15559685528?text=Hola%20Juan%20Pablo%2C%20vi%20la%20propuesta%20para%20<?= urlencode($empresa) ?>%20y%20quiero%20avanzar%20%F0%9F%91%8D" class="cta-btn primary" target="_blank">
                💬 Quiero avanzar
            </a>
            <a href="mailto:info@jpmarketpro.com?subject=Propuesta <?= urlencode($empresa) ?>" class="cta-btn secondary">
                ✉️ Enviar email
            </a>
        </div>
    </div>

    </div>
</div></div>

<!-- ⑨ FOOTER ───────────────────────────────────────────── -->
<div class="band band-foot">
    <div style="max-width:860px;margin:0 auto;padding:32px 24px;text-align:center;color:var(--muted);font-size:.78rem">
        Propuesta confidencial preparada por <strong style="color:var(--text)">Juan Pablo — JP Market Pro</strong><br>
        info@jpmarketpro.com &nbsp;·&nbsp; +1 (555) 968-5528 &nbsp;·&nbsp; jpmarketpro.com<br>
        <span style="margin-top:6px;display:inline-block">Código: <?= htmlspecialchars($prop['codigo']) ?> &nbsp;·&nbsp; <?= $mesAno ?></span>
    </div>
</div>

</div>
<?php endif; ?>

</body>
</html>
