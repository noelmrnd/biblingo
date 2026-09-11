-- Numero de paginas/capitulos leidos ese dia (control de lectura), y a que libro
-- pertenecen. Nullable: una lectura sin libro activo configurado sigue sin
-- registrar ningun avance, solo la racha (como siempre).

ALTER TABLE reading_logs
    ADD COLUMN units_read INT NULL AFTER reaction,
    ADD COLUMN book_id BIGINT NULL AFTER units_read,
    ADD FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL;
