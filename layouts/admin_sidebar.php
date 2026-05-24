<?php
// Determine active route for highlight
$currentAction = $_GET['action'] ?? 'dashboard';
$adminEmail = (string) ($_SESSION['admin_email'] ?? 'Admin');
$adminEmailSafe = htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8');
$adminInitialSafe = htmlspecialchars(strtoupper(substr($adminEmail !== '' ? $adminEmail : 'A', 0, 1)), ENT_QUOTES, 'UTF-8');

function isActive(string $prefix, string $current): string {
    return str_starts_with($current, $prefix) ? 'active' : '';
}
?>
<nav class="sidebar">
  <style>
    /* ── Sidebar Container (Aged Parchment/Dark Wood) ── */
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-w); z-index: 60;
      background: rgba(10, 5, 2, 0.98);
      border-right: 1px solid var(--glass-border);
      display: flex; flex-direction: column;
      backdrop-filter: blur(20px);
      overflow-y: auto;
    }

    /* ── Logo Section ── */
    .sidebar-logo {
      display: flex; align-items: center; gap: 12px;
      padding: 22px 24px 18px;
      border-bottom: 1px solid var(--glass-border);
    }
    .sidebar-logo-mark {
      width: 38px; height: 38px; border-radius: 12px;
      background: linear-gradient(135deg, #3a2418, #5a3822);
      border: 1px solid var(--rune-gold);
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 0 15px rgba(184, 122, 68, 0.3);
      color: var(--rune-gold);
      flex-shrink: 0;
    }
    .sidebar-logo-text {
      font-family: var(--font-display); font-size: 1.05rem;
      font-weight: 700; letter-spacing: 0.15em;
      background: linear-gradient(135deg, #ebc28e 0%, #b5783a 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .sidebar-logo-sub {
      font-size: 0.62rem; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--brown-dark); margin-top: 1px;
    }

    /* ── Navigation Sections ── */
    .sidebar-section {
      padding: 20px 16px 8px;
    }
    .sidebar-section-label {
      font-size: 0.62rem; letter-spacing: 0.14em; text-transform: uppercase;
      color: var(--brown-ink); padding: 0 8px; margin-bottom: 6px;
      font-family: var(--font-display);
    }

    .nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 14px; border-radius: 11px;
      text-decoration: none; color: var(--text-muted);
      font-size: 0.855rem; font-weight: 400;
      transition: all 0.2s ease; margin-bottom: 2px;
      position: relative; overflow: hidden;
    }
    
    /* Runic Accent Line */
    .nav-item::before {
      content: ''; position: absolute; left: 0; top: 20%; bottom: 20%; width: 3px;
      background: var(--rune-glow);
      border-radius: 0 2px 2px 0; opacity: 0; transition: opacity 0.2s;
      box-shadow: 0 0 8px var(--ember);
    }

    .nav-item:hover { 
      background: rgba(120, 70, 40, 0.15); 
      color: var(--brown-light); 
    }
    
    .nav-item.active {
      background: rgba(184, 122, 68, 0.12); 
      color: var(--rune-glow);
      font-weight: 500;
    }
    .nav-item.active::before { opacity: 1; }

    .nav-icon {
      width: 32px; height: 32px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      background: rgba(20, 12, 8, 0.6); 
      border: 1px solid var(--glass-border);
      color: var(--text-muted);
      transition: all 0.2s ease;
    }
    .nav-item:hover .nav-icon {
      background: rgba(58, 36, 24, 0.8); 
      border-color: var(--rune-gold);
      color: var(--brown-light);
      transform: scale(1.05);
    }
    .nav-item.active .nav-icon {
      background: rgba(58, 36, 24, 0.8); 
      border-color: var(--rune-gold);
      color: var(--rune-glow);
      transform: scale(1.05);
      box-shadow: inset 0 0 8px rgba(184, 122, 68, 0.2);
    }

    /* ── Sidebar Footer (Admin Profile) ── */
    .sidebar-footer {
      margin-top: auto; padding: 16px;
      border-top: 1px solid var(--glass-border);
    }
    .admin-info {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: 11px;
      background: rgba(20, 12, 8, 0.5); 
      border: 1px solid var(--glass-border);
    }
    .admin-avatar {
      width: 32px; height: 32px; border-radius: 50%;
      background: linear-gradient(135deg, #5a3822, #3a2418);
      border: 1px solid var(--rune-gold);
      display: flex; align-items: center; justify-content: center;
      font-size: 0.8rem; font-weight: 700; color: var(--rune-gold); flex-shrink: 0;
    }
    .admin-name { font-size: 0.8rem; color: var(--text-primary); font-weight: 500; }
    .admin-role { font-size: 0.65rem; color: var(--brown-dark); text-transform: uppercase; letter-spacing: 0.06em; }
  </style>

  <div class="sidebar-logo">
    <div class="sidebar-logo-mark">
      <svg xmlns="http://www.w3.org/2003/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
      </svg>
    </div>
    <div>
      <div class="sidebar-logo-text">YOPY</div>
      <div class="sidebar-logo-sub">Ancient Sanctum</div>
    </div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">The Ledger</div>

    <a href="<?= $basePath ?>/admin.php?action=dashboard" class="nav-item <?= isActive('dashboard', $currentAction) ?>">
      <div class="nav-icon">
        <svg xmlns="http://www.w3.org/2003/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
        </svg>
      </div>
      Dashboard
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Soul Records</div>

    <a href="<?= $basePath ?>/admin.php?action=users" class="nav-item <?= isActive('users', $currentAction) ?>">
      <div class="nav-icon">
        <svg xmlns="http://www.w3.org/2003/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      Parent Accounts
    </a>

    <a href="<?= $basePath ?>/admin.php?action=children" class="nav-item <?= isActive('children', $currentAction) ?>">
      <div class="nav-icon">
        <svg xmlns="http://www.w3.org/2003/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
      </div>
      Child Profiles
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Manifestations</div>

    <a href="<?= $basePath ?>/admin.php?action=characters" class="nav-item <?= isActive('characters', $currentAction) ?>">
      <div class="nav-icon">
        <svg xmlns="http://www.w3.org/2003/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
        </svg>
      </div>
      Characters
    </a>

    <a href="<?= $basePath ?>/admin.php?action=games" class="nav-item <?= isActive('games', $currentAction) ?>">
      <div class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="6" width="20" height="12" rx="3"/>
          <circle cx="8" cy="12" r="2"/>
          <circle cx="16" cy="10" r="1"/>
          <circle cx="18" cy="14" r="1"/>
        </svg>
      </div>
      Games
    </a>
  </div>

  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar"><?= $adminInitialSafe ?></div>
      <div>
        <div class="admin-name"><?= $adminEmailSafe ?></div>
        <div class="admin-role">High Overseer</div>
      </div>
    </div>
  </div>
</nav>

<div class="main-content">