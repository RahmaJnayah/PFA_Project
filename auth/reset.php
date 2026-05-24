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
$error = $_SESSION['reset_error'] ?? '';
unset($_SESSION['reset_error']);
$resetEmail = $resetEmail ?? '';
$selector = $selector ?? '';
$token = $token ?? '';
$isValid = $isValid ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password — YOPY</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;0,9..144,900;1,9..144,300&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --font-display:'Fraunces',serif;
  --font-body:'Outfit',sans-serif;
  --violet:#8b5cf6;
  --magenta:#ec4899;
  --cyan:#22d3ee;
  --ink:#040111;
  --ink-2:#0a0820;
  --line:rgba(255,255,255,.10);
  --white-soft:rgba(255,255,255,.55);
  --ease-out:cubic-bezier(.22,1,.36,1);
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:var(--font-body);background:var(--ink);color:#fff}
body{display:flex;align-items:center;justify-content:center;padding:24px}
.card{
  width:100%;max-width:440px;
  background:linear-gradient(160deg,rgba(91,33,182,.18),rgba(10,8,32,.95));
  border:1px solid rgba(139,92,246,.25);
  border-radius:20px;padding:32px 30px;
  box-shadow:0 24px 60px rgba(4,1,17,.6);
}
.h-eyebrow{font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#c4b5fd;margin-bottom:10px}
.h-title{font-family:var(--font-display);font-size:28px;font-weight:900;line-height:1.2;margin-bottom:6px}
.h-sub{color:var(--white-soft);font-size:13.5px;line-height:1.6;margin-bottom:22px}
.flash{display:flex;gap:8px;align-items:center;font-size:13px;padding:10px 12px;border-radius:12px;margin-bottom:16px}
.flash.error{background:rgba(208,0,0,.13);border:1px solid rgba(208,0,0,.38);color:#fca5a5}
.field{margin-bottom:18px}
.field label{display:block;font-size:10.5px;font-weight:600;letter-spacing:.13em;text-transform:uppercase;color:rgba(255,255,255,.45);margin-bottom:8px}
.field input{width:100%;background:transparent;border:none;border-bottom:1px solid var(--line);padding:8px 2px 10px;color:#fff;outline:none}
.btn{
  width:100%;padding:14px;border-radius:100px;border:none;
  font-family:var(--font-body);font-size:15px;font-weight:700;color:#fff;
  background:linear-gradient(135deg,#7c3aed,var(--violet),var(--magenta));
  box-shadow:0 4px 20px rgba(139,92,246,.35);
  transition:transform .2s var(--ease-out),box-shadow .2s var(--ease-out);
}
.btn:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(236,72,153,.45)}
.back{
  display:block;text-align:center;margin-top:18px;font-size:13px;
  color:rgba(255,255,255,.45);text-decoration:none
}
.back span{color:#c4b5fd}
</style>
</head>
<body>
  <div class="card">
    <div class="h-eyebrow">Password reset</div>
    <div class="h-title">Set a new password</div>
    <div class="h-sub">Choose a strong password you have not used before.</div>

    <?php if ($error): ?>
      <div class="flash error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($isValid): ?>
      <form method="POST" action="<?= $basePath ?>/auth/reset">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="email" value="<?= htmlspecialchars($resetEmail) ?>">
        <input type="hidden" name="selector" value="<?= htmlspecialchars($selector) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="field">
          <label for="password">New password</label>
          <input id="password" name="password" type="password" required autocomplete="new-password">
        </div>
        <div class="field">
          <label for="confirm_password">Confirm password</label>
          <input id="confirm_password" name="confirm_password" type="password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn">Update password</button>
      </form>
    <?php else: ?>
      <div class="h-sub">This reset link is not valid. You can request a new link below.</div>
      <a class="btn" href="<?= $basePath ?>/auth/forgot">Request a new link</a>
    <?php endif; ?>

    <a class="back" href="<?= $basePath ?>/auth/login">Back to <span>login</span></a>
  </div>
</body>
</html>
