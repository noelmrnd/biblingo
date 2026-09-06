-- Permite reintentar eventos de dominio fallidos (push de FCM, etc.) en vez de
-- dejarlos en status='failed' para siempre. DomainEventStore::markAsFailed
-- vuelve a poner el evento en 'pending' mientras retry_count no llegue al tope.

ALTER TABLE domain_events
    ADD COLUMN retry_count INT NOT NULL DEFAULT 0 AFTER error_message;
