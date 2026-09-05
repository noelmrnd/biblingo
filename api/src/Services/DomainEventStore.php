<?php

declare(strict_types=1);

namespace Biblingo\Services;

use Biblingo\Events\DomainEvent;

class DomainEventStore {
    /**
     * Guarda un evento de dominio en MySQL.
     * Puede recibir una conexión PDO existente para ejecutarse dentro de una transacción de base de datos.
     */
    public static function record(DomainEvent $event, ?\PDO $pdo = null): void {
        $db = $pdo ?? getDbConnection();

        $stmt = $db->prepare("
            INSERT INTO domain_events (id, event_name, aggregate_type, aggregate_id, payload, status, occurred_on)
            VALUES (?, ?, ?, ?, ?, 'pending', ?)
        ");

        $stmt->execute([
            $event->getId(),
            $event->getEventName(),
            $event->getAggregateType(),
            $event->getAggregateId(),
            json_encode($event->getPayload(), JSON_UNESCAPED_UNICODE),
            $event->getOccurredOn()
        ]);
    }

    /**
     * Obtiene una lista de eventos pendientes ordenados cronológicamente.
     */
    public static function getPendingEvents(int $limit = 50, ?\PDO $pdo = null): array {
        $db = $pdo ?? getDbConnection();

        $stmt = $db->prepare("
            SELECT id, event_name, aggregate_type, aggregate_id, payload, status, occurred_on
            FROM domain_events
            WHERE status = 'pending'
            ORDER BY occurred_on ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Marca un evento como procesado exitosamente.
     */
    public static function markAsProcessed(string $eventId, ?\PDO $pdo = null): void {
        $db = $pdo ?? getDbConnection();

        $stmt = $db->prepare("
            UPDATE domain_events
            SET status = 'processed', processed_at = CURRENT_TIMESTAMP, error_message = NULL
            WHERE id = ?
        ");
        $stmt->execute([$eventId]);
    }

    /**
     * Marca un evento como fallido y almacena el mensaje de error.
     */
    public static function markAsFailed(string $eventId, string $errorMessage, ?\PDO $pdo = null): void {
        $db = $pdo ?? getDbConnection();

        $stmt = $db->prepare("
            UPDATE domain_events
            SET status = 'failed', processed_at = CURRENT_TIMESTAMP, error_message = ?
            WHERE id = ?
        ");
        $stmt->execute([$errorMessage, $eventId]);
    }
}
