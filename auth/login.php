
<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$email = trim((string) ($_SESSION['old_email'] ?? ''));
$error = (string) ($_SESSION['login_error'] ?? '');
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['old_email'], $_SESSION['login_error'], $_SESSION['login_success']);

// Only show splash if there is NO error and NO success message
$showSplash = empty($error) && empty($success);
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$basePath = $basePath ?? preg_replace('#/views/auth$#', '', $scriptDir);
if ($basePath === '/') {
  $basePath = '';
}
$safeBasePath = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
$csrfToken = htmlspecialchars((string) $_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — YOPY</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <style>
    :root {
      --lilac: #E6C7E6;
      --soft-violet: #A3779D;
      --royal: #663399;
      --plum: #2E1A47;
      --plum-mid: #3d2460;
      --plum-light: #4e2f7a;
      --white: #F9F0FF;
      --radius: 20px;
      --ease-out: cubic-bezier(0.33, 1, 0.68, 1);
      --lavender: #a78bfa;
      --violet: #8b5cf6;
      --violet-light: #c4b5fd;
      --magenta: #ec4899;
      --cyan: #22d3ee;
      --ink-2: #160a2a;
      --line: rgba(255, 255, 255, 0.1);
      --white-soft: rgba(255, 255, 255, 0.7);
      --font-display: 'Playfair Display', serif;
      --font-body: 'Nunito', sans-serif;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      background: #1c0f30;
      font-family: 'Nunito', sans-serif;
      color: var(--white);
      overflow-x: hidden;
    }

    /* Layer 0: Three.js deep background */
    #canvas-bg {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      pointer-events: none;
    }

    /* Layer 2: 2D overlay (aurora, particles, etc.) */
    #canvas-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 9999;
      pointer-events: none;
    }

    /* Page container */
    .page-wrap {
      position: relative;
      z-index: 10;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px 20px;
    }

    /* Find this section in your <style> tag and wrap it in PHP */
    <?php if ($showSplash): ?>.page-wrap {
      opacity: 0;
      animation: formReveal 0.7s cubic-bezier(.22, 1, .36, 1) 2.5s forwards;
    }

    <?php else: ?>.page-wrap {
      opacity: 1;
      
      /* Show immediately if there is an error */
    }

    <?php endif; ?>

    /* Card */
    .card {
      background: rgba(46, 26, 71, 0.88);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1.5px solid rgba(163, 119, 157, 0.35);
      border-radius: 32px 32px 32px 32px;
      height: auto;
      margin-top:0px;
      width: 100%;
      max-width: 640px;
      padding: 4px 52px 48px;
      box-shadow: 0 8px 48px rgba(0, 0, 0, 0.6), 0 0 80px rgba(102, 51, 153, 0.22);
      animation: cardIn 0.9s cubic-bezier(.22, 1, .36, 1) both;
    }

    @keyframes cardIn {
      from {
        opacity: 0;
        transform: translateY(40px) scale(0.97);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    /* Logo area (kept from new design) */

    .logo-area {
      text-align: center;
      margin-bottom: 14px;
    }

    .logo-svg-wrap {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      margin-bottom: 4px;
    }

    .logo-icon-svg {
      width: 170px;
      height: 120px;
      filter: drop-shadow(0 6px 18px rgba(102, 51, 153, 0.55));
      animation: mascotBob 3.5s ease-in-out infinite;
      flex-shrink: 0;
    }

    @keyframes mascotBob {

      0%,
      100% {
        transform: translateY(0) rotate(-2deg);
      }

      50% {
        transform: translateY(-8px) rotate(2deg);
      }
    }

    .logo-text-svg {
      width: 160px;
      height: 58px;
      filter: drop-shadow(0 3px 10px rgba(102, 51, 153, 0.45));
    }

    .yopy-acronym {
      font-size: .78rem;
      font-weight: 600;
      letter-spacing: 2px;
      color: rgba(230, 199, 230, 0.6);
      margin-top: 2px;
      margin-bottom: 4px;
    }

    .acr-letter {
      color: var(--lilac);
      font-weight: 900;
      font-size: .9rem;
    }

    .logo-area p {
      font-size: .88rem;
      color: rgba(230, 199, 230, 0.48);
      margin-top: 4px;
      font-weight: 500;
      letter-spacing: .5px;
      font-style: italic;
    }

    /* Form fields (adapted from new design) */
    .field {
      margin-bottom: 18px;
    }

    .field label {
      display: block;
      font-size: .8rem;
      font-weight: 700;
      color: var(--lilac);
      margin-bottom: 7px;
      letter-spacing: .5px;
    }

    .field input {
      width: 100%;
      background: rgba(46, 26, 71, 0.8);
      border: 1.5px solid rgba(163, 119, 157, 0.38);
      border-radius: 12px;
      padding: 13px 16px;
      color: var(--white);
      font-family: 'Nunito', sans-serif;
      font-size: .95rem;
      font-weight: 600;
      outline: none;
      transition: border-color .25s, background .25s, box-shadow .25s;
    }

    .field input:focus {
      border-color: var(--lilac);
      background: rgba(61, 36, 96, 0.92);
      box-shadow: 0 0 0 3px rgba(230, 199, 230, 0.16), 0 0 22px rgba(102, 51, 153, 0.18);
    }

    .field input::placeholder {
      color: rgba(230, 199, 230, 0.28);
      font-weight: 400;
    }

    /* Eye toggle (kept from original login.php) */
    .eye-btn {
      position: absolute;
      right: 16px;
      top: 13px;
      background: none;
      border: none;
      padding: 5px;
      color: rgba(230, 199, 230, 0.58);
      cursor: pointer;
      font-size: 1.3rem;
      display: flex;
      align-items: center;
      transition: color .25s;
    }

    .eye-btn:hover {
      color: var(--lilac);
    }

    /* Submit button (styled like new design) */
    .submit-btn {
      width: 100%;
      margin-top: 32px;
      padding: 17px;
      border-radius: 14px;
      border: none;
      background: linear-gradient(135deg, #663399, #A3779D, #E6C7E6, #A3779D, #663399);
      background-size: 300% 300%;
      color: var(--plum);
      font-family: 'Nunito', sans-serif;
      font-size: 1.08rem;
      font-weight: 900;
      letter-spacing: .8px;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: transform .2s, box-shadow .2s;
      animation: gradShift 5s ease infinite;
      box-shadow: 0 4px 22px rgba(102, 51, 153, 0.5);
    }

    @keyframes gradShift {
      0% {
        background-position: 0% 50%;
      }

      50% {
        background-position: 100% 50%;
      }

      100% {
        background-position: 0% 50%;
      }
    }

    .submit-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 36px rgba(102, 51, 153, 0.58), 0 0 40px rgba(230, 199, 230, 0.18);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    .submit-btn .btn-shine {
      position: absolute;
      top: 0;
      left: -75%;
      width: 50%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.28), transparent);
      animation: shine 3s infinite;
    }

    @keyframes shine {
      0% {
        left: -75%;
      }

      50%,
      100% {
        left: 125%;
      }
    }

    /* Error / Success messages (adapted from new design + original) */
    .toast {
      position: relative;
      margin-bottom: 22px;
      background: rgba(30, 10, 55, 0.97);
      border: 1.5px solid var(--royal);
      backdrop-filter: blur(16px);
      border-radius: 14px;
      padding: 14px 24px;
      font-size: .9rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
      opacity: 1;
      min-width: 280px;
      text-align: center;
      justify-content: center;
      color: var(--lilac);
    }

    .toast.error {
      border-color: #f87171;
      color: #f87171;
    }

    .toast.success {
      border-color: var(--lilac);
      color: var(--lilac);
    }

    /* Login link row (kept + styled for new design) */
    .login-link {
      text-align: center;
      margin-top: 20px;
      font-size: .85rem;
      color: rgba(230, 199, 230, 0.48);
    }

    .login-link a {
      color: var(--lilac);
      text-decoration: none;
      font-weight: 800;
    }

    .login-link a:hover {
      text-decoration: underline;
      color: #fff;
    }

    /* Splash animation (kept exactly from new design) */
    #splash {
      position: fixed;
      inset: 0;
      z-index: 999999;
      background: #1c0f30;
      display: flex;
      align-items: center;
      justify-content: center;
      pointer-events: none;
    }

    #splash-logo {
      width: min(85vw, 520px);
      height: auto;
      transform-origin: center center;
      animation:
        splashIn 0.7s cubic-bezier(.22, 1, .36, 1) 0.1s both,
        splashZoom 0.85s cubic-bezier(.55, .06, .68, .19) 1.5s forwards;
      filter: drop-shadow(0 0 60px rgba(102, 51, 153, 0.9));
    }

    @keyframes splashIn {
      from {
        opacity: 0;
        transform: scale(0.3);
      }

      to {
        opacity: 1;
        transform: scale(1.0);
      }
    }

    @keyframes splashZoom {
      from {
        opacity: 1;
        transform: scale(1.0);
      }

      to {
        opacity: 0;
        transform: scale(0.08) translate(0px, -200px);
      }
    }

    #splash.hidden {
      display: none;
    }

    /* Form reveal after splash */

    @keyframes formReveal {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Update this section in your <style> block */
    .chars-row {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 45px;
      /* Adjust spacing between mascots */
      margin-bottom: 24px;
      flex-wrap: nowrap;
      /* Forces them to stay in one line */
    }

    /* Optional: Shrink images slightly if they are too wide for the card */
    .chars-row .logo-icon-svg {
      width: 60px;
      height: auto;
    }

    .brand {
      font-family: var(--font-display);
      font-size: 22px;
      font-weight: 900;
      letter-spacing: .22em;
      text-transform: uppercase;
      background: linear-gradient(90deg, var(--lavender), var(--cyan));
      background-size: 200% auto;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: shine 5s linear infinite;
    }

    @keyframes shine {
      to {
        background-position: 260% center
      }
    }

    /* ── Bottom headline ── */
    .orb-bot {
      position: relative;
      z-index: 10;
    }

    .orb-headline {
      font-family: var(--font-display);
      font-size: clamp(30px, 3.2vw, 46px);
      font-weight: 900;
      line-height: 1.14;
      color: #fff;
      margin-bottom: 14px;
      opacity: 0;
      animation: fadeUp .8s var(--ease-out) .25s forwards;
    }

    .orb-headline em {
      font-style: italic;
      background: linear-gradient(130deg, var(--violet-light), var(--magenta));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .orb-sub {
      font-size: 13.5px;
      font-weight: 300;
      line-height: 1.75;
      color: var(--white-soft);
      max-width: 295px;
      opacity: 0;
      animation: fadeUp .8s var(--ease-out) .40s forwards;
    }

    .orb-pills {
      display: flex;
      gap: 8px;
      margin-top: 22px;
      flex-wrap: wrap;
      opacity: 0;
      animation: fadeUp .8s var(--ease-out) .55s forwards;
    }

    .orb-pill {
      font-size: 11px;
      font-weight: 500;
      letter-spacing: .05em;
      padding: 5px 13px;
      border-radius: 99px;
      border: 1px solid rgba(167, 139, 250, .25);
      color: var(--lavender);
      background: rgba(139, 92, 246, .08);
    }

    /* ──────────────────────────────────────
    RIGHT — FORM PANEL
    ────────────────────────────────────── */
    .fp {
      background:
        radial-gradient(ellipse 70% 55% at 20% 20%, rgba(91, 33, 182, .28) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 80% 85%, rgba(236, 72, 153, .16) 0%, transparent 55%),
        var(--ink-2);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 48px 8% 48px 7%;
      overflow-y: auto;
      scrollbar-width: none;
      position: relative;
      /* stacking context for form content */
    }

    .fp::-webkit-scrollbar {
      display: none
    }

    /* Whole inner form fades in as one reliable unit */
    .fi {
      max-width: 390px;
      width: 100%;
      opacity: 0;
      animation: fadeUp .75s var(--ease-out) .15s both;
    }

    /* ── Form header ── */
    .eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .16em;
      text-transform: uppercase;
      color: var(--lavender);
      margin-bottom: 10px;
    }

    .form-title {
      font-family: var(--font-display);
      font-size: clamp(26px, 2.8vw, 38px);
      font-weight: 900;
      line-height: 1.18;
      color: #fff;
      margin-bottom: 6px;
    }

    .form-title span {
      font-style: italic;
      background: linear-gradient(130deg, var(--violet-light), var(--magenta), var(--gold));
      background-size: 260% auto;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: shine 5s linear infinite;
    }

    .form-sub {
      font-size: 13.5px;
      font-weight: 300;
      color: var(--white-soft);
      margin-bottom: 36px;
    }

    /* ── Error box ── */
    .error-box {
      background: rgba(208, 0, 0, .13);
      border: 1px solid rgba(208, 0, 0, .38);
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 13px;
      color: #fca5a5;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .error-box::before {
      content: '⚠'
    }

    /* ── Fields — underline style matching register ── */
    .fields {
      display: flex;
      flex-direction: column
    }

    .field {
      position: relative;
      margin-bottom: 28px;
    }

    .field:nth-child(1) {
      animation: fieldSlide .5s var(--ease-out) .35s both
    }

    .field:nth-child(2) {
      animation: fieldSlide .5s var(--ease-out) .45s both
    }

    @keyframes fieldSlide {
      from {
        opacity: 0;
        transform: translateX(-10px)
      }

      to {
        opacity: 1;
        transform: translateX(0)
      }
    }

    .field label {
      display: block;
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: .13em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, .35);
      margin-bottom: 9px;
      transition: color .3s;
    }

    .field:focus-within label {
      color: var(--lavender)
    }

    .field input {
      width: 100%;
      background: transparent;
      border: none;
      border-bottom: 1px solid var(--line);
      padding: 6px 40px 12px 2px;
      font-family: var(--font-body);
      font-size: 15px;
      font-weight: 400;
      color: #fff;
      outline: none;
      transition: border-color .3s;
    }

    .field input::placeholder {
      color: rgba(255, 255, 255, .15)
    }

    /* animated underline sweep */
    .fline {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, var(--violet), var(--magenta), var(--cyan));
      transform: scaleX(0);
      transform-origin: left;
      transition: transform .4s var(--ease-out);
      pointer-events: none;
    }

    .field:focus-within .fline {
      transform: scaleX(1)
    }

    /* field icons */
    .fic {
      position: absolute;
      right: 4px;
      bottom: 13px;
      color: rgba(255, 255, 255, .18);
      pointer-events: none;
      transition: color .3s;
    }

    .field:focus-within .fic {
      color: var(--lavender)
    }

    /* eye toggle */
    .eye-btn {
      position: absolute;
      right: 0;
      bottom: 7px;
      background: none;
      border: none;
      padding: 5px;
      color: rgba(255, 255, 255, .22);
      display: flex;
      align-items: center;
      transition: color .25s;
    }

    .eye-btn:hover {
      color: var(--lavender)
    }

    /* ── Forgot link row ── */
    .forgot-row {
      display: flex;
      justify-content: flex-end;
      margin-top: -16px;
      margin-bottom: 28px;
    }

    .forgot-link {
      font-size: 12.5px;
      font-weight: 600;
      background: linear-gradient(90deg, var(--cyan), var(--violet-light));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      position: relative;
      transition: filter .25s;
    }

    .forgot-link::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, var(--cyan), var(--violet-light));
      transform: scaleX(0);
      transform-origin: left;
      transition: transform .3s;
    }

    .forgot-link:hover::after {
      transform: scaleX(1)
    }

    .forgot-link:hover {
      filter: brightness(1.3)
    }

    /* ── Submit ── */
    .sbtn-wrap {
      margin-top: 4px;
    }
    .char-wrapper {
      position: relative;
      display: inline-block;
    }

    .sbtn {
      width: 100%;
      padding: 17px;
      border-radius: 100px;
      border: none;
      font-family: var(--font-body);
      font-size: 15.5px;
      font-weight: 700;
      color: #fff;
      letter-spacing: .02em;
      background: linear-gradient(135deg, #7c3aed, var(--violet), var(--magenta));
      background-size: 200% auto;
      box-shadow: 0 4px 20px rgba(139, 92, 246, .35);
      transition: background-position .4s, transform .25s var(--ease-out), box-shadow .25s;
      position: relative;
      overflow: hidden;
    }

    .sbtn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255, 255, 255, .14) 0%, transparent 55%);
    }

    .sbtn:hover {
      background-position: right center;
      transform: translateY(-3px);
      box-shadow: 0 12px 36px rgba(236, 72, 153, .50);
    }

    .sbtn:active {
      transform: translateY(0)
    }

    /* ── Divider ── */
    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 24px 0 18px;
    }

    .div-line {
      flex: 1;
      height: 1px;
      background: rgba(255, 255, 255, .07)
    }

    .div-text {
      font-size: 11px;
      color: rgba(255, 255, 255, .28);
      letter-spacing: .08em
    }

    /* ── Footer link ── */
    .ffoot {
      text-align: center;
      font-size: 14px;
      color: rgba(255, 255, 255, .38);
    }

    .signup-link {
      font-weight: 700;
      background: linear-gradient(90deg, #a78bfa, #ec4899, #ffb703);
      background-size: 200% auto;
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      animation: shine 4s linear infinite;
      position: relative;
      transition: filter .25s;
    }

    .signup-link::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      right: 0;
      height: 1.5px;
      background: linear-gradient(90deg, #a78bfa, #ec4899, #ffb703);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform .35s;
    }

    .signup-link:hover::after {
      transform: scaleX(1)
    }

    .signup-link:hover {
      filter: brightness(1.2)
    }

    /* ──────────────────────────────────────
   PAGE TRANSITION VEIL
    ────────────────────────────────────── */
    #veil {
      position: fixed;
      inset: 0;
      z-index: 9000;
      pointer-events: none;
      background: linear-gradient(135deg, #040111 0%, #0d0424 50%, #040111 100%);
      animation: veilReveal .65s var(--ease-out) forwards;
    }

    @keyframes veilReveal {
      0% {
        opacity: 1;
        transform: translateY(0) scale(1)
      }

      100% {
        opacity: 0;
        transform: translateY(-12px) scale(1.012)
      }
    }

    #veil.leaving {
      pointer-events: all;
      animation: veilCover .45s cubic-bezier(.55, 0, 1, .45) forwards;
    }

    @keyframes veilCover {
      0% {
        opacity: 0;
        transform: translateY(12px) scale(1.012)
      }

      100% {
        opacity: 1;
        transform: translateY(0) scale(1)
      }
    }

    .shell.is-leaving {
      animation: shellExit .45s cubic-bezier(.55, 0, 1, .45) forwards;
    }

    @keyframes shellExit {
      to {
        opacity: 0;
        transform: scale(.97) translateY(-8px)
      }
    }

    /* shared entrance keyframes */
    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(16px)
      }

      to {
        opacity: 1;
        transform: translateY(0)
      }
    }

    @keyframes fieldSlide {
      from {
        opacity: 0;
        transform: translateX(-10px)
      }

      to {
        opacity: 1;
        transform: translateX(0)
      }
    }


    /* ──────────────────────────────────────
    RESPONSIVE
    ────────────────────────────────────── */
    @media(max-width:860px) {
      .shell {
        grid-template-columns: 1fr
      }

      html,
      body {
        overflow: auto
      }

      .orb-panel {
        height: 260px;
        padding: 32px;
        justify-content: flex-end
      }

      #canvas-container {
        opacity: .6
      }

      .orb-headline {
        font-size: 26px
      }

      .orb-sub,
      .orb-pills {
        display: none
      }

      .fp {
        padding: 36px 24px 52px
      }

      .fi {
        max-width: 100%
      }
    }

    @media(max-width:480px) {
      .orb-panel {
        height: 190px
      }

      #canvas-container {
        display: none
      }
    }

    .logo-text-svg {
      width: 200px;
      height: auto;

    }
  </style>
