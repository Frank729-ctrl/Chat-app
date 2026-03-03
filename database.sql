-- ============================================================
-- ChatConnect Database Schema
-- ============================================================
-- STEP 1: Run this file:    mysql -u root -p < database.sql
-- STEP 2: Run setup script: php setup_demo_passwords.php
-- ============================================================

DROP DATABASE IF EXISTS chatconnect;
CREATE DATABASE chatconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chatconnect;

-- ─── USERS ───────────────────────────────────────────────────
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(191)  NOT NULL UNIQUE,
    password      VARCHAR(255)  NOT NULL,
    profile_image VARCHAR(500)  DEFAULT NULL,
    is_online     TINYINT(1)    NOT NULL DEFAULT 0,
    last_seen     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── CONVERSATIONS ───────────────────────────────────────────
CREATE TABLE conversations (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150)  DEFAULT NULL,
    is_group   TINYINT(1)    NOT NULL DEFAULT 0,
    created_by INT UNSIGNED  DEFAULT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── PARTICIPANTS ─────────────────────────────────────────────
CREATE TABLE participants (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    joined_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conv_user (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)         REFERENCES users(id)         ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── MESSAGES ────────────────────────────────────────────────
CREATE TABLE messages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED  NOT NULL,
    sender_id       INT UNSIGNED  NOT NULL,
    content         TEXT          NOT NULL,
    is_read         TINYINT(1)    NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conv_time (conversation_id, created_at),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id)       REFERENCES users(id)         ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── DEMO USERS (passwords set by setup_demo_passwords.php) ──
INSERT INTO users (full_name, email, password, is_online) VALUES
('Demo User',  'demo@chatconnect.app', 'TEMP', 0),
('Jane Smith', 'jane@chatconnect.app', 'TEMP', 0),
('Bob Johnson','bob@chatconnect.app',  'TEMP', 0);

-- Demo conversation between Demo User (id=1) and Jane (id=2)
INSERT INTO conversations (id, is_group, created_by) VALUES (1, 0, 1);
INSERT INTO participants (conversation_id, user_id) VALUES (1, 1), (1, 2);
INSERT INTO messages (conversation_id, sender_id, content) VALUES
(1, 2, 'Hey! Welcome to ChatConnect 👋'),
(1, 1, 'Thanks! Looks great so far.'),
(1, 2, 'Feel free to send me a message anytime!');