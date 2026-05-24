<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title>Finger Reader — Admin · YOPY</title>
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
    }

    /* ELDEN VINTAGE ATMOSPHERE */
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
      background: 
        radial-gradient(circle at 20% 30%, rgba(30, 12, 5, 0.55) 0%, rgba(8, 4, 1, 0.85) 100%),
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

    /* MAIN CONTAINER — STATUE SCREEN */
    .status-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 520px;
      margin: 2rem;
      animation: riseFromAsh 1s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
    }

    @keyframes riseFromAsh {
      0% {
        opacity: 0;
        transform: translateY(35px) scale(0.97);
        filter: blur(4px);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
      }
    }

    /* SOULS-LIKE STATS PANEL (STATUS SCREEN) */
    .souls-status-panel {
      background: rgba(18, 10, 6, 0.68);
      backdrop-filter: blur(6px);
      border: 1px solid #5c3b2a;
      border-bottom: none;
      border-radius: 20px 20px 0 0;
      padding: 1rem 1.5rem;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 1rem;
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
      font-size: 1.05rem;
      font-weight: 700;
      color: #f5d7b3;
      display: block;
      text-shadow: 0 0 3px #6f2e1a;
      font-family: 'Cinzel', monospace;
    }

    .rune-sigil {
      font-size: 0.7rem;
      color: #b8865b;
      margin-top: 4px;
      letter-spacing: 2px;
    }

    /* MAIN LOGIN CARD — ANCIENT TOME STYLE */
    .login-card {
      background: #140e09e0;
      backdrop-filter: blur(14px);
      border: 1px solid #79553d;
      border-top: 2px solid #b87a44;
      border-radius: 0 0 24px 24px;
      padding: 2rem 2rem 2.2rem;
      box-shadow: 0 20px 35px -12px black, inset 0 1px 0 rgba(255, 215, 160, 0.1);
      position: relative;
    }

    /* decorative corner runes */
    .login-card::before,
    .login-card::after {
      content: "⸸";
      position: absolute;
      font-size: 1.2rem;
      color: #9b5e2e;
      opacity: 0.5;
      font-family: monospace;
    }
    .login-card::before {
      top: 12px;
      left: 18px;
      transform: rotate(-15deg);
    }
    .login-card::after {
      bottom: 12px;
      right: 18px;
      transform: rotate(15deg);
    }

    .eldritch-title {
      text-align: center;
      margin-bottom: 1.5rem;
      position: relative;
    }
    .eldritch-title h2 {
      font-family: 'IM Fell English SC', 'Cinzel', serif;
      font-size: 1.3rem;
      font-weight: 600;
      letter-spacing: 4px;
      color: #e6c394;
      text-transform: uppercase;
      text-shadow: 0 2px 5px #2f1509;
      background: linear-gradient(135deg, #ebc28e, #b5783a);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      display: inline-block;
      border-bottom: 1px dashed #a26e3e;
      padding-bottom: 6px;
    }
    .sub-malediction {
      font-size: 0.65rem;
      color: #8b6b4b;
      letter-spacing: 2px;
      margin-top: 6px;
    }

    /* FLASH MESSAGE — BLOODY VELLUM */
    .flash-msg {
      padding: 10px 16px;
      margin-bottom: 22px;
      font-size: 0.8rem;
      font-weight: 500;
      text-align: center;
      background: rgba(30, 12, 5, 0.8);
      border-left: 4px solid;
      font-family: 'DM Sans', monospace;
      backdrop-filter: blur(4px);
    }
    .flash-msg.error {
      border-left-color: #b13e2b;
      color: #f3a683;
      background: #1f0c07b3;
      box-shadow: 0 0 6px rgba(180, 50, 20, 0.4);
    }

    /* FORM STYLES — RUINED SHRINE */
    .form-group {
      margin-bottom: 1.4rem;
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
    input {
      width: 100%;
      background: #0e0703d9;
      border: 1px solid #66412e;
      color: #f3e2cf;
      padding: 12px 16px;
      font-family: 'DM Sans', monospace;
      font-size: 0.9rem;
      border-radius: 8px;
      transition: all 0.2s;
      outline: none;
      box-shadow: inset 0 1px 3px #00000040;
    }
    input:focus {
      border-color: #cb7b3c;
      background: #1f110ad9;
      box-shadow: 0 0 8px rgba(195, 100, 35, 0.4), inset 0 0 3px #3a1f0e;
    }

    /* SOULS BUTTON — IRON + EMBERS */
    .btn-great-rune {
      width: 100%;
      background: #24180ee0;
      border: 1px solid #b17a48;
      color: #fbe9c3;
      padding: 14px 10px;
      margin-top: 12px;
      font-family: 'Cinzel', 'IM Fell English SC', serif;
      font-weight: 700;
      font-size: 0.85rem;
      letter-spacing: 5px;
      text-transform: uppercase;
      cursor: pointer;
      border-radius: 40px;
      transition: all 0.3s;
      box-shadow: 0 5px 12px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 200, 100, 0.2);
      position: relative;
      overflow: hidden;
    }
    .btn-great-rune:hover {
      background: #3e2618;
      border-color: #dc9f5c;
      color: #fff3e0;
      text-shadow: 0 0 4px #c2732e;
      letter-spacing: 6px;
      box-shadow: 0 0 15px rgba(210, 100, 30, 0.6);
      transform: scale(0.99);
    }

    /* STATS EXTRA (VINTAGE WARNING) */
    .cursed-hint {
      display: flex;
      justify-content: center;
      gap: 2rem;
      margin-top: 1.8rem;
      padding-top: 1rem;
      border-top: 1px dashed #67442e;
      font-size: 0.7rem;
      color: #9f7a58;
      letter-spacing: 1px;
    }
    .cursed-hint span {
      font-family: monospace;
    }

    .site-sigil {
      text-align: center;
      margin-bottom: 20px;
    }
    .sigil-mark {
      width: 64px;
      height: 64px;
      margin: 0 auto 8px;
      background: #2f1b0e;
      border-radius: 100px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      border: 1px solid #ab6f3c;
      box-shadow: 0 0 8px #7a3e1a;
      color: #e4b175;
      transform: rotate(12deg);
    }
    .sigil-text {
      font-family: 'IM Fell English SC', serif;
      font-size: 1.6rem;
      letter-spacing: 7px;
      font-weight: 600;
      background: linear-gradient(130deg, #e7c294, #9c673c);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      text-shadow: 0 2px 3px black;
    }
    .sigil-sub {
      font-size: 0.65rem;
      text-transform: uppercase;
      color: #9c7456;
      margin-top: 4px;
      letter-spacing: 2px;
    }

    /* responsive */
    @media (max-width: 500px) {
      .status-container {
        margin: 1rem;
      }
      .login-card {
        padding: 1.5rem;
      }
      .souls-status-panel {
        flex-direction: column;
        gap: 0.6rem;
      }
      .stat-block {
        text-align: left;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
      }
      .stat-value {
        display: inline-block;
        margin-left: 8px;
      }
      .stat-label {
        border-bottom: none;
      }
    }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="vintage-overlay"></div>
<div class="grunge-texture"></div>

<div class="status-container">
  <!-- SOULS-LIKE STATUS HEADER (MIMICS CHARACTER STAT SCREEN) -->
  <div class="souls-status-panel">
    <div class="stat-block">
      <div class="stat-label">VIGOR</div>
      <div class="stat-value">19 / 19</div>
      <div class="rune-sigil">✦ great rune</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">MIND</div>
      <div class="stat-value">14 / 14</div>
      <div class="rune-sigil">✧ focus</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">ENDURANCE</div>
      <div class="stat-value">22 / 22</div>
      <div class="rune-sigil">⛉ poise</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">AUTHORITY</div>
      <div class="stat-value">0 / 1</div>
      <div class="rune-sigil">✦ UNGRANTED</div>
    </div>
  </div>

  <!-- MAIN AUTHENTICATION CARD (VINTAGE EVIL) -->
  <div class="login-card">
    <div class="site-sigil">
      <div class="sigil-mark">🎲</div>
      <div class="sigil-text">YOPY</div>
      <div class="sigil-sub">Finger Reader · Roundtable Hold</div>
    </div>
    <div class="eldritch-title">
      <h2>✦  GRANT ACCESS  ✦</h2>
      <div class="sub-malediction">"Those who walk before the Erdtree"</div>
    </div>

    <!-- PHP FLASH MESSAGE (error or info) -->
    <?php if (!empty($flash)): ?>
      <div class="flash-msg <?= htmlspecialchars($flash['type']) ?>">
        ⚔ <?= htmlspecialchars($flash['msg']) ?> ⚔
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/admin.php?action=doLogin">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />

      <div class="form-group">
        <label for="email">☠ Tarnished Ember (email)</label>
        <input type="email" id="email" name="email" autocomplete="email" required placeholder="admin@yopy.app" />
      </div>

      <div class="form-group">
        <label for="password">🔑  Great Rune Seal (password)</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required placeholder="··············" />
      </div>

      <button type="submit" class="btn-great-rune">⚔  Enkindle Access  ⚔</button>
    </form>

    <div class="cursed-hint">
      <span>✜  TWO FINGERS  ✜</span>
      <span>⛧  ELDEN LORD  ⛧</span>
      <span>☠  SEEK THRONE  ☠</span>
    </div>
  </div>
  <!-- subtle death message -->
  <div style="text-align: center; margin-top: 14px; font-size: 0.6rem; color: #6b4935; letter-spacing: 1px; font-family: monospace;">
    「 GRAVELY READ ONLY — ADMIN CONSOLE 」
  </div>
</div>

<script>
  (function() {
    // Dark Souls vintage ember / ash particles — evil brown/orange vibe
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let width, height;
    let embers = [];

    const EMBER_COUNT = 130;
    const colors = ['#4f2a1b', '#7b3f1a', '#b45f2b', '#5a2e18', '#3f1f0c', '#ab6a36'];

    function resizeCanvas() {
      width = canvas.width = window.innerWidth;
      height = canvas.height = window.innerHeight;
      initEmbers();
    }

    function initEmbers() {
      embers = [];
      for (let i = 0; i < EMBER_COUNT; i++) {
        embers.push({
          x: Math.random() * width,
          y: Math.random() * height,
          radius: Math.random() * 3.5 + 0.8,
          speedY: Math.random() * 0.6 + 0.12,
          speedX: (Math.random() - 0.5) * 0.18,
          opacity: Math.random() * 0.55 + 0.15,
          color: colors[Math.floor(Math.random() * colors.length)],
          flicker: Math.random() * 0.05 + 0.02,
        });
      }
    }

    function drawEmbers() {
      if (!ctx) return;
      ctx.clearRect(0, 0, width, height);
      
      // Add subtle dark fog
      ctx.fillStyle = '#0b0502';
      ctx.fillRect(0, 0, width, height);
      
      for (let e of embers) {
        ctx.beginPath();
        ctx.arc(e.x, e.y, e.radius, 0, Math.PI * 2);
        // glowing ember effect
        const glow = e.opacity + Math.sin(Date.now() * 0.002 * e.flicker) * 0.1;
        ctx.fillStyle = e.color;
        ctx.shadowColor = '#c1622b';
        ctx.shadowBlur = 8;
        ctx.fill();
        ctx.shadowBlur = 0;
        // inner warm light
        ctx.beginPath();
        ctx.arc(e.x - 0.5, e.y - 0.3, e.radius * 0.5, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(255, 140, 50, ${e.opacity * 0.5})`;
        ctx.fill();
        
        // movement
        e.y -= e.speedY;
        e.x += e.speedX;
        
        // reset when off screen (creates infinite fall)
        if (e.y < -15) {
          e.y = height + 8;
          e.x = Math.random() * width;
        }
        if (e.x > width + 20) e.x = -20;
        if (e.x < -20) e.x = width + 20;
        
        // drifting upward souls-like
        if (Math.random() < 0.02) {
          e.speedX += (Math.random() - 0.5) * 0.05;
          e.speedX = Math.min(Math.max(e.speedX, -0.25), 0.25);
        }
      }
      
      // add sparse floating dust
      ctx.fillStyle = 'rgba(160, 80, 30, 0.08)';
      for (let i = 0; i < 35; i++) {
        if (Math.random() > 0.97) {
          ctx.beginPath();
          ctx.arc(Math.random() * width, Math.random() * height, Math.random() * 2, 0, Math.PI * 2);
          ctx.fill();
        }
      }
      requestAnimationFrame(drawEmbers);
    }
    
    window.addEventListener('resize', () => {
      resizeCanvas();
    });
    resizeCanvas();
    drawEmbers();
    
    // tiny flicker on card edges for candlelight feel
    const card = document.querySelector('.login-card');
    if (card) {
      setInterval(() => {
        const intensity = 0.7 + Math.random() * 0.3;
        card.style.boxShadow = `0 20px 35px -10px black, inset 0 0 3px rgba(190, 100, 30, ${intensity * 0.2})`;
      }, 800);
    }
  })();
</script>

<!-- Additional gothic decorative script: ensures that the canvas background does not overshadow content and adds eerie red vignette dynamic -->
<style>
  /* extra fine-tuning for souls vintage */
  ::selection {
    background: #8b3c1a;
    color: #fbe2bc;
  }
  input:-webkit-autofill,
  input:-webkit-autofill:focus {
    transition: background-color 0s 600000s, color 0s 600000s;
    -webkit-text-fill-color: #ecd9c6;
  }
  .btn-great-rune:active {
    transform: scale(0.97);
    transition: 0.05s;
    box-shadow: inset 0 2px 5px black;
  }
  body {
    background: #070302;
  }
  /* add worn out paper texture inside card */
  .login-card {
    background-image: repeating-linear-gradient(45deg, rgba(80, 40, 20, 0.2) 0px, rgba(80, 40, 20, 0.2) 1px, transparent 1px, transparent 8px);
  }
  .souls-status-panel {
    backdrop-filter: blur(10px);
    background: #1f1209b3;
  }
  .stat-value {
    font-family: 'Cinzel', monospace;
    font-weight: 700;
    letter-spacing: 1px;
  }
  /* runic glow on hover for stats */
  .stat-block:hover .stat-value {
    text-shadow: 0 0 5px #c7783a;
    transition: 0.2s;
  }
</style>
</body>
</html>