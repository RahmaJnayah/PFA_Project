<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$basePath = $basePath ?? preg_replace('#/views/auth$#', '', $scriptDir);
if ($basePath === '/') {
  $basePath = '';
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$old     = $_SESSION['register_old']     ?? [];
$error   = $_SESSION['register_error']   ?? '';
$success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_old'], $_SESSION['register_error'], $_SESSION['register_success']);

$safeBasePath = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
$csrfToken = htmlspecialchars((string) $_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account — YOPY</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;0,9..144,900;1,9..144,300;1,9..144,700&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
/* ──────────────────────────────────────
   TOKENS — login page palette
────────────────────────────────────── */
:root {
  --ink:         #040111;
  --ink-2:       #0a0820;
  --violet:      #8b5cf6;
  --violet-deep: #5b21b6;
  --lavender:    #c4b5fd;
  --violet-lt:   #d8b4fe;
  --magenta:     #ec4899;
  --cyan:        #22d3ee;
  --gold:        #ffb703;
  --white-soft:  rgba(255,255,255,.55);
  --cream:       rgba(255,255,255,.88);
  --line:        rgba(255,255,255,.10);
  --ff-head:     'Fraunces', serif;
  --ff-body:     'Outfit', sans-serif;
  --ease-out:    cubic-bezier(.22,1,.36,1);
}

/* ──────────────────────────────────────
   RESET / BASE
────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:var(--ff-body);background:var(--ink);color:var(--cream);overflow:hidden}
a{text-decoration:none}button{cursor:pointer}

/* ──────────────────────────────────────
   CURSOR
────────────────────────────────────── */
body{cursor:none}
#cur{
  position:fixed;width:9px;height:9px;border-radius:50%;
  background:var(--gold);pointer-events:none;z-index:9999;
  transform:translate(-50%,-50%);mix-blend-mode:difference;
}
#cur-ring{
  position:fixed;width:32px;height:32px;border-radius:50%;
  border:1px solid var(--gold);pointer-events:none;z-index:9998;
  transform:translate(-50%,-50%);opacity:.45;
  transition:width .3s var(--ease-out),height .3s var(--ease-out),opacity .2s;
}

/* ──────────────────────────────────────
   SHELL — 2-column grid
────────────────────────────────────── */
.shell{
  position:relative;z-index:1;
  display:grid;grid-template-columns:56% 44%;
  height:100vh;
}

/* ──────────────────────────────────────
   LEFT — DECORATIVE
────────────────────────────────────── */
.deco{
  position:relative;overflow:hidden;
  background:linear-gradient(155deg,#0d0424 0%,#040111 55%,#0d0424 100%);
  display:flex;flex-direction:column;
  justify-content:space-between;padding:52px 44px;
}

/* Noise texture */
.deco::before{
  content:'';position:absolute;inset:0;z-index:0;pointer-events:none;
  opacity:.032;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  background-size:200px;
}

/* Right edge separator */
.deco::after{
  content:'';position:absolute;right:0;top:8%;bottom:8%;width:1px;
  background:linear-gradient(to bottom,transparent,rgba(139,92,246,.25) 30%,rgba(139,92,246,.25) 70%,transparent);
  z-index:20;
}

/* ── Compass rose ── */
.compass{
  position:absolute;top:50%;left:50%;
  width:500px;height:500px;
  transform:translate(-50%,-50%);
  z-index:1;pointer-events:none;
}
.ring{
  position:absolute;border-radius:50%;
  border:1px solid rgba(139,92,246,.12);
  animation:spin linear infinite;
}
.ring:nth-child(1){inset:0;      animation-duration:100s;}
.ring:nth-child(2){inset:30px;   animation-duration:70s; animation-direction:reverse;}
.ring:nth-child(3){inset:72px;   animation-duration:150s;}
.ring:nth-child(4){inset:118px;  animation-duration:50s; animation-direction:reverse;}
.ring:nth-child(5){inset:168px;  animation-duration:220s;}
@keyframes spin{to{transform:rotate(360deg)}}

/* tick marks */
.ring::before,.ring::after{
  content:'';position:absolute;background:rgba(167,139,250,.55);border-radius:2px;
}
.ring::before{top:50%;left:-5px;width:10px;height:1px;transform:translateY(-50%)}
.ring::after {left:50%;top:-5px;width:1px;height:10px;transform:translateX(-50%)}

/* centre glow */
.c-core{
  position:absolute;inset:206px;border-radius:50%;
  border:1px solid rgba(139,92,246,.40);
  box-shadow:0 0 50px rgba(139,92,246,.15),inset 0 0 30px rgba(139,92,246,.08);
  display:flex;align-items:center;justify-content:center;
}
.c-star{
  width:26px;height:26px;
  background:linear-gradient(135deg,var(--lavender),var(--magenta));
  clip-path:polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%);
  animation:starPulse 3.5s ease-in-out infinite;opacity:.85;
}
@keyframes starPulse{
  0%,100%{transform:scale(1) rotate(0deg);opacity:.85}
  50%    {transform:scale(1.25) rotate(18deg);opacity:1}
}

