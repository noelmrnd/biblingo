CREATE DATABASE IF NOT EXISTS reading_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reading_app;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS users (
    id BIGINT PRIMARY KEY,
    apple_id VARCHAR(255) NULL UNIQUE,
    google_id VARCHAR(255) NULL UNIQUE,
    email VARCHAR(255) NULL,
    display_name VARCHAR(100) NOT NULL,
    invite_code VARCHAR(12) NOT NULL UNIQUE,
    streak_count INT DEFAULT 0,
    max_streak_count INT DEFAULT 0,
    streak_freezes INT NOT NULL DEFAULT 1,
    streak_freezes_used INT NOT NULL DEFAULT 0,
    last_read_date DATE NULL,
    reminder_time VARCHAR(10) DEFAULT '20:00',
    timezone VARCHAR(50) DEFAULT 'UTC',
    platform ENUM('ios', 'android', 'web') DEFAULT 'ios',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Registro diario de lectura
CREATE TABLE IF NOT EXISTS reading_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    read_date DATE NOT NULL,
    reaction VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_day (user_id, read_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Amistades (Relación bidireccional)
CREATE TABLE IF NOT EXISTS friendships (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    friend_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_friendship (user_id, friend_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Solicitudes de Amistad (Pendientes de aprobación)
CREATE TABLE IF NOT EXISTS friend_requests (
    id BIGINT PRIMARY KEY,
    sender_id BIGINT NOT NULL,
    receiver_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request (sender_id, receiver_id),
    INDEX idx_receiver (receiver_id),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
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

-- 6. Eventos de Dominio (Patrón Outbox para procesamiento desacoplado)
CREATE TABLE IF NOT EXISTS domain_events (
    id VARCHAR(36) PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    aggregate_type VARCHAR(50) NOT NULL,
    aggregate_id VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'processed', 'failed') DEFAULT 'pending',
    occurred_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    INDEX idx_status_occurred (status, occurred_on),
    INDEX idx_aggregate (aggregate_type, aggregate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

