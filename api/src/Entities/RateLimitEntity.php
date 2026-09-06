<?php

declare(strict_types=1);

namespace Biblingo\Entities;

/** Acceso a datos de la tabla `rate_limits`. */
class RateLimitEntity {
    /**
     * Suma un hit a la ventana vigente de (bucket, subject); si la ventana anterior
     * ya expiro, la reinicia en 1. Atomico via INSERT ... ON DUPLICATE KEY UPDATE
     * (fila bloqueada durante el UPDATE, sin condicion de carrera entre requests).
     * Devuelve el conteo de hits ya actualizado para esta ventana.
     */
    public static function hit(\PDO $db, string $bucket, string $subject, int $windowSeconds): int {
        $stmt = $db->prepare("
            INSERT INTO rate_limits (bucket, subject, window_start, hits)
            VALUES (?, ?, NOW(), 1)
            ON DUPLICATE KEY UPDATE
                hits = IF(window_start < DATE_SUB(NOW(), INTERVAL ? SECOND), 1, hits + 1),
                window_start = IF(window_start < DATE_SUB(NOW(), INTERVAL ? SECOND), NOW(), window_start)
        ");
        $stmt->execute([$bucket, $subject, $windowSeconds, $windowSeconds]);

        $select = $db->prepare("SELECT hits FROM rate_limits WHERE bucket = ? AND subject = ?");
        $select->execute([$bucket, $subject]);
        return (int)($select->fetch()['hits'] ?? 0);
    }
}
