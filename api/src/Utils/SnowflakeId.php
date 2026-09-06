<?php

declare(strict_types=1);

namespace Biblingo\Utils;

/**
 * Generador de IDs tipo Snowflake (64 bits: 41 timestamp + 5 datacenter + 5 worker + 12 sequence).
 * Unico punto de uso en el proyecto es SnowflakeId::nextId(); se simplifico a esa
 * unica API publica, sin parse/getters/generate() con overrides que nadie usaba.
 *
 * @link https://www.callicoder.com/distributed-unique-id-sequence-number-generator
 */
class SnowflakeId
{
    private const MAX_DATACENTER_LENGTH = 5;
    private const MAX_WORKER_LENGTH = 5;
    private const MAX_SEQUENCE_LENGTH = 12;
    private const START_TIME = '2022-01-01 00:00:00';

    private static ?int $datacenter = null;
    private static ?int $worker = null;
    private static int $lastTimestamp = -1;
    private static int $sequence = 0;

    public static function nextId(): int
    {
        self::init();

        $currentTime = self::currentMillis();
        $sequence = self::sequence($currentTime);
        while ($sequence > (-1 ^ (-1 << self::MAX_SEQUENCE_LENGTH))) {
            usleep(1);
            $currentTime = self::currentMillis();
            $sequence = self::sequence($currentTime);
        }

        $timestamp = $currentTime - self::startTimestamp();

        $workerLeftMoveLength = self::MAX_SEQUENCE_LENGTH;
        $datacenterLeftMoveLength = self::MAX_WORKER_LENGTH + $workerLeftMoveLength;
        $timestampLeftMoveLength = self::MAX_DATACENTER_LENGTH + $datacenterLeftMoveLength;

        return ($timestamp << $timestampLeftMoveLength)
            | (self::$datacenter << $datacenterLeftMoveLength)
            | (self::$worker << $workerLeftMoveLength)
            | $sequence;
    }

    public static function nextIdString(): string
    {
        return (string) self::nextId();
    }

    /**
     * Sin SNOWFLAKE_DATACENTER/WORKER en env (caso normal hoy), no usar mt_rand:
     * dos procesos con el mismo par (datacenter, worker) al azar y el mismo
     * milisegundo colisionarian. PID y hostname son deterministicos por proceso
     * vivo, asi que diferencian entre workers de PHP-FPM y entre contenedores
     * sin necesitar configuracion manual.
     */
    private static function init(): void
    {
        if (self::$datacenter !== null) {
            return;
        }

        self::$datacenter = isset($_ENV['SNOWFLAKE_DATACENTER'])
            ? abs((int)$_ENV['SNOWFLAKE_DATACENTER']) % 32
            : (crc32(gethostname() ?: 'biblingo') % 32);
        self::$worker = isset($_ENV['SNOWFLAKE_WORKER'])
            ? abs((int)$_ENV['SNOWFLAKE_WORKER']) % 32
            : (getmypid() % 32);
    }

    private static function sequence(int $currentTime): int
    {
        if (self::$lastTimestamp === $currentTime) {
            self::$sequence++;
        } else {
            self::$sequence = 0;
            self::$lastTimestamp = $currentTime;
        }

        return self::$sequence;
    }

    private static function currentMillis(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private static function startTimestamp(): int
    {
        return (int) strtotime(self::START_TIME) * 1000;
    }
}
