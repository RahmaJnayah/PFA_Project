# 🔗 Dashboard Integration Guide

This guide explains how to integrate the UNICEF News Indexation System into your existing **Sentiment Hub** dashboard.

---

## Quick Integration (5 minutes)

### Option 1: Add Search Bar to Navigation (Recommended)

Edit your dashboard's header/navigation section:

```html
<!-- In your dashboard header/nav -->
<div class="search-section">
    <form id="quick-news-search" method="POST" 
          action="/unicef_indexation/index.php?action=search" 
          style="display: flex; gap: 10px; max-width: 500px;">
        <input type="text" 
               name="query" 
               placeholder="🔍 Search UNICEF news..." 
               required
               style="flex: 1; padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc;">
        <button type="submit" 
                style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Search
        </button>
    </form>
</div>
```

---

## Full Integration with AJAX (Recommended)

This allows seamless integration without page reloads.

### Step 1: Add Files to Dashboard

Copy these files to your dashboard project:

```
dashboard/
├── js/
│   └── news-search-widget.js      ← Copy from /unicef_indexation/js/
├── css/
│   └── news-search-widget.css     ← Copy from /unicef_indexation/css/
└── index.html
```

### Step 2: Update Your Dashboard HTML

```html
<!DOCTYPE html>
<html>
<head>
    <!-- Your existing styles -->
    <link rel="stylesheet" href="css/news-search-widget.css">
</head>
<body>
    <!-- Your dashboard content -->
    
    <!-- News Search Widget Section -->
    <section class="news-search-widget">
        <div class="news-search-box">
            <input type="text" 
                   id="newsQuery" 
                   class="news-search-input" 
                   placeholder="Search UNICEF news...">
            <button id="searchBtn" class="news-search-btn">🔍 Search</button>
            <button id="extractBtn" class="news-search-btn" style="background: #48bb78;">📥 Extract News</button>
        </div>
        
        <!-- Error/Success Messages -->
        <div id="searchMessages"></div>
        
        <!-- Statistics -->
        <div id="statsContainer"></div>
        
        <!-- Results -->
        <div id="resultsContainer"></div>
    </section>

    <!-- Your existing scripts -->
    <script src="js/news-search-widget.js"></script>
    <script>
        // Initialize the widget when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize widget
            NewsSearchWidget.init({
                apiBase: '/unicef_indexation/index.php',
                resultsContainer: document.getElementById('resultsContainer'),
                statsContainer: document.getElementById('statsContainer'),
                errorContainer: document.getElementById('searchMessages')
            });

            // Get initial stats
            window.newsSearchWidget.getStats();

            // Handle search button
            document.getElementById('searchBtn').addEventListener('click', function() {
                const query = document.getElementById('newsQuery').value;
                window.newsSearchWidget.search(query);
            });

            // Handle Enter key
            document.getElementById('newsQuery').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('searchBtn').click();
                }
            });

            // Handle extract button
            document.getElementById('extractBtn').addEventListener('click', function() {
                if (confirm('This will extract 20 new articles from UNICEF. Continue?')) {
                    window.newsSearchWidget.extract(20);
                }
            });
        });
    </script>
</body>
</html>
```

---

## Integration Options

### Option A: Modal/Popup Integration

Display search results in a modal:

```html
<!-- Modal HTML -->
<div id="newsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="background: white; margin: 50px auto; width: 90%; max-width: 600px; border-radius: 10px; padding: 20px; max-height: 80vh; overflow-y: auto;">
        <button onclick="document.getElementById('newsModal').style.display='none'" style="float: right; background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
        <div id="newsModalContent"></div>
    </div>
</div>

<script>
function openNewsSearch() {
    document.getElementById('newsModal').style.display = 'block';
    
    NewsSearchWidget.init({
        apiBase: '/unicef_indexation/index.php',
        resultsContainer: document.getElementById('newsModalContent'),
        errorContainer: document.getElementById('newsModalContent')
    });
}
</script>
```

### Option B: Sidebar Integration

```html
<aside class="news-sidebar">
    <h3>📰 Latest News</h3>
    <div id="newsSidebarContent"></div>
</aside>

<script>
NewsSearchWidget.init({
    apiBase: '/unicef_indexation/index.php',
    resultsContainer: document.getElementById('newsSidebarContent')
});

// Show trending search on load
window.newsSearchWidget.search('UNICEF children');
</script>
```

### Option C: Embedded Dashboard Tab

Add a news tab to your dashboard:

```html
<div class="dashboard-tabs">
    <button class="tab" onclick="showTab('sentiment')">Sentiment</button>
    <button class="tab" onclick="showTab('news')">📰 News</button>
</div>

<div id="sentiment-tab" class="tab-content" style="display: block;">
    <!-- Your sentiment content -->
</div>

<div id="news-tab" class="tab-content" style="display: none;">
    <div class="news-search-widget">
        <div class="news-search-box">
            <input type="text" id="newsTabQuery" class="news-search-input" placeholder="Search news...">
            <button class="news-search-btn" onclick="searchFromTab()">Search</button>
        </div>
        <div id="newsTabResults"></div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    // Show selected tab
    document.getElementById(tabName + '-tab').style.display = 'block';
    
    // Initialize news widget if it's news tab
    if (tabName === 'news') {
        NewsSearchWidget.init({
            apiBase: '/unicef_indexation/index.php',
            resultsContainer: document.getElementById('newsTabResults'),
            errorContainer: document.getElementById('newsTabResults')
        });
    }
}

function searchFromTab() {
    const query = document.getElementById('newsTabQuery').value;
    window.newsSearchWidget.search(query);
}
</script>
```

