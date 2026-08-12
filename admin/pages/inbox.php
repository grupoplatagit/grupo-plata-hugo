<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/functions.php';
requireLogin();

$pageTitle = 'Inbox WhatsApp';
include __DIR__ . '/../../views/admin/header.php';
?>
<style>
/* ── Layout ── */
.inbox-wrap { display:grid;grid-template-columns:310px 1fr 280px;height:calc(100vh - 124px);background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 4px 32px rgba(0,0,0,.2); }

/* ── Sidebar ── */
.conv-sidebar { border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;background:var(--surface); }
.conv-sidebar-header { padding:16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px; }
.conv-sidebar-header h2 { font-size:.9rem;font-weight:800;flex:1;letter-spacing:-.2px; }
.conv-search { padding:10px 12px;border-bottom:1px solid var(--border); }
.conv-search input { width:100%;background:var(--bg);border:1px solid transparent;border-radius:10px;padding:8px 14px 8px 36px;color:var(--text);font-size:.8rem;font-family:var(--font);outline:none;transition:all .2s;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%236b7280' viewBox='0 0 24 24'%3E%3Cpath d='M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z' stroke='%236b7280' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:12px center; }
.conv-search input:focus { border-color:rgba(37,211,102,.4);background-color:rgba(37,211,102,.04); }

/* Filter pills */
.filter-pills { display:flex;gap:6px;padding:10px 12px;border-bottom:1px solid var(--border);overflow-x:auto;scrollbar-width:none; }
.filter-pills::-webkit-scrollbar { display:none; }
.filter-pill { padding:4px 11px;border-radius:20px;font-size:.71rem;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--bg);color:var(--muted);white-space:nowrap;transition:all .18s;font-family:var(--font); }
.filter-pill:hover { border-color:var(--text);color:var(--text); }
.filter-pill.active { background:#25d366;border-color:#25d366;color:#000; }
.filter-pill[data-f="potencial"].active  { background:#f59e0b;border-color:#f59e0b;color:#000; }
.filter-pill[data-f="calificado"].active { background:#3b82f6;border-color:#3b82f6;color:#fff; }
.filter-pill[data-f="agendado"].active   { background:#8b5cf6;border-color:#8b5cf6;color:#fff; }
.filter-pill[data-f="cerrado"].active    { background:#10b981;border-color:#10b981;color:#fff; }
.filter-pill[data-f="descartado"].active { background:#ef4444;border-color:#ef4444;color:#fff; }

.conv-list { flex:1;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--border) transparent; }

/* Conversation items */
.conv-item { padding:12px 14px 12px 12px;border-bottom:1px solid rgba(255,255,255,.03);cursor:pointer;transition:background .12s;display:flex;gap:11px;align-items:center;position:relative; }
.conv-item::before { content:'';position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:0 2px 2px 0;opacity:0;transition:opacity .2s; }
.conv-item:hover { background:rgba(255,255,255,.03); }
.conv-item.active { background:rgba(37,211,102,.06); }
.conv-item.active::before { opacity:1;background:#25d366; }

/* Avatar with label dot */
.conv-avatar-wrap { position:relative;flex-shrink:0; }
.conv-avatar { width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.88rem;font-weight:800;color:#fff;letter-spacing:-.5px; }
.conv-label-dot { position:absolute;bottom:1px;right:1px;width:11px;height:11px;border-radius:50%;border:2px solid var(--surface); }
.dot-nuevo      { background:#6b7280; }
.dot-potencial  { background:#f59e0b; }
.dot-calificado { background:#3b82f6; }
.dot-agendado   { background:#8b5cf6; }
.dot-cerrado    { background:#10b981; }
.dot-descartado { background:#ef4444; }

.conv-body { flex:1;min-width:0; }
.conv-name { font-size:.84rem;font-weight:700;color:var(--text);margin-bottom:3px; }
.conv-preview { font-size:.74rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.conv-meta { display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0; }
.conv-time { font-size:.67rem;color:var(--muted); }
.conv-unread { background:#25d366;color:#000;font-size:.62rem;font-weight:800;border-radius:50%;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center;padding:0 3px; }
.conv-empty { padding:48px 20px;text-align:center;color:var(--muted);font-size:.8rem;line-height:2; }

/* Context menu */
.conv-item .conv-menu-btn { opacity:0;position:absolute;right:8px;top:50%;transform:translateY(-50%);background:var(--surface2);border:1px solid var(--border);border-radius:6px;color:var(--muted);font-size:.75rem;padding:2px 7px;cursor:pointer;transition:all .15s;font-family:var(--font);z-index:2; }
.conv-item:hover .conv-menu-btn { opacity:1; }
.conv-item .conv-menu-btn:hover { color:var(--text);border-color:var(--text); }
.ctx-menu { position:fixed;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:6px;z-index:999;box-shadow:0 8px 32px rgba(0,0,0,.4);min-width:190px;backdrop-filter:blur(8px); }
.ctx-section { padding:4px 8px 2px;font-size:.62rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px; }
.ctx-item { display:flex;align-items:center;gap:9px;padding:7px 10px;border-radius:8px;cursor:pointer;font-size:.8rem;color:var(--text);transition:background .12s; }
.ctx-item:hover { background:rgba(255,255,255,.07); }
.ctx-item .ctx-dot { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
.ctx-divider { height:1px;background:var(--border);margin:4px 0; }

/* Label badge (used in info panel header) */
.lbl { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700; }
.lbl-nuevo      { background:rgba(107,114,128,.12);color:#9ca3af; }
.lbl-potencial  { background:rgba(245,158,11,.12);color:#f59e0b; }
.lbl-calificado { background:rgba(59,130,246,.12);color:#60a5fa; }
.lbl-agendado   { background:rgba(139,92,246,.12);color:#a78bfa; }
.lbl-cerrado    { background:rgba(16,185,129,.12);color:#34d399; }
.lbl-descartado { background:rgba(239,68,68,.1);color:#f87171; }

/* ── Chat area ── */
.chat-area { display:flex;flex-direction:column;overflow:hidden;border-right:1px solid var(--border); }
.chat-header { padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;background:var(--surface);flex-shrink:0; }
.chat-header-info { flex:1;min-width:0; }
.chat-header-name { font-size:.92rem;font-weight:800; }
.chat-header-sub { font-size:.72rem;color:var(--muted);margin-top:2px; }
.chat-messages { flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:8px;background:var(--bg);scrollbar-width:thin;scrollbar-color:var(--border) transparent; }
.chat-empty { flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:var(--muted);background:var(--bg); }
.msg-wrap { display:flex;flex-direction:column; }
.msg-wrap.out { align-items:flex-end; }
.msg-wrap.in  { align-items:flex-start; }
.msg-bubble { max-width:70%;padding:9px 14px;font-size:.875rem;line-height:1.55;word-break:break-word; }
.msg-in  { background:var(--surface);border:1px solid rgba(255,255,255,.07);color:var(--text);border-radius:16px 16px 16px 4px; }
.msg-out { background:linear-gradient(135deg,#1a5c2e,#215c35);border:1px solid rgba(37,211,102,.2);color:#d4f7e2;border-radius:16px 16px 4px 16px; }
.msg-time { font-size:.63rem;color:var(--muted);margin-top:4px;padding-right:2px;display:flex;align-items:center;justify-content:flex-end;gap:3px; }
.msg-out .msg-time { color:rgba(212,247,226,.4);text-align:right; }
.msg-in .msg-time { color:rgba(156,163,175,.6); }
.tick { font-size:.72rem;line-height:1;font-style:normal; }
.tick-sent      { color:rgba(212,247,226,.35); }
.tick-delivered { color:rgba(212,247,226,.55); }
.tick-read      { color:#60d8f3; }
.tick-failed    { color:#f87171; }
.msg-date-sep { text-align:center;font-size:.68rem;color:var(--muted);margin:10px 0;display:flex;align-items:center;gap:10px; }
.msg-date-sep::before,.msg-date-sep::after { content:'';flex:1;height:1px;background:var(--border); }
.chat-input-wrap { padding:10px 14px 12px;border-top:1px solid var(--border);background:var(--surface);flex-shrink:0; }
.chat-toolbar { display:flex;gap:6px;margin-bottom:8px; }
.btn-tool { padding:5px 12px;border-radius:8px;font-size:.72rem;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--muted);transition:all .15s;font-family:var(--font);display:flex;align-items:center;gap:4px; }
.btn-tool:hover { border-color:rgba(37,211,102,.5);color:#25d366;background:rgba(37,211,102,.06); }
.btn-tool.green { border-color:rgba(37,211,102,.3);color:#25d366;background:rgba(37,211,102,.07); }
.chat-input-row { display:flex;gap:8px;align-items:flex-end; }
.chat-input { flex:1;background:var(--bg);border:1px solid var(--border);border-radius:14px;padding:10px 16px;color:var(--text);font-family:var(--font);font-size:.875rem;resize:none;outline:none;max-height:120px;transition:border-color .2s;line-height:1.5; }
.chat-input:focus { border-color:rgba(37,211,102,.5); }
.btn-send { width:40px;height:40px;border-radius:50%;background:#25d366;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;transition:all .2s;color:#000; }
.btn-send:hover { background:#22c55e;box-shadow:0 0 16px rgba(37,211,102,.45);transform:scale(1.05); }
.btn-send:disabled { opacity:.35;cursor:not-allowed;transform:none; }

/* ── Media Attachment ── */
#mediaFileInput { display:none; }
.media-preview { background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:10px;margin-bottom:8px;display:flex;align-items:center;gap:10px; }
.media-preview img,.media-preview video { max-width:60px;max-height:60px;border-radius:6px;object-fit:cover; }
.media-preview-info { flex:1;min-width:0; }
.media-preview-name { font-size:.8rem;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.media-preview-size { font-size:.7rem;color:var(--muted);margin-top:2px; }
.media-preview-remove { background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:.9rem;padding:4px;transition:color .2s; }
.media-preview-remove:hover { color:var(--text); }
.media-caption { width:100%;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:8px 12px;font-size:.8rem;color:var(--text);font-family:var(--font);margin-bottom:8px;outline:none;transition:border-color .2s; }
.media-caption:focus { border-color:rgba(37,211,102,.5); }

/* ── Audio Recorder ── */
.recorder-controls { background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:8px;display:none; }
.recorder-controls.active { display:flex;align-items:center;gap:10px; }
.recorder-timer { font-size:.85rem;color:var(--muted);min-width:50px;font-weight:600;font-family:monospace; }
.recorder-btn { padding:6px 12px;border-radius:6px;border:1px solid var(--border);background:var(--surface);color:var(--text);cursor:pointer;font-size:.8rem;transition:all .2s;font-family:var(--font); }
.recorder-btn:hover { background:rgba(37,211,102,.1);border-color:rgba(37,211,102,.3); }
.recorder-btn.recording { background:#ef4444;color:#fff;border-color:#dc2626;animation:pulse 1s infinite; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.7; } }
.recorder-waveform { flex:1;height:30px;background:rgba(37,211,102,.05);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.75rem;color:var(--muted); }

/* ── Info panel ── */
.info-panel { overflow-y:auto;background:var(--surface);display:flex;flex-direction:column;scrollbar-width:thin;scrollbar-color:var(--border) transparent; }
.info-empty { padding:36px 16px;text-align:center;color:var(--muted);font-size:.8rem;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px; }

/* Contact card header */
.info-contact-card { padding:20px 16px;border-bottom:1px solid var(--border);display:flex;flex-direction:column;align-items:center;gap:10px;background:linear-gradient(180deg,rgba(37,211,102,.04) 0%,transparent 100%); }
.info-contact-avatar { width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;color:#fff; }
.info-contact-name { font-size:.9rem;font-weight:800;color:var(--text); }
.info-contact-phone { font-size:.72rem;color:var(--muted); }

/* Label pills selector */
.label-pills { display:flex;flex-wrap:wrap;gap:5px;margin-top:4px; }
.label-pill-btn { padding:4px 12px;border-radius:20px;font-size:.7rem;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--bg);color:var(--muted);transition:all .18s;font-family:var(--font); }
.label-pill-btn.active-lbl-nuevo      { background:rgba(107,114,128,.15);border-color:#6b7280;color:#9ca3af; }
.label-pill-btn.active-lbl-potencial  { background:rgba(245,158,11,.15);border-color:#f59e0b;color:#f59e0b; }
.label-pill-btn.active-lbl-calificado { background:rgba(59,130,246,.15);border-color:#3b82f6;color:#60a5fa; }
.label-pill-btn.active-lbl-agendado   { background:rgba(139,92,246,.15);border-color:#8b5cf6;color:#a78bfa; }
.label-pill-btn.active-lbl-cerrado    { background:rgba(16,185,129,.15);border-color:#10b981;color:#34d399; }
.label-pill-btn.active-lbl-descartado { background:rgba(239,68,68,.1);border-color:#ef4444;color:#f87171; }

.info-section { padding:14px 16px;border-bottom:1px solid var(--border); }
.info-section h4 { font-size:.65rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:10px; }
.info-row { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:7px;gap:8px; }
.info-label { font-size:.73rem;color:var(--muted);flex-shrink:0; }
.info-value { font-size:.78rem;color:var(--text);font-weight:600;text-align:right;word-break:break-all;max-width:60%; }
.notes-input { width:100%;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:9px 11px;color:var(--text);font-family:var(--font);font-size:.78rem;resize:none;outline:none;transition:border-color .2s;line-height:1.5; }
.notes-input:focus { border-color:rgba(37,211,102,.4); }
.quick-reply { padding:8px 11px;background:var(--bg);border:1px solid var(--border);border-radius:8px;font-size:.75rem;color:var(--text);cursor:pointer;transition:all .15s;text-align:left;font-family:var(--font);width:100%;margin-bottom:6px;line-height:1.4; }
.quick-reply:hover { border-color:rgba(37,211,102,.4);color:#25d366;background:rgba(37,211,102,.04); }
.btn-convert { width:100%;padding:11px;background:linear-gradient(135deg,#16532b,#25d366);color:#fff;border:none;border-radius:10px;font-family:var(--font);font-size:.82rem;font-weight:700;cursor:pointer;transition:all .2s;margin-top:6px;letter-spacing:.2px; }
.btn-convert:hover { box-shadow:0 4px 18px rgba(37,211,102,.3);transform:translateY(-1px); }

/* ── Modals ── */
.modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:200;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(6px); }
.modal-box { background:var(--surface);border:1px solid var(--border);border-radius:18px;width:480px;max-height:82vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4); }
.modal-box.wide { width:560px; }
.modal-header { padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0; }
.modal-header h3 { font-size:.94rem;font-weight:800; }
.modal-close { background:rgba(255,255,255,.06);border:1px solid var(--border);color:var(--muted);font-size:.8rem;cursor:pointer;padding:5px 8px;border-radius:8px;transition:all .15s; }
.modal-close:hover { color:var(--text);border-color:var(--text); }
.modal-body { padding:18px;overflow-y:auto; }
.modal-footer { padding:14px 18px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0; }
.tpl-card { background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:14px;cursor:pointer;transition:all .18s;margin-bottom:8px; }
.tpl-card:hover { border-color:#25d366;background:rgba(37,211,102,.04);transform:translateY(-1px); }
.tpl-name { font-size:.75rem;font-weight:800;color:#25d366;text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px; }
.tpl-lang { font-size:.68rem;color:var(--muted);margin-bottom:8px; }
.tpl-preview { font-size:.8rem;color:var(--text);line-height:1.6; }

/* ── Image Lightbox ── */
.image-lightbox-overlay { position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:500;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s;pointer-events:none; }
.image-lightbox-overlay.active { opacity:1;pointer-events:all; }
.image-lightbox-container { position:relative;display:flex;align-items:center;justify-content:center;width:100%;height:100%; }
.image-lightbox-img { max-width:90vw;max-height:90vh;object-fit:contain;border-radius:12px; }
.image-lightbox-close { position:absolute;top:20px;right:20px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:#fff;width:40px;height:40px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.3rem;transition:all .2s;font-weight:300; }
.image-lightbox-close:hover { background:rgba(255,255,255,0.25);border-color:rgba(255,255,255,0.5);color:#fff;transform:scale(1.1); }

/* ── Message Image Thumbnail ── */
.msg-image-thumb { max-width:280px;max-height:320px;width:auto;height:auto;object-fit:contain;border-radius:10px;display:block;cursor:zoom-in;background:var(--bg);border:1px solid var(--border);transition:transform .2s,box-shadow .2s; }
.msg-image-thumb:hover { transform:scale(1.02);box-shadow:0 4px 12px rgba(0,0,0,0.15); }
.msg-image-loading { padding:20px;color:var(--muted);font-size:.85rem;display:flex;align-items:center;gap:6px; }
.msg-image-error { padding:12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;color:#fca5a5;font-size:.8rem;display:flex;align-items:center;gap:6px; }

/* ── Message Audio ── */
.msg-audio { width:100%;max-width:280px;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:8px 12px; }
.msg-audio audio { width:100%;height:32px;outline:none; }
.msg-audio audio::-webkit-media-controls-panel { background-color:transparent; }

/* ── Message Video Thumbnail ── */
.msg-video-thumb { max-width:280px;max-height:320px;width:auto;height:auto;object-fit:contain;border-radius:10px;display:block;background:var(--bg);border:1px solid var(--border);cursor:zoom-in; }
.msg-video { width:100%;max-width:280px;background:var(--bg);border:1px solid var(--border);border-radius:10px;overflow:hidden; }
.msg-video video { width:100%;height:auto;display:block; }
.msg-video-loading { padding:40px 20px;color:var(--muted);font-size:.85rem;display:flex;align-items:center;justify-content:center;gap:6px;background:var(--bg);border:1px solid var(--border);border-radius:10px; }
.msg-video-error { padding:12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;color:#fca5a5;font-size:.8rem;display:flex;align-items:center;gap:6px; }
</style>

<div class="inbox-wrap">

<!-- Conversations sidebar -->
<div class="conv-sidebar">
    <div class="conv-sidebar-header">
        <span style="font-size:1.2rem">&#128383;</span>
        <h2>Conversaciones</h2>
        <span id="totalUnread"></span>
        <button id="btnSound" onclick="toggleSound()" title="Sonido activado" style="background:rgba(37,211,102,.12);border:1px solid rgba(37,211,102,.3);color:#25d366;border-radius:8px;width:28px;height:28px;cursor:pointer;font-size:.95rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">🔔</button>
        <button onclick="openNewChat()" title="Nueva conversación" style="background:rgba(37,211,102,.12);border:1px solid rgba(37,211,102,.3);color:#25d366;border-radius:8px;width:28px;height:28px;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;" onmouseover="this.style.background='rgba(37,211,102,.22)'" onmouseout="this.style.background='rgba(37,211,102,.12)'">+</button>
    </div>
    <div class="conv-search">
        <input type="text" id="convSearch" placeholder="Buscar nombre o mensaje..." oninput="filterConvs(this.value)">
    </div>
    <div class="filter-pills" id="filterTabs">
        <button class="filter-pill active" data-f="todos"      onclick="setFilter('todos',this)">Todos</button>
        <button class="filter-pill" data-f="nuevo"             onclick="setFilter('nuevo',this)">Nuevos</button>
        <button class="filter-pill" data-f="potencial"         onclick="setFilter('potencial',this)">Potencial</button>
        <button class="filter-pill" data-f="calificado"        onclick="setFilter('calificado',this)">Calificado</button>
        <button class="filter-pill" data-f="agendado"          onclick="setFilter('agendado',this)">Agendado</button>
        <button class="filter-pill" data-f="cerrado"           onclick="setFilter('cerrado',this)">Cerrado</button>
    </div>
    <div class="conv-list" id="convList"><div class="conv-empty">Cargando...</div></div>
</div>

<!-- Chat area -->
<div class="chat-area" id="chatArea">
    <div class="chat-empty">
        <div style="font-size:2.5rem;opacity:.15">&#128383;</div>
        <div style="font-weight:700;font-size:.9rem">Seleccioná una conversación</div>
        <div style="font-size:.78rem;margin-top:3px">Los mensajes de tus leads aparecerán acá</div>
    </div>
</div>

<!-- Info panel -->
<div class="info-panel" id="infoPanel">
    <div class="info-empty">
        <div style="font-size:2rem;opacity:.15">&#128100;</div>
        Seleccioná un lead para ver su información
    </div>
</div>

</div>

<!-- New conversation modal -->
<div class="modal-overlay" id="newChatModal" style="display:none" onclick="if(event.target===this)closeNewChat()">
    <div class="modal-box" style="width:460px">
        <div class="modal-header">
            <h3>&#128172; Nueva conversación</h3>
            <button class="modal-close" onclick="closeNewChat()">&#10005;</button>
        </div>
        <div class="modal-body">
            <!-- Tab selector -->
            <div style="display:flex;gap:6px;margin-bottom:18px">
                <button id="nc_tab_lead" onclick="ncTab('lead')" class="btn" style="flex:1;font-size:.78rem;background:var(--accent);color:#000;border:none">Buscar lead</button>
                <button id="nc_tab_phone" onclick="ncTab('phone')" class="btn" style="flex:1;font-size:.78rem;background:var(--surface2);color:var(--muted);border:1px solid var(--border)">Número manual</button>
            </div>

            <!-- Lead search -->
            <div id="nc_lead_section">
                <div class="form-group">
                    <input type="text" id="nc_search" placeholder="Buscar por nombre..." oninput="ncSearch(this.value)"
                        style="width:100%;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:9px 14px;color:var(--text);font-family:var(--font);font-size:.85rem;outline:none">
                </div>
                <div id="nc_lead_list" style="max-height:260px;overflow-y:auto;display:flex;flex-direction:column;gap:4px;margin-top:4px"></div>
            </div>

            <!-- Manual phone -->
            <div id="nc_phone_section" style="display:none">
                <div class="form-group">
                    <label>Número con código de país</label>
                    <input type="text" id="nc_phone_input" placeholder="+54 9 11 1234-5678"
                        style="width:100%;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:9px 14px;color:var(--text);font-family:var(--font);font-size:.85rem;outline:none">
                    <small style="color:var(--muted);font-size:.74rem;margin-top:5px;display:block">Solo podés enviar si esta persona te escribió en las últimas 24hs, o usá una plantilla aprobada.</small>
                </div>
                <button onclick="ncStartPhone()" class="btn btn-accent" style="width:100%">&#128172; Abrir chat</button>
            </div>
        </div>
    </div>
</div>

<!-- Templates modal -->
<div class="modal-overlay" id="tplModal" style="display:none" onclick="if(event.target===this)closeTpl()">
    <div class="modal-box">
        <div class="modal-header">
            <h3>&#128196; Plantillas aprobadas</h3>
            <button class="modal-close" onclick="closeTpl()">&#10005;</button>
        </div>
        <div class="modal-body" id="tplList"><div style="text-align:center;color:var(--muted);padding:20px">Cargando...</div></div>
    </div>
</div>

<!-- Convert to lead modal -->
<div class="modal-overlay" id="leadModal" style="display:none" onclick="if(event.target===this)closeLeadModal()">
    <div class="modal-box wide">
        <div class="modal-header">
            <h3>&#127919; Agregar como lead calificado</h3>
            <button class="modal-close" onclick="closeLeadModal()">&#10005;</button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" id="lf_nombre" placeholder="Juan García">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="lf_email" placeholder="juan@gmail.com">
                </div>
                <div class="form-group">
                    <label>WhatsApp</label>
                    <input type="text" id="lf_phone" readonly style="opacity:.6">
                </div>
                <div class="form-group">
                    <label>Nicho / Industria</label>
                    <input type="text" id="lf_nicho" placeholder="E-commerce, Salud...">
                </div>
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" id="lf_ciudad" placeholder="Buenos Aires">
                </div>
                <div class="form-group">
                    <label>País</label>
                    <input type="text" id="lf_pais" placeholder="Argentina">
                </div>
                <div class="form-group">
                    <label>Presupuesto</label>
                    <select id="lf_presupuesto">
                        <option value="">Sin especificar</option>
                        <option value="$500 - $1,000 USD">$500 – $1K USD</option>
                        <option value="$1,000 - $3,000 USD">$1K – $3K USD</option>
                        <option value="$3,000 - $5,000 USD">$3K – $5K USD</option>
                        <option value="$5,000+ USD">$5K+ USD</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Objetivo</label>
                    <input type="text" id="lf_objetivo" placeholder="Conseguir más clientes">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeLeadModal()" class="btn" style="background:var(--surface2);color:var(--text)">Cancelar</button>
            <button onclick="submitLead()" class="btn btn-accent" id="submitLeadBtn">&#127919; Guardar lead</button>
        </div>
    </div>
</div>

<script>
const API      = '<?= ADMIN_URL ?>/api/wa-messages.php';
const TPL_API  = '<?= ADMIN_URL ?>/api/wa-templates.php';
const CTX_API  = '<?= ADMIN_URL ?>/api/wa-contact.php';
const ADMIN    = '<?= ADMIN_URL ?>';

let activeLeadId = null;
let activePhone  = null;
let lastMsgId    = 0;
let pollTimer    = null;
let allConvs     = [];
let activeFilter = 'todos';

// Label config
const LABELS = {
    nuevo:      { text:'Nuevo',      cls:'lbl-nuevo' },
    potencial:  { text:'Potencial',  cls:'lbl-potencial' },
    calificado: { text:'Calificado', cls:'lbl-calificado' },
    agendado:   { text:'Agendado',   cls:'lbl-agendado' },
    cerrado:    { text:'Cerrado',    cls:'lbl-cerrado' },
    descartado: { text:'Descartado', cls:'lbl-descartado' },
};
const AVATAR_COLORS = {
    nuevo:'linear-gradient(135deg,#374151,#6b7280)',
    potencial:'linear-gradient(135deg,#78350f,#fbbf24)',
    calificado:'linear-gradient(135deg,#1a5c2e,#25d366)',
    agendado:'linear-gradient(135deg,#0c4a6e,#06b6d4)',
    cerrado:'linear-gradient(135deg,#14532d,#22c55e)',
    descartado:'linear-gradient(135deg,#7f1d1d,#ef4444)',
};

// ── Conversations ─────────────────────────────────────────────────────────────
function loadConversations() {
    fetch(API + '?action=conversations')
        .then(r => r.json())
        .then(d => {
            allConvs = d.data || [];
            applyFilter();
        });
}

function setFilter(f, btn) {
    activeFilter = f;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    applyFilter();
}

function applyFilter() {
    let convs = allConvs;
    if (activeFilter !== 'todos') convs = convs.filter(c => c.label === activeFilter);
    const q = document.getElementById('convSearch')?.value?.toLowerCase() || '';
    if (q) convs = convs.filter(c =>
        (c.nombre||'').toLowerCase().includes(q) ||
        (c.phone||'').includes(q) ||
        (c.last_msg||'').toLowerCase().includes(q)
    );
    renderConvs(convs);
}

function filterConvs(q) { applyFilter(); }

function renderConvs(convs) {
    const list = document.getElementById('convList');
    const totalUnread = allConvs.reduce((s,c) => s+parseInt(c.unread||0), 0);
    document.getElementById('totalUnread').innerHTML = totalUnread
        ? `<span class="conv-unread">${totalUnread}</span>` : '';

    if (!convs.length) {
        list.innerHTML = '<div class="conv-empty">&#128383; Sin conversaciones<br><small>Cambiá el filtro o esperá mensajes</small></div>';
        return;
    }
    list.innerHTML = convs.map(c => {
        const lbl      = LABELS[c.label] || LABELS.nuevo;
        const clr      = AVATAR_COLORS[c.label] || AVATAR_COLORS.nuevo;
        const isActive = c.lead_id ? c.lead_id == activeLeadId : c.phone == activePhone;
        const initial  = (c.nombre||'?').charAt(0).toUpperCase();
        const preview  = c.last_msg ? esc(c.last_msg).substring(0,55) + (c.last_msg.length>55?'…':'') : '<em>Sin mensajes</em>';
        return `
        <div class="conv-item ${isActive?'active':''}"
             onclick="openChat(${c.lead_id||'null'},'${esc(c.nombre)}','${esc(c.phone||'')}','${esc(c.nicho||'')}')"
             oncontextmenu="event.preventDefault();showCtxMenu(event,'${esc(c.phone||'')}',${c.lead_id||'null'},'${esc(c.nombre)}')">
            <div class="conv-avatar-wrap">
                <div class="conv-avatar" style="background:${clr}">${initial}</div>
                <div class="conv-label-dot dot-${c.label||'nuevo'}"></div>
            </div>
            <div class="conv-body">
                <div class="conv-name">${esc(c.nombre)}</div>
                <div class="conv-preview">${preview}</div>
            </div>
            <div class="conv-meta">
                <div class="conv-time">${fmtTime(c.last_ts)}</div>
                ${parseInt(c.unread)>0?`<span class="conv-unread">${c.unread}</span>`:''}
            </div>
            <button class="conv-menu-btn" onclick="event.stopPropagation();showCtxMenu(event,'${esc(c.phone||'')}',${c.lead_id||'null'})">⋯</button>
        </div>`;
    }).join('');
}

// ── Open chat ─────────────────────────────────────────────────────────────────
function openChat(leadId, nombre, phone, nicho) {
    activeLeadId = leadId||null;
    activePhone  = phone||null;
    lastMsgId    = 0;
    clearInterval(pollTimer);

    document.getElementById('chatArea').innerHTML = `
        <div class="chat-header">
            <div class="conv-avatar" style="width:34px;height:34px;font-size:.78rem;background:var(--surface2);border:1px solid var(--border)">${(nombre||'?').charAt(0).toUpperCase()}</div>
            <div class="chat-header-info">
                <div class="chat-header-name">${esc(nombre)}</div>
                <div class="chat-header-sub">${esc(phone)}</div>
            </div>
            ${leadId?`<a href="${ADMIN}/pages/leads.php" class="btn btn-sm" style="background:rgba(6,182,212,.1);color:#06b6d4;border:1px solid rgba(6,182,212,.25);font-size:.7rem;margin-right:4px">Ver lead</a>`:''}
        </div>
        <div class="chat-messages" id="msgList"></div>
        <div class="chat-input-wrap">
            <div class="chat-toolbar">
                <button class="btn-tool green" onclick="openTpl()">&#128196; Plantillas</button>
                <button class="btn-tool" onclick="insertQR('Hola! 👋 Gracias por tu mensaje, te respondo enseguida.')">&#9889; Saludo</button>
                <button class="btn-tool" onclick="insertQR('Perfecto, te paso más información ahora mismo.')">&#128228; Info</button>
                <button class="btn-tool" onclick="insertQR('¿Podemos agendar una llamada de 15 min? 📞')">&#128222; Llamada</button>
                <button class="btn-tool" onclick="document.getElementById('mediaFileInput').click()">📎 Adjuntar</button>
                <button class="btn-tool" onclick="toggleRecorder()">🎤 Grabar</button>
                <input type="file" id="mediaFileInput" accept="image/*,audio/*,video/*,.pdf,.doc,.docx" onchange="handleMediaSelect(event)">
            </div>
            <div class="recorder-controls" id="recorderControls">
                <div class="recorder-timer" id="recorderTimer">00:00</div>
                <div class="recorder-waveform" id="recorderWaveform">● En vivo</div>
                <button class="recorder-btn recording" id="recordBtn" onclick="toggleRecord()">⏹ Detener</button>
                <button class="recorder-btn" id="cancelBtn" onclick="cancelRecording()">✕ Cancelar</button>
            </div>
            <div id="mediaPreviewContainer"></div>
            <input type="text" id="mediaCaption" class="media-caption" placeholder="Agregar descripción (opcional)" style="display:none;">
            <div class="chat-input-row">
                <textarea class="chat-input" id="chatInput" rows="1"
                    placeholder="Escribí un mensaje... (Enter envía)"
                    onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
                <button class="btn-send" id="sendBtn" onclick="sendMsg()">&#10148;</button>
            </div>
        </div>`;

    loadMessages(true);
    pollTimer = setInterval(() => loadMessages(false), 3000);
    loadConversations();
    loadInfoPanel(leadId, nombre, phone, nicho);
}

// ── Messages ──────────────────────────────────────────────────────────────────
let lastDateShown = '';
const renderedMsgIds = new Set();
let soundReady = false;

// AudioContext único — se inicializa en el primer clic del usuario
let _audioCtx = null;
function getAudioCtx() {
    if (!_audioCtx) _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (_audioCtx.state === 'suspended') _audioCtx.resume();
    return _audioCtx;
}
// Inicializar en cualquier clic para desbloquear el audio del navegador
document.addEventListener('click', () => { try { getAudioCtx(); } catch(e){} });

let soundMuted = false;
function toggleSound() {
    soundMuted = !soundMuted;
    const btn = document.getElementById('btnSound');
    btn.textContent = soundMuted ? '🔕' : '🔔';
    btn.title = soundMuted ? 'Sonido desactivado' : 'Sonido activado';
    btn.style.color = soundMuted ? 'var(--muted)' : '#25d366';
    btn.style.borderColor = soundMuted ? 'var(--border)' : 'rgba(37,211,102,.3)';
    if (!soundMuted) { getAudioCtx(); playNotifSound(); } // prueba al activar
}

function playNotifSound() {
    try {
        const ctx = getAudioCtx();
        [[0, 620], [0.18, 860]].forEach(([t, freq]) => {
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.22, ctx.currentTime + t);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + 0.3);
            osc.start(ctx.currentTime + t);
            osc.stop(ctx.currentTime + t + 0.35);
        });
    } catch(e) { console.warn('Audio error:', e); }
}

function appendMessage(m, list) {
    if (renderedMsgIds.has(m.id)) return;
    renderedMsgIds.add(m.id);
    if (soundReady && !soundMuted && m.direction === 'in') playNotifSound();
    if (m.id > lastMsgId) lastMsgId = m.id;
    const ds = (m.created_at||'').substring(0,10);
    if (ds && ds !== lastDateShown) {
        lastDateShown = ds;
        const sep = document.createElement('div');
        sep.className = 'msg-date-sep';
        sep.textContent = fmtDate(m.created_at);
        list.appendChild(sep);
    }
    const wrap = document.createElement('div');
    wrap.className = `msg-wrap ${m.direction}`;
    wrap.dataset.id = m.id;
    const tick = m.direction==='out' ? renderTick(m.wa_status) : '';
    const msgType = m.message_type || 'text';

    let messageHTML = '';

    switch (msgType) {
        case 'text':
            messageHTML = `<div class="msg-bubble msg-${m.direction}">${esc(m.body)}<div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div></div>`;
            break;

        case 'audio':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media">
                <a href="${ADMIN}/api/wa-media.php?media_id=${esc(m.media_id)}" target="_blank" style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);border-radius:8px;color:var(--text);text-decoration:none;font-size:.85rem;font-weight:600;transition:all .2s">
                    🎵 Escuchar nota de voz
                </a>
                ${m.caption ? `<div style="margin-top:6px;font-size:.85rem">${esc(m.caption)}</div>` : ''}
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;

        case 'image':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media">
                <div class="msg-image-loading" id="img-loading-${m.id}">🖼️ Cargando imagen...</div>
                <img id="img-${m.id}"
                     src="${ADMIN}/api/wa-media.php?media_id=${esc(m.media_id)}"
                     alt="Imagen recibida"
                     class="msg-image-thumb"
                     style="display:none"
                     onclick="openImageLightbox('${esc(m.media_id)}', event)"
                     onerror="showImageError('${m.id}')"
                     onload="hideImageLoading('${m.id}')">
                <div class="msg-image-error" id="img-error-${m.id}" style="display:none">⚠️ No se pudo cargar la imagen</div>
                ${m.caption ? `<div style="margin-top:6px;font-size:.85rem">${esc(m.caption)}</div>` : ''}
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;

        case 'sticker':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media" style="background:transparent;border:none">
                <img src="${ADMIN}/api/wa-media.php?media_id=${esc(m.media_id)}&type=sticker"
                     style="max-width:200px;height:auto">
                <div class="msg-time" style="margin-top:4px">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;

        case 'document':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media">
                <div style="display:flex;gap:10px;align-items:center">
                    <span style="font-size:1.5rem">📄</span>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:.85rem">${esc(m.file_name || 'Documento')}</div>
                        ${m.caption ? `<div style="font-size:.75rem;color:var(--muted)">${esc(m.caption)}</div>` : ''}
                    </div>
                    <a href="${ADMIN}/api/wa-media.php?media_id=${esc(m.media_id)}&type=document&download=1"
                       class="btn btn-sm" style="padding:4px 10px;font-size:.7rem">⬇️</a>
                </div>
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;

        case 'video':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media">
                <a href="${ADMIN}/api/wa-media.php?media_id=${esc(m.media_id)}" target="_blank" style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);border-radius:8px;color:var(--text);text-decoration:none;font-size:.85rem;font-weight:600;transition:all .2s">
                    🎬 Ver video
                </a>
                ${m.caption ? `<div style="margin-top:6px;font-size:.85rem">${esc(m.caption)}</div>` : ''}
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;

        default:
            messageHTML = `<div class="msg-bubble msg-${m.direction}">
                📎 Tipo no soportado: ${esc(msgType)}
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
    }

    wrap.innerHTML = messageHTML;
    list.appendChild(wrap);
}

function loadMessages(initial=false) {
    if (!activeLeadId && !activePhone) return;
    const param = activeLeadId ? `lead_id=${activeLeadId}` : `phone=${encodeURIComponent(activePhone)}`;
    fetch(`${API}?action=messages&${param}&since=${lastMsgId}`)
        .then(r=>r.json())
        .then(d => {
            if (initial) {
                lastDateShown = ''; renderedMsgIds.clear(); soundReady = false;
                // Habilitar sonido 3s después de la carga inicial
                setTimeout(() => { soundReady = true; }, 3000);
            }
            if (!d.ok || !d.messages.length) return;
            const list = document.getElementById('msgList');
            if (!list) return;
            // Ordenar por id ASC antes de renderizar
            const msgs = [...d.messages].sort((a,b) => a.id - b.id);
            msgs.forEach(m => appendMessage(m, list));
            list.scrollTop = list.scrollHeight;
            if (!initial) loadConversations();
        });
}

// ── Send ──────────────────────────────────────────────────────────────────────
function sendMsg() {
    // Si hay media seleccionado, enviar media en lugar de texto
    if (selectedMedia) {
        sendMediaMessage();
        return;
    }

    const input = document.getElementById('chatInput');
    const btn   = document.getElementById('sendBtn');
    if (btn?.disabled) return; // evitar doble envío por Enter rápido
    const body  = input?.value.trim();
    if (!body || (!activeLeadId && !activePhone)) return;
    btn.disabled = true;
    input.value = ''; input.style.height = 'auto'; // limpiar ya para evitar reenvío
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({lead_id:activeLeadId, phone:activePhone, body}) })
    .then(r=>r.json())
    .then(d => {
        if (d.ok) {
            const list = document.getElementById('msgList');
            if (list && d.message) {
                appendMessage(d.message, list);
                list.scrollTop = list.scrollHeight;
            }
            loadConversations();
        } else { alert('Error: '+d.msg); }
    }).finally(()=>{ btn.disabled=false; });
}
function insertQR(t) { const i=document.getElementById('chatInput'); if(i){i.value=t;i.focus();autoResize(i);} }

// ── Info panel ────────────────────────────────────────────────────────────────
function loadInfoPanel(leadId, nombre, phone, nicho, lead) {
    const panel = document.getElementById('infoPanel');
    panel.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:.8rem">Cargando...</div>';

    // Load contact label/notes
    fetch(`${CTX_API}?action=get&phone=${encodeURIComponent(phone||'')}`)
        .then(r=>r.json())
        .then(d => {
            const contact = d.contact || {};
            if (leadId) {
                fetch(`${API}?action=messages&lead_id=${leadId}&since=0`)
                    .then(r=>r.json())
                    .then(d2 => renderInfoPanel(panel, d2.lead, phone, contact));
            } else {
                renderInfoPanel(panel, null, phone, contact);
            }
        });
}

function renderInfoPanel(panel, lead, phone, contact) {
    const curLabel  = contact.label || 'nuevo';
    const lbl       = LABELS[curLabel] || LABELS.nuevo;
    const clr       = AVATAR_COLORS[curLabel] || AVATAR_COLORS.nuevo;
    const dispName  = lead?.nombre || contact.wa_name || phone || '?';
    const initial   = dispName.charAt(0).toUpperCase();

    const labelPills = Object.entries(LABELS).map(([k,v]) => {
        const isAct = k === curLabel;
        return `<button class="label-pill-btn ${isAct?'active-lbl-'+k:''}"
            onclick="setLabel('${esc(phone)}','${k}',this)">${v.text}</button>`;
    }).join('');

    panel.innerHTML = `
    <div class="info-contact-card">
        <div class="info-contact-avatar" style="background:${clr}">${initial}</div>
        <div class="info-contact-name">${esc(dispName)}</div>
        <div class="info-contact-phone">${esc(phone||'')}</div>
        <div class="label-pills">${labelPills}</div>
    </div>

    ${lead ? `
    <div class="info-section">
        <h4>Información del lead</h4>
        <div class="info-row"><span class="info-label">Email</span><span class="info-value" style="font-size:.7rem">${esc(lead.email||'—')}</span></div>
        <div class="info-row"><span class="info-label">País</span><span class="info-value">${esc(lead.pais||'—')}</span></div>
        <div class="info-row"><span class="info-label">Nicho</span><span class="info-value">${esc(lead.nicho||'—')}</span></div>
        <div class="info-row"><span class="info-label">Presupuesto</span><span class="info-value" style="color:#25d366;font-weight:800">${esc(lead.presupuesto||'—')}</span></div>
        <div class="info-row"><span class="info-label">Objetivo</span><span class="info-value">${esc(lead.objetivo||'—')}</span></div>
    </div>` : `
    <div class="info-section">
        <button class="btn-convert" onclick="openLeadModal('${esc(phone)}', '${esc(contact.wa_name||'')}')">&#127919; Convertir a lead</button>
    </div>`}

    <div class="info-section">
        <h4>Notas internas</h4>
        <textarea class="notes-input" rows="4" id="notesField"
            placeholder="Anotá datos clave de este contacto..."
            onblur="saveNotes('${esc(phone)}',this.value)">${esc(contact.notes||'')}</textarea>
    </div>

    <div class="info-section">
        <h4>Respuestas rápidas</h4>
        ${quickReplies(lead)}
    </div>`;
}

function quickReplies(lead) {
    const nombre = lead?.nombre?.split(' ')[0] || 'ahí';
    return [
        `Hola ${nombre}! 👋 Gracias por tu mensaje, te respondo enseguida.`,
        `¿Podemos agendar una llamada de 15 min para charlar mejor? 📞`,
        `Te mando info sobre cómo trabajamos y resultados que logramos.`,
        `Perfecto ${nombre}, quedo a disposición. ¡Escribime! 🚀`,
    ].map(t=>`<button class="quick-reply" onclick="insertQR('${t.replace(/'/g,"\\'")}')">${t.substring(0,55)}${t.length>55?'...':''}</button>`).join('');
}

// ── Label & notes ─────────────────────────────────────────────────────────────
function setLabel(phone, label, btn) {
    // Update pills visually
    if (btn) {
        btn.closest('.label-pills').querySelectorAll('.label-pill-btn').forEach(b => {
            b.className = 'label-pill-btn';
        });
        btn.className = 'label-pill-btn active-lbl-' + label;
    }
    fetch(CTX_API, { method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'label', phone, label}) })
    .then(()=>loadConversations());
}
function saveNotes(phone, notes) {
    fetch(CTX_API, { method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'notes', phone, notes}) });
}

// ── Lead modal ────────────────────────────────────────────────────────────────
function openLeadModal(phone, waName='') {
    document.getElementById('lf_phone').value = phone;
    document.getElementById('lf_nombre').value = waName;
    document.getElementById('lf_email').value = '';
    document.getElementById('lf_nicho').value = '';
    document.getElementById('lf_ciudad').value = '';
    document.getElementById('lf_pais').value = '';
    document.getElementById('lf_objetivo').value = '';
    document.getElementById('leadModal').style.display = 'flex';
    document.getElementById('lf_nombre').focus();
}
function closeLeadModal() { document.getElementById('leadModal').style.display='none'; }
function submitLead() {
    const btn = document.getElementById('submitLeadBtn');
    const data = {
        action:'to_lead',
        phone:    document.getElementById('lf_phone').value,
        nombre:   document.getElementById('lf_nombre').value.trim(),
        email:    document.getElementById('lf_email').value.trim(),
        nicho:    document.getElementById('lf_nicho').value.trim(),
        ciudad:   document.getElementById('lf_ciudad').value.trim(),
        pais:     document.getElementById('lf_pais').value.trim(),
        presupuesto: document.getElementById('lf_presupuesto').value,
        objetivo: document.getElementById('lf_objetivo').value.trim(),
    };
    if (!data.nombre) { alert('El nombre es obligatorio'); return; }
    btn.disabled=true; btn.textContent='Guardando...';
    fetch(CTX_API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })
    .then(r=>r.json())
    .then(d => {
        if (d.ok) {
            closeLeadModal();
            activeLeadId = d.lead_id;
            loadConversations();
            loadInfoPanel(d.lead_id, data.nombre, data.phone, data.nicho);
        } else { alert('Error: '+d.msg); }
    }).finally(()=>{ btn.disabled=false; btn.textContent='🎯 Guardar lead'; });
}

// ── Toast (reemplaza alert/confirm) ───────────────────────────────────────────
function showToast(msg, type='error') {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;top:76px;right:24px;padding:12px 20px;border-radius:10px;font-size:.85rem;font-weight:600;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.35);transition:opacity .35s;background:${type==='ok'?'#22c55e':'#ef4444'};color:#fff;`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(),350); }, 2800);
}

// ── Templates ─────────────────────────────────────────────────────────────────
let _loadedTpls = [];

function openTpl() {
    if (!activeLeadId && !activePhone) { showToast('Abrí una conversación primero'); return; }
    document.getElementById('tplModal').style.display='flex';
    document.getElementById('tplList').innerHTML = '<div style="text-align:center;color:var(--muted);padding:20px">Cargando...</div>';
    fetch(TPL_API).then(r=>r.json()).then(d => {
        const list = document.getElementById('tplList');
        if (!d.ok || !d.templates.length) {
            list.innerHTML = `<div style="text-align:center;color:var(--muted);padding:20px">${d.msg||'Sin plantillas aprobadas.'}<br><small>Configurá el WABA ID en WhatsApp API</small></div>`;
            return;
        }
        _loadedTpls = d.templates;
        renderTplList();
    }).catch(() => {
        document.getElementById('tplList').innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Error al cargar plantillas</div>';
    });
}

function renderTplList() {
    document.getElementById('tplList').innerHTML = _loadedTpls.map((t,i) => `
        <div class="tpl-card" onclick="selectTpl(${i})">
            <div class="tpl-name">${esc(t.name)}</div>
            <div class="tpl-lang">&#127760; ${t.language} · &#9989; Aprobada</div>
            <div class="tpl-preview">${esc(t.preview||'(sin cuerpo)')}</div>
        </div>`).join('');
}

function selectTpl(i) {
    const tpl = _loadedTpls[i];
    if (!tpl) return;
    const vars = (tpl.preview || '').match(/\{\{\d+\}\}/g);
    if (!vars || !vars.length) {
        sendTpl(tpl, [], tpl.preview || '');
        return;
    }
    // Show variable form inside the modal (no prompt/alert)
    document.getElementById('tplList').innerHTML = `
        <button onclick="renderTplList()" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:.82rem;margin-bottom:14px;font-family:var(--font)">&#8592; Volver</button>
        <div class="tpl-name" style="margin-bottom:8px">${esc(tpl.name)}</div>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:14px;font-size:.84rem;line-height:1.6;color:var(--text);margin-bottom:16px">${esc(tpl.preview)}</div>
        ${vars.map((_,j) => `
        <div style="margin-bottom:12px">
            <label style="display:block;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Variable {{${j+1}}}</label>
            <input id="tpl_var_${j}" type="text" placeholder="${j===0?'Ej: Juan':'Valor...'}"
                style="width:100%;background:var(--bg);border:1px solid var(--border);border-radius:9px;padding:9px 14px;color:var(--text);font-family:var(--font);font-size:.88rem;outline:none;transition:border-color .2s"
                onfocus="this.style.borderColor='#25d366'" onblur="this.style.borderColor='var(--border)'"
                onkeydown="if(event.key==='Enter')submitTpl(${i})">
        </div>`).join('')}
        <button onclick="submitTpl(${i})" style="width:100%;padding:11px;background:#25d366;color:#000;border:none;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;font-family:var(--font);margin-top:4px">
            &#128172; Enviar plantilla
        </button>`;
    setTimeout(() => document.getElementById('tpl_var_0')?.focus(), 50);
}

function submitTpl(tplIdx) {
    const tpl = _loadedTpls[tplIdx];
    const vars = (tpl.preview || '').match(/\{\{\d+\}\}/g) || [];
    const params = [];
    let display = tpl.preview || '';
    for (let i = 0; i < vars.length; i++) {
        const val = (document.getElementById(`tpl_var_${i}`)?.value || '').trim();
        if (!val) { document.getElementById(`tpl_var_${i}`)?.focus(); showToast('Completá todos los campos'); return; }
        params.push(val);
        display = display.replace(`{{${i+1}}}`, val);
    }
    sendTpl(tpl, params, display);
}

function sendTpl(tpl, params, displayBody) {
    closeTpl();
    const btn = document.getElementById('sendBtn');
    if (btn) btn.disabled = true;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            lead_id:           activeLeadId,
            phone:             activePhone,
            template_name:     tpl.name,
            template_language: tpl.language,
            template_params:   params,
            template_body:     displayBody,
        })
    })
    .then(r=>r.json())
    .then(d => {
        if (d.ok) {
            const list = document.getElementById('msgList');
            if (list && d.message) {
                appendMessage(d.message, list);
                list.scrollTop = list.scrollHeight;
            }
            loadConversations();
            showToast('Plantilla enviada ✓', 'ok');
        } else {
            showToast('Error: ' + d.msg);
        }
    })
    .catch(() => showToast('Error de conexión'))
    .finally(() => { if (btn) btn.disabled = false; });
}

function closeTpl() { document.getElementById('tplModal').style.display='none'; }

// ── Utils ─────────────────────────────────────────────────────────────────────
function handleKey(e) { if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMsg();} }
function autoResize(el) { el.style.height='auto';el.style.height=Math.min(el.scrollHeight,100)+'px'; }
function fmtTime(ts) {
    if(!ts)return'';
    const d=new Date(ts.replace(' ','T')),now=new Date(),diff=(now-d)/1000;
    if(diff<60)return'ahora';
    if(diff<3600)return Math.floor(diff/60)+'m';
    if(diff<86400)return pad(d.getHours())+':'+pad(d.getMinutes());
    return d.getDate()+'/'+(d.getMonth()+1);
}
function fmtTimeFull(ts) { if(!ts)return'';const d=new Date(ts.replace(' ','T'));return pad(d.getHours())+':'+pad(d.getMinutes()); }
function fmtDate(ts) {
    if(!ts)return'';
    const d=new Date(ts.replace(' ','T')),now=new Date(),diff=Math.floor((now-d)/86400000);
    if(diff===0)return'Hoy';if(diff===1)return'Ayer';
    return d.getDate()+'/'+(d.getMonth()+1)+'/'+d.getFullYear();
}
function pad(n){return String(n).padStart(2,'0');}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');}
function renderTick(status){
    if(status==='read')      return '<i class="tick tick-read">✓✓</i>';
    if(status==='delivered') return '<i class="tick tick-delivered">✓✓</i>';
    if(status==='failed')    return '<i class="tick tick-failed">✗</i>';
    return '<i class="tick tick-sent">✓</i>';
}

// ── New conversation ──────────────────────────────────────────────────────────
let ncLeads = [];
function openNewChat() {
    document.getElementById('newChatModal').style.display = 'flex';
    ncTab('lead');
    document.getElementById('nc_search').focus();
    if (!ncLeads.length) {
        fetch(`${API}?action=leads_with_phone`)
            .then(r=>r.json())
            .then(d => { ncLeads = d.leads || []; ncSearch(''); });
    } else { ncSearch(''); }
}
function closeNewChat() { document.getElementById('newChatModal').style.display='none'; }
function ncTab(tab) {
    const isLead = tab === 'lead';
    document.getElementById('nc_lead_section').style.display  = isLead ? '' : 'none';
    document.getElementById('nc_phone_section').style.display = isLead ? 'none' : '';
    document.getElementById('nc_tab_lead').style.background   = isLead ? 'var(--accent)' : 'var(--surface2)';
    document.getElementById('nc_tab_lead').style.color        = isLead ? '#000' : 'var(--muted)';
    document.getElementById('nc_tab_lead').style.border       = isLead ? 'none' : '1px solid var(--border)';
    document.getElementById('nc_tab_phone').style.background  = !isLead ? 'var(--accent)' : 'var(--surface2)';
    document.getElementById('nc_tab_phone').style.color       = !isLead ? '#000' : 'var(--muted)';
    document.getElementById('nc_tab_phone').style.border      = !isLead ? 'none' : '1px solid var(--border)';
}
function ncSearch(q) {
    const list = document.getElementById('nc_lead_list');
    const results = q ? ncLeads.filter(l =>
        (l.nombre||'').toLowerCase().includes(q.toLowerCase()) ||
        (l.whatsapp||'').includes(q)
    ) : ncLeads;
    if (!results.length) {
        list.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:.8rem;padding:20px">Sin resultados</div>';
        return;
    }
    list.innerHTML = results.map(l => `
        <div onclick="ncSelectLead(${l.id},'${esc(l.nombre)}','${esc(l.whatsapp)}','${esc(l.nicho||'')}')"
            style="padding:10px 12px;border-radius:10px;border:1px solid var(--border);cursor:pointer;transition:all .15s;background:var(--bg);display:flex;align-items:center;gap:10px"
            onmouseover="this.style.borderColor='#25d366';this.style.background='rgba(37,211,102,.05)'"
            onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--bg)'">
            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#1a5c2e,#25d366);display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:.85rem;flex-shrink:0">${(l.nombre||'?').charAt(0).toUpperCase()}</div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.83rem;font-weight:700;color:var(--text)">${esc(l.nombre)}</div>
                <div style="font-size:.72rem;color:var(--muted)">${esc(l.whatsapp)}${l.nicho?` · ${esc(l.nicho)}`:''}</div>
            </div>
            <span style="font-size:.7rem;color:#25d366">&#128172;</span>
        </div>`).join('');
}
function ncSelectLead(leadId, nombre, phone, nicho) {
    closeNewChat();
    openChat(leadId, nombre, phone, nicho);
}
function ncStartPhone() {
    const phone = document.getElementById('nc_phone_input').value.trim();
    if (!phone) return;
    closeNewChat();
    openChat(null, phone, phone, '');
}

// ── Context menu ─────────────────────────────────────────────────────────────
const LABEL_DOTS = {
    nuevo:'#6b7280', potencial:'#f59e0b', calificado:'#3b82f6',
    agendado:'#8b5cf6', cerrado:'#10b981', descartado:'#ef4444'
};
function showCtxMenu(e, phone, leadId, nombre='') {
    closeCtxMenu();
    const m = document.createElement('div');
    m.className = 'ctx-menu';
    m.id = 'ctxMenu';
    const labelItems = Object.entries(LABELS).map(([k,v]) =>
        `<div class="ctx-item" onclick="ctxSetLabel('${esc(phone)}','${k}')">
            <div class="ctx-dot" style="background:${LABEL_DOTS[k]}"></div>${v.text}
        </div>`).join('');
    m.innerHTML = `
        ${nombre ? `<div style="padding:8px 12px 4px;font-size:.78rem;font-weight:800;color:var(--text);border-bottom:1px solid var(--border);margin-bottom:4px">${esc(nombre)}</div>` : ''}
        <div class="ctx-section">Cambiar etiqueta</div>
        ${labelItems}
        <div class="ctx-divider"></div>
        ${leadId
            ? `<div class="ctx-item" onclick="window.location='${ADMIN}/pages/leads.php'">&#128100; Ver lead</div>`
            : `<div class="ctx-item" onclick="closeCtxMenu();openLeadModal('${esc(phone)}')">&#127919; Convertir a lead</div>`
        }
        <div class="ctx-item" onclick="closeCtxMenu();openChat(${leadId||'null'},'${esc(nombre)}','${esc(phone)}','')">&#128172; Abrir chat</div>
        <div class="ctx-divider"></div>
        <div class="ctx-item" style="color:#ef4444" onclick="closeCtxMenu();deleteChat('${esc(phone)}',${leadId||'null'},'${esc(nombre)}')">&#128465; Eliminar chat</div>`;
    // Position near cursor
    document.body.appendChild(m);
    const rect = m.getBoundingClientRect();
    let x = e.clientX, y = e.clientY;
    if (x + 200 > window.innerWidth)  x = window.innerWidth - 205;
    if (y + rect.height > window.innerHeight) y = y - rect.height;
    m.style.left = x + 'px';
    m.style.top  = y + 'px';
    setTimeout(() => document.addEventListener('click', closeCtxMenu, {once:true}), 10);
}
function closeCtxMenu() {
    document.getElementById('ctxMenu')?.remove();
}
function deleteChat(phone, leadId, nombre) {
    if (!confirm(`¿Eliminar todos los mensajes con ${nombre || phone}?`)) return;

    fetch(CTX_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_chat', phone, lead_id: leadId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('✓ Chat eliminado');
            activePhone = null;
            activeLeadId = null;
            document.getElementById('chatArea').innerHTML = '';
            loadConversations();
        } else {
            alert('❌ Error: ' + (data.msg || 'desconocido'));
        }
    })
    .catch(e => alert('Error: ' + e.message));
}
function ctxSetLabel(phone, label) {
    closeCtxMenu();
    fetch(CTX_API, { method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'label', phone, label}) })
    .then(()=>{ loadConversations(); if(activePhone===phone||true) loadInfoPanel(activeLeadId,null,activePhone||phone,null); });
}

loadConversations();
setInterval(loadConversations, 3000);

// Auto-open lead or prospect from URL params
// ── Media modal para imágenes ────────────────────────────────────────────────
function openMediaModal(mediaId, type, mimeType) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.style.zIndex = '300';
    modal.onclick = e => { if (e.target === modal) modal.remove(); };

    let content = '';
    if (type === 'image') {
        content = `
            <div style="display:flex;align-items:center;justify-content:center;height:100%">
                <img src="${ADMIN}/api/wa-media.php?media_id=${esc(mediaId)}&type=image"
                     style="max-width:90%;max-height:90%;border-radius:12px">
            </div>`;
    } else {
        content = '<div style="color:white;text-align:center">Media no soportado en preview</div>';
    }

    modal.innerHTML = content;
    document.body.appendChild(modal);
}

// ── Media Upload Functions ──
let selectedMedia = null;

// ── Audio Recorder ──
let mediaRecorder = null;
let recordingChunks = [];
let recordingStartTime = 0;
let recordingInterval = null;

async function toggleRecorder() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        clearInterval(recordingInterval);
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        // Detectar MIME type REALMENTE compatible (NO WebM)
        // Prioridad: OGG > otro formato soportado nativamente
        const preferredMimes = [
            'audio/ogg;codecs=opus',  // OGG con Opus - ideal para WhatsApp
            'audio/ogg',               // OGG básico
        ];

        let selectedMime = null;
        let mediaRecorderOptions = {};

        console.log('🎙️ Detectando formatos de audio soportados...');
        for (const mime of preferredMimes) {
            const supported = MediaRecorder.isTypeSupported(mime);
            console.log(`  ${mime}: ${supported ? '✓ SOPORTADO' : '✗ no soportado'}`);
            if (supported) {
                selectedMime = mime;
                mediaRecorderOptions = { mimeType: mime };
                console.log('🎙️ ✓ Usando:', selectedMime);
                break;
            }
        }

        if (!selectedMime) {
            console.error('❌ ERROR: El navegador no soporta OGG/Opus nativo');
            alert('⚠️ Tu navegador no puede grabar notas de voz en formato compatible con WhatsApp.\n\nFormatos requeridos: audio/ogg o audio/ogg;codecs=opus\n\nIntenta con Chrome, Firefox o Edge versiones recientes.');
            stream.getTracks().forEach(track => track.stop());
            return;
        }

        // Crear MediaRecorder CON MIME TYPE ESPECÍFICO
        mediaRecorder = new MediaRecorder(stream, mediaRecorderOptions);
        recordingChunks = [];

        mediaRecorder.ondataavailable = (e) => {
            if (e.data.size > 0) recordingChunks.push(e.data);
        };

        mediaRecorder.onstop = () => {
            // El Blob debe usar EXACTAMENTE el mime type del recorder
            const actualMimeType = mediaRecorder.mimeType;
            const blob = new Blob(recordingChunks, { type: actualMimeType });

            // Determinar extensión (siempre .ogg para OGG)
            const extension = actualMimeType.includes('ogg') ? 'ogg' : 'ogg';

            const filename = `nota-voz-${Date.now()}.${extension}`;
            const file = new File([blob], filename, { type: actualMimeType });

            // DEBUG: Confirmar que todo coincide
            console.log('📁 AUDIO RECORDING DEBUG');
            console.log('  mimeType seleccionado:', selectedMime);
            console.log('  mediaRecorder.mimeType:', mediaRecorder.mimeType);
            console.log('  blob.type:', blob.type);
            console.log('  file.type:', file.type);
            console.log('  filename:', filename);
            console.log('  filesize:', file.size, 'bytes');
            console.log('  ✓ Todos coinciden:', actualMimeType === mediaRecorder.mimeType && actualMimeType === blob.type);

            selectedMedia = {
                file: file,
                name: file.name,
                size: file.size,
                type: actualMimeType,
                mediaType: 'audio',
            };

            showRecordingPreview(file);
            stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start();
        recordingStartTime = Date.now();
        document.getElementById('recorderControls').classList.add('active');

        recordingInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
            const mins = Math.floor(elapsed / 60);
            const secs = elapsed % 60;
            document.getElementById('recorderTimer').textContent =
                `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }, 100);
    } catch (error) {
        alert('Error al acceder al micrófono: ' + error.message);
    }
}

function toggleRecord() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        clearInterval(recordingInterval);
        document.getElementById('recorderControls').classList.remove('active');
    }
}

function cancelRecording() {
    if (mediaRecorder) {
        mediaRecorder.stop();
        clearInterval(recordingInterval);
    }
    selectedMedia = null;
    document.getElementById('recorderControls').classList.remove('active');
    document.getElementById('mediaPreviewContainer').innerHTML = '';
    document.getElementById('mediaCaption').style.display = 'none';
}

function showRecordingPreview(file) {
    const container = document.getElementById('mediaPreviewContainer');
    const captionInput = document.getElementById('mediaCaption');

    container.innerHTML = `
        <div class="media-preview">
            <div style="font-size:2rem">🎵</div>
            <div class="media-preview-info">
                <div class="media-preview-name">${file.name}</div>
                <div class="media-preview-size">${(file.size / 1024).toFixed(2)} KB</div>
            </div>
            <button class="media-preview-remove" onclick="clearMediaPreview()">✕</button>
        </div>
    `;

    captionInput.style.display = 'block';
    captionInput.value = '';
}

function handleMediaSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    selectedMedia = {
        file: file,
        name: file.name,
        size: file.size,
        type: file.type,
    };

    // Detectar tipo de media
    let mediaType = 'image';
    if (file.type.startsWith('audio/')) mediaType = 'audio';
    else if (file.type.startsWith('video/')) mediaType = 'video';
    else if (file.type === 'application/pdf' || file.type.includes('document')) mediaType = 'document';

    selectedMedia.mediaType = mediaType;

    showMediaPreview(file, mediaType);
}

function showMediaPreview(file, mediaType) {
    const container = document.getElementById('mediaPreviewContainer');
    const captionInput = document.getElementById('mediaCaption');

    let preview = '';
    const icons = { image: '🖼️', audio: '🎵', video: '🎬', document: '📄' };
    const icon = icons[mediaType] || '📎';

    if (file.type.startsWith('image/')) {
        const url = URL.createObjectURL(file);
        preview = `<img src="${url}" style="max-width:60px;max-height:60px;border-radius:6px;object-fit:cover;">`;
    } else if (file.type.startsWith('video/')) {
        const url = URL.createObjectURL(file);
        preview = `<video style="max-width:60px;max-height:60px;border-radius:6px;object-fit:cover;"><source src="${url}"></video>`;
    } else {
        preview = `<div style="font-size:2rem">${icon}</div>`;
    }

    container.innerHTML = `
        <div class="media-preview">
            ${preview}
            <div class="media-preview-info">
                <div class="media-preview-name">${file.name}</div>
                <div class="media-preview-size">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
            </div>
            <button class="media-preview-remove" onclick="clearMediaPreview()">✕</button>
        </div>
    `;

    captionInput.style.display = 'block';
    captionInput.value = '';
}

function clearMediaPreview() {
    selectedMedia = null;
    document.getElementById('mediaFileInput').value = '';
    document.getElementById('mediaPreviewContainer').innerHTML = '';
    document.getElementById('mediaCaption').style.display = 'none';
    document.getElementById('mediaCaption').value = '';
}

function sendMediaMessage() {
    if (!selectedMedia) {
        alert('Selecciona un archivo primero');
        return;
    }

    if (!activeLeadId && !activePhone) {
        alert('Selecciona una conversación');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'send_media');
    if (activeLeadId) formData.append('lead_id', activeLeadId);
    if (activePhone) formData.append('phone', activePhone);
    formData.append('media_type', selectedMedia.mediaType);
    formData.append('file', selectedMedia.file);
    formData.append('caption', document.getElementById('mediaCaption').value);

    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.textContent = '⏳';

    fetch(`${ADMIN}/api/wa-send-media.php?action=send_media`, {
        method: 'POST',
        body: formData,
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            clearMediaPreview();
            loadMessages(false);
        } else {
            // Show detailed error info for debugging
            let errorMsg = d.msg || 'No se pudo enviar';
            if (d.debug) {
                console.error('📤 Error al enviar multimedia:', d.debug);
                if (d.debug.http_code) {
                    errorMsg += ` [HTTP ${d.debug.http_code}]`;
                }
                if (d.debug.error) {
                    errorMsg += ` - ${JSON.stringify(d.debug.error).substring(0, 100)}`;
                }
            }
            alert('Error: ' + errorMsg);
        }
    })
    .catch(e => {
        console.error('❌ Fetch error:', e);
        alert('Error: ' + e.message);
    })
    .finally(() => {
        sendBtn.disabled = false;
        sendBtn.textContent = '▶';
    });
}

(function() {
    const params   = new URLSearchParams(location.search);
    const openId   = params.get('open_lead');
    const openPhone = params.get('open_phone');
    const openName  = params.get('open_name');

    if (openId) {
        fetch(`${API}?action=messages&lead_id=${openId}&since=0`)
            .then(r=>r.json())
            .then(d => {
                if (d.lead) openChat(d.lead.id, d.lead.nombre, d.lead.whatsapp, d.lead.nicho||'');
            });
    } else if (openPhone) {
        // Open directly by phone (prospect not yet a lead)
        openChat(null, decodeURIComponent(openName || openPhone), decodeURIComponent(openPhone), '');
    }
})();

// ── Image Lightbox Functions ──
function hideImageLoading(msgId) {
    const loading = document.getElementById(`img-loading-${msgId}`);
    const img = document.getElementById(`img-${msgId}`);
    if (loading) loading.style.display = 'none';
    if (img) img.style.display = 'block';
}

function showImageError(msgId) {
    const loading = document.getElementById(`img-loading-${msgId}`);
    const error = document.getElementById(`img-error-${msgId}`);
    if (loading) loading.style.display = 'none';
    if (error) error.style.display = 'flex';
}

function openImageLightbox(mediaId, event) {
    event.stopPropagation();

    let overlay = document.getElementById('imageLightboxOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'imageLightboxOverlay';
        overlay.className = 'image-lightbox-overlay';
        overlay.innerHTML = `
            <div class="image-lightbox-container">
                <button class="image-lightbox-close" onclick="closeImageLightbox()">&times;</button>
                <img id="lightboxImg" src="${ADMIN}/api/wa-media.php?media_id=${mediaId}" alt="Imagen" class="image-lightbox-img">
            </div>
        `;
        document.body.appendChild(overlay);

        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeImageLightbox();
        });

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeImageLightbox();
        });
    } else {
        document.getElementById('lightboxImg').src = `${ADMIN}/api/wa-media.php?media_id=${mediaId}`;
    }

    overlay.classList.add('active');
}

function closeImageLightbox() {
    const overlay = document.getElementById('imageLightboxOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

</script>

<?php include __DIR__ . '/../../views/admin/footer.php'; ?>
