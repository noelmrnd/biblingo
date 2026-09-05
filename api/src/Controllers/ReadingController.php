<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class ReadingController {
    public static function getStatus(string $userId) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("SELECT streak_count, max_streak_count, last_read_date, timezone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $userTz = $user['timezone'] ?? 'UTC';
        $today = DateUtils::getUserToday($userTz);
        $yesterday = DateUtils::getUserYesterday($userTz);
        $lastRead = $user['last_read_date'];
        $hasReadToday = $lastRead === $today;

        sendJsonResponse([
            'success'          => true,
            'streak_count'     => (int)$user['streak_count'],
            'max_streak_count' => (int)$user['max_streak_count'],
            'last_read_date'   => $lastRead,
            'last_read_label'  => DateUtils::formatReadDateLabel($lastRead, $today, $yesterday),
            'has_read_today'   => $hasReadToday,
        ]);
    }

    public static function logReading(string $userId, ?string $reaction = null) {
        $db = getDbConnection();

        $stmt = $db->prepare("SELECT streak_count, max_streak_count, last_read_date, timezone FROM users WHERE id = ?");
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
        $lastRead = $user['last_read_date'];

        $alreadyLoggedToday = ($lastRead === $today);

        if (!$alreadyLoggedToday) {
            if ($lastRead === $yesterday) {
                $currentStreak += 1;
            } else {
                $currentStreak = 1;
            }

            if ($currentStreak > $maxStreak) {
                $maxStreak = $currentStreak;
            }

            try {
                $db->beginTransaction();

                // 1. Actualizar contador y fecha en la tabla de usuarios
                $updateStmt = $db->prepare("
                    UPDATE users 
                    SET streak_count = ?, max_streak_count = ?, last_read_date = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$currentStreak, $maxStreak, $today, $userId]);

                // 2. Registrar la entrada en el historial de lecturas con Snowflake ID
                $logId = SnowflakeId::nextId();
                $logStmt = $db->prepare("
                    INSERT INTO reading_logs (id, user_id, read_date, reaction) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = CURRENT_TIMESTAMP
                ");
                $logStmt->execute([$logId, $userId, $today, $reaction]);

                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                sendJsonResponse(['error' => 'Error de base de datos al registrar lectura: ' . $e->getMessage()], 500);
            }
        }

        sendJsonResponse([
            'success'          => true,
            'already_read'     => $alreadyLoggedToday,
            'streak_count'     => $currentStreak,
            'max_streak_count' => $maxStreak,
            'last_read_date'   => $today,
            'last_read_label'  => 'Hoy',
            'reaction'         => $reaction
        ]);
    }
}
