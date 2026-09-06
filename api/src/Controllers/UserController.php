<?php

declare(strict_types=1);

namespace Biblingo\Controllers;

use Biblingo\Utils\DateUtils;
use Biblingo\Utils\SnowflakeId;
use Biblingo\Utils\StreakUtils;

class UserController {
    /**
     * Datos minimos que necesita la pantalla de Ajustes (Datos de perfil): nada de
     * racha, seguidores ni historial, a diferencia de getFriendProfile.
     */
    public static function getSettings(string $userId) {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT display_name, username, email, timezone, reminder_time FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        sendJsonResponse([
            'success' => true,
            'user' => [
                'display_name'  => $user['display_name'],
                'username'      => $user['username'],
                'email'         => $user['email'],
                'timezone'      => $user['timezone'],
                'reminder_time' => $user['reminder_time'],
            ]
        ]);
    }

    public static function updateProfile(string $userId) {
        $input = getJsonInput();
        $displayName = isset($input['display_name']) ? trim($input['display_name']) : null;
        $username     = isset($input['username']) ? strtolower(trim($input['username'])) : null;
        $reminderTime = isset($input['reminder_time']) ? trim($input['reminder_time']) : null;
        $timezone     = isset($input['timezone']) ? DateUtils::getSafeDateTimeZone($input['timezone'])->getName() : null;

        if (!$displayName && !$username && !$reminderTime && !$timezone) {
            sendJsonResponse(['error' => 'Sin datos para actualizar.'], 400);
        }

        $db = getDbConnection();
        $fields = [];
        $params = [];

        if ($displayName !== null) {
            if (mb_strlen($displayName) < 2 || mb_strlen($displayName) > 50) {
                sendJsonResponse(['error' => 'El nombre debe tener entre 2 y 50 caracteres.'], 400);
            }
            $fields[] = 'display_name = ?';
            $params[] = $displayName;
        }

        if ($username !== null) {
            if (!preg_match('/^[a-z0-9_]{3,20}$/', $username)) {
                sendJsonResponse(['error' => 'El usuario debe tener 3-20 caracteres: minusculas, numeros o guion bajo.'], 400);
            }
            $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkStmt->execute([$username, $userId]);
            if ($checkStmt->fetch()) {
                sendJsonResponse(['error' => 'Ese nombre de usuario ya está en uso.'], 400);
            }
            $fields[] = 'username = ?';
            $params[] = $username;
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

        $userStmt = $db->prepare("SELECT id, display_name, email, username, streak_count, max_streak_count, last_read_date, reminder_time, timezone, platform FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $updatedUser = $userStmt->fetch();
        if (!$updatedUser) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $status = StreakUtils::computeStatus($updatedUser['last_read_date'], (int)$updatedUser['streak_count'], $updatedUser['timezone']);

        sendJsonResponse([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'user'    => [
                ...$updatedUser,
                'id' => (string)$updatedUser['id'],
                'has_read_today' => $status->hasReadToday,
                'is_streak_lost' => $status->isStreakLost,
                'last_read_label' => $status->lastReadLabel,
            ],
        ]);
    }

    /**
     * Endpoint dedicado para registrar o actualizar el token push multidispositivo del usuario.
     */
    public static function registerPushToken(string $userId) {
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
            $tokenId = SnowflakeId::nextId();
            $tokenStmt = $db->prepare("
                INSERT INTO user_push_tokens (id, user_id, token, platform)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform), updated_at = CURRENT_TIMESTAMP
            ");
            $tokenStmt->execute([$tokenId, $userId, $pushToken, $platform]);

            sendJsonResponse([
                'success' => true,
                'message' => 'Token Push registrado con éxito.'
            ]);
        } catch (\PDOException $e) {
            // Manejar error de integridad de clave foránea si el usuario fue removido
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1452')) {
                sendJsonResponse(['error' => 'Usuario no encontrado en el sistema.'], 404);
            }
            sendJsonResponse(['error' => 'Error al guardar el token Push: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            sendJsonResponse(['error' => 'Error al guardar el token Push: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint dedicado para eliminar / desregistrar el token push al cerrar sesión.
     */
    public static function unregisterPushToken(string $userId) {
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
        } catch (\Exception $e) {
            sendJsonResponse(['error' => 'Error al eliminar el token Push: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Registra una sugerencia, reporte de bug u otro comentario enviado desde el perfil.
     */
    public static function submitFeedback(string $userId) {
        $input = getJsonInput();
        $type = strtolower(trim($input['type'] ?? 'other'));
        $message = trim($input['message'] ?? '');

        if (!in_array($type, ['idea', 'bug', 'other'], true)) {
            $type = 'other';
        }

        if (mb_strlen($message) < 5 || mb_strlen($message) > 2000) {
            sendJsonResponse(['error' => 'El mensaje debe tener entre 5 y 2000 caracteres.'], 400);
        }

        $db = getDbConnection();
        $feedbackId = SnowflakeId::nextId();
        $stmt = $db->prepare("INSERT INTO feedback (id, user_id, type, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$feedbackId, $userId, $type, $message]);

        sendJsonResponse([
            'success' => true,
            'message' => '¡Gracias por tu comentario! 🙌'
        ]);
    }

    /**
     * Elimina permanentemente la cuenta del usuario y todos sus datos asociados
     * (rachas, amistades, solicitudes, toques, tokens push) vía ON DELETE CASCADE.
     * Requerido por Apple App Store Review Guideline 5.1.1(v).
     */
    public static function deleteAccount(string $userId) {
        $db = getDbConnection();

        $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $checkStmt->execute([$userId]);
        if (!$checkStmt->fetch()) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        try {
            $deleteStmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $deleteStmt->execute([$userId]);

            sendJsonResponse([
                'success' => true,
                'message' => 'Tu cuenta y todos tus datos fueron eliminados permanentemente.'
            ]);
        } catch (\Exception $e) {
            sendJsonResponse(['error' => 'Error al eliminar la cuenta: ' . $e->getMessage()], 500);
        }
    }
}
