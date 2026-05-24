<?php
require_once __DIR__ . '/../child/game-menu/data/buddies.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title>YOPY · Companion Roster | Admin Sanctum</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;900&family=IM+Fell+English+SC&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      background: #0a0502;
      font-family: 'DM Sans', 'Cinzel', serif;
      color: #ecd9c6;
      padding: 2rem;
      position: relative;
    }

    /* Vintage atmosphere */
    #bg-canvas {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      pointer-events: none;
      opacity: 0.7;
    }

    .vintage-overlay {
      position: fixed;
      inset: 0;
      z-index: 1;
      pointer-events: none;
      background: radial-gradient(circle at 20% 30%, rgba(30, 12, 5, 0.55) 0%, rgba(8, 4, 1, 0.85) 100%),
                  repeating-linear-gradient(45deg, rgba(70, 35, 15, 0.12) 0px, rgba(70, 35, 15, 0.12) 2px, transparent 2px, transparent 8px);
      mix-blend-mode: multiply;
    }

    .grunge-texture {
      position: fixed;
      inset: 0;
      z-index: 1;
      pointer-events: none;
      background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iNDAwIiB2aWV3Qm94PSIwIDAgNDAwIDQwMCI+PGZpbHRlciBpZD0ibm9pc2UiPjxmZVR1cmJ1bGVuY2UgdHlwZT0iZnJhY3RhbE5vaXNlIiBiYXNlRnJlcXVlbmN5PSIuNyIgbnVtT2N0YXZlcz0iMyIgc3RpdGNoVGlsZXM9InN0aXRjaCIvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNub2lzZSkiIG9wYWNpdHk9IjAuMTgiLz48L3N2Zz4=');
      background-repeat: repeat;
      opacity: 0.2;
      mix-blend-mode: overlay;
    }

    .container {
      position: relative;
      z-index: 10;
      max-width: 1300px;
      margin: 0 auto;
      animation: riseFromAsh 0.8s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
    }

    @keyframes riseFromAsh {
      0% { opacity: 0; transform: translateY(25px) scale(0.98); filter: blur(3px); }
      100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
    }

    /* Souls-like status panel */
    .souls-status-panel {
      background: rgba(18, 10, 6, 0.7);
      backdrop-filter: blur(6px);
      border: 1px solid #5c3b2a;
      border-bottom: none;
      border-radius: 20px 20px 0 0;
      padding: 1rem 1.8rem;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 0;
      box-shadow: inset 0 1px 0 rgba(210, 150, 75, 0.2), 0 6px 12px rgba(0, 0, 0, 0.5);
    }

    .stat-block {
      flex: 1;
      text-align: center;
      font-family: 'IM Fell English SC', 'Cinzel', serif;
      letter-spacing: 0.08em;
    }

    .stat-label {
      font-size: 0.68rem;
      text-transform: uppercase;
      color: #b67e4a;
      border-bottom: 1px solid #6a3e2a;
      display: inline-block;
      margin-bottom: 6px;
      font-weight: 500;
    }

    .stat-value {
      font-size: 1rem;
      font-weight: 700;
      color: #f5d7b3;
      display: block;
      text-shadow: 0 0 3px #6f2e1a;
      font-family: 'Cinzel', monospace;
    }

    .rune-sigil {
      font-size: 0.65rem;
      color: #b8865b;
      margin-top: 4px;
      letter-spacing: 2px;
    }

    /* Header */
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin: 1.5rem 0 1.8rem;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .section-title {
      font-family: 'IM Fell English SC', 'Cinzel', serif;
      font-size: 1.6rem;
      font-weight: 600;
      background: linear-gradient(135deg, #ebc28e, #b5783a);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      letter-spacing: 2px;
    }
    .section-subtitle {
      font-size: 0.75rem;
      color: #b8865b;
      letter-spacing: 1px;
      margin-top: 4px;
    }

    /* Flash messages */
    .flash {
      padding: 12px 18px;
      margin-bottom: 20px;
      border-radius: 12px;
      background: rgba(30, 12, 5, 0.85);
      backdrop-filter: blur(8px);
      border-left: 4px solid;
      font-family: 'DM Sans', monospace;
      font-size: 0.85rem;
      font-weight: 500;
    }
    .flash.success {
      border-left-color: #6b9e5c;
      color: #d4e2c6;
    }
    .flash.error {
      border-left-color: #b13e2b;
      color: #f3a683;
    }

    /* Buttons */
    .btn {
      display: inline-block;
      padding: 8px 18px;
      border-radius: 40px;
      font-family: 'Cinzel', serif;
      font-size: 0.7rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      text-decoration: none;
      transition: all 0.2s;
      border: 1px solid #8b5a3a;
      background: rgba(30, 18, 10, 0.7);
      color: #e6c394;
      cursor: pointer;
    }
    .btn-primary {
      background: linear-gradient(135deg, #2f1e12, #4f2e1c);
      border-color: #b17a48;
      color: #fbe9c3;
    }
    .btn-primary:hover {
      background: linear-gradient(135deg, #4f2e1c, #6e4128);
      border-color: #dc9f5c;
      letter-spacing: 3px;
      transform: translateY(-1px);
    }
    .btn-ghost {
      background: rgba(30, 18, 10, 0.6);
      border-color: #7a553b;
    }
    .btn-ghost:hover {
      background: #3a2418;
      border-color: #c28248;
      color: #fae6c9;
    }
    .btn-danger {
      border-color: #8b3a2a;
      color: #e6a68b;
    }
    .btn-danger:hover {
      background: #5a2a1a;
      border-color: #d96a42;
      color: #ffcfb0;
    }
    .btn-sm {
      padding: 5px 12px;
      font-size: 0.65rem;
    }

    /* Badges */
    .badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 0.65rem;
      font-weight: 600;
      letter-spacing: 1px;
      font-family: 'Cinzel', monospace;
    }
    .badge-green {
      background: #2d4a2a80;
      border: 1px solid #6b9e5c;
      color: #cbe5b9;
    }
    .badge-grey {
      background: #4a3a2a80;
      border: 1px solid #8b765a;
      color: #d4c4a8;
    }

    /* Character grid */
    .char-card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
      margin-top: 20px;
    }
    .char-card {
      background: rgba(20, 14, 9, 0.85);
      backdrop-filter: blur(12px);
      border: 1px solid #7a553b;
      border-radius: 20px;
      padding: 20px;
      transition: border-color 0.2s, transform 0.2s;
      animation: fadeIn 0.5s ease both;
      position: relative;
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }
    .char-card:hover {
      border-color: #c28248;
      transform: translateY(-3px);
    }
    .char-card-glow {
      position: absolute;
      top: -1px;
      left: -1px;
      right: -1px;
      height: 3px;
      border-radius: 20px 20px 0 0;
      background: linear-gradient(90deg, #b87a44, transparent);
    }
    .char-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 16px;
    }
    .char-img-box {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #0e0703d9;
      border: 2px solid #b87a44;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
    }
    .char-img-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .char-img-box svg {
      width: 100%;
      height: 100%;
      display: block;
    }
    .char-img-box span {
      font-size: 0.65rem;
      color: #b8865b;
    }
    .char-name {
      font-family: 'Cinzel', 'IM Fell English SC', serif;
      font-size: 1rem;
      font-weight: 700;
      color: #f5d7b3;
    }
    .char-trait {
      font-size: 0.75rem;
      color: #dba870;
      margin-top: 2px;
    }
    .char-tagline {
      font-size: 0.82rem;
      color: #c69764;
      font-style: italic;
      margin: 12px 0 16px;
      border-left: 2px solid #b87a44;
      padding-left: 10px;
    }
    .char-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 12px;
    }
    .char-usage {
      font-size: 0.7rem;
      color: #9f7a58;
      margin-top: 12px;
      text-align: right;
    }
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 48px;
      background: rgba(20, 14, 9, 0.6);
      border-radius: 24px;
      color: #b8865b;
      font-style: italic;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 700px) {
      body { padding: 1rem; }
      .souls-status-panel { flex-direction: column; gap: 0.5rem; text-align: left; }
      .stat-block { display: flex; justify-content: space-between; align-items: baseline; }
      .stat-value { display: inline-block; margin-left: 8px; }
    }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="vintage-overlay"></div>
