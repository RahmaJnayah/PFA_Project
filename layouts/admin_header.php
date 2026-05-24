<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=IM+Fell+English+SC&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet" />

  <style>
    /* ═══════════════════════════════════════════════════════
       YOPY ADMIN — VINTAGE SOULS EDITION (Brown/Evil Vibe)
    ═══════════════════════════════════════════════════════ */
    :root {
      --bg-deep:        #0a0502;
      --bg-base:        #140e09;
      --bg-card:        rgba(20, 14, 9, 0.75);
      --bg-surface:     rgba(18, 10, 6, 0.95);
      --rune-gold:      #b87a44;
      --rune-glow:      #dc9f5c;
      --ember:          #c2622a;
      --brown-light:    #dba870;
      --brown-muted:    #c69764;
      --brown-dark:     #9f7a58;
      --brown-ink:      #6b4935;
      --rust:           #8b3a2a;
      --glass-border:   rgba(120, 70, 40, 0.35);
      --glass-border-h: rgba(185, 110, 60, 0.6);
      --text-primary:   #ecd9c6;
      --text-muted:     #c69764;
      --text-faint:     #9f7a58;
      --success:        #6b9e5c;
      --error:          #c96a4a;
      --warning:        #e6a34b;
      --sidebar-w:      260px;
      --topbar-h:       64px;
      --radius:         14px;
      --radius-lg:      22px;
      --font-display:   "Cinzel", "IM Fell English SC", serif;
      --font-body:      "DM Sans", sans-serif;
      --transition:     0.25s ease;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      width: 100%; min-height: 100vh;
      background: var(--bg-deep);
      font-family: var(--font-body);
      color: var(--text-primary);
      overflow-x: hidden;
    }

    /* ── Scrollbar (Rusty) ── */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg-deep); }
    ::-webkit-scrollbar-thumb { background: #7a4a2a; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: #a5623a; }

    /* ── Background & Overlays ── */
    #bg-canvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; opacity: 0.8; }

    .bg-overlay {
      position: fixed; inset: 0; z-index: 1; pointer-events: none;
      background:
        radial-gradient(ellipse 70% 60% at 10% 15%, rgba(80, 40, 20, 0.4) 0%, transparent 60%),
        radial-gradient(ellipse 50% 45% at 90% 85%, rgba(180, 80, 30, 0.2) 0%, transparent 55%);
      mix-blend-mode: multiply;
    }

    /* Texture overlay for that 'Grunge' feel */
    body::after {
      content: ""; position: fixed; inset: 0; z-index: 1; pointer-events: none; opacity: 0.05;
      background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iNDAwIiB2aWV3Qm94PSIwIDAgNDAwIDQwMCI+PGZpbHRlciBpZD0ibm9pc2UiPjxmZVR1cmJ1bGVuY2UgdHlwZT0iZnJhY3RhbE5vaXNlIiBiYXNlRnJlcXVlbmN5PSIuNyIgbnVtT2N0YXZlcz0iMyIgc3RpdGNoVGlsZXM9InN0aXRjaCIvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNub2lzZSkiLz48L3N2Zz4=');
    }

    /* ── Layout shell ── */
    .admin-layout { position: relative; z-index: 2; display: flex; min-height: 100vh; }

    /* ── Top bar (Aged Dark) ── */
    .top-bar {
      position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: var(--topbar-h); z-index: 50;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 32px;
      background: linear-gradient(to bottom, rgba(10, 5, 2, 0.96) 0%, rgba(10, 5, 2, 0.88) 100%);
      border-bottom: 1px solid var(--glass-border);
      backdrop-filter: blur(12px);
    }

    .top-bar-left { display: flex; align-items: center; gap: 12px; }

    .breadcrumb {
      font-size: 0.7rem; letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--brown-dark);
    }
    .breadcrumb span { color: var(--brown-muted); }

    .page-heading {
      font-family: var(--font-display); font-size: 1rem; font-weight: 600;
      letter-spacing: 0.15em; color: var(--text-primary);
      text-shadow: 0 1px 2px rgba(0,0,0,0.4);
    }

    .top-bar-right { display: flex; align-items: center; gap: 16px; }

    .admin-badge {
      font-size: 0.65rem; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--brown-light); background: rgba(90, 50, 25, 0.5);
      border: 1px solid var(--glass-border); padding: 4px 12px; border-radius: 40px;
    }

    .btn-logout {
      font-family: var(--font-body); font-size: 0.68rem; font-weight: 500;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--brown-muted); background: rgba(70, 40, 20, 0.4);
      border: 1px solid var(--glass-border); padding: 6px 16px; border-radius: 40px;
      cursor: pointer; text-decoration: none; transition: all var(--transition);
    }
    .btn-logout:hover {
      color: var(--error); border-color: #b85a3a;
      background: rgba(150, 60, 30, 0.25);
    }

    /* ── Main content ── */
    .main-content {
      margin-left: var(--sidebar-w);
      margin-top: var(--topbar-h);
      flex: 1; padding: 32px 36px 60px;
      min-height: calc(100vh - var(--topbar-h));
    }

    /* ── Flash messages ── */
    .flash {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 18px; border-radius: var(--radius);
      margin-bottom: 28px; font-size: 0.85rem; font-weight: 500;
      animation: fadeIn 0.4s ease;
      background: rgba(20, 12, 8, 0.85); backdrop-filter: blur(4px);
      border-left: 4px solid;
    }
    .flash.success { border-left-color: var(--success); color: #cde2be; }
    .flash.error   { border-left-color: var(--error); color: #f3c6b0; }

    /* ── Cards ── */
    .card {
      background: var(--bg-card); border: 1px solid var(--glass-border);
      border-radius: var(--radius-lg); backdrop-filter: blur(16px);
      padding: 26px; transition: border-color var(--transition), box-shadow 0.2s;
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }
    .card:hover { border-color: var(--glass-border-h); }

    /* ── Buttons ── */
    .btn {
      display: inline-flex; align-items: center; gap: 7px;
      font-family: var(--font-body); font-size: 0.75rem; font-weight: 500;
      letter-spacing: 0.1em; text-transform: uppercase;
      padding: 9px 20px; border-radius: 60px; border: 1px solid transparent;
      cursor: pointer; text-decoration: none; transition: all var(--transition);
    }
    .btn-primary {
      background: linear-gradient(135deg, #3a2418, #5a3822);
      border-color: #b87a44; color: #fbe9c3;
      box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    }
    .btn-primary:hover {
      background: linear-gradient(135deg, #5a3822, #7a4e2e);
      border-color: #dc9f5c; transform: translateY(-1px);
      box-shadow: 0 0 12px rgba(200, 100, 30, 0.4);
    }

    .btn-ghost {
      background: rgba(40, 25, 15, 0.6); color: var(--brown-light);
      border-color: #7a553b;
    }
    .btn-ghost:hover { background: #4a2e1c; border-color: #c28248; color: #fae6c9; }

    .btn-sm { padding: 5px 14px; font-size: 0.65rem; }

    /* ── Tables ── */
    .table-wrap { overflow-x: auto; border-radius: var(--radius-lg); }
    table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    thead th {
      text-align: left; padding: 12px 16px;
      font-family: var(--font-display); font-size: 0.7rem; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--brown-muted);
      border-bottom: 1px solid var(--glass-border);
    }
    tbody tr { border-bottom: 1px solid rgba(120, 70, 40, 0.2); transition: background var(--transition); }
    tbody tr:hover { background: rgba(80, 50, 30, 0.25); }
    tbody td { padding: 12px 16px; color: var(--text-muted); vertical-align: middle; }
    tbody td:first-child { color: var(--text-primary); font-weight: 500; }

    /* ── Badges ── */
    .badge {
      display: inline-block; padding: 2px 10px; border-radius: 40px;
      font-size: 0.65rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;
    }
    .badge-green   { background: rgba(80, 110, 60, 0.25); color: #b8d9a4; border: 1px solid #6b9e5c; }
    .badge-red     { background: rgba(150, 60, 30, 0.3); color: #e6a68b; border: 1px solid #c96a4a; }
    .badge-violet  { background: rgba(140, 90, 50, 0.3); color: #dba870; border: 1px solid #b87a44; }
    .badge-amber   { background: rgba(180, 110, 40, 0.3); color: #f3deba; border: 1px solid #c9a03d; }
    .badge-grey    { background: rgba(70, 50, 35, 0.4); color: #b8a27a; border: 1px solid #8b765a; }

    /* ── Forms ── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    label { font-size: 0.7rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--brown-muted); font-family: var(--font-display); }
    input[type=text], input[type=email], input[type=password], select, textarea {
      background: rgba(10, 5, 2, 0.75); border: 1px solid var(--glass-border);
      color: var(--text-primary); border-radius: var(--radius);
      padding: 10px 14px; font-family: var(--font-body); font-size: 0.85rem;
      outline: none; transition: all var(--transition);
    }
    input:focus, select:focus, textarea:focus { border-color: var(--rune-glow); box-shadow: 0 0 0 2px rgba(185, 110, 60, 0.3); }

    /* ── Section header ── */
    .section-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 1rem; }
    .section-title {
      font-family: var(--font-display); font-size: 1.4rem; font-weight: 700;
      letter-spacing: 0.1em; background: linear-gradient(135deg, #ebc28e, #b5783a);
      -webkit-background-clip: text; background-clip: text; color: transparent;
    }

    /* ── Animations ── */
    @keyframes fadeIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
    .fade-in { animation: fadeIn 0.5s ease both; }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="bg-overlay"></div>

<div class="admin-layout">
<header class="top-bar">
  <div class="top-bar-left">
    <div>
      <div class="breadcrumb">Admin <span>/</span> <?= htmlspecialchars($pageTitle ?? APP_NAME) ?></div>
      <div class="page-heading"><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></div>
    </div>
  </div>
  <div class="top-bar-right">
    <span class="admin-badge">Super Admin</span>
    <a class="btn-logout" href="<?= $basePath ?>/admin.php?action=logout">Logout</a>
  </div>
</header>

<script>
  /* Vintage Ember Particles Animation */
  (function() {
    const canvas = document.getElementById('bg-canvas');
    if(!canvas) return;
    const ctx = canvas.getContext('2d');
    let width, height, particles = [];
    const colors = ['#4f2a1b', '#7b3f1a', '#b45f2b', '#5a2e18', '#3f1f0c', '#ab6a36', '#8b4513'];

    function resize() {
      width = canvas.width = window.innerWidth;
      height = canvas.height = window.innerHeight;
      particles = [];
      for (let i = 0; i < 150; i++) {
        particles.push({
          x: Math.random() * width, y: Math.random() * height,
          r: Math.random() * 2.5 + 0.5,
          vy: Math.random() * 0.4 + 0.1,
          vx: (Math.random() - 0.5) * 0.1,
          opacity: Math.random() * 0.5 + 0.1,
          color: colors[Math.floor(Math.random() * colors.length)]
        });
      }
    }

    function draw() {
      ctx.clearRect(0, 0, width, height);
      for (let p of particles) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = p.opacity;
        ctx.shadowBlur = 5;
        ctx.shadowColor = '#b85f2a';
        ctx.fill();
        p.y -= p.vy; p.x += p.vx;
        if (p.y < -10) p.y = height + 10;
      }
      requestAnimationFrame(draw);
    }
    window.addEventListener('resize', resize);
    resize(); draw();
  })();
</script>