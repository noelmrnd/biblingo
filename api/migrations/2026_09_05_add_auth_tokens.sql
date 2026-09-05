-- Tabla de tokens de sesion: hasta ahora ningun endpoint validaba quien hacia
-- la peticion, cualquier user_id enviado por el cliente se aceptaba tal cual.
-- Los tokens emitidos en el login se guardan hasheados (sha256) y se validan
-- via Authorization: Bearer <token> en cada request (ver src/Utils/Auth.php).

CREATE TABLE IF NOT EXISTS auth_tokens (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
