<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Services/DomainEventStore.php';
require_once __DIR__ . '/../Events/FriendAddedEvent.php';
require_once __DIR__ . '/../Events/FriendNudgedEvent.php';
require_once __DIR__ . '/../Events/FriendRequestSentEvent.php';
require_once __DIR__ . '/../Events/FriendRequestAcceptedEvent.php';

class FriendController {
    public static function getFriends(string $userId) {
        $db = getDbConnection();

        // 1. Obtener lista de amigos del usuario, incluyendo al propio usuario, ya ordenados
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.invite_code, u.timezone,
                   0 AS is_self
            FROM friendships f
            JOIN users u ON f.friend_id = u.id
            WHERE f.user_id = ?
            UNION ALL
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.invite_code, u.timezone,
                   1 AS is_self
            FROM users u
            WHERE u.id = ?
            ORDER BY streak_count DESC, display_name ASC
        ");
        $stmt->execute([$userId, $userId]);
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
            $nudgeMap[(string)$n['receiver_id']] = $n['last_nudge_date'];
        }

        sendJsonResponse([
            'success' => true,
            'friends' => array_map(function($f) use ($nudgeMap) {
                $friendId = (string)$f['id'];
                $streakCount = (int)$f['streak_count'];
                $lastRead = $f['last_read_date'];
                $status = StreakUtils::computeStatus($lastRead, $streakCount, $f['timezone']);

                $lastNudgeDate = $nudgeMap[$friendId] ?? null;
                $nudgedToday = (!empty($lastNudgeDate) && $lastNudgeDate === $status->today);

                return [
                    'id'               => $friendId,
                    'display_name'     => $f['display_name'],
                    'streak_count'     => $streakCount,
                    'max_streak_count' => (int)$f['max_streak_count'],
                    'last_read_date'   => $lastRead,
                    'last_read_label'  => $status->lastReadLabel,
                    'invite_code'      => $f['invite_code'],
                    'nudged_today'     => $nudgedToday,
                    'has_read_today'   => $status->hasReadToday,
                    'is_streak_lost'   => $status->isStreakLost,
                    'is_self'          => (bool)$f['is_self']
                ];
            }, $friends)
        ]);
    }

    public static function addFriend(string $userId) {
        self::sendFriendRequest($userId);
    }

    public static function sendFriendRequest(string $userId) {
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

        $friendId = (string)$friend['id'];

        if ($friendId === $userId) {
            sendJsonResponse(['error' => 'No puedes enviarte una solicitud a ti mismo.'], 400);
        }

        // 1. Verificar si ya son amigos
        $friendCheck = $db->prepare("SELECT 1 FROM friendships WHERE user_id = ? AND friend_id = ?");
        $friendCheck->execute([$userId, $friendId]);
        if ($friendCheck->fetch()) {
            sendJsonResponse(['error' => 'Ya tienes a esta persona en tu lista de amigos.'], 400);
        }

        // Obtener datos del usuario actual para el mensaje
        $meStmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $meStmt->execute([$userId]);
        $me = $meStmt->fetch();
        if (!$me) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }
        $myDisplayName = $me['display_name'];

        // 2. Si la otra persona ya me había enviado una solicitud previa (mutua), auto-aceptar
        $inverseCheck = $db->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
        $inverseCheck->execute([$friendId, $userId]);
        $existingInverse = $inverseCheck->fetch();

        if ($existingInverse) {
            try {
                $db->beginTransaction();

                $insert1 = $db->prepare("INSERT IGNORE INTO friendships (id, user_id, friend_id) VALUES (?, ?, ?)");
                $insert1->execute([SnowflakeId::nextId(), $userId, $friendId]);

                $insert2 = $db->prepare("INSERT IGNORE INTO friendships (id, user_id, friend_id) VALUES (?, ?, ?)");
                $insert2->execute([SnowflakeId::nextId(), $friendId, $userId]);

                $del = $db->prepare("DELETE FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
                $del->execute([$userId, $friendId, $friendId, $userId]);

                $event = new FriendRequestAcceptedEvent($userId, $friendId, $myDisplayName);
                DomainEventStore::record($event, $db);

                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                sendJsonResponse(['error' => 'Error al aceptar solicitud: ' . $e->getMessage()], 500);
            }

            sendJsonResponse([
                'success'       => true,
                'auto_accepted' => true,
                'message'       => "¡{$friend['display_name']} te había enviado una solicitud! Ahora son amigos. 🎉",
                'friend'        => [
                    'id'           => $friendId,
                    'display_name' => $friend['display_name']
                ]
            ]);
        }

        // 3. Verificar si ya le envié una solicitud pendiente
        $pendingCheck = $db->prepare("SELECT 1 FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
        $pendingCheck->execute([$userId, $friendId]);
        if ($pendingCheck->fetch()) {
            sendJsonResponse(['error' => "Ya le enviaste una solicitud de amistad a {$friend['display_name']}. ⏳"], 400);
        }

        // 4. Crear solicitud de amistad y registrar evento de dominio
        try {
            $db->beginTransaction();

            $requestId = SnowflakeId::nextId();
            $insertReq = $db->prepare("INSERT IGNORE INTO friend_requests (id, sender_id, receiver_id) VALUES (?, ?, ?)");
            $insertReq->execute([$requestId, $userId, $friendId]);

            if ($insertReq->rowCount() === 0) {
                $db->rollBack();
                sendJsonResponse(['error' => "Ya le enviaste una solicitud de amistad a {$friend['display_name']}. ⏳"], 400);
            }

            $event = new FriendRequestSentEvent($userId, $friendId, $myDisplayName);
            DomainEventStore::record($event, $db);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            sendJsonResponse(['error' => 'Error al enviar solicitud de amistad: ' . $e->getMessage()], 500);
        }

        sendJsonResponse([
            'success' => true,
            'message' => "¡Solicitud de amistad enviada a {$friend['display_name']}! 👥",
            'friend'  => [
                'id'           => $friendId,
                'display_name' => $friend['display_name']
            ]
        ]);
    }

    public static function getFriendRequests(string $userId) {
        $db = getDbConnection();

        // 1. Solicitudes recibidas
        $recvStmt = $db->prepare("
            SELECT fr.id AS request_id, fr.sender_id, u.display_name, u.streak_count, u.last_read_date, u.invite_code, u.timezone, fr.created_at
            FROM friend_requests fr
            JOIN users u ON fr.sender_id = u.id
            WHERE fr.receiver_id = ?
            ORDER BY fr.created_at DESC
        ");
        $recvStmt->execute([$userId]);
        $received = $recvStmt->fetchAll();

        // 2. Solicitudes enviadas pendientes
        $sentStmt = $db->prepare("
            SELECT fr.id AS request_id, fr.receiver_id, u.display_name, u.streak_count, u.last_read_date, u.invite_code, u.timezone, fr.created_at
            FROM friend_requests fr
            JOIN users u ON fr.receiver_id = u.id
            WHERE fr.sender_id = ?
            ORDER BY fr.created_at DESC
        ");
        $sentStmt->execute([$userId]);
        $sent = $sentStmt->fetchAll();

        $mapReceived = array_map(function($r) {
            $tz = $r['timezone'] ?? 'UTC';
            $today = DateUtils::getUserToday($tz);
            $yesterday = DateUtils::getUserYesterday($tz);
            return [
                'request_id'      => (string)$r['request_id'],
                'sender_id'       => (string)$r['sender_id'],
                'display_name'    => $r['display_name'],
                'streak_count'    => (int)$r['streak_count'],
                'last_read_date'  => $r['last_read_date'],
                'last_read_label' => DateUtils::formatReadDateLabel($r['last_read_date'], $today, $yesterday),
                'invite_code'     => $r['invite_code'],
                'created_at'      => $r['created_at']
            ];
        }, $received);

        $mapSent = array_map(function($s) {
            $tz = $s['timezone'] ?? 'UTC';
            $today = DateUtils::getUserToday($tz);
            $yesterday = DateUtils::getUserYesterday($tz);
            return [
                'request_id'      => (string)$s['request_id'],
                'receiver_id'     => (string)$s['receiver_id'],
                'display_name'    => $s['display_name'],
                'streak_count'    => (int)$s['streak_count'],
                'last_read_date'  => $s['last_read_date'],
                'last_read_label' => DateUtils::formatReadDateLabel($s['last_read_date'], $today, $yesterday),
                'invite_code'     => $s['invite_code'],
                'created_at'      => $s['created_at']
            ];
        }, $sent);

        sendJsonResponse([
            'success'  => true,
            'requests' => $mapReceived,
            'received' => $mapReceived,
            'sent'     => $mapSent
        ]);
    }

    public static function cancelFriendRequest(string $userId) {
        $input = getJsonInput();
        $requestId = $input['request_id'] ?? null;
        $receiverId = $input['receiver_id'] ?? ($input['friend_id'] ?? null);

        if (!$requestId && !$receiverId) {
            sendJsonResponse(['error' => 'request_id o receiver_id es requerido.'], 400);
        }

        $db = getDbConnection();

        $del = $db->prepare("DELETE FROM friend_requests WHERE sender_id = ? AND (id = ? OR receiver_id = ?)");
        $del->execute([$userId, $requestId, $receiverId]);

        sendJsonResponse([
            'success' => true,
            'message' => 'Solicitud de amistad cancelada.'
        ]);
    }

    public static function acceptFriendRequest(string $userId) {
        $input = getJsonInput();
        $requestId = $input['request_id'] ?? null;
        $senderId = $input['sender_id'] ?? null;

        if (!$requestId && !$senderId) {
            sendJsonResponse(['error' => 'request_id o sender_id es requerido.'], 400);
        }

        $db = getDbConnection();

        // Buscar solicitud pendiente dirigida a este usuario
        $stmt = $db->prepare("
            SELECT fr.id, fr.sender_id, u.display_name
            FROM friend_requests fr
            JOIN users u ON fr.sender_id = u.id
            WHERE fr.receiver_id = ? AND (fr.id = ? OR fr.sender_id = ?)
        ");
        $stmt->execute([$userId, $requestId, $senderId]);
        $req = $stmt->fetch();

        if (!$req) {
            sendJsonResponse(['error' => 'Solicitud de amistad no encontrada o ya procesada.'], 404);
        }

        $senderId = (string)$req['sender_id'];
        $senderName = $req['display_name'];

        // Obtener nombre del usuario que acepta
        $meStmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $meStmt->execute([$userId]);
        $me = $meStmt->fetch();
        $myDisplayName = $me['display_name'] ?? 'Un usuario';

        try {
            $db->beginTransaction();

            // 1. Insertar relación de amistad bidireccional
            $insert1 = $db->prepare("INSERT IGNORE INTO friendships (id, user_id, friend_id) VALUES (?, ?, ?)");
            $insert1->execute([SnowflakeId::nextId(), $userId, $senderId]);

            $insert2 = $db->prepare("INSERT IGNORE INTO friendships (id, user_id, friend_id) VALUES (?, ?, ?)");
            $insert2->execute([SnowflakeId::nextId(), $senderId, $userId]);

            // 2. Eliminar cualquier solicitud pendiente entre ambos
            $del = $db->prepare("DELETE FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $del->execute([$userId, $senderId, $senderId, $userId]);

            // 3. Registrar evento de dominio para notificar al remitente
            $event = new FriendRequestAcceptedEvent($userId, $senderId, $myDisplayName);
            DomainEventStore::record($event, $db);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            sendJsonResponse(['error' => 'Error al aceptar solicitud: ' . $e->getMessage()], 500);
        }

        sendJsonResponse([
            'success' => true,
            'message' => "¡Has aceptado la solicitud de {$senderName}! 🎉",
            'friend'  => [
                'id'           => $senderId,
                'display_name' => $senderName
            ]
        ]);
    }

    public static function rejectFriendRequest(string $userId) {
        $input = getJsonInput();
        $requestId = $input['request_id'] ?? null;
        $senderId = $input['sender_id'] ?? null;

        if (!$requestId && !$senderId) {
            sendJsonResponse(['error' => 'request_id o sender_id es requerido.'], 400);
        }

        $db = getDbConnection();

        $del = $db->prepare("DELETE FROM friend_requests WHERE receiver_id = ? AND (id = ? OR sender_id = ?)");
        $del->execute([$userId, $requestId, $senderId]);

        sendJsonResponse([
            'success' => true,
            'message' => 'Solicitud de amistad rechazada.'
        ]);
    }

    public static function nudgeFriend(string $userId) {
        $input = getJsonInput();
        $friendId = $input['friend_id'] ?: null;

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

        // 5. Obtener nombre del remitente
        $meStmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $meStmt->execute([$userId]);
        $me = $meStmt->fetch();
        if (!$me) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }
        $myDisplayName = $me['display_name'];

        // 6. Registrar el toque y persistir evento de dominio en MySQL
        try {
            $db->beginTransaction();

            $nudgeId = SnowflakeId::nextId();
            $insertNudge = $db->prepare("INSERT INTO friend_nudges (id, sender_id, receiver_id, nudge_date) VALUES (?, ?, ?, ?)");
            $insertNudge->execute([$nudgeId, $userId, $friendId, $friendToday]);

            // Crear y persistir evento de dominio
            $event = new FriendNudgedEvent($userId, $friendId, $myDisplayName, $friendToday);
            DomainEventStore::record($event, $db);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            sendJsonResponse(['error' => "Ya le enviaste un recordatorio a {$friend['display_name']} hoy. ⏳"], 400);
        }

        sendJsonResponse([
            'success'   => true,
            'message'   => "¡Le enviaste un recordatorio a {$friend['display_name']}! 🔔",
            'friend_id' => $friendId
        ]);
    }

    public static function removeFriend(string $userId) {
        $input = getJsonInput();
        $friendId = $input['friend_id'] ?? ($_GET['friend_id'] ?? null);

        if (empty($friendId)) {
            sendJsonResponse(['error' => 'friend_id es requerido.'], 400);
        }

        $friendId = (string)$friendId;

        if ($friendId === $userId) {
            sendJsonResponse(['error' => 'No puedes eliminarte a ti mismo.'], 400);
        }

        $db = getDbConnection();

        try {
            $db->beginTransaction();

            // Eliminar la relación de amistad en ambas direcciones
            $deleteFriendship = $db->prepare("
                DELETE FROM friendships 
                WHERE (user_id = ? AND friend_id = ?) 
                   OR (user_id = ? AND friend_id = ?)
            ");
            $deleteFriendship->execute([$userId, $friendId, $friendId, $userId]);

            // Eliminar los toques intercambiados entre ambos usuarios
            $deleteNudges = $db->prepare("
                DELETE FROM friend_nudges 
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?)
            ");
            $deleteNudges->execute([$userId, $friendId, $friendId, $userId]);

            // Eliminar cualquier solicitud de amistad pendiente entre ambos
            $deleteRequests = $db->prepare("
                DELETE FROM friend_requests 
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?)
            ");
            $deleteRequests->execute([$userId, $friendId, $friendId, $userId]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            sendJsonResponse(['error' => 'Error al eliminar amigo: ' . $e->getMessage()], 500);
        }

        sendJsonResponse([
            'success'   => true,
            'message'   => 'Amigo eliminado correctamente.',
            'friend_id' => $friendId
        ]);
    }

    public static function getFriendHistory(string $userId, string $friendId) {
        $db = getDbConnection();

        $friendCheck = $db->prepare("SELECT 1 FROM friendships WHERE user_id = ? AND friend_id = ?");
        $friendCheck->execute([$userId, $friendId]);
        if (!$friendCheck->fetch() && $userId !== $friendId) {
            sendJsonResponse(['error' => 'No tienes permiso para ver el historial de este usuario.'], 403);
        }

        $stmt = $db->prepare("SELECT last_read_date, timezone FROM users WHERE id = ?");
        $stmt->execute([$friendId]);
        $friend = $stmt->fetch();

        if (!$friend) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $tz = $friend['timezone'] ?? 'UTC';
        $today = DateUtils::getUserToday($tz);

        $logsStmt = $db->prepare("
            SELECT read_date FROM reading_logs
            WHERE user_id = ? AND read_date >= DATE_SUB(?, INTERVAL 30 DAY)
            ORDER BY read_date DESC
        ");
        $logsStmt->execute([$friendId, $today]);
        $historyDates = array_column($logsStmt->fetchAll(), 'read_date');

        sendJsonResponse([
            'success'        => true,
            'has_read_today' => $friend['last_read_date'] === $today,
            'history'        => $historyDates,
        ]);
    }
}

