
const Charts = {};
function destroyChart(key) {
  if (Charts[key]) { try { Charts[key].destroy(); } catch(e){} Charts[key] = null; }
}

const SettingsStore = {
  defaults: {
    theme: 'dark',
    pushAlerts: true,
    emailDigest: false,
    quietHours: true,
    shareTeachers: true,
    analytics: true,
    twoFactor: true,
    loginNotifications: true,
    dataRetention: '12 months'
  },
  get() {
    const raw = localStorage.getItem('sh_settings');
    if (!raw) return { ...this.defaults };
    try {
      return { ...this.defaults, ...JSON.parse(raw) };
    } catch (err) {
      return { ...this.defaults };
    }
  },
  update(key, value) {
    const settings = this.get();
    settings[key] = value;
    localStorage.setItem('sh_settings', JSON.stringify(settings));
  },
  reset() {
    localStorage.setItem('sh_settings', JSON.stringify(this.defaults));
  }
};

const Api = {
  base: window.SH_BASE_PATH || '',
  async request(path, options = {}) {
    const res = await fetch(`${this.base}${path}`, {
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options
    });
    const payload = await res.json().catch(() => ({}));
    if (!res.ok || payload.status === 'error') {
      throw new Error(payload.message || 'Request failed');
    }
    return payload;
  }
};

const childrenState = { list: [] };
const profileState = { data: null };
const reportsState = { summary: null, reports: [] };
const notificationsState = { items: [], readIds: new Set() };
notificationsState.readIds = loadNotifReadState();

/* ─── TOAST ─── */
function showToast(msg, type = 'success') {
  const icons  = { success:'✅', error:'❌', info:'ℹ️', warning:'⚠️' };
  const colors = { success:'var(--mint)', error:'var(--coral)', info:'var(--cyan)', warning:'var(--gold)' };
  const t = document.createElement('div');
  t.className = 'toast';
  t.style.borderColor = colors[type];
  t.innerHTML = `<span class="toast-icon">${icons[type]}</span><span>${msg}</span>`;
  document.body.appendChild(t);
  setTimeout(() => { t.classList.add('hide'); setTimeout(() => t.remove(), 350); }, 3200);
}

/* ─── CONFIRM ─── */
function showConfirm(title, desc, onConfirm) {
  const overlay = document.getElementById('confirmOverlay');
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmDesc').textContent = desc;
  overlay.classList.add('active');
  const close = () => overlay.classList.remove('active');
  document.getElementById('confirmOk').onclick  = () => { close(); onConfirm(); };
  document.getElementById('confirmCancel').onclick = close;
  overlay.onclick = e => { if (e.target === overlay) close(); };
}

/* ─── NAVIGATION ─── */
const PAGE_MAP = {
  dashboard:'dashboardPage', moodmap:'moodmapPage',
  'manage-children':'manageChildrenPage', 'add-child':'addChildPage',
  news:'newsPage', reports:'reportsPage', notifications:'notificationsPage',
  profile:'profilePage', settings:'settingsPage'
};
const PAGE_TITLES = {
  dashboard:'Dashboard', moodmap:'Mood Map',
  'manage-children':'Manage Children', 'add-child':'Add Child Profile',
  news:'News & Insights', reports:'PDF Reports', notifications:'Notifications',
  profile:'Profile', settings:'Settings'
};

let currentPage = 'dashboard';

function showPage(id) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  const elId = PAGE_MAP[id] || (id + 'Page');
  const el = document.getElementById(elId);
  if (el) el.classList.add('active');
  document.querySelectorAll('.nav-item[data-page]').forEach(l => {
    l.classList.toggle('active', l.dataset.page === id);
  });
  document.getElementById('pageTitle').textContent = PAGE_TITLES[id] || 'Dashboard';
  window.scrollTo(0, 0);
  currentPage = id;
  if (window.innerWidth < 768) closeSidebar();
  switch(id) {
    case 'dashboard':       initDashboard();      break;
    case 'moodmap':         initMoodMap();        break;
    case 'manage-children': renderChildren();     break;
    case 'add-child':       resetAddChildForm();  break;
    case 'news':            renderNews('All');    break;
    case 'reports':         renderReports();      break;
    case 'notifications':   renderNotifications();break;
    case 'profile':         renderProfile();      break;
    case 'settings':        renderSettings();     break;
  }
}

/* ─── SIDEBAR ─── */
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');

function toggleSidebar() {
  if (window.innerWidth < 768) {
    // Mobile: offcanvas open/close
    sidebar.classList.toggle('mobile-open');
    backdrop.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
  } else {
    // Desktop: collapse/expand
    sidebar.classList.toggle('expanded');
  }
}

function closeSidebar() {
  sidebar.classList.remove('mobile-open');
  backdrop.classList.remove('active');
  document.body.style.overflow = '';
}

document.getElementById('sidebarToggle').addEventListener('click', toggleSidebar);
document.getElementById('sidebarLogo').addEventListener('click', () => {
  if (window.innerWidth >= 768) sidebar.classList.toggle('expanded');
});

// Close on backdrop click
backdrop?.addEventListener('click', closeSidebar);

// Wire nav items
document.querySelectorAll('.nav-item[data-page]').forEach(el => {
  el.addEventListener('click', e => { e.preventDefault(); showPage(el.dataset.page); });
});

// Handle resize
window.addEventListener('resize', () => {
  if (window.innerWidth >= 768) {
    sidebar.classList.remove('mobile-open');
    backdrop.classList.remove('active');
    document.body.style.overflow = '';
  }
});

/* ─── THEME ─── */
function applyTheme(isDark) {
  document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
  document.getElementById('themeIcon').textContent = isDark ? 'light_mode' : 'dark_mode';
  localStorage.setItem('sh_theme', isDark ? 'dark' : 'light');
  document.getElementById('darkModeBtn')?.classList.toggle('active', isDark);
  document.getElementById('lightModeBtn')?.classList.toggle('active', !isDark);
}
const savedTheme = SettingsStore.get().theme || 'dark';
applyTheme(savedTheme === 'dark');

document.getElementById('themeToggleBtn').addEventListener('click', () => {
  const isDark = document.documentElement.getAttribute('data-theme') !== 'dark';
  applyTheme(isDark);
  SettingsStore.update('theme', isDark ? 'dark' : 'light');
});

/* ─── TOPBAR BUTTONS ─── */
document.getElementById('notifTopBtn').addEventListener('click', () => showPage('notifications'));
document.getElementById('profileTopBtn').addEventListener('click', () => showPage('profile'));

function updateNotifBadge() {
  const count = notificationsState.items.filter(n => !n.read && n.severity === 'critical').length;
  const badge = document.getElementById('navNotifBadge');
  const dot   = document.getElementById('notifDot');
  if (badge) badge.textContent = count > 0 ? count : '';
  if (dot)   dot.style.display = count > 0 ? 'block' : 'none';
}

/* ════════════════════════════════════════════════
   DASHBOARD
════════════════════════════════════════════════ */
const analysisState = { data: null };
let rangeBound = false;

async function loadChildren() {
  const payload = await Api.request('/api/parent_children_api.php');
  childrenState.list = (payload.children || []).map(child => ({
    ...child,
    id: child.child_id,
    name: child.nickname
  }));
  return childrenState.list;
}

async function loadProfile() {
  const payload = await Api.request('/api/parent_profile_api.php');
  profileState.data = payload.profile || null;
  profileState.childCount = payload.child_count || 0;
  return profileState.data;
}

async function loadReports(start, end) {
  const qs = new URLSearchParams();
  if (start) qs.set('start', start);
  if (end) qs.set('end', end);
  const url = `/api/parent_reports_api.php${qs.toString() ? `?${qs.toString()}` : ''}`;
  const payload = await Api.request(url);
  reportsState.summary = payload.summary || null;
  reportsState.reports = payload.reports || [];
  return reportsState;
}

function ensureAnalysisDataAsync() {
  if (analysisState.data) return Promise.resolve(analysisState.data);
  return loadAnalysisRange().then(() => analysisState.data);
}

function initDashboard() {
  updateNotifBadge();
  renderDashNews();
  setupRangeControls();
  loadAnalysisRange();
  document.getElementById('viewLibraryBtn')?.addEventListener('click', openLibraryModal);
}

function setupRangeControls() {
  if (rangeBound) return;
  rangeBound = true;

  const startInput = document.getElementById('rangeStart');
  const endInput = document.getElementById('rangeEnd');
  const applyBtn = document.getElementById('applyRange');
  if (!startInput || !endInput || !applyBtn) return;

  const today = new Date();
  const start = new Date();
  start.setDate(today.getDate() - 14);
  if (!startInput.value) startInput.value = formatDateInput(start);
  if (!endInput.value) endInput.value = formatDateInput(today);

  applyBtn.addEventListener('click', e => {
    e.preventDefault();
    loadAnalysisRange();
  });
}

