<?php

declare(strict_types=1);

class DateUtils {
    /**
     * Valida y retorna un objeto DateTimeZone seguro.
     * Si la zona es nula, vacía o inválida, retorna UTC como fallback.
     */
    public static function getSafeDateTimeZone(?string $timezone): \DateTimeZone {
        try {
            if (!empty($timezone) && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
                return new \DateTimeZone($timezone);
            }
        } catch (\Throwable $e) {}
        return new \DateTimeZone('UTC');
    }

    /**
     * Obtiene la fecha 'Y-m-d' de "hoy" en la zona horaria dada.
     */
    public static function getUserToday(?string $timezone): string {
        $tz = self::getSafeDateTimeZone($timezone);
        $dt = new \DateTime('now', $tz);
        return $dt->format('Y-m-d');
    }

    /**
     * Obtiene la fecha 'Y-m-d' de "ayer" en la zona horaria dada.
     */
    public static function getUserYesterday(?string $timezone): string {
        $tz = self::getSafeDateTimeZone($timezone);
        $dt = new \DateTime('now', $tz);
        $dt->modify('-1 day');
        return $dt->format('Y-m-d');
    }

    /**
     * Numero de dias entre dos fechas 'Y-m-d' (siempre positivo, sin importar el orden).
     */
    public static function daysBetween(string $dateA, string $dateB): int {
        $a = new \DateTime($dateA);
        $b = new \DateTime($dateB);
        return (int)$a->diff($b)->days;
    }

    /**
     * Etiqueta amigable ('Hoy', 'Ayer', 'Sin racha' o DD/MM/YYYY) para una fecha 'Y-m-d',
     * comparada contra el hoy/ayer del dueño de esa fecha (no del viewer).
     */
    public static function formatReadDateLabel(?string $readDate, string $today, string $yesterday): string {
        if (empty($readDate)) {
            return 'Sin racha';
        }
        if ($readDate === $today) {
            return 'Hoy';
        }
        if ($readDate === $yesterday) {
            return 'Ayer';
        }
        return implode('/', array_reverse(explode('-', $readDate)));
    }
}
