
class SearchIntegration {
  constructor(options = {}) {
    this.apiBase            = options.apiBase            || 'search-api.php';
    this.searchInput        = document.getElementById(options.searchInputId        || 'searchInput');
    this.resultsContainer   = document.getElementById(options.resultsContainerId   || 'newsGrid');
    this.resultsInfoEl      = document.getElementById(options.resultsInfoId        || 'resultsContainer');
    this.newsHero           = document.getElementById('newsHero');
    this.filterRow          = document.getElementById('filterRow');
    this.catRecsChips       = document.getElementById('catRecsChips');
    this.clearBtn           = document.getElementById('searchClearBtn');
    this.indexationBadge    = document.getElementById('indexationBadge');
    this.indexationBadgeTxt = document.getElementById('indexationBadgeText');

    this.loading        = false;
    this.isSearchActive = false;   // true while a query is typed / results shown
    this.currentQuery   = '';
    this.currentResults = [];
    this.searchTimeout  = null;
    this.searchRetryTimeout = null;
    this._searchRequestId = 0;
    this._searchAbortController = null;
    this._lastSearchAt = 0;
    this.searchCooldownMs = options.searchCooldownMs || 2500;
    this.searchCooldownUntil = 0;

    // FIX: removed duplicate 'Psychology' key
    this.catColors = {
      'Psychology' : 'chip-cyan',
      'Health'     : 'chip-violet',
      'Education'  : 'chip-gold',
      'Research'   : 'chip-mint',
      'Development': 'chip-mint',
      'Nutrition'  : 'chip-gold',
      'Safety'     : 'chip-coral',
      'News'       : 'chip-cyan',
      'Technology' : 'chip-violet',
    };

    this._init();
  }

