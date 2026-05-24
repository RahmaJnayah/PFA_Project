/* ═══════════════════════════════════════════════════════════
   DATA.JS — State Management & Mock Data
   Sentiment Hub | Children Emotional Dashboard
═══════════════════════════════════════════════════════════ */

const DB = {
  // ── Default seed data ──────────────────────────────────
  defaults: {
    children: [
      {
        id: 'child_1',
        name: 'Sophia',
        birthdate: '2012-04-15',
        gender: 'girl',
        avatar: '👧',
        interests: ['painting', 'reading', 'music'],
        sharing: true,
        alerts: true,
        color: '#69DAFF',
        status: 'active',
        screenTime: '2h 15m',
        mood: 'Energetic',
        moodEmoji: '⚡',
        wellnessScore: 87,
        weeklyData: [88, 76, 62, 81, 92, 85, 78]
      },
      {
        id: 'child_2',
        name: 'Leo',
        birthdate: '2016-08-22',
        gender: 'boy',
        avatar: '👦',
        interests: ['sports', 'science', 'gaming'],
        sharing: false,
        alerts: true,
        color: '#A68CFF',
        status: 'offline',
        screenTime: '45m',
        mood: 'Calm',
        moodEmoji: '😌',
        wellnessScore: 91,
        weeklyData: [75, 82, 69, 88, 90, 83, 78]
      },
      {
        id: 'child_3',
        name: 'Ava',
        birthdate: '2009-11-03',
        gender: 'girl',
        avatar: '🧒',
        interests: ['music', 'painting'],
        sharing: true,
        alerts: true,
        color: '#FF6B6B',
        status: 'attention',
        screenTime: '5h 40m',
        mood: 'Withdrawn',
        moodEmoji: '😶',
        wellnessScore: 54,
        weeklyData: [45, 50, 58, 52, 60, 55, 54]
      }
    ],

    notifications: [
      { id: 'n1', type: 'alert', title: 'Frustration Spike — Leo', desc: 'Elevated vocal stress detected in the last 15 minutes. Leo may need downtime.', time: '2 mins ago', read: false, childId: 'child_2', action: 'Analyze' },
      { id: 'n2', type: 'insight', title: 'New Activity Pattern — Maya', desc: '24% increase in positive sentiment during creative play sessions.', time: '1 hour ago', read: false, childId: 'child_1', action: 'View' },
      { id: 'n3', type: 'report', title: "Weekly Report Ready — Sophia", desc: 'Comprehensive emotional digest for this week is available.', time: '3 hours ago', read: true, childId: 'child_1', action: 'Download' },
      { id: 'n4', type: 'article', title: 'New Article: Managing Tantrums', desc: "Based on Leo's recent sentiment trends, this article was curated for you.", time: '5 hours ago', read: true, childId: 'child_2', action: 'Read' },
      { id: 'n5', type: 'map', title: "Leo's Mood Map Updated", desc: 'New correlations found between sleep patterns and morning mood scores.', time: 'Yesterday', read: true, childId: 'child_2', action: 'View Map' }
    ],

    profile: {
      name: 'Alex Mercer',
      email: 'alex.mercer@prism.io',
      phone: '+1 (555) 987-6543',
      location: 'San Francisco, CA',
      plan: 'Family Intelligence Plus',
      billing: 'Oct 12, 2024',
      price: '$14.99/mo',
      avatar: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAlr97Z5omvQhc2RW7kBaDTZKNfEQHJuNntt8xo5WPzAgGPj9EQWW7GnNqDVFkQtIW6zIFqNStbRN2R2nISVI2rcpCncH10WWasCgHm7nHJIJbqjgIzmZjPpAwXF9bqORn_do7Fkevp9Wb3cq2FJUcYcu9D4NAYVDXxMgD40XzszF0hNsqLFFXTlMvw0reRDJx2NGjIhOie2SnlG7PFW6zMPHsywm4VPdzO8csmFLfWV9tvTsfMkg6c2G3EwdG0aerMltgXCePVBEQ',
      joined: '2023'
    },

    settings: {
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

    reports: [
      { id: 'r1', name: 'Weekly Sentiment — Leo (Oct 1–7)', date: 'Oct 8, 2024', size: '2.4 MB', childId: 'child_2' },
      { id: 'r2', name: 'Monthly Growth — Sophia (September)', date: 'Oct 1, 2024', size: '5.1 MB', childId: 'child_1' },
      { id: 'r3', name: 'Annual Summary — All Children (2023)', date: 'Jan 5, 2024', size: '12.8 MB', childId: null }
    ]
  },

  // ── Initialize localStorage ────────────────────────────
  init() {
    if (!localStorage.getItem('sh_initialized')) {
      localStorage.setItem('sh_children', JSON.stringify(this.defaults.children));
      localStorage.setItem('sh_notifications', JSON.stringify(this.defaults.notifications));
      localStorage.setItem('sh_profile', JSON.stringify(this.defaults.profile));
      localStorage.setItem('sh_settings', JSON.stringify(this.defaults.settings));
      localStorage.setItem('sh_reports', JSON.stringify(this.defaults.reports));
      localStorage.setItem('sh_initialized', 'true');
    }
  },

  reset() {
    localStorage.removeItem('sh_initialized');
    this.init();
  },

  // ── Children CRUD ─────────────────────────────────────
  getChildren() {
    return JSON.parse(localStorage.getItem('sh_children') || '[]');
  },

  saveChildren(children) {
    localStorage.setItem('sh_children', JSON.stringify(children));
  },

  addChild(child) {
    const children = this.getChildren();
    child.id = 'child_' + Date.now();
    child.status = 'active';
    child.screenTime = '0m';
    child.wellnessScore = 75;
    child.weeklyData = [70, 72, 75, 73, 76, 74, 75];
    child.color = ['#69DAFF', '#A68CFF', '#FFD166', '#4ECDC4', '#FF6B6B'][Math.floor(Math.random() * 5)];
    children.push(child);
    this.saveChildren(children);
    // also add a notification
    this.addNotification({
      type: 'insight',
      title: `${child.name}'s profile created`,
      desc: `Welcome! Start tracking ${child.name}'s emotional journey.`,
      time: 'Just now',
      childId: child.id,
      action: 'View'
    });
    return child;
  },

  removeChild(id) {
    const children = this.getChildren().filter(c => c.id !== id);
    this.saveChildren(children);
  },

  updateChild(id, updates) {
    const children = this.getChildren().map(c => c.id === id ? { ...c, ...updates } : c);
    this.saveChildren(children);
  },

  // ── Notifications ─────────────────────────────────────
  getNotifications() {
    return JSON.parse(localStorage.getItem('sh_notifications') || '[]');
  },

  addNotification(notif) {
    const notifs = this.getNotifications();
    notif.id = 'n_' + Date.now();
    notif.read = false;
    notifs.unshift(notif);
    localStorage.setItem('sh_notifications', JSON.stringify(notifs));
  },

  markAllRead() {
    const notifs = this.getNotifications().map(n => ({ ...n, read: true }));
    localStorage.setItem('sh_notifications', JSON.stringify(notifs));
  },

  markRead(id) {
    const notifs = this.getNotifications().map(n => n.id === id ? { ...n, read: true } : n);
    localStorage.setItem('sh_notifications', JSON.stringify(notifs));
  },

  getUnreadCount() {
    return this.getNotifications().filter(n => !n.read).length;
  },

  // ── Profile ───────────────────────────────────────────
  getProfile() {
    return JSON.parse(localStorage.getItem('sh_profile') || '{}');
  },

  updateProfile(updates) {
    const profile = { ...this.getProfile(), ...updates };
    localStorage.setItem('sh_profile', JSON.stringify(profile));
    return profile;
  },

  // ── Settings ──────────────────────────────────────────
  getSettings() {
    return JSON.parse(localStorage.getItem('sh_settings') || '{}');
  },

  updateSetting(key, value) {
    const settings = this.getSettings();
    settings[key] = value;
    localStorage.setItem('sh_settings', JSON.stringify(settings));
  },

  // ── Reports ───────────────────────────────────────────
  getReports() {
    return JSON.parse(localStorage.getItem('sh_reports') || '[]');
  },

  addReport(report) {
    const reports = this.getReports();
    report.id = 'r_' + Date.now();
    reports.unshift(report);
    localStorage.setItem('sh_reports', JSON.stringify(reports));
    return report;
  }
};

// ── Static content (news, week data) ─────────────────────
const CONTENT = {
  news: [
    { id: 1, title: 'Curiosity Spikes in Adolescence Linked to Neural Growth', desc: 'High curiosity metrics correlate with rapid neural development and cognitive growth in children aged 8–14.', cat: 'Psychology', date: 'Oct 15, 2024', readTime: '4 min', img: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDzIqJC0NVxmh2uLh2gbGrxT9JyJRsowD_6ve_LvZXI3FAVRGpNEy7ZkgWVwTx2gPUiKCk7Tj608VOC4mEDKsTexYgqKEULvN-hM0pRflVp-jObPt2q6lOPrYJaL3iddynrWi0C9UauEoAYV1F9IBwDd9CKSgwoJyhdlT2ht0B4xTea77W_5YrW6AY43zxNbkFAv2Oz-rxwyJRL4CNCkQAqY9RMzIh9BURwztvcnGsa7ujspBvSy270mGlOb_eeu8l1ENgfDC8V6kY' },
    { id: 2, title: 'Screen Time vs. Emotional Resonance: A New Study', desc: 'How to use sentiment tracking to create healthier digital habits for the whole family.', cat: 'Health', date: 'Oct 12, 2024', readTime: '6 min', img: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBjP249PButR74WLPt26RzBPwpalF9TZe9i7fkpQUH6F9We28Ngg8LGU6c3pcVYRpEO3CClBMUUZJe5sBzDVpPHh4z7kF42td4djvAdJplRZqBh_SBD7qDC7uHQ-qIFiMpJbb-udNNXmu-tSN_hYajK2nmtfGiqGx9cmJQ5Ow0alkkOWpGMIyfTZQOwWVdLxfJc4uRHkz8kAypmnfcT_f6L40JP_GgufU_dWzHK7_k21SYrqNES_ZCArpXb0giHXzpFX8TKxKtiapw' },
    { id: 3, title: 'Encouraging Cognitive Joy Through Creative Discovery', desc: 'Identifying the activities that trigger the highest positive sentiment in your home.', cat: 'Education', date: 'Oct 10, 2024', readTime: '3 min', img: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBdbXd3saT5NFRR47q_DANTXIH_91CNrUWZl-83Yf4IXTC3V_dX8sM7-IlGygmhrN0q-jjwxgTdfdcx8-RbBmGX3_YxH8n2RJlxW5ihvgaOOQC0JCNd8HLBT_oCPb6j6iSenUP4wKRKZJI4B8O3nk7uuJiTsnbpgK67FCtNrUCg2Z3k8MJvJd55dSwxe6_LlN1pOJrgWWOgZ5FnsbTDctUgh8Dk8AHayEIH6Hs5M9BHe8NSNQ9bX1yjmARr4RWpBi1FFJ9qAPHt3s8' },
    { id: 4, title: 'The Power of Sensory Play on Neural Plasticity', desc: 'Longitudinal studies highlight how tactile stimulation significantly improves neural plasticity.', cat: 'Research', date: 'Oct 8, 2024', readTime: '7 min', img: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAJRk7JWiQ147XMnevFxO3_X5bSjPWGhz5pXkO6HUnSCFfn27F8i-JDmvqpH1_BF4k-PoMCxax5I4Dzgha1EwlM2Uw-l_jwdGS7Q37OVeamKv_RnZL5frJS6g0Z1REgdN4FojeF8ewkfNqdkJ-CyQRwy2hQ63pMvSQxn73IlI8G0eeBH78BqddoLKKJIW72saJ7fPnxAzDesZuvL_GNN1XDq38uTfHGQrZAgI5C0TIl7huNOl0Yy4qXnLkkDuN-5fkUa8oJ3nWxxQE' },
    { id: 5, title: 'Building Empathy Through Storytelling & Narrative', desc: 'How structured narratives help children develop emotional intelligence and connect with others.', cat: 'Psychology', date: 'Oct 5, 2024', readTime: '5 min', img: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAGNPkxK0hIF2kXVhlWf1Ckwlw8ygs4jwHBTrn4WnRIqWDoE9o_GXGtOjecW0_0lGGw62kPUnnMWDXWB0iqYYUp5a1P7gDPJXV3cfwHFEx7KgCoO67PpH8hlrNyb6TsOQ31SZChiV7zL_yZFTi2yzKb-_1MOL5t96EVys58xMmKauXA5jgj9hKeN4rmmfGPHqURYiDlpaDUayBNqxNV3ARB-h2oIEgUN8FRP0Allja6OQWc-50OWyw4dlKUfgvVbuv2Yl0Yw3y4mQ0' },
    { id: 6, title: 'Digital Boundaries & Long-term Emotional Wellbeing', desc: 'Setting healthy screen limits protects emotional development and sleep quality in children.', cat: 'Health', date: 'Oct 3, 2024', readTime: '4 min', img: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDANf1IWVPADssX8F8N8gCo5sC4CVyXpI2zuKbncQ5sOiWOyDepGzSGuY0E_2HL4tDa5KgEEDl-_vedW6Sr4QrjgT6jN2l4fJkax6d9dlFQGkiPEtD8P0NlRz9mra4IYjZ1cDVPnid7LF3nsvW_PlMPdyPn2_zCenQ__v0irdxsvsf6QOq05IoabU7tMN30swsJTwwzc6qxY7a_zZCj1TT8utRJZrF9jWHr3IjnNq2CAktQBfYRsmfWxPZJMzECUYuiigVYPrvYxVM' }
  ],

  weekDays: [
    { day: 'Mon', emoji: '😊', name: 'Joyful',   score: 88, color: '#FFD166', emotions: { Joy: 88, Calm: 75, Curiosity: 80, Frustration: 12, Anxiety: 15 }, summary: 'Leo and Sophia showed high curiosity during creative play. A great start to the week with 88% positive sentiment.' },
    { day: 'Tue', emoji: '😌', name: 'Calm',     score: 76, color: '#69DAFF', emotions: { Joy: 70, Calm: 85, Curiosity: 60, Frustration: 18, Anxiety: 20 }, summary: 'A quieter Tuesday — calm and focused. Leo completed his mindfulness session. Sophia had an excellent art class.' },
    { day: 'Wed', emoji: '😰', name: 'Anxious',  score: 62, color: '#FF6B6B', emotions: { Joy: 55, Calm: 50, Curiosity: 65, Frustration: 35, Anxiety: 48 }, summary: 'Sophia showed elevated anxiety during homework time (3–5 PM). Recommend a structured break next Wednesday.' },
    { day: 'Thu', emoji: '🎯', name: 'Focused',  score: 81, color: '#A68CFF', emotions: { Joy: 72, Calm: 78, Curiosity: 88, Frustration: 20, Anxiety: 22 }, summary: 'High focus and curiosity scores. Leo was particularly engaged during science class. A very productive Thursday.' },
    { day: 'Fri', emoji: '🎉', name: 'Happy',    score: 92, color: '#FFD166', emotions: { Joy: 95, Calm: 80, Curiosity: 78, Frustration: 8,  Anxiety: 10 }, summary: 'Best day of the week! Friday activities brought peak joy across all children. Ava scored her highest in 3 weeks.' },
    { day: 'Sat', emoji: '🌟', name: 'Excited',  score: 85, color: '#4ECDC4', emotions: { Joy: 88, Calm: 70, Curiosity: 82, Frustration: 15, Anxiety: 12 }, summary: 'Weekend energy! Outdoor activities significantly boosted positive sentiment. Creative play was the highlight.' },
    { day: 'Sun', emoji: '😴', name: 'Relaxed',  score: 78, color: '#A68CFF', emotions: { Joy: 75, Calm: 90, Curiosity: 55, Frustration: 12, Anxiety: 14 }, summary: 'Peaceful Sunday. High calm scores. All children showed healthy relaxation indicators — battery fully recharged.' }
  ]
};