/**
 * UNICEF News Search Widget - JavaScript Integration
 * 
 * This file provides easy integration of the UNICEF news search
 * functionality into any dashboard or web application.
 * 
 * Usage:
 *   1. Include this file in your HTML
 *   2. Call NewsSearchWidget.init() when ready
 *   3. Use NewsSearchWidget.search(query) to search
 *   4. Use NewsSearchWidget.getStats() to get statistics
 */

class NewsSearchWidget {
    constructor(options = {}) {
        this.apiBase = options.apiBase || '/unicef_indexation/index.php';
        this.resultsContainer = options.resultsContainer || null;
        this.statsContainer = options.statsContainer || null;
        this.errorContainer = options.errorContainer || null;
        this.loading = false;
    }

    /**
     * Initialize the widget
     * @param {Object} options - Configuration options
     */
    static init(options = {}) {
        window.newsSearchWidget = new NewsSearchWidget(options);
        return window.newsSearchWidget;
    }

    /**
     * Perform a news search
     * @param {string} query - Search query
     * @param {Function} callback - Callback function (optional)
     */
    async search(query, callback = null) {
        if (!query || query.trim() === '') {
            this.showError('Please enter a search query');
            return;
        }

        this.loading = true;
        this.showLoading(true);

        try {
            const formData = new FormData();
            formData.append('query', query);

            const response = await fetch(
                `${this.apiBase}?action=search`,
                {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                }
            );

            const result = await response.json();

            if (result.success) {
                this.displayResults(result);
                if (callback) callback(result);
            } else {
                this.showError(result.error || 'Search failed');
            }
        } catch (error) {
            this.showError(`Error: ${error.message}`);
            console.error('Search error:', error);
        } finally {
            this.loading = false;
            this.showLoading(false);
        }
    }

    /**
     * Extract news from UNICEF
     * @param {number} numPosts - Number of posts to extract
     * @param {Function} callback - Callback function (optional)
     */
    async extract(numPosts = 20, callback = null) {
        this.loading = true;
        this.showLoading(true);

        try {
            const formData = new FormData();
            formData.append('num_posts', numPosts);

            const response = await fetch(
                `${this.apiBase}?action=extract`,
                {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                }
            );

            const result = await response.json();

            if (result.success) {
                this.showSuccess(`Extraction completed: ${result.output}`);
                if (callback) callback(result);
            } else {
                this.showError(result.error || 'Extraction failed');
            }
        } catch (error) {
            this.showError(`Error: ${error.message}`);
            console.error('Extraction error:', error);
        } finally {
            this.loading = false;
            this.showLoading(false);
        }
    }

    /**
     * Get statistics
     * @param {Function} callback - Callback function
     */
    async getStats(callback = null) {
        try {
            const response = await fetch(
                `${this.apiBase}?action=stats`,
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const result = await response.json();

            if (result.success) {
                if (!callback) {
                    this.displayStats(result.stats);
                } else {
                    callback(result.stats);
                }
            }
        } catch (error) {
            console.error('Stats error:', error);
        }
    }

    /**
     * Display search results
     * @param {Object} data - Result data
     */
    displayResults(data) {
        if (!this.resultsContainer) return;

        let html = `
            <div class="search-results">
                <div class="results-header">
                    <h3>Search Results for: "${data.query}"</h3>
                    <p class="results-meta">
                        Found <strong>${data.total_matches}</strong> articles
                        (Indexed: <strong>${data.indexed_count}</strong> new)
                    </p>
                </div>
        `;

        if (data.results && data.results.length > 0) {
            data.results.forEach(article => {
                const excerpt = article.content.substring(0, 200) + '...';
                const indexedBadge = article.is_indexed 
                    ? '<span class="badge badge-indexed">✓ Indexed</span>' 
                    : '';

                html += `
                    <div class="result-item">
                        <h4>${this.escapeHtml(article.title)}</h4>
                        <p>${this.escapeHtml(excerpt)}</p>
                        <div class="result-meta">
                            <span class="date">📅 ${article.publish_date || 'Unknown'}</span>
                            ${indexedBadge}
                        </div>
                        ${article.url ? `<a href="${article.url}" target="_blank" class="read-more">Read More →</a>` : ''}
                    </div>
                `;
            });
        } else {
            html += '<p class="no-results">No results found. Try different keywords.</p>';
        }

        html += '</div>';
        this.resultsContainer.innerHTML = html;
        this.clearError();
    }

    /**
     * Display statistics
     * @param {Object} stats - Statistics object
     */
    displayStats(stats) {
        if (!this.statsContainer) return;

        const html = `
            <div class="stats-display">
                <div class="stat-item">
                    <span class="stat-label">Total Articles</span>
                    <span class="stat-value">${stats.total}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Indexed</span>
                    <span class="stat-value">${stats.indexed}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value">${stats.non_indexed}</span>
                </div>
            </div>
        `;

        this.statsContainer.innerHTML = html;
    }

    /**
     * Show error message
     * @param {string} message - Error message
     */
    showError(message) {
        if (this.errorContainer) {
            this.errorContainer.innerHTML = `<div class="alert alert-error">${message}</div>`;
            this.errorContainer.style.display = 'block';
        } else {
            console.error(message);
        }
    }

    /**
     * Show success message
     * @param {string} message - Success message
     */
    showSuccess(message) {
        if (this.errorContainer) {
            this.errorContainer.innerHTML = `<div class="alert alert-success">${message}</div>`;
            this.errorContainer.style.display = 'block';
        } else {
            console.log(message);
        }
    }

    /**
     * Clear error messages
     */
    clearError() {
        if (this.errorContainer) {
            this.errorContainer.innerHTML = '';
            this.errorContainer.style.display = 'none';
        }
    }

    /**
     * Show/hide loading indicator
     * @param {boolean} show - Show or hide
     */
    showLoading(show) {
        // You can enhance this to show a loading spinner
        // For now, just update the UI state
        if (show) {
            document.body.style.cursor = 'wait';
        } else {
            document.body.style.cursor = 'default';
        }
    }

    /**
     * Escape HTML special characters
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NewsSearchWidget;
}
