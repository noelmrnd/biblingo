<?php

declare(strict_types=1);

namespace Biblingo\Controllers;

use Biblingo\Entities\ReadingLogEntity;
use Biblingo\Entities\UserEntity;
use Biblingo\Utils\DateUtils;
use Biblingo\Utils\SnowflakeId;
use Biblingo\Utils\StreakUtils;

class ReadingController {
    public static function getStatus(string $userId) {
        $db = getDbConnection();

        $user = UserEntity::getReadingStatusRow($db, $userId);

        if (!$user) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $lastRead = $user['last_read_date'];
        $status = StreakUtils::computeStatus($lastRead, (int)$user['streak_count'], $user['timezone'], (int)$user['streak_freezes']);

        $totalDaysRead = ReadingLogEntity::countTotalDaysRead($db, $userId);

        sendJsonResponse([
            'success'              => true,
            'username'             => $user['username'],
            'streak_count'         => (int)$user['streak_count'],
            'max_streak_count'     => (int)$user['max_streak_count'],
            'streak_freezes'       => (int)$user['streak_freezes'],
            'streak_freezes_used'  => (int)$user['streak_freezes_used'],
            'total_days_read'      => $totalDaysRead,
            'reaction_counts'      => FriendController::countReactions($db, $userId),
            'member_since'         => substr((string)$user['created_at'], 0, 10),
            'followers_count'      => FriendController::countFollowers($db, $userId),
            'following_count'      => FriendController::countFollowing($db, $userId),
            'last_read_date'       => $lastRead,
            'last_read_label'      => $status->lastReadLabel,
            'has_read_today'       => $status->hasReadToday,
            'is_streak_lost'       => $status->isStreakLost,
            'notification_prefs'   => UserEntity::getNotificationPrefs($db, $userId),
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

        $dates = ReadingLogEntity::fetchCalendarDates($db, $userId, $monthStart);

        sendJsonResponse([
            'success' => true,
            'dates'   => $dates,
        ]);
    }

    private const MAX_STREAK_FREEZES = 2;
    private const FREEZE_EVERY_DAYS = 7;
    private const VALID_REACTIONS = ['loved', 'thoughtful', 'peaceful', 'challenged', 'saddened'];

    public static function logReading(string $userId, ?string $reaction = null) {
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

                UserEntity::updateStreak($db, $userId, $currentStreak, $maxStreak, $freezesAvailable, $freezesUsed, $today);

                $logId = (string)SnowflakeId::nextId();
                ReadingLogEntity::upsertLog($db, $logId, $userId, $today, $reaction);
            }

            $db->commit();
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
            'last_read_date'   => $today,
            'last_read_label'  => 'Hoy',
            'reaction'         => $reaction
        ]);
    }
}
