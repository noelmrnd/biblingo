<?php

declare(strict_types=1);

namespace Libringo\Entities;

use Libringo\Utils\SnowflakeId;

/** Acceso a datos de la tabla `reading_logs`. */
class ReadingLogEntity {
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

    /**
     * INSERT puro (no upsert): solo se llama cuando !alreadyLoggedToday (garantizado
     * por el llamador, ReadingController::logReading), asi que no deberia existir
     * fila previa para ese user_id+read_date. Si igual existe (invariante roto en
     * algun otro lado), MySQL tira duplicate-key y el catch generico lo delata como
     * 500 en vez de tapar el bug con un silencioso "reemplazar lo que habia".
     */
    public static function insertLog(
        \PDO $db,
        string $logId,
        string $userId,
        string $readDate,
        ?string $reaction,
        ?int $unitsRead = null,
        ?string $bookId = null
    ): void {
        $stmt = $db->prepare("
            INSERT INTO reading_logs (id, user_id, read_date, reaction, units_read, book_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$logId, $userId, $readDate, $reaction, $unitsRead, $bookId]);
    }

    /**
     * UPDATE puro (no upsert): "avance extra" del dia, solo se llama DESPUES de que
     * el usuario ya marco su lectura de hoy (garantizado por el llamador,
     * BookController::updateProgress), asi que la fila de hoy ya debe existir. Si no
     * existe (invariante roto — se llamo antes de logReading), tira RuntimeException
     * en vez de crearla silenciosamente sin reaccion.
     */
    public static function incrementUnitsRead(\PDO $db, string $userId, string $readDate, int $unitsRead, string $bookId): void {
        // Chequeo de existencia aparte del UPDATE: con unitsRead=0 (ej. re-marcar
        // capitulos ya leidos) el UPDATE no cambia ninguna columna y MySQL reporta
        // rowCount()=0 igual que "no existe la fila" — confiar en rowCount() aca
        // daria falsos positivos.
        $existsStmt = $db->prepare("SELECT 1 FROM reading_logs WHERE user_id = ? AND read_date = ? LIMIT 1");
        $existsStmt->execute([$userId, $readDate]);
        if (!$existsStmt->fetch()) {
            throw new \RuntimeException('No existe un registro de lectura de hoy para sumar el avance.');
        }

        $stmt = $db->prepare("
            UPDATE reading_logs
            SET units_read = COALESCE(units_read, 0) + ?, book_id = ?
            WHERE user_id = ? AND read_date = ?
        ");
        $stmt->execute([$unitsRead, $bookId, $userId, $readDate]);
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
