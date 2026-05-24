<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title>YOPY · Admin Sanctum | Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;900&family=IM+Fell+English+SC&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet" />
  <style>
    /* ... (All your existing CSS remains the same) ... */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      min-height: 100vh; background: #0a0502;
      font-family: 'DM Sans', 'Cinzel', serif; color: #ecd9c6;
      padding: 2rem; position: relative;
    }
    #bg-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; opacity: 0.7; }
    .vintage-overlay {
      position: fixed; inset: 0; z-index: 1; pointer-events: none;
      background: radial-gradient(circle at 20% 30%, rgba(30, 12, 5, 0.55) 0%, rgba(8, 4, 1, 0.85) 100%),
                  repeating-linear-gradient(45deg, rgba(70, 35, 15, 0.12) 0px, rgba(70, 35, 15, 0.12) 2px, transparent 2px, transparent 8px);
      mix-blend-mode: multiply;
    }
    .grunge-texture {
      position: fixed; inset: 0; z-index: 1; pointer-events: none;
      background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iNDAwIiB2aWV3Qm94PSIwIDAgNDAwIDQwMCI+PGZpbHRlciBpZD0ibm9pc2UiPjxmZVR1cmJ1bGVuY2UgdHlwZT0iZnJhY3RhbE5vaXNlIiBiYXNlRnJlcXVlbmN5PSIuNyIgbnVtT2N0YXZlcz0iMyIgc3RpdGNoVGlsZXM9InN0aXRjaCIvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNub2lzZSkiIG9wYWNpdHk9IjAuMTgiLz48L3N2Zz4=');
      opacity: 0.2; mix-blend-mode: overlay;
    }
    .container { position: relative; z-index: 10; max-width: 1400px; margin: 0 auto; animation: riseFromAsh 0.8s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards; }
    @keyframes riseFromAsh { 0% { opacity: 0; transform: translateY(25px) scale(0.98); filter: blur(3px); } 100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); } }

    /* Header & Stats */
    .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; flex-wrap: wrap; gap: 1rem; }
    .section-title { font-family: 'IM Fell English SC', 'Cinzel', serif; font-size: 1.8rem; font-weight: 600; background: linear-gradient(135deg, #ebc28e, #b5783a); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: 2px; display: flex; align-items: center; gap: 12px; }
    .section-subtitle { font-size: 0.8rem; color: #b8865b; letter-spacing: 1px; margin-top: 4px; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px; margin-bottom: 40px; }
    .stat-card { background: rgba(20, 14, 9, 0.85); backdrop-filter: blur(12px); border: 1px solid #7a553b; border-radius: 20px; padding: 22px; transition: 0.2s; position: relative; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
    .stat-card:hover { border-color: #c28248; transform: translateY(-3px); }
    .stat-icon { margin-bottom: 12px; color: var(--accent-color); }
    .stat-value { font-family: 'Cinzel', serif; font-size: 2rem; font-weight: 700; color: #f5d7b3; line-height: 1; text-shadow: 0 0 3px #6f2e1a; }
    .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: #b8865b; margin-top: 8px; }
    .stat-sub { font-size: 0.75rem; color: #c69764; margin-top: 8px; }

    /* Dashboard Cards */
    .dash-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-top: 8px; }
    .card { background: rgba(20, 14, 9, 0.85); backdrop-filter: blur(12px); border: 1px solid #7a553b; border-radius: 20px; padding: 1.5rem; box-shadow: 0 12px 28px rgba(0,0,0,0.4); }
    .card-title { font-family: 'IM Fell English SC', serif; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: #dba870; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #7a553b; display: flex; align-items: center; gap: 10px; }
    
    /* Icon Sizing Helper */
    .icon-svg { width: 20px; height: 20px; stroke-width: 1.5; vertical-align: middle; }
    .stat-icon .icon-svg { width: 32px; height: 32px; filter: drop-shadow(0 0 8px var(--accent-color)); }

    /* Tables & Badges */
    .mini-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .mini-table th { font-family: 'IM Fell English SC', serif; color: #b8865b; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; border-bottom: 1px solid #5c3b2a; padding: 10px; text-align: left;}
    .mini-table td { padding: 10px; border-bottom: 1px solid #5c3b2a; color: #ecd9c6; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.7rem; border: 1px solid transparent; }
    .badge-amber { background: #5a3e1a80; border-color: #c9a03d; color: #f3deba; }
    .badge-green { background: #2d4a2a80; border-color: #6b9e5c; color: #cbe5b9; }
    .badge-grey { background: #3a2a2080; border-color: #7a553b; color: #d2c1ab; }

    /* Buttons */
    .btn { display: inline-block; padding: 8px 18px; border-radius: 40px; font-family: 'Cinzel', serif; font-size: 0.7rem; text-decoration: none; border: 1px solid #8b5a3a; cursor: pointer; }
    .btn-primary { background: linear-gradient(135deg, #2f1e12, #4f2e1c); color: #fbe9c3; border-color: #b17a48; }
    
    @media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="vintage-overlay"></div>
<div class="grunge-texture"></div>

<div class="container">
  <?php if (!empty($flash)): ?>
    <div class="flash <?= ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success' ?>">
      <?= htmlspecialchars($flash['msg'] ?? '') ?>
    </div>
  <?php endif; ?>

  <div class="section-header">
    <div>
      <div class="section-title">
        Welcome back 
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="color: #ebc28e; width: 24px; height: 24px;">
          <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
        </svg>
      </div>
      <div class="section-subtitle">The ledger of souls has been updated.</div>
    </div>
    <a href="<?= $basePath ?>/admin.php?action=users.create" class="btn btn-primary">＋ Add User</a>
  </div>

  <div class="stats-grid">
    <div class="stat-card" style="--accent-color: #b87a44;">
      <div class="stat-icon">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
      <div class="stat-label">Parent Accounts</div>
      <div class="stat-sub"><strong><?= $stats['active_users'] ?></strong> active</div>
    </div>

    <div class="stat-card" style="--accent-color: #c28248;">
      <div class="stat-icon">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['total_children']) ?></div>
      <div class="stat-label">Child Profiles</div>
      <div class="stat-sub">Bound to families</div>
    </div>

    <div class="stat-card" style="--accent-color: #e6a34b;">
      <div class="stat-icon">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M6 3h12l4 6-10 12L2 9z"/><path d="M11 3v18M12 3l4 6-4 12-4-12z"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['premium_users']) ?></div>
      <div class="stat-label">Premium Subscribers</div>
      <div class="stat-sub"><?= $stats['total_users'] > 0 ? round($stats['premium_users'] / $stats['total_users'] * 100, 1) : 0 ?>% conversion</div>
    </div>

    <div class="stat-card" style="--accent-color: #5f9b8a;">
      <div class="stat-icon">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14l-5-4.87 6.91-1.01L12 2z" />
         
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['total_characters']) ?></div>
      <div class="stat-label">Characters</div>
      <div class="stat-sub"><strong style="color:#8bc2aa"><?= $stats['active_characters'] ?></strong> manifestations</div>
    </div>
  </div>

  <div class="dash-grid">
    <div class="card">
      <div class="card-title">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Recent Parent Accounts
      </div>
      <div class="table-wrap">
        <table class="mini-table">
          <thead><tr><th>Name</th><th>Plan</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recentUsers as $u): ?>
              <?php $safePlan = in_array(($u['plan'] ?? 'free'), ['free', 'premium'], true) ? $u['plan'] : 'free'; ?>
              <?php $safeStatus = in_array(($u['status'] ?? 'active'), ['active', 'suspended'], true) ? $u['status'] : 'suspended'; ?>
              <tr>
                <td><?= htmlspecialchars($u['name']) ?><div style="font-size:0.7rem; color:#9f7a58;"><?= htmlspecialchars($u['email']) ?></div></td>
                <td><span class="badge <?= $safePlan === 'premium' ? 'badge-amber' : 'badge-grey' ?>"><?= htmlspecialchars($safePlan) ?></span></td>
                <td><span class="badge <?= $safeStatus === 'active' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars($safeStatus) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        Companion Characters
      </div>
      <div class="char-grid">
        <?php foreach ($characters as $ch): ?>
          <?php $safeColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($ch['color'] ?? '')) ? $ch['color'] : '#9B59B6'; ?>
          <div class="char-pill" style="display: flex; align-items: center; gap: 10px; background: rgba(30, 18, 10, 0.7); border: 1px solid #7a553b; border-radius: 50px; padding: 6px 14px; margin-bottom:8px;">
            <div class="char-dot" style="width:10px; height:10px; border-radius:50%; background: <?= htmlspecialchars($safeColor, ENT_QUOTES, 'UTF-8') ?>;"></div>
            <span><?= htmlspecialchars($ch['name']) ?></span>
            <span class="badge <?= $ch['is_active'] ? 'badge-green' : 'badge-grey' ?>" style="font-size:0.6rem;"><?= $ch['is_active'] ? 'Live' : 'Hidden' ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12h18M5 7h14M7 17h10"/></svg>
        Game Library
      </div>
      <div class="table-wrap">
        <table class="mini-table">
          <thead><tr><th>Game</th><th>Category</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($games)): ?>
              <tr>
                <td colspan="3" style="color:#b8865b; text-align:center;">No games found.</td>
              </tr>
            <?php else: foreach ($games as $g): ?>
              <tr>
                <td><?= htmlspecialchars($g['name']) ?></td>
                <td><?= htmlspecialchars($g['category']) ?></td>
                <td>
                  <span class="badge <?= (int)$g['is_active'] === 1 ? 'badge-green' : 'badge-grey' ?>">
                    <?= (int)$g['is_active'] === 1 ? 'Live' : 'Hidden' ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div style="margin-top:16px; text-align:right;">
        <a href="<?= $basePath ?>/admin.php?action=games" class="btn btn-primary">Manage Games →</a>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 11a3 3 0 1 0 6 0a3 3 0 1 0-6 0"/><path d="M3 13c1.5 3 4.5 5 9 5s7.5-2 9-5"/></svg>
        Recent Behavior Analysis
      </div>
      <div class="table-wrap">
        <table class="mini-table">
          <thead><tr><th>Child</th><th>State</th><th>Confidence</th></tr></thead>
          <tbody>
            <?php if (empty($recentAnalyses)): ?>
              <tr>
                <td colspan="3" style="color:#b8865b; text-align:center;">No analyses yet.</td>
              </tr>
            <?php else: foreach ($recentAnalyses as $a): ?>
              <tr>
                <td>
                  <?= htmlspecialchars($a['child_name']) ?>
                  <div style="font-size:0.7rem; color:#9f7a58;"><?= htmlspecialchars(date('Y-m-d', strtotime($a['period_end']))) ?></div>
                </td>
                <td><?= htmlspecialchars($a['dominant_state']) ?></td>
                <td><?= htmlspecialchars((string) $a['confidence']) ?>%</td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="grid-column: 1 / -1;">
      <div class="card-title">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
        Run Behavior Analysis
      </div>
      <form method="post" action="<?= $basePath ?>/admin.php?action=analysis.run_child" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf ?? '') ?>" />
        <select name="child_id" style="min-width: 240px; background: rgba(30, 18, 10, 0.7); border: 1px solid #7a553b; border-radius: 10px; color: #ecd9c6; padding: 8px 12px;">
          <option value="">Select a child</option>
          <?php foreach ($allChildren as $child): ?>
            <option value="<?= (int) $child['id'] ?>">
              <?= htmlspecialchars($child['name']) ?> (age <?= (int) $child['age'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Run Child Analysis</button>
      </form>
      <form method="post" action="<?= $basePath ?>/admin.php?action=analysis.run_all" style="margin-top: 14px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf ?? '') ?>" />
        <button type="submit" class="btn btn-ghost">Run Full Analysis For All Children</button>
      </form>

      <div class="table-wrap" style="margin-top: 20px;">
        <table class="mini-table">
          <thead><tr><th>Child</th><th>Period</th><th>State</th><th>Confidence</th></tr></thead>
          <tbody>
            <?php if (empty($analysisHistory)): ?>
              <tr>
                <td colspan="4" style="color:#b8865b; text-align:center;">No analysis history yet.</td>
              </tr>
            <?php else: foreach ($analysisHistory as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['child_name']) ?></td>
                <td>
                  <?= htmlspecialchars(date('Y-m-d', strtotime($a['period_start']))) ?> →
                  <?= htmlspecialchars(date('Y-m-d', strtotime($a['period_end']))) ?>
                </td>
                <td><?= htmlspecialchars($a['dominant_state']) ?></td>
                <td><?= htmlspecialchars((string) $a['confidence']) ?>%</td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="grid-column: 1 / -1;">
      <div class="card-title">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg>
        Recent Child Profiles
      </div>
      <div class="table-wrap">
        <table class="mini-table">
          <thead><tr><th>Child</th><th>Parent</th><th>Character</th><th>Aura</th></tr></thead>
          <tbody>
            <?php foreach ($recentChildren as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td style="color:#dba870;"><?= htmlspecialchars($c['parent_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['character_name'] ?? '—') ?></td>
                <td style="font-size:1.2rem;"><?= htmlspecialchars($c['emoji'] ?? '✨') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
 <!-- Vintage ember canvas -->
<script>
 

  (function() {
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];
    const PARTICLE_COUNT = 170;
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

  (function() {
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let width, height, particles = [];
    const colors = ['#4f2a1b', '#7b3f1a', '#b45f2b', '#5a2e18', '#3f1f0c'];

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
      ctx.fillStyle = '#0b0502';
      ctx.fillRect(0, 0, width, height);
      for (let p of particles) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = p.opacity;
        ctx.fill();
        p.y -= p.vy;
        if (p.y < -10) p.y = height + 10;
      }
      requestAnimationFrame(draw);
    }
    window.addEventListener('resize', resize);
    resize(); draw();
  })();
</script>
</body>
</html>