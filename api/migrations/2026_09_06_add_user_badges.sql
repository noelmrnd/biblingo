-- Medallas persistentes por hitos (racha, amigos, reacciones, etc). Catalogo
-- cerrado y estable definido en BadgeEntity::CATALOG (backend) y BADGES
-- (app/src/constants.js), badge_id es un string fijo tipo 'streak_7'.

CREATE TABLE IF NOT EXISTS user_badges (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    badge_id VARCHAR(30) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
