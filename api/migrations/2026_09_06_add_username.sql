-- Reemplaza el codigo de invitacion aleatorio por un username elegible/compartible.
ALTER TABLE users ADD COLUMN username VARCHAR(30) NULL UNIQUE AFTER display_name;
