<?php

declare(strict_types=1);

namespace Biblingo\Events;

class FriendRequestSentEvent extends DomainEvent {
    private string $senderId;
    private string $receiverId;
    private string $senderDisplayName;

    public function __construct(
        string $senderId,
        string $receiverId,
        string $senderDisplayName,
        ?string $id = null,
        ?string $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->senderDisplayName = $senderDisplayName;
    }

    public function getEventName(): string {
        return 'FriendRequestSent';
    }

    public function getAggregateType(): string {
        return 'friend_request';
    }

    public function getAggregateId(): string {
        return "{$this->senderId}_{$this->receiverId}";
    }

    public function getSenderId(): string {
        return $this->senderId;
    }

    public function getReceiverId(): string {
        return $this->receiverId;
    }

    public function getSenderDisplayName(): string {
        return $this->senderDisplayName;
    }

    public function getPayload(): array {
        return [
            'sender_id'          => $this->senderId,
            'receiver_id'        => $this->receiverId,
            'sender_name'        => $this->senderDisplayName,
            'notification_title' => '¡Solicitud de amistad! 👥',
            'notification_body'  => "{$this->senderDisplayName} te ha enviado una solicitud de amistad. ¡Toca para responder!",
            'notification_data'  => [
                'type'      => 'friend_request',
                'sender_id' => $this->senderId,
            ]
        ];
    }
}
