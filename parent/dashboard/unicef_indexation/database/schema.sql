-- Create database
CREATE DATABASE IF NOT EXISTS unicef_news_db;
USE unicef_news_db;

-- Create news table
CREATE TABLE IF NOT EXISTS unicef_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    content TEXT NOT NULL,
    url VARCHAR(500),
    publish_date DATE,
    is_indexed BOOLEAN DEFAULT FALSE,
    indexed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexation table to track queries and results
CREATE TABLE IF NOT EXISTS indexation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_text VARCHAR(500) NOT NULL,
    matched_docs TEXT,
    indexed_count INT,
    search_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add fulltext index for better search
ALTER TABLE unicef_news ADD FULLTEXT INDEX ft_content (content);