<?php $styles = style_settings($pdo); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title><?= h($pageTitle ?? ($config['app_name'] ?? 'Retiro 2027 - ICNV Catedral')) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root{
      --rt-primary: <?= h($styles['primary_color']) ?>;
      --rt-accent: <?= h($styles['accent_color']) ?>;
      --rt-surface: <?= h($styles['surface_color']) ?>;
      --rt-panel-bg: <?= h($styles['panel_bg_color']) ?>;
      --rt-card-shadow: <?= h($styles['card_shadow']) ?>;
      --rt-font-family: <?= h($styles['font_family']) ?>;
      --rt-font-size: <?= h($styles['font_size']) ?>px;
      --rt-font-weight: <?= h($styles['font_weight']) ?>;
      --rt-font-style: <?= h($styles['font_style']) ?>;
    }
    body { font-family: var(--rt-font-family); font-size: var(--rt-font-size); font-weight: var(--rt-font-weight); font-style: var(--rt-font-style); background:
      radial-gradient(circle at top left, rgba(13,148,136,.08), transparent 28%),
      radial-gradient(circle at top right, rgba(16,185,129,.07), transparent 24%),
      #f4f8f7; }
    .rt-btn-primary{ background: linear-gradient(135deg, color-mix(in srgb, var(--rt-primary) 92%, black), var(--rt-accent)); color: #fff; box-shadow: 0 12px 24px rgba(15,118,110,.18); }
    .rt-btn-secondary{ background: #fff; color: #0f172a; border: 1px solid #cbd5e1; box-shadow: 0 8px 18px rgba(15,23,42,.06); }
    .rt-card{ background: var(--rt-surface); box-shadow: var(--rt-card-shadow); border: 1px solid rgba(148,163,184,.14); }
    .rt-chip{ display:inline-flex; align-items:center; gap:.45rem; border-radius:999px; padding:.45rem .8rem; background:#ecfeff; color:#0f766e; font-weight:600; font-size:.9rem; }
    .rt-hero{ background: linear-gradient(135deg, rgba(11,59,57,.96), rgba(15,118,110,.88)); color:#fff; position:relative; overflow:hidden; }
    .rt-hero::before{ content:''; position:absolute; inset:0; background: radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 24%), radial-gradient(circle at bottom left, rgba(45,212,191,.18), transparent 28%); pointer-events:none; }
    .rt-stat-card{ position:relative; overflow:hidden; }
    .rt-stat-card::after{ content:''; position:absolute; inset:auto -40px -40px auto; width:120px; height:120px; border-radius:999px; background:rgba(255,255,255,.18); }
    .rt-admin-panel{ background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(255,255,255,.85)); backdrop-filter: blur(10px); }
    .sortable th a{ text-decoration:none; display:block; }
    input[type=text], input[type=email], input[type=number], input[type=password], input[type=date], input[type=file], select, textarea {
      width: 100%;
      border: 1px solid #cfd8e3;
      border-radius: 0.9rem;
      padding: 0.72rem 0.9rem;
      background: #fff;
      box-shadow: inset 0 1px 2px rgba(15,23,42,.03), 0 2px 10px rgba(15,23,42,.03);
      transition: border-color .2s, box-shadow .2s;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: var(--rt-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--rt-primary) 18%, white);
    }
    input[readonly], textarea[readonly], select[disabled], input[disabled] {
      background: #f3f6fb;
      color: #475569;
      border-color: #dbe5f0;
      box-shadow: inset 0 1px 2px rgba(15,23,42,.02);
      cursor: default;
    }
    label { display:block; font-weight:600; color:#334155; margin-bottom:.35rem; }
    .rt-input-wrap{ margin-bottom: 1rem; }

    .rt-admin-shell{ display:flex; min-height:100vh; }
    .rt-admin-sidebar-overlay{ position:fixed; inset:0; background:rgba(15,23,42,.45); opacity:0; pointer-events:none; transition:opacity .25s ease; z-index:40; }
    .rt-admin-sidebar-overlay.is-open{ opacity:1; pointer-events:auto; }
    .rt-admin-sidebar{ width:18rem; flex:0 0 18rem; background: linear-gradient(180deg, color-mix(in srgb, var(--rt-primary) 50%, #052e2b), color-mix(in srgb, var(--rt-panel-bg) 82%, #021312)); }
    .rt-admin-content{ flex:1; min-width:0; }
    .rt-admin-mobile-topbar{ display:none; position:sticky; top:0; z-index:35; backdrop-filter: blur(10px); background:rgba(248,250,252,.92); border-bottom:1px solid #e2e8f0; }
    .rt-admin-hamburger{ display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:14px; border:1px solid #cbd5e1; background:#fff; box-shadow:0 2px 10px rgba(15,23,42,.05); }
    .rt-admin-hamburger span, .rt-admin-hamburger::before, .rt-admin-hamburger::after{ content:""; display:block; width:18px; height:2px; background:#0f172a; border-radius:999px; transition:transform .2s ease, opacity .2s ease; }
    .rt-admin-hamburger{ position:relative; }
    .rt-admin-hamburger span{ position:absolute; }
    .rt-admin-hamburger::before{ position:absolute; transform:translateY(-6px); }
    .rt-admin-hamburger::after{ position:absolute; transform:translateY(6px); }
    body.rt-admin-menu-open{ overflow:hidden; }
    @media (max-width: 1023px){
      .rt-admin-shell{ display:block; }
      .rt-admin-mobile-topbar{ display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; }
      .rt-admin-sidebar{ position:fixed; top:0; left:0; bottom:0; max-width:86vw; width:18rem; transform:translateX(-105%); transition:transform .25s ease; z-index:50; box-shadow:0 24px 48px rgba(15,23,42,.28); overflow-y:auto; }
      .rt-admin-sidebar.is-open{ transform:translateX(0); }
      .rt-admin-content{ width:100%; }
    }

    .rt-theme-toggle{ display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:14px; border:1px solid rgba(255,255,255,.18); background:rgba(255,255,255,.10); color:#fff; box-shadow:0 8px 24px rgba(15,23,42,.18); }
    .rt-menu-section-toggle{ color:#f8fafc; }
    .rt-menu-section-items{ max-height:0; overflow:hidden; transition:max-height .25s ease; }
    .rt-menu-section-items.is-open{ max-height:520px; }
    .rt-menu-chevron{ transition:transform .22s ease; }
    .rt-menu-chevron.is-open{ transform:rotate(180deg); }
    .rt-admin-topbar{ display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; margin-bottom:1rem; border:1px solid rgba(148,163,184,.15); background:rgba(255,255,255,.78); backdrop-filter: blur(14px); }
    .rt-admin-title{ font-size:1.35rem; font-weight:700; color:#0f172a; }
    .rt-admin-subtitle{ font-size:.92rem; color:#64748b; }
    .rt-dark body, body.rt-dark{ background:#020617; color:#e2e8f0; }
    .rt-dark .rt-card, body.rt-dark .rt-card{ background:#0f172a; color:#e2e8f0; box-shadow:0 20px 40px rgba(0,0,0,.28); }
    .rt-dark .bg-white, body.rt-dark .bg-white{ background:#0f172a !important; color:#e2e8f0; }
    .rt-dark .bg-slate-100, body.rt-dark .bg-slate-100{ background:#020617 !important; }
    .rt-dark .text-slate-800, .rt-dark .text-slate-900, body.rt-dark .text-slate-800, body.rt-dark .text-slate-900{ color:#f8fafc !important; }
    .rt-dark .text-slate-700, .rt-dark .text-slate-600, .rt-dark .text-slate-500, body.rt-dark .text-slate-700, body.rt-dark .text-slate-600, body.rt-dark .text-slate-500{ color:#cbd5e1 !important; }
    .rt-dark .border-slate-200, .rt-dark .border-slate-300, body.rt-dark .border-slate-200, body.rt-dark .border-slate-300{ border-color:#334155 !important; }
    .rt-dark input[type=text], .rt-dark input[type=email], .rt-dark input[type=number], .rt-dark input[type=password], .rt-dark input[type=date], .rt-dark input[type=file], .rt-dark select, .rt-dark textarea,
    body.rt-dark input[type=text], body.rt-dark input[type=email], body.rt-dark input[type=number], body.rt-dark input[type=password], body.rt-dark input[type=date], body.rt-dark input[type=file], body.rt-dark select, body.rt-dark textarea { background:#0b1220; color:#e2e8f0; border-color:#334155; box-shadow: inset 0 1px 2px rgba(0,0,0,.25), 0 2px 10px rgba(0,0,0,.12); }
    .rt-dark input[readonly], .rt-dark textarea[readonly], .rt-dark select[disabled], .rt-dark input[disabled], body.rt-dark input[readonly], body.rt-dark textarea[readonly], body.rt-dark select[disabled], body.rt-dark input[disabled] { background:#111827; color:#94a3b8; border-color:#374151; }
    .rt-dark .rt-admin-mobile-topbar, body.rt-dark .rt-admin-mobile-topbar{ background:rgba(2,6,23,.92); border-bottom-color:#1e293b; }
    .rt-dark .rt-admin-hamburger, body.rt-dark .rt-admin-hamburger{ background:#0f172a; border-color:#334155; }
    .rt-dark .rt-admin-hamburger span, .rt-dark .rt-admin-hamburger::before, .rt-dark .rt-admin-hamburger::after,
    body.rt-dark .rt-admin-hamburger span, body.rt-dark .rt-admin-hamburger::before, body.rt-dark .rt-admin-hamburger::after{ background:#f8fafc; }
    .rt-dark .rt-theme-toggle, body.rt-dark .rt-theme-toggle{ background:rgba(15,23,42,.92); border-color:#475569; }
    .rt-dark table, body.rt-dark table{ color:#e2e8f0; }
    .rt-dark .hover\:bg-slate-50:hover, body.rt-dark .hover\:bg-slate-50:hover{ background:#111827 !important; }

  </style>
  <?= rt2027_favicon_links() ?>
  <link rel="stylesheet" href="assets/css/system.css?v=17">
</head>
<body class="text-slate-800 min-h-screen">
<script>
(function(){
  try {
    if (localStorage.getItem('rt-admin-theme') === 'dark') {
      document.documentElement.classList.add('rt-dark');
      document.body.classList.add('rt-dark');
    }
  } catch(e) {}
})();
</script>
