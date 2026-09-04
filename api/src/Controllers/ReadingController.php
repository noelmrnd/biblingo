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

        // Obtener historial de lecturas y reacciones de los últimos 30 días en una sola consulta
        $logsStmt = $db->prepare("
            SELECT read_date, reaction FROM reading_logs 
            WHERE user_id = ? AND read_date >= DATE_SUB(?, INTERVAL 30 DAY) 
            ORDER BY read_date DESC
        ");
        $logsStmt->execute([$userId, $today]);
        $logs = $logsStmt->fetchAll();

        $historyDates = array_column($logs, 'read_date');

        $hasReadToday = $user['last_read_date'] === $today;
        $yesterday = DateUtils::getUserYesterday($userTz);
        $lastRead = $user['last_read_date'];
        $streakCount = (int)$user['streak_count'];
        $isStreakLost = ($streakCount === 0 || empty($lastRead) || ($lastRead !== $today && $lastRead !== $yesterday));

        // Obtener la reacción más reciente (la de hoy) directamente de los registros ya obtenidos
        $todayReaction = null;
        if ($hasReadToday && !empty($logs) && $logs[0]['read_date'] === $today) {
            $todayReaction = $logs[0]['reaction'] ?? null;
        }

        sendJsonResponse([
            'success'          => true,
            'streak_count'     => $streakCount,
            'max_streak_count' => (int)$user['max_streak_count'],
            'last_read_date'   => $lastRead,
            'has_read_today'   => $hasReadToday,
            'today_reaction'   => $todayReaction,
            'is_streak_lost'   => $isStreakLost,
            'history'          => $historyDates,
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
            'reaction'         => $reaction
        ]);
    }
}
