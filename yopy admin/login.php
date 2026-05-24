<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login — YOPY</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg-deep: #0d0718; --bg-base: #1c0f30;
      --violet: #7c3aed; --lilac: #c4b5fd; --violet-soft: #a78bfa;
      --pink: #e879f9; --text-primary: #f0eaff; --text-muted: #9d86c8;
      --glass-border: rgba(167,139,250,0.18); --error: #f87171;
      --font-display: "Cinzel",serif; --font-body: "DM Sans",sans-serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
    html, body {
      min-height: 100vh; width: 100%;
      background: var(--bg-deep);
      font-family: var(--font-body);
      color: var(--text-primary);
      display: flex; align-items: center; justify-content: center;
    }
    #bg-canvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
    .bg-overlay {
      position: fixed; inset: 0; z-index: 1; pointer-events: none;
      background:
        radial-gradient(ellipse 80% 60% at 15% 10%, rgba(124,58,237,0.20) 0%, transparent 60%),
        radial-gradient(ellipse 55% 45% at 85% 80%, rgba(232,121,249,0.14) 0%, transparent 55%);
    }

    .login-wrap {
      position: relative; z-index: 2;
      width: 100%; max-width: 420px;
      padding: 24px;
      animation: fadeUp 0.7s ease both;
    }
    @keyframes fadeUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:none; } }

    .login-logo {
      text-align: center; margin-bottom: 36px;
    }
    .login-logo-mark {
      width: 56px; height: 56px; border-radius: 18px; margin: 0 auto 14px;
      background: linear-gradient(135deg, #7c3aed, #e879f9);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; box-shadow: 0 0 30px rgba(124,58,237,0.45);
    }
    .login-logo-text {
      font-family: var(--font-display); font-size: 1.6rem; font-weight: 700;
      letter-spacing: 0.2em;
      background: linear-gradient(135deg, #f0eaff 0%, #c4b5fd 60%, #a78bfa 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .login-logo-sub {
      font-size: 0.75rem; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--text-muted); margin-top: 4px;
    }

    .login-card {
      background: rgba(35,20,66,0.65);
      border: 1px solid var(--glass-border);
      border-radius: 24px; padding: 36px;
      backdrop-filter: blur(24px);
      box-shadow: 0 24px 60px rgba(0,0,0,0.4);
    }
    .login-title {
      font-family: var(--font-display); font-size: 0.9rem;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--text-muted); text-align: center;
      margin-bottom: 28px;
    }

    .flash-msg {
      padding: 11px 16px; border-radius: 10px; margin-bottom: 20px;
      font-size: 0.84rem; text-align: center; font-weight: 500;
    }
    .flash-msg.error { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: var(--error); }

    .form-group { margin-bottom: 18px; }
    label { display: block; font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; }
    input {
      width: 100%; background: rgba(13,7,24,0.6); border: 1px solid var(--glass-border);
      color: var(--text-primary); border-radius: 12px;
      padding: 13px 16px; font-family: var(--font-body); font-size: 0.9rem;
      outline: none; transition: border-color 0.25s, box-shadow 0.25s;
    }
    input:focus { border-color: var(--violet-soft); box-shadow: 0 0 0 3px rgba(124,58,237,0.15); }

    .btn-login {
      width: 100%; margin-top: 8px;
      background: linear-gradient(135deg, #7c3aed, #a855f7);
      color: #fff; border: none; border-radius: 50px;
      padding: 15px; font-family: var(--font-display);
      font-size: 0.8rem; letter-spacing: 0.15em; text-transform: uppercase;
      cursor: pointer; font-weight: 600;
      box-shadow: 0 0 24px rgba(124,58,237,0.35);
      transition: all 0.25s ease;
    }
    .btn-login:hover { box-shadow: 0 0 36px rgba(124,58,237,0.55); transform: translateY(-1px); }

    .login-hint {
      text-align: center; margin-top: 18px;
      font-size: 0.75rem; color: var(--text-muted); letter-spacing: 0.04em;
    }
  </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="bg-overlay"></div>

<div class="login-wrap">
  <div class="login-logo">
    <div class="login-logo-mark">✦</div>
    <div class="login-logo-text">YOPY</div>
    <div class="login-logo-sub">Admin Console</div>
  </div>

  <div class="login-card">
    <div class="login-title">Sign in to continue</div>

    <?php if (!empty($flash)): ?>
      <div class="flash-msg <?= htmlspecialchars($flash['type']) ?>">
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=doLogin">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" autocomplete="email" required placeholder="admin@yopy.app" />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required placeholder="••••••••" />
      </div>

      <button type="submit" class="btn-login">Enter Admin Panel</button>
    </form>

    <p class="login-hint">Protected area — authorised personnel only.</p>
  </div>
</div>

<script>
(function() {
  const cv = document.getElementById('bg-canvas');
  const ctx = cv.getContext('2d');
  let W, H, pts = [];
  function resize() { W = cv.width = innerWidth; H = cv.height = innerHeight; }
  window.addEventListener('resize', resize); resize();
  for (let i = 0; i < 80; i++) pts.push({
    x: Math.random()*W, y: Math.random()*H,
    r: Math.random()*1.5+0.2, vy: Math.random()*0.22+0.05,
    vx: (Math.random()-.5)*0.1, op: Math.random()*0.3+0.04,
    c: ['rgba(124,58,237,','rgba(167,139,250,','rgba(232,121,249,'][Math.floor(Math.random()*3)]
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
</body>
</html>
