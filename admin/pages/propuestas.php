<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
requireLogin();
include __DIR__ . '/../../views/admin/header.php';
?>

<style>
.estado-badge {
    display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
}
.estado-borrador  { background:rgba(148,163,184,.15); color:#94a3b8; }
.estado-enviada   { background:rgba(6,182,212,.15);   color:#06b6d4; }
.estado-vista     { background:rgba(251,191,36,.15);  color:#fbbf24; }
.estado-aceptada  { background:rgba(34,197,94,.15);   color:#22c55e; }
.estado-rechazada { background:rgba(239,68,68,.15);   color:#ef4444; }
.copy-btn { background:rgba(6,182,212,.12);color:#06b6d4;border:1px solid rgba(6,182,212,.3);border-radius:7px;padding:4px 10px;font-size:.72rem;cursor:pointer;font-family:var(--font); }
.copy-btn:hover { background:rgba(6,182,212,.25); }
</style>

<!-- Header -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div>
        <h1 style="font-size:1.4rem;font-weight:700;margin:0">Propuestas</h1>
        <p style="color:var(--muted);font-size:.85rem;margin:4px 0 0">Propuestas comerciales para clientes</p>
    </div>
    <button onclick="openNueva()" class="btn btn-accent">+ Nueva propuesta</button>
</div>

<!-- Table -->
<div class="card">
<table class="table">
    <thead>
        <tr>
            <th>Código</th>
            <th>Cliente / Empresa</th>
            <th>Clave</th>
            <th>Estado</th>
            <th>Vista</th>
            <th>Creada</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="tblBody">
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:32px">Cargando...</td></tr>
    </tbody>
</table>
</div>

<!-- Modal nueva propuesta -->
<div id="modalNueva" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;display:none;align-items:center;justify-content:center">
    <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:32px;width:100%;max-width:480px;position:relative">
        <h2 style="margin:0 0 20px;font-size:1.1rem">Nueva propuesta</h2>
        <div style="display:flex;flex-direction:column;gap:14px">
            <div>
                <label style="font-size:.8rem;color:var(--muted);display:block;margin-bottom:5px">Empresa / Estudio *</label>
                <input id="fEmpresa" type="text" placeholder="Ej: ACP Estudio Jurídico Contable" class="form-input" style="width:100%">
            </div>
            <div>
                <label style="font-size:.8rem;color:var(--muted);display:block;margin-bottom:5px">Nombre del contacto *</label>
                <input id="fNombre" type="text" placeholder="Ej: Dr. Martínez" class="form-input" style="width:100%">
            </div>
            <div>
                <label style="font-size:.8rem;color:var(--muted);display:block;margin-bottom:5px">Clave de acceso <span style="color:var(--muted)">(dejar vacío para autogenerar)</span></label>
                <input id="fClave" type="text" placeholder="Ej: ESTUDIO24" class="form-input" style="width:100%;text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
            </div>
            <div>
                <label style="font-size:.8rem;color:var(--muted);display:block;margin-bottom:5px">Notas internas</label>
                <textarea id="fNotas" placeholder="Notas opcionales..." class="form-input" rows="2" style="width:100%;resize:vertical"></textarea>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:24px">
            <button onclick="crearPropuesta()" class="btn btn-accent" style="flex:1">Crear propuesta</button>
            <button onclick="closeNueva()" class="btn" style="background:var(--surface);color:var(--muted)">Cancelar</button>
        </div>
    </div>
</div>

<script>
const API = '<?= ADMIN_URL ?>/api/propuestas.php';
const BASE_URL = '<?= BASE_URL ?>';

function openNueva() {
    document.getElementById('modalNueva').style.display = 'flex';
    document.getElementById('fEmpresa').focus();
}
function closeNueva() {
    document.getElementById('modalNueva').style.display = 'none';
    ['fEmpresa','fNombre','fClave','fNotas'].forEach(id => document.getElementById(id).value = '');
}

function estadoBadge(e) {
    const map = {borrador:'Borrador',enviada:'Enviada',vista:'Vista ✓',aceptada:'Aceptada ✓',rechazada:'Rechazada'};
    return `<span class="estado-badge estado-${e}">${map[e]||e}</span>`;
}
function fmtDate(d) {
    if (!d) return '—';
    return d.substring(0,10).split('-').reverse().join('/');
}

function loadPropuestas() {
    fetch(`${API}?action=list`).then(r=>r.json()).then(d => {
        const tb = document.getElementById('tblBody');
        if (!d.ok || !d.data.length) {
            tb.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:32px">Sin propuestas aún. Creá la primera.</td></tr>';
            return;
        }
        tb.innerHTML = d.data.map(p => {
            const link = `${BASE_URL}/propuesta/${p.codigo}`;
            return `<tr>
                <td style="font-weight:700;color:var(--accent);font-family:monospace">${esc(p.codigo)}</td>
                <td>
                    <div style="font-weight:600">${esc(p.cliente_empresa)}</div>
                    <div style="font-size:.78rem;color:var(--muted)">${esc(p.cliente_nombre)}</div>
                </td>
                <td>
                    <span style="font-family:monospace;background:rgba(255,255,255,.05);padding:2px 8px;border-radius:5px;font-size:.82rem">${esc(p.clave)}</span>
                </td>
                <td>
                    <select onchange="cambiarEstado(${p.id},this.value)" style="background:transparent;border:none;cursor:pointer;font-size:.8rem;color:inherit">
                        ${['borrador','enviada','vista','aceptada','rechazada'].map(e=>`<option value="${e}" ${e===p.estado?'selected':''}>${e.charAt(0).toUpperCase()+e.slice(1)}</option>`).join('')}
                    </select>
                </td>
                <td style="font-size:.8rem;color:var(--muted)">${p.viewed_at ? '👁 '+fmtDate(p.viewed_at) : '—'}</td>
                <td style="font-size:.8rem;color:var(--muted)">${fmtDate(p.created_at)}</td>
                <td style="display:flex;gap:6px;align-items:center">
                    <button class="copy-btn" onclick="copyLink('${esc(link)}','${esc(p.clave)}')">🔗 Copiar link</button>
                    <a href="${esc(link)}" target="_blank" style="color:var(--muted);font-size:1rem;text-decoration:none" title="Ver propuesta">&#128065;</a>
                    <button onclick="eliminar(${p.id})" style="background:rgba(239,68,68,.1);color:#ef4444;border:1px solid rgba(239,68,68,.2);border-radius:7px;padding:4px 8px;font-size:.72rem;cursor:pointer">&#10005;</button>
                </td>
            </tr>`;
        }).join('');
    });
}

function crearPropuesta() {
    const empresa = document.getElementById('fEmpresa').value.trim();
    const nombre  = document.getElementById('fNombre').value.trim();
    const clave   = document.getElementById('fClave').value.trim();
    const notas   = document.getElementById('fNotas').value.trim();
    if (!empresa || !nombre) { showToast('Empresa y nombre son requeridos'); return; }
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'crear', cliente_empresa:empresa, cliente_nombre:nombre, clave, notas})
    }).then(r=>r.json()).then(d => {
        if (!d.ok) { showToast(d.msg||'Error'); return; }
        closeNueva();
        loadPropuestas();
        const link = `${BASE_URL}/propuesta/${d.propuesta.codigo}`;
        showToast(`Creada ✓ — Clave: ${d.propuesta.clave}`, 'ok');
        setTimeout(() => copyLink(link, d.propuesta.clave, true), 600);
    });
}

function cambiarEstado(id, estado) {
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'estado', id, estado})
    }).then(r=>r.json()).then(d => { if (d.ok) loadPropuestas(); });
}

function eliminar(id) {
    if (!confirm('¿Eliminar esta propuesta?')) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'delete', id})
    }).then(r=>r.json()).then(d => { if (d.ok) loadPropuestas(); });
}

function copyLink(link, clave, silent=false) {
    const text = `🔗 Tu propuesta personalizada:\n${link}\n\n🔑 Clave de acceso: ${clave}`;
    navigator.clipboard.writeText(text).then(() => {
        if (!silent) showToast('Link y clave copiados al portapapeles ✓', 'ok');
    }).catch(() => {
        prompt('Copiá este texto:', text);
    });
}

function showToast(msg, type='error') {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;top:76px;right:24px;padding:12px 20px;border-radius:10px;font-size:.85rem;font-weight:600;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.35);transition:opacity .35s;background:${type==='ok'?'#22c55e':'#ef4444'};color:#fff;`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(),350); }, 3000);
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

document.getElementById('modalNueva').addEventListener('click', e => { if (e.target === e.currentTarget) closeNueva(); });

loadPropuestas();
</script>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
