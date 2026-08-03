<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$db = getDB();

// Leads with phone for quick-add
$leads = $db->query("SELECT id, nombre, whatsapp FROM leads ORDER BY nombre ASC")->fetchAll();

$pageTitle = 'Pipeline Comercial';
include __DIR__ . '/../../views/admin/header.php';

$etapas = [
    'nuevo'      => ['label' => 'Nuevo Lead',  'color' => '#06b6d4', 'icon' => '🎯'],
    'contactado' => ['label' => 'Contactado',   'color' => '#f59e0b', 'icon' => '📞'],
    'reunion'    => ['label' => 'Reunión',       'color' => '#8b5cf6', 'icon' => '🤝'],
    'propuesta'  => ['label' => 'Propuesta',     'color' => '#3b82f6', 'icon' => '📄'],
    'ganado'     => ['label' => 'Ganado',        'color' => '#22c55e', 'icon' => '🏆'],
    'perdido'    => ['label' => 'Perdido',       'color' => '#ef4444', 'icon' => '❌'],
];

$tiposActividad = [
    'nota'     => ['label' => 'Nota',     'icon' => '📝'],
    'llamada'  => ['label' => 'Llamada',  'icon' => '📞'],
    'reunion'  => ['label' => 'Reunión',  'icon' => '🤝'],
    'email'    => ['label' => 'Email',    'icon' => '📧'],
    'whatsapp' => ['label' => 'WhatsApp', 'icon' => '💬'],
];
?>

<style>
/* ── Layout ── */
.pipeline-wrap { overflow-x: auto; padding-bottom: 24px; }
.pipeline-board {
    display: flex; gap: 14px;
    min-width: max-content;
    align-items: flex-start;
}

/* ── Column ── */
.pipe-col {
    width: 260px; flex-shrink: 0;
    display: flex; flex-direction: column; gap: 10px;
}
.pipe-col-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    border-top: 3px solid var(--col-color);
}
.pipe-col-title {
    display: flex; align-items: center; gap: 8px;
    font-size: .82rem; font-weight: 700; color: var(--text);
}
.pipe-col-count {
    font-size: .72rem; font-weight: 700;
    background: var(--surface2); color: var(--text-muted);
    padding: 2px 8px; border-radius: 50px;
}
.pipe-col-valor {
    font-size: .7rem; color: var(--muted); margin-top: 2px;
    font-weight: 600;
}

/* Drop zone */
.pipe-cards {
    min-height: 80px;
    display: flex; flex-direction: column; gap: 8px;
    padding: 4px 0;
    border-radius: 8px;
    transition: background .2s;
}
.pipe-cards.drag-over { background: rgba(6,182,212,.06); }

/* ── Card ── */
.pipe-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 14px;
    cursor: grab;
    transition: box-shadow .2s, transform .15s, border-color .2s;
    position: relative;
    user-select: none;
}
.pipe-card:active { cursor: grabbing; }
.pipe-card.dragging { opacity: .4; transform: scale(.98); }
.pipe-card:hover { border-color: rgba(255,255,255,.15); box-shadow: 0 4px 16px rgba(0,0,0,.3); }

.pipe-card-titulo {
    font-size: .85rem; font-weight: 700; color: var(--text);
    margin-bottom: 4px; line-height: 1.3;
}
.pipe-card-cliente {
    font-size: .75rem; color: var(--muted); margin-bottom: 8px;
}
.pipe-card-valor {
    font-size: .8rem; font-weight: 700; color: #22c55e;
    margin-bottom: 8px;
}
.pipe-card-footer {
    display: flex; align-items: center; justify-content: space-between;
    gap: 6px;
}
.pipe-card-days {
    font-size: .68rem; color: var(--muted);
    background: var(--surface2); padding: 2px 8px; border-radius: 50px;
}
.pipe-card-actions { display: flex; gap: 4px; }
.pipe-card-btn {
    background: none; border: 1px solid var(--border);
    color: var(--muted); border-radius: 6px;
    padding: 3px 7px; cursor: pointer; font-size: .7rem;
    transition: background .15s, color .15s;
}
.pipe-card-btn:hover { background: var(--surface2); color: var(--text); }

