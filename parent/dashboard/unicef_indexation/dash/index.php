<?php
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
for ($i = 0; $i < 5; $i++) {
  $basePath = dirname($basePath);
}
$basePath = $basePath === '/' ? '' : $basePath;

session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'parent') {
  header('Location: ' . $basePath . '/auth/login');
  exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Sentiment Hub — Track your children's emotional wellbeing with real-time analytics and AI-powered insights."/>
  <title>Sentiment Hub | Children's Emotional Dashboard</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>
  <!-- Chart.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <!-- Three.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <!-- App CSS -->
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="Guide.css"/>
  <script>
    window.SH_BASE_PATH = "<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>";
  </script>
</head>
<body>

<!-- THREE.JS CANVAS -->
<canvas id="bg-three" aria-hidden="true"></canvas>

<!-- ═══════════════════════════════════════════════════════════ CONFIRM DIALOG -->
<div class="confirm-overlay" id="confirmOverlay" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
  <div class="confirm-box">
    <div class="confirm-icon">⚠️</div>
    <div class="confirm-title" id="confirmTitle">Are you sure?</div>
    <div class="confirm-desc" id="confirmDesc">This action cannot be undone.</div>
    <div class="confirm-actions">
      <button class="btn btn-ghost" id="confirmCancel">Cancel</button>
      <button class="btn btn-danger" id="confirmOk">Confirm</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ LIBRARY MODAL -->
<div class="modal-overlay" id="libraryModal" role="dialog" aria-modal="true" aria-labelledby="libraryModalTitle">
  <div class="modal-box" style="max-width:780px">
    <div class="modal-header">
      <h2 id="libraryModalTitle">📚 News Library</h2>
      <button class="icon-btn" id="closeLibraryModal" aria-label="Close library">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="modal-body" id="libraryModalBody"></div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ EDIT CHILD MODAL -->
<div class="modal-overlay" id="editChildModal" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-header">
      <h2>Edit Child Profile</h2>
      <button class="icon-btn" id="closeEditModal" aria-label="Close"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editChildId"/>
      <div class="form-group">
        <label class="form-label" for="editChildName">Child Name</label>
        <input class="form-input" id="editChildName" type="text" placeholder="Child's name"/>
      </div>
      <div class="form-group">
        <label class="form-label" for="editChildAge">Age (optional)</label>
        <input class="form-input" id="editChildAge" type="number" min="3" max="17" placeholder="Age"/>
      </div>
      <div class="form-group">
        <label class="form-label" for="editChildEmoji">Avatar Emoji</label>
        <select class="form-input" id="editChildEmoji">
          <option value="🧒">🧒 Neutral</option>
          <option value="👧">👧 Girl</option>
          <option value="👦">👦 Boy</option>
          <option value="🧑">🧑 Teen</option>
          <option value="👶">👶 Toddler</option>
          <option value="🦊">🦊 Fox</option>
          <option value="🦸">🦸 Hero</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="document.getElementById('editChildModal').classList.remove('active')">Cancel</button>
      <button class="btn btn-primary" id="saveEditChild">Save Changes</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ ARTICLE MODAL -->
<div class="modal-overlay" id="articleModal" role="dialog" aria-modal="true">
  <div class="modal-box" style="max-width:640px">
    <div class="modal-header">
      <h2 id="articleModalTitle">Article</h2>
      <button class="icon-btn" id="closeArticleModal" aria-label="Close"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="modal-body" id="articleModalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="document.getElementById('articleModal').classList.remove('active')">Close</button>
      <button class="btn btn-primary" onclick="showToast('Saved to reading list!','success');document.getElementById('articleModal').classList.remove('active')">
        <span class="material-symbols-outlined" style="font-size:16px">bookmark</span> Save
      </button>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════════════════ LAYOUT WRAPPER -->
<div class="app-layout" id="appLayout">

  <!-- ─── SIDEBAR (desktop) / OFFCANVAS (mobile) ─── -->
  <!-- Mobile Offcanvas Backdrop -->
  <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <!-- Mobile close button -->
    <button class="sidebar-close-btn d-md-none" id="sidebarCloseBtn" onclick="closeSidebar()" aria-label="Close menu">
      <span class="material-symbols-outlined">close</span>
    </button>

    <div class="sidebar-top">
      <div class="sidebar-logo" id="sidebarLogo" tabindex="0" aria-label="Sentiment Hub">🧠</div>
      <span class="sidebar-brand">Sentiment Hub</span>
    </div>

    <nav class="sidebar-nav" aria-label="Primary navigation">
      <button class="nav-item active" data-page="dashboard" aria-label="Dashboard">
        <span class="material-symbols-outlined mat">grid_view</span>
        <span class="nav-label">Dashboard</span>
      </button>
      <button class="nav-item" data-page="moodmap" aria-label="Mood Map">
        <span class="material-symbols-outlined mat">explore</span>
        <span class="nav-label">Mood Map</span>
      </button>

      <div class="nav-section">Children</div>
      <button class="nav-item" data-page="manage-children" aria-label="Manage Children">
        <span class="material-symbols-outlined mat">groups</span>
        <span class="nav-label">Manage Children</span>
      </button>
      <button class="nav-item" data-page="add-child" aria-label="Add Child">
        <span class="material-symbols-outlined mat">person_add</span>
        <span class="nav-label">Add Child</span>
      </button>

      <div class="nav-section">Resources</div>
      <button class="nav-item" data-page="news" aria-label="News and Insights">
        <span class="material-symbols-outlined mat">newspaper</span>
        <span class="nav-label">News &amp; Insights</span>
      </button>
      <button class="nav-item" data-page="reports" aria-label="PDF Reports">
        <span class="material-symbols-outlined mat">picture_as_pdf</span>
        <span class="nav-label">PDF Reports</span>
      </button>
      <button class="nav-item" data-page="notifications" aria-label="Notifications">
        <span class="material-symbols-outlined mat">notifications</span>
        <span class="nav-label">Notifications</span>
        <span class="nav-badge" id="navNotifBadge"></span>
      </button>
    </nav>

    <div class="sidebar-bottom">
      <button class="nav-item" data-page="profile" aria-label="Profile">
        <span class="material-symbols-outlined mat">person</span>
        <span class="nav-label">Profile</span>
      </button>
      <button class="nav-item" data-page="settings" aria-label="Settings">
        <span class="material-symbols-outlined mat">settings</span>
        <span class="nav-label">Settings</span>
      </button>
    </div>
  </aside>

  <!-- ─── MAIN CONTENT ─── -->
  <div class="main-content" id="main">

    <!-- TOPBAR -->
    <header class="topbar" role="banner">
      <button class="icon-btn" id="sidebarToggle" aria-label="Toggle navigation menu">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <h1 class="topbar-title" id="pageTitle">Dashboard</h1>

      <div class="topbar-actions">
        <button class="icon-btn" id="themeToggleBtn" aria-label="Toggle theme">
          <span class="material-symbols-outlined" id="themeIcon">light_mode</span>
        </button>
        <button class="icon-btn position-relative" id="notifTopBtn" aria-label="Notifications">
          <span class="material-symbols-outlined">notifications</span>
          <div class="notif-dot" id="notifDot"></div>
        </button>
        <div class="avatar-topbar" id="profileTopBtn" role="button" tabindex="0" aria-label="Go to profile">
          <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAlr97Z5omvQhc2RW7kBaDTZKNfEQHJuNntt8xo5WPzAgGPj9EQWW7GnNqDVFkQtIW6zIFqNStbRN2R2nISVI2rcpCncH10WWasCgHm7nHJIJbqjgIzmZjPpAwXF9bqORn_do7Fkevp9Wb3cq2FJUcYcu9D4NAYVDXxMgD40XzszF0hNsqLFFXTlMvw0reRDJx2NGjIhOie2SnlG7PFW6zMPHsywm4VPdzO8csmFLfWV9tvTsfMkg6c2G3EwdG0aerMltgXCePVBEQ"
            alt="Profile photo" width="36" height="36"/>
        </div>
        
          <button class="dropdown-item" role="menuitem" id="logoutBtn" style="background:var(--blueberry);color:var(--blue);"><a href="../../../../auth/modeChose.php">BACK</a></button>
      </div>
    </header>

    <!-- ─── PAGES ─── -->
    <main id="pageContainer" role="main">

      <!-- ══ DASHBOARD ══ -->
      <article class="page active" id="dashboardPage">
        <div class="page-inner">

          <!-- Stats Row -->
          <div class="row g-3 mb-4" role="list" aria-label="Key metrics">
            <div class="col-6 col-xl-3" role="listitem">
              <div class="stat-card">
                <div class="stat-icon" style="background:rgba(105,218,255,0.12);color:var(--cyan)">
                  <span class="material-symbols-outlined">sentiment_very_satisfied</span>
                </div>
                <div class="stat-value gradient-text" id="statBalance">--</div>
                <div class="stat-label">Balance Score</div>
                <div class="stat-trend up" id="statBalanceTrend">--</div>
              </div>
            </div>
            <div class="col-6 col-xl-3" role="listitem">
              <div class="stat-card">
                <div class="stat-icon" style="background:rgba(166,140,255,0.12);color:var(--violet)">
                  <span class="material-symbols-outlined">child_care</span>
                </div>
                <div class="stat-value" style="color:var(--violet)" id="statChildren">--</div>
                <div class="stat-label">Active Profiles</div>
                <div class="stat-trend" style="color:var(--muted)">All monitored</div>
              </div>
            </div>
            <div class="col-6 col-xl-3" role="listitem">
              <div class="stat-card">
                <div class="stat-icon" style="background:rgba(255,209,102,0.12);color:var(--gold)">
                  <span class="material-symbols-outlined">trending_up</span>
                </div>
                <div class="stat-value" style="color:var(--gold)" id="statGrowth">--</div>
                <div class="stat-label">Weekly Growth</div>
                <div class="stat-trend" id="statGrowthNote">--</div>
              </div>
            </div>
            <div class="col-6 col-xl-3" role="listitem">
              <div class="stat-card">
                <div class="stat-icon" style="background:rgba(78,205,196,0.12);color:var(--mint)">
                  <span class="material-symbols-outlined">notifications_active</span>
                </div>
                <div class="stat-value" style="color:var(--mint)" id="statNotifs">--</div>
                <div class="stat-label">New Alerts</div>
                <div class="stat-trend" style="color:var(--coral)">1 critical</div>
              </div>
            </div>
          </div>

          <!-- Hero Grid -->
          <div class="row g-3 mb-4">
            <!-- Score Ring -->
            <div class="col-12 col-lg-4">
              <div class="card h-100 d-flex flex-column align-items-center justify-content-center p-4">
                <div class="score-wrap">
                  <div class="score-ring animate-float">
                    <svg viewBox="0 0 200 200" role="img" aria-label="Score ring 84/100">
                      <defs>
                        <linearGradient id="scoreGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stop-color="#69DAFF"/>
                          <stop offset="100%" stop-color="#A68CFF"/>
                        </linearGradient>
                      </defs>
                      <circle cx="100" cy="100" r="86" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="10"/>
                      <circle class="score-progress" cx="100" cy="100" r="86" fill="none"
                        stroke="url(#scoreGrad)" stroke-width="10" stroke-linecap="round"
                        stroke-dasharray="540" stroke-dashoffset="86"
                        style="filter:drop-shadow(0 0 10px rgba(105,218,255,0.5))"/>
                    </svg>
                    <div class="score-center">
                        <div class="score-num" id="scoreNum">--</div>
                      <div class="score-lbl">Balance Score</div>
                        <span class="chip chip-cyan mt-2" style="font-size:10px" id="scoreChip">--</span>
                    </div>
                  </div>
                  <p class="label text-center mt-3">Weekly Emotional Balance</p>
                </div>
              </div>
            </div>
            <!-- Emotion Characters -->
            <div class="col-12 col-lg-8">
              <div class="card p-4 h-100">
                <span class="label">Emotional Summary</span>
                <h2 class="gradient-text" style="font-family:var(--fh);font-size:clamp(18px,3vw,22px);font-weight:800;margin:6px 0 3px">Your Child's Emotional World</h2>
                <p style="font-size:12px;color:var(--muted);margin-bottom:18px">Emotional states through friendly companions</p>
                <div class="row g-2" id="summaryGrid"></div>
              </div>
            </div>
          </div>

          <!-- Pulse Chart -->
          <div class="pulse-section mb-4">
            <div class="pulse-hdr">
              <div>
                <span class="label">Emotional Pulse</span>
                <h3 style="font-family:var(--fh);font-size:18px;font-weight:700;margin-top:3px">Continuous Sentiment Tracking</h3>
                <p style="font-size:12px;color:var(--muted);margin-top:1px">Active sessions <span id="timeRangeLabel">today</span></p>
              </div>
              <div class="range-controls" role="group" aria-label="Date range">
                <div class="range-field">
                  <span>From</span>
                  <input type="date" id="rangeStart" class="range-input" />
                </div>
                <div class="range-field">
                  <span>To</span>
                  <input type="date" id="rangeEnd" class="range-input" />
                </div>
                <button class="btn btn-ghost btn-sm" id="applyRange">Apply</button>
              </div>
            </div>
            <div style="height:180px;position:relative">
              <canvas id="pulseChart" aria-label="Wellness trend" role="img"></canvas>
            </div>
            <div class="time-label-row" id="timeLabelRow">
              <span>08:00</span><span>12:00</span><span>16:00</span><span>20:00</span><span>00:00</span>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-lg-4">
              <div class="card p-4 h-100">
                <div class="section-hdr mb-3">
                  <div>
                    <h3>Insights</h3>
                    <p>Signals derived from recent sessions</p>
                  </div>
                </div>
                <div class="insights-list" id="insightsList"></div>
              </div>
            </div>
            <div class="col-12 col-lg-8">
              <div class="card p-4 h-100">
                <div class="section-hdr mb-3">
                  <div>
                    <h3>Analysis History</h3>
                    <p>Chronological sessions in the selected range</p>
                  </div>
                  <span class="chip chip-cyan" id="historyCount">0 entries</span>
                </div>
                <div class="table-wrap">
                  <table class="analysis-table" aria-label="Analysis history">
                    <thead>
                      <tr>
                        <th>Child</th>
                        <th>Session</th>
                        <th>State</th>
                        <th>Confidence</th>
                        <th>Game</th>
                      </tr>
                    </thead>
                    <tbody id="analysisTableBody">
                      <tr><td colspan="5" class="muted">Loading analysis…</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-lg-7">
              <div class="card p-4 h-100">
                <div class="section-hdr mb-3">
                  <div>
                    <h3>Game Impact</h3>
                    <p>Average balance score by game in the selected range</p>
                  </div>
                </div>
                <div style="height:200px;position:relative">
                  <canvas id="gameImpactChart" role="img" aria-label="Game impact chart"></canvas>
                  <div class="chart-empty" id="gameImpactEmpty">No game data yet.</div>
                </div>
              </div>
            </div>
            <div class="col-12 col-lg-5">
              <div class="card p-4 h-100">
                <div class="section-hdr mb-3">
                  <div>
                    <h3>Data Quality</h3>
                    <p>Signal reliability across sessions</p>
                  </div>
                </div>
                <div style="height:200px;position:relative">
                  <canvas id="dataQualityChart" role="img" aria-label="Data quality chart"></canvas>
                  <div class="chart-empty" id="dataQualityEmpty">No data quality yet.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- News Preview -->
          <div class="section-hdr">
            <div>
              <h3>News &amp; Insights</h3>
              <p>Curated for your family profile</p>
            </div>
            <button class="btn btn-ghost btn-sm" id="viewLibraryBtn">View Library →</button>
          </div>
          <div class="dash-news-row" id="dashNewsRow" aria-label="News cards"></div>

        </div>
      </article>

      <!-- ══ MOOD MAP ══ -->
      <article class="page" id="moodmapPage">
        <div class="page-inner">
          <div class="page-hdr">
            <button class="back-btn" onclick="showPage('dashboard')"><span class="material-symbols-outlined">arrow_back</span></button>
            <div class="page-hdr-info">
              <span class="label">Weekly Overview</span>
              <h2 class="page-hdr-title gradient-text">Mood Map</h2>
            </div>
          </div>
          <div class="week-grid mb-4" id="weekMapGrid" role="list"></div>
          <div class="detail-panel mb-4" id="dayDetailPanel"></div>
          <div class="card p-4">
            <div class="section-hdr mb-3">
              <div>
                <h3>Weekly Emotion Trends</h3>
                <p>Joy, Calm &amp; Frustration across the week</p>
              </div>
              <div class="d-flex gap-3 flex-wrap">
                <span class="d-flex align-items-center gap-1" style="font-size:12px"><span style="width:10px;height:10px;border-radius:50%;background:var(--gold);display:inline-block"></span>Joy</span>
                <span class="d-flex align-items-center gap-1" style="font-size:12px"><span style="width:10px;height:10px;border-radius:50%;background:var(--cyan);display:inline-block"></span>Calm</span>
                <span class="d-flex align-items-center gap-1" style="font-size:12px"><span style="width:10px;height:10px;border-radius:50%;background:var(--coral);display:inline-block"></span>Frustration</span>
              </div>
            </div>
            <div style="height:180px;position:relative">
              <canvas id="weeklyChart" role="img" aria-label="Weekly emotion trends"></canvas>
            </div>
          </div>
        </div>
      </article>

      <!-- ══ MANAGE CHILDREN ══ -->
      <article class="page" id="manageChildrenPage">
        <div class="page-inner">
          <div class="page-hdr">
            <button class="back-btn" onclick="showPage('dashboard')"><span class="material-symbols-outlined">arrow_back</span></button>
            <div class="page-hdr-info flex-grow-1">
              <span class="label">Family Management</span>
              <h2 class="page-hdr-title">Manage <span class="gradient-text">Children</span></h2>
            </div>
            <button class="btn btn-primary" onclick="showPage('add-child')">
              <span class="material-symbols-outlined" style="font-size:16px">person_add</span> Add Child
            </button>
          </div>
          <div class="children-grid mb-4" id="childrenGrid" aria-live="polite"></div>
          <div class="activity-log">
            <h3 style="font-family:var(--fh);font-size:16px;font-weight:700;margin-bottom:14px">Recent Activity</h3>
            <div id="activityLog"></div>
          </div>
        </div>
      </article>

      <!-- ══ ADD CHILD ══ -->
      <article class="page" id="addChildPage">
        <div class="page-inner">
          <div class="page-hdr">
            <button class="back-btn" onclick="showPage('manage-children')"><span class="material-symbols-outlined">arrow_back</span></button>
            <div class="page-hdr-info">
              <span class="label">Family Management</span>
              <h2 class="page-hdr-title gradient-text">Add Child Profile</h2>
            </div>
          </div>
          <div class="form-wrap">
            <div class="form-group">
              <label class="form-label" id="avatarPickerLabel">Choose Avatar</label>
              <div class="avatar-picker">
                <div class="avatar-preview" id="avatarPreview" role="img" aria-label="Selected avatar">🧒</div>
                <div class="avatar-options">
                  <button type="button" class="avatar-opt sel" data-emoji="🧒" aria-pressed="true">🧒</button>
                  <button type="button" class="avatar-opt" data-emoji="👧" aria-pressed="false">👧</button>
                  <button type="button" class="avatar-opt" data-emoji="👦" aria-pressed="false">👦</button>
                  <button type="button" class="avatar-opt" data-emoji="🧑" aria-pressed="false">🧑</button>
                  <button type="button" class="avatar-opt" data-emoji="👶" aria-pressed="false">👶</button>
                  <button type="button" class="avatar-opt" data-emoji="🦸" aria-pressed="false">🦸</button>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="childName">Child Name <span style="color:var(--coral)">*</span></label>
              <input class="form-input" id="childName" type="text" placeholder="Enter child's name" required autocomplete="off"/>
              <div class="form-error" id="nameError">Please enter the child's name.</div>
            </div>
            <div class="form-group">
              <label class="form-label" for="childAge">Age (optional)</label>
              <input class="form-input" id="childAge" type="number" min="3" max="17" placeholder="Age"/>
            </div>
            <button class="submit-btn" id="addChildSubmit" type="button">✨ Save Child Profile</button>
          </div>
        </div>
      </article>

      <!-- ══ NEWS ══ -->
      <article class="page" id="newsPage">
        <div class="page-inner">

          <!-- Page header -->
          <div class="page-hdr">
            <button class="back-btn" onclick="showPage('dashboard')"><span class="material-symbols-outlined">arrow_back</span></button>
            <div class="page-hdr-info flex-grow-1">
              <span class="label">Curated Resources</span>
              <h2 class="page-hdr-title gradient-text">Pulse Insights</h2>
            </div>
            <!-- Indexation status badge -->
            <div id="indexationBadge" style="display:none;align-items:center;gap:8px;padding:6px 14px;border-radius:20px;background:rgba(105,218,255,0.08);border:1px solid rgba(105,218,255,0.2);font-size:11px;color:var(--cyan)">
              <span class="material-symbols-outlined" style="font-size:14px">database</span>
              <span id="indexationBadgeText">0 indexed</span>
            </div>
          </div>

          <!-- ── Search bar with icon + clear button ── -->
          <div class="news-search-wrap mb-3">
            <span class="material-symbols-outlined news-search-icon">search</span>
            <input type="text" id="searchInput" class="news-search-input" placeholder="Search child care topics, advice, insights…" autocomplete="off" list="newsSearchOptions"/>
            <datalist id="newsSearchOptions">
              <option value="children"></option>
              <option value="issues"></option>
              <option value="food"></option>
            </datalist>
            <button class="news-search-clear" id="searchClearBtn" aria-label="Clear search" style="display:none">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>

          <!-- ── Category recommendations (quick-jump chips) ── -->
          <div class="cat-recs mb-4" id="catRecs" aria-label="Recommended topics">
            <span class="cat-recs-label">Browse by topic:</span>
            <div class="cat-recs-chips" id="catRecsChips">
              <button class="cat-rec-chip active" data-cat="All"><span>✨</span> All</button>
              <button class="cat-rec-chip" data-cat="Psychology"><span>🧠</span> Psychology</button>
              <button class="cat-rec-chip" data-cat="Health"><span>💊</span> Health</button>
              <button class="cat-rec-chip" data-cat="Education"><span>📚</span> Education</button>
              <button class="cat-rec-chip" data-cat="Research"><span>🔬</span> Research</button>
              <button class="cat-rec-chip" data-cat="Development"><span>🌱</span> Development</button>
              <button class="cat-rec-chip" data-cat="Nutrition"><span>🥦</span> Nutrition</button>
              <button class="cat-rec-chip" data-cat="Safety"><span>🛡️</span> Safety</button>
            </div>
          </div>

          <!-- ── Hero (hides when searching) ── -->
          <div class="news-hero mb-4" id="newsHero">
            <div class="news-hero-content">
              <span class="chip chip-coral mb-3">🔥 Daily Deep Dive</span>
              <h2 style="font-family:var(--fh);font-size:clamp(18px,4vw,24px);font-weight:800;margin-bottom:10px;line-height:1.3">Understanding <span class="gradient-text">Frustration</span> Peaks in Children</h2>
              <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.7">New neurological data suggests late-afternoon emotional spikes correlate with sensory saturation and low blood sugar. A 15-minute outdoor break can reduce frustration by up to 40%.</p>
              <div class="d-flex gap-3 align-items-center flex-wrap">
                <button class="btn btn-primary" onclick="openArticleModal(1)">Read Full Analysis →</button>
                <span style="font-size:12px;color:var(--muted)">5 min read</span>
              </div>
            </div>
            <div class="news-hero-img">
              <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuD7OWklqTnAc9Y42CKyz71NLS0GVq6HU9CzYmBd90shV_Bhp4Ej_LD45juxm7-HK06HkqFTegKxdefagnOkZO1TcOoVy_EPyV26-Nr7KoPFcbZnbF8_QQJHeF0y6-8AwbsdKLNRUH0DwZhjDiUBxM7kRzXGwLL8Ws4dH47rAbUAIy3qkRcTgRK6pqd-FKsrbYKF6AIA144tyjLFFP1movnEzy0AhQC-6b-41efqi8tZnhFkriVZbPaKtf07jB45MRGoNlFDkAxX3Ds"
                alt="Child playing outdoors" loading="lazy"/>
            </div>
          </div>

          <!-- ── Search results info banner (shown only during/after search) ── -->
          <div id="resultsContainer" style="display:none"></div>

          <!-- ── Filter pills (classic category tabs for local news) ── -->
          <div class="filter-row mb-3" id="filterRow" role="group" aria-label="News category filter">
            <button class="filter-pill active" data-cat="All">All Insights</button>
            <button class="filter-pill" data-cat="Psychology">Psychology</button>
            <button class="filter-pill" data-cat="Health">Health</button>
            <button class="filter-pill" data-cat="Education">Education</button>
            <button class="filter-pill" data-cat="Research">Research</button>
          </div>

          <!-- ── Article grid ── -->
          <div class="news-grid" id="newsGrid" aria-live="polite"></div>

        </div>
      </article>

      <!-- ══ REPORTS ══ -->
      <article class="page" id="reportsPage">
        <div class="page-inner">
          <div class="page-hdr">
            <button class="back-btn" onclick="showPage('dashboard')"><span class="material-symbols-outlined">arrow_back</span></button>
            <div class="page-hdr-info flex-grow-1">
              <span class="label">Intelligence Archive</span>
              <h2 class="page-hdr-title">PDF <span class="gradient-text">Reports</span></h2>
            </div>
            <button class="btn btn-primary" id="generateReportBtn">
              <span class="material-symbols-outlined" style="font-size:16px">add_chart</span>
              <span class="d-none d-sm-inline">Generate New</span>
            </button>
          </div>

          <div class="report-preview mb-4">
            <div class="report-preview-header">
              <div>
                <div class="report-logo">🧠 Sentiment Hub</div>
                <div style="font-size:12px;color:rgba(26,11,46,0.45);margin-top:3px" id="reportRange">Report Window — --</div>
              </div>
              <div class="text-end">
                <div style="font-family:var(--fh);font-size:20px;font-weight:800;color:#1A0B2E" id="reportChildCount">-- Children</div>
                <div style="font-size:11px;color:rgba(26,11,46,0.45)" id="reportSubtitle">Based on analysis history</div>
              </div>
            </div>
            <div class="report-placeholder">📊 Emotion trend visualization preview</div>
            <div class="row g-3">
              <div class="col-6 col-sm-3 text-center"><div class="rms-val" id="reportBalance">--</div><div class="rms-lbl">Balance Score</div></div>
              <div class="col-6 col-sm-3 text-center"><div class="rms-val" id="reportJoy">--</div><div class="rms-lbl">Joy Index</div></div>
              <div class="col-6 col-sm-3 text-center"><div class="rms-val" id="reportStress">--</div><div class="rms-lbl">Stress Level</div></div>
              <div class="col-6 col-sm-3 text-center"><div class="rms-val" id="reportSessions">--</div><div class="rms-lbl">Sessions Analyzed</div></div>
            </div>
          </div>

          <div class="gen-progress" id="genProgress">
            <div class="d-flex align-items-center gap-3">
              <span style="font-size:20px">⚙️</span>
              <div>
                <div style="font-size:14px;font-weight:600">Generating Report…</div>
                <div style="font-size:12px;color:var(--muted)" id="genStatus">Analyzing emotional data…</div>
              </div>
            </div>
            <div class="gen-bar-track mt-2"><div class="gen-bar-fill" id="genBarFill"></div></div>
          </div>

          <h3 style="font-family:var(--fh);font-size:16px;font-weight:700;margin-bottom:12px">Generated Reports</h3>
          <div class="report-list" id="reportList" aria-live="polite"></div>
        </div>
      </article>

      <!-- ══ NOTIFICATIONS ══ -->
      <article class="page" id="notificationsPage">
        <div class="page-inner">
          <div class="page-hdr">
            <button class="back-btn" onclick="showPage('dashboard')"><span class="material-symbols-outlined">arrow_back</span></button>
            <div class="page-hdr-info flex-grow-1">
              <span class="label">Activity Feed</span>
              <h2 class="page-hdr-title gradient-text">Notifications</h2>
            </div>
            <button class="btn btn-ghost btn-sm" id="markAllReadBtn">Mark all read</button>
          </div>
          <div id="notifList" aria-live="polite"></div>
        </div>
      </article>

      <!-- ══ PROFILE ══ -->
      <article class="page" id="profilePage">
        <div class="page-inner">
          <div class="page-hdr">
            <button class="back-btn" onclick="showPage('dashboard')"><span class="material-symbols-outlined">arrow_back</span></button>
            <div class="page-hdr-info">
              <span class="label">Account</span>
              <h2 class="page-hdr-title">Parent Profile</h2>
            </div>
          </div>

          <div class="profile-hero mb-4">
            <div class="profile-avatar">
              <img src="" alt="Profile photo" id="profileAvatar" width="90" height="90"/>
              <div class="profile-verified">✓</div>
            </div>
            <div class="profile-info">
              <span class="chip chip-cyan mb-2" id="profilePlanBadge">--</span>
              <div class="profile-name" id="profileName">--</div>
              <div class="profile-role">Account owner · Member since <span id="profileMemberSince">--</span></div>
              <div style="font-size:12px;color:var(--muted);margin-top:4px" id="profileEmail">--</div>
              <div class="profile-stat-row" aria-label="Profile statistics">
                <div class="psi"><div class="psi-val" id="profileChildCount">--</div><div class="psi-lbl">Children</div></div>
                <div class="psi"><div class="psi-val" id="profileReportCount">--</div><div class="psi-lbl">Analyses</div></div>
                <div class="psi"><div class="psi-val" id="profileAvgScore">--</div><div class="psi-lbl">Avg Score</div></div>
              </div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-12 col-lg-7">
              <h3 style="font-family:var(--fh);font-size:16px;font-weight:700;margin-bottom:12px">Account Information</h3>
              <div class="editable-field">
                <div><div class="editable-lbl">Full Name</div><div class="editable-val" id="profileName2">--</div></div>
              </div>
              <div class="editable-field">
                <div><div class="editable-lbl">Email</div><div class="editable-val" id="profileEmail2">--</div></div>
              </div>
              <div class="editable-field">
                <div><div class="editable-lbl">Member Since</div><div class="editable-val" id="profileMemberSince">--</div></div>
              </div>
              <h3 style="font-family:var(--fh);font-size:16px;font-weight:700;margin:18px 0 10px">Subscription</h3>
              <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div style="font-size:14px;font-weight:700" id="profilePlan">--</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px" id="profileStatus">--</div>
                  </div>
                  <button class="btn btn-ghost btn-sm" onclick="showToast('Billing management is handled by support.','info')">Details</button>
                </div>
              </div>
              <button class="btn btn-danger w-100 mt-3 sign-out-btn">
                <span class="material-symbols-outlined" style="font-size:16px">logout</span> Sign Out
              </button>
            </div>
            <div class="col-12 col-lg-5">
              <h3 style="font-family:var(--fh);font-size:16px;font-weight:700;margin-bottom:12px">Family Profiles</h3>
              <div id="profileKidsList"></div>
              <button class="btn btn-ghost w-100 mt-2" onclick="showPage('add-child')">
                <span class="material-symbols-outlined" style="font-size:16px">person_add</span> Add Child Profile
              </button>
            </div>
          </div>
        </div>
      </article>

      <!-- ══ SETTINGS ══ -->
      <article class="page" id="settingsPage">
        <div class="page-inner">
          <div class="page-hdr">
            <button class="back-btn" onclick="showPage('dashboard')"><span class="material-symbols-outlined">arrow_back</span></button>
            <div class="page-hdr-info">
              <span class="label">Preferences</span>
              <h2 class="page-hdr-title">Settings</h2>
            </div>
          </div>

          <div class="settings-section mb-3">
            <h3 class="settings-title">
              <div class="settings-icon" style="background:rgba(105,218,255,0.1)"><span class="material-symbols-outlined" style="color:var(--cyan)">security</span></div>
              Account &amp; Security
            </h3>
            <div class="setting-row">
              <div class="setting-info"><h4>Email Address</h4><p id="settingsEmail">--</p></div>
              <button class="btn btn-ghost btn-sm" onclick="showToast('Email updates are managed by support.','info')">Details</button>
            </div>
            <div class="setting-row">
              <div class="setting-info"><h4>Password</h4><p>Last changed 3 months ago</p></div>
              <button class="btn btn-ghost btn-sm" id="changePasswordBtn">Update</button>
            </div>
            <div class="setting-row">
              <div class="setting-info"><h4>Two-Factor Authentication</h4><p>Add an extra layer of security</p></div>
              <label class="toggle"><input type="checkbox" id="setting2FA" checked/><div class="toggle-track"></div><div class="toggle-thumb"></div></label>
            </div>
            <div class="setting-row">
              <div class="setting-info"><h4>Login Notifications</h4><p>Alert on new device logins</p></div>
              <label class="toggle"><input type="checkbox" id="settingLoginN" checked/><div class="toggle-track"></div><div class="toggle-thumb"></div></label>
            </div>
          </div>

          <div class="settings-section mb-3">
            <h3 class="settings-title">
              <div class="settings-icon" style="background:rgba(166,140,255,0.1)"><span class="material-symbols-outlined" style="color:var(--violet)">palette</span></div>
              Visual Style
            </h3>
            <div class="theme-btns">
              <button class="theme-btn" id="lightModeBtn"><span class="material-symbols-outlined">light_mode</span> Light Mode</button>
              <button class="theme-btn active" id="darkModeBtn"><span class="material-symbols-outlined" style="color:var(--cyan)">dark_mode</span> Dark Mode</button>
            </div>
          </div>

          <div class="settings-section mb-3">
            <h3 class="settings-title">
              <div class="settings-icon" style="background:rgba(255,209,102,0.1)"><span class="material-symbols-outlined" style="color:var(--gold)">notifications</span></div>
              Notifications
            </h3>
            <div class="setting-row">
              <div class="setting-info"><h4>Push Notifications</h4><p>Real-time emotional alerts</p></div>
              <label class="toggle"><input type="checkbox" id="settingPush" checked/><div class="toggle-track"></div><div class="toggle-thumb"></div></label>
            </div>
            <div class="setting-row">
              <div class="setting-info"><h4>Email Reports</h4><p>Weekly summaries to your inbox</p></div>
              <label class="toggle"><input type="checkbox" id="settingEmail"/><div class="toggle-track"></div><div class="toggle-thumb"></div></label>
            </div>
            <div class="setting-row">
              <div class="setting-info"><h4>Quiet Hours</h4><p>Mute notifications 22:00–07:00</p></div>
              <label class="toggle"><input type="checkbox" id="settingQuiet" checked/><div class="toggle-track"></div><div class="toggle-thumb"></div></label>
            </div>
          </div>

          <div class="settings-section mb-3">
            <h3 class="settings-title">
              <div class="settings-icon" style="background:rgba(78,205,196,0.1)"><span class="material-symbols-outlined" style="color:var(--mint)">shield_lock</span></div>
              Privacy
            </h3>
            <div class="setting-row">
              <div class="setting-info"><h4>Share Data with Teachers</h4><p>Teachers can view emotional reports</p></div>
              <label class="toggle"><input type="checkbox" id="settingShare" checked/><div class="toggle-track"></div><div class="toggle-thumb"></div></label>
            </div>
            <div class="setting-row">
              <div class="setting-info"><h4>Anonymous Analytics</h4><p>Help improve the platform</p></div>
              <label class="toggle"><input type="checkbox" id="settingAnaly" checked/><div class="toggle-track"></div><div class="toggle-thumb"></div></label>
            </div>
            <div class="setting-row">
              <div class="setting-info"><h4>Data Retention</h4><p>How long we keep your data</p></div>
              <select class="form-input" id="settingRetention" style="width:auto;padding:6px 12px;font-size:12px">
                <option>12 months</option><option>6 months</option><option>3 months</option>
              </select>
            </div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button id="sh-replay-btn" onclick="Tour.replay()">
              <span class="material-symbols-outlined" style="font-size:16px">help</span> Replay Tutorial
            </button>
            <button class="btn btn-ghost" onclick="resetPreferences()">
              <span class="material-symbols-outlined" style="font-size:16px">restart_alt</span> Reset Preferences
            </button>
            <button class="btn btn-danger sign-out-btn">
              <span class="material-symbols-outlined" style="font-size:16px">logout</span> Sign Out
            </button>
          </div>
        </div>
      </article>

    </main>
  </div><!-- .main-content -->
</div><!-- .app-layout -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- App Scripts -->
<script src="data.js"></script>
<script src="app.js"></script>
<script src="bgthreejs.js"></script>
<script src="search-integration.js"></script>
<script src="Guide.js"></script>

<!-- THREE.JS ANIMATED BACKGROUND -->
<script>
(function () {
  const canvas = document.getElementById('bg-three');
  if (!canvas || typeof THREE === 'undefined') return;
  const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
  renderer.setSize(window.innerWidth, window.innerHeight);
  const scene  = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 100);
  camera.position.z = 5;
  const N = window.innerWidth < 768 ? 400 : 900;
  const pos = new Float32Array(N * 3);
  for (let i = 0; i < N; i++) {
    pos[i*3]   = (Math.random()-0.5)*20;
    pos[i*3+1] = (Math.random()-0.5)*20;
    pos[i*3+2] = (Math.random()-0.5)*12;
  }
  const geo = new THREE.BufferGeometry();
  geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  const mat = new THREE.PointsMaterial({ color:0x8860ff, size:0.045, transparent:true, opacity:0.65 });
  const particles = new THREE.Points(geo, mat);
  scene.add(particles);
  const N2 = window.innerWidth < 768 ? 150 : 350;
  const pos2 = new Float32Array(N2 * 3);
  for (let i = 0; i < N2; i++) {
    pos2[i*3]   = (Math.random()-0.5)*14;
    pos2[i*3+1] = (Math.random()-0.5)*14;
    pos2[i*3+2] = (Math.random()-0.5)*8;
  }
  const geo2 = new THREE.BufferGeometry();
  geo2.setAttribute('position', new THREE.BufferAttribute(pos2, 3));
  const mat2 = new THREE.PointsMaterial({ color:0x69daff, size:0.025, transparent:true, opacity:0.35 });
  const particles2 = new THREE.Points(geo2, mat2);
  scene.add(particles2);
  function animate() {
    requestAnimationFrame(animate);
    particles.rotation.y  += 0.00028;
    particles.rotation.x  += 0.00010;
    particles2.rotation.y -= 0.00018;
    particles2.rotation.z += 0.00008;
    renderer.render(scene, camera);
  }
  animate();
  window.addEventListener('resize', () => {
    renderer.setSize(window.innerWidth, window.innerHeight);
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
  });
})();
</script>
</body>
</html>