---

## CSS Customization

### Match Your Dashboard Theme

Override CSS variables to match your theme:

```html
<style>
    :root {
        --primary-color: #667eea;      /* Main accent color */
        --primary-hover: #5a67d8;      /* Hover state */
        --border-color: #e0e0e0;       /* Border color */
        --text-color: #1a202c;         /* Text color */
        --bg-color: #ffffff;           /* Background */
    }
    
    .news-search-btn {
        background: var(--primary-color);
    }
    
    .news-search-btn:hover {
        background: var(--primary-hover);
    }
    
    .news-search-input {
        border-color: var(--border-color);
        color: var(--text-color);
        background: var(--bg-color);
    }
    
    .result-item h4 {
        color: var(--primary-color);
    }
</style>
```

### Dark Mode Support

```css
@media (prefers-color-scheme: dark) {
    .result-item {
        background: #2d3748;
        color: #e2e8f0;
    }
    
    .result-item h4 {
        color: #90cdf4;
    }
    
    .news-search-input {
        background: #1a202c;
        color: #e2e8f0;
        border-color: #4a5568;
    }
}
```

---

## Advanced Integration

### Add to Existing Cards/Widgets

```html
<div class="card">
    <div class="card-header">
        <h3>UNICEF News Search</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <input type="text" id="cardSearchQuery" placeholder="Search news..." 
                   style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button onclick="searchCard()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Search
            </button>
        </div>
        <div id="cardSearchResults"></div>
    </div>
</div>

<script>
function searchCard() {
    const query = document.getElementById('cardSearchQuery').value;
    if (!window.cardNewsWidget) {
        window.cardNewsWidget = NewsSearchWidget.init({
            apiBase: '/unicef_indexation/index.php',
            resultsContainer: document.getElementById('cardSearchResults')
        });
    }
    window.cardNewsWidget.search(query);
}
</script>
```

### Connect to Your Dashboard Data

```javascript
// If you have user/context data in your dashboard
class DashboardNewsIntegration {
    constructor(userContext) {
        this.userContext = userContext;
        this.widget = NewsSearchWidget.init({
            apiBase: '/unicef_indexation/index.php',
            resultsContainer: document.getElementById('searchResults')
        });
    }
    
    // Search based on dashboard context
    searchByTopic(topic) {
        this.widget.search(topic);
    }
    
    // Get recommendations
    getRecommendations() {
        const topics = this.userContext.interests || ['children', 'health', 'education'];
        topics.forEach(topic => {
            this.widget.search(topic, (results) => {
                this.saveRecommendation(topic, results);
            });
        });
    }
    
    saveRecommendation(topic, results) {
        // Save to your dashboard database
        fetch('/api/recommendations', {
            method: 'POST',
            body: JSON.stringify({
                topic: topic,
                articles: results.results,
                timestamp: new Date()
            })
        });
    }
}

// Use it
const integration = new DashboardNewsIntegration(userContext);
integration.getRecommendations();
```

---

## API Endpoints Reference

### Search
```javascript
window.newsSearchWidget.search('vaccination', (result) => {
    console.log(`Found ${result.total_matches} articles`);
    console.log(`Indexed ${result.indexed_count} new articles`);
});
```

Response:
```json
{
  "success": true,
  "results": [...],
  "indexed_count": 5,
  "total_matches": 15,
  "query": "vaccination",
  "message": "Found 15 relevant documents"
}
```

### Extract News
```javascript
window.newsSearchWidget.extract(20, (result) => {
    console.log('Extraction complete:', result.output);
});
```

### Get Statistics
```javascript
window.newsSearchWidget.getStats((stats) => {
    console.log(`Total: ${stats.total}, Indexed: ${stats.indexed}, Pending: ${stats.non_indexed}`);
});
```

---

## Error Handling

```javascript
// Already built into widget, but you can also handle manually:

window.newsSearchWidget.search('query', (result) => {
    if (result.success) {
        console.log('Search success:', result);
    } else {
        console.error('Search failed:', result.error);
        // Show user-friendly error
        showNotification('Search failed. Please try again.', 'error');
    }
});
```

---

## Performance Tips

1. **Lazy Load Widget**: Only initialize when user accesses the news section
2. **Cache Results**: Store search results in localStorage
3. **Pagination**: Modify CSS to show only 5 results at a time
4. **Debounce Search**: Add delay before searching as user types

```javascript
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        window.newsSearchWidget.search(this.value);
    }, 300); // Wait 300ms after user stops typing
});
```

---

## Troubleshooting Integration

### Widget not loading?
- Check browser console for errors
- Verify `/unicef_indexation/index.php` is accessible
- Ensure MySQL database is running

### Results not showing?
- Verify news extraction has been run (`Extract News` button)
- Check browser console for network errors
- Try a different search query

### Styling conflicts?
- Use CSS classes as scoped as possible
- Check for !important rules conflicting
- Use CSS custom properties for theming

---

## Next Steps

1. ✅ Copy integration files to your project
2. ✅ Update your HTML with widget sections
3. ✅ Include CSS and JavaScript
4. ✅ Initialize widget with your containers
5. ✅ Test all functionality
6. ✅ Customize styling for your brand
7. ✅ Deploy to production

---

## Support

For issues or questions:
1. Check SETUP_GUIDE.md for troubleshooting
2. Review browser console for errors
3. Test components individually at `/unicef_indexation/index.php`
4. Verify all prerequisites are installed and running

---

**Last Updated**: April 2026
**Integration Version**: 1.0
**Status**: Ready for Production