function formatDateInput(date) {
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

function parseDateTime(value) {
  if (!value) return null;
  const normalized = value.replace(' ', 'T');
  const parsed = new Date(normalized);
  return isNaN(parsed.getTime()) ? null : parsed;
}

function formatLabel(date) {
  if (!date) return '';
  const options = { month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' };
  return date.toLocaleString(undefined, options).replace(',', '');
}

async function loadAnalysisRange() {
  const startInput = document.getElementById('rangeStart');
  const endInput = document.getElementById('rangeEnd');
  if (!startInput || !endInput) return;

  const start = startInput.value;
  const end = endInput.value;
  const basePath = window.SH_BASE_PATH || '';
  const url = `${basePath}/api/parent_analysis_api.php?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;

  try {
    const res = await fetch(url, { credentials: 'same-origin' });
    const payload = await res.json();
    if (!res.ok || payload.status !== 'success') {
      throw new Error(payload.message || 'Failed to load analysis');
    }
    analysisState.data = payload;
    renderAnalysisDashboard(payload);
  } catch (err) {
    showToast('Unable to load analysis data.', 'error');
    renderEmptyAnalysis();
  }
}

function renderEmptyAnalysis() {
  document.getElementById('statBalance').textContent = '--';
  document.getElementById('statBalanceTrend').textContent = '--';
  document.getElementById('statGrowth').textContent = '--';
  document.getElementById('statGrowthNote').textContent = '--';
  document.getElementById('scoreNum').textContent = '--';
  document.getElementById('scoreChip').textContent = '--';
  document.getElementById('statChildren').textContent = '0';
  document.getElementById('statNotifs').textContent = '0';
  const summary = document.getElementById('summaryGrid');
  if (summary) summary.innerHTML = '<div class="col-12" style="color:var(--muted)">No data available.</div>';
  const insights = document.getElementById('insightsList');
  if (insights) insights.innerHTML = '<div class="insight-item"><div class="insight-icon">ℹ️</div><div><div class="insight-title">No analysis</div><div class="insight-desc">Run sessions to see trends.</div></div></div>';
  const tableBody = document.getElementById('analysisTableBody');
  if (tableBody) tableBody.innerHTML = '<tr><td colspan="5" class="muted">No analysis yet.</td></tr>';
  document.getElementById('historyCount').textContent = '0 entries';
  const gameEmpty = document.getElementById('gameImpactEmpty');
  if (gameEmpty) gameEmpty.classList.add('active');
  const qualityEmpty = document.getElementById('dataQualityEmpty');
  if (qualityEmpty) qualityEmpty.classList.add('active');
  const gameCanvas = document.getElementById('gameImpactChart');
  if (gameCanvas) gameCanvas.style.display = 'none';
  const qualityCanvas = document.getElementById('dataQualityChart');
  if (qualityCanvas) qualityCanvas.style.display = 'none';
  destroyChart('pulse');
  destroyChart('gameImpact');
  destroyChart('dataQuality');
}

function renderAnalysisDashboard(payload) {
  const sessions = payload.sessions || [];
  const children = payload.children || [];
  const childMap = new Map(children.map(c => [c.id, c]));

  const sortedSessions = sessions.slice().sort((a, b) => {
    const aTime = parseDateTime(a.start_time)?.getTime() || 0;
    const bTime = parseDateTime(b.start_time)?.getTime() || 0;
    return aTime - bTime;
  });

  const balanceScores = [];
  const sessionByChild = {};
  const labelTimes = [];
  const timeIndex = new Map();

  sortedSessions.forEach(session => {
    const time = parseDateTime(session.start_time);
    if (!time) return;
    const ts = time.getTime();
    if (!timeIndex.has(ts)) {
      timeIndex.set(ts, labelTimes.length);
      labelTimes.push(time);
    }
    if (!sessionByChild[session.child_id]) {
      sessionByChild[session.child_id] = [];
    }
    const balance = computeBalance(session.scores);
    if (balance !== null) {
      balanceScores.push(balance);
    }
    sessionByChild[session.child_id].push({
      time,
      balance,
      state: session.state,
      confidence: session.confidence,
      scores: session.scores,
      game: session.game_slug,
    });
  });

  const avgBalance = balanceScores.length ? average(balanceScores) : 0;
  const trend = computeTrend(balanceScores);
  document.getElementById('statChildren').textContent = children.length.toString();
  document.getElementById('statBalance').textContent = `${avgBalance.toFixed(0)}%`;
  document.getElementById('statBalanceTrend').textContent = trend.label;
  document.getElementById('statGrowth').textContent = trend.deltaLabel;
  document.getElementById('statGrowthNote').textContent = trend.note;

  const scoreNum = document.getElementById('scoreNum');
  if (scoreNum) scoreNum.textContent = `${avgBalance.toFixed(0)}`;
  const scoreChip = document.getElementById('scoreChip');
  if (scoreChip) scoreChip.textContent = trend.deltaLabel;

  const sc = document.querySelector('.score-progress');
  if (sc) {
    const circ = 2 * Math.PI * 86;
    sc.setAttribute('stroke-dasharray', circ);
    sc.setAttribute('stroke-dashoffset', circ - (avgBalance / 100) * circ);
  }

  renderSummaryGrid(sortedSessions);
  renderAnalysisChart(labelTimes, sessionByChild, childMap);
  renderInsightsPanel(sortedSessions, sessionByChild, childMap);
  renderHistoryTable(sortedSessions, childMap);
  renderGameImpactChart(sortedSessions);
  renderDataQualityChart(sortedSessions);
  refreshNotificationsState(sortedSessions, childMap);
  const criticalCount = notificationsState.items.filter(n => n.severity === 'critical').length;
  document.getElementById('statNotifs').textContent = criticalCount.toString();

  const rangeLabel = document.getElementById('timeRangeLabel');
  if (rangeLabel) rangeLabel.textContent = `${payload.range.start} → ${payload.range.end}`;
  const row = document.getElementById('timeLabelRow');
  if (row) {
    const samples = labelTimes.slice(0, 5).map(time => `<span>${formatLabel(time)}</span>`).join('');
    row.innerHTML = samples || '<span>No sessions</span>';
  }
}

function computeBalance(scores) {
  if (!scores) return null;
  const total = (scores.focus || 0) + (scores.joy || 0) + (scores.frustration || 0) + (scores.boredom || 0);
  if (total <= 0) return null;
  return ((scores.focus || 0) + (scores.joy || 0)) / total * 100;
}

function scorePercent(scores, key) {
  if (!scores) return 0;
  const total = (scores.joy || 0) + (scores.focus || 0) + (scores.frustration || 0) + (scores.boredom || 0);
  if (total <= 0) return 0;
  return (scores[key] || 0) / total * 100;
}

function average(values) {
  if (!values.length) return 0;
  return values.reduce((sum, v) => sum + v, 0) / values.length;
}

function computeTrend(values) {
  if (values.length < 2) {
    return { deltaLabel: '0%', label: 'No change', note: 'Stable range' };
  }
  const mid = Math.floor(values.length / 2);
  const first = average(values.slice(0, mid));
  const second = average(values.slice(mid));
  const delta = first === 0 ? 0 : ((second - first) / first) * 100;
  const sign = delta >= 0 ? '+' : '';
  const deltaLabel = `${sign}${delta.toFixed(0)}%`;
  const label = delta >= 0 ? `↑ ${deltaLabel} vs early range` : `↓ ${Math.abs(delta).toFixed(0)}% vs early range`;
  const note = delta >= 0 ? 'Trending upward' : 'Trending downward';
  return { deltaLabel, label, note };
}

function renderSummaryGrid(sessions) {
  const summary = document.getElementById('summaryGrid');
  if (!summary) return;

  if (!sessions.length) {
    summary.innerHTML = '<div class="col-12" style="color:var(--muted)">No sessions in range.</div>';
    return;
  }

  const stateMap = {
    'Happy / Confident': { label: 'Happy', emoji: '✨', color: 'var(--gold)' },
    'High Engagement': { label: 'Focused', emoji: '🎯', color: 'var(--violet)' },
    'Agitated / Angry': { label: 'Angry', emoji: '🔥', color: 'var(--coral)' },
    'Disengaged / Sad': { label: 'Sad', emoji: '💜', color: 'var(--violet)' },
    'Neutral / Evaluating': { label: 'Neutral', emoji: '🌀', color: 'var(--cyan)' },
  };

  const counts = {};
  sessions.forEach(s => {
    const key = stateMap[s.state] ? s.state : 'Neutral / Evaluating';
    counts[key] = (counts[key] || 0) + 1;
  });

  const total = sessions.length;
  const items = Object.keys(counts)
    .map(key => ({ key, count: counts[key], pct: Math.round((counts[key] / total) * 100) }))
    .sort((a, b) => b.pct - a.pct)
    .slice(0, 4);

  summary.innerHTML = items.map(item => {
    const meta = stateMap[item.key];
    return `
      <div class="col-6 col-sm-3">
        <div class="char-card" style="border-color:${meta.color}">
          <span class="char-emoji">${meta.emoji}</span>
          <div class="char-name" style="color:${meta.color}">${meta.label}</div>
          <div class="char-trait">${item.count} sessions</div>
          <div class="char-pct" style="color:${meta.color}">${item.pct}%</div>
          <div class="prog-track mt-2"><div class="prog-fill" style="width:${item.pct}%;background:${meta.color}"></div></div>
        </div>
      </div>
    `;
  }).join('');
}

function renderAnalysisChart(labelTimes, sessionByChild, childMap) {
  const labels = labelTimes.map(formatLabel);
  const datasets = [];
  const palette = ['#69DAFF', '#A68CFF', '#FFD166', '#4ECDC4', '#FF6B6B', '#5FCEFF', '#C49BFF'];
  let colorIndex = 0;

  Object.keys(sessionByChild).forEach(childId => {
    const child = childMap.get(parseInt(childId, 10));
    const color = palette[colorIndex % palette.length];
    colorIndex++;
    const data = new Array(labelTimes.length).fill(null);
    sessionByChild[childId].forEach(entry => {
      const idx = labelTimes.findIndex(t => t.getTime() === entry.time.getTime());
      if (idx >= 0 && entry.balance !== null) {
        data[idx] = Math.round(entry.balance);
      }
    });
    datasets.push({
      label: child ? child.name : `Child ${childId}`,
      data,
      borderColor: color,
      backgroundColor: color + '33',
      tension: 0.45,
      fill: false,
      spanGaps: true,
      pointRadius: 3,
      pointHoverRadius: 6,
    });
  });

  destroyChart('pulse');
  const ctx = document.getElementById('pulseChart')?.getContext('2d');
  if (!ctx) return;
  Charts.pulse = new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: true, labels: { color: 'rgba(240,238,248,0.7)', font: { size: 11 } } },
        tooltip: {
          backgroundColor: 'rgba(19,16,30,0.95)',
          borderColor: 'rgba(105,218,255,0.3)',
          borderWidth: 1,
          titleColor: '#69DAFF',
          bodyColor: '#F0EEF8',
          callbacks: {
            label: ctx2 => `${ctx2.dataset.label}: ${ctx2.raw ?? '—'}%`
          }
        }
      },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(240,238,248,0.4)', font: { size: 10 }, maxTicksLimit: 6 } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(240,238,248,0.4)', font: { size: 11 } }, min: 0, max: 100 }
      }
    }
  });
}

function renderInsightsPanel(sessions, sessionByChild, childMap) {
  const insights = document.getElementById('insightsList');
  if (!insights) return;

  if (!sessions.length) {
    insights.innerHTML = '<div class="insight-item"><div class="insight-icon">ℹ️</div><div><div class="insight-title">No sessions</div><div class="insight-desc">Play sessions will unlock insights.</div></div></div>';
    return;
  }

  const volatility = [];
  Object.keys(sessionByChild).forEach(childId => {
    const values = sessionByChild[childId].map(entry => entry.balance).filter(v => v !== null);
    if (!values.length) return;
    const avg = average(values);
    const variance = average(values.map(v => Math.pow(v - avg, 2)));
    volatility.push({
      childId,
      std: Math.sqrt(variance),
      avg,
    });
  });

  const mostVolatile = volatility.sort((a, b) => b.std - a.std)[0];
  const mostEngaged = volatility.sort((a, b) => b.avg - a.avg)[0];
  const peakFrustration = sessions.slice().sort((a, b) => (b.scores.frustration || 0) - (a.scores.frustration || 0))[0];

  const items = [];
  if (mostVolatile) {
    const child = childMap.get(parseInt(mostVolatile.childId, 10));
    items.push({
      icon: '📈',
      title: 'Most volatile curve',
      desc: `${child ? child.name : 'A child'} shows the widest mood swings in this range.`
    });
  }
  if (mostEngaged) {
    const child = childMap.get(parseInt(mostEngaged.childId, 10));
    items.push({
      icon: '🌟',
      title: 'Best engagement',
      desc: `${child ? child.name : 'A child'} has the strongest average balance score.`
    });
  }
  if (peakFrustration) {
    const child = childMap.get(Number(peakFrustration.child_id));
    items.push({
      icon: '⚠️',
      title: 'Peak frustration',
      desc: `${child ? child.name : 'A child'} had the highest frustration during ${formatLabel(parseDateTime(peakFrustration.start_time))}.`
    });
  }

  insights.innerHTML = items.map(item => `
    <div class="insight-item">
      <div class="insight-icon">${item.icon}</div>
      <div>
        <div class="insight-title">${item.title}</div>
        <div class="insight-desc">${item.desc}</div>
      </div>
    </div>
  `).join('');
}

function renderHistoryTable(sessions, childMap) {
  const body = document.getElementById('analysisTableBody');
  const count = document.getElementById('historyCount');
  if (!body || !count) return;

  if (!sessions.length) {
    body.innerHTML = '<tr><td colspan="5" class="muted">No analysis data in this range.</td></tr>';
    count.textContent = '0 entries';
    return;
  }

  count.textContent = `${sessions.length} entries`;
  body.innerHTML = sessions.map(session => {
    const child = childMap.get(Number(session.child_id));
    const time = formatLabel(parseDateTime(session.start_time));
    return `
      <tr>
        <td>${child ? child.name : 'Unknown'}</td>
        <td>${time}</td>
        <td>${session.state}</td>
        <td>${session.confidence.toFixed(1)}%</td>
        <td>${session.game_slug || '—'}</td>
      </tr>
    `;
  }).join('');
}

function formatRelativeTime(date) {
  if (!date) return '';
  const diffMs = Date.now() - date.getTime();
  const mins = Math.max(1, Math.floor(diffMs / 60000));
  if (mins < 60) return `${mins}m ago`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
}

function buildNotificationsFromSessions(sessions, childMap) {
  if (!sessions.length) return [];

  const items = [];
  const sorted = sessions.slice().sort((a, b) => {
    const aTime = parseDateTime(a.start_time)?.getTime() || 0;
    const bTime = parseDateTime(b.start_time)?.getTime() || 0;
    return bTime - aTime;
  });

  const peakBySignal = { frustration: null, boredom: null, joy: null };
  sorted.forEach(session => {
    const frustration = scorePercent(session.scores, 'frustration');
    const boredom = scorePercent(session.scores, 'boredom');
    const joy = scorePercent(session.scores, 'joy');
    if (!peakBySignal.frustration || frustration > peakBySignal.frustration.value) {
      peakBySignal.frustration = { session, value: frustration };
    }
    if (!peakBySignal.boredom || boredom > peakBySignal.boredom.value) {
      peakBySignal.boredom = { session, value: boredom };
    }
    if (!peakBySignal.joy || joy > peakBySignal.joy.value) {
      peakBySignal.joy = { session, value: joy };
    }
  });

  const pushAlert = (session, value, label) => {
    const child = childMap.get(Number(session.child_id));
    const time = parseDateTime(session.start_time);
    items.push({
      id: `alert-${label}-${session.session_id}`,
      type: 'alert',
      severity: 'critical',
      title: `${label} peak — ${child ? child.name : 'Child'}`,
      desc: `${label} intensity reached ${Math.round(value)}% during ${formatGameLabel(session.game_slug)}.`,
      time: time ? formatRelativeTime(time) : 'Recently',
      action: 'Review'
    });
  };

  if (peakBySignal.frustration && peakBySignal.frustration.value >= 55) {
    pushAlert(peakBySignal.frustration.session, peakBySignal.frustration.value, 'Frustration');
  }
  if (peakBySignal.boredom && peakBySignal.boredom.value >= 55) {
    pushAlert(peakBySignal.boredom.session, peakBySignal.boredom.value, 'Boredom');
  }

  if (peakBySignal.joy && peakBySignal.joy.value >= 70) {
    const session = peakBySignal.joy.session;
    const child = childMap.get(Number(session.child_id));
    const time = parseDateTime(session.start_time);
    items.push({
      id: `joy-${session.session_id}`,
      type: 'insight',
      severity: 'info',
      title: `Joy spike — ${child ? child.name : 'Child'}`,
      desc: `Joy peaked at ${Math.round(peakBySignal.joy.value)}% during ${formatGameLabel(session.game_slug)}.`,
      time: time ? formatRelativeTime(time) : 'Recently',
      action: 'View'
    });
  }

  sorted.slice(0, 6).forEach(session => {
    const confidence = session.confidence || 0;
    if (session.state === 'Agitated / Angry' && confidence >= 45) {
      const child = childMap.get(Number(session.child_id));
      const time = parseDateTime(session.start_time);
      items.push({
        id: `state-${session.session_id}`,
        type: 'alert',
        severity: 'critical',
        title: `High stress detected — ${child ? child.name : 'Child'}`,
        desc: `Session flagged as ${session.state} (${confidence.toFixed(0)}%) in ${formatGameLabel(session.game_slug)}.`,
        time: time ? formatRelativeTime(time) : 'Recently',
        action: 'Analyze'
      });
    }
  });

  return items.slice(0, 8);
}

function refreshNotificationsState(sessions, childMap) {
  notificationsState.items = buildNotificationsFromSessions(sessions, childMap).map(item => ({
    ...item,
    read: notificationsState.readIds.has(item.id)
  }));
  updateNotifBadge();
}

function formatGameLabel(slug) {
  if (!slug) return 'Unknown';
  return slug
    .replace(/_/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase());
}

function buildGameStats(sessions) {
  const stats = new Map();
  sessions.forEach(session => {
    const slug = session.game_slug || 'unknown';
    if (!stats.has(slug)) {
      stats.set(slug, { count: 0, balances: [], focus: [], joy: [], frustration: [], boredom: [] });
    }
    const entry = stats.get(slug);
    entry.count += 1;
    const balance = computeBalance(session.scores);
    if (balance !== null) entry.balances.push(balance);
    entry.focus.push(scorePercent(session.scores, 'focus'));
    entry.joy.push(scorePercent(session.scores, 'joy'));
    entry.frustration.push(scorePercent(session.scores, 'frustration'));
    entry.boredom.push(scorePercent(session.scores, 'boredom'));
  });

  return Array.from(stats.entries()).map(([slug, entry]) => ({
    slug,
    label: formatGameLabel(slug),
    count: entry.count,
    balance: average(entry.balances),
    focus: average(entry.focus),
    joy: average(entry.joy),
    frustration: average(entry.frustration),
    boredom: average(entry.boredom)
  }));
}

function renderGameImpactChart(sessions) {
  const canvas = document.getElementById('gameImpactChart');
  const empty = document.getElementById('gameImpactEmpty');
  if (!canvas) return;

  const stats = buildGameStats(sessions)
    .sort((a, b) => b.count - a.count)
    .slice(0, 6);

  if (!stats.length) {
    destroyChart('gameImpact');
    if (empty) empty.classList.add('active');
    canvas.style.display = 'none';
    return;
  }

  if (empty) empty.classList.remove('active');
  canvas.style.display = 'block';
  destroyChart('gameImpact');

  const ctx = canvas.getContext('2d');
  const labels = stats.map(item => item.label);
  const data = stats.map(item => Math.round(item.balance || 0));
  const colors = ['#69DAFF', '#A68CFF', '#FFD166', '#4ECDC4', '#FF6B6B', '#5FCEFF'];

  Charts.gameImpact = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Balance',
        data,
        backgroundColor: data.map((_, i) => colors[i % colors.length] + 'AA'),
        borderColor: data.map((_, i) => colors[i % colors.length]),
        borderWidth: 1,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(19,16,30,0.95)',
          borderColor: 'rgba(105,218,255,0.3)',
          borderWidth: 1,
          titleColor: '#69DAFF',
          bodyColor: '#F0EEF8',
          callbacks: {
            label: ctx2 => `${ctx2.raw ?? '—'}% balance`
          }
        }
      },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(240,238,248,0.4)', font: { size: 10 } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(240,238,248,0.4)', font: { size: 10 } }, min: 0, max: 100 }
      }
    }
  });
}

function renderDataQualityChart(sessions) {
  const canvas = document.getElementById('dataQualityChart');
  const empty = document.getElementById('dataQualityEmpty');
  if (!canvas) return;

  const counts = { raw_signals: 0, summary_only: 0, no_data: 0 };
  sessions.forEach(session => {
    const key = session.data_quality || 'no_data';
    if (counts[key] === undefined) counts[key] = 0;
    counts[key] += 1;
  });

  const total = Object.values(counts).reduce((sum, v) => sum + v, 0);
  if (!total) {
    destroyChart('dataQuality');
    if (empty) empty.classList.add('active');
    canvas.style.display = 'none';
    return;
  }

  if (empty) empty.classList.remove('active');
  canvas.style.display = 'block';
  destroyChart('dataQuality');

  const ctx = canvas.getContext('2d');
  Charts.dataQuality = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Raw signals', 'Summary only', 'No data'],
      datasets: [{
        data: [counts.raw_signals || 0, counts.summary_only || 0, counts.no_data || 0],
        backgroundColor: ['#69DAFF', '#A68CFF', '#FF6B6B'],
        borderColor: 'rgba(13,11,20,0.9)',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { color: 'rgba(240,238,248,0.6)', font: { size: 11 } } },
        tooltip: {
          backgroundColor: 'rgba(19,16,30,0.95)',
          borderColor: 'rgba(105,218,255,0.3)',
          borderWidth: 1,
          titleColor: '#69DAFF',
          bodyColor: '#F0EEF8',
          callbacks: {
            label: ctx2 => `${ctx2.label}: ${ctx2.raw} sessions`
          }
        }
      },
      cutout: '62%'
    }
  });
}

function renderDashNews() {
  const container = document.getElementById('dashNewsRow');
  if (!container) return;
  const catColors = { Psychology:'chip-cyan', Health:'chip-violet', Education:'chip-gold', Research:'chip-mint' };
  container.innerHTML = CONTENT.news.slice(0,4).map(n => `
    <div class="news-card" onclick="showPage('news')">
      <img class="news-card-img" src="${n.img}" alt="${n.title}" width="280" height="160" loading="lazy"/>
      <div class="news-card-body">
        <span class="chip ${catColors[n.cat]||'chip-cyan'}">${n.cat}</span>
        <div class="news-card-title">${n.title}</div>
        <div class="news-card-desc">${n.desc.substring(0,80)}…</div>
      </div>
    </div>`).join('');
}

// Legacy period buttons removed in favor of custom range selector.

function openLibraryModal() {
  const body = document.getElementById('libraryModalBody');
  const catColors = { Psychology:'chip-cyan', Health:'chip-violet', Education:'chip-gold', Research:'chip-mint' };
  body.innerHTML = CONTENT.news.map(n => `
    <div style="display:flex;gap:14px;padding:14px;border-radius:13px;cursor:pointer;transition:all .2s;border:1px solid transparent;"
      onmouseover="this.style.background='var(--glass)';this.style.borderColor='var(--border)'"
      onmouseout="this.style.background='';this.style.borderColor='transparent'">
      <img src="${n.img}" alt="${n.title}" width="90" height="60" style="width:80px;min-width:80px;height:60px;border-radius:9px;object-fit:cover;" loading="lazy"/>
      <div>
        <span class="chip ${catColors[n.cat]||'chip-cyan'}" style="margin-bottom:6px">${n.cat}</span>
        <h4 style="font-size:13px;font-weight:700;margin-bottom:3px;margin-top:6px">${n.title}</h4>
        <p style="font-size:12px;color:var(--muted)">${n.desc}</p>
        <div style="font-size:10px;color:var(--cyan);margin-top:5px">📅 ${n.date} · ${n.readTime} read</div>
      </div>
    </div>`).join('');
  document.getElementById('libraryModal').classList.add('active');
}
document.getElementById('libraryModal')?.addEventListener('click', e => { if(e.target.id==='libraryModal') e.target.classList.remove('active'); });
document.getElementById('closeLibraryModal')?.addEventListener('click', () => document.getElementById('libraryModal').classList.remove('active'));

/* ════════════════════════════════════════════════
   MOOD MAP
════════════════════════════════════════════════ */
let selectedDayIdx = 0;

function ensureAnalysisData(callback) {
  if (analysisState.data) {
    callback(analysisState.data);
    return;
  }
  loadAnalysisRange().then(() => callback(analysisState.data)).catch(() => callback(null));
}

function initMoodMap() {
  ensureAnalysisData(data => {
    const moodData = buildMoodMapData(data);
    selectedDayIdx = 0;
    renderWeekGrid(moodData);
    renderDayDetail(moodData, selectedDayIdx);
    renderWeeklyChart(moodData);
  });
}

function buildMoodMapData(payload) {
  const sessions = payload?.sessions || [];
  const endDate = payload?.range?.end ? new Date(payload.range.end) : new Date();
  const days = [];
  const dayMap = new Map();

  for (let i = 6; i >= 0; i--) {
    const date = new Date(endDate);
    date.setDate(endDate.getDate() - i);
    const key = date.toISOString().slice(0, 10);
    dayMap.set(key, []);
    days.push({ key, date });
  }

  sessions.forEach(session => {
    const time = parseDateTime(session.start_time);
    if (!time) return;
    const key = time.toISOString().slice(0, 10);
    if (dayMap.has(key)) {
      dayMap.get(key).push(session);
    }
  });

  const stateMeta = {
    'Happy / Confident': { name: 'Happy', emoji: '✨', color: '#FFD166' },
    'High Engagement': { name: 'Focused', emoji: '🎯', color: '#A68CFF' },
    'Agitated / Angry': { name: 'Agitated', emoji: '🔥', color: '#FF6B6B' },
    'Disengaged / Sad': { name: 'Sad', emoji: '💜', color: '#A68CFF' },
    'Neutral / Evaluating': { name: 'Neutral', emoji: '🌀', color: '#69DAFF' }
  };

  const dayData = days.map(({ key, date }) => {
    const sessionsForDay = dayMap.get(key) || [];
    if (!sessionsForDay.length) {
      return {
        key,
        day: date.toLocaleDateString(undefined, { weekday: 'short' }),
        name: 'Quiet',
        emoji: '🌙',
        score: 0,
        color: '#69DAFF',
        emotions: { Joy: 0, Calm: 0, Frustration: 0 },
        summary: 'No sessions recorded for this day.'
      };
    }

    const totals = { Joy: 0, Calm: 0, Frustration: 0 };
    const balances = [];
    const stateCounts = {};
    sessionsForDay.forEach(session => {
      const scores = session.scores || {};
      const total = (scores.joy || 0) + (scores.focus || 0) + (scores.frustration || 0) + (scores.boredom || 0);
      if (total > 0) {
        totals.Joy += (scores.joy || 0) / total * 100;
        totals.Calm += (scores.focus || 0) / total * 100;
        totals.Frustration += (scores.frustration || 0) / total * 100;
        balances.push(((scores.joy || 0) + (scores.focus || 0)) / total * 100);
      }
      stateCounts[session.state] = (stateCounts[session.state] || 0) + 1;
    });

    const count = sessionsForDay.length;
    const joy = totals.Joy / count;
    const calm = totals.Calm / count;
    const frustration = totals.Frustration / count;
    const score = balances.length ? average(balances) : 0;
    const dominantState = Object.keys(stateCounts).sort((a, b) => stateCounts[b] - stateCounts[a])[0] || 'Neutral / Evaluating';
    const meta = stateMeta[dominantState] || stateMeta['Neutral / Evaluating'];

    return {
      key,
      day: date.toLocaleDateString(undefined, { weekday: 'short' }),
      name: meta.name,
      emoji: meta.emoji,
      score: Math.round(score),
      color: meta.color,
      emotions: {
        Joy: Math.round(joy),
        Calm: Math.round(calm),
        Frustration: Math.round(frustration)
      },
      summary: `${count} sessions recorded. Dominant state: ${meta.name}.`
    };
  });

  return { days: dayData };
}

function renderWeekGrid(moodData) {
  const grid = document.getElementById('weekMapGrid');
  if (!grid) return;
  grid.innerHTML = moodData.days.map((d, i) => `
    <div class="day-tile ${i===selectedDayIdx?'sel':''}" style="--day-color:${d.color}" onclick="selectDay(${i})" role="listitem">
      <div class="day-lbl">${d.day}</div>
      <div class="day-emoji">${d.emoji}</div>
      <div class="day-name" style="color:${d.color}">${d.name}</div>
      <div class="day-score gradient-text">${d.score}</div>
      <div class="day-bar mt-1"><div class="prog-track"><div class="prog-fill" style="width:${d.score}%;background:${d.color}"></div></div></div>
    </div>`).join('');
}

window.selectDay = function(i) {
  selectedDayIdx = i;
  const moodData = buildMoodMapData(analysisState.data);
  renderWeekGrid(moodData);
  renderDayDetail(moodData, i);
};

function renderDayDetail(moodData, i) {
  const d = moodData.days[i];
  const el = document.getElementById('dayDetailPanel');
  if (!el) return;
  const emoColors = { Joy:'#FFD166', Calm:'#69DAFF', Frustration:'#FF6B6B' };
  el.innerHTML = `
    <div class="detail-top">
      <div>
        <div class="label">Selected Day</div>
        <h3 style="font-family:var(--fh);font-size:20px;font-weight:800;margin-top:4px">${d.day} — <span style="color:${d.color}">${d.name}</span></h3>
        <p style="font-size:13px;color:var(--muted);margin-top:5px;max-width:500px">${d.summary}</p>
      </div>
      <div class="detail-day-emoji">${d.emoji}</div>
    </div>
    <div class="emo-bars">
      ${Object.entries(d.emotions).map(([name,val]) => `
        <div class="emo-bar-row">
          <div class="emo-bar-lbl">${name}</div>
          <div class="emo-bar-track"><div class="emo-bar-fill" style="width:${val}%;background:${emoColors[name]||'var(--cyan)'}"></div></div>
          <div class="emo-bar-pct" style="color:${emoColors[name]||'var(--cyan)'}">${val}%</div>
        </div>`).join('')}
    </div>`;
}

function renderWeeklyChart(moodData) {
  destroyChart('weekly');
  const ctx = document.getElementById('weeklyChart')?.getContext('2d');
  if (!ctx) return;
  Charts.weekly = new Chart(ctx, {
    type: 'line',
    data: {
      labels: moodData.days.map(d => d.day),
      datasets: [
        { label:'Joy',         data:moodData.days.map(d=>d.emotions.Joy),         borderColor:'#FFD166',fill:true,backgroundColor:'rgba(255,209,102,0.08)',tension:0.4,borderWidth:2,pointRadius:4 },
        { label:'Calm',        data:moodData.days.map(d=>d.emotions.Calm),        borderColor:'#69DAFF',fill:true,backgroundColor:'rgba(105,218,255,0.05)',tension:0.4,borderWidth:2,pointRadius:4 },
        { label:'Frustration', data:moodData.days.map(d=>d.emotions.Frustration), borderColor:'#FF6B6B',fill:false,tension:0.4,borderWidth:2,borderDash:[4,4],pointRadius:3 }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{labels:{color:'rgba(240,238,248,0.6)',font:{size:11},boxWidth:12}}, tooltip:{backgroundColor:'rgba(19,16,30,0.95)'} },
      scales:{
        x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'rgba(240,238,248,0.4)',font:{size:11}}},
        y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'rgba(240,238,248,0.4)',font:{size:11}},min:0,max:100}
      }
    }
  });
}


/* ════════════════════════════════════════════════
   MANAGE CHILDREN
════════════════════════════════════════════════ */
function themeColor(theme) {
  const themeMap = {
    'theme-rose': '#FF6B6B',
    'theme-sky': '#69DAFF',
    'theme-violet': '#A68CFF',
    'theme-mint': '#4ECDC4',
    'theme-gold': '#FFD166'
  };
  return themeMap[theme] || '#69DAFF';
}

function buildChildStats(children, sessions) {
  const stats = {};
  children.forEach(child => {
    stats[child.id] = {
      sessionCount: 0,
      balanceValues: [],
      lastSession: null,
      lastState: 'No data',
      lastConfidence: 0,
      lastTime: null,
      status: 'offline'
    };
  });

  sessions.forEach(session => {
    const childId = Number(session.child_id);
    if (!stats[childId]) return;
    const entry = stats[childId];
    entry.sessionCount += 1;
    const balance = computeBalance(session.scores);
    if (balance !== null) entry.balanceValues.push(balance);

    const time = parseDateTime(session.start_time);
    if (!entry.lastTime || (time && time > entry.lastTime)) {
      entry.lastTime = time;
      entry.lastSession = session;
      entry.lastState = session.state || 'Neutral / Evaluating';
      entry.lastConfidence = session.confidence || 0;
    }
  });

  Object.values(stats).forEach(entry => {
    const avgBalance = entry.balanceValues.length ? average(entry.balanceValues) : 0;
    entry.balance = Math.round(avgBalance);

    if (entry.lastTime) {
      const hours = (Date.now() - entry.lastTime.getTime()) / (1000 * 60 * 60);
      if (entry.lastState === 'Agitated / Angry' && entry.lastConfidence >= 45) {
        entry.status = 'attention';
      } else if (hours <= 6) {
        entry.status = 'active';
      }
    }
  });

  return stats;
}

async function renderChildren() {
  const grid = document.getElementById('childrenGrid');
  if (!grid) return;

  try {
    const children = await loadChildren();
    let analysis = null;
    try {
      analysis = await ensureAnalysisDataAsync();
    } catch (err) {
      analysis = null;
    }
    const sessions = analysis?.sessions || [];
    const childMap = new Map(children.map(c => [c.id, c]));
    const stats = buildChildStats(children, sessions);
    const statusMap = {
      active:    { label:'Active Now',   cls:'chip-cyan' },
      offline:   { label:'Offline',      cls:'' },
      attention: { label:'⚠ Attention', cls:'chip-coral' }
    };
    const stateColors = {
      'Happy / Confident': 'var(--gold)',
      'High Engagement': 'var(--violet)',
      'Agitated / Angry': 'var(--coral)',
      'Disengaged / Sad': 'var(--muted)',
      'Neutral / Evaluating': 'var(--cyan)'
    };

    if (!children.length) {
      grid.innerHTML = '<div class="muted">No children profiles yet.</div>';
      document.getElementById('statChildren').textContent = '0';
      renderActivityLog([], childMap);
      return;
    }

    grid.innerHTML = children.map(child => {
      const entry = stats[child.id];
      const status = statusMap[entry.status] || statusMap.offline;
      const ringColor = themeColor(child.theme);
      const stateColor = stateColors[entry.lastState] || 'var(--text)';
      const ageLabel = child.age ? `${child.age} years old` : 'Age: —';
      const lastTimeLabel = entry.lastTime ? formatLabel(entry.lastTime) : 'No sessions yet';
      return `
      <div class="child-card" data-id="${child.id}">
        <span class="chip ${status.cls} child-status-badge">${status.label}</span>
        <div class="child-avatar-wrap">
          <div class="child-avatar">${child.emoji || child.avatar || '🧒'}</div>
          <div class="child-avatar-ring" style="border-color:${ringColor}"></div>
        </div>
        <div class="child-name">${child.name}</div>
        <div class="child-age">${ageLabel}</div>
        <div class="child-stats">
          <div class="child-stat"><span>Sessions</span><span style="font-weight:700;color:${ringColor}">${entry.sessionCount}</span></div>
          <div class="child-stat"><span>Latest State</span><span style="color:${stateColor}">${entry.lastState}</span></div>
          <div class="child-stat"><span>Balance</span><span style="font-weight:700;color:${entry.balance >= 70 ? 'var(--mint)' : 'var(--coral)'}">${entry.balance}%</span></div>
        </div>
        <div class="child-meta" style="font-size:11px;color:var(--muted);margin-top:8px">Last session: ${lastTimeLabel}</div>
        <div class="child-actions">
          <button class="btn btn-ghost btn-sm" style="flex:1" onclick="openEditModal('${child.id}')">
            <span class="material-symbols-outlined" style="font-size:15px">edit</span> Edit
          </button>
          <button class="btn btn-danger btn-sm" onclick="deleteChild('${child.id}','${child.name}')">
            <span class="material-symbols-outlined" style="font-size:15px">delete</span>
          </button>
        </div>
      </div>`;
    }).join('') + `
      <div class="add-child-tile" onclick="showPage('add-child')">
        <div class="add-child-icon"><span class="material-symbols-outlined">add</span></div>
        <h3 style="font-family:var(--fh);font-size:15px;font-weight:700">Add Child Profile</h3>
        <p style="font-size:12px;color:var(--muted)">Manage another household member</p>
      </div>`;

    renderActivityLog(sessions, childMap);
    document.getElementById('statChildren').textContent = children.length.toString();
  } catch (err) {
    grid.innerHTML = '<div class="muted">Unable to load children right now.</div>';
  }
}

function renderActivityLog(sessions, childMap) {
  const log = document.getElementById('activityLog');
  if (!log) return;
  if (!sessions.length) {
    log.innerHTML = '<div class="muted">No recent sessions to display.</div>';
    return;
  }

  const sorted = sessions.slice().sort((a, b) => {
    const aTime = parseDateTime(a.start_time)?.getTime() || 0;
    const bTime = parseDateTime(b.start_time)?.getTime() || 0;
    return bTime - aTime;
  }).slice(0, 4);

  const stateColors = {
    'Happy / Confident': 'var(--gold)',
    'High Engagement': 'var(--violet)',
    'Agitated / Angry': 'var(--coral)',
    'Disengaged / Sad': 'var(--muted)',
    'Neutral / Evaluating': 'var(--cyan)'
  };

  log.innerHTML = sorted.map(session => {
    const child = childMap.get(Number(session.child_id));
    const time = parseDateTime(session.start_time);
    const color = stateColors[session.state] || 'var(--cyan)';
    return `
      <div class="activity-item">
        <div class="activity-dot" style="background:${color}"></div>
        <div class="activity-text">${child ? child.name : 'Child'} played <strong>${formatGameLabel(session.game_slug)}</strong> — ${session.state}</div>
        <div class="activity-time">${time ? formatRelativeTime(time) : 'Recently'}</div>
      </div>`;
  }).join('');
}

window.deleteChild = function(id, name) {
  showConfirm(`Remove ${name}?`, `This will permanently delete ${name}'s profile and all associated data.`, async () => {
    try {
      await Api.request('/api/parent_children_api.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'delete', child_id: id })
      });
      await renderChildren();
      showToast(`${name}'s profile removed`, 'info');
    } catch (err) {
      showToast('Unable to remove profile right now.', 'error');
    }
  });
};

