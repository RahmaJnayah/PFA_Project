<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet" />

  <style>
    /* ═══════════════════════════════════════════════════════
       YOPY ADMIN — Global Styles
    ═══════════════════════════════════════════════════════ */
    :root {
      --bg-deep:        #0d0718;
      --bg-base:        #1c0f30;
      --bg-card:        rgba(35, 20, 66, 0.7);
      --bg-surface:     rgba(28, 15, 48, 0.95);
      --violet-royal:   #7c3aed;
      --violet-soft:    #a78bfa;
      --lilac:          #c4b5fd;
      --lilac-light:    #ede9fe;
      --pink-accent:    #e879f9;
      --teal-accent:    #2dd4bf;
      --amber-accent:   #fbbf24;
      --glass-border:   rgba(167, 139, 250, 0.18);
      --glass-border-h: rgba(167, 139, 250, 0.45);
      --glow-violet:    rgba(124, 58, 237, 0.5);
      --text-primary:   #f0eaff;
      --text-muted:     #9d86c8;
      --text-faint:     #5a4880;
      --success:        #34d399;
      --error:          #f87171;
      --warning:        #fbbf24;
      --sidebar-w:      260px;
      --topbar-h:       64px;
      --radius:         14px;
      --radius-lg:      22px;
      --font-display:   "Cinzel", serif;
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

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg-deep); }
    ::-webkit-scrollbar-thumb { background: rgba(124,58,237,0.4); border-radius: 3px; }

    /* ── Background canvas ── */
    #bg-canvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; }

    .bg-overlay {
      position: fixed; inset: 0; z-index: 1; pointer-events: none;
      background:
        radial-gradient(ellipse 80% 60% at 5% 5%,  rgba(124,58,237,0.15) 0%, transparent 55%),
        radial-gradient(ellipse 55% 45% at 95% 90%, rgba(232,121,249,0.10) 0%, transparent 50%);
    }

    /* ── Layout shell ── */
    .admin-layout {
      position: relative; z-index: 2;
      display: flex; min-height: 100vh;
    }

    /* ── Top bar ── */
    .top-bar {
      position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: var(--topbar-h); z-index: 50;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 32px;
      background: linear-gradient(to bottom, rgba(13,7,24,0.96) 0%, rgba(13,7,24,0.85) 100%);
      border-bottom: 1px solid var(--glass-border);
      backdrop-filter: blur(16px);
    }

    .top-bar-left { display: flex; align-items: center; gap: 12px; }

    .breadcrumb {
      font-size: 0.8rem; letter-spacing: 0.08em; text-transform: uppercase;
      color: var(--text-faint);
    }
    .breadcrumb span { color: var(--text-muted); }

    .page-heading {
      font-family: var(--font-display); font-size: 1.05rem; font-weight: 600;
      letter-spacing: 0.1em; color: var(--text-primary);
    }

    .top-bar-right { display: flex; align-items: center; gap: 16px; }

    .admin-badge {
      font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase;
      color: var(--lilac); background: rgba(124,58,237,0.15);
      border: 1px solid rgba(124,58,237,0.3); padding: 5px 14px; border-radius: 50px;
    }

    .btn-logout {
      font-family: var(--font-body); font-size: 0.72rem; font-weight: 500;
      letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--text-muted); background: rgba(124,58,237,0.08);
      border: 1px solid rgba(167,139,250,0.18); padding: 7px 18px; border-radius: 50px;
      cursor: pointer; text-decoration: none;
      transition: all var(--transition);
    }
    .btn-logout:hover {
      color: var(--error); border-color: rgba(248,113,113,0.4);
      background: rgba(248,113,113,0.08);
    }

    /* ── Main content ── */
    .main-content {
      margin-left: var(--sidebar-w);
      margin-top: var(--topbar-h);
      flex: 1; padding: 36px 40px 80px;
      min-height: calc(100vh - var(--topbar-h));
    }

    /* ── Flash messages ── */
    .flash {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 20px; border-radius: var(--radius);
      margin-bottom: 28px; font-size: 0.88rem; font-weight: 500;
      animation: fadeIn 0.4s ease;
    }
    .flash.success { background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.3); color: var(--success); }
    .flash.error   { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: var(--error); }

    /* ── Cards ── */
    .card {
      background: var(--bg-card); border: 1px solid var(--glass-border);
      border-radius: var(--radius-lg); backdrop-filter: blur(20px);
      padding: 28px; transition: border-color var(--transition);
    }
    .card:hover { border-color: var(--glass-border-h); }

    /* ── Buttons ── */
    .btn {
      display: inline-flex; align-items: center; gap: 7px;
      font-family: var(--font-body); font-size: 0.8rem; font-weight: 500;
      letter-spacing: 0.07em; text-transform: uppercase;
      padding: 10px 22px; border-radius: 50px; border: none; cursor: pointer;
      text-decoration: none; transition: all var(--transition);
    }
    .btn-primary {
      background: linear-gradient(135deg, #7c3aed, #a855f7);
      color: #fff;
      box-shadow: 0 0 20px rgba(124,58,237,0.3);
    }
    .btn-primary:hover { box-shadow: 0 0 30px rgba(124,58,237,0.55); transform: translateY(-1px); }

    .btn-ghost {
      background: rgba(124,58,237,0.08); color: var(--text-muted);
      border: 1px solid var(--glass-border);
    }
    .btn-ghost:hover { background: rgba(124,58,237,0.16); color: var(--lilac); border-color: var(--glass-border-h); }

    .btn-danger {
      background: rgba(248,113,113,0.1); color: var(--error);
      border: 1px solid rgba(248,113,113,0.25);
    }
    .btn-danger:hover { background: rgba(248,113,113,0.2); }

    .btn-sm { padding: 6px 14px; font-size: 0.7rem; }

    /* ── Tables ── */
    .table-wrap { overflow-x: auto; border-radius: var(--radius-lg); }
    table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    thead th {
      text-align: left; padding: 14px 18px;
      font-family: var(--font-display); font-size: 0.7rem; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--text-faint);
      border-bottom: 1px solid var(--glass-border);
    }
    tbody tr { border-bottom: 1px solid rgba(167,139,250,0.07); transition: background var(--transition); }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: rgba(124,58,237,0.06); }
    tbody td { padding: 14px 18px; color: var(--text-muted); vertical-align: middle; }
    tbody td:first-child { color: var(--text-primary); font-weight: 500; }

    /* ── Badges ── */
    .badge {
      display: inline-block; padding: 3px 10px; border-radius: 50px;
      font-size: 0.68rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase;
    }
    .badge-green   { background: rgba(52,211,153,0.15); color: #34d399; border: 1px solid rgba(52,211,153,0.3); }
    .badge-red     { background: rgba(248,113,113,0.15); color: #f87171; border: 1px solid rgba(248,113,113,0.3); }
    .badge-violet  { background: rgba(167,139,250,0.15); color: var(--violet-soft); border: 1px solid rgba(167,139,250,0.3); }
    .badge-amber   { background: rgba(251,191,36,0.15);  color: #fbbf24; border: 1px solid rgba(251,191,36,0.3); }
    .badge-grey    { background: rgba(90,72,128,0.2);    color: var(--text-faint); border: 1px solid rgba(90,72,128,0.25); }

    /* ── Forms ── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-group.full { grid-column: 1 / -1; }
    label { font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); }
    input[type=text], input[type=email], input[type=password], input[type=number],
    input[type=color], select, textarea {
      background: rgba(13,7,24,0.6); border: 1px solid var(--glass-border);
      color: var(--text-primary); border-radius: var(--radius);
      padding: 11px 16px; font-family: var(--font-body); font-size: 0.875rem;
      outline: none; width: 100%;
      transition: border-color var(--transition), box-shadow var(--transition);
    }
    input:focus, select:focus, textarea:focus {
      border-color: var(--violet-soft);
      box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
    }
    select option { background: #1c0f30; }
    textarea { resize: vertical; min-height: 90px; }

    /* ── Pagination ── */
    .pagination { display: flex; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link {
      width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
      border-radius: 10px; text-decoration: none; font-size: 0.8rem; font-weight: 500;
      color: var(--text-muted); border: 1px solid var(--glass-border);
      background: rgba(35,20,66,0.4); transition: all var(--transition);
    }
    .page-link:hover, .page-link.active {
      color: var(--lilac); background: rgba(124,58,237,0.2);
      border-color: var(--violet-soft);
    }

    /* ── Section header ── */
    .section-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 28px;
    }
    .section-title {
      font-family: var(--font-display); font-size: 1.15rem;
      font-weight: 600; letter-spacing: 0.08em; color: var(--text-primary);
    }
    .section-subtitle { font-size: 0.82rem; color: var(--text-faint); margin-top: 4px; }

    /* ── Animations ── */
    @keyframes fadeIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
    .fade-in { animation: fadeIn 0.5s ease both; }

    @keyframes slideIn { from { opacity:0; transform: translateX(-12px); } to { opacity:1; transform:none; } }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="bg-overlay"></div>

<div class="admin-layout">
<!-- top bar -->
<header class="top-bar">
  <div class="top-bar-left">
    <span class="breadcrumb">YOPY / <span><?= htmlspecialchars($pageTitle ?? '') ?></span></span>
  </div>
  <div class="top-bar-right">
    <span class="admin-badge">⬡ Admin Panel</span>
    <a href="index.php?action=logout" class="btn-logout">Sign Out</a>
  </div>
</header>

<script>
/* Ambient particle background */
(function() {
  const cv = document.getElementById('bg-canvas');
  const ctx = cv.getContext('2d');
  let W, H, pts = [];
  function resize() { W = cv.width = window.innerWidth; H = cv.height = window.innerHeight; }
  window.addEventListener('resize', resize); resize();
  const cols = ['rgba(124,58,237,','rgba(167,139,250,','rgba(196,181,253,','rgba(232,121,249,'];
  for (let i = 0; i < 80; i++) pts.push({
    x: Math.random()*W, y: Math.random()*H,
    r: Math.random()*1.5+0.2, vy: Math.random()*0.25+0.05,
    vx: (Math.random()-.5)*0.12, op: Math.random()*0.35+0.03,
    c: cols[Math.floor(Math.random()*cols.length)]
  });
  (function loop() {
    ctx.clearRect(0,0,W,H);
    pts.forEach(p => {
      p.y -= p.vy; p.x += p.vx;
      if (p.y < -8) { p.y = H+8; p.x = Math.random()*W; }
      ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle = p.c+p.op+')'; ctx.fill();
    });
    requestAnimationFrame(loop);
  })();
})();
</script>
