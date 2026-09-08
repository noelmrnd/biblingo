-- Marca dias saltados que un protector de racha cubrio, para poder pintarlos
-- distinto (🧊) en el calendario mensual. Fila normal en reading_logs, sin
-- lectura real (reaction NULL), solo con is_frozen_day = TRUE.

ALTER TABLE reading_logs
    ADD COLUMN is_frozen_day BOOLEAN NOT NULL DEFAULT FALSE AFTER reaction;
