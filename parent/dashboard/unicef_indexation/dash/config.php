<?php
/**
 * NJAREB Configuration - Search & Indexation System
 * Self-contained configuration for the njareb folder
 */

// Database configuration for XAMPP
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3307));
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'unicef_news_db');

// Python script paths (parent folder)
define('PYTHON_INDEXER', dirname(__DIR__) . '/python/indexation_engine.py');
define('PYTHON_CMD', getenv('PYTHON_CMD') ?: '');

// Application configuration
define('SITE_NAME', 'UNICEF News Indexation - Sentiment Hub');

// API Response defaults
define('DEFAULT_RESULTS_LIMIT', 10);
?>
