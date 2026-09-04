<?php

declare(strict_types=1);

final class SnowflakeException extends Exception
{
    private function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }

    public static function invalidId(int $value): self
    {
        return new self(
            sprintf('Invalid value: %d', $value),
            100
        );
    }

    public static function invalidTimestamp(int $value): self
    {
        return new self(
            sprintf('The starttime cannot be greater than current time and the maximum time difference is %d', $value),
            200
        );
    }
}