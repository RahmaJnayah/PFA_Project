# Search & Indexation Integration Guide

## Overview
The UNICEF News Indexation & Search system has been successfully integrated into the njareb Sentiment Hub application. Users can now search for news articles from the parent folder's indexation database directly within the News & Insights page (`#newsPage`).

## Integration Summary

### What's Been Added

#### 1. **search-integration.js** (NEW FILE)
Location: `njareb/search-integration.js`

This module provides the complete search functionality:
- Real-time search with debouncing (400ms)
- API calls to the parent folder's search system
- Results formatting with consistent styling
- Article modal display
- Category-based color coding

**Key Features:**
- Automatic initialization on DOM ready
- Debounced search input (prevents excessive API calls)
- Enter key support for immediate search
- Loading state visualization
- Error handling
- Category color mapping (Psychology, Health, Education, Research, News)

### Files Modified

#### 2. **app.js**
- Updated search input event listener to navigate to news page on focus
- Enhanced filter pill functionality to clear search when clicked
- Integration with SearchIntegration module instance

#### 3. **index.html**
- Added `<script src="search-integration.js"></script>` after `app.js`
- Maintains existing HTML elements: `#searchInput`, `#resultsContainer`, `#newsGrid`

## How It Works

### User Interaction Flow

1. **User visits News & Insights Page**
   - Page initializes with category filter pills (All, Psychology, Health, Education, Research)
   - Featured hero article displayed
   - Default news articles shown (from mock data)

2. **User types in search box**
   - SearchIntegration listens to input with debounce
   - After 400ms of inactivity, search fires automatically
   - OR user can press Enter for immediate search

3. **Search results display**
   - `#resultsContainer` shows search metadata (number of results found)
   - `#newsGrid` renders search results as news cards
   - Each card includes: image, category badge, title, description, date, read time

4. **User clicks result**
   - Article modal opens with full content
   - User can read the article or click "Read Full Article" for external link

5. **User clicks category filter**
   - Search is cleared
   - Default news filtered by category
   - Filter pills highlight the active category

### API Communication

**Search Request:**
```javascript
POST /index.php?action=search
Content-Type: application/x-www-form-urlencoded

query=search+term
```

**Expected Response:**
```json
{
  "success": true,
  "query": "search term",
  "total_matches": 10,
  "indexed_count": 5,
  "results": [
    {
      "id": 1,
      "title": "Article Title",
      "description": "Article description",
      "content": "Full article content",
      "category": "Psychology",
      "date": "2024-10-15",
      "image": "https://...",
      "url": "https://..."
    }
    // ... more results
  ]
}
```

## Styling & Design

### Design Consistency
All search results maintain the existing njareb design:
- **Color Variables**: Uses CSS variables from style.css (--cyan, --violet, --gold, --mint, --coral, etc.)
- **Typography**: Matches Sentiment Hub's font stack (Syne for headings, DM Sans for body)
- **Component Reuse**: News cards, chips, modals all use existing styles
- **Responsive**: Fully responsive design inherited from Bootstrap 5 integration

### CSS Classes Used
- `.news-card` - Individual article card container
- `.news-card-img` - Article image with 160px height
- `.news-card-body` - Card content area
- `.news-card-title` - Article title
- `.news-card-desc` - Article description excerpt
- `.news-card-meta` - Article metadata (date, read time)
- `.chip`, `.chip-cyan`, `.chip-violet`, `.chip-gold`, `.chip-mint` - Category badges
- `.modal-overlay`, `.modal-box` - Modal dialog for full article view

## Search Features

### Search Input Behavior
- **Debounced**: 400ms delay to reduce API calls while user is typing
- **Auto-navigate**: Focuses news page when user starts typing
- **Enter to search**: User can press Enter for immediate results
- **Clear on focus**: Clicking filter pills clears search and restores category view

