DROP DATABASE IF EXISTS yopy_platform;
CREATE DATABASE IF NOT EXISTS yopy_platform
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE yopy_platform;

-- 1. Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('parent','child','admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_users_email (email),
    UNIQUE KEY uniq_users_username (username)
) ENGINE=InnoDB;

-- 2. Parents Table
CREATE TABLE parents (
    parent_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    CONSTRAINT fk_parents_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Children Table
CREATE TABLE children (
    child_id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    nickname VARCHAR(50) NOT NULL,
    age INT NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_children_parent
        FOREIGN KEY (parent_id) REFERENCES parents(parent_id)
        ON DELETE CASCADE,
    INDEX idx_children_parent (parent_id)
) ENGINE=InnoDB;

-- 4. Characters Table
CREATE TABLE characters (
    character_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    avatar_image VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 5. Games Table
CREATE TABLE games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    difficulty VARCHAR(20) NOT NULL,
    description TEXT
) ENGINE=InnoDB;







-- 8. Password Resets Table (Fixed & Cleaned).............
CREATE TABLE password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    selector VARCHAR(32) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_selector (selector)
) ENGINE=InnoDB;