<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title>YOPY · Child Profile | Admin Sanctum</title>
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
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      color: #ecd9c6;
      padding: 2rem;
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

    /* main container */
    .container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 880px;
      margin: 0 auto;
      animation: riseFromAsh 0.8s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
    }

    @keyframes riseFromAsh {
      0% { opacity: 0; transform: translateY(25px) scale(0.98); filter: blur(3px); }
      100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
    }

    /* Souls-like status panel (custom stats) */
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

    /* Header section */
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 1.5rem;
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
      text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    .section-subtitle {
      font-size: 0.75rem;
      color: #b8865b;
      letter-spacing: 1px;
      margin-top: 4px;
    }
    .btn-ghost {
      background: rgba(30, 18, 10, 0.7);
      border: 1px solid #8b5a3a;
      color: #e6c394;
      padding: 8px 18px;
      border-radius: 40px;
      font-family: 'Cinzel', serif;
      font-size: 0.7rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      text-decoration: none;
      transition: all 0.2s;
      display: inline-block;
    }
    .btn-ghost:hover {
      background: #3a2418;
      border-color: #c28248;
      color: #fae6c9;
      transform: translateY(-1px);
    }

    /* Main card */
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
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
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

    select {
      cursor: pointer;
      appearance: none;
      background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="%23b67e4a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
      background-repeat: no-repeat;
      background-position: right 1rem center;
    }

    .btn-primary {
      background: linear-gradient(135deg, #2f1e12, #4f2e1c);
      border: 1px solid #b17a48;
      color: #fbe9c3;
      padding: 12px 28px;
      font-family: 'Cinzel', 'IM Fell English SC', serif;
      font-weight: 600;
      font-size: 0.8rem;
      letter-spacing: 4px;
      text-transform: uppercase;
      cursor: pointer;
      border-radius: 40px;
      transition: all 0.3s;
      box-shadow: 0 2px 8px rgba(0,0,0,0.5);
      text-decoration: none;
      display: inline-block;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #4f2e1c, #6e4128);
      border-color: #dc9f5c;
      letter-spacing: 5px;
      box-shadow: 0 0 12px rgba(210, 100, 30, 0.5);
      transform: translateY(-1px);
    }

    .action-buttons {
      margin-top: 28px;
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      align-items: center;
    }

    .card-footer-note {
      margin-top: 1.5rem;
      font-size: 0.65rem;
      text-align: center;
      color: #9f7a58;
      letter-spacing: 1px;
      border-top: 1px dashed #67442e;
      padding-top: 1rem;
    }

    .fade-in {
      animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 650px) {
      .container { padding: 0 1rem; }
      .card { padding: 1.5rem; }
      .section-title { font-size: 1.3rem; }
      .souls-status-panel { flex-direction: column; gap: 0.8rem; text-align: left; }
      .stat-block { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; }
      .stat-value { display: inline-block; margin-left: 8px; }
    }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="vintage-overlay"></div>
<div class="grunge-texture"></div>

<div class="container">
  <!-- Souls-like status panel (vintage brown evil vibe) -->
  <div class="souls-status-panel">
    <div class="stat-block">
      <div class="stat-label">COMPANIONSHIP</div>
      <div class="stat-value">✦ unbound ✦</div>
      <div class="rune-sigil">bond of grace</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">GROWTH</div>
      <div class="stat-value">emergent</div>
      <div class="rune-sigil">age of kindling</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">AFFINITY</div>
      <div class="stat-value">0 / 1</div>
      <div class="rune-sigil">parental seal</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">LEGACY</div>
      <div class="stat-value">✦ character ✦</div>
      <div class="rune-sigil">familiar spirit</div>
    </div>
  </div>

  <!-- Header with title and back link -->
  <div class="section-header fade-in">
    <div>
      <div class="section-title"><?= $isEdit ? 'Edit Child Profile' : 'New Child Profile' ?></div>
      <div class="section-subtitle">
        <?= $isEdit ? 'Update profile details' : 'Add a child to a parent account' ?>
      </div>
    </div>
    <a href="<?= $basePath ?>/admin.php?action=children" class="btn-ghost">← Back</a>
  </div>

  <!-- Main form card -->
  <div class="card fade-in">
    <form method="POST" action="<?= $basePath ?>/admin.php?action=<?= $isEdit ? 'children.update' : 'children.store' ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$child['id'] ?>" />
      <?php endif; ?>

      <div class="form-grid">
        <!-- Child's Name -->
        <div class="form-group">
          <label for="name">Child's Name</label>
          <input type="text" id="name" name="name" required
                 value="<?= htmlspecialchars($child['name'] ?? '') ?>"
                 placeholder="e.g. Mia" />
        </div>

        <!-- Age (optional) -->
        <div class="form-group">
          <label for="age">Age (optional)</label>
          <input type="number" id="age" name="age" min="1" max="17"
                 value="<?= htmlspecialchars($child['age'] ?? '') ?>"
                 placeholder="e.g. 7" />
        </div>

        <!-- Parent Account (dynamic from $users) -->
        <div class="form-group">
          <label for="user_id">Parent Account</label>
          <select id="user_id" name="user_id" required>
            <option value="">— Select parent —</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>"
                <?= ($child['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Companion Character (dynamic from $characters) -->
        <div class="form-group">
          <label for="character_id">Companion Character (optional)</label>
          <select id="character_id" name="character_id">
            <option value="">— None chosen —</option>
            <?php foreach ($characters as $ch): ?>
              <option value="<?= (int)$ch['id'] ?>"
                <?= ($child['character_id'] ?? '') == $ch['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ch['name']) ?> — <?= htmlspecialchars($ch['trait']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Avatar Emoji -->
        <div class="form-group">
          <label for="emoji">Avatar Emoji</label>
          <input type="text" id="emoji" name="emoji"
                 value="<?= htmlspecialchars($child['emoji'] ?? '🦊') ?>"
                 placeholder="🦊" maxlength="4" />
        </div>

        <!-- Card Theme -->
        <div class="form-group">
          <label for="theme">Card Theme</label>
          <select id="theme" name="theme">
            <?php
            $themes = [
              'theme-rose'  => '🌸 Rose',
              'theme-teal'  => '🌊 Teal',
              'theme-blue'  => '💙 Blue',
              'theme-amber' => '🌟 Amber',
              'theme-mint'  => '🌿 Mint',
              'theme-sky'   => '☁️ Sky',
            ];
            foreach ($themes as $val => $label): ?>
              <option value="<?= $val ?>"
                <?= ($child['theme'] ?? 'theme-rose') === $val ? 'selected' : '' ?>>
                <?= $label ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="action-buttons">
        <button type="submit" class="btn-primary">
          <?= $isEdit ? '✔ Save Changes' : '＋ Create Profile' ?>
        </button>
        <a href="<?= $basePath ?>/admin.php?action=children" class="btn-ghost">Cancel</a>
      </div>
    </form>
    <div class="card-footer-note">
      ⚔  Seal of the Two Fingers  ⚔  —  Each child shall be bound to their guardian.
    </div>
  </div>
</div>

<script>
  (function() {
    // Atmospheric ember particles (vintage souls vibe)
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];
    const PARTICLE_COUNT = 150;
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
        const glow = p.opacity + Math.sin(Date.now() * 0.0025 * p.flicker) * 0.1;
        ctx.fillStyle = p.color;
        ctx.shadowColor = '#b85f2a';
        ctx.shadowBlur = 6;
        ctx.fill();
        ctx.shadowBlur = 0;
        
        // inner ember glow
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
    
    // optional subtle flicker for the card to match the candlelight atmosphere
    const card = document.querySelector('.card');
    if (card) {
      setInterval(() => {
        const intensity = 0.7 + Math.random() * 0.3;
        card.style.boxShadow = `0 20px 35px -10px black, inset 0 0 3px rgba(190, 100, 30, ${intensity * 0.2})`;
      }, 1300);
    }
  })();
</script>
</body>
</html>