<div class="grunge-texture"></div>

<div class="container">
  <!-- Souls status panel -->
  <div class="souls-status-panel">
    <div class="stat-block">
      <div class="stat-label">COMPANIONS</div>
      <div class="stat-value"><?= count($characters) ?></div>
      <div class="rune-sigil">bound spirits</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">ACTIVE</div>
      <div class="stat-value"><?= count(array_filter($characters, fn($c) => $c['is_active'])) ?></div>
      <div class="rune-sigil">visible to children</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">LEGACY</div>
      <div class="stat-value">✦ roster ✦</div>
      <div class="rune-sigil">familiar forge</div>
    </div>
  </div>

  <!-- Flash messages -->
  <?php if (!empty($flash)): ?>
    <div class="flash <?= htmlspecialchars($flash['type']) ?>">
      <?= $flash['type'] === 'success' ? '✔' : '✕' ?>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="section-header fade-in">
    <div>
      <div class="section-title">Companion Characters</div>
      <div class="section-subtitle">Manage the onboarding buddy roster shown to children</div>
    </div>
    <a href="<?= $basePath ?>/admin.php?action=characters.create" class="btn btn-primary">＋ New Character</a>
  </div>

  <!-- Character grid -->
  <div class="char-card-grid fade-in">
    <?php if (empty($characters)): ?>
      <div class="empty-state">
        ⚔ No characters yet. Add your first companion! ⚔
      </div>
    <?php else: foreach ($characters as $i => $ch): ?>
      <div class="char-card" style="animation-delay: <?= $i * 0.07 ?>s; border-color: <?= htmlspecialchars($ch['color']) ?>66;">
        <div class="char-card-glow" style="background: linear-gradient(90deg, <?= htmlspecialchars($ch['color']) ?>, transparent);"></div>

        <div class="char-header">
          <div>
            <div class="char-name"><?= htmlspecialchars($ch['name']) ?></div>
            <div class="char-trait"><?= htmlspecialchars($ch['trait']) ?></div>
            <div style="margin-top: 6px;">
              <span class="badge <?= $ch['is_active'] ? 'badge-green' : 'badge-grey' ?>">
                <?= $ch['is_active'] ? '● Live' : '○ Hidden' ?>
              </span>
            </div>
          </div>
        </div>

        <div class="char-tagline">"<?= htmlspecialchars($ch['tagline']) ?>"</div>

        <?php if (isset($ch['usage_count'])): ?>
          <div class="char-usage">Used by <?= (int)$ch['usage_count'] ?> child profile(s)</div>
        <?php endif; ?>

        <div class="char-actions">
          <a href="<?= $basePath ?>/admin.php?action=characters.edit&id=<?= (int)$ch['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>

          <form method="POST" action="<?= $basePath ?>/admin.php?action=characters.toggle" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="id" value="<?= (int)$ch['id'] ?>" />
            <button type="submit" class="btn btn-ghost btn-sm">
              <?= $ch['is_active'] ? 'Hide' : 'Show' ?>
            </button>
          </form>

              <form method="POST" action="<?= $basePath ?>/admin.php?action=characters.delete"
                onsubmit='return confirm("Delete " + <?= json_encode((string)($ch['name'] ?? '')) ?> + "? This cannot be undone.");'
                style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="id" value="<?= (int)$ch['id'] ?>" />
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Vintage ember canvas -->
<script>
  (function() {
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];
    const PARTICLE_COUNT = 160;
    const colors = ['#4f2a1b', '#7b3f1a', '#b45f2b', '#5a2e18', '#3f1f0c', '#ab6a36', '#8b4513'];

    function resizeCanvas() {
      width = canvas.width = window.innerWidth;
      height = canvas.height = window.innerHeight;
      initParticles();
    }

    function initParticles() {
      particles = [];
      for (let i = 0; i < PARTICLE_COUNT; i++) {
        particles.push({
          x: Math.random() * width,
          y: Math.random() * height,
          r: Math.random() * 3.2 + 0.6,
          vy: Math.random() * 0.55 + 0.1,
          vx: (Math.random() - 0.5) * 0.12,
          opacity: Math.random() * 0.55 + 0.1,
          color: colors[Math.floor(Math.random() * colors.length)],
          flicker: Math.random() * 0.07 + 0.02
        });
      }
    }

    function drawParticles() {
      if (!ctx) return;
      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = '#0b0502';
      ctx.fillRect(0, 0, width, height);
      
      for (let p of particles) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.shadowColor = '#b85f2a';
        ctx.shadowBlur = 6;
        ctx.fill();
        ctx.shadowBlur = 0;
        
        ctx.beginPath();
        ctx.arc(p.x - 0.4, p.y - 0.3, p.r * 0.4, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(230, 110, 40, ${p.opacity * 0.5})`;
        ctx.fill();
        
        p.y -= p.vy;
        p.x += p.vx;
        if (p.y < -15) { p.y = height + 10; p.x = Math.random() * width; }
        if (p.x > width + 20) p.x = -20;
        if (p.x < -20) p.x = width + 20;
        if (Math.random() < 0.02) p.vx += (Math.random() - 0.5) * 0.05;
        p.vx = Math.min(Math.max(p.vx, -0.25), 0.25);
      }
      requestAnimationFrame(drawParticles);
    }
    
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();
    drawParticles();
  })();
</script>
</body>
</html>