-- Protectores de racha: cubren automaticamente 1 dia salteado sin perder la racha.
-- Usuarios nuevos arrancan con 1 disponible. streak_freezes_used es historico,
-- nunca se decrementa (metrica de cuantos se han consumido en total).

ALTER TABLE users
    ADD COLUMN streak_freezes INT NOT NULL DEFAULT 1 AFTER max_streak_count,
    ADD COLUMN streak_freezes_used INT NOT NULL DEFAULT 0 AFTER streak_freezes;
