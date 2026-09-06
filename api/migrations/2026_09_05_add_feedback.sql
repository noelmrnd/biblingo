CREATE TABLE IF NOT EXISTS feedback (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    type ENUM('idea', 'bug', 'other') NOT NULL DEFAULT 'other',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