/* outer dots — cycle through palette */
.c-dot{
  position:absolute;width:5px;height:5px;
  border-radius:50%;opacity:.65;
  animation:dotPulse 2.8s ease-in-out infinite;
}
.c-dot:nth-child(1){top:0;  left:50%;transform:translate(-50%,-50%);background:var(--lavender)}
.c-dot:nth-child(2){bottom:0;left:50%;transform:translate(-50%,50%);animation-delay:.7s;background:var(--magenta)}
.c-dot:nth-child(3){left:0; top:50%; transform:translate(-50%,-50%);animation-delay:1.4s;background:var(--cyan)}
.c-dot:nth-child(4){right:0;top:50%; transform:translate(50%,-50%); animation-delay:2.1s;background:var(--gold)}
@keyframes dotPulse{
  0%,100%{opacity:.65;scale:1}
  50%    {opacity:1;  scale:1.9}
}

/* ── deco text ── */
.deco-top{position:relative;z-index:10}
.brand{
  font-family:var(--ff-head);font-size:22px;font-weight:900;
  letter-spacing:.22em;text-transform:uppercase;
  background:linear-gradient(90deg,var(--lavender),var(--cyan));
  background-size:200% auto;
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  animation:shine 5s linear infinite;
  opacity:0;animation:fadeUp .8s var(--ease-out) .1s forwards,shine 5s linear .1s infinite;
}

