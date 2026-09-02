<?php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Services/FCMService.php';

class FriendController {
    public static function getFriends($userId) {
        $db = getDbConnection();

        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.invite_code 
            FROM friendships f
            JOIN users u ON f.friend_id = u.id
            WHERE f.user_id = ?
            ORDER BY u.streak_count DESC, u.display_name ASC
        ");
        $stmt->execute([$userId]);
        $friends = $stmt->fetchAll();

        sendJsonResponse([
            'success' => true,
            'friends' => array_map(function($f) {
                return [
                    'id'               => (int)$f['id'],
                    'display_name'     => $f['display_name'],
                    'streak_count'     => (int)$f['streak_count'],
                    'max_streak_count' => (int)$f['max_streak_count'],
                    'last_read_date'   => $f['last_read_date'],
                    'invite_code'      => $f['invite_code']
                ];
            }, $friends)
        ]);
    }

    public static function addFriend($userId) {
        $input = getJsonInput();
        $inviteCode = strtoupper(trim($input['invite_code'] ?? ''));

        if (empty($inviteCode)) {
            sendJsonResponse(['error' => 'Código de invitación requerido.'], 400);
        }

        $db = getDbConnection();

        // Buscar al amigo por invite_code
        $targetStmt = $db->prepare("SELECT id, display_name, push_token FROM users WHERE invite_code = ?");
        $targetStmt->execute([$inviteCode]);
        $friend = $targetStmt->fetch();

        if (!$friend) {
            sendJsonResponse(['error' => 'No se encontró ningún usuario con ese código de invitación.'], 404);
        }

        $friendId = (int)$friend['id'];

        if ($friendId === (int)$userId) {
            sendJsonResponse(['error' => 'No puedes agregarte a ti mismo como amigo.'], 400);
        }

        // Obtener datos del usuario actual para el mensaje
        $meStmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $meStmt->execute([$userId]);
        $me = $meStmt->fetch();
        $myDisplayName = $me ? $me['display_name'] : 'Un usuario';

        // Insertar relaciones bidireccionales
        try {
            $db->beginTransaction();

            $insert1 = $db->prepare("INSERT IGNORE INTO friendships (user_id, friend_id) VALUES (?, ?)");
            $insert1->execute([$userId, $friendId]);

            $insert2 = $db->prepare("INSERT IGNORE INTO friendships (user_id, friend_id) VALUES (?, ?)");
            $insert2->execute([$friendId, $userId]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            sendJsonResponse(['error' => 'Error al agregar amigo: ' . $e->getMessage()], 500);
        }

        // Enviar notificación Push al amigo vía FCM
        if (!empty($friend['push_token'])) {
            FCMService::sendPushNotification(
                $friend['push_token'],
                '¡Nuevo Amigo en Biblingo! 🎉',
                "{$myDisplayName} te ha agregado a sus amigos. ¡Compite por la mejor racha!",
                ['type' => 'friend_added', 'user_id' => $userId]
            );
        }

        sendJsonResponse([
            'success' => true,
            'message' => '¡Amigo agregado con éxito!',
            'friend'  => [
                'id'           => $friendId,
                'display_name' => $friend['display_name']
            ]
        ]);
    }
}
