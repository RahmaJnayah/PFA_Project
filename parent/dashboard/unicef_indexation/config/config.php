<?php
// Database configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_PORT', 3307);        // Changed from default 3306 to 3307
define('DB_USER', 'root');      // Default XAMPP username
define('DB_PASS', '');          // Default XAMPP password (empty)
define('DB_NAME', 'unicef_news_db');

// Python script paths (adjust for Windows)
define('PYTHON_EXTRACTOR', 'C:\xampp\htdocs\pfa\PRO-PFE-YOPY\yopy-platform\views\parent\dashboard\unicef_indexation\python\extract_news.py');
define('PYTHON_INDEXER', 'C:\xampp\htdocs\pfa\PRO-PFE-YOPY\yopy-platform\views\parent\dashboard\unicef_indexation\python\indexation_engine.py');

// Application configuration
define('SITE_NAME', 'UNICEF News Indexation System');
?>
