<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title>YOPY · <?= $isEdit ? 'Edit Guardian' : 'New Guardian' ?> | Admin Sanctum</title>
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
      max-width: 900px;
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

    /* Card */
    .card {
      background: #140e09e0;
      backdrop-filter: blur(14px);
      border: 1px solid #79553d;
      border-top: 2px solid #b87a44;
      border-radius: 0 0 24px 24px;
      padding: 2rem 2rem 2.2rem;
      box-shadow: 0 20px 35px -12px black, inset 0 1px 0 rgba(255, 215, 160, 0.1);
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .form-group {
      margin-bottom: 0.2rem;
    }

    label {
      display: block;
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 3px;
      color: #c69764;
      margin-bottom: 8px;
      font-weight: 500;
      font-family: 'IM Fell English SC', monospace;
    }

    input, select {
      width: 100%;
      background: #0e0703d9;
      border: 1px solid #66412e;
      color: #f3e2cf;
      padding: 12px 16px;
      font-family: 'DM Sans', monospace;
      font-size: 0.9rem;
      border-radius: 12px;
      transition: all 0.2s;
      outline: none;
    }

    input:focus, select:focus {
      border-color: #cb7b3c;
      background: #1f110ad9;
      box-shadow: 0 0 8px rgba(195, 100, 35, 0.4);
    }

    input[type="password"] {
      letter-spacing: 0.2em;
    }

    .action-buttons {
      margin-top: 28px;
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      align-items: center;
    }

    .fade-in {
      animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 650px) {
      body { padding: 1rem; }
      .souls-status-panel { flex-direction: column; gap: 0.5rem; text-align: left; }
      .stat-block { display: flex; justify-content: space-between; align-items: baseline; }
      .stat-value { display: inline-block; margin-left: 8px; }
      .card { padding: 1.5rem; }
      .section-title { font-size: 1.3rem; }
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
      <div class="stat-label">GUARDIAN</div>
      <div class="stat-value"><?= $isEdit ? '✧ Edit Mode ✧' : '✧ New Soul ✧' ?></div>
      <div class="rune-sigil">parental bond</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">STATUS</div>
      <div class="stat-value"><?= isset($user['status']) && $user['status'] === 'suspended' ? '⛧ Suspended' : '✦ Active ✦' ?></div>
      <div class="rune-sigil">current standing</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">PLAN</div>
      <div class="stat-value"><?= isset($user['plan']) && $user['plan'] === 'premium' ? 'Premium' : 'Free' ?></div>
      <div class="rune-sigil">subscription</div>
    </div>
  </div>

  <!-- Header -->
  <div class="section-header fade-in">
    <div>
      <div class="section-title"><?= $isEdit ? 'Edit User' : 'New Parent Account' ?></div>
      <div class="section-subtitle">
        <?= $isEdit ? 'Update account information' : 'Create a new parent account' ?>
      </div>
    </div>
    <a href="<?= $basePath ?>/admin.php?action=users" class="btn btn-ghost">← Back</a>
  </div>

  <!-- Main form card -->
  <div class="card fade-in">
    <form method="POST" action="<?= $basePath ?>/admin.php?action=<?= $isEdit ? 'users.update' : 'users.store' ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$user['id'] ?>" />
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" required
                 value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                 placeholder="e.g. Alex Johnson" />
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required
                 value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                 placeholder="parent@example.com" />
        </div>

        <div class="form-group">
          <label for="password"><?= $isEdit ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
          <input type="password" id="password" name="password"
                 <?= $isEdit ? '' : 'required' ?>
                 placeholder="••••••••" minlength="8" />
        </div>

        <div class="form-group">
          <label for="pin">4-Digit PIN <?= $isEdit ? '(leave blank to keep current)' : '' ?></label>
          <input type="password" id="pin" name="pin"
                 <?= $isEdit ? '' : 'required' ?>
                 placeholder="••••" maxlength="4" pattern="\d{4}" />
        </div>

        <div class="form-group">
          <label for="plan">Subscription Plan</label>
          <select id="plan" name="plan">
            <option value="free"    <?= ($user['plan'] ?? '') === 'free'    ? 'selected' : '' ?>>Free</option>
            <option value="premium" <?= ($user['plan'] ?? '') === 'premium' ? 'selected' : '' ?>>Premium</option>
          </select>
        </div>

        <div class="form-group">
          <label for="status">Account Status</label>
          <select id="status" name="status">
            <option value="active"    <?= ($user['status'] ?? 'active') === 'active'    ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
          </select>
        </div>
      </div>

      <div class="action-buttons">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? '✔ Save Changes' : '＋ Create Account' ?>
        </button>
        <a href="<?= $basePath ?>/admin.php?action=users" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
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