### Category Mapping
| Category | Color Class | Color |
|----------|------------|-------|
| Psychology | chip-cyan | #69DAFF |
| Health | chip-violet | #A68CFF |
| Education | chip-gold | #FFD166 |
| Research | chip-mint | #4ECDC4 |
| Unknown | chip-cyan | #69DAFF |

### Error Handling
- Network errors display user-friendly message
- No results shows "🔍 No articles found" state
- Loading state shows spinner animation
- HTML sanitization prevents XSS attacks

## Key Functions

### SearchIntegration Class Methods

```javascript
// Constructor
new SearchIntegration({
  apiBase: '../index.php',
  searchInputId: 'searchInput',
  resultsContainerId: 'newsGrid',
  resultsInfoId: 'resultsContainer'
})

// Public Methods
search(query)              // Perform search
clearResults()             // Clear search and restore defaults
openArticle(index)         // Open article modal
displayResults(data)       // Render search results
showError(message)         // Show error message
```

### Global Access
SearchIntegration instance is available globally:
```javascript
window.searchIntegration.search('psychology')
window.searchIntegration.clearResults()
```

## Integration Points

### With Parent Folder
- API endpoint: `../index.php?action=search`
- Requires NewsModel and SearchController from parent folder
- Uses same database connection

### With Sentiment Hub
- Inherits all existing styles and themes
- Uses app.js navigation (`showPage()`, `currentPage`)
- Integrates with njareb data structure
- Modal system (`articleModal`, `openArticleModal`)

## Troubleshooting

### Search Not Working
1. Check that parent folder's search API is accessible at `../index.php`
2. Verify database configuration in parent folder
3. Check browser console for error messages
4. Ensure Flask/Python services are running for indexation

### Styling Issues
1. Verify style.css is loaded before search-integration.js
2. Check CSS variable values in browser DevTools
3. Clear browser cache and reload

### Results Not Displaying
1. Check if API returns valid JSON response
2. Verify `#newsGrid` element exists in HTML
3. Check browser console for JavaScript errors
4. Verify database has indexed articles

### Modal Not Opening
1. Ensure `article-modal` exists in HTML (it's auto-created if not)
2. Check that CSS modal styles are loaded
3. Verify no JavaScript errors in console

## Performance Considerations

- **Debounce delay**: 400ms balances responsiveness with API efficiency
- **Caching**: Consider implementing result caching for frequent searches
- **Lazy loading**: Images use `loading="lazy"` for better performance
- **Placeholder images**: Fallback placeholder used if image fails to load

## Future Enhancements

Potential improvements:
1. Add search history/saved searches
2. Implement result sorting options (date, relevance, category)
3. Add pagination for large result sets
4. Add advanced filters (date range, multiple categories)
5. Implement local caching for offline search
6. Add analytics for search queries

## File Manifest

| File | Status | Purpose |
|------|--------|---------|
| search-integration.js | NEW | Main search module |
| app.js | MODIFIED | Enhanced search handling |
| index.html | MODIFIED | Added search-integration.js script |
| data.js | UNCHANGED | Still provides mock news data |
| style.css | UNCHANGED | Existing styles already sufficient |

## Testing Checklist

- [ ] Search box is visible on News page
- [ ] Typing in search box triggers API call after 400ms
- [ ] Pressing Enter in search box triggers immediate search
- [ ] Search results display as cards in newsGrid
- [ ] Results info shows in resultsContainer
- [ ] Category badges show correct colors
- [ ] Clicking result opens modal
- [ ] Modal shows full article content
- [ ] Clicking category filter clears search
- [ ] "No results" message displays properly
- [ ] Loading state shows spinner
- [ ] Error messages display on API failure
- [ ] Mobile responsive layout works
- [ ] Light/dark theme works with search results

## Support

For issues or questions:
1. Check browser DevTools Console for errors
2. Verify parent folder API is running
3. Check network requests in DevTools Network tab
4. Review this documentation
5. Check database connection in parent folder

---

**Integration Date**: April 7, 2026  
**Status**: ✅ Complete and Ready for Testing
