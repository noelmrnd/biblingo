-- Denormaliza el conteo de dias leidos (mismo patron que streak_count y
-- pages_read): se llama en casi cada carga de perfil (propio, de amigos, login)
-- y era un COUNT(*) sobre reading_logs cada vez. Se incrementa junto al INSERT
-- de reading_logs en ReadingLogEntity::insertLog (ver ReadingController::logReading).

ALTER TABLE users
    ADD COLUMN days_read INT NOT NULL DEFAULT 0 AFTER max_streak_count;

UPDATE users u
SET days_read = (
    SELECT COUNT(*) FROM reading_logs r
    WHERE r.user_id = u.id AND r.is_frozen_day = 0
);