window.openEditModal = function(id) {
  const child = childrenState.list.find(c => String(c.id) === String(id));
  if (!child) return;
  document.getElementById('editChildId').value = child.id;
  document.getElementById('editChildName').value = child.name || '';
  document.getElementById('editChildAge').value = child.age ?? '';
  document.getElementById('editChildEmoji').value = child.emoji || '🧒';
  document.getElementById('editChildModal').classList.add('active');
};
document.getElementById('editChildModal')?.addEventListener('click', e => { if(e.target.id==='editChildModal') e.target.classList.remove('active'); });
document.getElementById('closeEditModal')?.addEventListener('click', () => document.getElementById('editChildModal').classList.remove('active'));
document.getElementById('saveEditChild')?.addEventListener('click', async () => {
  const id   = document.getElementById('editChildId').value;
  const name = document.getElementById('editChildName').value.trim();
  if (!name) { showToast('Name cannot be empty', 'error'); return; }
  const ageValue = document.getElementById('editChildAge').value;
  const emoji = document.getElementById('editChildEmoji').value;
  try {
    await Api.request('/api/parent_children_api.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'update', child_id: id, nickname: name, age: ageValue, emoji })
    });
    document.getElementById('editChildModal').classList.remove('active');
    await renderChildren();
    showToast(`${name}'s profile updated`, 'success');
  } catch (err) {
    showToast('Unable to update profile right now.', 'error');
  }
});

