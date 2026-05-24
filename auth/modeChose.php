<?php

session_start();

// ── AUTH GUARD ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
  header('Location: ../../auth/login.php');
  exit;
}

// ── DB CONFIG ───────────────────────────────────────────────────
// Adjust the path below to wherever database.php lives relative to this file
require_once __DIR__ . '/../../config/database.php';


function getDB(): PDO
{
  return Database::connect();
}

function modeChoseThrottleConfig(): array
{
  return [
    'max_attempts' => 5,
    'window_seconds' => 300,
    'lock_seconds' => 900,
  ];
}

function modeChoseThrottleKey(string $action): string
{
  return 'mode_chose_throttle_' . $action;
}

function modeChoseThrottleState(string $action): array
{
  $key = modeChoseThrottleKey($action);
  $state = $_SESSION[$key] ?? [];
  return is_array($state) ? $state : [];
}

function modeChoseThrottleBlocked(string $action): bool
{
  $state = modeChoseThrottleState($action);
  $lockedUntil = (int) ($state['locked_until'] ?? 0);
  return $lockedUntil > time();
}

function modeChoseThrottleMessage(string $action): ?string
{
  $state = modeChoseThrottleState($action);
  $lockedUntil = (int) ($state['locked_until'] ?? 0);
  if ($lockedUntil > time()) {
    return 'Too many attempts. Please wait before trying again.';
  }
  return null;
}

function modeChoseThrottleFailure(string $action): void
{
  $config = modeChoseThrottleConfig();
  $state = modeChoseThrottleState($action);
  $now = time();
  $windowStart = (int) ($state['window_start'] ?? 0);
  $attempts = (int) ($state['attempts'] ?? 0);

  if ($windowStart === 0 || ($now - $windowStart) > $config['window_seconds']) {
    $windowStart = $now;
    $attempts = 0;
  }

  $attempts++;
  $state = [
    'window_start' => $windowStart,
    'attempts' => $attempts,
  ];

  if ($attempts >= $config['max_attempts']) {
    $state['locked_until'] = $now + $config['lock_seconds'];
  }

  $_SESSION[modeChoseThrottleKey($action)] = $state;
}

function modeChoseThrottleReset(string $action): void
{
  unset($_SESSION[modeChoseThrottleKey($action)]);
}

