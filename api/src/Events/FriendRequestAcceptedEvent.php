<?php

declare(strict_types=1);

require_once __DIR__ . '/DomainEvent.php';

class FriendRequestAcceptedEvent extends DomainEvent {
    private string $acceptorId;
    private string $requesterId;
    private string $acceptorDisplayName;

    public function __construct(
        string $acceptorId,
        string $requesterId,
        string $acceptorDisplayName,
        ?string $id = null,
        ?string $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
        $this->acceptorId = $acceptorId;
        $this->requesterId = $requesterId;
        $this->acceptorDisplayName = $acceptorDisplayName;
    }

    public function getEventName(): string {
        return 'FriendRequestAccepted';
    }

    public function getAggregateType(): string {
        return 'friendship';
    }

    public function getAggregateId(): string {
        return "{$this->acceptorId}_{$this->requesterId}";
    }

    public function getAcceptorId(): string {
        return $this->acceptorId;
    }

    public function getRequesterId(): string {
        return $this->requesterId;
    }

    public function getAcceptorDisplayName(): string {
        return $this->acceptorDisplayName;
    }

    public function getPayload(): array {
        return [
            'sender_id'          => $this->acceptorId,
            'receiver_id'        => $this->requesterId,
            'sender_name'        => $this->acceptorDisplayName,
            'notification_title' => '¡Solicitud de amistad aceptada! 🎉',
            'notification_body'  => "{$this->acceptorDisplayName} ha aceptado tu solicitud de amistad. ¡Ya compiten en el ranking!",
            'notification_data'  => [
                'type'    => 'friend_added',
                'user_id' => $this->acceptorId,
            ]
        ];
    }
}
