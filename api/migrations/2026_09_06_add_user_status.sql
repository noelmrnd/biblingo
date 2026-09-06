-- Estatus de cuenta: permite bloquear (banned) o marcar como borrada (deleted)
-- sin ejecutar un DELETE fisico. "deleted" se setea manualmente cuando el
-- usuario solicita eliminar su cuenta (ver UserController::deleteAccount).
-- Los tokens de sesion y el login dejan de funcionar para cualquier estado
-- distinto de "active" (ver Auth::authenticate y AuthController).

ALTER TABLE users
    ADD COLUMN status ENUM('active', 'banned', 'deleted') NOT NULL DEFAULT 'active' AFTER platform;
