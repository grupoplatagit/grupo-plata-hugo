<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();
$pageTitle = 'Prospectos';
include __DIR__ . '/../../views/admin/header.php';
?>
<style>
.estado-badge { display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;cursor:pointer;border:none;font-family:var(--font); }
.estado-nuevo        { background:rgba(107,114,128,.15);color:#9ca3af; }
.estado-contactado   { background:rgba(6,182,212,.15);color:#22d3ee; }
.estado-respondio    { background:rgba(245,158,11,.15);color:#fbbf24; }
.estado-demo_enviada { background:rgba(139,92,246,.15);color:#a78bfa; }
.estado-ganado       { background:rgba(34,197,94,.15);color:#4ade80; }
.estado-descartado   { background:rgba(239,68,68,.1);color:#f87171; }
.estado-select { border:none;border-radius:20px;padding:3px 8px;font-size:.7rem;font-weight:700;cursor:pointer;font-family:var(--font);outline:none; }
.pro-actions { display:flex;gap:6px;align-items:center; }
.btn-wa { background:rgba(37,211,102,.12);border:1px solid rgba(37,211,102,.3);color:#25d366;border-radius:8px;padding:4px 10px;font-size:.72rem;font-weight:700;cursor:pointer;font-family:var(--font);transition:all .15s; }
.btn-wa:hover { background:rgba(37,211,102,.25); }
.stat-pill { background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 20px;text-align:center; }
.stat-pill .num { font-size:1.6rem;font-weight:800;line-height:1; }
.stat-pill .lbl { font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-top:4px; }
.notes-cell { max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted);font-size:.78rem; }
.filter-bar { display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center; }
.fpill { padding:5px 14px;border-radius:20px;font-size:.75rem;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--bg);color:var(--muted);transition:all .15s;font-family:var(--font); }
.fpill:hover,.fpill.active { background:var(--accent);border-color:var(--accent);color:#000; }
.search-input { background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:7px 14px;color:var(--text);font-size:.83rem;font-family:var(--font);outline:none;transition:border-color .2s; }
.search-input:focus { border-color:var(--accent); }
</style>

<!-- Stats -->
<div id="statsRow" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:12px;margin-bottom:24px"></div>

<!-- Toolbar -->
<div class="filter-bar">
    <input type="text" class="search-input" id="searchInput" placeholder="Buscar nombre, zona..." oninput="loadProspectos()" style="width:220px">
    <button class="fpill active" data-e="todos" onclick="setFiltro('todos',this)">Todos</button>
    <button class="fpill" data-e="nuevo"        onclick="setFiltro('nuevo',this)">Nuevos</button>
    <button class="fpill" data-e="contactado"   onclick="setFiltro('contactado',this)">Contactados</button>
    <button class="fpill" data-e="respondio"    onclick="setFiltro('respondio',this)">Respondieron</button>
    <button class="fpill" data-e="demo_enviada" onclick="setFiltro('demo_enviada',this)">Demo enviada</button>
    <button class="fpill" data-e="ganado"       onclick="setFiltro('ganado',this)">Ganados</button>
    <button class="fpill" data-e="descartado"   onclick="setFiltro('descartado',this)">Descartados</button>
    <button class="fpill" data-web="sin"        onclick="setFiltroWeb('sin',this)" style="border-color:rgba(251,191,36,.4);color:#fbbf24">🔥 Sin web</button>
    <button class="fpill" data-web="con"        onclick="setFiltroWeb('con',this)" style="border-color:rgba(34,197,94,.3);color:#22c55e">✓ Con web</button>
    <div style="margin-left:auto;display:flex;gap:8px">
        <button onclick="enriquecer()" class="btn btn-sm" style="background:rgba(34,197,94,.1);color:#22c55e;border:1px solid rgba(34,197,94,.25)">✨ Enriquecer web/IG</button>
        <button onclick="importar200()" class="btn btn-sm" style="background:rgba(139,92,246,.12);color:#a78bfa;border:1px solid rgba(139,92,246,.25)">&#8659; Importar 200</button>
        <button onclick="importar()" class="btn btn-sm" style="background:rgba(6,182,212,.1);color:var(--accent);border:1px solid rgba(6,182,212,.25)">&#8659; Importar JSON</button>
        <button onclick="openAgregar()" class="btn btn-sm btn-accent">+ Agregar</button>
        <button onclick="runSecuencia()" id="btnRun" class="btn btn-sm" style="background:rgba(37,211,102,.12);color:#25d366;border:1px solid rgba(37,211,102,.3)">&#9654; Enviar 3 ahora</button>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table id="proTable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Zona</th>
                        <th>Teléfono</th>
                        <th>Web</th>
                        <th>Estado</th>
                        <th>Notas</th>
                        <th>Último contacto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="proBody">
                    <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Agregar modal -->
<div id="agregarModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:200;align-items:center;justify-content:center;backdrop-filter:blur(6px)">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;width:440px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4)">
        <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <h3 style="font-size:.95rem;font-weight:800">+ Agregar prospecto</h3>
            <button onclick="closeAgregar()" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:var(--muted);font-size:.8rem;cursor:pointer;padding:5px 8px;border-radius:8px">✕</button>
        </div>
        <div style="padding:18px;display:flex;flex-direction:column;gap:12px">
            <div><label style="display:block;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px">Nombre *</label>
            <input id="ag_nombre" type="text" placeholder="Estudio Contable García" style="width:100%;background:var(--bg);border:1px solid var(--border);border-radius:9px;padding:9px 14px;color:var(--text);font-family:var(--font);font-size:.88rem;outline:none"></div>
            <div><label style="display:block;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px">Teléfono * (con código de país)</label>
            <input id="ag_tel" type="text" placeholder="+54 9 11 1234-5678" style="width:100%;background:var(--bg);border:1px solid var(--border);border-radius:9px;padding:9px 14px;color:var(--text);font-family:var(--font);font-size:.88rem;outline:none"></div>
            <div><label style="display:block;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px">Zona</label>
            <input id="ag_zona" type="text" placeholder="Belgrano, CABA" style="width:100%;background:var(--bg);border:1px solid var(--border);border-radius:9px;padding:9px 14px;color:var(--text);font-family:var(--font);font-size:.88rem;outline:none"></div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px">
                <button onclick="closeAgregar()" class="btn" style="background:var(--surface2);color:var(--text)">Cancelar</button>
                <button onclick="guardarAgregar()" class="btn btn-accent">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Notes modal -->
<div id="notesModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:200;align-items:center;justify-content:center;backdrop-filter:blur(6px)">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;width:460px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4)">
        <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <h3 id="notesTitle" style="font-size:.95rem;font-weight:800"></h3>
            <button onclick="closeNotes()" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:var(--muted);font-size:.8rem;cursor:pointer;padding:5px 8px;border-radius:8px">✕</button>
        </div>
        <div style="padding:18px">
            <textarea id="notesText" rows="5" style="width:100%;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:10px 14px;color:var(--text);font-family:var(--font);font-size:.88rem;resize:none;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px">
                <button onclick="closeNotes()" class="btn" style="background:var(--surface2);color:var(--text)">Cancelar</button>
                <button onclick="saveNotes()" class="btn btn-accent">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
const API = '<?= ADMIN_URL ?>/api/prospectos.php';
const WA_INBOX = '<?= ADMIN_URL ?>/pages/inbox.php';
let filtroActivo = 'todos';
let filtroWeb    = 'todos'; // 'todos' | 'sin' | 'con'
let notesId = null;

const ESTADOS = {
    nuevo:        { label:'Nuevo',        cls:'estado-nuevo' },
    contactado:   { label:'Contactado',   cls:'estado-contactado' },
    respondio:    { label:'Respondió',    cls:'estado-respondio' },
    demo_enviada: { label:'Demo enviada', cls:'estado-demo_enviada' },
    ganado:       { label:'Ganado ✓',    cls:'estado-ganado' },
    descartado:   { label:'Descartado',   cls:'estado-descartado' },
};

function setFiltro(e, btn) {
    filtroActivo = e;
    document.querySelectorAll('.fpill[data-e]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadProspectos();
}

function setFiltroWeb(w, btn) {
    filtroWeb = filtroWeb === w ? 'todos' : w;
    document.querySelectorAll('.fpill[data-web]').forEach(b => b.classList.remove('active'));
    if (filtroWeb !== 'todos') btn.classList.add('active');
    loadProspectos();
}

function loadProspectos() {
    const q = document.getElementById('searchInput')?.value || '';
    const e = filtroActivo !== 'todos' ? `&estado=${filtroActivo}` : '';
    const w = filtroWeb   !== 'todos' ? `&web=${filtroWeb}`   : '';
    fetch(`${API}?action=list${e}${w}&q=${encodeURIComponent(q)}`)
        .then(r=>r.json())
        .then(d => {
            renderStats(d.totals || {});
            renderTable(d.data || []);
        });
}

function renderStats(t) {
    const items = [
        { key:'total',        label:'Total',      color:'var(--accent)' },
        { key:'contactado',   label:'Contactados',color:'#22d3ee' },
        { key:'respondio',    label:'Respondieron',color:'#fbbf24' },
        { key:'demo_enviada', label:'Demo enviada',color:'#a78bfa' },
        { key:'ganado',       label:'Ganados',    color:'#4ade80' },
    ];
    document.getElementById('statsRow').innerHTML = items.map(i => `
        <div class="stat-pill">
            <div class="num" style="color:${i.color}">${t[i.key]||0}</div>
            <div class="lbl">${i.label}</div>
        </div>`).join('');
}

function renderTable(rows) {
    const tbody = document.getElementById('proBody');
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px">Sin prospectos en esta categoría</td></tr>';
        return;
    }
    tbody.innerHTML = rows.map(p => {
        const e = ESTADOS[p.estado] || ESTADOS.nuevo;
        const uc = p.ultimo_contacto ? p.ultimo_contacto.substring(0,10) : '—';
        const opciones = Object.entries(ESTADOS).map(([k,v]) =>
            `<option value="${k}" ${k===p.estado?'selected':''}>${v.label}</option>`).join('');
        const telClean = (p.telefono||'').replace(/\D/g,'');
        const waLink   = telClean ? `https://wa.me/${telClean}` : null;
        const igQuery  = encodeURIComponent(p.nombre);
        const igLink   = `https://www.instagram.com/explore/search/?q=${igQuery}`;
        const webBadge = p.web
            ? `<a href="${esc(p.web)}" target="_blank" style="font-size:.68rem;color:#22c55e;font-weight:700" title="${esc(p.web)}">✓ WEB</a>`
            : `<span style="font-size:.68rem;color:var(--muted)">SIN WEB</span>`;
        const dirTip = p.direccion ? ` title="${esc(p.direccion)}"` : '';
        return `<tr>
            <td style="font-weight:700;font-size:.85rem"${dirTip}>${esc(p.nombre)}</td>
            <td style="color:var(--muted);font-size:.8rem">${esc(p.zona||'—')}</td>
            <td style="font-size:.8rem">${esc(p.telefono||'—')}</td>
            <td>${webBadge}</td>
            <td>
                <select class="estado-select ${e.cls}" onchange="cambiarEstado(${p.id},this.value,this)" style="background:${getBg(p.estado)};color:${getColor(p.estado)}">
                    ${opciones}
                </select>
            </td>
            <td class="notes-cell" onclick="openNotes(${p.id},'${esc(p.nombre)}','${esc(p.notas||'').replace(/'/g,"\\'")}',event)" style="cursor:pointer" title="${esc(p.notas||'Clic para agregar nota')}">
                ${p.notas ? esc(p.notas) : '<span style="color:var(--border)">+ agregar nota</span>'}
            </td>
            <td style="font-size:.75rem;color:var(--muted)">${uc}</td>
            <td>
                <div class="pro-actions">
                    ${waLink ? `<a href="${waLink}" target="_blank" style="background:rgba(37,211,102,.12);border:1px solid rgba(37,211,102,.3);color:#25d366;border-radius:8px;padding:4px 8px;font-size:.75rem;text-decoration:none" title="WhatsApp directo">💬</a>` : ''}
                    <a href="${igLink}" target="_blank" style="background:rgba(225,48,108,.1);border:1px solid rgba(225,48,108,.25);color:#e1306c;border-radius:8px;padding:4px 8px;font-size:.75rem;text-decoration:none" title="Buscar en Instagram">📷</a>
                    <button class="btn-wa" onclick="abrirWA('${esc(p.telefono||'')}','${esc(p.nombre)}')" title="Abrir en Inbox">&#128172;</button>
                    <button onclick="enviarAhora(${p.id},'${esc(p.nombre)}')" style="background:rgba(37,211,102,.12);border:1px solid rgba(37,211,102,.3);color:#25d366;border-radius:8px;padding:4px 8px;font-size:.72rem;cursor:pointer;font-family:var(--font)" title="Enviar secuencia ahora">&#9654;</button>
                    <button onclick="eliminar(${p.id})" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#f87171;border-radius:8px;padding:4px 8px;font-size:.72rem;cursor:pointer;font-family:var(--font)" title="Eliminar">✕</button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function getBg(e) {
    const m = {nuevo:'rgba(107,114,128,.15)',contactado:'rgba(6,182,212,.15)',respondio:'rgba(245,158,11,.15)',demo_enviada:'rgba(139,92,246,.15)',ganado:'rgba(34,197,94,.15)',descartado:'rgba(239,68,68,.1)'};
    return m[e]||m.nuevo;
}
function getColor(e) {
    const m = {nuevo:'#9ca3af',contactado:'#22d3ee',respondio:'#fbbf24',demo_enviada:'#a78bfa',ganado:'#4ade80',descartado:'#f87171'};
    return m[e]||m.nuevo;
}

function cambiarEstado(id, estado, sel) {
    sel.style.background = getBg(estado);
    sel.style.color = getColor(estado);
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'estado',id,estado})
    }).then(()=>loadProspectos());
}

function openNotes(id, nombre, notas, e) {
    e?.stopPropagation();
    notesId = id;
    document.getElementById('notesTitle').textContent = nombre;
    document.getElementById('notesText').value = notas;
    document.getElementById('notesModal').style.display = 'flex';
    setTimeout(() => document.getElementById('notesText').focus(), 50);
}
function closeNotes() { document.getElementById('notesModal').style.display='none'; notesId=null; }
function saveNotes() {
    if (!notesId) return;
    const notas = document.getElementById('notesText').value;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'notas',id:notesId,notas})
    }).then(()=>{ closeNotes(); loadProspectos(); });
}

function abrirWA(tel, nombre) {
    if (!tel) { showToast('Sin número de teléfono'); return; }
    window.location.href = WA_INBOX + '?open_phone=' + encodeURIComponent(tel) + '&open_name=' + encodeURIComponent(nombre);
}

function eliminar(id) {
    if (!confirm('¿Eliminar este prospecto?')) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'delete',id})
    }).then(()=>loadProspectos());
}

function enriquecer() {
    if (!confirm('¿Actualizar web e Instagram en los prospectos con datos del agente?\n\nSolo sobreescribe campos vacíos.')) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'enrich'})
    }).then(r=>r.json()).then(d => {
        showToast(d.msg || (d.ok ? 'OK' : 'Error'), d.ok ? 'ok' : 'error');
        if (d.ok) loadProspectos();
    });
}

function importar200() {
    if (!confirm('¿Importar los 200 prospectos del archivo JS?\n\nSe saltarán duplicados y los que no tienen teléfono.')) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'import_200'})
    }).then(r=>r.json()).then(d => {
        showToast(d.msg || (d.ok ? 'Importado' : 'Error'), d.ok ? 'ok' : 'error');
        if (d.ok) loadProspectos();
    });
}

function importar() {
    if (!confirm('¿Importar los prospectos del JSON generado?\n\nLos que ya existan no se duplicarán.')) return;
    fetch(`${API}?action=import`)
        .then(r=>r.json())
        .then(d => {
            showToast(d.msg || (d.ok ? 'Importado' : 'Error'), d.ok ? 'ok' : 'error');
            if (d.ok) loadProspectos();
        });
}

function openAgregar() {
    document.getElementById('ag_nombre').value = '';
    document.getElementById('ag_tel').value = '';
    document.getElementById('ag_zona').value = '';
    document.getElementById('agregarModal').style.display = 'flex';
    setTimeout(() => document.getElementById('ag_nombre').focus(), 50);
}
function closeAgregar() { document.getElementById('agregarModal').style.display = 'none'; }
function guardarAgregar() {
    const nombre = document.getElementById('ag_nombre').value.trim();
    const tel    = document.getElementById('ag_tel').value.trim();
    const zona   = document.getElementById('ag_zona').value.trim();
    if (!nombre || !tel) { showToast('Nombre y teléfono son obligatorios'); return; }
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'agregar', nombre, telefono:tel, zona})
    }).then(r=>r.json()).then(d => {
        if (d.ok) { closeAgregar(); showToast('Prospecto agregado', 'ok'); loadProspectos(); }
        else showToast(d.msg || 'Error');
    });
}

function enviarAhora(id, nombre) {
    if (!confirm(`¿Enviar la secuencia ahora a "${nombre}"?`)) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'enviar_ahora', id})
    }).then(r=>r.json()).then(d => {
        showToast(d.msg || (d.ok ? 'Enviado' : 'Error'), d.ok ? 'ok' : 'error');
        if (d.ok) loadProspectos();
    });
}

async function runSecuencia() {
    const btn = document.getElementById('btnRun');
    btn.disabled = true;
    const TOTAL = 3;
    let enviados = 0;
    for (let i = 0; i < TOTAL; i++) {
        btn.textContent = `⏳ Enviando ${i+1}/${TOTAL}...`;
        try {
            // PHP responde rápido y envía demo_web en background
            const d = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({action:'enviar_apertura'})
            }).then(r=>r.json());
            if (!d.ok) {
                if (d.msg === 'no_more') { showToast('Sin más prospectos nuevos', 'ok'); break; }
                showToast('Error: ' + (d.msg || 'desconocido')); break;
            }
            enviados++;
            showToast(`Enviado a ${d.nombre} ✓`, 'ok');
            if (d.remaining === 0) break;
        } catch(e) { showToast('Error de conexión'); break; }
    }
    if (enviados > 0) loadProspectos();
    btn.disabled = false;
    btn.textContent = '▶ Enviar 3 ahora';
}

function showToast(msg, type='error') {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;top:76px;right:24px;padding:12px 20px;border-radius:10px;font-size:.85rem;font-weight:600;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.35);transition:opacity .35s;background:${type==='ok'?'#22c55e':'#ef4444'};color:#fff;`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(),350); }, 2800);
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

loadProspectos();
</script>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