$BUDDY_SVGS = [
    'joy' => '<svg viewBox="0 0 180 200" xmlns="http://www.w3.org/2000/svg"><g>
  <ellipse cx="90" cy="30" rx="46" ry="24" fill="#38BDF8"/>
  <polygon points="70,20 80,2 93,22" fill="#38BDF8"/>
  <polygon points="90,16 102,0 112,20" fill="#60CAFF"/>
  <polygon points="50,26 58,6 72,26" fill="#29A8E0"/>
  <polygon points="110,22 118,4 130,26" fill="#60CAFF"/>
  <ellipse cx="90" cy="120" rx="62" ry="66" fill="#FDE68A"/>
  <ellipse cx="40" cy="134" rx="20" ry="12" fill="rgba(251,146,60,.3)"/>
  <ellipse cx="140" cy="134" rx="20" ry="12" fill="rgba(251,146,60,.3)"/>
  <ellipse cx="66" cy="112" rx="20" ry="22" fill="white" class="eb"/>
  <circle cx="66" cy="115" r="13" fill="#38BDF8" class="eb"/>
  <circle cx="66" cy="115" r="7.5" fill="#1E3A5F" class="eb"/>
  <circle cx="70" cy="110" r="3.5" fill="white" class="eb"/>
  <ellipse cx="114" cy="112" rx="20" ry="22" fill="white" class="eb eb2"/>
  <circle cx="114" cy="115" r="13" fill="#38BDF8" class="eb eb2"/>
  <circle cx="114" cy="115" r="7.5" fill="#1E3A5F" class="eb eb2"/>
  <circle cx="118" cy="110" r="3.5" fill="white" class="eb eb2"/>
  <circle cx="90" cy="126" r="5" fill="rgba(180,100,20,.2)"/>
  <path d="M52 148 Q90 180 128 148" stroke="#B45309" stroke-width="6" fill="none" stroke-linecap="round"/>
  <rect x="74" y="148" width="13" height="12" rx="3.5" fill="white"/>
  <rect x="90" y="148" width="13" height="12" rx="3.5" fill="white"/>
  <rect x="106" y="148" width="10" height="12" rx="3.5" fill="white"/>
  </g><text x="8" y="44" font-size="22" opacity=".8">&#11088;</text><text x="148" y="38" font-size="18" opacity=".7">&#10024;</text></svg>',

    'sadness' => '<svg viewBox="0 0 180 200" xmlns="http://www.w3.org/2000/svg"><g>
  <ellipse cx="90" cy="46" rx="54" ry="38" fill="#2563EB"/>
  <ellipse cx="50" cy="72" rx="18" ry="32" fill="#1D4ED8"/>
  <ellipse cx="90" cy="126" rx="52" ry="60" fill="#93C5FD"/>
  <circle cx="66" cy="118" r="24" fill="none" stroke="#1E3A8A" stroke-width="6"/>
  <circle cx="114" cy="118" r="24" fill="none" stroke="#1E3A8A" stroke-width="6"/>
  <line x1="42" y1="110" x2="36" y2="106" stroke="#1E3A8A" stroke-width="4.5"/>
  <line x1="138" y1="110" x2="144" y2="106" stroke="#1E3A8A" stroke-width="4.5"/>
  <ellipse cx="66" cy="118" rx="15" ry="18" fill="white" class="eb"/>
  <circle cx="66" cy="121" r="11" fill="#2563EB" class="eb"/>
  <circle cx="66" cy="121" r="6" fill="#1E3A8A" class="eb"/>
  <circle cx="70" cy="116" r="3" fill="white" class="eb"/>
  <ellipse cx="114" cy="118" rx="15" ry="18" fill="white" class="eb eb2"/>
  <circle cx="114" cy="121" r="11" fill="#2563EB" class="eb eb2"/>
  <circle cx="114" cy="121" r="6" fill="#1E3A8A" class="eb eb2"/>
  <circle cx="118" cy="116" r="3" fill="white" class="eb eb2"/>
  <path d="M48 94 Q66 102 84 94" stroke="#1E3A8A" stroke-width="4.5" fill="none" stroke-linecap="round"/>
  <path d="M96 94 Q114 102 132 94" stroke="#1E3A8A" stroke-width="4.5" fill="none" stroke-linecap="round"/>
  <path d="M60 156 Q90 144 120 156" stroke="#1D4ED8" stroke-width="5" fill="none" stroke-linecap="round"/>
  <ellipse cx="60" cy="178" rx="5.5" ry="9" fill="#BFDBFE" opacity=".9"/>
  <circle cx="60" cy="170" r="5.5" fill="#BFDBFE" opacity=".9"/>
  <ellipse cx="120" cy="180" rx="4.5" ry="7" fill="#BFDBFE" opacity=".65"/>
  <circle cx="120" cy="174" r="4.5" fill="#BFDBFE" opacity=".65"/>
  </g></svg>',

    'anger' => '<svg viewBox="0 0 180 200" xmlns="http://www.w3.org/2000/svg">
  <g style="animation:headShake 1.8s ease-in-out infinite">
  <path d="M72 46 Q62 22 76 12 Q80 28 83 22 Q86 10 92 16 Q90 30 96 24 Q100 10 108 18 Q104 34 96 40Z" fill="#FB923C"/>
  <path d="M76 46 Q68 26 78 16 Q82 30 85 24 Q88 12 94 18 Q92 32 98 26 Q102 14 108 22 Q104 34 96 40Z" fill="#FCD34D"/>
  <rect x="20" y="52" width="140" height="120" rx="20" fill="#DC2626"/>
  <rect x="20" y="104" width="140" height="4" fill="rgba(0,0,0,.1)"/>
  <rect x="20" y="120" width="140" height="4" fill="rgba(0,0,0,.1)"/>
  <rect x="20" y="148" width="140" height="24" rx="20" fill="rgba(0,0,0,.12)"/>
  <polygon points="24,76 66,98 66,106 24,82" fill="#7F1D1D"/>
  <polygon points="156,76 114,98 114,106 156,82" fill="#7F1D1D"/>
  <ellipse cx="60" cy="114" rx="20" ry="16" fill="white" class="eb"/>
  <circle cx="60" cy="114" r="11" fill="#7F1D1D" class="eb"/>
  <circle cx="63" cy="110" r="5" fill="white" class="eb"/>
  <ellipse cx="120" cy="114" rx="20" ry="16" fill="white" class="eb eb2"/>
  <circle cx="120" cy="114" r="11" fill="#7F1D1D" class="eb eb2"/>
  <circle cx="123" cy="110" r="5" fill="white" class="eb eb2"/>
  <rect x="46" y="144" width="88" height="22" rx="10" fill="#7F1D1D"/>
  <rect x="48" y="144" width="14" height="22" fill="white"/>
  <rect x="64" y="144" width="14" height="22" fill="white"/>
  <rect x="80" y="144" width="14" height="22" fill="white"/>
  <rect x="96" y="144" width="14" height="22" fill="white"/>
  <rect x="112" y="144" width="10" height="22" fill="white"/>
  <path d="M26 64 Q20 52 26 38" stroke="#FB923C" stroke-width="4" fill="none" stroke-linecap="round"/>
  <path d="M154 64 Q160 52 154 38" stroke="#FB923C" stroke-width="4" fill="none" stroke-linecap="round"/>
  </g></svg>',

    'disgust' => '<svg viewBox="0 0 180 200" xmlns="http://www.w3.org/2000/svg"><g>
  <ellipse cx="90" cy="34" rx="54" ry="30" fill="#15803D"/>
  <ellipse cx="56" cy="58" rx="18" ry="32" fill="#166534"/>
  <ellipse cx="124" cy="58" rx="18" ry="32" fill="#166534"/>
  <rect x="62" y="186" width="56" height="14" rx="7" fill="#C026D3" opacity=".72"/>
  <ellipse cx="90" cy="120" rx="56" ry="62" fill="#4ADE80"/>
  <line x1="52" y1="84" x2="42" y2="70" stroke="#14532D" stroke-width="4" stroke-linecap="round"/>
  <line x1="64" y1="78" x2="57" y2="63" stroke="#14532D" stroke-width="4" stroke-linecap="round"/>
  <line x1="76" y1="75" x2="73" y2="60" stroke="#14532D" stroke-width="4" stroke-linecap="round"/>
  <line x1="128" y1="84" x2="138" y2="70" stroke="#14532D" stroke-width="4" stroke-linecap="round"/>
  <line x1="116" y1="78" x2="123" y2="63" stroke="#14532D" stroke-width="4" stroke-linecap="round"/>
  <line x1="104" y1="75" x2="107" y2="60" stroke="#14532D" stroke-width="4" stroke-linecap="round"/>
  <ellipse cx="64" cy="108" rx="22" ry="18" fill="white" class="eb"/>
  <circle cx="64" cy="108" r="13" fill="#15803D" class="eb"/>
  <circle cx="64" cy="108" r="7" fill="#052e16" class="eb"/>
  <circle cx="68" cy="103" r="4" fill="white" class="eb"/>
  <ellipse cx="116" cy="108" rx="22" ry="18" fill="white" class="eb eb2"/>
  <circle cx="116" cy="108" r="13" fill="#15803D" class="eb eb2"/>
  <circle cx="116" cy="108" r="7" fill="#052e16" class="eb eb2"/>
  <circle cx="120" cy="103" r="4" fill="white" class="eb eb2"/>
  <path d="M46 90 Q64 82 82 90" stroke="#14532D" stroke-width="5" fill="none" stroke-linecap="round"/>
  <path d="M98 90 Q116 82 134 90" stroke="#14532D" stroke-width="5" fill="none" stroke-linecap="round"/>
  <path d="M56 148 Q74 138 90 142 Q106 138 124 148" stroke="#14532D" stroke-width="5" fill="none" stroke-linecap="round"/>
  <path d="M66 160 Q90 152 114 160" stroke="#14532D" stroke-width="4" fill="none" stroke-linecap="round"/>
  </g></svg>',

    'fear' => '<svg viewBox="0 0 180 200" xmlns="http://www.w3.org/2000/svg">
  <g style="animation:tremor .3s ease-in-out infinite alternate">
  <line x1="90" y1="4" x2="90" y2="30" stroke="#7E22CE" stroke-width="4.5" stroke-linecap="round"/>
  <ellipse cx="90" cy="2" rx="7" ry="10" fill="#A855F7" opacity=".7"/>
  <path d="M90 30 Q102 20 114 30 Q126 40 138 28" stroke="#7E22CE" stroke-width="3.5" fill="none" stroke-linecap="round"/>
  <ellipse cx="90" cy="126" rx="48" ry="72" fill="#D8B4FE"/>
  <ellipse cx="60" cy="106" rx="26" ry="30" fill="white" class="eb"/>
  <circle cx="60" cy="110" r="18" fill="#7E22CE" class="eb"/>
  <circle cx="60" cy="110" r="9" fill="#1A0533" class="eb"/>
  <circle cx="65" cy="103" r="5.5" fill="white" class="eb"/>
  <ellipse cx="120" cy="106" rx="26" ry="30" fill="white" class="eb eb2"/>
  <circle cx="120" cy="110" r="18" fill="#7E22CE" class="eb eb2"/>
  <circle cx="120" cy="110" r="9" fill="#1A0533" class="eb eb2"/>
  <circle cx="125" cy="103" r="5.5" fill="white" class="eb eb2"/>
  <path d="M40 76 Q60 66 80 76" stroke="#4C1D95" stroke-width="5" fill="none" stroke-linecap="round"/>
  <path d="M100 76 Q120 66 140 76" stroke="#4C1D95" stroke-width="5" fill="none" stroke-linecap="round"/>
  <ellipse cx="90" cy="164" rx="18" ry="14" fill="#4C1D95"/>
  <ellipse cx="90" cy="162" rx="12" ry="10" fill="#1A0533"/>
  <path d="M34 122 Q27 128 34 134" stroke="rgba(126,34,206,.5)" stroke-width="3.5" fill="none" stroke-linecap="round"/>
  <path d="M146 122 Q153 128 146 134" stroke="rgba(126,34,206,.5)" stroke-width="3.5" fill="none" stroke-linecap="round"/>
  </g></svg>',

    'anxiety' => '<svg viewBox="0 0 180 200" xmlns="http://www.w3.org/2000/svg"><g>
  <ellipse cx="90" cy="38" rx="58" ry="32" fill="#EA580C"/>
  <path d="M32 38 Q24 14 36 8 Q38 24 46 19 Q40 4 54 1 Q56 18 66 14 Q62 0 76 2 Q80 18 86 14 Q86 0 98 4 Q96 18 104 14 Q106 2 116 8 Q112 22 122 18 Q124 4 134 10 Q130 26 140 22Z" fill="#C2410C"/>
  <ellipse cx="90" cy="128" rx="58" ry="70" fill="#FDBA74"/>
  <ellipse cx="46" cy="142" rx="18" ry="10" fill="rgba(239,68,68,.2)"/>
  <ellipse cx="134" cy="142" rx="18" ry="10" fill="rgba(239,68,68,.2)"/>
  <circle cx="64" cy="118" r="20" fill="white" class="eb"/>
  <path d="M64 118 m14 0 a14 14 0 1 1-28 0 a14 14 0 1 1 28 0" fill="none" stroke="#431407" stroke-width="3.5" class="eb"/>
  <path d="M64 118 m8 0 a8 8 0 1 1-16 0 a8 8 0 1 1 16 0" fill="none" stroke="#431407" stroke-width="3" class="eb"/>
  <path d="M64 118 m4 0 a4 4 0 1 1-8 0 a4 4 0 1 1 8 0" fill="none" stroke="#431407" stroke-width="2.5" class="eb"/>
  <circle cx="64" cy="118" r="2" fill="#431407" class="eb"/>
  <circle cx="68" cy="112" r="3.5" fill="white" class="eb"/>
  <circle cx="116" cy="118" r="20" fill="white" class="eb eb2"/>
  <path d="M116 118 m14 0 a14 14 0 1 1-28 0 a14 14 0 1 1 28 0" fill="none" stroke="#431407" stroke-width="3.5" class="eb eb2"/>
  <path d="M116 118 m8 0 a8 8 0 1 1-16 0 a8 8 0 1 1 16 0" fill="none" stroke="#431407" stroke-width="3" class="eb eb2"/>
  <path d="M116 118 m4 0 a4 4 0 1 1-8 0 a4 4 0 1 1 8 0" fill="none" stroke="#431407" stroke-width="2.5" class="eb eb2"/>
  <circle cx="116" cy="118" r="2" fill="#431407" class="eb eb2"/>
  <circle cx="120" cy="112" r="3.5" fill="white" class="eb eb2"/>
  <path d="M46 98 Q64 106 82 98" stroke="#92400E" stroke-width="5" fill="none" stroke-linecap="round"/>
  <path d="M98 98 Q116 106 134 98" stroke="#92400E" stroke-width="5" fill="none" stroke-linecap="round"/>
  <path d="M48 156 Q68 166 90 156 Q112 166 132 156" stroke="#92400E" stroke-width="5" fill="none" stroke-linecap="round"/>
  <ellipse cx="152" cy="92" rx="6" ry="9" fill="#FEF9C3" opacity=".85"/>
  <circle cx="152" cy="84" r="6" fill="#FEF9C3" opacity=".85"/>
  </g></svg>'
];
$userId = (int)$_SESSION['user_id'];

