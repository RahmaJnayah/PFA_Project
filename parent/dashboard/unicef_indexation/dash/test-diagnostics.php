<?php
/**
 * Diagnostic Test Script for NJAREB Search System
 * Verify all components are working
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NJAREB Search System - Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test { margin: 15px 0; padding: 15px; border-left: 4px solid #ddd; }
        .pass { border-left-color: #4CAF50; background: #f1f8f4; }
        .fail { border-left-color: #f44336; background: #fef1f0; }
        .warn { border-left-color: #ff9800; background: #fff8f3; }
        .status { font-weight: bold; margin-bottom: 5px; }
        .pass .status { color: #4CAF50; }
        .fail .status { color: #f44336; }
        .warn .status { color: #ff9800; }
        .message { font-size: 12px; color: #666; margin-top: 5px; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 20px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 NJAREB Search System - Diagnostics</h1>
        <p>Testing all components of the search and indexation system...</p>

        <h2>1. Configuration</h2>
        <?php
        // Test 1: Config file
        if (file_exists('config.php')) {
            require_once 'config.php';
            echo '<div class="test pass">';
            echo '<div class="status">✓ config.php found</div>';
            echo '<div class="message">Database: ' . DB_HOST . ':' . DB_PORT . '/' . DB_NAME . '</div>';
            if (defined('PYTHON_INDEXER')) {
                echo '<div class="message">Python: ' . PYTHON_INDEXER . '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="test fail">';
            echo '<div class="status">✗ config.php NOT found</div>';
            echo '<div class="message">Expected at: ' . __DIR__ . '/config.php</div>';
            echo '</div>';
        }
        ?>

        <h2>2. Models</h2>
        <?php
        // Test 2: NewsModel
        if (file_exists('NewsModel.php')) {
            echo '<div class="test pass">';
            echo '<div class="status">✓ NewsModel.php found</div>';
            echo '</div>';
        } else {
            echo '<div class="test fail">';
            echo '<div class="status">✗ NewsModel.php NOT found</div>';
            echo '</div>';
        }
        ?>

        <h2>3. API</h2>
        <?php
        // Test 3: Search API
        if (file_exists('search-api.php')) {
            echo '<div class="test pass">';
            echo '<div class="status">✓ search-api.php found</div>';
            echo '<div class="message">Endpoint: <code>search-api.php?action=search</code></div>';
            echo '</div>';
        } else {
            echo '<div class="test fail">';
            echo '<div class="status">✗ search-api.php NOT found</div>';
            echo '</div>';
        }
        ?>

        <h2>4. Frontend</h2>
        <?php
        // Test 4: JavaScript
        if (file_exists('search-integration.js')) {
            echo '<div class="test pass">';
            echo '<div class="status">✓ search-integration.js found</div>';
            echo '</div>';
        } else {
            echo '<div class="test fail">';
            echo '<div class="status">✗ search-integration.js NOT found</div>';
            echo '</div>';
        }
        ?>

        <h2>5. Python Integration</h2>
        <?php
        // Test 5: Python
        if (defined('PYTHON_INDEXER') && file_exists(PYTHON_INDEXER)) {
            echo '<div class="test pass">';
            echo '<div class="status">✓ indexation_engine.py found</div>';
            echo '<div class="message">Path: ' . PYTHON_INDEXER . '</div>';
            echo '</div>';
        } else {
            echo '<div class="test fail">';
            echo '<div class="status">✗ indexation_engine.py NOT found</div>';
            if (defined('PYTHON_INDEXER')) {
                echo '<div class="message">Expected: ' . PYTHON_INDEXER . '</div>';
            }
            echo '</div>';
        }
        ?>

        <h2>6. Database</h2>
        <?php
        // Test 6: Database connection
        if (class_exists('NewsModel')) {
            try {
                $model = new NewsModel();
                if ($model->testConnection()) {
                    echo '<div class="test pass">';
                    echo '<div class="status">✓ Database connection OK</div>';
                    $stats = $model->getStats();
                    echo '<div class="message">Total articles: ' . $stats['total'] . '</div>';
                    echo '<div class="message">Indexed: ' . $stats['indexed'] . '</div>';
                    echo '<div class="message">Non-indexed: ' . $stats['non_indexed'] . '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="test fail">';
                    echo '<div class="status">✗ Database ping failed</div>';
                    echo '</div>';
                }
                $model->closeConnection();
            } catch (Exception $e) {
                echo '<div class="test fail">';
                echo '<div class="status">✗ Database connection failed</div>';
                echo '<div class="message">Error: ' . $e->getMessage() . '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="test warn">';
            echo '<div class="status">⚠ NewsModel not loaded (load config.php first)</div>';
            echo '</div>';
        }
        ?>

        <h2>7. System Environment</h2>
        <?php
        // Test 7: Environment
        echo '<div class="test">';
        echo '<div class="status">ℹ System Information</div>';
        echo '<div class="message">OS: ' . php_uname() . '</div>';
        echo '<div class="message">PHP: ' . phpversion() . '</div>';
        echo '<div class="message">Extensions: ';
        if (extension_loaded('mysqli')) {
            echo '<code>✓mysqli</code> ';
        }
        if (extension_loaded('json')) {
            echo '<code>✓json</code> ';
        }
        echo '</div>';
        echo '</div>';
        ?>

        <h2>Quick Test</h2>
        <?php
        if (file_exists('search-api.php')) {
            echo '<p>To test the search API, try:</p>';
            echo '<code>POST search-api.php?action=search</code><br>';
            echo '<code>body: query=test</code><br><br>';
        }
        ?>

        <h2>Troubleshooting</h2>
        <ul>
            <li>If tests fail, check file permissions</li>
            <li>Verify MySQL is running: port 3307</li>
            <li>Confirm database structure: <code>database/schema.sql</code></li>
            <li>Test Python directly: <code>python indexation_engine.py "test"</code></li>
            <li>Check browser console (F12) for JavaScript errors</li>
        </ul>

        <p><a href="index.html">Back to App →</a></p>
    </div>
</body>
</html>
