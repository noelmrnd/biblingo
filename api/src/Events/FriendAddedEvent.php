<?php

declare(strict_types=1);

require_once __DIR__ . '/DomainEvent.php';

class FriendAddedEvent extends DomainEvent {
    private string $userId;
    private string $friendId;
    private string $userDisplayName;

    public function __construct(
        string $userId,
        string $friendId,
        string $userDisplayName,
        ?string $id = null,
        ?string $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
        $this->userId = $userId;
        $this->friendId = $friendId;
        $this->userDisplayName = $userDisplayName;
    }

    public function getEventName(): string {
        return 'FriendAdded';
    }

    public function getAggregateType(): string {
        return 'friendship';
    }

    public function getAggregateId(): string {
        return "{$this->userId}_{$this->friendId}";
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getFriendId(): string {
        return $this->friendId;
    }

    public function getUserDisplayName(): string {
        return $this->userDisplayName;
    }

    public function getPayload(): array {
        return [
            'sender_id'          => $this->userId,
            'receiver_id'        => $this->friendId,
            'sender_name'        => $this->userDisplayName,
            'notification_title' => '¡Nuevo Amigo en Biblingo! 🎉',
            'notification_body'  => "{$this->userDisplayName} te ha agregado a sus amigos. ¡Compite por la mejor racha!",
            'notification_data'  => [
                'type'    => 'friend_added',
                'user_id' => $this->userId,
            ]
        ];
    }
}
