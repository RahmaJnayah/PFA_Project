<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title>Fractured Grace · YOPY | 404: Lost Archive</title>
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

    /* Souls vintage atmosphere */
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

    /* main container — status screen style */
    .status-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 580px;
      margin: 2rem;
      animation: riseFromAsh 0.9s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
    }

    @keyframes riseFromAsh {
      0% { opacity: 0; transform: translateY(35px) scale(0.97); filter: blur(4px); }
      100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
    }

    /* souls-like stat panel (mimics character status) */
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
      cursor: default;
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

    /* 404 card — broken seal */
    .error-card {
      background: #140e09e0;
      backdrop-filter: blur(14px);
      border: 1px solid #79553d;
      border-top: 2px solid #b87a44;
      border-radius: 0 0 24px 24px;
      padding: 2rem 2rem 2.2rem;
      box-shadow: 0 20px 35px -12px black, inset 0 1px 0 rgba(255, 215, 160, 0.1);
      position: relative;
      text-align: center;
    }

    .error-card::before,
    .error-card::after {
      content: "⸸";
      position: absolute;
      font-size: 1.2rem;
      color: #9b5e2e;
      opacity: 0.5;
      font-family: monospace;
    }
    .error-card::before { top: 12px; left: 18px; transform: rotate(-15deg); }
    .error-card::after { bottom: 12px; right: 18px; transform: rotate(15deg); }

    /* runic symbol */
    .rune-symbol {
      font-size: 5rem;
      filter: drop-shadow(0 0 20px rgba(180, 90, 30, 0.5));
      margin-bottom: 0.5rem;
      cursor: pointer;
      transition: all 0.3s;
    }
    .rune-symbol:hover {
      transform: scale(1.02);
      filter: drop-shadow(0 0 28px #b56f3a);
    }

    .glowing-404 {
      font-family: 'IM Fell English SC', 'Cinzel', serif;
      font-size: 5rem;
      font-weight: 700;
      line-height: 1;
      background: linear-gradient(135deg, #e7c294, #b5783a, #7c481f);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      text-shadow: 0 0 8px rgba(0,0,0,0.5);
      letter-spacing: 6px;
    }

    .status-badge {
      font-family: var(--font-display);
      font-size: 1rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: #b5814a;
      margin-top: 0.25rem;
    }

    .lost-message {
      color: #dbbc95;
      font-size: 0.9rem;
      max-width: 360px;
      margin: 1rem auto;
      line-height: 1.7;
      border-left: 2px solid #a26336;
      padding-left: 1rem;
    }

    .btn-return {
      display: inline-block;
      margin-top: 0.8rem;
      background: #24180ee0;
      border: 1px solid #b17a48;
      color: #fbe9c3;
      padding: 10px 24px;
      font-family: 'Cinzel', 'IM Fell English SC', serif;
      font-weight: 600;
      font-size: 0.75rem;
      letter-spacing: 4px;
      text-transform: uppercase;
      text-decoration: none;
      border-radius: 40px;
      transition: all 0.3s;
      box-shadow: 0 2px 8px black;
    }
    .btn-return:hover {
      background: #3e2618;
      border-color: #dc9f5c;
      letter-spacing: 5px;
      box-shadow: 0 0 12px rgba(210, 100, 30, 0.5);
    }

    /* MYSTERY LAYER (plot twist modal) */
    .fools-sanctum {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(3, 1, 0, 0.94);
      backdrop-filter: blur(12px);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      visibility: hidden;
      opacity: 0;
      transition: visibility 0s 0.3s, opacity 0.3s ease;
      font-family: 'Cinzel', serif;
    }
    .fools-sanctum.active {
      visibility: visible;
      opacity: 1;
      transition: visibility 0s 0s, opacity 0.3s ease;
    }
    .sanctum-card {
      max-width: 480px;
      background: #170f09f2;
      border: 2px ridge #b57242;
      border-radius: 28px;
      padding: 2rem;
      text-align: center;
      box-shadow: 0 0 55px rgba(140, 60, 20, 0.7);
      transform: scale(0.95);
      transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }
    .active .sanctum-card {
      transform: scale(1);
    }
    .sanctum-icon {
      font-size: 3.6rem;
      filter: drop-shadow(0 0 8px #c5702c);
    }
    .sanctum-title {
      font-size: 1.9rem;
      font-weight: 700;
      background: linear-gradient(135deg, #e6bc8b, #b4642a);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      letter-spacing: 3px;
      font-family: 'IM Fell English SC', monospace;
    }
    .sanctum-sub {
      font-size: 0.7rem;
      color: #b68b60;
      text-transform: uppercase;
      letter-spacing: 3px;
      margin-bottom: 1.2rem;
    }
    .revelation-text {
      font-size: 0.9rem;
      line-height: 1.6;
      color: #efdbbc;
      margin: 1rem 0;
      border-left: 2px solid #b5652e;
      padding-left: 1rem;
      text-align: left;
    }
    .riddle-input {
      background: #0b0501;
      border: 1px solid #ac6d3c;
      color: #f3ddb2;
      padding: 8px 12px;
      width: 80%;
      margin: 0.8rem 0;
      font-family: monospace;
      text-align: center;
      border-radius: 40px;
    }
    .sanctum-btn {
      background: none;
      border: 1px solid #8b573a;
      color: #c99362;
      padding: 6px 18px;
      border-radius: 60px;
      margin: 0.4rem;
      cursor: pointer;
      font-family: 'Cinzel';
      transition: 0.2s;
    }
    .sanctum-btn:hover {
      background: #5f351e;
      color: #ffe2be;
      border-color: #db8f48;
    }
    .feedback {
      margin-top: 12px;
      font-size: 0.7rem;
      color: #c48c54;
      min-height: 40px;
    }
    /* hidden clue indicator */
    .hidden-clue {
      font-size: 0.65rem;
      color: #ac7a4a;
      margin-top: 12px;
      letter-spacing: 1px;
      cursor: help;
      border-bottom: 1px dotted #a65c2c;
      display: inline-block;
    }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="vintage-overlay"></div>
<div class="grunge-texture"></div>

<div class="status-container">
  <!-- STATUS PANEL (souls-like) -->
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
    <div class="stat-block" id="arcaneStat">
      <div class="stat-label">ARCANE</div>
      <div class="stat-value">0 / 1</div>
      <div class="rune-sigil">⛉ SEALED</div>
    </div>
    <div class="stat-block">
      <div class="stat-label">DISCOVERY</div>
      <div class="stat-value">??</div>
      <div class="rune-sigil">✦ hidden</div>
    </div>
  </div>

  <!-- 404 CARD (vintage evil) -->
  <div class="error-card">
    <div class="rune-symbol" id="mysticRune">✦</div>
    <div class="glowing-404">404</div>
    <div class="status-badge">PAGE NOT FOUND</div>
    <div class="lost-message">
      The path you seek has crumbled into ash. The Grace of this archive is absent — yet a whisper remains.
    </div>
    <a href="<?= $basePath ?? '#' ?>/admin.php?action=dashboard" class="btn-return">← RETURN TO DASHBOARD</a>
    <div class="hidden-clue" id="clueTrigger">* Something stirs beyond the error... *</div>
  </div>
  <div style="text-align: center; margin-top: 14px; font-size: 0.6rem; color: #6b4935;">
    「 FRACTURED SITE OF GRACE 」
  </div>
</div>

<!-- MYSTERY SANCTUM (Plot Twist / Lord of Mysteries style) -->
<div id="foolsSanctum" class="fools-sanctum">
  <div class="sanctum-card">
    <div class="sanctum-icon">*</div>
    <div class="sanctum-title">THE FOOL'S CORRIDOR</div>
    <div class="sanctum-sub">beyond the fracture</div>
    <div class="revelation-text" id="revelationMsg">
      “You stepped into a missing page, yet the gray fog parts.<br>
      The <strong>404</strong> is not an error — it is a seal. Only one who speaks the<br>
      <em>True Name of the Forgotten Archive</em> may see what lies beneath.”
    </div>
    <div>
      <input type="text" id="sealAnswer" class="riddle-input" placeholder="✜  whisper the name  ✜" autocomplete="off">
    </div>
    <button id="unsealBtn" class="sanctum-btn">⚔ Invoke the Forgotten ⚔</button>
    <button id="closeSanctum" class="sanctum-btn">Return to Fracture</button>
    <div id="sanctumFeedback" class="feedback"></div>
  </div>
</div>

<script>
  (function() {
    // --- Souls-like canvas: ash and embers ---
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

    // --- MYSTERY PLOT TWIST: Lord of Mysteries style hidden corridor ---
    const mysticRune = document.getElementById('mysticRune');
    const clueTrigger = document.getElementById('clueTrigger');
    const sanctum = document.getElementById('foolsSanctum');
    const closeSanctumBtn = document.getElementById('closeSanctum');
    const unsealBtn = document.getElementById('unsealBtn');
    const sealAnswer = document.getElementById('sealAnswer');
    const sanctumFeedback = document.getElementById('sanctumFeedback');
    const revelationMsg = document.getElementById('revelationMsg');
    const arcaneStatValueSpan = document.querySelector('#arcaneStat .stat-value');
    const arcaneRuneSpan = document.querySelector('#arcaneStat .rune-sigil');

    let sanctumOpened = false;

    function openSanctum() {
      if (sanctum.classList.contains('active')) return;
      sanctum.classList.add('active');
      document.body.style.overflow = 'hidden';
      sanctumOpened = true;
      // reset feedback
      sealAnswer.value = '';
      sanctumFeedback.innerHTML = '';
      revelationMsg.innerHTML = `“You stepped into a missing page, yet the gray fog parts.<br>
      The <strong>404</strong> is not an error — it is a seal. Only one who speaks the<br>
      <em>True Name of the Forgotten Archive</em> may see what lies beneath.”`;
    }

    function closeSanctum() {
      sanctum.classList.remove('active');
      document.body.style.overflow = '';
    }

    // open via rune click or clue text
    mysticRune.addEventListener('click', openSanctum);
    clueTrigger.addEventListener('click', openSanctum);

    closeSanctumBtn.addEventListener('click', closeSanctum);
    // click outside to close
    sanctum.addEventListener('click', (e) => {
      if (e.target === sanctum) closeSanctum();
    });

    // The riddle answer (Lord of Mysteries reference)
    unsealBtn.addEventListener('click', () => {
      const answer = sealAnswer.value.trim().toLowerCase();
      // multiple valid answers referencing the Fool / Lord of Mysteries / 404 hidden lore
      const validKeys = ['the fool', 'klein moretti', 'fool', 'zhou mingrui', 'fool pathway', 'seer', 'lord of mysteries', 'fool’s error', '404 forgotten', 'gray fog', 'error'];
      const isValid = validKeys.some(key => answer === key || answer.includes(key) && answer.length > 2);
      
      if (isValid) {
        // PLOT TWIST: The 404 page reveals its hidden truth: the seal is lifted
        revelationMsg.innerHTML = `“You have whispered the forgotten name. The seal of 404 trembles...<br>
        The <strong>ARCANE</strong> stat awakens. The lost page was never missing — it was <em>hidden by the Fool</em>.<br>
        ✦ Now you see: The error is but a curtain. The true console lies beyond the veil. ✦”`;
        sanctumFeedback.innerHTML = '✨ The gray fog recedes. Your Discovery rises. ✨';
        
        // update ARCANE stat from 0/1 to 1/1 as a visual reward (mystery)
        if (arcaneStatValueSpan && arcaneStatValueSpan.innerText === '0 / 1') {
          arcaneStatValueSpan.innerText = '1 / 1';
          arcaneStatValueSpan.style.textShadow = '0 0 10px #e6943a';
          if (arcaneRuneSpan) arcaneRuneSpan.innerHTML = '✦ UNVEILED (Fool’s Insight)';
        }
        // also change the hidden clue text
        clueTrigger.innerHTML = '* The Fool watches from the corridor... *';
        clueTrigger.style.color = '#dc9f5c';
        // disable unseal button to keep lore
        unsealBtn.disabled = true;
        unsealBtn.innerText = '✦ Truth Unveiled ✦';
        // extra atmospheric: add a little floating text effect
        const runeElem = document.querySelector('.rune-symbol');
        runeElem.style.filter = 'drop-shadow(0 0 25px #d98a3a)';
      } else {
        sanctumFeedback.innerHTML = '❌ The name does not echo in the void. Try again, seeker of truths.';
        revelationMsg.innerHTML = `“The 404 remains sealed. Only the true name of the Fool or the Forgotten Archive can tear the veil.”`;
        // shake effect
        const card = document.querySelector('.sanctum-card');
        if (card) {
          card.style.transform = 'scale(0.97)';
          setTimeout(() => { card.style.transform = ''; }, 150);
        }
      }
    });

    // Additional hidden interaction: if user types 'fool' into any input? optional easter egg
    const anyInput = document.querySelectorAll('input');
    anyInput.forEach(inp => {
      inp.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase();
        if ((val.includes('fool') || val.includes('klein')) && !sanctumOpened) {
          const whisper = document.createElement('div');
          whisper.textContent = '⚡ A whisper from the gray fog... click the rune ⚡';
          whisper.style.fontSize = '0.65rem';
          whisper.style.color = '#cf9f6e';
          whisper.style.marginTop = '6px';
          const parent = inp.closest('.error-card') || document.body;
          if (!parent.querySelector('.whisper-fool')) {
            whisper.classList.add('whisper-fool');
            parent.appendChild(whisper);
            setTimeout(() => whisper.remove(), 2500);
          }
        }
      });
    });

    // small atmospheric flicker for error card
    const errorCard = document.querySelector('.error-card');
    if (errorCard) {
      setInterval(() => {
        const intensity = 0.7 + Math.random() * 0.3;
        errorCard.style.boxShadow = `0 20px 35px -10px black, inset 0 0 3px rgba(190, 100, 30, ${intensity * 0.2})`;
      }, 1200);
    }
  })();
</script>
</body>
</html>