-- Rate limiting generico por ventana fija. Ver Utils/RateLimiter y
-- Entities/RateLimitEntity.

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket VARCHAR(50) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    window_start TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hits INT NOT NULL DEFAULT 1,
    PRIMARY KEY (bucket, subject)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
