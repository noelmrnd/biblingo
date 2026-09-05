<?php

declare(strict_types=1);

namespace Biblingo\Services;

class DomainEventProcessor {
    /**
     * Procesa una tanda de eventos pendientes.
     * Despacha las notificaciones push correspondientes de manera asíncrona.
     */
    public static function processPending(int $limit = 50): array {
        $events = DomainEventStore::getPendingEvents($limit);
        $processed = 0;
        $failed = 0;

        foreach ($events as $eventRow) {
            $eventId   = $eventRow['id'];
            $eventName = $eventRow['event_name'];
            $payload   = json_decode($eventRow['payload'] ?? '{}', true) ?: [];

            try {
                self::handleEvent($eventName, $payload);
                DomainEventStore::markAsProcessed($eventId);
                $processed++;
            } catch (\Throwable $e) {
                DomainEventStore::markAsFailed($eventId, $e->getMessage());
                $failed++;
            }
        }

        return [
            'total'     => count($events),
            'processed' => $processed,
            'failed'    => $failed
        ];
    }

    /**
     * Enruta el evento a su manejador específico.
     */
    private static function handleEvent(string $eventName, array $payload): void {
        switch ($eventName) {
            case 'FriendAdded':
            case 'FriendRequestAccepted':
                self::handleFriendAdded($payload);
                break;

            case 'FriendRequestSent':
                self::handleFriendRequestSent($payload);
                break;

            case 'FriendNudged':
                self::handleFriendNudged($payload);
                break;

            default:
                // Si es un evento no reconocido o no requiere acción externa, se ignora o registra
                error_log("DomainEventProcessor: Evento no manejado: {$eventName}");
                break;
        }
    }

    private static function handleFriendAdded(array $payload): void {
        $receiverId = $payload['receiver_id'] ?? null;
        $title      = $payload['notification_title'] ?? '¡Nuevo Amigo en Biblingo! 🎉';
        $body       = $payload['notification_body'] ?? 'Alguien te ha agregado a sus amigos.';
        $data       = $payload['notification_data'] ?? ['type' => 'friend_added'];

        if ($receiverId !== null) {
            FCMService::sendPushNotificationToUser($receiverId, $title, $body, $data);
        }
    }

    private static function handleFriendNudged(array $payload): void {
        $receiverId = $payload['receiver_id'] ?? null;
        $title      = $payload['notification_title'] ?? '📖 Recordatorio de lectura';
        $body       = $payload['notification_body'] ?? '¡Tienes un recordatorio de lectura!';
        $data       = $payload['notification_data'] ?? ['type' => 'nudge'];

        if ($receiverId !== null) {
            FCMService::sendPushNotificationToUser($receiverId, $title, $body, $data);
        }
    }

    private static function handleFriendRequestSent(array $payload): void {
        $receiverId = $payload['receiver_id'] ?? null;
        $title      = $payload['notification_title'] ?? '¡Solicitud de amistad! 👥';
        $body       = $payload['notification_body'] ?? 'Alguien te ha enviado una solicitud de amistad.';
        $data       = $payload['notification_data'] ?? ['type' => 'friend_request'];

        if ($receiverId !== null) {
            FCMService::sendPushNotificationToUser($receiverId, $title, $body, $data);
        }
    }
}