/* ════════════════════════════════════════════════
   ADD CHILD
════════════════════════════════════════════════ */
let selectedAvatar  = '🧒';

function resetAddChildForm() {
  selectedAvatar = '🧒';
  const nameEl = document.getElementById('childName');
  const ageEl = document.getElementById('childAge');
  if (nameEl) nameEl.value = '';
  if (ageEl) ageEl.value = '';
  document.getElementById('avatarPreview').textContent = '🧒';
  document.querySelectorAll('.avatar-opt').forEach(o => o.classList.toggle('sel', o.dataset.emoji === '🧒'));
  clearErrors();
}

function clearErrors() {
  document.querySelectorAll('.form-input.error').forEach(i => i.classList.remove('error'));
  document.querySelectorAll('.form-error.show').forEach(e => e.classList.remove('show'));
}

document.querySelectorAll('.avatar-opt').forEach(o => {
  o.addEventListener('click', () => {
    selectedAvatar = o.dataset.emoji;
    document.getElementById('avatarPreview').textContent = selectedAvatar;
    document.querySelectorAll('.avatar-opt').forEach(a => a.classList.remove('sel'));
    o.classList.add('sel');
  });
});
document.getElementById('addChildSubmit')?.addEventListener('click', async () => {
  clearErrors();
  const nameEl = document.getElementById('childName');
  let valid = true;
  if (!nameEl.value.trim()) { nameEl.classList.add('error'); document.getElementById('nameError').classList.add('show'); valid = false; }
  if (!valid) return;
  const ageEl = document.getElementById('childAge');
  try {
    await Api.request('/api/parent_children_api.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'create',
        nickname: nameEl.value.trim(),
        age: ageEl?.value || '',
        emoji: selectedAvatar
      })
    });
    showToast('Profile created successfully!', 'success');
    await renderChildren();
    showPage('manage-children');
  } catch (err) {
    showToast('Unable to create profile right now.', 'error');
  }
});