/* Add card btn */
.pipe-add-btn {
    display: flex; align-items: center; gap: 6px;
    width: 100%; background: none;
    border: 1px dashed var(--border);
    color: var(--muted); border-radius: 10px;
    padding: 10px 14px; cursor: pointer; font-size: .8rem;
    transition: border-color .2s, color .2s, background .2s;
    font-family: var(--font);
}
.pipe-add-btn:hover { border-color: var(--accent); color: var(--accent); background: rgba(6,182,212,.04); }

/* ── Stats bar ── */
.pipe-stats {
    display: flex; gap: 16px; flex-wrap: wrap;
    margin-bottom: 24px;
}
.pipe-stat {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 10px; padding: 14px 20px;
    display: flex; flex-direction: column; gap: 4px;
    flex: 1; min-width: 120px;
}
.pipe-stat-val { font-size: 1.3rem; font-weight: 800; color: #fff; }
.pipe-stat-lbl { font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }

/* ── Modal ── */
.pipe-modal-bg {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.75); backdrop-filter: blur(4px);
    z-index: 1000; display: none;
    align-items: center; justify-content: center; padding: 20px;
}
.pipe-modal-bg.open { display: flex; }
.pipe-modal {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 16px; padding: 28px;
    width: 100%; max-width: 520px;
    max-height: 90vh; overflow-y: auto;
}
.pipe-modal h3 { font-size: 1.1rem; font-weight: 800; margin-bottom: 20px; }
.pipe-modal .form-group { margin-bottom: 14px; }
.pipe-modal label { font-size: .78rem; color: var(--muted); display: block; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.pipe-modal input,
.pipe-modal select,
.pipe-modal textarea {
    width: 100%; background: var(--bg);
    border: 1px solid var(--border); border-radius: 8px;
    padding: 10px 12px; color: var(--text); font-size: .88rem;
    font-family: var(--font);
}
.pipe-modal textarea { min-height: 80px; resize: vertical; }
.pipe-modal .modal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.pipe-modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

/* ── Detail Panel ── */
.detail-panel-bg {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.6); backdrop-filter: blur(3px);
    z-index: 1000; display: none;
}
.detail-panel-bg.open { display: block; }
.detail-panel {
    position: fixed; right: 0; top: 0; bottom: 0;
    width: 420px; max-width: 100vw;
    background: var(--bg2); border-left: 1px solid var(--border);
    z-index: 1001; overflow-y: auto;
    transform: translateX(100%); transition: transform .3s ease;
    display: flex; flex-direction: column;
}
.detail-panel.open { transform: translateX(0); }
.detail-panel-head {
    padding: 20px 22px; border-bottom: 1px solid var(--border);
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    position: sticky; top: 0; background: var(--bg2); z-index: 1;
}
.detail-panel-head h3 { font-size: 1rem; font-weight: 800; line-height: 1.3; }
.detail-close { background: none; border: none; color: var(--muted); font-size: 1.4rem; cursor: pointer; padding: 2px 6px; }
.detail-close:hover { color: #fff; }
.detail-body { padding: 20px 22px; flex: 1; }
.detail-section { margin-bottom: 24px; }
.detail-section-title {
    font-size: .7rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .1em; color: var(--muted); margin-bottom: 12px;
    padding-bottom: 8px; border-bottom: 1px solid var(--border);
}

/* Activities */
.activity-item {
    display: flex; gap: 10px; margin-bottom: 12px;
}
.activity-icon {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--surface); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; flex-shrink: 0;
}
.activity-body { flex: 1; }
.activity-desc { font-size: .83rem; color: var(--text); line-height: 1.4; }
.activity-meta { font-size: .7rem; color: var(--muted); margin-top: 3px; }

