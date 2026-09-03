<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class UserController {
    public static function updateProfile($userId) {
        $input = getJsonInput();
        $displayName = isset($input['display_name']) ? trim($input['display_name']) : null;
        $pushToken   = isset($input['push_token']) ? trim($input['push_token']) : null;
        $reminderTime = isset($input['reminder_time']) ? trim($input['reminder_time']) : null;
        $timezone     = isset($input['timezone']) ? DateUtils::getSafeDateTimeZone($input['timezone'])->getName() : null;

        if (!$displayName && !$pushToken && !$reminderTime && !$timezone) {
            sendJsonResponse(['error' => 'Sin datos para actualizar.'], 400);
        }

        $db = getDbConnection();
        $fields = [];
        $params = [];

        if ($displayName) {
            $fields[] = 'display_name = ?';
            $params[] = $displayName;
        }

        if ($reminderTime !== null) {
            $fields[] = 'reminder_time = ?';
            $params[] = $reminderTime;
        }

        if ($timezone !== null) {
            $fields[] = 'timezone = ?';
            $params[] = $timezone;
        }

        if (!empty($fields)) {
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        // Si se proporcionó un push_token, almacenarlo también en user_push_tokens para soporte multidispositivo
        if (!empty($pushToken)) {
            $platform = strtolower(trim($input['platform'] ?? 'ios'));
            if (!in_array($platform, ['ios', 'android', 'web'], true)) {
                $platform = 'ios';
            }

            try {
                // Registrar o reasignar el token de forma atómica al usuario actual
                $tokenStmt = $db->prepare("
                    INSERT INTO user_push_tokens (user_id, token, platform)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform), updated_at = CURRENT_TIMESTAMP
                ");
                $tokenStmt->execute([$userId, $pushToken, $platform]);
            } catch (Exception $e) {
                error_log("Error al guardar token multidispositivo: " . $e->getMessage());
            }
        }

        sendJsonResponse([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.'
        ]);
    }
}