/* ════════════════════════════════════════════════
/* ════════════════════════════════════════════════
   NEWS
════════════════════════════════════════════════ */
let activeNewsFilter = 'All';

const CAT_COLORS = { Psychology:'chip-cyan', Health:'chip-violet', Education:'chip-gold', Research:'chip-mint', Development:'chip-mint', Nutrition:'chip-gold', Safety:'chip-coral' };

function renderNews(filter) {
  activeNewsFilter = filter || 'All';
  const filtered = activeNewsFilter === 'All' ? CONTENT.news : CONTENT.news.filter(n => n.cat === activeNewsFilter);

  // Sync both filter-pill rows (classic tabs + cat-rec chips)
  document.querySelectorAll('.filter-pill').forEach(p =>
    p.classList.toggle('active', p.dataset.cat === activeNewsFilter));
  document.querySelectorAll('.cat-rec-chip').forEach(p =>
    p.classList.toggle('active', p.dataset.cat === activeNewsFilter));

  const grid = document.getElementById('newsGrid');
  if (!grid) return;

  if (!filtered.length) {
    grid.innerHTML = '<p style="color:var(--muted);grid-column:1/-1;text-align:center;padding:40px">No articles in this category yet.</p>';
    return;
  }

  grid.innerHTML = filtered.map(n => `
    <div class="news-card" onclick="openArticleModal(${n.id})">
      <img class="news-card-img" src="${n.img}" alt="${n.title}" width="260" height="160" loading="lazy"/>
      <div class="news-card-body">
        <span class="chip ${CAT_COLORS[n.cat]||'chip-cyan'}">${n.cat}</span>
        <div class="news-card-title">${n.title}</div>
        <div class="news-card-desc">${n.desc.substring(0,90)}…</div>
        <div class="news-card-meta"><span>${n.date}</span><span style="color:var(--cyan)">${n.readTime} read</span></div>
      </div>
    </div>`).join('');
}
// Expose globally so search-integration.js can call it
window.renderNews = renderNews;