/* Tasks */
.tarea-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 10px; background: var(--surface);
    border: 1px solid var(--border); border-radius: 8px; margin-bottom: 6px;
}
.tarea-check {
    width: 18px; height: 18px; border-radius: 4px;
    border: 2px solid var(--border); background: none;
    cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s, border-color .2s;
}
.tarea-check.done { background: #22c55e; border-color: #22c55e; }
.tarea-titulo { font-size: .82rem; color: var(--text); flex: 1; }
.tarea-titulo.done { text-decoration: line-through; color: var(--muted); }
.tarea-date { font-size: .7rem; color: var(--muted); }

/* Add activity form */
.quick-add {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 10px; padding: 14px;
}
.quick-add textarea {
    width: 100%; background: var(--bg);
    border: 1px solid var(--border); border-radius: 8px;
    padding: 8px 10px; color: var(--text); font-size: .83rem;
    font-family: var(--font); resize: none; min-height: 60px;
    margin-bottom: 10px;
}
.quick-add-row { display: flex; gap: 8px; flex-wrap: wrap; }
.tipo-btn {
    background: var(--bg); border: 1px solid var(--border);
    color: var(--muted); border-radius: 8px;
    padding: 5px 12px; cursor: pointer; font-size: .75rem;
    font-family: var(--font); transition: all .15s;
}
.tipo-btn.active, .tipo-btn:hover { background: var(--accent); color: #000; border-color: var(--accent); font-weight: 700; }
</style>

<!-- Stats -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div>
        <h1 style="font-size:1.4rem;font-weight:800">Pipeline Comercial</h1>
        <p style="color:var(--muted);font-size:.85rem;margin-top:4px">Gestión visual de oportunidades</p>
    </div>
    <button class="btn btn-accent" onclick="openNewOp()">+ Nueva oportunidad</button>
</div>

<div class="pipe-stats" id="pipeStats"></div>

<!-- Board -->
<div class="pipeline-wrap">
    <div class="pipeline-board" id="pipelineBoard">
        <?php foreach ($etapas as $key => $e): ?>
        <div class="pipe-col" data-etapa="<?= $key ?>">
            <div class="pipe-col-head" style="--col-color:<?= $e['color'] ?>">
                <div>
                    <div class="pipe-col-title">
                        <span><?= $e['icon'] ?></span>
                        <span><?= $e['label'] ?></span>
                    </div>
                    <div class="pipe-col-valor" id="val-<?= $key ?>">$0</div>
                </div>
                <span class="pipe-col-count" id="cnt-<?= $key ?>">0</span>
            </div>
            <div class="pipe-cards" id="col-<?= $key ?>"
                 ondragover="event.preventDefault();this.classList.add('drag-over')"
                 ondragleave="this.classList.remove('drag-over')"
                 ondrop="dropCard(event,'<?= $key ?>')">
            </div>
            <button class="pipe-add-btn" onclick="openNewOp('<?= $key ?>')">+ Agregar</button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Modal nueva oportunidad ── -->
<div class="pipe-modal-bg" id="modalBg">
    <div class="pipe-modal">
        <h3 id="modalTitle">Nueva Oportunidad</h3>
        <input type="hidden" id="opId">
        <div class="form-group">
            <label>Título / Proyecto</label>
            <input type="text" id="opTitulo" placeholder="Ej: Rediseño web para Empresa X">
        </div>
        <div class="modal-row">
            <div class="form-group">
                <label>Cliente</label>
                <input type="text" id="opCliente" placeholder="Nombre del cliente">
            </div>
            <div class="form-group">
                <label>Lead (opcional)</label>
                <select id="opLead">
                    <option value="">— Sin lead —</option>
                    <?php foreach($leads as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-row">
            <div class="form-group">
                <label>Valor estimado</label>
                <input type="number" id="opValor" placeholder="0" min="0">
            </div>
            <div class="form-group">
                <label>Moneda</label>
                <select id="opMoneda">
                    <option value="USD">USD</option>
                    <option value="ARS">ARS</option>
                    <option value="EUR">EUR</option>
                </select>
            </div>
        </div>
        <div class="modal-row">
            <div class="form-group">
                <label>Etapa</label>
                <select id="opEtapa">
                    <?php foreach($etapas as $k => $e): ?>
                    <option value="<?= $k ?>"><?= $e['icon'] ?> <?= $e['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Probabilidad %</label>
                <input type="number" id="opProb" placeholder="0" min="0" max="100">
            </div>
        </div>
        <div class="form-group">
            <label>Notas</label>
            <textarea id="opNotas" placeholder="Notas iniciales sobre esta oportunidad..."></textarea>
        </div>
        <div id="motivoRow" class="form-group" style="display:none">
            <label>Motivo de pérdida</label>
            <select id="opMotivo">
                <option value="">Seleccioná un motivo</option>
                <option>Precio muy alto</option>
                <option>Eligió a la competencia</option>
                <option>Proyecto cancelado</option>
                <option>Sin respuesta</option>
                <option>No era el momento</option>
                <option>Otro</option>
            </select>
        </div>
        <div class="pipe-modal-footer">
            <button class="btn" onclick="closeModal()" style="background:var(--surface2)">Cancelar</button>
            <button class="btn btn-accent" onclick="saveOp()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Detail Panel ── -->
<div class="detail-panel-bg" id="detailBg" onclick="closeDetail()"></div>
<div class="detail-panel" id="detailPanel">
    <div class="detail-panel-head">
        <div>
            <h3 id="detailTitulo">—</h3>
            <div id="detailCliente" style="font-size:.78rem;color:var(--muted);margin-top:4px"></div>
        </div>
        <button class="detail-close" onclick="closeDetail()">✕</button>
    </div>
    <div class="detail-body">

        <!-- Info -->
        <div class="detail-section">
            <div class="detail-section-title">Detalles</div>
            <div id="detailInfo" style="font-size:.83rem;color:var(--muted);line-height:1.8"></div>
        </div>

        <!-- Tasks -->
        <div class="detail-section">
            <div class="detail-section-title" style="display:flex;align-items:center;justify-content:space-between">
                <span>Tareas</span>
                <button class="pipe-card-btn" onclick="showAddTask()">+ Agregar</button>
            </div>
            <div id="detailTareas"></div>
            <div id="addTaskForm" style="display:none;margin-top:8px">
                <div style="display:flex;gap:8px">
                    <input type="text" id="newTaskTitulo" placeholder="Nueva tarea..."
                           style="flex:1;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:8px 10px;color:var(--text);font-size:.83rem">
                    <input type="date" id="newTaskFecha"
                           style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:8px 10px;color:var(--text);font-size:.83rem">
                    <button class="btn btn-sm btn-accent" onclick="saveTask()">OK</button>
                </div>
            </div>
        </div>

        <!-- Activities -->
        <div class="detail-section">
            <div class="detail-section-title">Actividades</div>
            <div class="quick-add" style="margin-bottom:14px">
                <div class="quick-add-row" style="margin-bottom:8px">
                    <?php foreach($tiposActividad as $k => $t): ?>
                    <button class="tipo-btn <?= $k==='nota'?'active':'' ?>" data-tipo="<?= $k ?>" onclick="selectTipo(this)"><?= $t['icon'] ?> <?= $t['label'] ?></button>
                    <?php endforeach; ?>
                </div>
                <textarea id="actDesc" placeholder="Escribí lo que ocurrió..."></textarea>
                <button class="btn btn-sm btn-accent" onclick="saveActivity()" style="width:100%">Registrar actividad</button>
            </div>
            <div id="detailActividades"></div>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:8px;padding-top:16px;border-top:1px solid var(--border)">
            <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2)" onclick="deleteOp()">🗑 Eliminar</button>
            <button class="btn btn-sm btn-accent" onclick="editOp()">✏ Editar</button>
        </div>
    </div>
</div>

<script>
const API = '<?= ADMIN_URL ?>/api/pipeline.php';
let currentOpId = null;
let dragId = null;

// ── Load board ────────────────────────────────────────────────────────────────
async function loadBoard() {
    const r = await fetch(API + '?action=get');
    const d = await r.json();
    if (!d.ok) return;

    const etapas = ['nuevo','contactado','reunion','propuesta','ganado','perdido'];
    let totalVal = 0, totalCnt = 0, ganado = 0, perdido = 0;

    etapas.forEach(e => {
        const col   = document.getElementById('col-' + e);
        const cards = d.data[e] || [];
        col.innerHTML = cards.map(c => cardHTML(c)).join('');
        document.getElementById('cnt-' + e).textContent = cards.length;
        const val = d.totals[e]?.valor || 0;
        document.getElementById('val-' + e).textContent = '$' + val.toLocaleString('es-AR');
        if (e !== 'perdido') { totalVal += val; totalCnt += cards.length; }
        if (e === 'ganado')  ganado = val;
        if (e === 'perdido') perdido = cards.length;
    });

    document.getElementById('pipeStats').innerHTML = `
        <div class="pipe-stat"><div class="pipe-stat-val">${totalCnt}</div><div class="pipe-stat-lbl">Oportunidades activas</div></div>
        <div class="pipe-stat"><div class="pipe-stat-val" style="color:#22c55e">$${totalVal.toLocaleString('es-AR')}</div><div class="pipe-stat-lbl">Pipeline total</div></div>
        <div class="pipe-stat"><div class="pipe-stat-val" style="color:#22c55e">$${ganado.toLocaleString('es-AR')}</div><div class="pipe-stat-lbl">Ganado</div></div>
        <div class="pipe-stat"><div class="pipe-stat-val" style="color:#f87171">${perdido}</div><div class="pipe-stat-lbl">Perdidos</div></div>
    `;
}

function cardHTML(c) {
    const dias = Math.floor((Date.now() - new Date(c.updated_at).getTime()) / 86400000);
    const val  = c.valor > 0 ? `<div class="pipe-card-valor">$${Number(c.valor).toLocaleString('es-AR')} ${c.moneda}</div>` : '';
    return `
        <div class="pipe-card" draggable="true" data-id="${c.id}"
             ondragstart="dragStart(event,${c.id})"
             ondragend="dragEnd(event)">
            <div class="pipe-card-titulo">${escHtml(c.titulo)}</div>
            ${c.cliente ? `<div class="pipe-card-cliente">👤 ${escHtml(c.cliente)}</div>` : ''}
            ${val}
            <div class="pipe-card-footer">
                <span class="pipe-card-days">${dias === 0 ? 'Hoy' : dias + 'd'}</span>
                <div class="pipe-card-actions">
                    <button class="pipe-card-btn" onclick="event.stopPropagation();openDetail(${c.id})">📋</button>
                </div>
            </div>
        </div>`;
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Drag & Drop ───────────────────────────────────────────────────────────────
function dragStart(e, id) {
    dragId = id;
    e.target.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}
function dragEnd(e) { e.target.classList.remove('dragging'); }

async function dropCard(e, etapa) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    if (!dragId) return;

    // Check if moving to "perdido" — show reason
    if (etapa === 'perdido') {
        const motivo = prompt('¿Motivo de pérdida?\n\n1. Precio muy alto\n2. Eligió a la competencia\n3. Proyecto cancelado\n4. Sin respuesta\n5. No era el momento\n6. Otro\n\nEscribí el motivo:');
        if (motivo === null) return;
        await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'update', id:dragId, motivo_perdida:motivo}) });
    }

    await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'move', id:dragId, etapa}) });
    dragId = null;
    loadBoard();
}

