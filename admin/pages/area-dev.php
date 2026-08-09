<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

requireLogin();

$db = getDB();

// Obtener admins
$admins = $db->query("SELECT email, nombre FROM admins WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AREA DEV | WhatsApp Config</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #0a1f47;
            --surface: #0d2547;
            --accent: #00A8E8;
            --text: #d4d4d4;
            --border: #1a4a7a;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { text-align: center; margin-bottom: 30px; font-size: 1.8rem; color: var(--accent); }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 16px; }
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #8fa0b8;
            margin-bottom: 6px;
        }
        input, select {
            width: 100%;
            background: rgba(10, 47, 95, 0.5);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
        }
        input:focus { outline: none; border-color: var(--accent); }
        .btn {
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .btn:hover { opacity: 0.9; }
        .btn-secondary {
            background: #666;
            color: #fff;
        }
        .user-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .user-section:first-child { border-top: none; padding-top: 0; margin-top: 0; }
        .user-name { font-weight: 600; color: var(--accent); margin-bottom: 12px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .status { font-size: 0.85rem; margin-top: 8px; padding: 6px 10px; border-radius: 6px; }
        .status.ok { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status.empty { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 AREA DEV - Configuración WhatsApp</h1>

        <div class="card">
            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border);">
                <p style="font-size: 0.9rem; color: #8fa0b8; margin-bottom: 10px;">
                    Configura WABA ID y Phone Number ID para cada usuario
                </p>
            </div>

            <div id="config-container">
                <!-- Se llena con JavaScript -->
            </div>

            <button class="btn" style="margin-top: 20px;" onclick="saveAll()">💾 Guardar Todo</button>
        </div>
    </div>

    <script>
        const admins = <?php echo json_encode($admins); ?>;

        async function loadConfigs() {
            const resp = await fetch('/admin/api/area-dev.php?action=get_all');
            const result = await resp.json();
            return result.data || [];
        }

        async function renderConfigs() {
            const configs = await loadConfigs();
            const configMap = {};
            configs.forEach(c => { configMap[c.admin_email] = c; });

            const html = admins.map(admin => {
                const cfg = configMap[admin.email] || {};
                return `
                    <div class="user-section">
                        <div class="user-name">👤 ${admin.nombre} (${admin.email})</div>
                        <div class="grid">
                            <div class="form-group">
                                <label>WABA ID</label>
                                <input type="text" class="waba-id" data-email="${admin.email}"
                                       value="${cfg.waba_id || ''}" placeholder="1668825147519903">
                            </div>
                            <div class="form-group">
                                <label>Phone Number ID</label>
                                <input type="text" class="phone-id" data-email="${admin.email}"
                                       value="${cfg.phone_number_id || ''}" placeholder="1225619913971231">
                            </div>
                        </div>
                        ${cfg.updated_at ? `<div class="status ok">✓ Actualizado: ${cfg.updated_at}</div>` :
                          `<div class="status empty">⚠ Sin configurar</div>`}
                    </div>
                `;
            }).join('');

            document.getElementById('config-container').innerHTML = html;
        }

        async function saveAll() {
            const inputs = document.querySelectorAll('[data-email]');
            const configs = {};
            inputs.forEach(inp => {
                const email = inp.dataset.email;
                if (!configs[email]) configs[email] = {};
                if (inp.classList.contains('waba-id')) configs[email].waba_id = inp.value.trim();
                if (inp.classList.contains('phone-id')) configs[email].phone_number_id = inp.value.trim();
            });

            for (const [email, data] of Object.entries(configs)) {
                await fetch('/admin/api/area-dev.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_email: email, ...data })
                });
            }

            alert('✓ Configuración guardada');
            renderConfigs();
        }

        renderConfigs();
    </script>
</body>
</html>
