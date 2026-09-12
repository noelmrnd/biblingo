<?php

declare(strict_types=1);

namespace Libringo\Entities;

/** Acceso a datos de la tabla `books`. */
class BookEntity {
    public const MODE_LINEAR = 'linear';
    public const MODE_BITMASK = 'bitmask';

    public const BIBLE_TOTAL_CHAPTERS = 1189;
    public const BITMASK_BYTES = 149;

    public static function findById(\PDO $db, string $bookId): array|false {
        $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        return $stmt->fetch();
    }

    public static function findActiveByUser(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT * FROM books WHERE user_id = ? AND status = 'reading' LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /** Igual que findActiveByUser pero con FOR UPDATE, para el flujo de registrar progreso. */
    public static function findActiveByUserForUpdate(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT * FROM books WHERE user_id = ? AND status = 'reading' LIMIT 1 FOR UPDATE");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function countFinishedByUser(\PDO $db, string $userId): int {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM books WHERE user_id = ? AND status = 'finished'");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public static function markAbandoned(\PDO $db, string $bookId): void {
        $stmt = $db->prepare("UPDATE books SET status = 'abandoned' WHERE id = ?");
        $stmt->execute([$bookId]);
    }

    public static function create(
        \PDO $db,
        string $id,
        string $userId,
        string $title,
        string $trackingMode,
        ?int $totalUnits
    ): void {
        $stmt = $db->prepare(
            "INSERT INTO books (id, user_id, title, tracking_mode, total_units, current_unit, progress_bitmask)
             VALUES (?, ?, ?, ?, ?, 0, ?)"
        );
        $bitmask = $trackingMode === self::MODE_BITMASK
            ? str_repeat("\0", self::BITMASK_BYTES)
            : null;
        $stmt->execute([$id, $userId, $title, $trackingMode, $totalUnits, $bitmask]);
    }

    public static function updateLinearProgress(\PDO $db, string $bookId, int $currentUnit, string $status): void {
        $stmt = $db->prepare("UPDATE books SET current_unit = ?, status = ? WHERE id = ?");
        $stmt->execute([$currentUnit, $status, $bookId]);
    }

    public static function updateBitmask(\PDO $db, string $bookId, string $bitmask, string $status): void {
        $stmt = $db->prepare("UPDATE books SET progress_bitmask = ?, status = ? WHERE id = ?");
        $stmt->execute([$bitmask, $status, $bookId]);
    }

    /** Indices (1-based) de los capitulos ya marcados como leidos, para prellenar el front. */
    public static function decodeBitmaskChapters(?string $bitmask): array {
        if ($bitmask === null) return [];
        $bytes = unpack('C*', $bitmask);
        $chapters = [];
        foreach ($bytes as $byteIndex0 => $byte) {
            $byteIndex = $byteIndex0 - 1; // unpack('C*') es 1-indexed
            if ($byte === 0) continue;
            for ($bit = 0; $bit < 8; $bit++) {
                if ($byte & (1 << $bit)) {
                    $chapterNumber = $byteIndex * 8 + $bit + 1;
                    if ($chapterNumber <= self::BIBLE_TOTAL_CHAPTERS) {
                        $chapters[] = $chapterNumber;
                    }
                }
            }
        }
        sort($chapters);
        return $chapters;
    }

    public static function countSetBits(string $bitmask): int {
        return count(self::decodeBitmaskChapters($bitmask));
    }

    /** Unidades leidas: cuenta bits marcados en modo bitmask, o current_unit en modo lineal. */
    public static function getCurrentUnit(array $book): int {
        return $book['tracking_mode'] === self::MODE_BITMASK
            ? self::countSetBits($book['progress_bitmask'])
            : (int)$book['current_unit'];
    }

    /**
     * Aplica OR bit a bit: marca como leidos los capitulos nuevos sin tocar los
     * que ya estaban marcados (evita pisar progreso si dos dispositivos mandan
     * distintos capitulos casi al mismo tiempo). Devuelve [nuevoBitmask, cantidadDeCapitulosNuevos].
     */
    public static function orChapters(string $bitmask, array $chapterNumbers): array {
        $bytes = array_values(unpack('C*', $bitmask));
        $newCount = 0;
        foreach ($chapterNumbers as $chapter) {
            if ($chapter < 1 || $chapter > self::BIBLE_TOTAL_CHAPTERS) continue;
            $index = $chapter - 1;
            $byteIndex = intdiv($index, 8);
            $bit = $index % 8;
            $mask = 1 << $bit;
            if (!($bytes[$byteIndex] & $mask)) {
                $bytes[$byteIndex] |= $mask;
                $newCount++;
            }
        }
        return [pack('C*', ...$bytes), $newCount];
    }

    /**
     * Nucleo compartido de "registrar avance": lo usan tanto el flujo principal
     * (ReadingController::logReading, junto con la racha/reaccion en una sola
     * transaccion) como el flujo de "avance extra" (BookController::updateProgress,
     * disponible el resto del dia una vez ya se marco la lectura de hoy). $book
     * debe venir ya leido con FOR UPDATE por el llamador (misma transaccion).
     * Lanza \InvalidArgumentException con mensaje listo para el usuario si el
     * input no es valido; el llamador debe hacer rollback antes de responder.
     *
     * @return array{units_read: int, finished: bool, book_id: string, book: array}
     */
    public static function applyProgress(\PDO $db, array $book, ?int $newPage, ?array $chapters): array {
        $bookId = (string)$book['id'];

        if ($book['tracking_mode'] === self::MODE_LINEAR) {
            $currentUnit = (int)$book['current_unit'];
            $totalUnits = (int)$book['total_units'];

            if ($newPage === null || $newPage <= $currentUnit || $newPage > $totalUnits) {
                throw new \InvalidArgumentException("current_page debe ser mayor a {$currentUnit} y como maximo {$totalUnits}.");
            }

            $unitsRead = $newPage - $currentUnit;
            $finished = ($newPage === $totalUnits);
            self::updateLinearProgress($db, $bookId, $newPage, $finished ? 'finished' : 'reading');
            $book['current_unit'] = $newPage;
        } else {
            if (empty($chapters)) {
                throw new \InvalidArgumentException('chapters es requerido (array de numeros de capitulo).');
            }

            [$newBitmask, $unitsRead] = self::orChapters($book['progress_bitmask'], $chapters);
            $finished = (self::countSetBits($newBitmask) === self::BIBLE_TOTAL_CHAPTERS);
            self::updateBitmask($db, $bookId, $newBitmask, $finished ? 'finished' : 'reading');
            $book['progress_bitmask'] = $newBitmask;
        }

        $book['status'] = $finished ? 'finished' : 'reading';

        return ['units_read' => $unitsRead, 'finished' => $finished, 'book_id' => $bookId, 'book' => $book];
    }
}