.deco-bot{position:relative;z-index:10}
.headline{
  font-family:var(--ff-head);
  font-size:clamp(30px,3.2vw,46px);font-weight:900;line-height:1.14;
  color:#fff;margin-bottom:14px;
  opacity:0;animation:fadeUp .8s var(--ease-out) .25s forwards;
}
.headline em{
  font-style:italic;
  background:linear-gradient(130deg,var(--violet-lt),var(--magenta));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.deco-sub{
  font-size:13.5px;font-weight:300;line-height:1.75;
  color:var(--white-soft);max-width:295px;
  opacity:0;animation:fadeUp .8s var(--ease-out) .40s forwards;
}
.pills{
  display:flex;gap:8px;margin-top:22px;flex-wrap:wrap;
  opacity:0;animation:fadeUp .8s var(--ease-out) .55s forwards;
}
.pill{
  font-size:11px;font-weight:500;letter-spacing:.05em;
  padding:5px 13px;border-radius:99px;
  border:1px solid rgba(167,139,250,.25);
  color:var(--lavender);background:rgba(139,92,246,.08);
}

/* ──────────────────────────────────────
   RIGHT — FORM
────────────────────────────────────── */
.fp{
  background:
    radial-gradient(ellipse 70% 55% at 80% 20%, rgba(91,33,182,.28) 0%, transparent 60%),
    radial-gradient(ellipse 50% 40% at 20% 85%, rgba(236,72,153,.16) 0%, transparent 55%),
    var(--ink-2);
  display:flex;flex-direction:column;justify-content:center;
  padding:48px 8% 48px 7%;
  overflow-y:auto;scrollbar-width:none;
  position:relative;
}
.fp::-webkit-scrollbar{display:none}
/* Animate the whole inner block as one reliable unit */
.fi{
  max-width:390px;width:100%;
  opacity:0;
  animation:fadeUp .75s var(--ease-out) .15s both;
}

/* header */
.eyebrow{
  font-size:11px;font-weight:600;letter-spacing:.16em;
  text-transform:uppercase;color:var(--lavender);margin-bottom:10px;
}
.form-title{
  font-family:var(--ff-head);
  font-size:clamp(26px,2.8vw,38px);font-weight:900;line-height:1.18;
  color:#fff;margin-bottom:6px;
}
.form-title span{
  font-style:italic;
  background:linear-gradient(130deg,var(--violet-lt),var(--magenta));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.form-sub{
  font-size:13.5px;font-weight:300;color:var(--white-soft);
  margin-bottom:36px;
}

/* step dots */
.steps{
  display:flex;gap:6px;margin-bottom:30px;
}
.sd{width:22px;height:3px;border-radius:99px;background:var(--line);transition:background .4s}
.sd.active{background:var(--violet)}
.sd.done  {background:rgba(139,92,246,.38)}

/* flash */
.flash{
  display:flex;align-items:flex-start;gap:10px;
  padding:12px 15px;border-radius:11px;
  font-size:13px;line-height:1.5;margin-bottom:22px;
}
.flash-err{background:rgba(208,0,0,.13);border:1px solid rgba(208,0,0,.38);color:#fca5a5}
.flash-ok {background:rgba(52,211,153,.08);border:1px solid rgba(52,211,153,.27);color:#86efac}
.fi-ico{font-size:14px;line-height:1.2}

/* fields */
.fields{display:flex;flex-direction:column}

.field{ position:relative;margin-bottom:26px; }
.field:nth-child(1){ animation:fieldSlide .5s var(--ease-out) .35s both }
.field:nth-child(2){ animation:fieldSlide .5s var(--ease-out) .43s both }
.field:nth-child(3){ animation:fieldSlide .5s var(--ease-out) .51s both }
.field:nth-child(4){ animation:fieldSlide .5s var(--ease-out) .59s both }

@keyframes fieldSlide{
  from{opacity:0;transform:translateX(-10px)}
  to  {opacity:1;transform:translateX(0)}
}

.frow{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.frow .field{margin-bottom:0}

.field label{
  display:block;font-size:10.5px;font-weight:600;letter-spacing:.13em;
  text-transform:uppercase;color:rgba(255,255,255,.35);
  margin-bottom:9px;transition:color .3s;
}
.field:focus-within label{color:var(--lavender)}

.field input,
.field select{
  width:100%;background:transparent;border:none;
  border-bottom:1px solid var(--line);
  padding:6px 34px 12px 2px;
  font-family:var(--ff-body);font-size:15px;font-weight:400;
  color:#fff;outline:none;
  transition:border-color .3s;
  appearance:none;-webkit-appearance:none;
}
.field input::placeholder{color:rgba(255,255,255,.15)}
.field select option{background:#0a0820}

/* animated underline — login's 4-stop palette */
.fline{
  position:absolute;bottom:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,var(--violet),var(--magenta),var(--cyan));
  transform:scaleX(0);transform-origin:left;
  transition:transform .4s var(--ease-out);pointer-events:none;
}
.field:focus-within .fline{transform:scaleX(1)}

/* icons */
.fic{
  position:absolute;right:4px;bottom:13px;
  color:rgba(255,255,255,.18);pointer-events:none;transition:color .3s;
}
.field:focus-within .fic{color:var(--lavender)}

.eye-btn{
  position:absolute;right:0;bottom:7px;
  background:none;border:none;padding:5px;
  color:rgba(255,255,255,.22);display:flex;
  align-items:center;transition:color .25s;
}
.eye-btn:hover{color:var(--lavender)}

.fcaret{
  position:absolute;right:2px;bottom:13px;
  pointer-events:none;color:rgba(255,255,255,.24);
}

/* strength */
.strength{
  display:flex;align-items:center;gap:5px;
  margin-top:8px;margin-bottom:26px;
  opacity:0;animation:fadeUp .65s var(--ease-out) .68s both;
}
.sbar{flex:1;height:2px;border-radius:99px;background:var(--line);transition:background .4s}
.sbar.l1{background:var(--magenta)}
.sbar.l2{background:var(--gold)}
.sbar.l3{background:var(--cyan)}
.sbar.l4{background:var(--lavender)}
.slbl{
  min-width:42px;font-size:10px;font-weight:600;
  letter-spacing:.08em;text-align:right;
  color:rgba(255,255,255,.28);transition:color .3s;
}

/* submit — exactly the login button */
.sbtn-wrap{
  margin-top:4px;
}
.sbtn{
  width:100%;padding:17px;border-radius:100px;border:none;
  font-family:var(--ff-body);font-size:15.5px;font-weight:700;
  color:#fff;letter-spacing:.02em;
  background:linear-gradient(135deg,#7c3aed,var(--violet),var(--magenta));
  background-size:200% auto;
  box-shadow:0 4px 20px rgba(139,92,246,.35);
  transition:background-position .4s,transform .25s var(--ease-out),box-shadow .25s;
  position:relative;overflow:hidden;
}
.sbtn::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.14) 0%,transparent 55%);
}
.sbtn:hover{
  background-position:right center;
  transform:translateY(-3px);
  box-shadow:0 12px 36px rgba(236,72,153,.50);
}
.sbtn:active{transform:translateY(0)}

/* footer */
.ffoot{
  margin-top:24px;text-align:center;
  font-size:13.5px;color:rgba(255,255,255,.38);
}
.lnk{
  font-weight:700;
  background:linear-gradient(90deg,var(--lavender),var(--magenta),var(--gold));
  background-size:200% auto;
  -webkit-background-clip:text;background-clip:text;color:transparent;
  animation:shine 4s linear infinite;
  position:relative;transition:filter .25s;
}
.lnk::after{
  content:'';position:absolute;bottom:-1px;left:0;right:0;
  height:1.5px;
  background:linear-gradient(90deg,var(--lavender),var(--magenta),var(--gold));
  transform:scaleX(0);transform-origin:left;transition:transform .35s;
}
.lnk:hover::after{transform:scaleX(1)}
.lnk:hover{filter:brightness(1.2)}

/* cursor uses violet */
#cur{background:var(--violet)}
#cur-ring{border-color:var(--violet)}

/* shine keyframe */
@keyframes shine{to{background-position:260% center}}

/* ──────────────────────────────────────
   PAGE TRANSITION VEIL
────────────────────────────────────── */
#veil{
  position:fixed;inset:0;z-index:9000;pointer-events:none;
  background:linear-gradient(135deg,#040111 0%,#0d0424 50%,#040111 100%);
  animation:veilReveal .65s var(--ease-out) forwards;
}
@keyframes veilReveal{
  0%  {opacity:1;transform:translateY(0)    scale(1)}
  100%{opacity:0;transform:translateY(-12px) scale(1.012)}
}
#veil.leaving{
  pointer-events:all;
  animation:veilCover .45s cubic-bezier(.55,0,1,.45) forwards;
}
@keyframes veilCover{
  0%  {opacity:0;transform:translateY(12px) scale(1.012)}
  100%{opacity:1;transform:translateY(0)   scale(1)}
}
.shell.is-leaving{
  animation:shellExit .45s cubic-bezier(.55,0,1,.45) forwards;
}
@keyframes shellExit{
  to{opacity:0;transform:scale(.97) translateY(-8px)}
}

/* keyframes */
@keyframes fadeUp{
  from{opacity:0;transform:translateY(16px)}
  to  {opacity:1;transform:translateY(0)}
}

/* ──────────────────────────────────────
   RESPONSIVE
────────────────────────────────────── */
@media(max-width:860px){
  .shell{grid-template-columns:1fr}
  html,body{overflow:auto}
  .deco{height:250px;padding:32px;justify-content:flex-end}
  .compass{width:300px;height:300px;left:62%;top:50%}
  .c-core{inset:120px}
  .headline{font-size:26px}
  .deco-sub,.pills{display:none}
  .fp{padding:36px 24px 52px}
  .fi{max-width:100%}
}
@media(max-width:480px){
  .deco{height:190px}
  .compass{width:210px;height:210px;left:64%}
  .c-core{inset:84px}
  .frow{grid-template-columns:1fr}
  .frow .field{margin-bottom:26px}
}
</style>
</head>
<body>

<div id="veil"></div>
<div id="cur"></div>
<div id="cur-ring"></div>

<div class="shell">

  <!-- ═══════════ LEFT — DECO ═══════════ -->
  <div class="deco">
    <!-- compass -->
    <div class="compass">
      <div class="ring"></div>
      <div class="ring"></div>
      <div class="ring"></div>
      <div class="ring"></div>
      <div class="ring"></div>
      <div class="c-dot"></div>
      <div class="c-dot"></div>
      <div class="c-dot"></div>
      <div class="c-dot"></div>
      <div class="c-core"><div class="c-star"></div></div>
    </div>

    <div class="deco-top">
      <div class="brand">YOPY</div>
    </div>

    <div class="deco-bot">
      <h2 class="headline">
        Where <em>families</em><br>come alive
      </h2>
      <p class="deco-sub">
        Share moments, nurture bonds, and build memories that last a lifetime — all in one place.
      </p>
      <div class="pills">
        <span class="pill">✦ Free forever</span>
        <span class="pill">♡ Family-first</span>
        <span class="pill">✿ Private &amp; safe</span>
      </div>
    </div>
  </div>


  <!-- ═══════════ RIGHT — FORM ═══════════ -->
  <div class="fp">
    <div class="fi">

      <div class="steps">
        <div class="sd active"></div>
        <div class="sd"></div>
        <div class="sd"></div>
      </div>

      <div class="eyebrow">New account</div>
      <h1 class="form-title">Let's get you <span>started</span></h1>
      <p class="form-sub">Takes less than a minute. No credit card needed.</p>

      <?php if($error): ?>
      <div class="flash flash-err">
        <span class="fi-ico">⚠</span>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>
      <?php if($success): ?>
      <div class="flash flash-ok">
        <span class="fi-ico">✓</span>
        <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= $safeBasePath ?>/auth/register" id="regForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="fields">

          <!-- Username -->
          <div class="field">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" placeholder="e.g. stargazer42"
                   value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required autocomplete="username" maxlength="50">
            <div class="fline"></div>
            <svg class="fic" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>

          <!-- Email -->
          <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" placeholder="you@example.com"
                   value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required autocomplete="email">
            <div class="fline"></div>
            <svg class="fic" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>

          <!-- PIN -->
          <div class="frow">
            <div class="field">
              <label for="pin">PIN</label>
              <input id="pin" type="password" name="pin" placeholder="4 digits"
                     required autocomplete="new-password" minlength="4" maxlength="4" pattern="\d{4}">
              <div class="fline"></div>
              <button type="button" class="eye-btn" id="eyePinBtn" aria-label="Toggle PIN">
                <svg id="eyePinIco" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="field">
              <label for="cpin">Confirm PIN</label>
              <input id="cpin" type="password" name="confirm_pin" placeholder="repeat"
                     required autocomplete="new-password" minlength="4" maxlength="4" pattern="\d{4}">
              <div class="fline"></div>
              <svg class="fic" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
              </svg>
            </div>
          </div>
          <!-- Password row -->
          <div class="frow">
            <div class="field">
              <label for="password">Password</label>
              <input id="password" type="password" name="password" placeholder="min 8 chars"
                     required autocomplete="new-password" minlength="8"
                     oninput="evalStrength(this.value)">
              <div class="fline"></div>
              <button type="button" class="eye-btn" id="eyeBtn" aria-label="Toggle password">
                <svg id="eyeIco" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="field">
              <label for="cpw">Confirm</label>
              <input id="cpw" type="password" name="confirm_password" placeholder="repeat"
                     required autocomplete="new-password">
              <div class="fline"></div>
              <svg class="fic" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
              </svg>
            </div>
          </div>

        </div><!-- /fields -->

        <!-- Strength -->
        <div class="strength">
          <div class="sbar" id="sb1"></div>
          <div class="sbar" id="sb2"></div>
          <div class="sbar" id="sb3"></div>
          <div class="sbar" id="sb4"></div>
          <span class="slbl" id="sLbl"></span>
        </div>

        <div class="sbtn-wrap">
          <button type="submit" class="sbtn">Create my account →</button>
        </div>

      </form>

      <p class="ffoot">
        Already have an account?
        <a href="<?= $safeBasePath ?>/auth/login" class="lnk">Sign in</a>
      </p>

    </div>
  </div>

</div><!-- /shell -->

<script>
/* ── CUSTOM CURSOR ── */
const cur  = document.getElementById('cur');
const ring = document.getElementById('cur-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY});
(function loop(){
  rx+=(mx-rx)*.13; ry+=(my-ry)*.13;
  cur.style.left  = mx+'px'; cur.style.top  = my+'px';
  ring.style.left = rx+'px'; ring.style.top = ry+'px';
  requestAnimationFrame(loop);
})();
document.querySelectorAll('a,button,input,select').forEach(el=>{
  el.addEventListener('mouseenter',()=>{ring.style.width='52px';ring.style.height='52px';ring.style.opacity='.75'});
  el.addEventListener('mouseleave',()=>{ring.style.width='32px';ring.style.height='32px';ring.style.opacity='.45'});
});

/* ── STEP DOTS ── */
const dots=[...document.querySelectorAll('.sd')];
let hi=0;
[['username',0],['email',0],['pin',1],['cpin',1],['password',2],['cpw',2]].forEach(([id,step])=>{
  const el=document.getElementById(id);
  if(!el)return;
  el.addEventListener('focus',()=>{
    if(step>hi)hi=step;
    dots.forEach((d,i)=>{
      d.className='sd'+(i<hi?' done':i===hi?' active':'');
    });
  });
});

/* ── EYE TOGGLE ── */
document.getElementById('eyeBtn').addEventListener('click',function(){
  const p=document.getElementById('password'),show=p.type==='password';
  p.type=show?'text':'password';
  document.getElementById('eyeIco').innerHTML=show
    ?`<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
      <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
      <line x1="1" y1="1" x2="23" y2="23"/>`
    :`<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
      <circle cx="12" cy="12" r="3"/>`;
});

/* Eye toggle for PIN fields */
document.getElementById('eyePinBtn').addEventListener('click',function(){
  const p=document.getElementById('pin'), show=p.type==='password';
  p.type=show?'text':'password';
  document.getElementById('eyePinIco').innerHTML= show
    ?`<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
       <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
       <line x1="1" y1="1" x2="23" y2="23"/>`
    :`<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
       <circle cx="12" cy="12" r="3"/>`;
});

/* ── STRENGTH ── */
function evalStrength(v){
  let s=0;
  if(v.length>=8)s++;
  if(/[A-Z]/.test(v))s++;
  if(/[0-9]/.test(v))s++;
  if(/[^A-Za-z0-9]/.test(v))s++;
  const labs=['','Weak','Fair','Good','Strong'];
  const cols=['','#ec4899','#ffb703','#22d3ee','#c4b5fd'];
  const cls =['','l1','l2','l3','l4'];
  for(let i=1;i<=4;i++)
    document.getElementById('sb'+i).className='sbar'+(i<=s?' '+cls[s]:'');
  const l=document.getElementById('sLbl');
  l.textContent=v.length?labs[s]:'';
  l.style.color=v.length?cols[s]:'rgba(255,255,255,.28)';
}

/* ── VALIDATE ── */
document.getElementById('regForm').addEventListener('submit',function(e){
  const pinVal = document.getElementById('pin').value;
  const cpinVal = document.getElementById('cpin').value;
  if(!/^\d{4}$/.test(pinVal)){e.preventDefault();alert('PIN must be exactly 4 digits.');return}
  if(pinVal!==cpinVal){e.preventDefault();alert('PINs do not match.');return}
  const pw=document.getElementById('password').value;
  const cp=document.getElementById('cpw').value;
  if(pw.length<8){e.preventDefault();alert('Password must be at least 8 characters.');return}
  if(pw!==cp){e.preventDefault();alert('Passwords do not match.')}
});

/* ── PAGE TRANSITIONS ── */
(function(){
  const veil  = document.getElementById('veil');
  const shell = document.querySelector('.shell');

  document.addEventListener('click', function(e){
    const a = e.target.closest('a[href]');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto') ||
        href.startsWith('http') || href.startsWith('//')) return;

    e.preventDefault();
    veil.classList.add('leaving');
    shell.classList.add('is-leaving');
    setTimeout(() => { window.location.href = href; }, 430);
  });
})();
</script>
</body>
</html>