<?php

declare(strict_types=1);

namespace Biblingo\Controllers;

use Biblingo\Entities\FeedbackEntity;
use Biblingo\Entities\PushTokenEntity;
use Biblingo\Entities\UserEntity;
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
        $user = UserEntity::getSettingsRow($db, $userId);

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
            if (UserEntity::usernameTakenByOther($db, $username, $userId)) {
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
            UserEntity::updateFields($db, $userId, $fields, $params);
        }

        $updatedUser = UserEntity::getUpdatedProfileRow($db, $userId);
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
            $tokenId = (string)SnowflakeId::nextId();
            PushTokenEntity::upsert($db, $tokenId, $userId, $pushToken, $platform);

            sendJsonResponse([
                'success' => true,
                'message' => 'Token Push registrado con éxito.'
            ]);
        } catch (\PDOException $e) {
            // Manejar error de integridad de clave foránea si el usuario fue removido
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1452')) {
                sendJsonResponse(['error' => 'Usuario no encontrado en el sistema.'], 404);
            }
            error_log('[UserController::registerPushToken] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error al guardar el token Push.'], 500);
        } catch (\Throwable $e) {
            error_log('[UserController::registerPushToken] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error al guardar el token Push.'], 500);
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
                PushTokenEntity::deleteAllForUser($db, $userId);
            } else {
                PushTokenEntity::deleteByToken($db, $userId, $pushToken);
            }

            sendJsonResponse([
                'success' => true,
                'message' => $removeAll ? 'Todos los tokens Push fueron eliminados.' : 'Token Push eliminado con éxito.'
            ]);
        } catch (\Exception $e) {
            error_log('[UserController::unregisterPushToken] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error al eliminar el token Push.'], 500);
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
        $feedbackId = (string)SnowflakeId::nextId();
        FeedbackEntity::insert($db, $feedbackId, $userId, $type, $message);

        sendJsonResponse([
            'success' => true,
            'message' => '¡Gracias por tu comentario! 🙌'
        ]);
    }

    /**
     * Marca la cuenta como eliminada (soft delete) en vez de borrarla fisicamente:
     * status pasa a 'deleted', lo que corta el login y cualquier token de sesion
     * activo (ver AuthController y Auth::authenticate). El borrado fisico, si se
     * decide hacerlo, se realiza manualmente para evitar eliminar datos por error.
     * Requerido por Apple App Store Review Guideline 5.1.1(v).
     */
    public static function deleteAccount(string $userId) {
        $db = getDbConnection();

        if (!UserEntity::existsId($db, $userId)) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        try {
            UserEntity::updateStatus($db, $userId, UserEntity::STATUS_DELETED);
            // Limpieza inmediata: aunque fetchTokensForUser ya filtra por status,
            // no tiene sentido conservar tokens push de una cuenta eliminada.
            PushTokenEntity::deleteAllForUser($db, $userId);

            sendJsonResponse([
                'success' => true,
                'message' => 'Tu cuenta fue eliminada.'
            ]);
        } catch (\Exception $e) {
            error_log('[UserController::deleteAccount] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error al eliminar la cuenta.'], 500);
        }
    }
}
