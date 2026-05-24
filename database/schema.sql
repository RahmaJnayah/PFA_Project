-- ═══════════════════════════════════════════════════════════════
-- YOPY — Database Schema (merged for admin module)
-- Engine: MySQL 8+ / MariaDB 10.4+
-- ═══════════════════════════════════════════════════════════════
use yopy_platform;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(50) NOT NULL,
	email VARCHAR(100) NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	pin_hash VARCHAR(255) NULL,
	role ENUM('parent','child','admin') NOT NULL,
	plan ENUM('free','premium') NOT NULL DEFAULT 'free',
	status ENUM('active','suspended') NOT NULL DEFAULT 'active',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY uniq_users_email (email),
	UNIQUE KEY uniq_users_username (username)
) ENGINE=InnoDB;

-- 2. Parents Table
CREATE TABLE IF NOT EXISTS parents (
	parent_id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL,
	CONSTRAINT fk_parents_user
		FOREIGN KEY (user_id) REFERENCES users(id)
		ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Characters Table
CREATE TABLE IF NOT EXISTS characters (
	character_id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(50) NOT NULL,
	avatar_image VARCHAR(255) DEFAULT NULL,
	trait VARCHAR(120) NOT NULL DEFAULT 'Full of Sunshine',
	tagline VARCHAR(255) NOT NULL DEFAULT 'Let\'s make every moment magical! ✨',
	color CHAR(7) NOT NULL DEFAULT '#9B59B6',
	is_active TINYINT(1) NOT NULL DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Children Table
CREATE TABLE IF NOT EXISTS children (
	child_id INT AUTO_INCREMENT PRIMARY KEY,
	parent_id INT NOT NULL,
	nickname VARCHAR(50) NOT NULL,
	age INT NULL,
	avatar VARCHAR(255) DEFAULT NULL,
	emoji VARCHAR(8) DEFAULT '🦊',
	theme VARCHAR(40) DEFAULT 'theme-rose',
	character_id INT DEFAULT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_children_parent
		FOREIGN KEY (parent_id) REFERENCES parents(parent_id)
		ON DELETE CASCADE,
	CONSTRAINT fk_children_character
		FOREIGN KEY (character_id) REFERENCES characters(character_id)
		ON DELETE SET NULL,
	INDEX idx_children_parent (parent_id)
) ENGINE=InnoDB;

-- 5. Games Table
CREATE TABLE IF NOT EXISTS games (
	game_id INT AUTO_INCREMENT PRIMARY KEY,
	slug VARCHAR(100) NOT NULL,
	name VARCHAR(100) NOT NULL,
	category VARCHAR(50) NOT NULL,
	difficulty VARCHAR(20) NOT NULL,
	description TEXT,
	is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;







-- 8. Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
	reset_id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL,
	selector VARCHAR(32) NOT NULL,
	token_hash CHAR(64) NOT NULL,
	expires_at DATETIME NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_password_resets_email (email),
	INDEX idx_password_resets_selector (selector)
) ENGINE=InnoDB;



-- ── Backfill columns for existing databases ───────────────────────────────
ALTER TABLE users
	ADD COLUMN IF NOT EXISTS pin_hash VARCHAR(255) NULL,
	ADD COLUMN IF NOT EXISTS plan ENUM('free','premium') NOT NULL DEFAULT 'free',
	ADD COLUMN IF NOT EXISTS status ENUM('active','suspended') NOT NULL DEFAULT 'active',
	ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE characters
	ADD COLUMN IF NOT EXISTS avatar_image VARCHAR(255) NULL,
	ADD COLUMN IF NOT EXISTS trait VARCHAR(120) NOT NULL DEFAULT 'Full of Sunshine',
	ADD COLUMN IF NOT EXISTS tagline VARCHAR(255) NOT NULL DEFAULT 'Let\'s make every moment magical! ✨',
	ADD COLUMN IF NOT EXISTS color CHAR(7) NOT NULL DEFAULT '#9B59B6',
	ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1,
	ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE children
	ADD COLUMN IF NOT EXISTS emoji VARCHAR(8) DEFAULT '🦊',
	ADD COLUMN IF NOT EXISTS theme VARCHAR(40) DEFAULT 'theme-rose',
	ADD COLUMN IF NOT EXISTS character_id INT DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE games
	ADD COLUMN IF NOT EXISTS slug VARCHAR(100) NOT NULL DEFAULT '',
	ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;


UPDATE games SET slug = 'memory_game' WHERE name = 'Memory Match' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'simon_says' WHERE name = 'Simon Says' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'sudoku' WHERE name = 'Sudoku' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'tic_tac_toe' WHERE name = 'Tic-Tac-Toe' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'whack_a_mole' WHERE name = 'Whack-a-Mole' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'snake_retro' WHERE name = 'Snake Retro' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'math_sprint' WHERE name = 'Math Sprint' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'word_scramble' WHERE name = 'Word Scramble' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'spelling_bee' WHERE name = 'Spelling Bee' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'tetris' WHERE name = 'Tetris' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'maze_runner' WHERE name = 'Maze Runner' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'tile_puzzle' WHERE name = 'Tile Puzzle' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'hangman_quest' WHERE name = 'Hangman Quest' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'synonym_challenge' WHERE name = 'Synonym Challenge' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'tower_blocks' WHERE name = 'Tower Blocks' AND (slug IS NULL OR slug = '');
UPDATE games SET slug = 'image_puzzle' WHERE name = 'Image Puzzle' AND (slug IS NULL OR slug = '');

SET FOREIGN_KEY_CHECKS = 1;

-- Seed data (safe to run multiple times)
INSERT IGNORE INTO characters (character_id, name, avatar_image, trait, tagline, color, is_active) VALUES
  (1, 'Joyla',  'joy.png',   'Full of Sunshine',   'Let\'s make every moment magical! ✨',          '#FFD700', 1),
  (2, 'Sparko', 'langer.png','Fierce & Fearless',  'Standing up for what\'s right — always! 🔥',   '#FF4422', 1),
  (3, 'Ticky',  'lemo.png',  'Alert & Thoughtful', 'Being careful keeps us safe and smart! 🌀',     '#FF9500', 1),
  (4, 'Binky',  'lilo.png',  'Brave at Heart',     'Even when scared, we grow stronger! 💜',        '#9B59B6', 1),
  (5, 'Bluey',  'cry.png',   'Deep & Caring',      'Feelings matter — all of them! 💙',             '#4FC3F7', 1),
  (6, 'Poppi',  'anx.png',   'Wildly Creative',    'When things get crazy — dance! 🎉',             '#FF6B6B', 1);

INSERT IGNORE INTO games (game_id, slug, name, category, difficulty, description, is_active) VALUES
	(1, 'memory_game', 'Memory Match', 'Memory', 'medium', 'Flip cards and find matching pairs.', 1),
	(2, 'simon_says', 'Simon Says', 'Sequence', 'medium', 'Watch the colour pattern and repeat it back.', 1),
	(3, 'sudoku', 'Sudoku', 'Logic', 'medium', 'Fill the grid — every row, column and box 1–9.', 1),
	(4, 'tic_tac_toe', 'Tic-Tac-Toe', 'Strategy', 'medium', 'Beat the bot — 3 in a row wins!', 1),
	(5, 'whack_a_mole', 'Whack-a-Mole', 'Reaction', 'medium', 'Whack the moles, dodge the bombs!', 1),
	(6, 'snake_retro', 'Snake Retro', 'Retro', 'medium', 'Eat, grow, survive — classic arcade fun!', 1),
	(7, 'math_sprint', 'Math Sprint', 'Math', 'medium', 'True or False? Race the clock on multiplication!', 1),
	(8, 'word_scramble', 'Word Scramble', 'Language', 'medium', 'Unscramble the letters before time runs out!', 1),
	(9, 'spelling_bee', 'Spelling Bee', 'Language', 'medium', 'Hear the word, spell it right!', 1),
	(10, 'tetris', 'Tetris', 'Classic', 'medium', 'Stack blocks, clear lines, level up!', 1),
	(11, 'maze_runner', 'Maze Runner', 'Strategy', 'medium', 'Navigate the maze from key to home — fastest wins!', 1),
	(12, 'tile_puzzle', 'Tile Puzzle', 'Cognitive', 'medium', 'Slide tiles to solve — A* hints when stuck!', 1),
	(13, 'hangman_quest', 'Hangman Quest', 'Word', 'medium', 'Guess the word letter by letter — use hints when stuck!', 1),
	(14, 'synonym_challenge', 'Synonym Challenge', 'English', 'easy', 'Match the meanings to win! High-speed vocabulary training.', 1),
	(15, 'tower_blocks', 'Tower Blocks', 'Reaction', 'easy', 'Stack blocks as high as you can! Click or tap at the right moment.', 1),
	(16, 'image_puzzle', 'Image Puzzle', 'Puzzle', 'medium', 'Rebuild the scattered image pieces!', 1),
	(17, 'canon_defender', 'Canon Defender', 'Action', 'medium', 'Defend the base by hitting incoming targets.', 1),
	(18, 'cut_the_rope', 'Cut the Rope', 'Puzzle', 'medium', 'Cut ropes to deliver the candy with precision.', 1);

INSERT INTO users (username, email, password_hash, role, plan, status)
VALUES ('YOPY Admin', 'admin@yopy.app', '$2b$12$xn2mKkJgpv.o1z8uRqdBiuTnLzZf2yWikdUxYLHZB1Zw8K.nLnYHO', 'admin', 'premium', 'active');
ALTER TABLE children 
ADD COLUMN IF NOT EXISTS buddy VARCHAR(20) DEFAULT NULL 
COMMENT 'Selected buddy key: joy, sadness, anger, disgust, fear, anxiety' 
AFTER theme;
-- ═══════════════════════════════════════════════════════════════
-- YOPY — Database Patch (run AFTER init.sql + schema.sql)
-- Fixes:
--   1. game_behaviors table missing from schema
--   2. All 15 games inserted into games table
-- ═════════════════════
USE yopy_platform;

-- ─────────────────────────────────────────────────────────────
-- FIX 1 : Create game_behaviors table (was missing entirely)
--          Referenced by GameService::saveBehavior() but never
--          defined in schema.sql or init.sql.
-- ─────────────────────────────────────────────────────────────


-- ─────────────────────────────────────────────────────────────
-- FIX 2 : Add missing columns that the PHP code expects
--          (is_active on games, difficulty on game_sessions).
--          Uses IF NOT EXISTS so it is safe to re-run.
-- ─────────────────────────────────────────────────────────────
ALTER TABLE games
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description;

    

-- ─────────────────────────────────────────────────────────────
-- FIX 3 : Insert all 15 YOPY games
--          Uses INSERT IGNORE so duplicates are skipped safely.
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO games (name, category, difficulty, description, is_active) VALUES
('hangman_quest',      'language',   'medium', 'Guess the hidden word letter by letter before the hangman is complete', 1),
('image_puzzle',       'cognitive',  'medium', 'Reassemble a scrambled image by sliding tiles into the correct order',  1),
('math_sprint',        'math',       'hard',   'Solve arithmetic challenges as fast as possible before time runs out',   1),
('maze_runner',        'motor',      'medium', 'Navigate a character through procedurally generated mazes',             1),
('memory_game',        'cognitive',  'medium', 'Classic memory card matching game with cute animal emoji pairs',         1),
('simon_says',         'memory',     'easy',   'Repeat the growing colour sequence shown by the game',                  1),
('snake_retro',        'motor',      'medium', 'Classic snake — eat pellets, grow longer, avoid walls and yourself',    1),
('spelling_bee',       'language',   'medium', 'Listen to a word and spell it correctly from the audio clue',           1),
('sudoku_pro',         'logic',      'hard',   '9×9 number puzzle — fill every row, column and box with 1–9',          1),
('synonym_challenge',  'language',   'medium', 'Pick the word that means the same as the one shown',                    1),
('tetris_block',       'spatial',    'hard',   'Rotate and place falling blocks to complete full horizontal lines',     1),
('tic_tac_toe',        'logic',      'easy',   'Classic noughts and crosses — beat the AI or play with a friend',      1),
('tile_puzzle',        'cognitive',  'medium', 'Slide numbered tiles into the correct order with as few moves as possible', 1),
('tower_blocks',       'spatial',    'medium', 'Stack blocks as high as possible without toppling the tower',           1),
('whack_a_mole',       'motor',      'easy',   'Tap or click the moles as they pop up to score points',                1),
('word_scramble',      'language',   'easy',   'Unscramble the mixed-up letters to form a valid word',                  1);




ALTER TABLE children
    ADD COLUMN IF NOT EXISTS buddy VARCHAR(40) DEFAULT NULL
        COMMENT 'Chosen buddy/character slug (e.g. joy, sadness, anger)'
        AFTER character_id;

-- Backfill existing rows from character_id if available
-- (maps character_id 1-6 to the buddy slugs used in JS)
UPDATE children
SET buddy = CASE character_id
    WHEN 1 THEN 'joy'
    WHEN 2 THEN 'anger'
    WHEN 3 THEN 'anxiety'
    WHEN 4 THEN 'fear'
    WHEN 5 THEN 'sadness'
    WHEN 6 THEN 'disgust'
    ELSE NULL
END
WHERE buddy IS NULL AND character_id IS NOT NULL;

SELECT '✅ buddy column added and backfilled' AS message;
SELECT child_id, nickname, character_id, buddy FROM children;
CREATE TABLE IF NOT EXISTS game_events (
    event_id   BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    `signal`   ENUM(
        'reaction',
        'error',
        'success',
        'abandon',
        'pace_change',
        'retry',
        'hint',
        'timeout'
    ) NOT NULL,
    value      FLOAT  NULL,
    ts         BIGINT NOT NULL,

    INDEX idx_events_session_signal_ts (session_id, `signal`, ts),
    INDEX idx_events_session_ts        (session_id, ts)

    
) ENGINE=InnoDB;

CREATE TABLE game_behaviors (
    session_id      INT AUTO_INCREMENT PRIMARY KEY,
    child_id        INT NOT NULL,
    game_id         INT NOT NULL,
    start_time      DATETIME NOT NULL,
    end_time        DATETIME DEFAULT NULL,
    difficulty      VARCHAR(20) NOT NULL DEFAULT 'easy',
    points          INT DEFAULT NULL,
    completion_time INT DEFAULT NULL,
    signals         JSON NOT NULL,
    raw_signals     JSON NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_child_game (child_id, game_id),
    INDEX idx_session  (session_id),

    CONSTRAINT fk_behaviors_child
        FOREIGN KEY (child_id) REFERENCES children(child_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_behaviors_game
        FOREIGN KEY (game_id) REFERENCES games(game_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS child_behavior_analysis (
	analysis_id         INT AUTO_INCREMENT PRIMARY KEY,
	child_id            INT NOT NULL,
	period_start        DATETIME NOT NULL,
	period_end          DATETIME NOT NULL,
	session_count       INT NOT NULL DEFAULT 0,
	focus_points        DECIMAL(10, 2) NOT NULL DEFAULT 0,
	frustration_points  DECIMAL(10, 2) NOT NULL DEFAULT 0,
	boredom_points      DECIMAL(10, 2) NOT NULL DEFAULT 0,
	joy_points          DECIMAL(10, 2) NOT NULL DEFAULT 0,
	dominant_state      VARCHAR(40) NOT NULL,
	confidence          DECIMAL(5, 2) NOT NULL DEFAULT 0,
	details_json        JSON NOT NULL,
	source_weights      JSON NOT NULL,
	created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

	UNIQUE KEY uniq_child_period (child_id, period_start, period_end),
	INDEX idx_child_period_end (child_id, period_end),
	INDEX idx_analysis_created (created_at),

	CONSTRAINT fk_analysis_child
		FOREIGN KEY (child_id) REFERENCES children(child_id)
		ON DELETE CASCADE
) ENGINE=InnoDB;
