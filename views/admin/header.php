<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Grupo Plata Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>/public/assets/img/favicon-32x32.png">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/public/assets/img/favicon.ico">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:       #0a0a0a;
            --surface:  #161616;
            --surface2: #1e1e1e;
            --accent:   #06b6d4;
            --accent-2: #22d3ee;
            --accent-glow: rgba(6,182,212,0.25);
            --text:     #d4d4d4;
            --muted:    #6b6b6b;
            --border:   #2a2a2a;
            --danger:   #ef4444;
            --success:  #22c55e;
            --font:     'Inter', system-ui, sans-serif;
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: var(--font); background: var(--bg); color: var(--text);
            display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 248px; background: var(--surface); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; position: fixed; height: 100vh;
        }
        .sidebar-brand {
            padding: 22px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-brand-logo { height: 36px; width: auto; object-fit: contain;
            filter: brightness(0) invert(1); }
        .sidebar-brand-text { font-size: .7rem; color: var(--muted); letter-spacing: 1px; text-transform: uppercase; }

        .sidebar-section { padding: 20px 16px 6px; font-size: .68rem; color: var(--muted);
            text-transform: uppercase; letter-spacing: 1.5px; }
        .sidebar-nav { padding: 8px 0; flex: 1; overflow-y: auto; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 11px 20px;
            color: var(--muted); font-size: .88rem; font-weight: 500;
            text-decoration: none; transition: all .15s; border-left: 2px solid transparent;
            margin: 1px 0;
        }
        .nav-item:hover { color: var(--text); background: var(--surface2); }
        .nav-item.active {
            color: var(--accent); background: rgba(6,182,212,0.08);
            border-left-color: var(--accent); font-weight: 600;
        }
        .nav-item .icon { font-size: 1rem; width: 20px; text-align: center; }
        .sidebar-footer {
            padding: 16px 20px; border-top: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg,#0e7490,#06b6d4);
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700; color: #000; flex-shrink: 0;
        }
        .sidebar-user-name { font-size: .82rem; font-weight: 600; color: var(--text); }
        .sidebar-user-role { font-size: .72rem; color: var(--muted); }

        /* Main */
        .main { margin-left: 248px; flex: 1; display: flex; flex-direction: column; }
        .topbar {
            background: rgba(10,10,10,0.9); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0 32px; height: 60px; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 10;
        }
        :root[data-theme="light"] .topbar {
            background: rgba(245,245,245,0.95);
        }
        .topbar-title { font-weight: 700; font-size: 1rem; color: var(--text); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-badge {
            background: rgba(6,182,212,0.1); border: 1px solid rgba(6,182,212,0.25);
            color: var(--accent); font-size: .75rem; font-weight: 600;
            padding: 4px 12px; border-radius: 50px;
        }
        .topbar-user  { display: flex; align-items: center; gap: 10px; font-size: .85rem; color: var(--muted); }
        .topbar-user a { color: var(--danger); font-size: .82rem; transition: color .2s; }
        .topbar-user a:hover { color: #f87171; }

        .page-content { padding: 32px; flex: 1; }

        /* Cards */
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { font-size: .95rem; font-weight: 700; }
        .card-body { padding: 24px; }

        /* Stats row */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr));
            gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 22px 24px;
            transition: border-color .2s;
        }
        .stat-card:hover { border-color: rgba(6,182,212,0.3); }
        .stat-card .val { font-size: 2rem; font-weight: 800; color: var(--accent); line-height: 1; }
        .stat-card .lbl { font-size: .78rem; color: var(--muted); margin-top: 6px; text-transform: uppercase; letter-spacing: .5px; }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 16px; text-align: left; font-size: .875rem; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: 1px; }
        tbody tr:hover td { background: var(--surface2); }
        tbody tr:last-child td { border-bottom: none; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px;
            border-radius: 10px; font-size: .85rem; font-weight: 600; cursor: pointer;
            border: none; text-decoration: none; transition: all .2s; font-family: var(--font); }
        .btn-accent { background: var(--accent); color: #000; }
        .btn-accent:hover { background: var(--accent-2); box-shadow: 0 0 20px var(--accent-glow); }
        .btn-danger { background: rgba(239,68,68,.1); color: var(--danger); border: 1px solid rgba(239,68,68,.25); }
        .btn-danger:hover { background: rgba(239,68,68,.2); }
        .btn-sm { padding: 5px 12px; font-size: .78rem; border-radius: 7px; }

        /* Forms */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: .78rem; font-weight: 600;
            color: var(--muted); margin-bottom: 7px; text-transform: uppercase; letter-spacing: .8px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; background: var(--bg); border: 1px solid var(--border);
            border-radius: 9px; padding: 10px 14px; color: var(--text);
            font-family: var(--font); font-size: .9rem; transition: border-color .2s, box-shadow .2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(6,182,212,0.1); }

        /* Alerts */
        .alert { padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: .875rem; }
        .alert-success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.25); color: var(--success); }
        .alert-danger  { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: var(--danger); }

        /* Badge */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: .72rem; font-weight: 600; }
        .badge-active   { background: rgba(34,197,94,.12); color: var(--success); }
        .badge-inactive { background: rgba(239,68,68,.12); color: var(--danger); }

        /* Light theme */
        @media (prefers-color-scheme: light) {
            :root:not([data-theme="dark"]) {
                --bg:       #ffffff;
                --surface:  #f5f5f5;
                --surface2: #efefef;
                --accent:   #0891b2;
                --accent-2: #06b6d4;
                --accent-glow: rgba(6,182,212,0.15);
                --text:     #1a1a1a;
                --muted:    #666666;
                --border:   #e5e5e5;
            }
        }
        :root[data-theme="light"] {
            --bg:       #ffffff;
            --surface:  #f5f5f5;
            --surface2: #efefef;
            --accent:   #0891b2;
            --accent-2: #06b6d4;
            --accent-glow: rgba(6,182,212,0.15);
            --text:     #1a1a1a;
            --muted:    #666666;
            --border:   #e5e5e5;
        }
        :root[data-theme="light"] .sidebar-brand-logo { filter: none !important; }

        /* Toggle button */
        .theme-toggle {
            background: rgba(6,182,212,0.1);
            border: 1px solid rgba(6,182,212,0.3);
            color: #06b6d4;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: all .2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            font-weight: 600;
        }
        .theme-toggle:hover {
            background: rgba(6,182,212,0.2);
            border-color: rgba(6,182,212,0.5);
        }
        :root[data-theme="light"] .theme-toggle {
            background: rgba(8,145,178,0.15);
            border-color: rgba(8,145,178,0.4);
            color: #0891b2;
        }
        :root[data-theme="light"] .theme-toggle:hover {
            background: rgba(8,145,178,0.25);
            border-color: rgba(8,145,178,0.6);
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/public/assets/img/logo.png" alt="Grupo Plata" class="sidebar-brand-logo">
        <div>
            <div style="font-size:.85rem;font-weight:800;color:var(--text)">GRUPO PLATA</div>
            <div class="sidebar-brand-text">Panel Admin</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">General</div>
        <a href="<?= ADMIN_URL ?>/index.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])==='index.php' ? 'active' : '' ?>">
            <span class="icon">&#9707;</span> Dashboard
        </a>

        <div class="sidebar-section">CRM</div>
        <a href="<?= ADMIN_URL ?>/pages/leads.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'leads')!==false ? 'active' : '' ?>">
            <span class="icon">&#127919;</span> Leads
        </a>
        <a href="<?= ADMIN_URL ?>/pages/prospectos.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'prospectos')!==false ? 'active' : '' ?>">
            <span class="icon">&#128270;</span> Prospectos
        </a>
        <a href="<?= ADMIN_URL ?>/pages/pipeline.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'pipeline')!==false ? 'active' : '' ?>">
            <span class="icon">&#128202;</span> Pipeline
        </a>
        <a href="<?= ADMIN_URL ?>/pages/propuestas.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'propuestas')!==false ? 'active' : '' ?>">
            <span class="icon">&#128196;</span> Propuestas
        </a>
        <a href="<?= ADMIN_URL ?>/pages/clientes.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'clientes')!==false && strpos($_SERVER['PHP_SELF'],'nuevo')=== false ? 'active' : '' ?>">
            <span class="icon">&#128101;</span> Clientes
        </a>
        <a href="<?= ADMIN_URL ?>/pages/nuevo-cliente.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'nuevo-cliente')!==false ? 'active' : '' ?>">
            <span class="icon">&#43;</span> Nuevo cliente
        </a>

        <div class="sidebar-section">Integraciones</div>
        <a href="<?= ADMIN_URL ?>/pages/inbox.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'inbox')!==false ? 'active' : '' ?>" style="<?= strpos($_SERVER['PHP_SELF'],'inbox')===false ? 'color:#25d366' : '' ?>">
            <span class="icon">&#128172;</span> Inbox WA
            <?php
            $unreadWA = 0;
            try {
                $unreadWA = (int)getDB()->query("SELECT COUNT(*) FROM wa_messages WHERE direction='in' AND leido=0")->fetchColumn();
            } catch(Exception $e) {}
            if ($unreadWA): ?>
                <span style="margin-left:auto;background:#25d366;color:#000;font-size:.65rem;font-weight:800;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center"><?= $unreadWA ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= ADMIN_URL ?>/pages/whatsapp.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'whatsapp')!==false ? 'active' : '' ?>" style="<?= strpos($_SERVER['PHP_SELF'],'whatsapp')===false ? 'color:#25d366' : '' ?>">
            <span class="icon">&#128383;</span> WhatsApp API
        </a>

        <div class="sidebar-section">Sitio</div>
        <a href="<?= BASE_URL ?>" target="_blank" class="nav-item">
            <span class="icon">&#127758;</span> Ver sitio web
        </a>

        <div class="sidebar-section">Cuenta</div>
        <a href="<?= ADMIN_URL ?>/pages/cambiar-password.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'cambiar-password')!==false ? 'active' : '' ?>">
            <span class="icon">&#128274;</span> Cambiar contraseña
        </a>
        <a href="#" onclick="openAreaDev(); return false" class="nav-item">
            <span class="icon">🔧</span> AREA DEV
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-avatar">JP</div>
        <div>
            <div class="sidebar-user-name"><?= htmlspecialchars(currentAdmin()) ?></div>
            <div class="sidebar-user-role">Administrador</div>
        </div>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <span class="topbar-title"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></span>
        <div class="topbar-right">
            <button class="theme-toggle" id="themeToggle" title="Cambiar tema">🌙</button>
            <span class="topbar-badge">&#128994; Online</span>
            <div class="topbar-user">
                <span><?= htmlspecialchars(currentAdmin()) ?></span>
                <a href="<?= ADMIN_URL ?>/logout.php">Salir &#8594;</a>
            </div>
        </div>
    </div>

    <!-- Modal AREA DEV -->
    <div id="areaDevModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:32px;max-width:400px;width:90%">
            <h2 style="margin-bottom:20px;color:var(--accent)">🔐 Acceso AREA DEV</h2>
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:0.8rem;color:var(--muted);text-transform:uppercase;margin-bottom:8px">Contraseña</label>
                <input type="password" id="areaDevPassword" placeholder="Ingresá la contraseña"
                    style="width:100%;background:rgba(10,10,10,0.5);border:1px solid var(--border);border-radius:8px;padding:10px 12px;color:var(--text);font-family:inherit;font-size:0.95rem">
            </div>
            <div style="display:flex;gap:10px">
                <button onclick="validateAreaDev()" style="flex:1;background:var(--accent);color:#000;border:none;border-radius:8px;padding:10px;font-weight:600;cursor:pointer;font-family:inherit">Entrar</button>
                <button onclick="closeAreaDev()" style="flex:1;background:var(--muted);color:#000;border:none;border-radius:8px;padding:10px;font-weight:600;cursor:pointer;font-family:inherit">Cancelar</button>
            </div>
            <div id="areaDevError" style="color:#ef4444;font-size:0.85rem;margin-top:12px;display:none"></div>
        </div>
    </div>

    <script>
        // Theme toggle
        function initTheme() {
            const saved = localStorage.getItem('adminTheme') || 'dark';
            const toggle = document.getElementById('themeToggle');
            const html = document.documentElement;

            html.setAttribute('data-theme', saved);
            updateToggleIcon(saved);

            toggle.addEventListener('click', () => {
                const current = html.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                localStorage.setItem('adminTheme', next);
                updateToggleIcon(next);
            });
        }

        function updateToggleIcon(theme) {
            const toggle = document.getElementById('themeToggle');
            toggle.textContent = theme === 'dark' ? '☀️' : '🌙';
        }

        // Initialize on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTheme);
        } else {
            initTheme();
        }
    </script>

    <div class="page-content">
