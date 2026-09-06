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
     * Obtiene una lista de eventos pendientes ordenados cronológicamente. Incluye
     * eventos que fallaron pero todavia tienen reintentos disponibles (ver
     * markAsFailed) — status='failed' definitivo solo se alcanza tras agotarlos.
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

    private const MAX_RETRIES = 5;

    /**
     * Registra el fallo e incrementa retry_count. Mientras queden reintentos
     * disponibles vuelve a 'pending' para que el worker lo tome de nuevo en la
     * siguiente pasada; agotados los reintentos queda en 'failed' definitivo.
     */
    public static function markAsFailed(string $eventId, string $errorMessage, ?\PDO $pdo = null): void {
        $db = $pdo ?? getDbConnection();

        $stmt = $db->prepare("
            UPDATE domain_events
            SET retry_count = retry_count + 1,
                status = IF(retry_count + 1 < ?, 'pending', 'failed'),
                processed_at = CURRENT_TIMESTAMP,
                error_message = ?
            WHERE id = ?
        ");
        $stmt->execute([self::MAX_RETRIES, $errorMessage, $eventId]);
    }
}
