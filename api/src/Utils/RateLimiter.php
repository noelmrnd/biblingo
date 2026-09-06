<?php

declare(strict_types=1);

namespace Biblingo\Utils;

use Biblingo\Entities\RateLimitEntity;

/** Limite simple de "maxHits por ventana de windowSeconds" por (bucket, subject). */
class RateLimiter {
    public static function allow(string $bucket, string $subject, int $windowSeconds, int $maxHits): bool {
        $hits = RateLimitEntity::hit(getDbConnection(), $bucket, $subject, $windowSeconds);
        return $hits <= $maxHits;
    }
}