// ── New / Edit modal ──────────────────────────────────────────────────────────
function openNewOp(etapa = 'nuevo') {
    document.getElementById('opId').value = '';
    document.getElementById('opTitulo').value = '';
    document.getElementById('opCliente').value = '';
    document.getElementById('opLead').value = '';
    document.getElementById('opValor').value = '';
    document.getElementById('opMoneda').value = 'USD';
    document.getElementById('opEtapa').value = etapa;
    document.getElementById('opProb').value = '';
    document.getElementById('opNotas').value = '';
    document.getElementById('modalTitle').textContent = 'Nueva Oportunidad';
    document.getElementById('motivoRow').style.display = 'none';
    document.getElementById('modalBg').classList.add('open');
}

function closeModal() { document.getElementById('modalBg').classList.remove('open'); }

document.getElementById('opEtapa').addEventListener('change', function() {
    document.getElementById('motivoRow').style.display = this.value === 'perdido' ? 'block' : 'none';
});

async function saveOp() {
    const id     = document.getElementById('opId').value;
    const titulo = document.getElementById('opTitulo').value.trim();
    if (!titulo) { alert('El título es requerido'); return; }

    const payload = {
        titulo,
        cliente:        document.getElementById('opCliente').value,
        lead_id:        document.getElementById('opLead').value || null,
        etapa:          document.getElementById('opEtapa').value,
        valor:          document.getElementById('opValor').value || 0,
        moneda:         document.getElementById('opMoneda').value,
        probabilidad:   document.getElementById('opProb').value || 0,
        notas:          document.getElementById('opNotas').value,
        motivo_perdida: document.getElementById('opMotivo').value,
    };

    if (id) { payload.action = 'update'; payload.id = id; }
    else     { payload.action = 'create'; }

    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const json = await res.json();
    if (!json.ok) { alert('Error al guardar: ' + (json.msg || 'desconocido')); return; }
    closeModal();
    loadBoard();
    if (id && currentOpId == id) openDetail(id);
}

