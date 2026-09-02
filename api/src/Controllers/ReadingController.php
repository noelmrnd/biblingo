<?php

require_once __DIR__ . '/../../config/db.php';

class ReadingController {
    public static function getStatus($userId) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("SELECT streak_count, max_streak_count, last_read_date FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        // Obtener historial de lecturas de los últimos 30 días
        $logsStmt = $db->prepare("
            SELECT read_date FROM reading_logs 
            WHERE user_id = ? AND read_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            ORDER BY read_date DESC
        ");
        $logsStmt->execute([$userId]);
        $logs = $logsStmt->fetchAll(PDO::FETCH_COLUMN);

        $today = date('Y-m-d');
        $hasReadToday = ($user['last_read_date'] === $today);

        sendJsonResponse([
            'success'          => true,
            'streak_count'     => (int)$user['streak_count'],
            'max_streak_count' => (int)$user['max_streak_count'],
            'last_read_date'   => $user['last_read_date'],
            'has_read_today'   => $hasReadToday,
            'history'          => $logs
        ]);
    }

    public static function logReading($userId) {
        $db = getDbConnection();

        $stmt = $db->prepare("SELECT streak_count, max_streak_count, last_read_date FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

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

            // Actualizar usuario
            $updateStmt = $db->prepare("
                UPDATE users 
                SET streak_count = ?, max_streak_count = ?, last_read_date = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$currentStreak, $maxStreak, $today, $userId]);

            // Insertar o ignorar en reading_logs
            $logStmt = $db->prepare("
                INSERT INTO reading_logs (user_id, read_date) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
            ");
            $logStmt->execute([$userId, $today]);
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
