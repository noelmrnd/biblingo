<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class UserController {
    public static function updateProfile($userId) {
        $input = getJsonInput();
        $displayName = isset($input['display_name']) ? trim($input['display_name']) : null;
        $reminderTime = isset($input['reminder_time']) ? trim($input['reminder_time']) : null;
        $timezone     = isset($input['timezone']) ? DateUtils::getSafeDateTimeZone($input['timezone'])->getName() : null;

        if (!$displayName && !$reminderTime && !$timezone) {
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

        sendJsonResponse([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.'
        ]);
    }

    /**
     * Endpoint dedicado para registrar o actualizar el token push multidispositivo del usuario.
     */
    public static function registerPushToken($userId) {
        $input = getJsonInput();
        $pushToken = isset($input['push_token']) ? trim($input['push_token']) : (isset($input['token']) ? trim($input['token']) : null);
        $platform  = strtolower(trim($input['platform'] ?? 'ios'));

        if (empty($pushToken)) {
            sendJsonResponse(['error' => 'push_token es requerido.'], 400);
        }

        if (!in_array($platform, ['ios', 'android', 'web'], true)) {
            $platform = 'ios';
        }

        $db = getDbConnection();
        try {
            $tokenStmt = $db->prepare("
                INSERT INTO user_push_tokens (user_id, token, platform)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform), updated_at = CURRENT_TIMESTAMP
            ");
            $tokenStmt->execute([$userId, $pushToken, $platform]);

            sendJsonResponse([
                'success' => true,
                'message' => 'Token Push registrado con éxito.'
            ]);
        } catch (Exception $e) {
            sendJsonResponse(['error' => 'Error al guardar el token Push: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint dedicado para eliminar / desregistrar el token push al cerrar sesión.
     */
    public static function unregisterPushToken($userId) {
        $input = getJsonInput();
        $pushToken = isset($input['push_token']) ? trim($input['push_token']) : (isset($input['token']) ? trim($input['token']) : null);
        if (!$pushToken) {
            $pushToken = isset($_GET['push_token']) ? trim($_GET['push_token']) : (isset($_GET['token']) ? trim($_GET['token']) : null);
        }
        $removeAll = !empty($input['all']) || !empty($_GET['all']);

        if (empty($pushToken) && !$removeAll) {
            sendJsonResponse(['error' => 'push_token es requerido para desregistrar este dispositivo (o enviar all=true).'], 400);
        }

        $db = getDbConnection();
        try {
            if ($removeAll) {
                $stmt = $db->prepare("DELETE FROM user_push_tokens WHERE user_id = ?");
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->prepare("DELETE FROM user_push_tokens WHERE user_id = ? AND token = ?");
                $stmt->execute([$userId, $pushToken]);
            }

            sendJsonResponse([
                'success' => true,
                'message' => $removeAll ? 'Todos los tokens Push fueron eliminados.' : 'Token Push eliminado con éxito.'
            ]);
        } catch (Exception $e) {
            sendJsonResponse(['error' => 'Error al eliminar el token Push: ' . $e->getMessage()], 500);
        }
    }
}


