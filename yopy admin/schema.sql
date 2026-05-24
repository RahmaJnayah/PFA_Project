-- ═══════════════════════════════════════════════════════════════
--  YOPY — Database Schema
--  Run this once to initialise all required tables.
--  Engine: MySQL 8+ / MariaDB 10.4+
-- ═══════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────
-- 1. users  (parent accounts + admin accounts)
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120)    NOT NULL,
  `email`         VARCHAR(180)    NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `pin_hash`      VARCHAR(255)        NULL COMMENT '4-digit PIN for profile switcher',
  `role`          ENUM('parent','admin') NOT NULL DEFAULT 'parent',
  `plan`          ENUM('free','premium') NOT NULL DEFAULT 'free',
  `status`        ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────
-- 2. characters  (onboarding companion buddies)
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `characters` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80)   NOT NULL,
  `image`       VARCHAR(255)      NULL COMMENT 'Relative path or filename, e.g. joy.png',
  `trait`       VARCHAR(120)  NOT NULL,
  `tagline`     VARCHAR(255)  NOT NULL,
  `color`       CHAR(7)       NOT NULL DEFAULT '#9B59B6' COMMENT 'Hex accent colour',
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────
-- 3. children  (child profiles linked to a parent)
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `children` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL COMMENT 'FK → users.id',
  `character_id` INT UNSIGNED     NULL COMMENT 'FK → characters.id',
  `name`         VARCHAR(80)  NOT NULL,
  `emoji`        VARCHAR(8)   NOT NULL DEFAULT '🦊',
  `theme`        VARCHAR(40)  NOT NULL DEFAULT 'theme-rose',
  `age`          TINYINT UNSIGNED NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_children_user`
    FOREIGN KEY (`user_id`)      REFERENCES `users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_children_char`
    FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────
-- 4. Seed data — default characters from onboarding
-- ─────────────────────────────────────────────────
INSERT IGNORE INTO `characters` (`character_id`, `name`, `avatar_image`, `trait`, `tagline`, `color`, `is_active`) VALUES
  (1, 'Joyla',  'joy.png',   'Full of Sunshine',    'Let''s make every moment magical! ✨',           '#FFD700', 1),
  (2, 'Sparko', 'langer.png','Fierce & Fearless',   'Standing up for what''s right — always! 🔥',    '#FF4422', 1),
  (3, 'Ticky',  'lemo.png',  'Alert & Thoughtful',  'Being careful keeps us safe and smart! 🌀',      '#FF9500', 1),
  (4, 'Binky',  'lilo.png',  'Brave at Heart',      'Even when scared, we grow stronger! 💜',         '#9B59B6', 1),
  (5, 'Bluey',  'cry.png',   'Deep & Caring',       'Feelings matter — all of them! 💙',              '#4FC3F7', 1),
  (6, 'Poppi',  'anx.png',   'Wildly Creative',     'When things get crazy — dance! 🎉',              '#FF6B6B', 1);

-- ─────────────────────────────────────────────────
-- 5. Seed data — default admin user
--    Password: yopy2025!   (change immediately in production)
--    Generate a new hash:  password_hash('yopy2025!', PASSWORD_BCRYPT)
-- ─────────────────────────────────────────────────
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `plan`, `status`) VALUES
  (1, 'YOPY Admin', 'admin@yopy.app',
   '$2y$12$placeholder_change_this_before_going_live_xxxxxx',
   'admin', 'premium', 'active');
