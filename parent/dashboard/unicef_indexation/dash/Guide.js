/* ═══════════════════════════════════════════════════════════════════
   GUIDE.JS  —  Sentiment Hub First-Time User Guidance System
   ───────────────────────────────────────────────────────────────────
   Architecture
   ─────────────
   • TourEngine   – core state machine (steps, navigation, lifecycle)
   • Spotlight    – dim overlay with a "hole" cut out around the target
   • Tooltip      – popover that positions itself relative to the target
   • TourUI       – progress bar, dot indicators, control buttons
   • TourData     – the step definitions (easy to extend)
   • Public API   – window.Tour.start() / .replay() / .destroy()

   localStorage key:  "sh_tour_done"   → set to "1" when finished/skipped
   ═══════════════════════════════════════════════════════════════════ */

(function (window) {
  'use strict';

  /* ─────────────────────────────────────────────────────────────────
     1.  TOUR STEP DEFINITIONS
         Each step targets a CSS selector (or null for centred modal).
         position: 'top'|'bottom'|'left'|'right'|'center'
         page:     if set, the tour will navigate to that page first
         onEnter:  optional callback before step renders
  ───────────────────────────────────────────────────────────────── */
  const TOUR_STEPS = [
    /* ── Welcome ── */
    {
      id:       'welcome',
      target:   null,                  // null = centred welcome modal
      position: 'center',
      emoji:    '👋',
      title:    'Welcome to Sentiment Hub!',
      desc:     "You're about to explore a dashboard designed to help you understand and support your child's emotional wellbeing. This quick tour takes about 60 seconds.",
      page:     'dashboard',
    },

    /* ── Sidebar navigation ── */
    {
      id:       'sidebar',
      target:   '#sidebar',
      position: 'right',
      emoji:    '🗂️',
      title:    'Your Navigation Hub',
      desc:     "Everything lives here. Click any icon to switch pages. Expand the sidebar with the menu button to see full labels. The badge shows unread notifications.",
      page:     'dashboard',
    },

    /* ── Balance score ring ── */
    {
      id:       'score-ring',
      target:   '.score-ring',
      position: 'right',
      emoji:    '🎯',
      title:    'Emotional Balance Score',
      desc:     "This ring shows your child's overall emotional wellness score for the week — calculated from joy, calm, stress, and focus signals. Higher is better!",
      page:     'dashboard',
    },

    /* ── Character cards ── */
    {
      id:       'chars',
      target:   '.chars-grid',
      position: 'bottom',
      emoji:    '🧸',
      title:    'Emotion Characters',
      desc:     "Meet Joyla, Sparko, Ticky, and Binky! Each character represents an emotion type. Their percentage shows how dominant that emotion was this week.",
      page:     'dashboard',
    },

    /* ── Pulse chart ── */
    {
      id:       'pulse',
      target:   '.pulse-section',
      position: 'top',
      emoji:    '📈',
      title:    'The Emotional Pulse',
      desc:     "This live chart tracks sentiment across the day, week, or month. Switch between periods using the Day / Week / Month buttons above the chart.",
      page:     'dashboard',
    },

    /* ── Mood Map nav item ── */
    {
      id:       'moodmap-nav',
      target:   '.nav-item[data-page="moodmap"]',
      position: 'right',
      emoji:    '🗓️',
      title:    'Mood Map',
      desc:     "Tap here to see a day-by-day breakdown of your child's emotions. Each day tile shows a mood score — click it to see 5 individual emotion bars.",
      page:     'dashboard',
    },

    /* ── Deep Insights nav item ── */
    {
      id:       'insights-nav',
      target:   '.nav-item[data-page="insights"]',
      position: 'right',
      emoji:    '🧠',
      title:    'Deep Insights',
      desc:     "AI-generated story cards explain emotional patterns in plain language — morning peaks, stress triggers, and growth stories. No jargon, just clarity.",
      page:     'dashboard',
    },

    /* ── Manage Children nav ── */
    {
      id:       'children-nav',
      target:   '.nav-item[data-page="manage-children"]',
      position: 'right',
      emoji:    '👨‍👩‍👧‍👦',
      title:    'Manage Children',
      desc:     "View all child profiles here. Each card shows screen time, current mood, and a wellness score. Cards highlighted in red need your attention.",
      page:     'dashboard',
    },

    /* ── Add Child button ── */
    {
      id:       'add-child-nav',
      target:   '.nav-item[data-page="add-child"]',
      position: 'right',
      emoji:    '➕',
      title:    'Add a Child Profile',
      desc:     "Click here to register a new child. You'll pick an avatar, set interests, and configure alerts. It only takes 30 seconds!",
      page:     'dashboard',
    },

    /* ── Notifications nav ── */
    {
      id:       'notif-nav',
      target:   '.nav-item[data-page="notifications"]',
      position: 'right',
      emoji:    '🔔',
      title:    'Notifications & Alerts',
      desc:     "Critical emotional spikes show up here instantly. You can also configure quiet hours and choose between push alerts or email digests.",
      page:     'dashboard',
    },

    /* ── Report button ── */
    {
      id:       'reports-nav',
      target:   '.nav-item[data-page="reports"]',
      position: 'right',
      emoji:    '📄',
      title:    'PDF Reports',
      desc:     "Generate and download detailed weekly or monthly reports for any child. Great for sharing with teachers or pediatricians.",
      page:     'dashboard',
    },

    /* ── Theme toggle ── */
    {
      id:       'theme',
      target:   '#themeToggleBtn',
      position: 'bottom',
      emoji:    '🌓',
      title:    'Light & Dark Mode',
      desc:     "Switch between dark (default) and light mode anytime. Your preference is saved automatically.",
      page:     'dashboard',
    },

    /* ── Finish ── */
    {
      id:       'finish',
      target:   null,
      position: 'center',
      emoji:    '🚀',
      title:    "You're All Set!",
      desc:     "That's the tour! Remember, you can replay it anytime from Settings → Replay Tutorial. Now go explore — your child's emotional journey awaits.",
      page:     'dashboard',
      isLast:   true,
    },
  ];


  /* ─────────────────────────────────────────────────────────────────
     2.  UTILITY HELPERS
  ───────────────────────────────────────────────────────────────── */

  /** Get bounding rect relative to the viewport (handles scroll) */
  function getRect(el) {
    return el.getBoundingClientRect();
  }

  /** Clamp a value between min and max */
  function clamp(val, min, max) {
    return Math.max(min, Math.min(max, val));
  }

  /** Navigate to a page using the existing showPage() function */
  function navigateTo(pageId) {
    if (typeof window.showPage === 'function') {
      window.showPage(pageId);
    }
  }

  /** Smooth scroll an element into view */
  function scrollToEl(el) {
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
  }


  /* ─────────────────────────────────────────────────────────────────
     3.  DOM BUILDER  — injects all guide HTML once on first call
  ───────────────────────────────────────────────────────────────── */
  let tourContainer = null;   // outer wrapper div
  let spotlightEl   = null;   // SVG overlay with cutout
  let tooltipEl     = null;   // the floating popover
  let progressBar   = null;   // thin top progress line

  function buildDOM() {
    if (tourContainer) return;   // already built

    /* ── Outer container (holds everything, pointer-events managed per child) ── */
    tourContainer = document.createElement('div');
    tourContainer.id = 'sh-tour';
    tourContainer.setAttribute('role', 'dialog');
    tourContainer.setAttribute('aria-modal', 'true');
    tourContainer.setAttribute('aria-label', 'Product tour');
    tourContainer.style.cssText = 'position:fixed;inset:0;z-index:9000;pointer-events:none;';

    /* ── Progress bar (top edge of viewport) ── */
    progressBar = document.createElement('div');
    progressBar.id = 'sh-tour-progress';
    tourContainer.appendChild(progressBar);

    /* ── Spotlight SVG ── */
    spotlightEl = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    spotlightEl.id = 'sh-tour-spotlight';
    spotlightEl.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    spotlightEl.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;pointer-events:all;';
    spotlightEl.innerHTML = `
      <defs>
        <mask id="sh-tour-mask">
          <rect width="100%" height="100%" fill="white"/>
          <rect id="sh-tour-hole" rx="16" fill="black"/>
        </mask>
      </defs>
      <rect
        width="100%" height="100%"
        fill="rgba(0,0,0,0.72)"
        mask="url(#sh-tour-mask)"
        style="backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);"
      />
    `;
    tourContainer.appendChild(spotlightEl);

    /* ── Tooltip popover ── */
    tooltipEl = document.createElement('div');
    tooltipEl.id = 'sh-tour-tooltip';
    tooltipEl.setAttribute('role', 'tooltip');
    tooltipEl.innerHTML = `
      <div id="sh-tour-tt-inner">
        <div id="sh-tour-tt-header">
          <span id="sh-tour-tt-emoji" aria-hidden="true"></span>
          <span id="sh-tour-tt-counter"></span>
        </div>
        <h3 id="sh-tour-tt-title"></h3>
        <p  id="sh-tour-tt-desc"></p>
        <div id="sh-tour-tt-dots"></div>
        <div id="sh-tour-tt-actions">
          <button id="sh-tour-skip"  class="sh-btn sh-btn-ghost">Skip tour</button>
          <div id="sh-tour-nav-btns">
            <button id="sh-tour-prev" class="sh-btn sh-btn-ghost" aria-label="Previous step">← Prev</button>
            <button id="sh-tour-next" class="sh-btn sh-btn-primary" aria-label="Next step">Next →</button>
          </div>
        </div>
        <div id="sh-tour-tt-arrow"></div>
      </div>
    `;
    tourContainer.appendChild(tooltipEl);

    document.body.appendChild(tourContainer);

    /* Wire button events */
    document.getElementById('sh-tour-next').addEventListener('click', () => TourEngine.next());
    document.getElementById('sh-tour-prev').addEventListener('click', () => TourEngine.prev());
    document.getElementById('sh-tour-skip').addEventListener('click', () => TourEngine.skip());

    /* Click outside spotlight → advance */
    spotlightEl.addEventListener('click', (e) => {
      // Only advance if clicking the dark backdrop, not inside the hole/tooltip
      TourEngine.next();
    });

    /* Keyboard navigation */
    document.addEventListener('keydown', onKeyDown);

    /* Reposition on scroll / resize */
    window.addEventListener('resize', debounce(TourEngine.refresh.bind(TourEngine), 100));
    window.addEventListener('scroll', debounce(TourEngine.refresh.bind(TourEngine), 60), true);
  }

  function onKeyDown(e) {
    if (!TourEngine.active) return;
    if (e.key === 'ArrowRight' || e.key === 'Enter') TourEngine.next();
    if (e.key === 'ArrowLeft')                        TourEngine.prev();
    if (e.key === 'Escape')                           TourEngine.skip();
  }

  function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  }


  /* ─────────────────────────────────────────────────────────────────
     4.  SPOTLIGHT ENGINE — moves/resizes the SVG cut-out
  ───────────────────────────────────────────────────────────────── */
  const Spotlight = {
    PAD: 10,   // padding around highlighted element

    /** Move the hole to cover the target element */
    point(el) {
      const hole = document.getElementById('sh-tour-hole');
      if (!el) {
        /* No target → hide hole (full overlay for centred modal) */
        hole.setAttribute('x', -9999);
        hole.setAttribute('y', -9999);
        hole.setAttribute('width', 0);
        hole.setAttribute('height', 0);
        return;
      }
      const r   = getRect(el);
      const pad = this.PAD;
      hole.setAttribute('x',      r.left   - pad);
      hole.setAttribute('y',      r.top    - pad);
      hole.setAttribute('width',  r.width  + pad * 2);
      hole.setAttribute('height', r.height + pad * 2);
    },
  };


  /* ─────────────────────────────────────────────────────────────────
     5.  TOOLTIP POSITIONER — calculates where the popover should sit
  ───────────────────────────────────────────────────────────────── */
  const TooltipPos = {
    MARGIN: 18,   // gap between tooltip and target

    position(targetEl, preferredSide) {
      const tt       = tooltipEl;
      const arrow    = document.getElementById('sh-tour-tt-arrow');
      const vw       = window.innerWidth;
      const vh       = window.innerHeight;
      const ttRect   = tt.getBoundingClientRect();
      const ttW      = ttRect.width  || 340;
      const ttH      = ttRect.height || 260;

      /* Centred (no target) */
      if (!targetEl || preferredSide === 'center') {
        tt.style.left      = '50%';
        tt.style.top       = '50%';
        tt.style.transform = 'translate(-50%,-50%)';
        arrow.style.display = 'none';
        return;
      }

      arrow.style.display = 'block';
      const r    = getRect(targetEl);
      const pad  = this.MARGIN;
      let   left = 0, top = 0, side = preferredSide;

      /* Auto-pick side if there isn't enough room */
      if (side === 'right'  && r.right  + pad + ttW > vw) side = 'left';
      if (side === 'left'   && r.left   - pad - ttW < 0)  side = 'right';
      if (side === 'bottom' && r.bottom + pad + ttH > vh) side = 'top';
      if (side === 'top'    && r.top    - pad - ttH < 0)  side = 'bottom';

      switch (side) {
        case 'right':
          left = r.right + pad;
          top  = clamp(r.top + r.height / 2 - ttH / 2, 8, vh - ttH - 8);
          break;
        case 'left':
          left = r.left - pad - ttW;
          top  = clamp(r.top + r.height / 2 - ttH / 2, 8, vh - ttH - 8);
          break;
        case 'bottom':
          left = clamp(r.left + r.width / 2 - ttW / 2, 8, vw - ttW - 8);
          top  = r.bottom + pad;
          break;
        case 'top':
        default:
          left = clamp(r.left + r.width / 2 - ttW / 2, 8, vw - ttW - 8);
          top  = r.top - pad - ttH;
          break;
      }

      tt.style.left      = left + 'px';
      tt.style.top       = top  + 'px';
      tt.style.transform = 'none';

      /* Position arrow */
      const arrowSize = 10;
      arrow.className = 'sh-arrow-' + side;
      switch (side) {
        case 'right':
          arrow.style.cssText = `left:-${arrowSize}px;top:50%;transform:translateY(-50%);border-width:${arrowSize}px ${arrowSize}px ${arrowSize}px 0;border-color:transparent var(--bg2,#13101E) transparent transparent;`;
          break;
        case 'left':
          arrow.style.cssText = `right:-${arrowSize}px;top:50%;transform:translateY(-50%);border-width:${arrowSize}px 0 ${arrowSize}px ${arrowSize}px;border-color:transparent transparent transparent var(--bg2,#13101E);`;
          break;
        case 'bottom':
          arrow.style.cssText = `top:-${arrowSize}px;left:50%;transform:translateX(-50%);border-width:0 ${arrowSize}px ${arrowSize}px;border-color:transparent transparent var(--bg2,#13101E);`;
          break;
        case 'top':
          arrow.style.cssText = `bottom:-${arrowSize}px;left:50%;transform:translateX(-50%);border-width:${arrowSize}px ${arrowSize}px 0;border-color:var(--bg2,#13101E) transparent transparent;`;
          break;
      }
    },
  };


  /* ─────────────────────────────────────────────────────────────────
     6.  TOUR UI  — updates text, dots, progress bar, buttons
  ───────────────────────────────────────────────────────────────── */
  const TourUI = {
    render(stepIdx) {
      const step  = TOUR_STEPS[stepIdx];
      const total = TOUR_STEPS.length;

      /* Progress bar */
      progressBar.style.width = ((stepIdx + 1) / total * 100) + '%';

      /* Text content */
      document.getElementById('sh-tour-tt-emoji').textContent   = step.emoji;
      document.getElementById('sh-tour-tt-title').textContent   = step.title;
      document.getElementById('sh-tour-tt-desc').textContent    = step.desc;
      document.getElementById('sh-tour-tt-counter').textContent = `${stepIdx + 1} / ${total}`;

      /* Dot indicators */
      const dotsEl = document.getElementById('sh-tour-tt-dots');
      dotsEl.innerHTML = '';
      TOUR_STEPS.forEach((_, i) => {
        const d = document.createElement('span');
        d.className = 'sh-dot' + (i === stepIdx ? ' sh-dot-active' : '');
        d.setAttribute('aria-hidden', 'true');
        dotsEl.appendChild(d);
      });

      /* Buttons */
      const prevBtn = document.getElementById('sh-tour-prev');
      const nextBtn = document.getElementById('sh-tour-next');
      prevBtn.style.display = stepIdx === 0 ? 'none' : 'inline-flex';
      nextBtn.textContent   = step.isLast ? '🚀 Get Started' : 'Next →';
      nextBtn.className     = step.isLast
        ? 'sh-btn sh-btn-finish'
        : 'sh-btn sh-btn-primary';
    },
  };


  /* ─────────────────────────────────────────────────────────────────
     7.  TOUR ENGINE  — the state machine
  ───────────────────────────────────────────────────────────────── */
  const TourEngine = {
    active:      false,
    currentStep: 0,
    _highlightEl: null,

    /* ── Start the tour (called on first visit or replay) ── */
    start(fromStep = 0) {
      buildDOM();
      this.active      = true;
      this.currentStep = fromStep;
      tourContainer.style.display = 'block';
      tourContainer.style.opacity = '0';

      /* Fade in */
      requestAnimationFrame(() => {
        tourContainer.style.transition = 'opacity 0.35s ease';
        tourContainer.style.opacity    = '1';
      });

      this._renderStep(this.currentStep);
    },

    /* ── Advance to next step ── */
    next() {
      if (!this.active) return;
      if (this.currentStep < TOUR_STEPS.length - 1) {
        this.currentStep++;
        this._renderStep(this.currentStep);
      } else {
        this.finish();
      }
    },

    /* ── Go back one step ── */
    prev() {
      if (!this.active || this.currentStep === 0) return;
      this.currentStep--;
      this._renderStep(this.currentStep);
    },

    /* ── Skip: mark done and close ── */
    skip() {
      localStorage.setItem('sh_tour_done', '1');
      this._close();
      if (typeof window.showToast === 'function') {
        window.showToast('Tour skipped. Replay anytime from Settings.', 'info');
      }
    },

    /* ── Finish: mark done and close ── */
    finish() {
      localStorage.setItem('sh_tour_done', '1');
      this._close();
      if (typeof window.showToast === 'function') {
        window.showToast('🎉 Tour complete! You\'re all set.', 'success');
      }
    },

    /* ── Replay: clear the flag and restart ── */
    replay() {
      localStorage.removeItem('sh_tour_done');
      this.start(0);
    },

    /* ── Check first visit ── */
    initIfFirstVisit() {
      if (!localStorage.getItem('sh_tour_done')) {
        setTimeout(() => this.start(0), 1400);
      }
    },

    /* ── Refresh positions (resize/scroll) ── */
    refresh() {
      if (!this.active) return;
      const step = TOUR_STEPS[this.currentStep];
      const el   = step.target ? document.querySelector(step.target) : null;
      Spotlight.point(el);
      TooltipPos.position(el, step.position);
    },

    /* ── Destroy and clean up ── */
    destroy() {
      this._close();
      if (tourContainer) {
        tourContainer.remove();
        tourContainer = null;
        spotlightEl   = null;
        tooltipEl     = null;
        progressBar   = null;
      }
      document.removeEventListener('keydown', onKeyDown);
    },

    /* ── Internal: render a specific step ── */
    _renderStep(idx) {
      const step = TOUR_STEPS[idx];

      /* 1. Navigate to the right page first if needed */
      const needsNav = step.page &&
        typeof window.showPage === 'function';
      if (needsNav) {
        window.showPage(step.page);
      }

      /* 2. Wait a tick for the DOM to settle after navigation */
      setTimeout(() => {
        /* 3. Find target element */
        const el = step.target ? document.querySelector(step.target) : null;
        this._highlightEl = el;

        /* 4. Scroll target into view */
        if (el) scrollToEl(el);

        /* 5. Wait for scroll to settle before positioning */
        setTimeout(() => {
          /* 6. Update spotlight */
          Spotlight.point(el);

          /* 7. Show tooltip with slide-in animation */
          tooltipEl.style.opacity   = '0';
          tooltipEl.style.transform = 'translateY(8px) scale(0.97)';
          tooltipEl.style.display   = 'block';

          /* 8. Update content */
          TourUI.render(idx);

          /* 9. Position tooltip */
          TooltipPos.position(el, step.position);

          /* 10. Animate in */
          requestAnimationFrame(() => {
            tooltipEl.style.transition = 'opacity 0.28s ease, transform 0.28s cubic-bezier(0.34,1.56,0.64,1)';
            tooltipEl.style.opacity    = '1';
            tooltipEl.style.transform  = 'translateY(0) scale(1)';
          });

          /* 11. Run optional step callback */
          if (typeof step.onEnter === 'function') step.onEnter(el);
        }, 120);
      }, needsNav ? 320 : 0);
    },

    /* ── Internal: close & fade out ── */
    _close() {
      this.active = false;
      if (!tourContainer) return;

      /* Remove highlight ring */
      if (this._highlightEl) {
        this._highlightEl.classList.remove('sh-tour-highlight');
        this._highlightEl = null;
      }

      tourContainer.style.transition = 'opacity 0.3s ease';
      tourContainer.style.opacity    = '0';
      setTimeout(() => {
        if (tourContainer) tourContainer.style.display = 'none';
      }, 320);
    },
  };


  /* ─────────────────────────────────────────────────────────────────
     8.  PUBLIC API  — exposed on window.Tour
  ───────────────────────────────────────────────────────────────── */
  window.Tour = {
    /**
     * Start the tour from the beginning (or from a specific step index).
     * Optionally force-start even if already completed.
     */
    start(fromStep = 0, force = false) {
      if (!force && localStorage.getItem('sh_tour_done')) return;
      TourEngine.start(fromStep);
    },

    /** Replay the full tour (clears the "done" flag) */
    replay() {
      TourEngine.replay();
    },

    /** Completely remove the tour from the DOM */
    destroy() {
      TourEngine.destroy();
    },

    /** Check first-visit and auto-start if needed */
    initIfFirstVisit() {
      TourEngine.initIfFirstVisit();
    },

    /** Total number of tour steps */
    get totalSteps() {
      return TOUR_STEPS.length;
    },
  };

}(window));