  /* ─── Setup ─────────────────────────────────────────── */
  _init() {
    if (!this.searchInput) {
      console.warn('SearchIntegration: #searchInput not found');
      return;
    }

    // Debounced input handler
    this.searchInput.addEventListener('input', (e) => {
      const q = e.target.value.trim();
      this._toggleClearBtn(q.length > 0);

      clearTimeout(this.searchTimeout);
      if (q.length >= 2) {
        const delay = q.length < 6 ? 350 : q.length < 12 ? 550 : 750;
        this.searchTimeout = setTimeout(() => this.search(q), delay);
      } else if (q.length === 0) {
        this.clearResults();
      }
    });

    // Enter key triggers immediately
    this.searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (this.loading || Date.now() < this.searchCooldownUntil) {
          return;
        }
        clearTimeout(this.searchTimeout);
        const q = e.target.value.trim();
        if (q) this.search(q);
      }
      if (e.key === 'Escape') this.clearResults();
    });

    // Clear button
    this.clearBtn?.addEventListener('click', () => this.clearResults());
  }

  /* ─── Hero / UI state ───────────────────────────────── */
  _enterSearchMode() {
    if (this.isSearchActive) return;
    this.isSearchActive = true;

    // Hide hero with fade-out
    if (this.newsHero) {
      this.newsHero.style.transition = 'opacity 0.25s, max-height 0.35s';
      this.newsHero.style.overflow   = 'hidden';
      this.newsHero.style.opacity    = '0';
      this.newsHero.style.maxHeight  = this.newsHero.scrollHeight + 'px';
      requestAnimationFrame(() => {
        this.newsHero.style.maxHeight = '0';
        this.newsHero.style.marginBottom = '0';
      });
    }

    // Grey out filter pills (search overrides them)
    this.filterRow?.querySelectorAll('.filter-pill').forEach(p => {
      p.classList.remove('active');
      p.style.opacity = '0.4';
    });

    // Show results info banner
    if (this.resultsInfoEl) this.resultsInfoEl.style.display = 'block';
  }

  _exitSearchMode() {
    if (!this.isSearchActive) return;
    this.isSearchActive = false;

    // Show hero again
    if (this.newsHero) {
      this.newsHero.style.maxHeight  = '500px';
      this.newsHero.style.opacity    = '1';
      this.newsHero.style.marginBottom = '';
    }

    // Restore filter pills
    this.filterRow?.querySelectorAll('.filter-pill').forEach(p => {
      p.style.opacity = '';
    });

    // Hide results banner
    if (this.resultsInfoEl) {
      this.resultsInfoEl.style.display = 'none';
      this.resultsInfoEl.innerHTML = '';
    }

    // Hide indexation badge
    if (this.indexationBadge) this.indexationBadge.style.display = 'none';
  }

  _toggleClearBtn(show) {
    if (this.clearBtn) this.clearBtn.style.display = show ? 'flex' : 'none';
  }

  /* ─── Search ─────────────────────────────────────────── */
  async search(query) {
    const normalizedQuery = (query || '').trim();
    if (!normalizedQuery) { this.clearResults(); return; }

    const now = Date.now();
    if (now < this.searchCooldownUntil) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => this.search(normalizedQuery), this.searchCooldownUntil - now);
      return;
    }

    if (now - this._lastSearchAt < 600 && this.currentQuery === normalizedQuery) {
      return;
    }

    // Avoid sending the exact same query again while a request is already in flight.
    if (this.loading && this.currentQuery === normalizedQuery) {
      return;
    }

    clearTimeout(this.searchRetryTimeout);
    this.searchRetryTimeout = null;

    if (this._searchAbortController) {
      this._searchAbortController.abort();
    }

    const requestId = ++this._searchRequestId;
    const controller = new AbortController();
    this._searchAbortController = controller;

    this.loading      = true;
    this.currentQuery = normalizedQuery;
    this._lastSearchAt = now;
    this.searchCooldownUntil = now + this.searchCooldownMs;

    this._enterSearchMode();
    this._showLoading();

    try {
      const form = new FormData();
      form.append('query', normalizedQuery);

      const res = await fetch(`${this.apiBase}?action=search`, {
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body   : form,
        signal : controller.signal,
      });

      if (requestId !== this._searchRequestId) {
        return;
      }

      if (!res.ok) {
        if (res.status === 429) {
          throw new Error('RATE_LIMIT');
        }
        let msg = `HTTP ${res.status}`;
        try { const b = await res.json(); if (b.error) msg = b.error; } catch(_) {}
        throw new Error(msg);
      }

      const data = await res.json();

      if (data.error && !data.results?.length) {
        this._showError(data.error);
        this._showNoResults();
        return;
      }

      if (data.success && data.results?.length) {
        this.currentResults = data.results;
        this._displayResults(data);
        this._updateIndexationBadge(data.indexed_count, data.total_matches);
        this._injectDynamicCategories(data.results);
      } else {
        this._showNoResults();
      }

    } catch (err) {
      if (err?.name === 'AbortError' || requestId !== this._searchRequestId) {
        return;
      }

      if (err?.message === 'RATE_LIMIT' || String(err?.message || '').includes('Too many requests')) {
        this.searchRetryTimeout = setTimeout(() => {
          if (this.currentQuery === normalizedQuery && requestId === this._searchRequestId) {
            this.search(normalizedQuery);
          }
        }, 2200);
        return;
      }

      console.error('Search error:', err);
      this._showError(`Search failed: ${err.message}`);
    } finally {
      if (requestId === this._searchRequestId) {
        this.loading = false;
        if (this._searchAbortController === controller) {
          this._searchAbortController = null;
        }
      }
    }
  }

  /* ─── Results display ───────────────────────────────── */
  _displayResults(data) {
    const total   = data.total_matches || data.results.length;
    const indexed = data.indexed_count || 0;

    // Info banner
    if (this.resultsInfoEl) {
      this.resultsInfoEl.innerHTML = `
        <div class="search-results-banner">
          <div class="srb-left">
            <span class="material-symbols-outlined srb-icon">manage_search</span>
            <span>Found <strong style="color:var(--cyan)">${total}</strong> results for
              "<strong>${this._esc(data.query || this.currentQuery)}</strong>"</span>
          </div>
          ${indexed ? `<span class="srb-indexed-pill">
            <span class="material-symbols-outlined" style="font-size:12px">database</span>
            ${indexed} newly indexed
          </span>` : ''}
        </div>`;
    }

    // Cards
    if (!this.resultsContainer) return;
    this.resultsContainer.innerHTML = data.results.map((art, i) => {
      const cat      = art.category || art.cat || 'News';
      const catCls   = this.catColors[cat] || 'chip-cyan';
      const img      = art.image || art.img || '';
      const date     = art.publish_date || art.date || '';
      const title    = this._esc(art.title || '');
      const excerpt  = this._esc((art.content || art.description || '').substring(0, 110));
      const words    = (art.content || '').split(/\s+/).length;
      const readTime = Math.max(1, Math.ceil(words / 200)) + ' min';
      const srcName  = art.source ? `<span class="srb-source">${this._esc(art.source)}</span>` : '';

      return `
        <div class="news-card search-result-card" onclick="window.searchIntegration.openArticle(${i})">
          ${img ? `<img class="news-card-img" src="${img}" alt="${title}" width="260" height="160" loading="lazy"
            onerror="this.style.display='none'"/>` :
            `<div class="news-card-img news-card-img-placeholder"><span class="material-symbols-outlined">article</span></div>`}
          <div class="news-card-body">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <span class="chip ${catCls}">${this._esc(cat)}</span>
              ${srcName}
            </div>
            <div class="news-card-title">${title}</div>
            <div class="news-card-desc">${excerpt}${excerpt.length >= 109 ? '…' : ''}</div>
            <div class="news-card-meta">
              <span>${date}</span>
              <span style="color:var(--cyan)">${readTime} read</span>
            </div>
          </div>
        </div>`;
    }).join('');
  }

  /* ─── Indexation badge ──────────────────────────────── */
  _updateIndexationBadge(indexed, total) {
    if (!this.indexationBadge) return;
    this.indexationBadge.style.display = 'flex';
    if (this.indexationBadgeTxt) {
      this.indexationBadgeTxt.textContent =
        indexed > 0 ? `${indexed} newly indexed · ${total} matched` : `${total} matched`;
    }
  }

  /* ─── Dynamic category injection ───────────────────── */
  _injectDynamicCategories(results) {
    if (!this.catRecsChips) return;

    // Collect unique categories from DB results
    const existingCats = new Set(
      [...this.catRecsChips.querySelectorAll('.cat-rec-chip')].map(c => c.dataset.cat)
    );
    const catEmojis = {
      Development:'🌱', Nutrition:'🥦', Safety:'🛡️', Mindfulness:'🧘',
      Parenting:'👨‍👧', Behavior:'💡', Sleep:'😴', Language:'💬',
    };

    results.forEach(art => {
      const cat = art.category || art.cat;
      if (!cat || existingCats.has(cat)) return;
      existingCats.add(cat);

      const chip = document.createElement('button');
      chip.className    = 'cat-rec-chip cat-rec-chip-dynamic';
      chip.dataset.cat  = cat;
      chip.innerHTML    = `<span>${catEmojis[cat] || '📌'}</span> ${this._esc(cat)}`;
      chip.addEventListener('click', () => {
        if (window.searchIntegration) window.searchIntegration.clearResults();
        if (window.renderNews) window.renderNews(cat);
      });
      this.catRecsChips.appendChild(chip);
    });
  }

  /* ─── No results ────────────────────────────────────── */
  _showNoResults() {
    if (this.resultsInfoEl) {
      this.resultsInfoEl.innerHTML = `
        <div class="search-results-banner" style="border-color:rgba(255,107,107,0.3)">
          <span style="color:var(--coral)">No results for "<strong>${this._esc(this.currentQuery)}</strong>" — try different keywords.</span>
        </div>`;
    }
    if (this.resultsContainer) {
      this.resultsContainer.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--muted)">
          <div style="font-size:48px;margin-bottom:16px">🔍</div>
          <p style="font-size:14px">No articles found matching your search.</p>
          <p style="font-size:12px;margin-top:8px">Try different keywords or browse a category above.</p>
        </div>`;
    }
  }

  /* ─── Error ─────────────────────────────────────────── */
  _showError(message) {
    if (this.resultsInfoEl) {
      this.resultsInfoEl.innerHTML = `
        <div class="search-results-banner" style="border-color:rgba(255,107,107,0.3);color:var(--coral)">
          <span class="material-symbols-outlined" style="font-size:16px;flex-shrink:0">error</span>
          <span>${this._esc(message)}</span>
        </div>`;
    }
    console.error('[SearchIntegration]', message);
  }

  /* ─── Loading skeleton ───────────────────────────────── */
  _showLoading() {
    if (!this.resultsContainer) return;
    this.resultsContainer.innerHTML = Array(4).fill(0).map(() => `
      <div class="news-card skeleton-card">
        <div class="skeleton skeleton-img"></div>
        <div class="news-card-body" style="gap:10px;display:flex;flex-direction:column">
          <div class="skeleton" style="height:18px;width:60%;border-radius:6px"></div>
          <div class="skeleton" style="height:14px;width:90%;border-radius:6px"></div>
          <div class="skeleton" style="height:14px;width:75%;border-radius:6px"></div>
          <div class="skeleton" style="height:11px;width:50%;border-radius:6px"></div>
        </div>
      </div>`).join('');

    if (this.resultsInfoEl) {
      this.resultsInfoEl.innerHTML = `
        <div class="search-results-banner">
          <div class="search-spinner"></div>
          <span style="color:var(--muted)">Searching through indexed articles…</span>
        </div>`;
    }
  }

  /* ─── Clear / restore ───────────────────────────────── */
  clearResults(targetCat) {
    this.currentQuery   = '';
    this.currentResults = [];
    this._toggleClearBtn(false);

    if (this.searchInput) this.searchInput.value = '';

    this._exitSearchMode();

    // Restore local news grid
    if (window.renderNews) {
      window.renderNews(targetCat || window.activeNewsFilter || 'All');
    }

    // Sync cat-rec chip active state
    const cat = targetCat || 'All';
    document.querySelectorAll('.cat-rec-chip').forEach(p =>
      p.classList.toggle('active', p.dataset.cat === cat));
    document.querySelectorAll('.filter-pill').forEach(p =>
      p.classList.toggle('active', p.dataset.cat === cat));
  }

  /* ─── Article modal ─────────────────────────────────── */
  openArticle(index) {
    const art = this.currentResults[index];
    if (!art) return;

    const cat      = art.category || art.cat || 'News';
    const catCls   = this.catColors[cat] || 'chip-cyan';
    const title    = this._esc(art.title || '');
    const img      = art.image || art.img || '';
    const date     = art.publish_date || art.date || '';
    const words    = (art.content || '').split(/\s+/).length;
    const readTime = Math.max(1, Math.ceil(words / 200)) + ' min';
    const body     = this._esc(art.content || art.description || '');
    const url      = art.url || '#';
    const source   = art.source ? `· ${this._esc(art.source)}` : '';

    // Reuse the existing articleModal already in the DOM
    const modal = document.getElementById('articleModal');
    document.getElementById('articleModalTitle').textContent = art.title || 'Article';
    document.getElementById('articleModalBody').innerHTML = `
      ${img ? `<img src="${img}" alt="${title}" style="width:100%;height:200px;object-fit:cover;border-radius:14px;margin-bottom:16px;" loading="lazy"
        onerror="this.style.display='none'"/>` : ''}
      <span class="chip ${catCls}" style="margin-bottom:12px">${this._esc(cat)}</span>
      <h2 style="font-family:var(--fh);font-size:20px;font-weight:800;margin:10px 0 8px;line-height:1.35">${title}</h2>
      <div style="font-size:12px;color:var(--muted);margin-bottom:16px">
        📅 ${date} ${source} · ⏱ ${readTime} read
      </div>
      <p style="font-size:14px;color:var(--muted);line-height:1.75">${body}</p>
      ${url && url !== '#' ? `
        <a href="${url}" target="_blank" rel="noopener" class="btn btn-primary" style="margin-top:18px;display:inline-flex;align-items:center;gap:6px">
          <span class="material-symbols-outlined" style="font-size:16px">open_in_new</span> Read Full Article
        </a>` : ''}`;
    if (modal) modal.classList.add('active');
  }

  /* ─── Utility ───────────────────────────────────────── */
  _esc(text) {
    return String(text || '').replace(/[&<>"']/g, m =>
      ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m]));
  }
}

/* ── Inject required CSS for new elements ─────────────── */
(function injectStyles() {
  if (document.getElementById('si-styles')) return;
  const s = document.createElement('style');
  s.id = 'si-styles';
  s.textContent = `
    /* ── Search bar ─────────────────────────────── */
    .news-search-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    .news-search-icon {
      position: absolute;
      left: 14px;
      color: var(--muted);
      font-size: 20px;
      pointer-events: none;
      z-index: 1;
    }
    .news-search-input {
      width: 100%;
      padding: 12px 44px 12px 44px;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: var(--glass);
      color: var(--text);
      font-size: 14px;
      font-family: var(--fb);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      backdrop-filter: blur(10px);
    }
    .news-search-input:focus {
      border-color: var(--cyan);
      box-shadow: 0 0 0 3px rgba(105,218,255,0.12);
    }
    .news-search-input::placeholder { color: var(--muted); }
    .news-search-clear {
      position: absolute;
      right: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: none;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      transition: background .15s, color .15s;
    }
    .news-search-clear:hover { background: var(--bg3); color: var(--text); }
    .news-search-clear .material-symbols-outlined { font-size: 18px; }

    /* ── Category recommendations ─────────────── */
    .cat-recs { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .cat-recs-label { font-size: 11px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
    .cat-recs-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .cat-rec-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 14px;
      border-radius: 20px;
      border: 1px solid var(--border);
      background: var(--glass);
      color: var(--muted);
      font-size: 12px;
      font-family: var(--fb);
      font-weight: 500;
      cursor: pointer;
      transition: all .2s;
      white-space: nowrap;
    }
    .cat-rec-chip:hover { border-color: var(--cyan); color: var(--cyan); background: rgba(105,218,255,0.06); }
    .cat-rec-chip.active { border-color: var(--cyan); color: var(--cyan); background: rgba(105,218,255,0.1); font-weight: 700; }
    .cat-rec-chip-dynamic { border-style: dashed; }

    /* ── Results info banner ──────────────────── */
    .search-results-banner {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      margin-bottom: 14px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: var(--glass);
      font-size: 13px;
      color: var(--text);
      flex-wrap: wrap;
    }
    .srb-left { display: flex; align-items: center; gap: 8px; flex: 1; }
    .srb-icon { font-size: 18px; color: var(--cyan); flex-shrink: 0; }
    .srb-source {
      font-size: 10px;
      color: var(--muted);
      padding: 2px 7px;
      border-radius: 8px;
      border: 1px solid var(--border);
    }
    .srb-indexed-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      border-radius: 20px;
      background: rgba(105,218,255,0.1);
      border: 1px solid rgba(105,218,255,0.25);
      color: var(--cyan);
      font-size: 11px;
      font-weight: 600;
      margin-left: auto;
    }

    /* ── Skeleton loading ─────────────────────── */
    .skeleton-card { pointer-events: none; }
    .skeleton {
      background: linear-gradient(90deg, var(--bg2) 25%, var(--bg3) 50%, var(--bg2) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.4s infinite;
    }
    .skeleton-img {
      width: 100%;
      height: 160px;
      border-radius: 14px 14px 0 0;
    }
    @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

    /* ── Search spinner ───────────────────────── */
    .search-spinner {
      width: 16px; height: 16px;
      border: 2px solid var(--border);
      border-top-color: var(--cyan);
      border-radius: 50%;
      animation: spin .7s linear infinite;
      flex-shrink: 0;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Card placeholder (no image) ──────────── */
    .news-card-img-placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg3);
      color: var(--muted);
      border-radius: 14px 14px 0 0;
    }
    .news-card-img-placeholder .material-symbols-outlined { font-size: 40px; }

    /* ── Hero transition ──────────────────────── */
    #newsHero {
      transition: opacity .25s ease, max-height .35s ease, margin-bottom .35s ease;
      max-height: 500px;
      overflow: hidden;
    }
  `;
  document.head.appendChild(s);
})();

/* ── Auto-init ────────────────────────────────────────── */
function initSearchIntegration() {
  window.searchIntegration = new SearchIntegration({
    searchInputId      : 'searchInput',
    resultsContainerId : 'newsGrid',
    resultsInfoId      : 'resultsContainer',
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSearchIntegration);
} else {
  initSearchIntegration();
}