// ── Detail Panel ──────────────────────────────────────────────────────────────
async function openDetail(id) {
    currentOpId = id;
    const r = await fetch(API + '?action=detail&id=' + id);
    const d = await r.json();
    if (!d.ok) return;
    const op = d.op;

    document.getElementById('detailTitulo').textContent = op.titulo;
    document.getElementById('detailCliente').textContent = op.cliente || '';
    document.getElementById('detailInfo').innerHTML = `
        <div><strong>Etapa:</strong> ${op.etapa}</div>
        <div><strong>Valor:</strong> $${Number(op.valor).toLocaleString('es-AR')} ${op.moneda}</div>
        ${op.probabilidad ? `<div><strong>Probabilidad:</strong> ${op.probabilidad}%</div>` : ''}
        ${op.notas ? `<div style="margin-top:8px;padding:10px;background:var(--surface);border-radius:8px;font-size:.82rem">${escHtml(op.notas)}</div>` : ''}
        ${op.motivo_perdida ? `<div style="margin-top:6px;color:#f87171"><strong>Motivo pérdida:</strong> ${escHtml(op.motivo_perdida)}</div>` : ''}
    `;

    renderTareas(d.tareas);
    renderActividades(d.actividades);

    document.getElementById('detailBg').classList.add('open');
    document.getElementById('detailPanel').classList.add('open');
}

