<?php

declare(strict_types=1);

namespace Biblingo\Entities;

/** Acceso a datos de la tabla `reading_logs`. */
class ReadingLogEntity {
    public static function countTotalDaysRead(\PDO $db, string $userId): int {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM reading_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    /** Conteo de reacciones registradas por dia de lectura, ej. {"loved": 12, "peaceful": 5}. */
    public static function countReactionsGrouped(\PDO $db, string $userId): array {
        $stmt = $db->prepare("
            SELECT reaction, COUNT(*) AS total
            FROM reading_logs
            WHERE user_id = ? AND reaction IS NOT NULL
            GROUP BY reaction
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Historial de lectura, usado para el tracker semanal/mensual. */
    public static function fetchHistoryDates(\PDO $db, string $userId, string $today, int $days): array {
        $stmt = $db->prepare("
            SELECT read_date FROM reading_logs
            WHERE user_id = ? AND read_date >= DATE_SUB(?, INTERVAL {$days} DAY)
            ORDER BY read_date DESC
        ");
        $stmt->execute([$userId, $today]);
        return array_column($stmt->fetchAll(), 'read_date');
    }

    public static function fetchCalendarDates(\PDO $db, string $userId, string $monthStart): array {
        $stmt = $db->prepare("
            SELECT read_date FROM reading_logs
            WHERE user_id = ?
              AND read_date >= ?
              AND read_date < DATE_ADD(?, INTERVAL 1 MONTH)
        ");
        $stmt->execute([$userId, $monthStart, $monthStart]);
        return array_column($stmt->fetchAll(), 'read_date');
    }

    public static function upsertLog(\PDO $db, string $logId, string $userId, string $readDate, ?string $reaction): void {
        $stmt = $db->prepare("
            INSERT INTO reading_logs (id, user_id, read_date, reaction)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$logId, $userId, $readDate, $reaction]);
    }
}
