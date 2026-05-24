<?php
/**
 * NewsModel - Database access layer for njareb
 * Handles all database operations for search and indexation
 */

class NewsModel {
    private $conn;
    
    public function __construct() {
        try {
            // Use mysqli for better compatibility
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            throw new Exception("Database Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get all news (indexed and non-indexed)
     */
    public function getAllNews() {
        try {
            $sql = "SELECT id, title, content, category, publish_date, image, url, is_indexed, created_at 
                   FROM unicef_news 
                   ORDER BY created_at DESC";
            
            $result = $this->conn->query($sql);
            
            if (!$result) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            
            $news = [];
            while ($row = $result->fetch_assoc()) {
                $news[] = $row;
            }
            
            return $news;
        } catch (Exception $e) {
            error_log("getAllNews error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get indexed news
     */
    public function getIndexedNews() {
        try {
            $sql = "SELECT id, title, content, category, publish_date, image, url, is_indexed, indexed_date 
                   FROM unicef_news 
                   WHERE is_indexed = TRUE 
                   ORDER BY indexed_date DESC";
            
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("getIndexedNews error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get search history
     */
    public function getSearchHistory() {
        try {
            $sql = "SELECT query_text, matched_docs, indexed_count, search_date 
                   FROM indexation_log 
                   ORDER BY search_date DESC 
                   LIMIT 50";
            
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("getSearchHistory error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics
     */
    public function getStats() {
        try {
            $result = [
                'total' => 0,
                'indexed' => 0,
                'non_indexed' => 0
            ];
            
            // Total count
            $sql = "SELECT COUNT(*) as count FROM unicef_news";
            $res = $this->conn->query($sql);
            if ($res) {
                $row = $res->fetch_assoc();
                $result['total'] = $row['count'];
            }
            
            // Indexed count
            $sql = "SELECT COUNT(*) as count FROM unicef_news WHERE is_indexed = TRUE";
            $res = $this->conn->query($sql);
            if ($res) {
                $row = $res->fetch_assoc();
                $result['indexed'] = $row['count'];
            }
            
            // Non-indexed count
            $result['non_indexed'] = $result['total'] - $result['indexed'];
            
            return $result;
        } catch (Exception $e) {
            error_log("getStats error: " . $e->getMessage());
            return ['total' => 0, 'indexed' => 0, 'non_indexed' => 0];
        }
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        return $this->conn && $this->conn->ping();
    }
    
    /**
     * Close connection
     */
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->closeConnection();
    }
}
?>
