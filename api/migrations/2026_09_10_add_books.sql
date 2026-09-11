-- Libro actual del usuario (control de lectura). Solo 1 libro 'reading' por
-- usuario a la vez (validado en BookController, no a nivel SQL). tracking_mode
-- 'bitmask' se usa para libros de capitulos no lineales (ej. Biblia, 1189
-- capitulos): progress_bitmask guarda 1 bit por capitulo, current_unit no aplica.

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
