-- Agrega status 'processing' para poder reclamar eventos pendientes de forma
-- atomica (UPDATE ... LIMIT) antes de despacharlos, evitando que dos workers
-- concurrentes (dos replicas o un daemon solapado) procesen el mismo evento
-- dos veces y dupliquen notificaciones push.

ALTER TABLE domain_events
    MODIFY COLUMN status ENUM('pending', 'processing', 'processed', 'failed') DEFAULT 'pending';
