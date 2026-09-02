<?php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Services/FCMService.php';

class FriendController {
    public static function getFriends($userId) {
        $db = getDbConnection();
        $today = date('Y-m-d');

        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.invite_code,
                   (SELECT COUNT(*) FROM friend_nudges fn WHERE fn.sender_id = ? AND fn.receiver_id = u.id AND fn.nudge_date = ?) AS nudged_today
            FROM friendships f
            JOIN users u ON f.friend_id = u.id
            WHERE f.user_id = ?
            ORDER BY u.streak_count DESC, u.display_name ASC
        ");
        $stmt->execute([$userId, $today, $userId]);
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
                    'invite_code'      => $f['invite_code'],
                    'nudged_today'     => ((int)$f['nudged_today']) > 0
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

    public static function nudgeFriend($userId) {
        $input = getJsonInput();
        $friendId = (int)($input['friend_id'] ?? 0);

        if (!$friendId) {
            sendJsonResponse(['error' => 'friend_id es requerido.'], 400);
        }

        $db = getDbConnection();

        // 1. Verificar amistad
        $stmt = $db->prepare("SELECT user_id FROM friendships WHERE user_id = ? AND friend_id = ?");
        $stmt->execute([$userId, $friendId]);
        if (!$stmt->fetch()) {
            sendJsonResponse(['error' => 'No tienes agregada a esta persona como amiga.'], 403);
        }

        // 2. Obtener información de remitente y destinatario
        $meStmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $meStmt->execute([$userId]);
        $me = $meStmt->fetch();
        $myDisplayName = $me ? $me['display_name'] : 'Un amigo';

        $friendStmt = $db->prepare("SELECT id, display_name, push_token, last_read_date FROM users WHERE id = ?");
        $friendStmt->execute([$friendId]);
        $friend = $friendStmt->fetch();

        if (!$friend) {
            sendJsonResponse(['error' => 'Amigo no encontrado.'], 404);
        }

        // 3. Verificar si el amigo ya leyó hoy
        $today = date('Y-m-d');
        if (!empty($friend['last_read_date']) && substr($friend['last_read_date'], 0, 10) === $today) {
            sendJsonResponse(['error' => "{$friend['display_name']} ya completó su lectura de hoy."], 400);
        }

        // 4. Verificar si YA se le envió un recordatorio hoy (límite de 1 al día)
        $nudgeCheck = $db->prepare("SELECT id FROM friend_nudges WHERE sender_id = ? AND receiver_id = ? AND nudge_date = ?");
        $nudgeCheck->execute([$userId, $friendId, $today]);
        if ($nudgeCheck->fetch()) {
            sendJsonResponse(['error' => "Ya le enviaste un recordatorio a {$friend['display_name']} hoy. ⏳"], 400);
        }

        // 5. Registrar el toque en la base de datos
        try {
            $insertNudge = $db->prepare("INSERT INTO friend_nudges (sender_id, receiver_id, nudge_date) VALUES (?, ?, ?)");
            $insertNudge->execute([$userId, $friendId, $today]);
        } catch (Exception $e) {
            // Ignorar error si ya existía registro
        }

        // 6. Enviar notificación Push vía FCM si cuenta con token
        if (!empty($friend['push_token'])) {
            FCMService::sendPushNotification(
                $friend['push_token'],
                '📖 Recordatorio de lectura',
                "{$myDisplayName} te ha enviado un recordatorio para que leas hoy y protejas tu racha. 🔥",
                ['type' => 'nudge', 'sender_id' => $userId]
            );
        }

        sendJsonResponse([
            'success'   => true,
            'message'   => "¡Le enviaste un recordatorio a {$friend['display_name']}! 🔔",
            'friend_id' => $friendId
        ]);
    }
}