</head>

<body>

  <!-- Canvas backgrounds (required by new design scripts) -->
  <canvas id="canvas-bg"></canvas>
  <canvas id="canvas-overlay"></canvas>

  <!-- Splash screen with original base64 logo (image kept exactly) -->
  <?php if ($showSplash): ?>
    <div id="splash">
      <img id="splash-logo" src="./../public/images/logo_with_character-removebg-preview.png" alt="YOPY Logo">
    </div>
  <?php endif; ?>
  <div class="page-wrap">
 <div class="card">
  <div class="chars-row">
    <div class="char-wrap"><img src="./../public/images/langer.png" alt="Mascot" class="logo-icon-svg"></div>
    <div class="char-wrap"><img src="./../public/images/cry.png" alt="Mascot" class="logo-icon-svg"></div>
    <div class="char-wrap"><img src="./../public/images/joy.png" alt="Mascot" class="logo-icon-svg"></div>
    <div class="char-wrap"><img src="./../public/images/lemo.png" alt="Mascot" class="logo-icon-svg"></div>
    <div class="char-wrap"><img src="./../public/images/lilo.png" alt="Mascot" class="logo-icon-svg"></div>
    <div class="char-wrap"><img src="./../public/images/rita.png" alt="Mascot" class="logo-icon-svg"></div>
  </div>
    <div class="logo-area">
      <div class="logo-svg-wrap">
        <img src="./../public/images/logo_with_character-removebg-preview.png" alt="YOPY Logo" class="logo-icon-svg">
        
      </div>
                <?php if ($error): ?>
                <div class="toast error">⚠ <?= htmlspecialchars($error) ?></div>
              <?php endif; ?>
              <?php if ($success): ?>
                <div class="toast success">✔ <?= htmlspecialchars($success) ?></div>
              <?php endif; ?>

              <form method="POST" action="<?= $safeBasePath ?>/auth/login" id="loginForm" novalidate>

                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <!-- Email field -->
                <div class="field">
                  <label for="email">Email address</label>
                  <input id="email" type="email" name="email" placeholder="you@example.com"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email" maxlength="254">
                </div>

                <!-- Password field with eye toggle (original functionality kept) -->
                <div class="field" style="position:relative;">
                  <label for="password">Password</label>
                  <input id="password" type="password" name="password" placeholder="••••••••"
                    required autocomplete="current-password">
                  <button type="button" class="eye-btn" id="eyeBtn" aria-label="Toggle password">
                    👁
                  </button>
                </div>

                <!-- Forgot password (untouched, exactly as in original login.php) -->
                <div style="text-align:right; margin: -8px 0 24px 0;">
                  <a href="<?= $safeBasePath ?>/auth/forgot" style="font-size:.85rem; font-weight:800; color:var(--lilac); text-decoration:none;">Forgot password?</a>
                </div>

                <!-- Submit -->
                <button type="submit" class="submit-btn" id="submitBtn">
                  Sign In →
                  <span class="btn-shine"></span>
                </button>

              </form>

              <!-- Divider + Register link (kept from original) -->
              <div class="login-link">
                NEW HERE?&nbsp;&nbsp;
                <a href="<?= $safeBasePath ?>/auth/register">Create your account ✦</a>
              </div>

            </div>
        </div>

        <script>
          /* ── THREE.JS BACKGROUND LAYER 0 (exactly from new design) ── */
          (function() {
            const cv = document.getElementById('canvas-bg');
            const renderer = new THREE.WebGLRenderer({
              canvas: cv,
              alpha: false,
              antialias: true
            });
            renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
            renderer.setSize(innerWidth, innerHeight);
            renderer.setClearColor(0x1c0f30, 1);

            const scene = new THREE.Scene();
            const cam = new THREE.PerspectiveCamera(70, innerWidth / innerHeight, 0.1, 200);
            cam.position.z = 30;

            const pal = [0xE6C7E6, 0xA3779D, 0x663399, 0x4a1f7a, 0xd4a8d4, 0x9944cc, 0xc490c4, 0xbb66ee, 0x7722bb];

            const orbs = [];
            for (let i = 0; i < 42; i++) {
              const r = Math.random() * 1.7 + 0.35;
              const col = pal[i % pal.length];
              const mat = new THREE.MeshStandardMaterial({
                color: col,
                emissive: col,
                emissiveIntensity: 0.55 + Math.random() * 0.35,
                transparent: true,
                opacity: Math.random() * 0.55 + 0.18,
                roughness: 0.1,
                metalness: 0.25
              });
              const m = new THREE.Mesh(new THREE.SphereGeometry(r, 32, 32), mat);
              m.position.set((Math.random() - .5) * 115, (Math.random() - .5) * 75, (Math.random() - .5) * 50 - 8);
              m.userData = {
                sp: Math.random() * .009 + .003,
                ph: Math.random() * Math.PI * 2,
                rs: (Math.random() - .5) * .013,
                bo: mat.opacity,
                bp: Math.random() * Math.PI * 2
              };
              scene.add(m);
              orbs.push(m);
            }

            const sp = new Float32Array(600 * 3);
            for (let i = 0; i < 600 * 3; i++) sp[i] = (Math.random() - .5) * 250;
            const sg = new THREE.BufferGeometry();
            sg.setAttribute('position', new THREE.BufferAttribute(sp, 3));
            scene.add(new THREE.Points(sg, new THREE.PointsMaterial({
              color: 0xE6C7E6,
              size: .22,
              transparent: true,
              opacity: .42
            })));

            const bp2 = new Float32Array(120 * 3);
            for (let i = 0; i < 120 * 3; i++) bp2[i] = (Math.random() - .5) * 190;
            const bg2 = new THREE.BufferGeometry();
            bg2.setAttribute('position', new THREE.BufferAttribute(bp2, 3));
            scene.add(new THREE.Points(bg2, new THREE.PointsMaterial({
              color: 0xffffff,
              size: .36,
              transparent: true,
              opacity: .62
            })));

            scene.add(new THREE.AmbientLight(0x6633aa, .85));
            const pl1 = new THREE.PointLight(0xaa44ff, 4.5, 115);
            pl1.position.set(-25, 25, 15);
            scene.add(pl1);
            const pl2 = new THREE.PointLight(0xE6C7E6, 2.8, 90);
            pl2.position.set(25, -18, 12);
            scene.add(pl2);
            const pl3 = new THREE.PointLight(0x9933ff, 2.5, 75);
            pl3.position.set(0, 10, 22);
            scene.add(pl3);
            const pl4 = new THREE.PointLight(0xff88ff, 2.0, 65);
            pl4.position.set(15, 20, -10);
            scene.add(pl4);

            const rings = [];
            for (let i = 0; i < 9; i++) {
              const col = pal[i % pal.length];
              const mat = new THREE.MeshStandardMaterial({
                color: col,
                emissive: col,
                emissiveIntensity: .78,
                transparent: true,
                opacity: .22
              });
              const m = new THREE.Mesh(new THREE.TorusGeometry(Math.random() * 4 + 2, .13, 16, 80), mat);
              m.position.set((Math.random() - .5) * 80, (Math.random() - .5) * 55, (Math.random() - .5) * 28 - 18);
              m.rotation.set(Math.random() * Math.PI, Math.random() * Math.PI, 0);
              m.userData = {
                rx: (Math.random() - .5) * .006,
                ry: (Math.random() - .5) * .006
              };
              scene.add(m);
              rings.push(m);
            }

            let t = 0,
              mx = 0,
              my = 0;
            document.addEventListener('mousemove', e => {
              mx = (e.clientX / innerWidth - .5) * 2;
              my = (e.clientY / innerHeight - .5) * 2;
            });

            (function loop() {
              requestAnimationFrame(loop);
              t += .012;
              orbs.forEach(o => {
                o.position.y += Math.sin(t * o.userData.sp * 80 + o.userData.ph) * .014;
                o.position.x += Math.cos(t * o.userData.sp * 60 + o.userData.ph) * .006;
                o.rotation.x += o.userData.rs;
                o.rotation.z += o.userData.rs * .7;
                o.material.opacity = o.userData.bo + Math.sin(t * 1.3 + o.userData.bp) * .09;
                o.material.emissiveIntensity = .55 + Math.sin(t * 1.6 + o.userData.bp) * .22;
              });
              rings.forEach(r => {
                r.rotation.x += r.userData.rx;
                r.rotation.y += r.userData.ry;
              });
              cam.position.x += (mx * 5 - cam.position.x) * .025;
              cam.position.y += (-my * 3.5 - cam.position.y) * .025;
              cam.lookAt(scene.position);
              pl1.position.x = Math.sin(t * .5) * 30;
              pl1.position.y = Math.cos(t * .4) * 22;
              pl2.position.x = Math.cos(t * .35) * 26;
              pl2.position.y = Math.sin(t * .45) * 18;
              pl4.position.x = Math.sin(t * .28 + 1) * 22;
              pl4.position.z = Math.cos(t * .3) * 14;
              renderer.render(scene, cam);
            })();

            window.addEventListener('resize', () => {
              cam.aspect = innerWidth / innerHeight;
              cam.updateProjectionMatrix();
              renderer.setSize(innerWidth, innerHeight);
            });
          })();

          /* ── 2D OVERLAY LAYER (aurora, particles, shooting stars - kept from new design) ── */
          (function() {
            const cv = document.getElementById('canvas-overlay');
            const ctx = cv.getContext('2d');
            let W, H;

            function resize() {
              W = cv.width = innerWidth;
              H = cv.height = innerHeight;
            }
            resize();
            window.addEventListener('resize', resize);

            const COLS = ['#E6C7E6', '#A3779D', '#c084fc', '#d4a8d4', '#9944cc', '#e9d5ff', '#ffffff', '#bb99dd'];

            const pts = [];
            for (let i = 0; i < 140; i++) {
              pts.push({
                x: Math.random() * innerWidth,
                y: Math.random() * innerHeight,
                r: Math.random() * 2.4 + 0.4,
                col: COLS[i % COLS.length],
                vx: (Math.random() - .5) * .22,
                vy: -(Math.random() * .5 + .14),
                a: Math.random() * .5 + .12,
                wo: Math.random() * Math.PI * 2,
                ws: Math.random() * .022 + .007,
                pu: Math.random() * Math.PI * 2,
                ps: Math.random() * .028 + .01
              });
            }

            const auroras = [{
                yf: .08,
                amp: 35,
                freq: .0052,
                sp: .0055,
                col: [102, 51, 153],
                a: .10,
                th: 70,
                ph: 0.0
              },
              {
                yf: .22,
                amp: 28,
                freq: .0068,
                sp: .0080,
                col: [163, 119, 157],
                a: .08,
                th: 52,
                ph: 2.1
              },
              {
                yf: .40,
                amp: 42,
                freq: .0045,
                sp: .0048,
                col: [102, 51, 153],
                a: .09,
                th: 65,
                ph: 1.3
              },
              {
                yf: .55,
                amp: 25,
                freq: .0072,
                sp: .0095,
                col: [196, 144, 196],
                a: .07,
                th: 44,
                ph: 3.7
              },
              {
                yf: .70,
                amp: 38,
                freq: .0058,
                sp: .0060,
                col: [163, 119, 157],
                a: .08,
                th: 58,
                ph: 0.9
              },
              {
                yf: .85,
                amp: 22,
                freq: .0080,
                sp: .0110,
                col: [230, 199, 230],
                a: .06,
                th: 36,
                ph: 2.5
              },
              {
                yf: .95,
                amp: 18,
                freq: .0090,
                sp: .0070,
                col: [102, 51, 153],
                a: .05,
                th: 28,
                ph: 1.8
              },
            ];

            const shoots = [];
            let lastShoot = -9999;

            function spawnShoot(ts) {
              if (ts - lastShoot < 1800) return;
              const count = Math.random() > .5 ? 2 : 1;
              for (let c = 0; c < count; c++) {
                const fromTop = Math.random() > .4;
                const sx = fromTop ? Math.random() * W : (Math.random() > .5 ? -20 : W + 20);
                const sy = fromTop ? -20 : Math.random() * H * .5;
                const ang = Math.PI * .22 + (Math.random() - .5) * .35;
                const spd = Math.random() * 16 + 10;
                shoots.push({
                  x: sx,
                  y: sy,
                  vx: Math.cos(ang) * spd * (sx < 0 ? 1 : -1 || 1),
                  vy: Math.sin(ang) * spd,
                  len: Math.random() * 140 + 70,
                  life: 1,
                  decay: Math.random() * .022 + .016,
                  col: COLS[Math.floor(Math.random() * 4)]
                });
              }
              lastShoot = ts;
            }

            const sparks = [];
            for (let i = 0; i < 50; i++) {
              sparks.push({
                x: Math.random() * innerWidth,
                y: Math.random() * innerHeight,
                sz: Math.random() * 3.5 + 1,
                col: COLS[i % COLS.length],
                a: Math.random(),
                ad: Math.random() > .5 ? 1 : -1,
                as: Math.random() * .018 + .006
              });
            }

            function star4(x, y, r, col, a) {
              ctx.save();
              ctx.globalAlpha = a;
              ctx.fillStyle = col;
              ctx.beginPath();
              for (let i = 0; i < 8; i++) {
                const ang = i / 8 * Math.PI * 2,
                  rr = i % 2 === 0 ? r : r * .35;
                i === 0 ? ctx.moveTo(x + Math.cos(ang) * rr, y + Math.sin(ang) * rr) : ctx.lineTo(x + Math.cos(ang) * rr, y + Math.sin(ang) * rr);
              }
              ctx.closePath();
              ctx.fill();
              ctx.restore();
            }

            function hexRGB(h) {
              const n = parseInt(h.replace('#', ''), 16);
              return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
            }

            let t = 0;

            function loop(ts) {
              requestAnimationFrame(loop);
              ctx.clearRect(0, 0, W, H);
              t += .014;

              auroras.forEach(a => {
                a.ph += a.sp;
                const cy = H * a.yf;
                const grd = ctx.createLinearGradient(0, cy - a.th, 0, cy + a.th);
                const [r, g, b] = a.col;
                grd.addColorStop(0, `rgba(${r},${g},${b},0)`);
                grd.addColorStop(0.5, `rgba(${r},${g},${b},${a.a})`);
                grd.addColorStop(1, `rgba(${r},${g},${b},0)`);
                ctx.beginPath();
                ctx.moveTo(0, cy + Math.sin(a.ph) * a.amp);
                for (let x = 0; x <= W; x += 5) {
                  const yy = cy + Math.sin(x * a.freq + a.ph) * a.amp + Math.sin(x * a.freq * .65 + a.ph * 1.4) * a.amp * .38;
                  ctx.lineTo(x, yy);
                }
                ctx.lineTo(W, cy + a.th * 2.5);
                ctx.lineTo(0, cy + a.th * 2.5);
                ctx.closePath();
                ctx.fillStyle = grd;
                ctx.fill();
              });

              pts.forEach(p => {
                p.wo += p.ws;
                p.pu += p.ps;
                p.x += p.vx + Math.sin(p.wo) * .28;
                p.y += p.vy;
                if (p.y < -12) {
                  p.y = H + 12;
                  p.x = Math.random() * W;
                }
                if (p.x < -12) p.x = W + 12;
                if (p.x > W + 12) p.x = -12;
                const al = p.a * (.72 + Math.sin(p.pu) * .28);
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r * (1 + Math.sin(p.pu) * .18), 0, Math.PI * 2);
                ctx.fillStyle = p.col;
                ctx.globalAlpha = al;
                ctx.fill();
                ctx.globalAlpha = 1;
              });

              spawnShoot(ts);
              for (let i = shoots.length - 1; i >= 0; i--) {
                const s = shoots[i];
                s.x += s.vx;
                s.y += s.vy;
                s.life -= s.decay;
                if (s.life <= 0) {
                  shoots.splice(i, 1);
                  continue;
                }
                const steps = s.len / Math.sqrt(s.vx * s.vx + s.vy * s.vy);
                const tx = s.x - s.vx * steps,
                  ty = s.y - s.vy * steps;
                const grd = ctx.createLinearGradient(s.x, s.y, tx, ty);
                grd.addColorStop(0, `rgba(255,255,255,${(s.life*.95).toFixed(2)})`);
                const [r, g, b] = hexRGB(s.col);
                grd.addColorStop(0.4, `rgba(${r},${g},${b},${(s.life*.6).toFixed(2)})`);
                grd.addColorStop(1, 'rgba(102,51,153,0)');
                ctx.save();
                ctx.strokeStyle = grd;
                ctx.lineWidth = 2.8 * s.life;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(s.x, s.y);
                ctx.lineTo(tx, ty);
                ctx.stroke();
                ctx.beginPath();
                ctx.arc(s.x, s.y, 3.5 * s.life, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255,255,255,${(s.life*.85).toFixed(2)})`;
                ctx.fill();
                ctx.restore();
              }

              sparks.forEach(s => {
                s.a += s.ad * s.as;
                if (s.a >= .88 || s.a <= .04) s.ad *= -1;
                star4(s.x, s.y, s.sz, s.col, s.a);
              });
            }
            requestAnimationFrame(loop);
          })();

          /* ── Eye toggle (kept exactly from original login.php) ── */
          document.getElementById('eyeBtn').addEventListener('click', function() {
            const p = document.getElementById('password');
            const show = p.type === 'password';
            p.type = show ? 'text' : 'password';
            this.textContent = show ? '🙈' : '👁';
          });

          /* ── Splash auto-hide (already handled by CSS animation) ── */
          setTimeout(() => {
            const splash = document.getElementById('splash');
            splash.classList.add('hidden');
          }, 3200);
        </script>
</body>

</html>