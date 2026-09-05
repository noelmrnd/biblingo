<?php

declare(strict_types=1);

/**
 * Resultado de StreakUtils::computeStatus. Propiedades publicas de solo lectura
 * en vez de un array asociativo, para tener autocompletado y errores en tiempo
 * de analisis si se usa un nombre de campo que no existe.
 */
final readonly class StreakStatus {
    public function __construct(
        public string $today,
        public string $yesterday,
        public bool $hasReadToday,
        public bool $isStreakLost,
        public string $lastReadLabel,
    ) {}
}
