<?php
/**
 * Search API Handler - Local API for njareb
 * Handles search requests and communicates with Python indexation engine
 */

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'parent') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

function normalizeSearchQuery(string $query): string
{
    $query = trim($query);
    $query = preg_replace('/\s+/u', ' ', $query) ?? $query;
    return mb_strtolower($query, 'UTF-8');
}

function rateLimitSearch(string $query, int $limitSeconds = 2): void
{
    $now = microtime(true);
    $normalizedQuery = normalizeSearchQuery($query);
    $last = $_SESSION['search_api_last_request'] ?? null;

    if (is_array($last)) {
        $lastTime = (float) ($last['time'] ?? 0);
        $lastQuery = (string) ($last['query'] ?? '');

        if ($lastTime > 0 && ($now - $lastTime) < $limitSeconds && $lastQuery === $normalizedQuery) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Too many requests. Please wait a moment before searching again.',
            ]);
            exit;
        }
    }

    $_SESSION['search_api_last_request'] = [
        'time' => $now,
        'query' => $normalizedQuery,
    ];
}

try {
    // Load configuration and models
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/NewsModel.php';

    // Set JSON response header
    header('Content-Type: application/json; charset=utf-8');
    
    // Check if AJAX request
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    
    // Get action parameter
    $action = isset($_GET['action']) ? trim($_GET['action']) : 'search';
    
    // Handle different actions
    switch($action) {
        case 'search':
            handleSearch();
            break;
        case 'stats':
            handleStats();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Throwable $e) {
    error_log('Search API error: ' . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error. Please try again later.'
    ]);
}

/**
 * Handle search request
 */
function handleSearch() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['query'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        return;
    }
    
    $query = trim($_POST['query']);

    rateLimitSearch($query);

    if ($query === '' || strlen($query) < 2) {
        echo json_encode(['success' => false, 'error' => 'Query is too short']);
        return;
    }

    if (strlen($query) > 120) {
        echo json_encode(['success' => false, 'error' => 'Query is too long']);
        return;
    }
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Empty query']);
        return;
    }
    
    try {
        if (!is_callable('shell_exec')) {
            throw new Exception('Search engine unavailable');
        }

        // Call Python indexation engine
        $pythonScript = PYTHON_INDEXER;
        
        // Check if Python script exists
        if (!file_exists($pythonScript)) {
            throw new Exception('Search engine unavailable');
        }
        
        // Determine Python command based on OS
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // Allow explicit Python command override from config/env
        $pythonCmd = defined('PYTHON_CMD') && PYTHON_CMD !== '' ? PYTHON_CMD : null;

        // Try to find Python in system PATH
        if ($pythonCmd === null) {
            if ($isWindows) {
                // Windows: try python.exe, python3.exe, then py.exe
                $output = null;
                exec('where python.exe 2>nul', $output);
                if (!empty($output)) {
                    $pythonCmd = 'python.exe';
                } else {
                    exec('where python3.exe 2>nul', $output);
                    if (!empty($output)) {
                        $pythonCmd = 'python3.exe';
                    } else {
                        exec('where py.exe 2>nul', $output);
                        if (!empty($output)) {
                            $pythonCmd = 'py -3';
                        } else {
                            $pythonCmd = 'python';
                        }
                    }
                }
            } else {
                // Unix-like: try python3 first, then python
                $output = null;
                exec('which python3 2>/dev/null', $output);
                if (!empty($output)) {
                    $pythonCmd = 'python3';
                } else {
                    exec('which python 2>/dev/null', $output);
                    if (!empty($output)) {
                        $pythonCmd = 'python';
                    } else {
                        $pythonCmd = 'python';
                    }
                }
            }
        }
        
        // Build command with proper escaping
        $command = $pythonCmd . " " . escapeshellarg($pythonScript) . " " . escapeshellarg($query);
        
        // Execute command (capture stderr for diagnostics)
        $redirectCmd = ' 2>&1';
        $output = shell_exec($command . $redirectCmd);
        
        if (!$output) {
            throw new Exception('Search engine unavailable');
        }
        
        // Extract JSON from output (in case there's extra text)
        $json = extractJSON($output);
        
        if (!$json) {
            throw new Exception('Search engine returned an invalid response');
        }
        
        // Parse JSON
        $result = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parse error: " . json_last_error_msg());
        }
        
        // Return results
        if ($result && isset($result['results'])) {
            echo json_encode([
                'success' => true,
                'results' => $result['results'],
                'indexed_count' => $result['indexed_count'] ?? 0,
                'query' => $query,
                'total_matches' => $result['total_matches'] ?? 0,
                'message' => "Found " . ($result['total_matches'] ?? 0) . " relevant documents"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No results found for your query',
                'query' => $query,
                'results' => []
            ]);
        }
        
    } catch (Throwable $e) {
        error_log('Search request failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Search error. Please try again later.',
            'query' => $query
        ]);
    }
}

/**
 * Handle stats request
 */
function handleStats() {
    try {
        $model = new NewsModel();
        $stats = $model->getStats();
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
        $model->closeConnection();
    } catch (Throwable $e) {
        error_log('Search stats failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Stats error. Please try again later.'
        ]);
    }
}

/**
 * Extract JSON from mixed output
 * Handles cases where there's non-JSON text before or after the JSON
 */
function extractJSON($output) {
    $output = trim($output);
    
    // Find the first { and last }
    $start = strpos($output, '{');
    $end = strrpos($output, '}');
    
    if ($start !== false && $end !== false && $start < $end) {
        return substr($output, $start, $end - $start + 1);
    }
    
    return null;
}
?>