window.openArticleModal = function(id) {
  const n = CONTENT.news.find(x => x.id === id);
  if (!n) return;
  document.getElementById('articleModal').classList.add('active');
  document.getElementById('articleModalBody').innerHTML = `
    <img src="${n.img}" alt="${n.title}" style="width:100%;height:200px;object-fit:cover;border-radius:14px;margin-bottom:16px;" loading="lazy"/>
    <span class="chip ${CAT_COLORS[n.cat]||'chip-cyan'}" style="margin-bottom:12px">${n.cat}</span>
    <h2 style="font-family:var(--fh);font-size:20px;font-weight:800;margin:10px 0 8px;line-height:1.35">${n.title}</h2>
    <div style="font-size:12px;color:var(--muted);margin-bottom:16px">📅 ${n.date} · ⏱ ${n.readTime} read</div>
    <p style="font-size:14px;color:var(--muted);line-height:1.75">${n.desc}</p>
    <p style="font-size:14px;color:var(--muted);line-height:1.75;margin-top:12px">Research in child developmental psychology continues to uncover how early experiences shape emotional regulation. Parents who engage actively with their children's emotional signals tend to see significantly better outcomes in social development and academic performance.</p>`;
};
document.getElementById('articleModal')?.addEventListener('click', e => { if(e.target.id==='articleModal') e.target.classList.remove('active'); });
document.getElementById('closeArticleModal')?.addEventListener('click', () => document.getElementById('articleModal').classList.remove('active'));

