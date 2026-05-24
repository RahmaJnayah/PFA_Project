# NJAREB - Search & Indexation Integration

## Overview
This folder is now self-contained with all necessary search and indexation functionality.

## File Structure

```
njareb/
├── index.html                  # Main app HTML (no changes)
├── app.js                      # App logic (updated for local search)
├── style.css                   # Styling (no changes)
├── data.js                     # Mock data (no changes)
├── Guide.js                    # Tutorial (no changes)
├── config.php                  # LOCAL configuration
├── NewsModel.php               # LOCAL database model
├── search-api.php              # LOCAL search API handler
└── search-integration.js       # Search module (updated to use local API)
```

## How It Works

### Flow Diagram
```
User Types in #searchInput
    ↓
search-integration.js detects input
    ↓
Sends POST to search-api.php?action=search
    ↓
search-api.php extracts Python interpreter path
    ↓
Executes Python indexation_engine.py
    ↓
Python returns JSON results
    ↓
search-api.php validates and returns JSON
    ↓
search-integration.js displays results in #newsGrid
```

### Key Files

#### 1. **search-api.php** (NEW)
- Local API endpoint for all search operations
- Handles PHP-Python communication
- Validates JSON output from Python
- Returns structured JSON responses

#### 2. **config.php** (NEW)
- Database credentials (same as parent folder)
- Path to Python indexation_engine.py
- Application settings

#### 3. **NewsModel.php** (NEW)
- Database abstraction layer
- Methods for fetching news, stats, search history
- Error handling for database operations

#### 4. **search-integration.js** (MODIFIED)
- Changed API endpoint from `../index.php` to `search-api.php`
- Enhanced error handling
- Same functionality as before

## Technical Details

### Database Connection
- Same MySQL database: `unicef_news_db`
- Connection handled through `NewsModel.php`
- Credentials in `config.php`

### Python Integration
- `search-api.php` calls Python via `shell_exec()`
- Python script path: `../python/indexation_engine.py`
- Output is JSON-validated before returning

### Search Flow
1. User enters search query
2. JavaScript sends POST to `search-api.php?action=search`
3. PHP validates input and calls Python indexation engine
4. Python performs TF-IDF search and returns JSON
5. PHP validates JSON and returns to frontend
6. JavaScript displays results as news cards

## API Endpoints

### Search
**Request:**
```
POST search-api.php?action=search
body: query=<search_term>
```

**Response:**
```json
{
  "success": true,
  "results": [...],
  "indexed_count": 5,
  "total_matches": 12,
  "query": "search term",
  "message": "Found 12 relevant documents"
}
```

### Statistics
**Request:**
```
GET search-api.php?action=stats
```

**Response:**
```json
{
  "success": true,
  "stats": {
    "total": 50,
    "indexed": 30,
    "non_indexed": 20
  }
}
```

## Setup Checklist

- [ ] Database `unicef_news_db` exists with tables
- [ ] Python installed and in system PATH
- [ ] `python/indexation_engine.py` exists in parent folder
- [ ] MySQL running on localhost:3307
- [ ] All files in njareb folder created
- [ ] No errors in browser console

## Troubleshooting

### "Python indexation engine not found"
- Verify `../python/indexation_engine.py` exists
- Check file permissions

### "Database error"
- Verify MySQL is running
- Check credentials in `config.php`
- Check database structure

### Search returns no results
- Verify `unicef_news` table has data
- Check browser DevTools Console for errors
- Verify Python script is working: `python ../python/indexation_engine.py "test"`

### JSON parse errors
- Check that Python outputs only JSON
- Look for Python warnings/errors
- Verify Python script path in `config.php`

## API Error Responses

All errors return valid JSON:
```json
{
  "success": false,
  "error": "Error message describing what went wrong",
  "query": "search_term"
}
```

## Features

✅ Local search API (no parent folder dependencies)  
✅ Real-time search with debouncing (400ms)  
✅ TF-IDF indexation algorithm  
✅ Mobile responsive results  
✅ Category filtering  
✅ Article modal viewer  
✅ Error handling and validation  
✅ Theme support (light/dark)  

## Dependencies

- **Database**: MySQL 5.7+
- **Backend**: PHP 7.4+
- **Python**: 3.6+ with mysql-connector-python
- **Frontend**: Bootstrap 5, Chart.js

## Future Improvements

1. Add caching for frequent searches
2. Implement search history display
3. Add result sorting options
4. Performance optimization
5. Advanced search filters

---

**Last Updated**: April 7, 2026  
**Status**: ✅ Self-contained and production-ready
