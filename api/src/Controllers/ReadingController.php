<?php

declare(strict_types=1);

namespace Libringo\Controllers;

use Libringo\Entities\BadgeEntity;
use Libringo\Entities\BookEntity;
use Libringo\Entities\ReadingLogEntity;
use Libringo\Entities\UserEntity;
use Libringo\Utils\DateUtils;
use Libringo\Utils\SnowflakeId;
use Libringo\Utils\StreakUtils;

class ReadingController {
    public static function getStatus(string $userId) {
        $db = getDbConnection();

        $user = UserEntity::getReadingStatusRow($db, $userId);

        if (!$user) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $lastRead = $user['last_read_date'];
        $status = StreakUtils::computeStatus($lastRead, (int)$user['streak_count'], $user['timezone'], (int)$user['streak_freezes']);

        $activeBook = BookEntity::findActiveByUser($db, $userId);

        sendJsonResponse([
            'success'              => true,
            'username'             => $user['username'],
            'streak_count'         => (int)$user['streak_count'],
            'max_streak_count'     => (int)$user['max_streak_count'],
            'streak_freezes'       => (int)$user['streak_freezes'],
            'streak_freezes_used'  => (int)$user['streak_freezes_used'],
            'days_read'      => (int)$user['days_read'],
            'pages_read'     => (int)$user['pages_read'],
            'current_book_title'   => $activeBook['title'] ?? null,
            'reading_current_unit' => $activeBook ? BookEntity::getCurrentUnit($activeBook) : null,
            'reading_total_units'  => $activeBook ? (int)$activeBook['total_units'] : null,
            'reaction_counts'      => FriendController::countReactions($db, $userId),
            'member_since'         => substr((string)$user['created_at'], 0, 10),
            'followers_count'      => FriendController::countFollowers($db, $userId),
            'following_count'      => FriendController::countFollowing($db, $userId),
            'last_read_date'       => $lastRead,
            'last_read_label'      => $status->lastReadLabel,
            'has_read_today'       => $status->hasReadToday,
            'is_streak_lost'       => $status->isStreakLost,
            'will_use_freeze_today' => $status->willUseFreezeToday,
            'missed_days'          => $status->missedDays,
            'notification_prefs'   => UserEntity::getNotificationPrefs($db, $userId),
            'badges'               => BadgeEntity::listForUser($db, $userId),
        ]);
    }

    /**
     * Dias leidos dentro de un mes especifico (year/month), para el calendario mensual
     * de Racha. A diferencia de getStatus, no trae nada mas (sin racha/seguidores/etc).
     */
    public static function getCalendar(string $userId, int $year, int $month) {
        if ($month < 1 || $month > 12) {
            sendJsonResponse(['error' => 'month debe estar entre 1 y 12.'], 400);
        }
        if ($year < 2020 || $year > 2100) {
            sendJsonResponse(['error' => 'year invalido.'], 400);
        }

        $db = getDbConnection();
        $monthStart = sprintf('%04d-%02d-01', $year, $month);

        $days = ReadingLogEntity::fetchCalendarDates($db, $userId, $monthStart);

        sendJsonResponse([
            'success' => true,
            'days'    => $days,
        ]);
    }

    private const MAX_STREAK_FREEZES = 2;
    private const FREEZE_EVERY_DAYS = 7;
    private const VALID_REACTIONS = ['loved', 'thoughtful', 'peaceful', 'challenged', 'moved'];

