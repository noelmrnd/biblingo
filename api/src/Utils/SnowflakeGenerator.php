<?php

declare(strict_types=1);

namespace Biblingo\Utils;

/**
 * @link https://www.callicoder.com/distributed-unique-id-sequence-number-generator
 * @link https://github.com/godruoyi/php-snowflake/blob/master/src/Snowflake.php
 */
class SnowflakeGenerator
{
    const MAX_TIMESTAMP_LENGTH = 41;
    const MAX_DATACENTER_LENGTH = 5;
    const MAX_WORKER_LENGTH = 5;
    const MAX_SEQUENCE_LENGTH = 12;
    const MAX_FIRST_LENGTH = 1;
    const START_TIME = '2022-01-01 00:00:00';
    private ?int $datacenter;
    private ?int $worker;
    private ?int $startTime;
    private int $lastTimeStamp = -1;
    private int $sequence = 0;

    public function __construct(int $datacenter = -1, int $worker = -1)
    {
        $maxDataCenter = -1 ^ (-1 << self::MAX_DATACENTER_LENGTH);
        $maxWorkId = -1 ^ (-1 << self::MAX_WORKER_LENGTH);

        // If not set datacenter or workid, we will set a default value to use.
        $this->datacenter = $datacenter > $maxDataCenter || $datacenter < 0 ? mt_rand(0, 31) : $datacenter;
        $this->worker = $worker > $maxWorkId || $worker < 0 ? mt_rand(0, 31) : $worker;
    }

    public function generate(): SnowflakeId
    {
        $currentTime = $this->getCurrentMicrotime();
        while (($sequence = $this->sequence($currentTime)) > (-1 ^ (-1 << self::MAX_SEQUENCE_LENGTH))) {
            usleep(1);
            $currentTime = $this->getCurrentMicrotime();
        }

        $startTime = $this->getStartTimeStamp();

        $timestamp = $currentTime - $startTime;

        return new SnowflakeId($timestamp, $sequence, $this->worker, $this->datacenter, $startTime);
    }

    public function getStartTimeStamp(): int
    {
        return $this->startTime ?? self::getDefaultStartTimestamp();
    }

    /**
     * @throws SnowflakeException
     */
    public function setStartTimeStamp(int $startTime): self
    {
        $missTime = $this->getCurrentMicrotime() - $startTime;
        $maxTimeDiff = ((1 << self::MAX_TIMESTAMP_LENGTH) - 1);
        if ($missTime < 0 || $missTime > $maxTimeDiff) {
            throw SnowflakeException::invalidTimestamp($maxTimeDiff);
        }

        $this->startTime = $startTime;

        return $this;
    }

    private function getCurrentMicrotime(): int
    {
        return floor(microtime(true) * 1000) | 0;
    }

    private function sequence(int $currentTime): int
    {
        if ($this->lastTimeStamp === $currentTime) {
            ++$this->sequence;
            $this->lastTimeStamp = $currentTime;

            return $this->sequence;
        }

        $this->sequence = 0;
        $this->lastTimeStamp = $currentTime;

        return 0;
    }

    public static function getDefaultStartTimestamp(): int
    {
        return strtotime(self::START_TIME) * 1000;
    }

    public static function isValid(int $id): bool
    {
        $idStr = decbin($id);

        if (strlen($idStr) < self::MAX_TIMESTAMP_LENGTH) {
            return false;
        }

        return true;
    }

    /**
     * @throws SnowflakeException
     */
    public static function getParts(int $id): array
    {
        if (!self::isValid($id)) {
            throw SnowflakeException::invalidId($id);
        }

        $idStr = decbin($id);

        return [
            'timestamp' => bindec(substr($idStr, 0, -22)),
            'sequence' => bindec(substr($idStr, -12)),
            'worker' => bindec(substr($idStr, -17, 5)),
            'datacenter' => bindec(substr($idStr, -22, 5)),
        ];
    }

    public static function getRealTimestamp(int $startTime, int $timestamp): int
    {
        return ($startTime / 1000) + (int)($timestamp / 1000);
    }

    public static function getValue(int $timestamp, int $datacenter, int $worker, int $sequence): int
    {
        $workerLeftMoveLength = self::MAX_SEQUENCE_LENGTH;
        $datacenterLeftMoveLength = self::MAX_WORKER_LENGTH + $workerLeftMoveLength;
        $timestampLeftMoveLength = self::MAX_DATACENTER_LENGTH + $datacenterLeftMoveLength;

        return ($timestamp << $timestampLeftMoveLength)
            | ($datacenter << $datacenterLeftMoveLength)
            | ($worker << $workerLeftMoveLength)
            | ($sequence);
    }

    public function getDatacenter(): ?int
    {
        return $this->datacenter;
    }

    public function getWorker(): ?int
    {
        return $this->worker;
    }

    public function getStartTime(): ?int
    {
        return $this->startTime;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }
}
