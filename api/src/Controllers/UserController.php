<?php

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

        if ($pushToken !== null) {
            $fields[] = 'push_token = ?';
            $params[] = $pushToken;
        }

        if ($reminderTime !== null) {
            $fields[] = 'reminder_time = ?';
            $params[] = $reminderTime;
        }

        if ($timezone !== null) {
            $fields[] = 'timezone = ?';
            $params[] = $timezone;
        }

        $params[] = $userId;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        sendJsonResponse([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.'
        ]);
    }
}
