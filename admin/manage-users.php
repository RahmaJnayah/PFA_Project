<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title>YOPY · Guardian Archives | Admin Sanctum</title>
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

    /* Card and table */
    .card {
      background: #140e09e0;
      backdrop-filter: blur(14px);
      border: 1px solid #79553d;
      border-top: 2px solid #b87a44;
      border-radius: 0 0 24px 24px;
      padding: 1.5rem;
      box-shadow: 0 20px 35px -12px black, inset 0 1px 0 rgba(255, 215, 160, 0.1);
    }

    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }

    th, td {
      padding: 12px 10px;
      text-align: left;
      border-bottom: 1px solid #5c3b2a;
    }

    th {
      font-family: 'IM Fell English SC', 'Cinzel', serif;
      font-weight: 600;
      letter-spacing: 1px;
      color: #dba870;
      text-transform: uppercase;
      font-size: 0.7rem;
    }

    td {
      color: #ecd9c6;
    }

    /* Badges */
    .badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 0.7rem;
      font-weight: 500;
      font-family: 'Cinzel', monospace;
    }
    .badge-amber {
      background: #5a3e1a80;
      border: 1px solid #c9a03d;
      color: #f3deba;
    }
    .badge-grey {
      background: #4a3a2a80;
      border: 1px solid #8b765a;
      color: #d4c4a8;
    }
    .badge-green {
      background: #2d4a2a80;
      border: 1px solid #6b9e5c;
      color: #cbe5b9;
    }
    .badge-red {
      background: #6a2a1a80;
      border: 1px solid #c96a4a;
      color: #f3c6b0;
    }

    /* Pagination */
    .pagination {
      margin-top: 24px;
      display: flex;
      justify-content: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .page-link {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 30px;
      background: rgba(30, 18, 10, 0.7);
      border: 1px solid #7a553b;
      color: #e6c394;
      text-decoration: none;
      font-size: 0.8rem;
      font-family: 'Cinzel', monospace;
      transition: 0.2s;
    }
    .page-link:hover {
      background: #3a2418;
      border-color: #c28248;
      color: #fae6c9;
    }
    .page-link.active {
      background: #5a3822;
      border-color: #dc9f5c;
      color: #fff0d0;
      box-shadow: 0 0 6px rgba(200, 100, 30, 0.5);
    }

    @media (max-width: 750px) {
      body { padding: 1rem; }
      .souls-status-panel { flex-direction: column; gap: 0.5rem; text-align: left; }
      .stat-block { display: flex; justify-content: space-between; align-items: baseline; }
      .stat-value { display: inline-block; margin-left: 8px; }
      th, td { padding: 8px 6px; font-size: 0.75rem; }
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
      <div class="stat-label">GUARDIANS</div>
      <div class="stat-value"><?= number_format($total) ?></div>
      <div class="rune-sigil">bound souls</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">ACTIVE</div>
      <div class="stat-value"><?= number_format(count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'active'))) ?></div>
      <div class="rune-sigil">vital flame</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">PREMIUM</div>
      <div class="stat-value"><?= number_format(count(array_filter($users, fn($u) => ($u['plan'] ?? 'free') === 'premium'))) ?></div>
      <div class="rune-sigil">greater rune</div>
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
      <div class="section-title">Parent Accounts</div>
      <div class="section-subtitle"><?= number_format($total) ?> accounts registered</div>
    </div>
    <a href="<?= $basePath ?>/admin.php?action=users.create" class="btn btn-primary">＋ New Account</a>
  </div>

  <!-- Main card with table -->
  <div class="card fade-in">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name / Email</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Children</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="7" style="text-align:center; padding:40px; color:#b8865b; font-style:italic;">
                ⚔ No accounts found. ⚔
              </td>
            </tr>
          <?php else: foreach ($users as $u): ?>
            <tr>
              <td style="color:#b8865b; font-size:0.75rem;">#<?= (int)$u['id'] ?></td>
              <td>
                <strong><?= htmlspecialchars($u['name']) ?></strong>
                <div style="font-size:0.75rem; color:#9f7a58;"><?= htmlspecialchars($u['email']) ?></div>
              </td>
              <td>
                <span class="badge <?= ($u['plan'] ?? 'free') === 'premium' ? 'badge-amber' : 'badge-grey' ?>">
                  <?= htmlspecialchars($u['plan'] ?? 'free') ?>
                </span>
              </td>
              <td>
                <span class="badge <?= ($u['status'] ?? 'active') === 'active' ? 'badge-green' : 'badge-red' ?>">
                  <?= htmlspecialchars($u['status'] ?? 'active') ?>
                </span>
              </td>
              <td style="color:#dba870;"><?= (int)($u['child_count'] ?? 0) ?></td>
              <td style="font-size:0.78rem;">
                <?= isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '—' ?>
              </td>
              <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                  <a href="<?= $basePath ?>/admin.php?action=users.edit&id=<?= (int)$u['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>

                  <form method="POST" action="<?= $basePath ?>/admin.php?action=users.toggleStatus" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                    <button type="submit" class="btn btn-ghost btn-sm">
                      <?= ($u['status'] ?? 'active') === 'active' ? 'Suspend' : 'Restore' ?>
                    </button>
                  </form>

                  <?php if (($u['role'] ?? '') !== 'admin'): ?>
                    <form method="POST" action="<?= $basePath ?>/admin.php?action=users.delete"
                          onsubmit="return confirm('Delete this user and all their data? This cannot be undone.');"
                          style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                      <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                  <?php else: ?>
                    <span class="badge badge-grey">Admin</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a href="<?= $basePath ?>/admin.php?action=users&page=<?= $i ?>"
             class="page-link <?= $i === $page ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
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