<?php

declare(strict_types=1);

namespace Biblingo\Utils;

/**
 * Estado de racha calculado en vivo a partir de columnas guardadas (streak_count,
 * last_read_date). El contador guardado en la fila solo se corrige/resetea cuando
 * el dueño vuelve a leer (en ReadingController::logReading) — no hay cron que lo
 * revise. Cualquier endpoint que MUESTRE una racha debe pasar por aqui para saber
 * si, aunque la columna aun no se haya actualizado, ya deberia verse como rota.
 */
class StreakUtils {
    public static function computeStatus(?string $lastReadDate, int $streakCount, ?string $timezone): StreakStatus {
        $today = DateUtils::getUserToday($timezone);
        $yesterday = DateUtils::getUserYesterday($timezone);
        $hasReadToday = $lastReadDate === $today;
        $isStreakLost = ($streakCount > 0 && $lastReadDate !== $today && $lastReadDate !== $yesterday);

        return new StreakStatus(
            $today,
            $yesterday,
            $hasReadToday,
            $isStreakLost,
            DateUtils::formatReadDateLabel($lastReadDate, $today, $yesterday),
        );
    }
}
