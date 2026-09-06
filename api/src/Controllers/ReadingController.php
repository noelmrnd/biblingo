<?php

declare(strict_types=1);

namespace Biblingo\Controllers;

use Biblingo\Utils\DateUtils;
use Biblingo\Utils\SnowflakeId;
use Biblingo\Utils\StreakUtils;

class ReadingController {
    public static function getStatus(string $userId) {
        $db = getDbConnection();

        $stmt = $db->prepare("SELECT username, streak_count, max_streak_count, streak_freezes, streak_freezes_used, last_read_date, timezone, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $lastRead = $user['last_read_date'];
        $status = StreakUtils::computeStatus($lastRead, (int)$user['streak_count'], $user['timezone']);

        $totalDaysStmt = $db->prepare("SELECT COUNT(*) AS total FROM reading_logs WHERE user_id = ?");
        $totalDaysStmt->execute([$userId]);
        $totalDaysRead = (int)($totalDaysStmt->fetch()['total'] ?? 0);

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

        $db = getDbConnection();
        $monthStart = sprintf('%04d-%02d-01', $year, $month);

        $stmt = $db->prepare("
            SELECT read_date FROM reading_logs
            WHERE user_id = ?
              AND read_date >= ?
              AND read_date < DATE_ADD(?, INTERVAL 1 MONTH)
        ");
        $stmt->execute([$userId, $monthStart, $monthStart]);

        sendJsonResponse([
            'success' => true,
            'dates'   => array_column($stmt->fetchAll(), 'read_date'),
        ]);
    }

    private const MAX_STREAK_FREEZES = 2;
    private const FREEZE_EVERY_DAYS = 7;

    public static function logReading(string $userId, ?string $reaction = null) {
        $db = getDbConnection();

        $stmt = $db->prepare("SELECT streak_count, max_streak_count, streak_freezes, streak_freezes_used, last_read_date, timezone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
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
            $gapDays = $lastRead ? DateUtils::daysBetween($lastRead, $today) : null;

            if ($lastRead === $yesterday) {
                $currentStreak += 1;
            } elseif ($gapDays === 2 && $freezesAvailable > 0) {
                // Se salto exactamente 1 dia: se cubre con un protector en vez de perder la racha.
                $currentStreak += 1;
                $freezesAvailable -= 1;
                $freezesUsed += 1;
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

            try {
                $db->beginTransaction();

                // 1. Actualizar contador y fecha en la tabla de usuarios
                $updateStmt = $db->prepare("
                    UPDATE users
                    SET streak_count = ?, max_streak_count = ?, streak_freezes = ?, streak_freezes_used = ?, last_read_date = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$currentStreak, $maxStreak, $freezesAvailable, $freezesUsed, $today, $userId]);

                // 2. Registrar la entrada en el historial de lecturas con Snowflake ID
                $logId = SnowflakeId::nextId();
                $logStmt = $db->prepare("
                    INSERT INTO reading_logs (id, user_id, read_date, reaction)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = CURRENT_TIMESTAMP
                ");
                $logStmt->execute([$logId, $userId, $today, $reaction]);

                $db->commit();
            } catch (\Exception $e) {
                $db->rollBack();
                sendJsonResponse(['error' => 'Error de base de datos al registrar lectura: ' . $e->getMessage()], 500);
            }
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