// Classic filter-pill tabs (below hero) — clear search state when clicked
document.querySelectorAll('.filter-pill').forEach(p => {
  p.addEventListener('click', () => {
    if (window.searchIntegration) window.searchIntegration.clearResults();
    else renderNews(p.dataset.cat);
  });
});

// Cat-rec chips (above hero) — same behaviour
document.querySelectorAll('.cat-rec-chip').forEach(p => {
  p.addEventListener('click', () => {
    if (window.searchIntegration) window.searchIntegration.clearResults();
    renderNews(p.dataset.cat);
  });
});

// Search integration - Auto-navigate to news page when typing
document.getElementById('searchInput')?.addEventListener('focus', function() {
  if (currentPage !== 'news') showPage('news');
});

/* ════════════════════════════════════════════════
   REPORTS
════════════════════════════════════════════════ */
async function renderReports() {
  const list = document.getElementById('reportList');
  if (!list) return;

  const start = document.getElementById('rangeStart')?.value || '';
  const end = document.getElementById('rangeEnd')?.value || '';

  try {
    const [children] = await Promise.all([
      loadChildren().catch(() => childrenState.list),
      loadReports(start, end)
    ]);

    const summary = reportsState.summary;
    const childCount = children.length;
    const subtitleEl = document.getElementById('reportSubtitle');
    if (summary) {
      document.getElementById('reportRange').textContent = `Report Window — ${summary.range.start} → ${summary.range.end}`;
      document.getElementById('reportChildCount').textContent = `${childCount} Children`;
      if (subtitleEl) subtitleEl.textContent = `${summary.report_count} analyses in range`;
      document.getElementById('reportBalance').textContent = `${summary.balance.toFixed(0)}%`;
      document.getElementById('reportJoy').textContent = `${summary.joy.toFixed(0)}%`;
      document.getElementById('reportStress').textContent = `${summary.stress.toFixed(0)}%`;
      document.getElementById('reportSessions').textContent = `${summary.sessions}`;
    } else {
      document.getElementById('reportRange').textContent = 'Report Window — --';
      document.getElementById('reportChildCount').textContent = `${childCount} Children`;
      document.getElementById('reportBalance').textContent = '--';
      document.getElementById('reportJoy').textContent = '--';
      document.getElementById('reportStress').textContent = '--';
      document.getElementById('reportSessions').textContent = '--';
      if (subtitleEl) subtitleEl.textContent = 'Based on analysis history';
    }

    if (!reportsState.reports.length) {
      list.innerHTML = '<div class="muted" style="padding:10px">No analysis reports in this range.</div>';
      return;
    }

    list.innerHTML = reportsState.reports.map(r => `
      <div class="report-item">
        <div class="report-file-icon"><span class="material-symbols-outlined">description</span></div>
        <div class="report-info">
          <div class="report-name">${r.child_name} · ${r.period_start.slice(0, 10)} → ${r.period_end.slice(0, 10)}</div>
          <div class="report-meta">${r.session_count} sessions · ${r.dominant_state} · ${r.confidence.toFixed(0)}%</div>
        </div>
        <div class="report-actions">
          <button class="btn btn-ghost btn-sm" onclick="previewReport('${r.id}')">Preview</button>
          <button class="btn btn-primary btn-sm" onclick="downloadReport('${r.id}')">
            <span class="material-symbols-outlined" style="font-size:15px">download</span>
          </button>
        </div>
      </div>`).join('');
  } catch (err) {
    list.innerHTML = '<div class="muted" style="padding:10px">Unable to load reports right now.</div>';
  }
}

window.previewReport  = id => {
  const report = reportsState.reports.find(r => String(r.id) === String(id));
  if (!report) { showToast('Report not found', 'error'); return; }
  showToast(`Previewing ${report.child_name}'s report…`, 'info');
};
window.downloadReport = function(id) {
  const report = reportsState.reports.find(r => String(r.id) === String(id));
  if (!report) { showToast('Report not found', 'error'); return; }
  const content = `SENTIMENT HUB — REPORT\n${'─'.repeat(40)}\n${report.child_name}\n${report.period_start} → ${report.period_end}\n\nBalance Score: ${report.balance}%\nJoy Index: ${report.joy}%\nStress Level: ${report.stress}%\nSessions: ${report.session_count}\nState: ${report.dominant_state} (${report.confidence.toFixed(0)}%)`;
  const blob = new Blob([content], { type:'text/plain' });
  const url  = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = `sentiment_report_${report.child_name.replace(/[^a-z0-9]/gi,'_')}.txt`;
  document.body.appendChild(a); a.click();
  document.body.removeChild(a); URL.revokeObjectURL(url);
  showToast('Report downloaded!', 'success');
};

document.getElementById('generateReportBtn')?.addEventListener('click', async () => {
  const prog = document.getElementById('genProgress');
  const fill = document.getElementById('genBarFill');
  const status = document.getElementById('genStatus');
  const btn  = document.getElementById('generateReportBtn');
  prog.classList.add('active');
  btn.disabled = true;
  const steps = ['Refreshing analysis…','Updating summaries…','Syncing report list…'];
  let pct = 0, step = 0;
  fill.style.width = '0%';
  const timer = setInterval(() => {
    pct = Math.min(pct + 6, 100);
    fill.style.width = pct + '%';
    if (step < steps.length && pct > step * 33) { if(status) status.textContent = steps[step]; step++; }
    if (pct >= 100) {
      clearInterval(timer);
      setTimeout(async () => {
        prog.classList.remove('active');
        btn.disabled = false;
        await renderReports();
        showToast('Reports refreshed from analysis history.', 'success');
      }, 350);
    }
  }, 80);
});

/* ════════════════════════════════════════════════
   NOTIFICATIONS
════════════════════════════════════════════════ */
function loadNotifReadState() {
  const raw = localStorage.getItem('sh_notif_read');
  if (!raw) return new Set();
  try {
    const ids = JSON.parse(raw);
    return new Set(Array.isArray(ids) ? ids : []);
  } catch (err) {
    return new Set();
  }
}

function persistNotifReadState() {
  localStorage.setItem('sh_notif_read', JSON.stringify(Array.from(notificationsState.readIds)));
}

async function renderNotifications() {
  const list = document.getElementById('notifList');
  if (!list) return;

  try {
    const [children, analysis] = await Promise.all([
      loadChildren().catch(() => childrenState.list),
      ensureAnalysisDataAsync()
    ]);
    const childMap = new Map((children || []).map(c => [c.id, c]));
    const sessions = analysis?.sessions || [];

    refreshNotificationsState(sessions, childMap);
    const notifs = notificationsState.items;
    const typeMap = {
      alert:   { icon:'warning',   color:'var(--coral)',  bg:'rgba(255,107,107,0.1)',  action:'Analyze Now' },
      insight: { icon:'insights',  color:'var(--cyan)',   bg:'rgba(105,218,255,0.1)',  action:'View Pattern' }
    };
    const critical = notifs.filter(n => n.severity === 'critical');
    const recent   = notifs.filter(n => n.severity !== 'critical');

    list.innerHTML = `
      <div class="notif-sect">🔴 Critical Alerts</div>
      ${critical.length ? critical.map(n => notifCard(n, typeMap)).join('') : '<p style="color:var(--muted);font-size:13px;padding:8px">No critical alerts 🎉</p>'}
      <div class="notif-sect">💬 Recent Updates</div>
      ${recent.length ? recent.map(n => notifCard(n, typeMap)).join('') : '<p style="color:var(--muted);font-size:13px;padding:8px">No recent updates</p>'}
      <div class="notif-sect">⚙️ Notification Preferences</div>
      <div class="pref-grid" id="prefGrid"></div>`;
    renderPrefGrid();
    updateNotifBadge();
  } catch (err) {
    list.innerHTML = '<p style="color:var(--muted);font-size:13px;padding:8px">Unable to load notifications.</p>';
  }
}

