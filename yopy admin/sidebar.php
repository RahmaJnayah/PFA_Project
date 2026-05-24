<?php
// Determine active route for highlight
$currentAction = $_GET['action'] ?? 'dashboard';

function isActive(string $prefix, string $current): string {
    return str_starts_with($current, $prefix) ? 'active' : '';
}
?>
<nav class="sidebar">
  <style>
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-w); z-index: 60;
      background: rgba(13, 7, 24, 0.97);
      border-right: 1px solid var(--glass-border);
      display: flex; flex-direction: column;
      backdrop-filter: blur(20px);
      overflow-y: auto;
    }

    .sidebar-logo {
      display: flex; align-items: center; gap: 12px;
      padding: 22px 24px 18px;
      border-bottom: 1px solid var(--glass-border);
    }
    .sidebar-logo-mark {
      width: 38px; height: 38px; border-radius: 12px;
      background: linear-gradient(135deg, #7c3aed, #e879f9);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; box-shadow: 0 0 18px rgba(124,58,237,0.4);
      flex-shrink: 0;
    }
    .sidebar-logo-text {
      font-family: var(--font-display); font-size: 1.05rem;
      font-weight: 700; letter-spacing: 0.15em;
      background: linear-gradient(135deg, #f0eaff 0%, #c4b5fd 60%, #a78bfa 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .sidebar-logo-sub {
      font-size: 0.62rem; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--text-faint); margin-top: 1px;
    }

    .sidebar-section {
      padding: 20px 16px 8px;
    }
    .sidebar-section-label {
      font-size: 0.62rem; letter-spacing: 0.14em; text-transform: uppercase;
      color: var(--text-faint); padding: 0 8px; margin-bottom: 6px;
    }

    .nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 14px; border-radius: 11px;
      text-decoration: none; color: var(--text-muted);
      font-size: 0.855rem; font-weight: 400;
      transition: all 0.2s ease; margin-bottom: 2px;
      position: relative; overflow: hidden;
    }
    .nav-item::before {
      content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
      background: linear-gradient(to bottom, #7c3aed, #e879f9);
      border-radius: 0 2px 2px 0; opacity: 0; transition: opacity 0.2s;
    }
    .nav-item:hover { background: rgba(124,58,237,0.1); color: var(--lilac); }
    .nav-item.active {
      background: rgba(124,58,237,0.16); color: var(--lilac);
      font-weight: 500;
    }
    .nav-item.active::before { opacity: 1; }

    .nav-icon {
      width: 32px; height: 32px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; flex-shrink: 0;
      background: rgba(35,20,66,0.5); border: 1px solid rgba(167,139,250,0.1);
      transition: all 0.2s ease;
    }
    .nav-item:hover .nav-icon, .nav-item.active .nav-icon {
      background: rgba(124,58,237,0.2); border-color: rgba(167,139,250,0.25);
    }

    .nav-badge {
      margin-left: auto; font-size: 0.65rem; font-weight: 600;
      background: rgba(124,58,237,0.25); color: var(--violet-soft);
      padding: 2px 8px; border-radius: 20px; border: 1px solid rgba(124,58,237,0.3);
    }

    .sidebar-footer {
      margin-top: auto; padding: 16px;
      border-top: 1px solid var(--glass-border);
    }
    .admin-info {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: 11px;
      background: rgba(35,20,66,0.4); border: 1px solid var(--glass-border);
    }
    .admin-avatar {
      width: 32px; height: 32px; border-radius: 50%;
      background: linear-gradient(135deg, #7c3aed, #a855f7);
      display: flex; align-items: center; justify-content: center;
      font-size: 0.8rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .admin-name { font-size: 0.8rem; color: var(--text-primary); font-weight: 500; }
    .admin-role { font-size: 0.65rem; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.06em; }
  </style>

  <!-- Logo -->
  <div class="sidebar-logo">
    <div class="sidebar-logo-mark">✦</div>
    <div>
      <div class="sidebar-logo-text">YOPY</div>
      <div class="sidebar-logo-sub">Admin Console</div>
    </div>
  </div>

  <!-- Main nav -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Overview</div>

    <a href="index.php?action=dashboard" class="nav-item <?= isActive('dashboard', $currentAction) ?>">
      <div class="nav-icon">📊</div>
      Dashboard
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Accounts</div>

    <a href="index.php?action=users" class="nav-item <?= isActive('users', $currentAction) ?>">
      <div class="nav-icon">👤</div>
      Parent Accounts
    </a>

    <a href="index.php?action=children" class="nav-item <?= isActive('children', $currentAction) ?>">
      <div class="nav-icon">🌟</div>
      Child Profiles
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Content</div>

    <a href="index.php?action=characters" class="nav-item <?= isActive('characters', $currentAction) ?>">
      <div class="nav-icon">🦄</div>
      Characters
    </a>
  </div>

  <!-- Footer admin info -->
  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar">A</div>
      <div>
        <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_email'] ?? 'Admin') ?></div>
        <div class="admin-role">Super Admin</div>
      </div>
    </div>
  </div>
</nav>

<!-- Wrap main in a div for layout -->
<div class="main-content">
