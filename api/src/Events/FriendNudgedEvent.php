<?php

declare(strict_types=1);

namespace Biblingo\Events;

class FriendNudgedEvent extends DomainEvent {
    private string $senderId;
    private string $receiverId;
    private string $senderDisplayName;
    private string $nudgeDate;

    public function __construct(
        string $senderId,
        string $receiverId,
        string $senderDisplayName,
        string $nudgeDate,
        ?string $id = null,
        ?string $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->senderDisplayName = $senderDisplayName;
        $this->nudgeDate = $nudgeDate;
    }

    public function getEventName(): string {
        return 'FriendNudged';
    }

    public function getAggregateType(): string {
        return 'friend_nudge';
    }

    public function getAggregateId(): string {
        return "{$this->senderId}_{$this->receiverId}_{$this->nudgeDate}";
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

    public function getNudgeDate(): string {
        return $this->nudgeDate;
    }

    public function getPayload(): array {
        return [
            'sender_id'          => $this->senderId,
            'receiver_id'        => $this->receiverId,
            'sender_name'        => $this->senderDisplayName,
            'nudge_date'         => $this->nudgeDate,
            'notification_title' => '📖 Recordatorio de lectura',
            'notification_body'  => "{$this->senderDisplayName} te ha enviado un recordatorio para que leas hoy y protejas tu racha. 🔥",
            'notification_data'  => [
                'type'      => 'nudge',
                'sender_id' => $this->senderId,
            ]
        ];
    }
}