function closeDetail() {
    document.getElementById('detailBg').classList.remove('open');
    document.getElementById('detailPanel').classList.remove('open');
    currentOpId = null;
}

function renderTareas(tareas) {
    document.getElementById('detailTareas').innerHTML = tareas.length
        ? tareas.map(t => `
            <div class="tarea-item">
                <div class="tarea-check ${t.completada=='1'?'done':''}" onclick="toggleTask(${t.id})">
                    ${t.completada=='1' ? '✓' : ''}
                </div>
                <span class="tarea-titulo ${t.completada=='1'?'done':''}">${escHtml(t.titulo)}</span>
                ${t.fecha_limite ? `<span class="tarea-date">${t.fecha_limite}</span>` : ''}
            </div>`).join('')
        : '<p style="color:var(--muted);font-size:.8rem">Sin tareas</p>';
}

function renderActividades(acts) {
    const icons = {nota:'📝',llamada:'📞',reunion:'🤝',email:'📧',whatsapp:'💬'};
    document.getElementById('detailActividades').innerHTML = acts.length
        ? acts.map(a => `
            <div class="activity-item">
                <div class="activity-icon">${icons[a.tipo]||'📝'}</div>
                <div class="activity-body">
                    <div class="activity-desc">${escHtml(a.descripcion)}</div>
                    <div class="activity-meta">${a.fecha || a.created_at}</div>
                </div>
            </div>`).join('')
        : '<p style="color:var(--muted);font-size:.8rem">Sin actividades registradas</p>';
}