function notifCard(n, typeMap) {
  const t = typeMap[n.type] || typeMap.insight;
  return `
  <div class="notif-item ${n.read ? '' : 'unread'}" style="--nc:${t.color}" onclick="markNotifRead('${n.id}')">
    <div class="notif-icon-wrap" style="background:${t.bg}">
      <span class="material-symbols-outlined" style="color:${t.color}">${t.icon}</span>
    </div>
    <div class="notif-content">
      <div class="notif-title">${n.title}</div>
      <div class="notif-desc">${n.desc}</div>
      <div class="notif-meta">
        <span class="notif-time">${n.time}</span>
        <button class="btn btn-ghost btn-sm" style="padding:4px 10px;font-size:11px" onclick="event.stopPropagation()">${n.action || 'View'}</button>
      </div>
    </div>
  </div>`;
}

window.markNotifRead = id => {
  notificationsState.readIds.add(id);
  persistNotifReadState();
  renderNotifications();
};
document.getElementById('markAllReadBtn')?.addEventListener('click', () => {
  notificationsState.items.forEach(item => notificationsState.readIds.add(item.id));
  persistNotifReadState();
  renderNotifications();
  showToast('All notifications marked as read', 'info');
});

function renderPrefGrid() {
  const s  = SettingsStore.get();
  const pg = document.getElementById('prefGrid');
  if (!pg) return;
  const prefs = [
    { key:'pushAlerts',  emoji:'🔔', name:'Push Alerts',  desc:'Real-time spikes' },
    { key:'emailDigest', emoji:'📧', name:'Email Digest', desc:'Weekly summaries' },
    { key:'quietHours',  emoji:'🌙', name:'Quiet Hours',  desc:'Mute 22:00–07:00' }
  ];
  pg.innerHTML = prefs.map(p => `
    <div class="pref-card">
      <div class="pref-top">
        <span class="pref-emoji">${p.emoji}</span>
        <label class="toggle"><input type="checkbox" ${s[p.key] ? 'checked' : ''} onchange="SettingsStore.update('${p.key}',this.checked);showToast('Preference saved','success')"/>
        <div class="toggle-track"></div><div class="toggle-thumb"></div></label>
      </div>
      <div class="pref-name">${p.name}</div><div class="pref-desc">${p.desc}</div>
    </div>`).join('');
}

/* ════════════════════════════════════════════════
   PROFILE
════════════════════════════════════════════════ */
async function renderProfile() {
  try {
    const [profile, children, analysis, reports] = await Promise.all([
      loadProfile(),
      loadChildren().catch(() => childrenState.list),
      ensureAnalysisDataAsync(),
      loadReports().catch(() => reportsState)
    ]);

    const avatar = document.getElementById('profileAvatar');
    if (avatar && !avatar.src) {
      avatar.src = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAlr97Z5omvQhc2RW7kBaDTZKNfEQHJuNntt8xo5WPzAgGPj9EQWW7GnNqDVFkQtIW6zIFqNStbRN2R2nISVI2rcpCncH10WWasCgHm7nHJIJbqjgIzmZjPpAwXF9bqORn_do7Fkevp9Wb3cq2FJUcYcu9D4NAYVDXxMgD40XzszF0hNsqLFFXTlMvw0reRDJx2NGjIhOie2SnlG7PFW6zMPHsywm4VPdzO8csmFLfWV9tvTsfMkg6c2G3EwdG0aerMltgXCePVBEQ';
    }

    const sessions = analysis?.sessions || [];
    const avgScore = sessions.length ? average(sessions.map(s => computeBalance(s.scores)).filter(v => v !== null)) : 0;

    const planLabel = profile?.plan ? profile.plan.charAt(0).toUpperCase() + profile.plan.slice(1) : '--';
    const statusLabel = profile?.status ? `Status: ${profile.status}` : '--';
    const joined = profile?.joined_year || '--';

    const fields = [
      ['profileName', profile?.name || '--'],
      ['profileEmail', profile?.email || '--'],
      ['profileMemberSince', joined],
      ['profilePlan', planLabel],
      ['profileStatus', statusLabel],
      ['profileName2', profile?.name || '--'],
      ['profileEmail2', profile?.email || '--'],
      ['profilePlanBadge', planLabel],
      ['profileChildCount', children.length.toString()],
      ['profileReportCount', (reports?.reports?.length || 0).toString()],
      ['profileAvgScore', avgScore ? `${avgScore.toFixed(0)}%` : '--'],
      ['settingsEmail', profile?.email || '--']
    ];

    fields.forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    });

    const stats = buildChildStats(children, sessions);
    const profileKids = document.getElementById('profileKidsList');
    if (profileKids) {
      profileKids.innerHTML = children.map(child => {
        const entry = stats[child.id];
        const status = entry?.status === 'active' ? 'Active' : entry?.status === 'attention' ? 'Needs Attention' : 'Offline';
        return `
          <div class="editable-field" onclick="showPage('manage-children')">
            <div class="d-flex align-items-center gap-3">
              <div style="width:40px;height:40px;border-radius:12px;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">${child.emoji || child.avatar || '🧒'}</div>
              <div><div class="editable-lbl">${child.name}</div><div class="editable-val" style="font-size:12px">${child.age ? `${child.age} years old` : 'Age: —'} · ${status}</div></div>
            </div>
            <span class="material-symbols-outlined edit-icon">chevron_right</span>
          </div>`;
      }).join('');
    }
  } catch (err) {
    showToast('Unable to load profile details.', 'error');
  }
}

/* ════════════════════════════════════════════════
   SETTINGS
════════════════════════════════════════════════ */
function renderSettings() {
  const s = SettingsStore.get();
  document.getElementById('darkModeBtn')?.classList.toggle('active',  s.theme==='dark');
  document.getElementById('lightModeBtn')?.classList.toggle('active', s.theme==='light');
  const toggleMap = {
    'settingPush':'pushAlerts','settingEmail':'emailDigest','settingQuiet':'quietHours',
    'settingShare':'shareTeachers','settingAnaly':'analytics','setting2FA':'twoFactor','settingLoginN':'loginNotifications'
  };
  Object.entries(toggleMap).forEach(([id,key]) => { const el=document.getElementById(id); if(el) el.checked=!!s[key]; });
  const ret = document.getElementById('settingRetention');
  if (ret) ret.value = s.dataRetention||'12 months';
  loadProfile().then(profile => {
    const email = profile?.email || '--';
    const el = document.getElementById('settingsEmail');
    if (el) el.textContent = email;
  }).catch(() => {});
}

document.getElementById('darkModeBtn')?.addEventListener('click',  () => { applyTheme(true);  SettingsStore.update('theme','dark'); });
document.getElementById('lightModeBtn')?.addEventListener('click', () => { applyTheme(false); SettingsStore.update('theme','light'); });

const settingToggles = {
  'settingPush':'pushAlerts','settingEmail':'emailDigest','settingQuiet':'quietHours',
  'settingShare':'shareTeachers','settingAnaly':'analytics','setting2FA':'twoFactor','settingLoginN':'loginNotifications'
};
Object.entries(settingToggles).forEach(([id,key]) => {
  document.getElementById(id)?.addEventListener('change', function() { SettingsStore.update(key,this.checked); showToast('Setting saved','success'); });
});
document.getElementById('settingRetention')?.addEventListener('change', function() { SettingsStore.update('dataRetention',this.value); showToast('Setting saved','success'); });
document.getElementById('changePasswordBtn')?.addEventListener('click', () => showToast('Password reset email sent','info'));
document.querySelectorAll('.sign-out-btn').forEach(btn => {
  btn.addEventListener('click', () => showConfirm('Sign Out?','You will be signed out. Your data will be saved.',() => showToast('Signed out. Goodbye! 👋','info')));
});

window.resetPreferences = function() {
  showConfirm('Reset Preferences?','This will reset local dashboard preferences.',() => {
    SettingsStore.reset();
    applyTheme(SettingsStore.get().theme === 'dark');
    renderSettings();
    showToast('Preferences reset', 'info');
  });
};

/* ════════════════════════════════════════════════
   INIT
════════════════════════════════════════════════ */
const _oldGuide = document.getElementById('guideOverlay');
if (_oldGuide) _oldGuide.remove();
window.nextGuide  = () => window.Tour?.start?.();
window.closeGuide = () => {};

document.addEventListener('DOMContentLoaded', () => {
  updateNotifBadge();
  showPage('dashboard');
  setTimeout(() => { if (window.Tour) window.Tour.initIfFirstVisit(); }, 1400);
});
