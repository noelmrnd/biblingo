-- Contador historico de paginas/capitulos leidos (control de lectura) y las
-- 2 opciones de privacidad de perfil que lo acompañan.

ALTER TABLE users
    ADD COLUMN pages_read INT NOT NULL DEFAULT 0 AFTER max_streak_count,
    ADD COLUMN show_current_book BOOLEAN NOT NULL DEFAULT TRUE AFTER notification_prefs,
    ADD COLUMN show_reading_progress BOOLEAN NOT NULL DEFAULT TRUE AFTER show_current_book;