function selectTipo(btn) {
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

async function saveActivity() {
    const desc = document.getElementById('actDesc').value.trim();
    if (!desc || !currentOpId) return;
    const tipo = document.querySelector('.tipo-btn.active')?.dataset.tipo || 'nota';
    await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'add_actividad', oportunidad_id:currentOpId, tipo, descripcion:desc}) });
    document.getElementById('actDesc').value = '';
    const r = await fetch(API + '?action=detail&id=' + currentOpId);
    const d = await r.json();
    renderActividades(d.actividades);
}

function showAddTask() {
    const f = document.getElementById('addTaskForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

async function saveTask() {
    const titulo = document.getElementById('newTaskTitulo').value.trim();
    if (!titulo || !currentOpId) return;
    const fecha = document.getElementById('newTaskFecha').value;
    await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'add_tarea', oportunidad_id:currentOpId, titulo, fecha_limite:fecha||null}) });
    document.getElementById('newTaskTitulo').value = '';
    document.getElementById('newTaskFecha').value = '';
    document.getElementById('addTaskForm').style.display = 'none';
    const r = await fetch(API + '?action=detail&id=' + currentOpId);
    const d = await r.json();
    renderTareas(d.tareas);
}

async function toggleTask(id) {
    await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'toggle_tarea', id}) });
    const r = await fetch(API + '?action=detail&id=' + currentOpId);
    const d = await r.json();
    renderTareas(d.tareas);
}

async function deleteOp() {
    if (!currentOpId || !confirm('¿Eliminar esta oportunidad?')) return;
    await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'delete', id:currentOpId}) });
    closeDetail();
    loadBoard();
}

function editOp() {
    if (!currentOpId) return;
    closeDetail();
    // re-open modal with current data
    fetch(API + '?action=detail&id=' + currentOpId).then(r=>r.json()).then(d=>{
        const op = d.op;
        document.getElementById('opId').value = op.id;
        document.getElementById('opTitulo').value = op.titulo;
        document.getElementById('opCliente').value = op.cliente || '';
        document.getElementById('opLead').value = op.lead_id || '';
        document.getElementById('opValor').value = op.valor;
        document.getElementById('opMoneda').value = op.moneda;
        document.getElementById('opEtapa').value = op.etapa;
        document.getElementById('opProb').value = op.probabilidad;
        document.getElementById('opNotas').value = op.notas || '';
        document.getElementById('opMotivo').value = op.motivo_perdida || '';
        document.getElementById('motivoRow').style.display = op.etapa === 'perdido' ? 'block' : 'none';
        document.getElementById('modalTitle').textContent = 'Editar Oportunidad';
        document.getElementById('modalBg').classList.add('open');
    });
}

// Click on card opens detail
document.getElementById('pipelineBoard').addEventListener('click', function(e) {
    const card = e.target.closest('.pipe-card');
    if (card && !e.target.closest('.pipe-card-btn')) openDetail(card.dataset.id);
});

loadBoard();
</script>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
