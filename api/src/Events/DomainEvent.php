<?php

declare(strict_types=1);

abstract class DomainEvent {
    protected string $id;
    protected string $occurredOn;

    public function __construct(?string $id = null, ?string $occurredOn = null) {
        $this->id = $id ?? self::generateUuid();
        $this->occurredOn = $occurredOn ?? gmdate('Y-m-d H:i:s');
    }

    abstract public function getEventName(): string;
    abstract public function getAggregateType(): string;
    abstract public function getAggregateId(): string;
    abstract public function getPayload(): array;

    public function getId(): string {
        return $this->id;
    }

    public function getOccurredOn(): string {
        return $this->occurredOn;
    }

    public function toArray(): array {
        return [
            'id'             => $this->getId(),
            'event_name'     => $this->getEventName(),
            'aggregate_type' => $this->getAggregateType(),
            'aggregate_id'   => $this->getAggregateId(),
            'payload'        => $this->getPayload(),
            'occurred_on'    => $this->getOccurredOn(),
        ];
    }

    public static function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // Version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // Variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
