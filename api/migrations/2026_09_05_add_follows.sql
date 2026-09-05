-- Reemplaza el modelo de amistad mutua (friendships/friend_requests) por seguimiento
-- asimetrico estilo Duolingo. Migra las amistades existentes como follows en ambas
-- direcciones (ya eran mutuas) y elimina las tablas viejas.

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

INSERT IGNORE INTO follows (id, follower_id, followed_id, created_at)
SELECT id, user_id, friend_id, created_at FROM friendships;

DROP TABLE IF EXISTS friend_requests;
DROP TABLE IF EXISTS friendships;