    /**
     * $newPage/$chapters: avance opcional del libro activo, registrado en la MISMA
     * transaccion que la racha (atomico — si el avance es invalido, no se marca la
     * racha ni se guarda nada). Si el usuario no tiene libro activo, se ignoran.
     * Para avance adicional el mismo dia DESPUES de ya haber leido hoy, ver
     * BookController::updateProgress en su lugar (no repite la racha/reaccion).
     */
    public static function logReading(string $userId, ?string $reaction = null, ?int $newPage = null, ?array $chapters = null) {
        if ($reaction !== null && !in_array($reaction, self::VALID_REACTIONS, true)) {
            sendJsonResponse(['error' => 'reaction invalida.'], 400);
        }

        $db = getDbConnection();

        // Lectura + calculo + escritura del estado de racha, todo bajo una misma
        // transaccion con FOR UPDATE: evita que dos requests concurrentes de
        // logReading lean el mismo estado viejo y lo pisen dos veces.
        try {
            $db->beginTransaction();

            $user = UserEntity::getStreakRowForUpdate($db, $userId);

            if (!$user) {
                $db->rollBack();
                sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
            }

            $userTz = $user['timezone'] ?? 'UTC';
            $today = DateUtils::getUserToday($userTz);
            $yesterday = DateUtils::getUserYesterday($userTz);

            $currentStreak = (int)$user['streak_count'];
            $maxStreak = (int)$user['max_streak_count'];
            $freezesAvailable = (int)$user['streak_freezes'];
            $freezesUsed = (int)$user['streak_freezes_used'];
            $lastRead = $user['last_read_date'];

            $alreadyLoggedToday = ($lastRead === $today);
            $usedFreeze = false;
            $freezesUsedThisTime = 0;
            $frozenDates = [];
            $newBadges = [];

            if (!$alreadyLoggedToday) {
                // Dias saltados entre la ultima lectura y hoy (sin contar ninguno de los dos
                // extremos). Con last_read=ayer da 0 (racha normal, sin protector).
                $missedDays = $lastRead ? max(0, DateUtils::daysBetween($lastRead, $today) - 1) : null;

                if ($lastRead === $yesterday) {
                    $currentStreak += 1;
                } elseif ($missedDays !== null && $missedDays > 0 && $freezesAvailable >= $missedDays) {
                    // Cada protector cubre 1 dia saltado (como Duolingo): con N protectores
                    // disponibles se pueden cubrir hasta N dias seguidos sin perder la racha.
                    $currentStreak += 1;
                    $freezesAvailable -= $missedDays;
                    $freezesUsed += $missedDays;
                    $usedFreeze = true;
                    $freezesUsedThisTime = $missedDays;
                    $frozenDates = self::datesBetweenExclusive($lastRead, $today);
                } else {
                    $currentStreak = 1;
                }

                if ($currentStreak > $maxStreak) {
                    $maxStreak = $currentStreak;
                }

                // Otorgar un protector nuevo cada FREEZE_EVERY_DAYS de racha activa, con tope.
                if ($currentStreak > 0 && $currentStreak % self::FREEZE_EVERY_DAYS === 0 && $freezesAvailable < self::MAX_STREAK_FREEZES) {
                    $freezesAvailable += 1;
                }

                $unitsRead = null;
                $bookId = null;
                $bookFinished = false;
                $activeBook = BookEntity::findActiveByUserForUpdate($db, $userId);
                if ($activeBook) {
                    $progress = BookEntity::applyProgress($db, $activeBook, $newPage, $chapters);
                    $unitsRead = $progress['units_read'];
                    $bookId = $progress['book_id'];
                    $bookFinished = $progress['finished'];
                    UserEntity::incrementTotalPagesRead($db, $userId, $unitsRead);
                }

                UserEntity::updateStreak($db, $userId, $currentStreak, $maxStreak, $freezesAvailable, $freezesUsed, $today);

                $logId = (string)SnowflakeId::nextId();
                ReadingLogEntity::insertLog($db, $logId, $userId, $today, $reaction, $unitsRead, $bookId);
                UserEntity::incrementDaysRead($db, $userId);

                if (!empty($frozenDates)) {
                    ReadingLogEntity::insertFrozenDays($db, $userId, $frozenDates);
                }

                $pagesRead = (int)$user['pages_read'] + ($unitsRead ?? 0);
                $daysRead = (int)$user['days_read'] + 1;
                $newBadges = self::checkBadgesAfterLog($db, $userId, $currentStreak, $reaction, $user['created_at'], $bookFinished, $pagesRead, $daysRead);
            }

            $db->commit();
        } catch (\InvalidArgumentException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            sendJsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[ReadingController::logReading] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error de base de datos al registrar lectura.'], 500);
        }

        sendJsonResponse([
            'success'          => true,
            'already_read'     => $alreadyLoggedToday,
            'streak_count'     => $currentStreak,
            'max_streak_count' => $maxStreak,
            'streak_freezes'   => $freezesAvailable,
            'streak_freezes_used' => $freezesUsed,
            'used_freeze'      => $usedFreeze,
            'freezes_used_this_time' => $freezesUsedThisTime,
            'last_read_date'   => $today,
            'last_read_label'  => 'Hoy',
            'reaction'         => $reaction,
            'new_badges'       => $newBadges
        ]);
    }

    /**
     * Chequeo de medallas ligado a la respuesta HTTP de logReading (no via
     * domain event) porque el frontend necesita 'new_badges' en la MISMA
     * response para el confetti/modal instantaneo — ver BadgeEventHandler
     * para el resto de las medallas (following/nudge), que si se disparan
     * de forma desacoplada porque su UI no depende de la response inmediata.
     *
     * "Fundador" tambien se chequea aca (no en el registro): asi el usuario
     * la ve celebrada como cualquier otra medalla en su primera lectura, sin
     * necesitar un mecanismo aparte para medallas otorgadas fuera de este flujo.
     */
    private static function checkBadgesAfterLog(\PDO $db, string $userId, int $currentStreak, ?string $reaction, string $userCreatedAt, bool $bookFinished, int $pagesRead, int $daysRead): array {
        $badgeValues = [
            'streak'    => $currentStreak,
            'days_read' => $daysRead,
            // 'pages' => $pagesRead, // medallas de paginas deshabilitadas, ver BadgeEntity::CATALOG
        ];

        if ($bookFinished) {
            $badgeValues['books_finished'] = BookEntity::countFinishedByUser($db, $userId);
        }

        if ($reaction !== null) {
            $reactionCounts = ReadingLogEntity::countReactionsGrouped($db, $userId);
            foreach ($reactionCounts as $row) {
                $key = 'reaction_' . $row['reaction'];
                $badgeValues[$key] = (int)$row['total'];
            }

            // $reactionCounts ya viene agrupado por reaccion (1 fila por tipo distinto
            // usado), asi que su cantidad de filas ES la cantidad de tipos distintos.
            $badgeValues['reaction_variety'] = count($reactionCounts);
        }

        $founderCutoff = getEnvVar('FOUNDER_BADGE_CUTOFF');
        if ($founderCutoff !== '' && new \DateTimeImmutable($userCreatedAt) < new \DateTimeImmutable($founderCutoff)) {
            $badgeValues['founder'] = 1;
        }

        return BadgeEntity::checkAndAward($db, $userId, $badgeValues);
    }

    /** Fechas 'Y-m-d' estrictamente entre dos fechas dadas (sin incluir ninguno de los extremos). */
    private static function datesBetweenExclusive(string $fromDate, string $toDate): array {
        $dates = [];
        $cursor = (new \DateTime($fromDate))->modify('+1 day');
        $end = new \DateTime($toDate);
        while ($cursor < $end) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->modify('+1 day');
        }
        return $dates;
    }
}