// ── AJAX / POST HANDLER ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
  header('Content-Type: application/json; charset=utf-8');
  $pdo    = getDB();
  $action = $_POST['action'];

  // ── Verify parent PIN ──────────────────────────────────────
  if ($action === 'verify_pin') {
    if (modeChoseThrottleBlocked('verify_pin')) {
      http_response_code(429);
      echo json_encode(['success' => false, 'message' => modeChoseThrottleMessage('verify_pin')]);
      exit;
    }

    $pin = $_POST['pin'] ?? '';
    $st  = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ? AND role = 'parent'");
    $st->execute([$userId]);
    $row = $st->fetch();

    if ($row && $row['pin_hash'] && password_verify($pin, $row['pin_hash'])) {
      modeChoseThrottleReset('verify_pin');
      $_SESSION['chosen_mode'] = 'parent';
      echo json_encode([
        'success' => true,
        'redirect' => '../parent/dashboard/unicef_indexation/dash/index.php'
      ]);
    } else {
      modeChoseThrottleFailure('verify_pin');
      // If no PIN is set yet, any 4-digit code fails gracefully
      echo json_encode(['success' => false, 'message' => 'Incorrect PIN']);
    }
    exit;
  }

  // ── Verify account password (for "Forgot PIN?" flow) ───────
  if ($action === 'verify_password') {
    if (modeChoseThrottleBlocked('verify_password')) {
      http_response_code(429);
      echo json_encode(['success' => false, 'message' => modeChoseThrottleMessage('verify_password')]);
      exit;
    }

    $password = $_POST['password'] ?? '';
    $st = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $st->execute([$userId]);
    $row = $st->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
      modeChoseThrottleReset('verify_password');
      echo json_encode(['success' => true]);
    } else {
      modeChoseThrottleFailure('verify_password');
      echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    }
    exit;
  }

  // ── Save new PIN (reset flow) ───────────────────────────────
  if ($action === 'reset_pin') {
    $newPin = $_POST['pin'] ?? '';
    if (strlen($newPin) === 4 && ctype_digit($newPin)) {
      $hash = password_hash($newPin, PASSWORD_BCRYPT);
      $pdo->prepare("UPDATE users SET pin_hash = ? WHERE id = ?")
        ->execute([$hash, $userId]);
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Invalid PIN format']);
    }
    exit;
  }


  // ── Select a child profile → session + redirect ─────────────
  if ($action === 'select_child') {
    $childId = (int)($_POST['child_id'] ?? 0);

    // Verify this child actually belongs to this parent
    $st = $pdo->prepare("
            SELECT c.child_id
            FROM   children c
            JOIN   parents  p ON c.parent_id = p.parent_id
            WHERE  c.child_id = ?
            AND    p.user_id  = ?
        ");
    $st->execute([$childId, $userId]);

    if ($st->fetch()) {
      $_SESSION['chosen_mode'] = 'child';
      $_SESSION['child_id']    = $childId;
      echo json_encode([
        'success'  => true,
        'redirect' => '../child/character-selection/choose-character.php'
      ]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Invalid child profile']);
    }
    exit;
  }
  // ── Add a child profile ─────────────────────────────────────
  if ($action === 'add_child') {
    $name = trim($_POST['name'] ?? '');
    $age  = (!empty($_POST['age']) && is_numeric($_POST['age']))
      ? (int)$_POST['age'] : null;

    if ($name === '') {
      echo json_encode(['success' => false, 'message' => 'Name is required']);
      exit;
    }

    $st = $pdo->prepare("SELECT parent_id FROM parents WHERE user_id = ?");
    $st->execute([$userId]);
    $parent = $st->fetch();

    if (!$parent) {
      echo json_encode(['success' => false, 'message' => 'Parent profile not found']);
      exit;
    }

    // Duplicate nickname check
    $st = $pdo->prepare(
      "SELECT child_id FROM children WHERE parent_id = ? AND LOWER(nickname) = LOWER(?)"
    );
    $st->execute([$parent['parent_id'], $name]);
    if ($st->fetch()) {
      echo json_encode(['success' => false, 'message' => 'A child with that name already exists']);
      exit;
    }

    $pdo->prepare("INSERT INTO children (parent_id, nickname, age) VALUES (?, ?, ?)")
      ->execute([$parent['parent_id'], $name, $age]);

    $childId = (int)$pdo->lastInsertId();

    echo json_encode([
      'success'  => true,
      'child_id' => $childId,
      'name'     => $name,
      'age'      => $age,
      'emoji'    => '🦊',
      'theme'    => 'theme-rose',
      'buddy'    => null, // Add this line
    ]);
    exit;
  }

  // ── Delete a child profile ──────────────────────────────────
  if ($action === 'delete_child') {
    $childId = (int)($_POST['child_id'] ?? 0);

    // Ownership check: verify this specific child belongs to the logged-in parent.
    // Uses its own query with both child_id AND user_id — $st is never
    // overwritten by unrelated data before the fetch() call.
    $st = $pdo->prepare("
            SELECT 1
            FROM   children c
            JOIN   parents  p ON c.parent_id = p.parent_id
            WHERE  c.child_id = ?
            AND    p.user_id  = ?
            LIMIT  1
        ");
    $st->execute([$childId, $userId]);

    if ($st->fetch()) {
      $pdo->prepare("DELETE FROM children WHERE child_id = ?")->execute([$childId]);
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Invalid child profile']);
    }
    exit;
  }

  echo json_encode(['success' => false, 'message' => 'Unknown action']);
  exit;
}

// ── PAGE RENDER — fetch data ────────────────────────────────────
try {
  $pdo = getDB();
} catch (PDOException $e) {
  error_log('modeChose DB connection failed: ' . $e->getMessage());
  if (!headers_sent()) {
    http_response_code(500);
  }
  exit('An unexpected error occurred. Please try again later.');
}

// Parent user
$st = $pdo->prepare("SELECT username FROM users WHERE id = ? AND role = 'parent'");
$st->execute([$userId]);
$parentUser = $st->fetch();

if (!$parentUser) {
  // Shouldn't happen, but guard anyway
  header('Location: login.php');
  exit;
}

// Children (with DB emoji + theme + buddy)
$st = $pdo->prepare("
    SELECT c.child_id, c.nickname, c.age, c.emoji, c.theme, c.buddy
    FROM   children c
    JOIN   parents  p ON c.parent_id = p.parent_id
    WHERE  p.user_id = ?
    ORDER  BY c.created_at ASC
");
$st->execute([$userId]);
$childRows = $st->fetchAll();

// Build JS-safe data objects
$jsParentName = json_encode($parentUser['username'], JSON_HEX_TAG | JSON_HEX_APOS);
$jsChildren   = json_encode(
  array_map(fn($c) => [
    'child_id' => (int)$c['child_id'],
    'name'     => $c['nickname'],
    'age'      => $c['age'] !== null ? (int)$c['age'] : null,
    'emoji'    => $c['emoji'] ?: '',
    'theme'    => $c['theme'] ?: 'theme-rose',
    'buddy'    => $c['buddy'] ?? null,
  ], $childRows),
  JSON_HEX_TAG | JSON_HEX_APOS
);

// Logout URL (same logic as modeChose.php)
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$basePath = ($basePath === '/' ? '' : $basePath);
$basePath = dirname(dirname($basePath));
$logoutUrl = htmlspecialchars($basePath . '/auth/logout');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOPY — Who's Watching?</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-deep: #0d0718;
      --bg-base: #1c0f30;
      --violet-royal: #7c3aed;
      --violet-soft: #a78bfa;
      --lilac: #c4b5fd;
      --lilac-light: #ede9fe;
      --pink-accent: #e879f9;
      --card-bg: rgba(35, 20, 66, 0.6);
      --glass-border: rgba(167, 139, 250, 0.2);
      --glow-parent: rgba(124, 58, 237, 0.65);
      --glow-child: rgba(196, 181, 253, 0.5);
      --text-primary: #f0eaff;
      --text-muted: #9d86c8;
      --radius-card: 22px;
      --font-display: "Cinzel", serif;
      --font-body: "DM Sans", sans-serif;
      --success: #34d399;
      --danger: #f87171;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      width: 100%;
      min-height: 100vh;
      background: var(--bg-deep);
      font-family: var(--font-body);
      color: var(--text-primary);
      overflow-x: hidden;
    }

    #bg-canvas {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
    }

    .bg-overlay {
      position: fixed;
      inset: 0;
      z-index: 1;
      pointer-events: none;
      background:
        radial-gradient(ellipse 80% 60% at 15% 10%, rgba(124, 58, 237, 0.20) 0%, transparent 60%),
        radial-gradient(ellipse 55% 45% at 85% 80%, rgba(232, 121, 249, 0.14) 0%, transparent 55%),
        radial-gradient(ellipse 100% 80% at 50% 50%, rgba(13, 7, 24, 0.55) 0%, transparent 100%);
    }

    .page {
      position: relative;
      z-index: 2;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 24px 88px;
      opacity: 0;
      animation: fadeIn 1s ease forwards 0.15s;
    }

    @keyframes fadeIn {
      to {
        opacity: 1;
      }
    }

    /* TOP BAR */
    .top-bar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 10;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 36px;
      background: linear-gradient(to bottom, rgba(13, 7, 24, 0.92) 0%, transparent 100%);
    }

    .logo-wrap {
      display: flex;
      align-items: center;
    }

    .logo-wrap img {
      height: 80px;
      width: auto;
      mix-blend-mode: screen;
      filter: brightness(1.05) contrast(1.05);
    }

    .btn-logout {
      font-family: var(--font-body);
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-muted);
      background: rgba(124, 58, 237, 0.08);
      border: 1px solid rgba(167, 139, 250, 0.18);
      padding: 9px 20px;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn-logout:hover {
      color: var(--lilac);
      border-color: var(--violet-soft);
      background: rgba(124, 58, 237, 0.2);
      box-shadow: 0 0 18px rgba(124, 58, 237, 0.28);
    }

    /* HEADER */
    .header {
      text-align: center;
      margin-bottom: 56px;
    }

    .header h1 {
      font-family: var(--font-display);
      font-size: clamp(1.7rem, 4vw, 2.8rem);
      font-weight: 600;
      letter-spacing: 0.07em;
      color: var(--text-primary);
      margin-bottom: 10px;
    }

    .header h1 .yopy-text {
      background: linear-gradient(135deg, #f472b6 0%, #c084fc 40%, #818cf8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .header p {
      font-size: 0.95rem;
      color: var(--text-muted);
      font-weight: 300;
      letter-spacing: 0.06em;
      font-style: italic;
    }

    /* GRID */
    .profiles-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      max-width: 1120px;
      width: 100%;
    }

    /* CARD */
    .profile-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      width: 152px;
      cursor: pointer;
      opacity: 0;
      transform: translateY(28px);
      animation: cardIn 0.65s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }

    @keyframes cardIn {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes cardAppear {
      from {
        opacity: 0;
        transform: translateY(20px) scale(0.9);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    @keyframes cardDisappear {
      from {
        opacity: 1;
        transform: scale(1);
      }

      to {
        opacity: 0;
        transform: scale(0.8) translateY(10px);
      }
    }

    .card-inner {
      width: 152px;
      height: 152px;
      border-radius: var(--radius-card);
      background: var(--card-bg);
      backdrop-filter: blur(22px) saturate(1.4);
      border: 1px solid var(--glass-border);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      transition:
        transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1),
        box-shadow 0.4s ease,
        border-color 0.4s ease;
    }

    .card-inner::before {
      content: "";
      position: absolute;
      top: 0;
      left: -70%;
      width: 40%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.06), transparent);
      transform: skewX(-15deg);
      transition: left 0.65s ease;
    }

    .profile-card:hover .card-inner::before {
      left: 150%;
    }

    .card-inner::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 48%;
      background: linear-gradient(to top, rgba(13, 7, 24, 0.55), transparent);
      border-radius: 0 0 var(--radius-card) var(--radius-card);
      pointer-events: none;
    }

    .profile-card.parent .card-inner {
      border-color: rgba(124, 58, 237, 0.42);
      background: rgba(80, 30, 150, 0.22);
    }

    .profile-card.parent:hover .card-inner {
      transform: scale(1.09);
      border-color: rgba(167, 139, 250, 0.75);
      box-shadow:
        0 0 0 1.5px rgba(124, 58, 237, 0.55),
        0 0 35px var(--glow-parent),
        0 0 70px rgba(124, 58, 237, 0.2),
        0 22px 45px rgba(0, 0, 0, 0.55);
    }

    .profile-card.child .card-inner {
      border-color: rgba(196, 181, 253, 0.22);
    }

    .profile-card.child:hover .card-inner {
      transform: scale(1.08);
      box-shadow:
        0 0 0 1.5px rgba(196, 181, 253, 0.48),
        0 0 30px var(--glow-child),
        0 0 60px rgba(196, 181, 253, 0.12),
        0 20px 42px rgba(0, 0, 0, 0.45);
      border-color: rgba(196, 181, 253, 0.62);
    }

    .parent-ring {
      position: absolute;
      inset: -4px;
      border-radius: calc(var(--radius-card) + 5px);
      background: linear-gradient(135deg, #7c3aed, #e879f9, #a78bfa, #7c3aed);
      background-size: 300% 300%;
      z-index: -1;
      opacity: 0;
      transition: opacity 0.4s ease;
      animation: ringRotate 4s linear infinite;
    }

    @keyframes ringRotate {
      to {
        background-position: 300% 300%;
      }
    }

    .profile-card.parent:hover .parent-ring {
      opacity: 1;
    }

    .child-dots {
      position: absolute;
      bottom: 14px;
      display: flex;
      gap: 5px;
      z-index: 2;
    }

    .dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      opacity: 0.7;
    }

    .profile-name {
      font-size: 0.9rem;
      font-weight: 500;
      letter-spacing: 0.04em;
      color: var(--lilac-light);
      text-align: center;
      transition: color 0.3s;
      max-width: 144px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .profile-card:hover .profile-name {
      color: #fff;
    }

    .profile-label {
      font-size: 0.62rem;
      font-weight: 600;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 3px 11px;
      border-radius: 50px;
      margin-top: -7px;
    }

    .label-parent {
      background: rgba(124, 58, 237, 0.22);
      color: var(--violet-soft);
      border: 1px solid rgba(124, 58, 237, 0.38);
    }

    .label-child {
      background: rgba(196, 181, 253, 0.1);
      color: var(--text-muted);
      border: 1px solid rgba(196, 181, 253, 0.18);
    }

    .avatar-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      position: relative;
      z-index: 1;
    }

    .avatar-wrap svg {
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .profile-card:hover .avatar-wrap svg {
      transform: scale(1.1) translateY(-3px);
    }

    .child-avatar {
      font-size: 4rem;
      line-height: 1;
      filter: drop-shadow(0 3px 10px rgba(0, 0, 0, 0.45));
      position: relative;
      z-index: 1;
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      display: block;
    }

    .profile-card:hover .child-avatar {
      transform: scale(1.15) translateY(-4px) rotate(-5deg);
    }

    .theme-rose .card-inner {
      background: rgba(251, 113, 133, 0.08);
    }

    .theme-teal .card-inner {
      background: rgba(45, 212, 191, 0.08);
    }

    .theme-blue .card-inner {
      background: rgba(96, 165, 250, 0.08);
    }

    .theme-amber .card-inner {
      background: rgba(251, 191, 36, 0.08);
    }

    .theme-mint .card-inner {
      background: rgba(52, 211, 153, 0.08);
    }

    .theme-sky .card-inner {
      background: rgba(56, 189, 248, 0.08);
    }

    /* BOTTOM */
    .bottom-actions {
      margin-top: 60px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
    }

    .btn-manage {
      font-family: var(--font-body);
      font-size: 0.76rem;
      font-weight: 500;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-muted);
      background: transparent;
      border: 1px solid rgba(124, 58, 237, 0.22);
      padding: 10px 30px;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-manage:hover {
      color: var(--lilac);
      border-color: var(--violet-soft);
      box-shadow: 0 0 18px rgba(124, 58, 237, 0.18);
    }

    /* ─── SHARED MODAL BASE ─── */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 50;
      background: rgba(13, 7, 24, 0.88);
      backdrop-filter: blur(10px);
      align-items: center;
      justify-content: center;
    }

    .modal-backdrop.open {
      display: flex;
      animation: fadeIn 0.25s ease;
    }

    .modal {
      background: linear-gradient(145deg, rgba(45, 22, 82, 0.95), rgba(28, 15, 48, 0.98));
      border: 1px solid rgba(124, 58, 237, 0.42);
      border-radius: 26px;
      padding: 48px 42px;
      width: min(400px, 92vw);
      text-align: center;
      box-shadow:
        0 0 80px rgba(124, 58, 237, 0.28),
        0 0 0 1px rgba(167, 139, 250, 0.08) inset,
        0 48px 96px rgba(0, 0, 0, 0.7);
      animation: scaleIn 0.38s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes scaleIn {
      from {
        opacity: 0;
        transform: scale(0.86);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    .modal-parent-icon {
      margin: 0 auto 18px;
      display: block;
    }

    .modal h2 {
      font-family: var(--font-display);
      font-size: 1.25rem;
      letter-spacing: 0.08em;
      color: var(--text-primary);
      margin-bottom: 6px;
    }

    .modal p {
      font-size: 0.83rem;
      color: var(--text-muted);
      margin-bottom: 30px;
      font-style: italic;
    }

    /* PIN */
    .pin-dots {
      display: flex;
      gap: 16px;
      justify-content: center;
      margin-bottom: 30px;
    }

    .pin-dot {
      width: 15px;
      height: 15px;
      border-radius: 50%;
      border: 2px solid rgba(124, 58, 237, 0.42);
      background: transparent;
      transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .pin-dot.filled {
      background: var(--violet-royal);
      border-color: var(--violet-soft);
      box-shadow: 0 0 14px var(--glow-parent);
      transform: scale(1.2);
    }

    .pin-keypad {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 11px;
      margin-bottom: 18px;
    }

    .pin-key {
      aspect-ratio: 1;
      background: rgba(124, 58, 237, 0.09);
      border: 1px solid rgba(124, 58, 237, 0.18);
      border-radius: 16px;
      font-family: var(--font-display);
      font-size: 1.3rem;
      color: var(--lilac);
      cursor: pointer;
      transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .pin-key:hover {
      background: rgba(124, 58, 237, 0.22);
      border-color: rgba(167, 139, 250, 0.5);
      box-shadow: 0 0 16px rgba(124, 58, 237, 0.3);
      transform: scale(1.08);
    }

    .pin-key:active {
      transform: scale(0.94);
    }

    .pin-key.del {
      background: rgba(232, 121, 249, 0.07);
      color: var(--pink-accent);
    }

    .modal-cancel {
      font-size: 0.78rem;
      color: var(--text-muted);
      cursor: pointer;
      background: none;
      border: none;
      font-family: var(--font-body);
      letter-spacing: 0.06em;
      transition: color 0.22s;
      text-transform: uppercase;
    }

    .modal-cancel:hover {
      color: var(--lilac);
    }

    .pin-error {
      font-size: 0.76rem;
      color: var(--danger);
      margin-top: 12px;
      opacity: 0;
      transition: opacity 0.28s;
    }

    .pin-error.show {
      opacity: 1;
    }

    /* FORGOT PIN link */
    .forgot-pin-link {
      font-size: 0.75rem;
      color: var(--violet-soft);
      cursor: pointer;
      background: none;
      border: none;
      font-family: var(--font-body);
      letter-spacing: 0.04em;
      transition: color 0.22s;
      text-decoration: underline;
      text-underline-offset: 3px;
      margin-bottom: 14px;
      display: inline-block;
    }

    .forgot-pin-link:hover {
      color: var(--lilac);
    }

    /* FORGOT PIN / Password modal */
    .modal-small {
      width: min(360px, 92vw);
      padding: 40px 36px;
    }

    .modal-input-wrap {
      position: relative;
      margin: 18px 0 8px;
    }

    .modal-input {
      width: 100%;
      background: rgba(124, 58, 237, 0.08);
      border: 1px solid rgba(124, 58, 237, 0.32);
      border-radius: 14px;
      padding: 13px 18px;
      font-family: var(--font-body);
      font-size: 0.92rem;
      color: var(--text-primary);
      outline: none;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .modal-input:focus {
      border-color: var(--violet-soft);
      box-shadow: 0 0 20px rgba(124, 58, 237, 0.25);
    }

    .modal-input.error {
      border-color: var(--danger);
      box-shadow: 0 0 14px rgba(248, 113, 113, 0.22);
    }

    .modal-input.success {
      border-color: var(--success);
      box-shadow: 0 0 14px rgba(52, 211, 153, 0.22);
    }

    .modal-input-msg {
      font-size: 0.75rem;
      margin-top: 7px;
      opacity: 0;
      transition: opacity 0.25s;
      text-align: left;
    }

    .modal-input-msg.show {
      opacity: 1;
    }

    .modal-input-msg.error-msg {
      color: var(--danger);
    }

    .modal-input-msg.success-msg {
      color: var(--success);
    }

    .btn-primary {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, var(--violet-royal), #6d28d9);
      border: none;
      border-radius: 14px;
      font-family: var(--font-body);
      font-size: 0.88rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: #fff;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 8px;
      box-shadow: 0 0 24px rgba(124, 58, 237, 0.35);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #8b5cf6, var(--violet-royal));
      box-shadow: 0 0 36px rgba(124, 58, 237, 0.55);
      transform: translateY(-1px);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    /* ─── MANAGE PROFILES MODAL ─── */
    .manage-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 60;
      background: rgba(13, 7, 24, 0.92);
      backdrop-filter: blur(14px);
      align-items: flex-start;
      justify-content: center;
      overflow-y: auto;
      padding: 40px 20px;
    }

    .manage-modal-backdrop.open {
      display: flex;
      animation: fadeIn 0.28s ease;
    }

    .manage-modal {
      background: linear-gradient(160deg, rgba(40, 18, 75, 0.97), rgba(22, 10, 45, 0.99));
      border: 1px solid rgba(124, 58, 237, 0.36);
      border-radius: 30px;
      width: min(640px, 100%);
      box-shadow:
        0 0 100px rgba(124, 58, 237, 0.22),
        0 0 0 1px rgba(167, 139, 250, 0.06) inset,
        0 60px 120px rgba(0, 0, 0, 0.8);
      animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      overflow: hidden;
      margin: auto;
    }

    .manage-header {
      padding: 32px 36px 24px;
      border-bottom: 1px solid rgba(124, 58, 237, 0.18);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .manage-header-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .manage-header-icon {
      width: 44px;
      height: 44px;
      border-radius: 14px;
      background: rgba(124, 58, 237, 0.18);
      border: 1px solid rgba(124, 58, 237, 0.32);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .manage-header h3 {
      font-family: var(--font-display);
      font-size: 1.1rem;
      letter-spacing: 0.07em;
      color: var(--text-primary);
    }

    .manage-header p {
      font-size: 0.76rem;
      color: var(--text-muted);
      margin: 2px 0 0;
      font-style: italic;
    }

    .btn-close-manage {
      background: rgba(124, 58, 237, 0.1);
      border: 1px solid rgba(124, 58, 237, 0.22);
      border-radius: 10px;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--text-muted);
      transition: all 0.25s;
    }

    .btn-close-manage:hover {
      background: rgba(124, 58, 237, 0.24);
      color: var(--lilac);
      border-color: var(--violet-soft);
    }

    .manage-body {
      padding: 28px 36px 36px;
    }

    /* Children list */
    .children-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 32px;
    }

    .children-empty {
      text-align: center;
      padding: 32px;
      color: var(--text-muted);
      font-size: 0.85rem;
      font-style: italic;
      border: 1px dashed rgba(124, 58, 237, 0.2);
      border-radius: 16px;
    }

    .child-row {
      display: flex;
      align-items: center;
      gap: 16px;
      background: rgba(124, 58, 237, 0.07);
      border: 1px solid rgba(124, 58, 237, 0.16);
      border-radius: 16px;
      padding: 14px 18px;
      transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
      animation: rowAppear 0.45s cubic-bezier(0.22, 1, 0.36, 1) forwards;
      opacity: 0;
      transform: translateX(-10px);
    }

    @keyframes rowAppear {
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .child-row:hover {
      background: rgba(124, 58, 237, 0.12);
      border-color: rgba(124, 58, 237, 0.3);
      box-shadow: 0 4px 18px rgba(124, 58, 237, 0.12);
    }

    .child-row.removing {
      animation: rowRemove 0.42s cubic-bezier(0.22, 1, 0.36, 1) forwards !important;
    }

    @keyframes rowRemove {
      to {
        opacity: 0;
        transform: translateX(20px) scale(0.95);
        max-height: 0;
        padding: 0;
        margin: 0;
      }
    }

    .child-row-emoji {
      font-size: 2rem;
      line-height: 1;
      filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.4));
      flex-shrink: 0;
    }

    .child-row-info {
      flex: 1;
    }

    .child-row-name {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--lilac-light);
      letter-spacing: 0.02em;
    }

    .child-row-meta {
      font-size: 0.76rem;
      color: var(--text-muted);
      margin-top: 2px;
      display: flex;
      gap: 10px;
    }

    .child-meta-badge {
      background: rgba(167, 139, 250, 0.1);
      border: 1px solid rgba(167, 139, 250, 0.18);
      border-radius: 6px;
      padding: 1px 8px;
      font-size: 0.7rem;
      color: var(--text-muted);
      letter-spacing: 0.04em;
    }

    .btn-delete-child {
      background: rgba(248, 113, 113, 0.08);
      border: 1px solid rgba(248, 113, 113, 0.2);
      border-radius: 10px;
      padding: 8px 14px;
      font-size: 0.72rem;
      color: #f87171;
      font-family: var(--font-body);
      letter-spacing: 0.06em;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.25s;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-delete-child:hover {
      background: rgba(248, 113, 113, 0.16);
      border-color: rgba(248, 113, 113, 0.4);
      box-shadow: 0 0 14px rgba(248, 113, 113, 0.2);
      transform: scale(1.04);
    }

    /* ADD CHILD FORM */
    .add-child-section {
      border-top: 1px solid rgba(124, 58, 237, 0.18);
      padding-top: 24px;
    }

    .add-child-title {
      font-family: var(--font-display);
      font-size: 0.85rem;
      letter-spacing: 0.1em;
      color: var(--violet-soft);
      text-transform: uppercase;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-child-form {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-label {
      font-size: 0.72rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 500;
    }

    .form-input,
    .form-select {
      background: rgba(124, 58, 237, 0.08);
      border: 1px solid rgba(124, 58, 237, 0.28);
      border-radius: 12px;
      padding: 11px 15px;
      font-family: var(--font-body);
      font-size: 0.88rem;
      color: var(--text-primary);
      outline: none;
      transition: border-color 0.3s, box-shadow 0.3s;
      width: 100%;
      -webkit-appearance: none;
    }

    .form-input:focus,
    .form-select:focus {
      border-color: var(--violet-soft);
      box-shadow: 0 0 16px rgba(124, 58, 237, 0.22);
    }

    .form-input.field-error {
      border-color: var(--danger);
      box-shadow: 0 0 12px rgba(248, 113, 113, 0.18);
    }

    .form-input.field-success {
      border-color: var(--success);
    }

    .form-select {
      cursor: pointer;
    }

    .form-select option {
      background: #1c0f30;
      color: var(--text-primary);
    }

    .form-error-msg {
      font-size: 0.73rem;
      color: var(--danger);
      opacity: 0;
      transition: opacity 0.25s;
      min-height: 1em;
    }

    .form-error-msg.show {
      opacity: 1;
    }

    .btn-add-child {
      padding: 13px;
      background: linear-gradient(135deg, rgba(124, 58, 237, 0.5), rgba(109, 40, 217, 0.6));
      border: 1px solid rgba(124, 58, 237, 0.45);
      border-radius: 14px;
      font-family: var(--font-body);
      font-size: 0.85rem;
      font-weight: 600;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      color: var(--lilac);
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 4px;
    }

    .btn-add-child:hover {
      background: linear-gradient(135deg, rgba(124, 58, 237, 0.75), rgba(109, 40, 217, 0.85));
      border-color: var(--violet-soft);
      box-shadow: 0 0 28px rgba(124, 58, 237, 0.4);
      transform: translateY(-1px);
      color: #fff;
    }

    .btn-add-child:active {
      transform: translateY(0);
    }

    .btn-add-child:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    /* CONFIRM DIALOG */
    .confirm-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 80;
      background: rgba(13, 7, 24, 0.72);
      backdrop-filter: blur(8px);
      align-items: center;
      justify-content: center;
    }

    .confirm-backdrop.open {
      display: flex;
      animation: fadeIn 0.2s ease;
    }

    .confirm-box {
      background: linear-gradient(145deg, rgba(60, 25, 100, 0.97), rgba(30, 12, 55, 0.99));
      border: 1px solid rgba(248, 113, 113, 0.3);
      border-radius: 22px;
      padding: 36px 32px;
      width: min(360px, 92vw);
      text-align: center;
      box-shadow: 0 0 60px rgba(248, 113, 113, 0.15), 0 40px 80px rgba(0, 0, 0, 0.7);
      animation: scaleIn 0.32s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .confirm-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: rgba(248, 113, 113, 0.12);
      border: 2px solid rgba(248, 113, 113, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 1.4rem;
    }

    .confirm-box h3 {
      font-family: var(--font-display);
      font-size: 1rem;
      letter-spacing: 0.06em;
      color: var(--text-primary);
      margin-bottom: 8px;
    }

    .confirm-box p {
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-bottom: 24px;
      font-style: italic;
    }

    .confirm-actions {
      display: flex;
      gap: 12px;
    }

    .btn-confirm-cancel {
      flex: 1;
      padding: 12px;
      background: rgba(124, 58, 237, 0.1);
      border: 1px solid rgba(124, 58, 237, 0.22);
      border-radius: 12px;
      font-family: var(--font-body);
      font-size: 0.8rem;
      color: var(--text-muted);
      cursor: pointer;
      transition: all 0.25s;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .btn-confirm-cancel:hover {
      background: rgba(124, 58, 237, 0.2);
      color: var(--lilac);
    }

    .btn-confirm-delete {
      flex: 1;
      padding: 12px;
      background: rgba(248, 113, 113, 0.12);
      border: 1px solid rgba(248, 113, 113, 0.3);
      border-radius: 12px;
      font-family: var(--font-body);
      font-size: 0.8rem;
      color: var(--danger);
      cursor: pointer;
      transition: all 0.25s;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-weight: 600;
    }

    .btn-confirm-delete:hover {
      background: rgba(248, 113, 113, 0.22);
      border-color: rgba(248, 113, 113, 0.5);
      box-shadow: 0 0 18px rgba(248, 113, 113, 0.22);
    }

    /* TRANSITION */
    .transition-overlay {
      position: fixed;
      inset: 0;
      z-index: 100;
      background: var(--bg-deep);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.5s ease;
    }

    .transition-overlay.active {
      opacity: 1;
      pointer-events: all;
    }

    /* RESPONSIVE */
    @media (max-width: 640px) {
      .profile-card {
        width: 136px;
      }

      .card-inner {
        width: 136px;
        height: 136px;
      }

      .profiles-grid {
        gap: 22px;
      }

      .child-avatar {
        font-size: 3.5rem;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .manage-modal {
        border-radius: 22px;
      }

      .manage-header,
      .manage-body {
        padding-left: 22px;
        padding-right: 22px;
      }
    }

    @media (max-width: 420px) {
      .profile-card {
        width: 118px;
      }

      .card-inner {
        width: 118px;
        height: 118px;
      }

      .child-avatar {
        font-size: 3rem;
      }
    }

    .child-avatar-svg {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .profile-card.child:hover .child-avatar-svg svg {
      transform: scale(1.15) translateY(-4px) rotate(-5deg);
    }

    .child-avatar-svg svg {
      max-width: 118px;
      max-height: 125px;
      filter: drop-shadow(0 3px 12px rgba(0, 0, 0, 0.5));
    }
  </style>
</head>

<body>

  <canvas id="bg-canvas"></canvas>
  <div class="bg-overlay"></div>

  <!-- TOP BAR -->
  <nav class="top-bar">
    <div class="logo-wrap">
      <img src="./../../public/images/logo_with_character-removebg-preview.png" alt="YOPY">
    </div>
    <a href="<?= $logoutUrl ?>" class="btn-logout">Sign Out</a>
  </nav>

  <!-- PAGE -->
  <main class="page" id="page">
    <header class="header">
      <h1>Who's using <span class="yopy-text">YOPY</span>?</h1>
      <p>Choose a profile to continue</p>
    </header>
    <div class="profiles-grid" id="profilesGrid"></div>
    <div class="bottom-actions">
      <button class="btn-manage" onclick="openManageFlow()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3" />
          <path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
        Manage Profiles
      </button>
    </div>
  </main>

  <!-- ─── PIN MODAL ─── -->
  <div class="modal-backdrop" id="pinModal">
    <div class="modal" id="pinModalInner">
      <svg class="modal-parent-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="72" height="72">
        <defs>
          <linearGradient id="bg-grad2" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#663399" stop-opacity="0.5" />
            <stop offset="100%" stop-color="#3d2460" stop-opacity="0.9" />
          </linearGradient>
          <linearGradient id="fig-grad2" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#E6C7E6" />
            <stop offset="100%" stop-color="#A3779D" />
          </linearGradient>
          <filter id="glow2" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="4" result="blur" />
            <feComposite in="SourceGraphic" in2="blur" operator="over" />
          </filter>
        </defs>
        <circle cx="60" cy="60" r="56" fill="url(#bg-grad2)" stroke="#A3779D" stroke-width="2" stroke-opacity="0.5" filter="url(#glow2)" />
        <g fill="url(#fig-grad2)">
          <circle cx="60" cy="42" r="14" />
          <path d="M60 62c-15.5 0-28 10.5-31 24 0 3.3 2.7 6 6 6h50c3.3 0 6-2.7 6-6-3-13.5-15.5-24-31-24z" />
        </g>
      </svg>
      <h2>Parent Access</h2>
      <p id="pinModalDesc">Enter your 4-digit PIN to continue</p>
      <div class="pin-dots" id="pinDots">
        <div class="pin-dot" id="dot-0"></div>
        <div class="pin-dot" id="dot-1"></div>
        <div class="pin-dot" id="dot-2"></div>
        <div class="pin-dot" id="dot-3"></div>
      </div>
      <div class="pin-keypad">
        <button class="pin-key" onclick="pinInput('1')">1</button>
        <button class="pin-key" onclick="pinInput('2')">2</button>
        <button class="pin-key" onclick="pinInput('3')">3</button>
        <button class="pin-key" onclick="pinInput('4')">4</button>
        <button class="pin-key" onclick="pinInput('5')">5</button>
        <button class="pin-key" onclick="pinInput('6')">6</button>
        <button class="pin-key" onclick="pinInput('7')">7</button>
        <button class="pin-key" onclick="pinInput('8')">8</button>
        <button class="pin-key" onclick="pinInput('9')">9</button>
        <button class="pin-key" onclick="clearPin()">✕</button>
        <button class="pin-key" onclick="pinInput('0')">0</button>
        <button class="pin-key del" onclick="deletePin()">⌫</button>
      </div>
      <div class="pin-error" id="pinError">✕ Incorrect PIN. Try again.</div>
      <button class="forgot-pin-link" onclick="openForgotPin()">Forgot PIN?</button>
      <br>
      <button class="modal-cancel" onclick="closePinModal()">Cancel</button>
    </div>
  </div>

  <!-- ─── FORGOT PIN MODAL ─── -->
  <div class="modal-backdrop" id="forgotPinModal">
    <div class="modal modal-small">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="rgba(167,139,250,0.7)" stroke-width="1.5" style="margin:0 auto 16px;display:block;">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
      </svg>
      <h2>Forgot PIN?</h2>
      <p>Enter your account password to verify identity</p>
      <div class="modal-input-wrap">
        <input class="modal-input" type="password" id="forgotPasswordInput" placeholder="Account password" autocomplete="current-password">
      </div>
      <div class="modal-input-msg" id="forgotPasswordMsg"></div>
      <button class="btn-primary" style="margin-top:16px;" onclick="verifyForgotPassword()">Verify Password</button>
      <br><br>
      <button class="modal-cancel" onclick="closeForgotPin()">← Back to PIN</button>
    </div>
  </div>

  <!-- ─── RESET PIN MODAL ─── -->
  <div class="modal-backdrop" id="resetPinModal">
    <div class="modal modal-small">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="rgba(52,211,153,0.7)" stroke-width="1.5" style="margin:0 auto 16px;display:block;">
        <polyline points="20 6 9 17 4 12" />
      </svg>
      <h2>Reset PIN</h2>
      <p>Create a new 4-digit PIN</p>
      <div class="pin-dots" id="resetPinDots">
        <div class="pin-dot" id="rdot-0"></div>
        <div class="pin-dot" id="rdot-1"></div>
        <div class="pin-dot" id="rdot-2"></div>
        <div class="pin-dot" id="rdot-3"></div>
      </div>
      <div class="pin-keypad">
        <button class="pin-key" onclick="resetPinInput('1')">1</button>
        <button class="pin-key" onclick="resetPinInput('2')">2</button>
        <button class="pin-key" onclick="resetPinInput('3')">3</button>
        <button class="pin-key" onclick="resetPinInput('4')">4</button>
        <button class="pin-key" onclick="resetPinInput('5')">5</button>
        <button class="pin-key" onclick="resetPinInput('6')">6</button>
        <button class="pin-key" onclick="resetPinInput('7')">7</button>
        <button class="pin-key" onclick="resetPinInput('8')">8</button>
        <button class="pin-key" onclick="resetPinInput('9')">9</button>
        <button class="pin-key" onclick="clearResetPin()">✕</button>
        <button class="pin-key" onclick="resetPinInput('0')">0</button>
        <button class="pin-key del" onclick="deleteResetPin()">⌫</button>
      </div>
      <div class="pin-error" id="resetPinMsg" style="color:var(--success)"></div>
      <button class="modal-cancel" style="margin-top:14px;" onclick="closeResetPin()">Cancel</button>
    </div>
  </div>

  <!-- ─── MANAGE PROFILES MODAL ─── -->
  <div class="manage-modal-backdrop" id="manageModal">
    <div class="manage-modal">
      <div class="manage-header">
        <div class="manage-header-left">
          <div class="manage-header-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--violet-soft)" stroke-width="1.8">
              <circle cx="12" cy="8" r="4" />
              <path d="M6 20v-2a6 6 0 0 1 12 0v2" />
              <circle cx="19" cy="8" r="2.5" />
              <path d="M22 14v-1a2.5 2.5 0 0 0-5 0v1" />
            </svg>
          </div>
          <div>
            <h3>Manage Profiles</h3>
            <p>Add or remove child profiles</p>
          </div>
        </div>
        <button class="btn-close-manage" onclick="closeManageModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>
      <div class="manage-body">
        <div id="childrenList" class="children-list"></div>
        <div class="add-child-section">
          <div class="add-child-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Add Child Profile
          </div>
          <div class="add-child-form">
            <div class="form-group">
              <label class="form-label">Name <span style="color:var(--pink-accent)">*</span></label>
              <input class="form-input" type="text" id="childName" placeholder="e.g. Emma" maxlength="30" autocomplete="off">
              <div class="form-error-msg" id="childNameError"></div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Age</label>
                <input class="form-input" type="number" id="childAge" placeholder="e.g. 8" min="1" max="17">
              </div>
              <div class="form-group">
                <label class="form-label">Gender</label>
                <select class="form-select" id="childGender">
                  <option value="">Select...</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
            <button class="btn-add-child" id="btnAddChild" onclick="addChild()">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
              </svg>
              Add Child
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── CONFIRM DELETE DIALOG ─── -->
  <div class="confirm-backdrop" id="confirmDelete">
    <div class="confirm-box">
      <div class="confirm-icon">🗑️</div>
      <h3>Delete Profile?</h3>
      <p id="confirmDeleteMsg">This action cannot be undone.</p>
      <div class="confirm-actions">
        <button class="btn-confirm-cancel" onclick="cancelDelete()">Keep</button>
        <button class="btn-confirm-delete" onclick="confirmDeleteChild()">Delete</button>
      </div>
    </div>
  </div>

  <div class="transition-overlay" id="transitionOverlay"></div>

  <script>
    // ═══════════════════════════════════════════════════════════════
    // YOPY — accounts.php  JS  (DB-driven)
    // ═══════════════════════════════════════════════════════════════

    // ── DATA injected from PHP / DB ───────────────────────────────
    const userData = {
      parentName: <?= $jsParentName ?>,
      children: <?= $jsChildren ?>
    };
    // Buddy SVGs from buddies.php
    const BUDDY_SVGS = <?= json_encode($BUDDY_SVGS, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    // childConfigs maps a DB theme → visual config (dots colours)
    // The emoji now comes straight from the DB child record.
    const themeDotsMap = {
      'theme-rose': ["#fb7185", "#fda4af", "#ffe4e6"],
      'theme-teal': ["#2dd4bf", "#5eead4", "#ccfbf1"],
      'theme-blue': ["#60a5fa", "#93c5fd", "#dbeafe"],
      'theme-amber': ["#fbbf24", "#fcd34d", "#fef3c7"],
      'theme-mint': ["#34d399", "#6ee7b7", "#d1fae5"],
      'theme-sky': ["#38bdf8", "#7dd3fc", "#e0f2fe"],
    };
    // Fallback rotation for newly added children (before DB returns emoji)
    const fallbackConfigs = [{
        emoji: "🐼",
        theme: "theme-amber"
      },
      {
        emoji: "🐨",
        theme: "theme-mint"
      },
      {
        emoji: "🐙",
        theme: "theme-sky"
      },
      {
        emoji: "🦊",
        theme: "theme-rose"
      },
      {
        emoji: "🐸",
        theme: "theme-teal"
      },
      {
        emoji: "🦄",
        theme: "theme-blue"
      },

    ];

    let currentPin = "";
    let currentPinMode = "parent"; // "parent" | "manage"
    let pendingDeleteIndex = -1;

    // ── HELPER: call our own PHP AJAX handler ─────────────────────
    async function api(action, extra = {}) {
      const body = new URLSearchParams({
        action,
        ...extra
      });
      const res = await fetch(window.location.href, {
        method: 'POST',
        body
      });
      return res.json();
    }

    // ── PARENT SVG ────────────────────────────────────────────────
    const parentSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="88" height="88">
  <defs>
    <linearGradient id="bg-p" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#663399" stop-opacity="0.45"/>
      <stop offset="100%" stop-color="#3d2460" stop-opacity="0.85"/>
    </linearGradient>
    <linearGradient id="fig-p" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#E6C7E6"/>
      <stop offset="100%" stop-color="#A3779D"/>
    </linearGradient>
    <filter id="glow-p" x="-20%" y="-20%" width="140%" height="140%">
      <feGaussianBlur stdDeviation="4" result="blur"/>
      <feComposite in="SourceGraphic" in2="blur" operator="over"/>
    </filter>
  </defs>
  <circle cx="60" cy="60" r="56" fill="url(#bg-p)" stroke="#A3779D" stroke-width="2" stroke-opacity="0.45" filter="url(#glow-p)"/>
  <g fill="url(#fig-p)">
    <circle cx="60" cy="42" r="14"/>
    <path d="M60 62c-15.5 0-28 10.5-31 24 0 3.3 2.7 6 6 6h50c3.3 0 6-2.7 6-6-3-13.5-15.5-24-31-24z"/>
  </g>
</svg>`;

    // ── BUILD PROFILE GRID ────────────────────────────────────────

    function buildProfiles() {
      const grid = document.getElementById("profilesGrid");
      grid.innerHTML = "";

      const allProfiles = [{
          type: "parent",
          name: userData.parentName
        },
        ...userData.children.map((child, i) => ({
          type: "child",
          child_id: child.child_id,
          name: child.name,
          age: child.age,
          emoji: child.emoji || fallbackConfigs[i % fallbackConfigs.length].emoji,
          theme: child.theme || fallbackConfigs[i % fallbackConfigs.length].theme,
          buddy: child.buddy ?? null // ← THIS WAS MISSING
        }))
      ];

      allProfiles.forEach((p, idx) => {
        const card = document.createElement("div");
        const themeClass = p.type === "child" ? (p.theme || "theme-rose") : "";
        card.className = `profile-card ${p.type} ${themeClass}`;
        card.style.animationDelay = `${0.08 + idx * 0.1}s`;

        if (p.type === "parent") {
          card.innerHTML = `
        <div class="card-inner">
          <div class="parent-ring"></div>
          <div class="avatar-wrap">${parentSVG}</div>
        </div>
        <span class="profile-name">${escHtml(p.name)}</span>
        <span class="profile-label label-parent">Parent</span>`;
        } else {
          const dots = (themeDotsMap[p.theme] || themeDotsMap['theme-rose'])
            .map(c => `<div class="dot" style="background:${c}"></div>`).join("");

          card.innerHTML = `
        <div class="card-inner">
          ${p.buddy && BUDDY_SVGS[p.buddy] 
              ? `<div class="child-avatar-svg">${BUDDY_SVGS[p.buddy]}</div>` 
              : `<span class="child-avatar">${p.emoji}</span>`}
          <div class="child-dots">${dots}</div>
        </div>
        <span class="profile-name">${escHtml(p.name)}</span>
        <span class="profile-label label-child">Child</span>`;
        }

        card.addEventListener("click", () => selectProfile(p));
        grid.appendChild(card);
      });
    }

    function escHtml(s) {
      return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    // ── PROFILE SELECTION ─────────────────────────────────────────
    function selectProfile(p) {
      if (p.type === "parent") {
        currentPinMode = "parent";
        document.getElementById("pinModalDesc").textContent = "Enter your 4-digit PIN to continue";
        openPinModal();
      } else {
        // Child selected → set session server-side then redirect
        doTransition(async () => {
          const data = await api('select_child', {
            child_id: p.child_id
          });
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            console.error(data.message);
            document.getElementById("transitionOverlay").classList.remove("active");
          }
        });
      }
    }

    function doTransition(callback) {
      const o = document.getElementById("transitionOverlay");
      o.classList.add("active");
      setTimeout(callback, 500);
    }

    // ── PIN MODAL ─────────────────────────────────────────────────
    function openPinModal() {
      clearPin();
      document.getElementById("pinModal").classList.add("open");
    }

    function closePinModal() {
      document.getElementById("pinModal").classList.remove("open");
      clearPin();
    }

    function pinInput(d) {
      if (currentPin.length >= 4) return;
      currentPin += d;
      updateDots();
      if (currentPin.length === 4) setTimeout(verifyPin, 120);
    }

    function deletePin() {
      currentPin = currentPin.slice(0, -1);
      updateDots();
      hideError();
    }

    function clearPin() {
      currentPin = "";
      updateDots();
      hideError();
    }

    function updateDots() {
      for (let i = 0; i < 4; i++)
        document.getElementById(`dot-${i}`).classList.toggle("filled", i < currentPin.length);
    }

    // PIN is verified SERVER-SIDE via AJAX — no PIN stored in JS
    async function verifyPin() {
      const data = await api('verify_pin', {
        pin: currentPin
      });
      if (data.success) {
        closePinModal();
        if (currentPinMode === "manage") {
          openManageModal();
        } else {
          doTransition(() => {
            window.location.href = data.redirect;
          });
        }
      } else {
        document.getElementById("pinError").classList.add("show");
        const m = document.getElementById("pinModalInner");
        const shakes = [
          [-9, 0],
          [9, 0],
          [-6, 0],
          [5, 0],
          [-2, 0],
          [0, 0]
        ];
        shakes.forEach(([x], i) => setTimeout(() => m.style.transform = `translateX(${x}px)`, i * 55));
        setTimeout(clearPin, 650);
      }
    }

    function hideError() {
      document.getElementById("pinError").classList.remove("show");
    }

    // ── MANAGE PROFILES FLOW ──────────────────────────────────────
    function openManageFlow() {
      currentPinMode = "manage";
      document.getElementById("pinModalDesc").textContent = "Enter your PIN to access Manage Profiles";
      openPinModal();
    }

    function openManageModal() {
      renderChildrenList();
      clearAddForm();
      document.getElementById("manageModal").classList.add("open");
    }

    function closeManageModal() {
      document.getElementById("manageModal").classList.remove("open");
    }

    function renderChildrenList() {
      const list = document.getElementById("childrenList");
      list.innerHTML = "";

      if (userData.children.length === 0) {
        list.innerHTML = `<div class="children-empty">No child profiles yet. Add one below.</div>`;
        return;
      }

      userData.children.forEach((child, i) => {
        const emoji = child.emoji || fallbackConfigs[i % fallbackConfigs.length].emoji;
        const row = document.createElement("div");
        row.className = "child-row";
        row.id = `child-row-${i}`;
        row.style.animationDelay = `${i * 0.07}s`;
        row.innerHTML = `
          <span class="child-row-emoji">${emoji}</span>
          <div class="child-row-info">
          <div class="child-row-name">${escHtml(child.name)}</div>
          <div class="child-row-meta">
          ${child.age ? `<span class="child-meta-badge">Age ${child.age}</span>` : ""}
          </div>
          </div>
          <button class="btn-delete-child" onclick="promptDeleteChild(${i})">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
          <path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
          Delete
          </button>`;
        list.appendChild(row);
      });
    }

    // ── ADD CHILD (calls PHP then updates local data + grid) ──────
    function clearAddForm() {
      document.getElementById("childName").value = "";
      document.getElementById("childAge").value = "";
      document.getElementById("childGender").value = "";
      document.getElementById("childName").className = "form-input";
      document.getElementById("childNameError").className = "form-error-msg";
      document.getElementById("childNameError").textContent = "";
    }

    async function addChild() {
      const nameEl = document.getElementById("childName");
      const ageEl = document.getElementById("childAge");
      const nameErr = document.getElementById("childNameError");
      const btn = document.getElementById("btnAddChild");

      const name = nameEl.value.trim();
      const age = parseInt(ageEl.value) || null;

      if (!name) {
        nameEl.className = "form-input field-error";
        nameErr.textContent = "Name is required.";
        nameErr.className = "form-error-msg show";
        nameEl.focus();
        return;
      }

      btn.disabled = true;
      btn.textContent = "Saving…";

      const data = await api('add_child', {
        name,
        age: age ?? ""
      });

      btn.disabled = false;
      btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
  </svg> Add Child`;

      if (!data.success) {
        nameEl.className = "form-input field-error";
        nameErr.textContent = data.message || "An error occurred.";
        nameErr.className = "form-error-msg show";
        nameEl.focus();
        return;
      }

      nameEl.className = "form-input field-success";

      // Push new child into local data (with DB-returned id + defaults)
      userData.children.push({
        child_id: data.child_id,
        name: data.name,
        age: data.age,
        emoji: data.emoji || '🦊',
        theme: data.theme || 'theme-rose',
      });

      buildProfiles();
      renderChildrenList();
      clearAddForm();

      // Scroll new row into view
      const list = document.getElementById("childrenList");
      setTimeout(() => {
        const lastRow = list.lastElementChild;
        if (lastRow && lastRow.scrollIntoView)
          lastRow.scrollIntoView({
            behavior: "smooth",
            block: "nearest"
          });
      }, 100);
    }

    // ── DELETE CHILD ──────────────────────────────────────────────
    function promptDeleteChild(idx) {
      pendingDeleteIndex = idx;
      const child = userData.children[idx];
      document.getElementById("confirmDeleteMsg").textContent =
        `Are you sure you want to delete "${child.name}"? This cannot be undone.`;
      document.getElementById("confirmDelete").classList.add("open");
    }

    function cancelDelete() {
      pendingDeleteIndex = -1;
      document.getElementById("confirmDelete").classList.remove("open");
    }

    async function confirmDeleteChild() {
      if (pendingDeleteIndex < 0) return;

      const child = userData.children[pendingDeleteIndex];
      document.getElementById("confirmDelete").classList.remove("open");

      const row = document.getElementById(`child-row-${pendingDeleteIndex}`);
      if (row) row.classList.add("removing");

      const data = await api('delete_child', {
        child_id: child.child_id
      });

      if (data.success) {
        const idx = pendingDeleteIndex;
        pendingDeleteIndex = -1;
        setTimeout(() => {
          userData.children.splice(idx, 1);
          buildProfiles();
          renderChildrenList();
        }, 420);
      } else {
        if (row) row.classList.remove("removing");
        pendingDeleteIndex = -1;
        console.error(data.message);
      }
    }

    // ── FORGOT PIN ────────────────────────────────────────────────
    const FORGOT_PIN_COOLDOWN_MS = 3000;
    let forgotPinLoading = false;
    let forgotPinCooldownUntil = 0;

    function isForgotPinCoolingDown() {
      return Date.now() < forgotPinCooldownUntil;
    }

    function startForgotPinCooldown() {
      forgotPinCooldownUntil = Date.now() + FORGOT_PIN_COOLDOWN_MS;
    }

    function openForgotPin() {
      closePinModal();
      document.getElementById("forgotPasswordInput").value = "";
      document.getElementById("forgotPasswordMsg").className = "modal-input-msg";
      document.getElementById("forgotPasswordInput").className = "modal-input";
      document.getElementById("forgotPinModal").classList.add("open");
    }

    function closeForgotPin() {
      document.getElementById("forgotPinModal").classList.remove("open");
      openPinModal();
    }

    // Password verified SERVER-SIDE — no password stored in JS
    async function verifyForgotPassword() {
      if (forgotPinLoading || isForgotPinCoolingDown()) {
        return;
      }

      const inp = document.getElementById("forgotPasswordInput");
      const msg = document.getElementById("forgotPasswordMsg");

      forgotPinLoading = true;
      startForgotPinCooldown();

      try {
        const data = await api('verify_password', {
          password: inp.value
        });

        if (data.success) {
          inp.className = "modal-input success";
          msg.className = "modal-input-msg show success-msg";
          msg.textContent = "✓ Password verified!";
          setTimeout(() => {
            document.getElementById("forgotPinModal").classList.remove("open");
            openResetPin();
          }, 800);
        } else {
          inp.className = "modal-input error";
          msg.className = "modal-input-msg show error-msg";
          msg.textContent = "✕ Incorrect password. Please try again.";
          inp.focus();
        }
      } catch (error) {
        msg.className = "modal-input-msg show error-msg";
        msg.textContent = "✕ Too many attempts. Please wait a few seconds and try again.";
      } finally {
        forgotPinLoading = false;
      }
    }
    document.getElementById("forgotPasswordInput").addEventListener("keydown", e => {
      if (e.key === "Enter") {
        e.preventDefault();
        if (forgotPinLoading || isForgotPinCoolingDown()) {
          return;
        }
        verifyForgotPassword();
      }
    });

    // ── RESET PIN ─────────────────────────────────────────────────
    let resetPinValue = "";

    function openResetPin() {
      resetPinValue = "";
      updateResetDots();
      const msg = document.getElementById("resetPinMsg");
      msg.textContent = "";
      msg.style.color = "var(--success)";
      document.getElementById("resetPinModal").classList.add("open");
    }

    function closeResetPin() {
      document.getElementById("resetPinModal").classList.remove("open");
    }

    function resetPinInput(d) {
      if (resetPinValue.length >= 4) return;
      resetPinValue += d;
      updateResetDots();
      if (resetPinValue.length === 4) setTimeout(saveNewPin, 200);
    }

    function deleteResetPin() {
      resetPinValue = resetPinValue.slice(0, -1);
      updateResetDots();
    }

    function clearResetPin() {
      resetPinValue = "";
      updateResetDots();
    }

    function updateResetDots() {
      for (let i = 0; i < 4; i++)
        document.getElementById(`rdot-${i}`).classList.toggle("filled", i < resetPinValue.length);
    }

    // New PIN saved SERVER-SIDE
    async function saveNewPin() {
      const data = await api('reset_pin', {
        pin: resetPinValue
      });
      const msg = document.getElementById("resetPinMsg");

      if (data.success) {
        msg.textContent = "✓ PIN updated successfully!";
        msg.style.opacity = "1";
        setTimeout(() => {
          closeResetPin();
          clearPin();
          document.getElementById("pinModalDesc").textContent =
            currentPinMode === "manage" ?
            "Enter your new PIN to access Manage Profiles" :
            "Enter your new 4-digit PIN";
          openPinModal();
        }, 1000);
      } else {
        msg.textContent = "✕ " + (data.message || "Failed to save PIN");
        msg.style.color = "var(--danger)";
        msg.style.opacity = "1";
        setTimeout(clearResetPin, 900);
      }
    }

    // ── LOGOUT ───────────────────────────────────────────────────
    function handleLogout() {
      doTransition(() => {
        window.location.href = "<?= $logoutUrl ?>";
      });
    }

    // ── CLOSE ON BACKDROP CLICK ───────────────────────────────────
    document.getElementById("pinModal").addEventListener("click", e => {
      if (e.target.id === "pinModal") closePinModal();
    });
    document.getElementById("forgotPinModal").addEventListener("click", e => {
      if (e.target.id === "forgotPinModal") closeForgotPin();
    });
    document.getElementById("resetPinModal").addEventListener("click", e => {
      if (e.target.id === "resetPinModal") closeResetPin();
    });
    document.getElementById("manageModal").addEventListener("click", e => {
      if (e.target.id === "manageModal") closeManageModal();
    });
    document.getElementById("confirmDelete").addEventListener("click", e => {
      if (e.target.id === "confirmDelete") cancelDelete();
    });

    // ── KEYBOARD for ADD CHILD ───────────────────────────────────
    document.getElementById("childName").addEventListener("keydown", e => {
      if (e.key === "Enter") addChild();
    });
    document.getElementById("childAge").addEventListener("keydown", e => {
      if (e.key === "Enter") addChild();
    });
    document.getElementById("childName").addEventListener("input", () => {
      document.getElementById("childName").className = "form-input";
      document.getElementById("childNameError").className = "form-error-msg";
    });

    // ── PARTICLES BACKGROUND ──────────────────────────────────────
    (function() {
      const cv = document.getElementById("bg-canvas");
      const ctx = cv.getContext("2d");
      let W, H, particles = [];

      function resize() {
        W = cv.width = window.innerWidth;
        H = cv.height = window.innerHeight;
      }
      window.addEventListener("resize", resize);
      resize();

      const colors = [
        "rgba(124,58,237,",
        "rgba(167,139,250,",
        "rgba(196,181,253,",
        "rgba(232,121,249,",
        "rgba(139,92,246,",
      ];

      class P {
        constructor() {
          this.reset(true);
        }
        reset(init = false) {
          this.x = Math.random() * W;
          this.y = init ? Math.random() * H : H + 12;
          this.r = Math.random() * 1.8 + 0.3;
          this.vy = Math.random() * 0.38 + 0.07;
          this.vx = (Math.random() - 0.5) * 0.2;
          this.op = Math.random() * 0.5 + 0.04;
          this.c = colors[Math.floor(Math.random() * colors.length)];
        }
        update() {
          this.y -= this.vy;
          this.x += this.vx;
          if (this.y < -12) this.reset();
        }
        draw() {
          ctx.beginPath();
          ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
          ctx.fillStyle = this.c + this.op + ")";
          ctx.fill();
        }
      }

      for (let i = 0; i < 130; i++) particles.push(new P());

      function drawNebulas() {
        [
          [W * .14, H * .12, W * .3, "rgba(124,58,237,0.065)"],
          [W * .88, H * .78, W * .24, "rgba(232,121,249,0.055)"],
          [W * .5, H * .5, W * .2, "rgba(167,139,250,0.04)"],
        ].forEach(([x, y, r, c]) => {
          const g = ctx.createRadialGradient(x, y, 0, x, y, r);
          g.addColorStop(0, c);
          g.addColorStop(1, "transparent");
          ctx.fillStyle = g;
          ctx.fillRect(0, 0, W, H);
        });
      }

      function loop() {
        ctx.clearRect(0, 0, W, H);
        drawNebulas();
        particles.forEach(p => {
          p.update();
          p.draw();
        });
        requestAnimationFrame(loop);
      }
      loop();
    })();

    // ── INIT ──────────────────────────────────────────────────────
    buildProfiles();
  </script>
</body>

</html>