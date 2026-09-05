<?php

declare(strict_types=1);

namespace Biblingo\Utils;

class SnowflakeId
{
    private static ?SnowflakeGenerator $defaultGenerator = null;

    private int $timestamp;
    private int $sequence;
    private int $worker;
    private int $datacenter;
    private int $startTime;
    private int $value;

    public static function getGenerator(): SnowflakeGenerator
    {
        if (self::$defaultGenerator === null) {
            $dc = isset($_ENV['SNOWFLAKE_DATACENTER']) ? (int)$_ENV['SNOWFLAKE_DATACENTER'] : -1;
            $w  = isset($_ENV['SNOWFLAKE_WORKER']) ? (int)$_ENV['SNOWFLAKE_WORKER'] : -1;
            self::$defaultGenerator = new SnowflakeGenerator($dc, $w);
        }

        return self::$defaultGenerator;
    }

    public static function generate(?int $datacenter = null, ?int $worker = null): self
    {
        if ($datacenter !== null || $worker !== null) {
            $generator = new SnowflakeGenerator($datacenter ?? -1, $worker ?? -1);
            return $generator->generate();
        }

        return self::getGenerator()->generate();
    }

    public static function nextId(): int
    {
        return self::generate()->getValue();
    }

    public static function nextIdString(): string
    {
        return (string) self::nextId();
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function __construct(int $timestamp, int $sequence, int $worker, int $datacenter, int $startTime)
    {
        $this->timestamp = $timestamp;
        $this->sequence = $sequence;
        $this->worker = $worker;
        $this->datacenter = $datacenter;
        $this->startTime = $startTime;
        $this->value = SnowflakeGenerator::getValue($timestamp, $datacenter, $worker, $sequence);
    }

    /**
     * @throws SnowflakeException
     */
    public static function parse(int $id, ?int $startTime = null): self
    {
        $parts = SnowflakeGenerator::getParts($id);
        $startTime = $startTime ?? SnowflakeGenerator::getDefaultStartTimestamp();
        return new static($parts['timestamp'], $parts['sequence'], $parts['worker'], $parts['datacenter'], $startTime);
    }

    public function getDate(): \DateTimeImmutable
    {
        $ts = SnowflakeGenerator::getRealTimestamp($this->startTime, $this->timestamp);
        return (new \DateTimeImmutable())->setTimestamp($ts);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function getWorker(): int
    {
        return $this->worker;
    }

    public function getDatacenter(): int
    {
        return $this->datacenter;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }
}
