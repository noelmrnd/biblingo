<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Services/FCMService.php';

class FriendController {
    public static function getFriends($userId) {
        $db = getDbConnection();

        // 1. Obtener lista de amigos del usuario
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.invite_code, u.timezone
            FROM friendships f
            JOIN users u ON f.friend_id = u.id
            WHERE f.user_id = ?
            ORDER BY u.streak_count DESC, u.display_name ASC
        ");
        $stmt->execute([$userId]);
        $friends = $stmt->fetchAll();

        // 2. Obtener la última fecha de toque enviada por este usuario en una sola consulta indexada
        $nudgesStmt = $db->prepare("
            SELECT receiver_id, MAX(nudge_date) AS last_nudge_date
            FROM friend_nudges
            WHERE sender_id = ?
            GROUP BY receiver_id
        ");
        $nudgesStmt->execute([$userId]);
        $nudges = $nudgesStmt->fetchAll();

        // Mapear toques por id de amigo para búsqueda O(1)
        $nudgeMap = [];
        foreach ($nudges as $n) {
            $nudgeMap[(int)$n['receiver_id']] = $n['last_nudge_date'];
        }

        sendJsonResponse([
            'success' => true,
            'friends' => array_map(function($f) use ($nudgeMap) {
                $friendId = (int)$f['id'];
                $tz = $f['timezone'] ?? 'UTC';
                $friendToday = DateUtils::getUserToday($tz);
                $friendYesterday = DateUtils::getUserYesterday($tz);

                $streakCount = (int)$f['streak_count'];
                $lastRead = $f['last_read_date'];
                $hasReadToday = (!empty($lastRead) && $lastRead === $friendToday);
                $isStreakLost = ($streakCount === 0 || empty($lastRead) || ($lastRead !== $friendToday && $lastRead !== $friendYesterday));

                $lastNudgeDate = $nudgeMap[$friendId] ?? null;
                $nudgedToday = (!empty($lastNudgeDate) && $lastNudgeDate === $friendToday);

                return [
                    'id'               => $friendId,
                    'display_name'     => $f['display_name'],
                    'streak_count'     => $streakCount,
                    'max_streak_count' => (int)$f['max_streak_count'],
                    'last_read_date'   => $lastRead,
                    'invite_code'      => $f['invite_code'],
                    'nudged_today'     => $nudgedToday,
                    'has_read_today'   => $hasReadToday,
                    'is_streak_lost'   => $isStreakLost
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
        $targetStmt = $db->prepare("SELECT id, display_name FROM users WHERE invite_code = ?");
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

        // Enviar notificación Push al amigo vía FCM en todos sus dispositivos
        FCMService::sendPushNotificationToUser(
            $friendId,
            '¡Nuevo Amigo en Biblingo! 🎉',
            "{$myDisplayName} te ha agregado a sus amigos. ¡Compite por la mejor racha!",
            ['type' => 'friend_added', 'user_id' => $userId]
        );

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
        $today = date('Y-m-d');

        // 1. Verificar INMEDIATAMENTE si ya se envió un recordatorio hoy (búsqueda directa por índice en friend_nudges)
        $nudgeCheck = $db->prepare("SELECT 1 FROM friend_nudges WHERE sender_id = ? AND receiver_id = ? AND nudge_date = ?");
        $nudgeCheck->execute([$userId, $friendId, $today]);
        if ($nudgeCheck->fetch()) {
            sendJsonResponse(['error' => 'Ya le enviaste un recordatorio a este amigo hoy. ⏳'], 400);
        }

        // 2. Obtener datos del amigo y validar relación de amistad en 1 sola consulta
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.last_read_date, u.timezone, f.user_id AS is_friend
            FROM users u
            LEFT JOIN friendships f ON f.user_id = ? AND f.friend_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId, $friendId]);
        $friend = $stmt->fetch();

        if (!$friend || empty($friend['id'])) {
            sendJsonResponse(['error' => 'Amigo no encontrado.'], 404);
        }

        if (empty($friend['is_friend'])) {
            sendJsonResponse(['error' => 'No tienes agregada a esta persona como amiga.'], 403);
        }

        $friendTz = $friend['timezone'] ?? 'UTC';
        $friendToday = DateUtils::getUserToday($friendTz);

        // Si el día local del amigo difiere de hoy en el servidor, verificar con su fecha local exacta
        if ($friendToday !== $today) {
            $preciseCheck = $db->prepare("SELECT 1 FROM friend_nudges WHERE sender_id = ? AND receiver_id = ? AND nudge_date = ?");
            $preciseCheck->execute([$userId, $friendId, $friendToday]);
            if ($preciseCheck->fetch()) {
                sendJsonResponse(['error' => "Ya le enviaste un recordatorio a {$friend['display_name']} hoy. ⏳"], 400);
            }
        }

        // 4. Verificar si el amigo ya leyó en su día local
        if (!empty($friend['last_read_date']) && $friend['last_read_date'] === $friendToday) {
            sendJsonResponse(['error' => "{$friend['display_name']} ya completó su lectura de hoy."], 400);
        }

        // 5. Registrar el toque en la base de datos
        try {
            $insertNudge = $db->prepare("INSERT INTO friend_nudges (sender_id, receiver_id, nudge_date) VALUES (?, ?, ?)");
            $insertNudge->execute([$userId, $friendId, $friendToday]);
        } catch (Exception $e) {
            sendJsonResponse(['error' => "Ya le enviaste un recordatorio a {$friend['display_name']} hoy. ⏳"], 400);
        }

        // 6. Obtener nombre del remitente y enviar notificación Push vía FCM
        $meStmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $meStmt->execute([$userId]);
        $me = $meStmt->fetch();
        $myDisplayName = $me ? $me['display_name'] : 'Un amigo';

        // 6. Obtener nombre del remitente y enviar notificación Push a todos sus dispositivos
        FCMService::sendPushNotificationToUser(
            $friendId,
            '📖 Recordatorio de lectura',
            "{$myDisplayName} te ha enviado un recordatorio para que leas hoy y protejas tu racha. 🔥",
            ['type' => 'nudge', 'sender_id' => $userId]
        );

        sendJsonResponse([
            'success'   => true,
            'message'   => "¡Le enviaste un recordatorio a {$friend['display_name']}! 🔔",
            'friend_id' => $friendId
        ]);
    }
}
