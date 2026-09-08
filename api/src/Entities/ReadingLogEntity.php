<?php

declare(strict_types=1);

namespace Libringo\Entities;

use Libringo\Utils\SnowflakeId;

/** Acceso a datos de la tabla `reading_logs`. */
class ReadingLogEntity {
    public static function countTotalDaysRead(\PDO $db, string $userId): int {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM reading_logs WHERE user_id = ? AND is_frozen_day = 0");
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

    /** Historial de lectura (con protectores de racha usados), para el tracker semanal. */
    public static function fetchHistoryDates(\PDO $db, string $userId, string $today, int $days): array {
        $stmt = $db->prepare("
            SELECT read_date, is_frozen_day FROM reading_logs
            WHERE user_id = ? AND read_date >= DATE_SUB(?, INTERVAL $days DAY)
            ORDER BY read_date DESC
        ");
        $stmt->execute([$userId, $today]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['is_frozen_day'] = (bool)$row['is_frozen_day'];
        }
        return $rows;
    }

    /** Dias del mes con su fecha y si fueron cubiertos por un protector de racha, listos para la API. */
    public static function fetchCalendarDates(\PDO $db, string $userId, string $monthStart): array {
        $stmt = $db->prepare("
            SELECT read_date, is_frozen_day FROM reading_logs
            WHERE user_id = ?
              AND read_date >= ?
              AND read_date < DATE_ADD(?, INTERVAL 1 MONTH)
        ");
        $stmt->execute([$userId, $monthStart, $monthStart]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['is_frozen_day'] = (bool)$row['is_frozen_day'];
        }
        return $rows;
    }

    public static function upsertLog(\PDO $db, string $logId, string $userId, string $readDate, ?string $reaction): void {
        $stmt = $db->prepare("
            INSERT INTO reading_logs (id, user_id, read_date, reaction)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$logId, $userId, $readDate, $reaction]);
    }

    /** Registra los dias salteados que un protector de racha cubrio, para pintarlos en el calendario. */
    public static function insertFrozenDays(\PDO $db, string $userId, array $dates): void {
        $stmt = $db->prepare("
            INSERT INTO reading_logs (id, user_id, read_date, is_frozen_day)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE is_frozen_day = 1
        ");
        foreach ($dates as $date) {
            $stmt->execute([(string)SnowflakeId::nextId(), $userId, $date]);
        }
    }
}
