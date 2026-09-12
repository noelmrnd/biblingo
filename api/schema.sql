CREATE DATABASE IF NOT EXISTS reading_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reading_app;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS users (
    id BIGINT PRIMARY KEY,
    apple_id VARCHAR(255) NULL UNIQUE,
    google_id VARCHAR(255) NULL UNIQUE,
    email VARCHAR(255) NULL,
    display_name VARCHAR(100) NOT NULL,
    username VARCHAR(30) NOT NULL UNIQUE,
    streak_count INT DEFAULT 0,
    max_streak_count INT DEFAULT 0,
    days_read INT NOT NULL DEFAULT 0,
    streak_freezes INT NOT NULL DEFAULT 1,
    streak_freezes_used INT NOT NULL DEFAULT 0,
    last_read_date DATE NULL,
    reminder_time VARCHAR(10) DEFAULT '20:00',
    timezone VARCHAR(50) DEFAULT 'UTC',
    platform ENUM('ios', 'android', 'web') DEFAULT 'ios',
    status ENUM('active', 'banned', 'deleted') NOT NULL DEFAULT 'active',
    notification_prefs JSON NULL,
    pages_read INT NOT NULL DEFAULT 0,
    show_current_book BOOLEAN NOT NULL DEFAULT TRUE,
    show_reading_progress BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1b. Libro actual del usuario (control de lectura). Solo 1 libro 'reading' por
-- usuario a la vez (validado en BookController, no a nivel SQL). tracking_mode
-- 'bitmask' se usa para libros de capitulos no lineales (ej. Biblia, 1189
-- capitulos): progress_bitmask guarda 1 bit por capitulo, current_unit no aplica.
-- Definida antes de reading_logs porque esta la referencia via FK (book_id).
CREATE TABLE IF NOT EXISTS books (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    tracking_mode ENUM('linear', 'bitmask') NOT NULL DEFAULT 'linear',
    total_units INT NULL,
    current_unit INT NOT NULL DEFAULT 0,
    progress_bitmask BINARY(149) NULL,
    status ENUM('reading', 'finished', 'abandoned') NOT NULL DEFAULT 'reading',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_status (user_id, status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Registro diario de lectura
CREATE TABLE IF NOT EXISTS reading_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    read_date DATE NOT NULL,
    reaction VARCHAR(50) NULL,
    is_frozen_day BOOLEAN NOT NULL DEFAULT FALSE,
    units_read INT NULL,
    book_id BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_day (user_id, read_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Seguimientos (asimétrico, estilo Duolingo). "Amigos mutuos" = ambos se siguen.
CREATE TABLE IF NOT EXISTS follows (
    id BIGINT PRIMARY KEY,
    follower_id BIGINT NOT NULL,
    followed_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, followed_id),
    INDEX idx_followed (followed_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Registro de toques/recordatorios diarios entre amigos
CREATE TABLE IF NOT EXISTS friend_nudges (
    id BIGINT PRIMARY KEY,
    sender_id BIGINT NOT NULL,
    receiver_id BIGINT NOT NULL,
    nudge_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sender_receiver_date (sender_id, receiver_id, nudge_date),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4b. Medallas ganadas (racha, amigos, reacciones, etc). Catalogo cerrado y
-- estable en BadgeEntity::CATALOG / app/src/constants.js BADGES.
CREATE TABLE IF NOT EXISTS user_badges (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    badge_id VARCHAR(30) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tokens de Notificaciones Push Multidispositivo
CREATE TABLE IF NOT EXISTS user_push_tokens (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token VARCHAR(255) NOT NULL,
    platform ENUM('ios', 'android', 'web') DEFAULT 'ios',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_device_token (token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5b. Tokens de sesion emitidos tras un login verificado (Bearer auth)
CREATE TABLE IF NOT EXISTS auth_tokens (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5b2. Bloqueos entre usuarios. Bloquear quita cualquier follow existente en ambos
-- sentidos (ver BlockController::blockUser) y bloquea nuevos follows y la vista
-- del perfil mientras el bloqueo exista. reason es un selector opcional, no texto
-- libre (evita moderar contenido arbitrario en esta v1).
CREATE TABLE IF NOT EXISTS blocks (
    id BIGINT PRIMARY KEY,
    blocker_id BIGINT NOT NULL,
    blocked_id BIGINT NOT NULL,
    reason ENUM('spam', 'inappropriate_content', 'harassment', 'other') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_block (blocker_id, blocked_id),
    INDEX idx_blocked (blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5c. Sugerencias y reportes enviados desde el perfil
CREATE TABLE IF NOT EXISTS feedback (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    type ENUM('idea', 'bug', 'other') NOT NULL DEFAULT 'other',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Eventos de Dominio (Patrón Outbox para procesamiento desacoplado)
CREATE TABLE IF NOT EXISTS domain_events (
    id VARCHAR(36) PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    aggregate_type VARCHAR(50) NOT NULL,
    aggregate_id VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'processing', 'processed', 'failed') DEFAULT 'pending',
    occurred_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    retry_count INT NOT NULL DEFAULT 0,
    INDEX idx_status_occurred (status, occurred_on),
    INDEX idx_aggregate (aggregate_type, aggregate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

