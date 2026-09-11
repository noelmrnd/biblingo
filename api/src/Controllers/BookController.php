<?php

declare(strict_types=1);

namespace Libringo\Controllers;

use Libringo\Entities\BadgeEntity;
use Libringo\Entities\BookEntity;
use Libringo\Entities\ReadingLogEntity;
use Libringo\Entities\UserEntity;
use Libringo\Utils\DateUtils;
use Libringo\Utils\SnowflakeId;

class BookController {
    private const MAX_TITLE_LENGTH = 255;

    /**
     * Crea el libro activo del usuario. Si ya tenia uno en 'reading', lo marca
     * 'abandoned' (solo se permite 1 libro activo a la vez, ver CLAUDE.md).
     * title == 'biblia' (match flexible) activa tracking_mode 'bitmask' con
     * total_units fijo (1189 capitulos); total_pages del body se ignora en ese caso.
     */
    public static function create(string $userId) {
        $input = getJsonInput();
        $title = trim((string)($input['title'] ?? ''));

        if ($title === '') {
            sendJsonResponse(['error' => 'title es requerido.'], 400);
        }
        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            sendJsonResponse(['error' => 'title demasiado largo.'], 400);
        }

        $trackingMode = BookEntity::detectTrackingMode($title);

        if ($trackingMode === BookEntity::MODE_BITMASK) {
            $totalUnits = BookEntity::BIBLE_TOTAL_CHAPTERS;
        } else {
            $totalPages = isset($input['total_pages']) ? (int)$input['total_pages'] : null;
            if ($totalPages === null || $totalPages <= 0) {
                sendJsonResponse(['error' => 'total_pages es requerido y debe ser mayor a 0.'], 400);
            }
            $totalUnits = $totalPages;
        }

        $db = getDbConnection();

        try {
            $db->beginTransaction();

            $current = BookEntity::findActiveByUser($db, $userId);
            if ($current) {
                BookEntity::markAbandoned($db, (string)$current['id']);
            }

            $bookId = (string)SnowflakeId::nextId();
            BookEntity::create($db, $bookId, $userId, $title, $trackingMode, $totalUnits);

            $db->commit();
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[BookController::create] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error de base de datos al crear el libro.'], 500);
        }

        sendJsonResponse([
            'success' => true,
            'book' => self::formatBook([
                'id' => $bookId,
                'title' => $title,
                'tracking_mode' => $trackingMode,
                'total_units' => $totalUnits,
                'current_unit' => 0,
                'progress_bitmask' => null,
                'status' => 'reading',
            ]),
        ]);
    }

    /**
     * Quita el libro activo sin reemplazarlo (el control de lectura es opcional,
     * ver CLAUDE.md). El progreso guardado no se pierde: la fila queda 'abandoned',
     * igual que al cambiar de libro, solo que sin crear uno nuevo.
     */
    public static function removeActive(string $userId) {
        $db = getDbConnection();

        $current = BookEntity::findActiveByUser($db, $userId);
        if (!$current) {
            sendJsonResponse(['error' => 'No tienes un libro activo.'], 404);
        }

        BookEntity::markAbandoned($db, (string)$current['id']);

        sendJsonResponse(['success' => true]);
    }

    /** Libro activo del usuario (o null), para que el front sepa si mostrar el paso de progreso. */
    public static function getActive(string $userId) {
        $db = getDbConnection();
        $book = BookEntity::findActiveByUser($db, $userId);

        sendJsonResponse([
            'success' => true,
            'book' => $book ? self::formatBook($book) : null,
        ]);
    }

    /**
     * "Avance extra": registrar mas paginas/capitulos leidos el mismo dia DESPUES
     * de ya haber marcado la lectura de hoy (ver ReadingController::logReading
     * para ese primer registro, que hace lo mismo pero atomico con la racha y la
     * reaccion). Nunca toca la racha ni la reaccion — solo suma sobre el mismo
     * reading_log del dia (unique_user_day), nunca crea una fila nueva.
     * Modo 'linear': body.current_page (nueva pagina, mayor a la actual). Modo
     * 'bitmask': body.chapters (array de capitulos, OR incremental).
     */
    public static function updateProgress(string $userId) {
        $input = getJsonInput();
        $newPage = isset($input['current_page']) ? (int)$input['current_page'] : null;
        $chapters = is_array($input['chapters'] ?? null) ? array_map('intval', $input['chapters']) : null;

        $db = getDbConnection();
        $book = null;

        try {
            $db->beginTransaction();

            $book = BookEntity::findActiveByUserForUpdate($db, $userId);
            if (!$book) {
                $db->rollBack();
                sendJsonResponse(['error' => 'No tienes un libro activo.'], 404);
            }

            $result = BookEntity::applyProgress($db, $book, $newPage, $chapters);
            $book = $result['book'];

            $userRow = UserEntity::getTimezoneAndPagesRead($db, $userId);
            UserEntity::incrementTotalPagesRead($db, $userId, $result['units_read']);

            $today = DateUtils::getUserToday($userRow['timezone']);
            ReadingLogEntity::incrementUnitsRead($db, $userId, $today, $result['units_read'], $result['book_id']);

            // Este flujo no pasa por ReadingController::checkBadgesAfterLog (no toca
            // racha/reaccion), asi que el chequeo de medallas de lectura va aca —
            // terminar un libro via "avance extra" tambien debe celebrarse.
            $badgeValues = ['pages' => (int)$userRow['pages_read'] + $result['units_read']];
            if ($result['finished']) {
                $badgeValues['books_finished'] = BookEntity::countFinishedByUser($db, $userId);
            }
            $newBadges = BadgeEntity::checkAndAward($db, $userId, $badgeValues);

            $db->commit();
        } catch (\InvalidArgumentException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            sendJsonResponse(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            sendJsonResponse(['error' => $e->getMessage() . ' Registra tu lectura de hoy primero.'], 409);
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[BookController::updateProgress] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error de base de datos al registrar el avance.'], 500);
        }

        sendJsonResponse([
            'success' => true,
            'units_read' => $result['units_read'],
            'finished' => $result['finished'],
            'book' => self::formatBook($book),
            'new_badges' => $newBadges,
        ]);
    }

    private static function formatBook(array $book): array {
        $formatted = [
            'id' => (string)$book['id'],
            'title' => $book['title'],
            'tracking_mode' => $book['tracking_mode'],
            'total_units' => $book['total_units'] !== null ? (int)$book['total_units'] : null,
            'current_unit' => (int)$book['current_unit'],
            'status' => $book['status'],
        ];

        if ($book['tracking_mode'] === BookEntity::MODE_BITMASK) {
            $chapters = BookEntity::decodeBitmaskChapters($book['progress_bitmask'] ?? null);
            $formatted['read_chapters'] = $chapters;
            $formatted['current_unit'] = count($chapters);
        }

        return $formatted;
    }
}
