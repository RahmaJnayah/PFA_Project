<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title>YOPY · Companion Forge | Admin Sanctum</title>
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
      max-width: 1200px;
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

    .form-group.full {
      grid-column: 1 / -1;
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

    input[type="color"] {
      width: 52px;
      height: 42px;
      padding: 4px;
      cursor: pointer;
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

    /* Two column layout */
    .form-two-col {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 28px;
      align-items: start;
    }

    @media (max-width: 900px) {
      .form-two-col { grid-template-columns: 1fr; }
      .container { padding: 0 1rem; }
      .card { padding: 1.5rem; }
      .section-title { font-size: 1.3rem; }
      .souls-status-panel { flex-direction: column; gap: 0.8rem; text-align: left; }
      .stat-block { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; }
      .stat-value { display: inline-block; margin-left: 8px; }
    }

    /* Preview panel */
    .char-preview {
      background: rgba(18, 10, 6, 0.75);
      backdrop-filter: blur(10px);
      border: 1px solid #7a553b;
      border-radius: 20px;
      padding: 28px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      text-align: center;
      position: sticky;
      top: 24px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.4);
    }

    .preview-img-wrap {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: #0e0703d9;
      border: 2px solid #b87a44;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
      box-shadow: inset 0 0 0 2px rgba(200, 120, 50, 0.3), 0 0 0 2px #2f1e12;
    }

    .preview-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .preview-no-img {
      font-size: 0.7rem;
      color: #b8865b;
      font-style: italic;
    }

    .preview-name {
      font-family: 'Cinzel', 'IM Fell English SC', serif;
      font-size: 1.1rem;
      font-weight: 700;
      color: #f5d7b3;
      text-shadow: 0 0 3px #6f2e1a;
    }

    .preview-trait {
      font-size: 0.82rem;
      color: #dba870;
      letter-spacing: 0.5px;
    }

    .preview-tagline {
      font-size: 0.78rem;
      color: #c69764;
      font-style: italic;
    }

    .preview-swatch {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      border: 2px solid rgba(255, 215, 160, 0.3);
      box-shadow: 0 0 14px currentColor;
    }

    .fade-in {
      animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* checkbox styling */
    .form-group label input[type="checkbox"] {
      width: auto;
      margin-right: 10px;
      accent-color: #b67e4a;
    }
    label span strong {
      font-weight: 600;
      color: #f5d7b3;
    }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="vintage-overlay"></div>
<div class="grunge-texture"></div>

<div class="container">
  <!-- Souls-like status panel -->
  <div class="souls-status-panel">
    <div class="stat-block">
      <div class="stat-label">ESSENCE</div>
      <div class="stat-value">✦ companion ✦</div>
      <div class="rune-sigil">bond of grace</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">WISDOM</div>
      <div class="stat-value">emergent</div>
      <div class="rune-sigil">age of tales</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">PRESENCE</div>
      <div class="stat-value">0 / 1</div>
      <div class="rune-sigil">roster seal</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">AURA</div>
      <div class="stat-value">✦ colour ✦</div>
      <div class="rune-sigil">familiar spirit</div>
    </div>
  </div>

  <!-- Header -->
  <div class="section-header fade-in">
    <div>
      <div class="section-title"><?= $isEdit ? 'Edit Character' : 'New Character' ?></div>
      <div class="section-subtitle">
        <?= $isEdit ? 'Update companion details' : 'Add a new onboarding buddy to the roster' ?>
      </div>
    </div>
    <a href="<?= $basePath ?>/admin.php?action=characters" class="btn-ghost">← Back</a>
  </div>

  <!-- Two column: form + preview -->
  <div class="form-two-col fade-in">
    <!-- Form card -->
    <div class="card">
      <form method="POST" action="<?= $basePath ?>/admin.php?action=<?= $isEdit ? 'characters.update' : 'characters.store' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= (int)$character['id'] ?>" />
        <?php endif; ?>

        <div class="form-grid">
          <div class="form-group">
            <label for="name">Character Name</label>
            <input type="text" id="name" name="name" required
                   value="<?= htmlspecialchars($character['name'] ?? '') ?>"
                   placeholder="e.g. Joyla"
                   oninput="updatePreview()" />
          </div>

          <div class="form-group">
            <label for="trait">Trait / Tagline Badge</label>
            <input type="text" id="trait" name="trait" required
                   value="<?= htmlspecialchars($character['trait'] ?? '') ?>"
                   placeholder="e.g. Full of Sunshine"
                   oninput="updatePreview()" />
          </div>

          <div class="form-group full">
            <label for="tagline">Dialogue Tagline</label>
            <input type="text" id="tagline" name="tagline" required
                   value="<?= htmlspecialchars($character['tagline'] ?? '') ?>"
                   placeholder="e.g. Let's make every moment magical! ✨"
                   oninput="updatePreview()" />
          </div>

          <div class="form-group full">
            <label for="image">Image Filename / Path</label>
            <input type="text" id="image" name="image"
                   value="<?= htmlspecialchars($character['image'] ?? '') ?>"
                   placeholder="e.g. images/joy.png or /uploads/joy.webp"
                   oninput="updatePreview()" />
            <span style="font-size:0.72rem; color:#9f7a58; margin-top:4px; display:block;">
              ⚔ Enter the correct relative path from the site root (e.g., <strong>images/joy.png</strong>).<br>
              If the image fails to load, a placeholder will appear.
            </span>
          </div>

          <div class="form-group">
            <label for="color">Accent Colour</label>
            <div style="display:flex; gap:10px; align-items:center;">
              <input type="color" id="color" name="color"
                     value="<?= htmlspecialchars($character['color'] ?? '#9B59B6') ?>"
                     style="width:52px; height:42px; padding:4px; cursor:pointer;"
                     oninput="updatePreview()" />
              <input type="text" id="color-text"
                     value="<?= htmlspecialchars($character['color'] ?? '#9B59B6') ?>"
                     placeholder="#9B59B6"
                     style="flex:1;"
                     oninput="syncColor(this.value)" />
            </div>
          </div>

          <div class="form-group" style="display:flex; flex-direction:column; justify-content:flex-end;">
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; text-transform:none; font-size:0.82rem; letter-spacing:0.02em;">
              <input type="checkbox" id="is_active" name="is_active" value="1"
                     <?= ($character['is_active'] ?? 1) ? 'checked' : '' ?>
                     style="width:18px; height:18px; accent-color: #b67e4a; cursor:pointer;" />
              <span>
                <strong style="color:#f5d7b3;">Visible to children</strong>
                <span style="display:block; font-size:0.72rem; color:#9f7a58; margin-top:2px;">
                  Uncheck to hide this character from the onboarding screen
                </span>
              </span>
            </label>
          </div>
        </div>

        <div class="action-buttons">
          <button type="submit" class="btn-primary">
            <?= $isEdit ? '✔ Save Changes' : '✦ Create Character' ?>
          </button>
          <a href="<?= $basePath ?>/admin.php?action=characters" class="btn-ghost">Cancel</a>
        </div>
      </form>
    </div>

    <!-- Live Preview -->
    <div class="char-preview" id="charPreview">
      <div class="preview-img-wrap" id="previewImgWrap">
        <img id="previewImg" style="display:none;" alt="preview" />
        <span class="preview-no-img" id="previewNoImg">No image</span>
      </div>

      <div class="preview-swatch" id="previewSwatch"
           style="background: <?= htmlspecialchars($character['color'] ?? '#9B59B6') ?>;
                  box-shadow: 0 0 16px <?= htmlspecialchars($character['color'] ?? '#9B59B6') ?>77;">
      </div>

      <div class="preview-name" id="previewName">
        <?= htmlspecialchars($character['name'] ?? 'Character Name') ?>
      </div>
      <div class="preview-trait" id="previewTrait">
        <?= htmlspecialchars($character['trait'] ?? 'Trait goes here') ?>
      </div>
      <div class="preview-tagline" id="previewTagline">
        "<?= htmlspecialchars($character['tagline'] ?? 'Tagline goes here') ?>"
      </div>

      <span style="font-size:0.65rem; letter-spacing:0.1em; text-transform:uppercase; color:#9f7a58; margin-top:8px;">
        Live Preview
      </span>
    </div>
  </div>
</div>

<script>
  // Live preview update function (with image error handling)
  function updatePreview() {
    const name = document.getElementById('name').value || 'Character Name';
    const trait = document.getElementById('trait').value || 'Trait goes here';
    const tagline = document.getElementById('tagline').value || 'Tagline goes here';
    const imagePath = document.getElementById('image').value;
    const color = document.getElementById('color').value;

    document.getElementById('previewName').textContent = name;
    document.getElementById('previewTrait').textContent = trait;
    document.getElementById('previewTagline').textContent = '"' + tagline + '"';

    const img = document.getElementById('previewImg');
    const noImg = document.getElementById('previewNoImg');

    if (imagePath) {
      // Set the image source and attach error handler
      img.onerror = function() {
        img.style.display = 'none';
        noImg.style.display = 'block';
      };
      img.onload = function() {
        img.style.display = 'block';
        noImg.style.display = 'none';
      };
      img.src = imagePath;
      // If image was already loaded successfully, onload will fire; if cached, it might fire immediately.
      // But we also need to handle the case where the image is already loaded and valid.
      // Force a check by setting src to same value will trigger events.
    } else {
      img.style.display = 'none';
      noImg.style.display = 'block';
    }

    const swatch = document.getElementById('previewSwatch');
    swatch.style.background = color;
    swatch.style.boxShadow = `0 0 16px ${color}77`;
    document.getElementById('color-text').value = color;
  }

  function syncColor(val) {
    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
      document.getElementById('color').value = val;
      updatePreview();
    }
  }

  // Initial load: if there is an existing image, trigger the preview
  window.addEventListener('DOMContentLoaded', function() {
    updatePreview();
  });

  // Canvas ember particles (vintage souls vibe)
  (function() {
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