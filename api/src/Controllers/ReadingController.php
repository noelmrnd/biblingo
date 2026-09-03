<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class ReadingController {
    public static function getStatus($userId) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("SELECT streak_count, max_streak_count, last_read_date, timezone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $userTz = $user['timezone'] ?? 'UTC';
        $today = DateUtils::getUserToday($userTz);

        // Obtener historial de lecturas de los últimos 30 días
        $logsStmt = $db->prepare("
            SELECT read_date FROM reading_logs 
            WHERE user_id = ? AND read_date >= DATE_SUB(?, INTERVAL 30 DAY) 
            ORDER BY read_date DESC
        ");
        $logsStmt->execute([$userId, $today]);
        $logs = $logsStmt->fetchAll(PDO::FETCH_COLUMN);

        $hasReadToday = ($user['last_read_date'] === $today);
        $yesterday = DateUtils::getUserYesterday($userTz);
        $lastRead = $user['last_read_date'];
        $streakCount = (int)$user['streak_count'];
        $isStreakLost = ($streakCount === 0 || empty($lastRead) || ($lastRead !== $today && $lastRead !== $yesterday));

        sendJsonResponse([
            'success'          => true,
            'streak_count'     => $streakCount,
            'max_streak_count' => (int)$user['max_streak_count'],
            'last_read_date'   => $lastRead,
            'has_read_today'   => $hasReadToday,
            'is_streak_lost'   => $isStreakLost,
            'history'          => $logs
        ]);
    }

    public static function logReading($userId) {
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

                // 2. Registrar la entrada en el historial de lecturas
                $logStmt = $db->prepare("
                    INSERT INTO reading_logs (user_id, read_date) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
                ");
                $logStmt->execute([$userId, $today]);

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
            'last_read_date'   => $today
        ]);
    }
}
