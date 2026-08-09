<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

requireLogin();

$db = getDB();

// Crear tabla si no existe
try {
    $db->exec("CREATE TABLE IF NOT EXISTS user_whatsapp_config (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_email     TEXT NOT NULL UNIQUE,
        waba_id         TEXT,
        phone_number_id TEXT,
        created_at      TEXT DEFAULT (datetime('now','localtime')),
        updated_at      TEXT DEFAULT (datetime('now','localtime'))
    )");
} catch (Exception $e) {
    die("Error creando tabla: " . $e->getMessage());
}

// Obtener admins
try {
    $admins = $db->query("SELECT email, nombre FROM admins WHERE activo = 1 ORDER BY nombre")->fetchAll();
} catch (Exception $e) {
    die("Error obteniendo admins: " . $e->getMessage());
}

$pageTitle = 'AREA DEV - Config WhatsApp';
include __DIR__ . '/../../views/admin/header.php';
?>

<div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:24px">
    <h2 style="color:var(--accent);margin-bottom:20px">🔧 Configuración WhatsApp por Usuario</h2>

    <div style="margin-bottom:20px;padding:16px;background:rgba(6,182,212,0.08);border-left:3px solid var(--accent);border-radius:6px">
        <p style="font-size:0.9rem;color:var(--text)">Configura WABA ID y Phone Number ID para cada usuario</p>
    </div>

    <div id="config-container" style="display:grid;gap:20px">
        <!-- Se llena con JavaScript -->
    </div>

    <button onclick="saveAll()" style="margin-top:20px;background:var(--accent);color:#000;border:none;border-radius:8px;padding:12px 20px;font-weight:600;cursor:pointer;font-family:inherit;font-size:0.95rem">
        💾 Guardar Todo
    </button>
</div>

<script>
const admins = <?php echo json_encode($admins); ?>;

async function loadConfigs() {
    try {
        const resp = await fetch('/admin/api/area-dev.php?action=get_all');
        const result = await resp.json();
        return result.data || [];
    } catch (e) {
        console.error('Error cargando configs:', e);
        return [];
    }
}

async function renderConfigs() {
    const configs = await loadConfigs();
    const configMap = {};
    configs.forEach(c => { configMap[c.admin_email] = c; });

    const html = admins.map(admin => {
        const cfg = configMap[admin.email] || {};
        return `
            <div style="background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:16px">
                <div style="font-weight:600;color:var(--accent);margin-bottom:12px">👤 ${admin.nombre}</div>
                <div style="font-size:0.85rem;color:var(--muted);margin-bottom:12px">${admin.email}</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                    <div>
                        <label style="display:block;font-size:0.75rem;text-transform:uppercase;color:var(--muted);margin-bottom:6px">WABA ID</label>
                        <input type="text" class="waba-id" data-email="${admin.email}"
                               value="${cfg.waba_id || ''}" placeholder="1668825147519903"
                               style="width:100%;background:rgba(10,10,10,0.5);border:1px solid var(--border);border-radius:8px;padding:8px 10px;color:var(--text);font-family:monospace;font-size:0.85rem">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;text-transform:uppercase;color:var(--muted);margin-bottom:6px">Phone Number ID</label>
                        <input type="text" class="phone-id" data-email="${admin.email}"
                               value="${cfg.phone_number_id || ''}" placeholder="1225619913971231"
                               style="width:100%;background:rgba(10,10,10,0.5);border:1px solid var(--border);border-radius:8px;padding:8px 10px;color:var(--text);font-family:monospace;font-size:0.85rem">
                    </div>
                </div>
                ${cfg.updated_at ? `<div style="font-size:0.8rem;color:#22c55e">✓ Actualizado: ${cfg.updated_at}</div>` :
                  `<div style="font-size:0.8rem;color:#ef4444">⚠ Sin configurar</div>`}
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

    let savedCount = 0;
    for (const [email, data] of Object.entries(configs)) {
        try {
            const resp = await fetch('/admin/api/area-dev.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ admin_email: email, ...data })
            });
            if (resp.ok) savedCount++;
        } catch (e) {
            console.error('Error guardando ' + email, e);
        }
    }

    alert('✓ ' + savedCount + ' configuraciones guardadas');
    renderConfigs();
}

renderConfigs();
</script>

<?php
include __DIR__ . '/../../views/admin/footer.php';
