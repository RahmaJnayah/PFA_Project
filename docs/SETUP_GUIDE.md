## 🎯 UNICEF News Indexation System - Complete Setup Guide

This document provides step-by-step instructions to set up, test, and integrate the complete UNICEF news search and indexation system.

---

## 📋 Table of Contents

1. [System Architecture](#architecture)
2. [Prerequisites](#prerequisites)
3. [Installation Steps](#installation)
4. [Database Setup](#database)
5. [Python Configuration](#python)
6. [Testing the System](#testing)
7. [Integration with Dashboard](#integration)
8. [Troubleshooting](#troubleshooting)
9. [API Reference](#api-reference)

---

## 🏗️ System Architecture {#architecture}

The system follows MVC (Model-View-Controller) with Python integration:

```
User Interface (HTML/CSS/JS)
        ↓
  PHP Controller
        ↓
  Python Scripts (Extraction & Indexation)
        ↓
   MySQL Database
```

### Components:

1. **Frontend**: HTML/CSS/JavaScript with search interface
2. **Backend**: PHP (MVC architecture)
   - **Model**: Database operations (`NewsModel.php`)
   - **View**: HTML templates for UI
   - **Controller**: Request handling (`SearchController.php`)
3. **Processing**: Python scripts
   - `extract_news.py`: Extracts and stores news
   - `indexation_engine.py`: Implements TF-IDF search algorithm
4. **Database**: MySQL with two main tables
   - `unicef_news`: Stores news articles
   - `indexation_log`: Tracks search queries

---

## ✅ Prerequisites {#prerequisites}

### Required Software:
- **XAMPP** (Apache, MySQL, PHP)
- **Python 3.8+** with `mysql-connector-python`
- **Windows**, macOS, or Linux

### Installation:

1. **XAMPP**: Download from https://www.apachefriends.org
   - Install with Apache and MySQL enabled

2. **Python Packages**:
   ```bash
   pip install mysql-connector-python requests beautifulsoup4
   ```

3. **Verify Python is in PATH**:
   ```bash
   python --version
   python -m mysql.connector --version
   ```

---

## 🚀 Installation Steps {#installation}

### Step 1: Copy Project to XAMPP

```bash
C:\xampp\htdocs\unicef_indexation\
```

Project structure should be:
```
unicef_indexation/
├── index.php                          ← Entry point
├── config/
│   └── config.php                     ← Database config
├── database/
│   └── schema.sql                     ← Database schema
├── php/
│   ├── controllers/
│   │   └── SearchController.php
│   ├── models/
│   │   └── NewsModel.php
│   └── views/
│       ├── search_form.php
│       ├── search_results.php
│       ├── extract_form.php
│       ├── extraction_result.php
│       ├── search_history.php
│       ├── stats.php
│       └── search_view.php
└── python/
    ├── extract_news.py
    └── indexation_engine.py
```

### Step 2: Update Configuration

Edit `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');              // XAMPP default (empty)
define('DB_NAME', 'unicef_news_db');

// Adjust paths if needed (use your XAMPP installation path)
define('PYTHON_EXTRACTOR', 'C:\\xampp\\htdocs\\unicef_indexation\\python\\extract_news.py');
define('PYTHON_INDEXER', 'C:\\xampp\\htdocs\\unicef_indexation\\python\\indexation_engine.py');
```

---

## 🗄️ Database Setup {#database}

### Step 1: Start MySQL

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**

### Step 2: Create Database

Open phpMyAdmin: http://localhost/phpmyadmin

**Option A: Manual SQL**
1. Click "SQL" tab
2. Copy and paste entire contents of `database/schema.sql`
3. Click Execute

**Option B: Import File**
1. Click "Import" tab
2. Select `database/schema.sql`
3. Click "Import"

### Step 3: Verify Tables

In phpMyAdmin, under `unicef_news_db`:
- Table `unicef_news` should exist with columns:
  - id, title, content, url, publish_date, is_indexed, indexed_date, created_at
  
- Table `indexation_log` should exist with columns:
  - id, query_text, matched_docs, indexed_count, search_date

---

## 🐍 Python Configuration {#python}

### Step 1: Test Python Connection

Create test file: `test_python_connection.py`

```python
import mysql.connector

try:
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='unicef_news_db'
    )
    print("✅ Database connection successful!")
    conn.close()
except Exception as e:
    print(f"❌ Connection failed: {e}")
```

Run:
```bash
cd C:\xampp\htdocs\unicef_indexation
python test_python_connection.py
```

### Step 2: Verify Imports

```bash
python -c "import mysql.connector; import requests; import bs4; print('✅ All packages installed')"
```

---

## 🧪 Testing the System {#testing}

### Test 1: Verify Web Interface

1. Start XAMPP (Apache & MySQL)
2. Go to: `http://localhost/unicef_indexation/`
3. You should see the search dashboard

### Test 2: Extract News

1. Click "📥 Extract News" button
2. Leave default (20 posts) or customize
3. Click "Start Extraction"
4. You should see success message with Python output

**Expected Output:**
```
✅ Saved 20 news items to database
📰 Extracted 20 news items
```

### Test 3: Search Articles

1. Go back to main search page
2. Type search query (e.g., "children vaccination", "climate change", "malnutrition")
3. Click "Search"
4. You should see results with:
   - Article titles and content snippets
   - "Indexed" badge for newly indexed docs
   - Statistics: "Found X relevant documents. Indexed Y new documents."

### Test 4: View Statistics

1. Click "📈 Statistics"
2. You should see:
   - Total News Articles
   - Indexed Documents
   - Pending Indexation

### Test 5: Search History

1. Click "📊 Search History"
2. You should see all previous searches logged with:
   - Query text
   - Number of documents indexed
   - Search timestamp

### Test 6: Check Database

In phpMyAdmin:

1. **news table**: Should have 20+ articles
   - is_indexed should be 0 or 1 depending on searches
   - indexed_date should have timestamps for indexed articles

2. **indexation_log**: Should have entries for each search
   - Shows query_text and indexed_count

---

## 🔗 Integration with Dashboard {#integration}

### For Your Existing "Sentiment Hub" Dashboard:

#### Option 1: Add Search Bar to Navigation

Edit your dashboard header/navigation file:

```html
<!-- Add to your navigation -->
<div class="search-widget">
    <form id="news-search-form" method="POST" action="/unicef_indexation/index.php?action=search">
        <input type="text" name="query" placeholder="Search UNICEF news..." required>
        <button type="submit">Search</button>
    </form>
</div>
```

#### Option 2: Iframe Integration

Embed search system in a dashboard page:

```html
<iframe 
    src="/unicef_indexation/index.php" 
    width="100%" 
    height="600px" 
    frameborder="0"
    style="border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
</iframe>
```

#### Option 3: AJAX Integration

Integrate search without page reload:

```javascript
// In your dashboard JavaScript
async function searchNews(query) {
    const formData = new FormData();
    formData.append('query', query);
    
    const response = await fetch('/unicef_indexation/index.php?action=search', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        // Display results in your modal/container
        displaySearchResults(result);
    } else {
        console.error('Search error:', result.error);
    }
}

function displaySearchResults(data) {
    const resultsHtml = data.results.map(article => `
        <div class="article-card">
            <h3>${article.title}</h3>
            <p>${article.content.substring(0, 200)}...</p>
            <small>Published: ${article.publish_date}</small>
            ${article.is_indexed ? '<span class="badge">Indexed</span>' : ''}
        </div>
    `).join('');
    
    // Update your dashboard element
    document.getElementById('search-results').innerHTML = resultsHtml;
}
```

---

## 🚨 Troubleshooting {#troubleshooting}

### Issue 1: "Database connection failed"

**Solution**:
1. Ensure MySQL is running in XAMPP
2. Check credentials in `config/config.php`
3. Verify database `unicef_news_db` exists
4. Run phpMyAdmin to test connection manually

### Issue 2: "Python script not found" or "Python command failed"

**Solution**:
1. Verify Python is installed: `python --version`
2. Add Python to Windows PATH:
   - Control Panel → System → Environment Variables
   - Add Python installation directory to PATH
3. Verify script paths in `config/config.php`
4. Test Python directly:
   ```bash
   python C:\xampp\htdocs\unicef_indexation\python\extract_news.py
   ```

### Issue 3: "No search results" or empty results

**Possible causes**:
1. No news articles in database (run Extract News first)
2. Query terms too specific or not in database
3. Python script error (check Apache error logs)

**Debug**:
1. Open XAMPP logs: `C:\xampp\apache\logs\error.log`
2. Check phpMyAdmin for news table content
3. Try sample search terms: "children", "vaccine", "education"

### Issue 4: "No module named mysql.connector"

**Solution**:
```bash
pip install mysql-connector-python
```

If still failing, explicitly specify Python version:
```bash
python3.9 -m pip install mysql-connector-python
```

### Issue 5: Special characters or UTF-8 encoding issues

**Solution**:
Ensure all PHP files have UTF-8 encoding:
- In text editor: Set encoding to UTF-8 (no BOM)
- In PHP: Already set in `NewsModel.php`: `$this->conn->set_charset("utf8");`

### Issue 6: XAMPP paths wrong for Python

On different systems, adjust in `config/config.php`:

**Windows**:
```php
define('PYTHON_EXTRACTOR', 'C:\\xampp\\htdocs\\unicef_indexation\\python\\extract_news.py');
```

**macOS**:
```php
define('PYTHON_EXTRACTOR', '/Applications/XAMPP/xamppfiles/htdocs/unicef_indexation/python/extract_news.py');
```

**Linux**:
```php
define('PYTHON_EXTRACTOR', '/opt/lampp/htdocs/unicef_indexation/python/extract_news.py');
```

---

## 📡 API Reference {#api-reference}

### Regular Form Requests

#### 1. Search News
```
POST /unicef_indexation/index.php?action=search
Parameters:
  - query (string): Search query

Response:
  - Rendered HTML with search results
  - Shows matched articles
  - Displays indexation count
```

#### 2. Extract News
```
POST /unicef_indexation/index.php?action=extract
Parameters:
  - num_posts (int, optional): Number of articles (default: 20)

Response:
  - Rendered HTML with extraction status
  - Python output messages
```

#### 3. View Statistics
```
GET /unicef_indexation/index.php?action=stats
Response:
  - Rendered HTML with stats dashboard
```

#### 4. View Search History
```
GET /unicef_indexation/index.php?action=history
Response:
  - Rendered HTML with search history table
```

### AJAX API (JSON Responses)

To make AJAX requests, add header: `X-Requested-With: XMLHttpRequest`

#### 1. Search (AJAX)
```
POST /unicef_indexation/index.php?action=search
Headers:
  X-Requested-With: XMLHttpRequest

Parameters:
  - query (string): Search query

Response (JSON):
{
  "success": true,
  "results": [...],
  "indexed_count": 5,
  "total_matches": 15,
  "query": "vaccination",
  "message": "Found 15 relevant documents"
}
```

#### 2. Extract (AJAX)
```
POST /unicef_indexation/index.php?action=extract
Headers:
  X-Requested-With: XMLHttpRequest

Parameters:
  - num_posts (int): Number of articles

Response (JSON):
{
  "success": true,
  "message": "News extraction completed",
  "output": "✅ Saved 20 news items to database"
}
```

#### 3. Statistics (AJAX)
```
GET /unicef_indexation/index.php?action=stats
Headers:
  X-Requested-With: XMLHttpRequest

Response (JSON):
{
  "success": true,
  "stats": {
    "total": 100,
    "indexed": 45,
    "non_indexed": 55
  }
}
```

---

## 📊 How the Search Algorithm Works

### TF-IDF Algorithm

The system uses **TF-IDF** (Term Frequency-Inverse Document Frequency) for relevance ranking:

1. **Preprocessing**: 
   - Lowercase all text
   - Remove punctuation
   - Remove stopwords (the, and, or, etc.)
   - Tokenize into words

2. **Term Frequency (TF)**:
   ```
   TF(term) = (Count of term in document) / (Total terms in document)
   ```

3. **Inverse Document Frequency (IDF)**:
   ```
   IDF(term) = log(Total documents / Documents containing term)
   ```

4. **TF-IDF Score**:
   ```
   Score = TF(term) × IDF(term)
   ```

5. **Ranking**:
   - Calculate TF-IDF for each query term in each document
   - Sum scores for all query terms
   - Return top 10 most relevant documents

### Indexation Process

When a search is performed:
1. Python script receives query
2. Fetches all documents from database
3. Calculates TF-IDF scores
4. Updates `is_indexed = TRUE` for top 10 most relevant documents
5. Records search in `indexation_log` table
6. Returns results to PHP

---

## 🔒 Security Considerations

### Current Implementation:
✅ Uses parameterized queries (prepared statements) to prevent SQL injection
✅ Sanitizes output with `htmlspecialchars()`
✅ Validates and trims user input

### Recommendations for Production:
1. Add user authentication
2. Implement rate limiting on search/extract
3. Use HTTPS for all connections
4. Store sensitive config in environment variables
5. Add CSRF tokens for form submissions
6. Implement logging for auditing

---

## 📈 Performance Tips

1. **Database Indexing**: 
   - `unicef_news` has fulltext index on `content`
   - Consider adding index on `is_indexed` and `publish_date`

2. **Caching**:
   - Cache search results for common queries
   - Cache statistics in memory

3. **Pagination**:
   - For large result sets, implement pagination
   - Reduce data transfer and UI rendering time

4. **Python Optimization**:
   - Consider running Python scripts asynchronously
   - Implement background job queue (e.g., Celery) for large extractions

---

## 🎓 Learning Path

1. Start with extraction: Extract sample news
2. Test searches: Try different query terms
3. Check database: View data in phpMyAdmin
4. Review code: Understand PHP/Python interaction
5. Customize: Add more news sources or modify algorithm
6. Integrate: Add to your dashboard

---

## ✨ Next Steps

1. ✅ Complete all installation steps
2. ✅ Test each component
3. ✅ Extract sample news
4. ✅ Perform test searches
5. ✅ Check database
6. ✅ Integrate with your dashboard
7. ✅ Deploy to production

---

## 📞 Support

If you encounter issues:
1. Check the Troubleshooting section
2. Review error logs in XAMPP
3. Test components individually
4. Verify all prerequisites are installed
5. Check file permissions and paths

---

**Last Updated**: April 2026
**Version**: 1.0
**Status**: Production-Ready
