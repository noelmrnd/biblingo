<?php

declare(strict_types=1);

namespace Biblingo\Controllers;

use Biblingo\Events\FriendAddedEvent;
use Biblingo\Events\FriendNudgedEvent;
use Biblingo\Services\DomainEventStore;
use Biblingo\Utils\DateUtils;
use Biblingo\Utils\SnowflakeId;
use Biblingo\Utils\StreakUtils;

class FriendController {
    /**
     * Lista de personas que el usuario sigue (ranking), mas su propia fila. El
     * seguimiento es asimetrico (estilo Duolingo): is_mutual indica si tambien
     * lo siguen de vuelta, condicion que habilita el toque y otras funciones sociales.
     */
    public static function getFriends(string $userId) {
        $db = getDbConnection();

        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.invite_code, u.timezone,
                   0 AS is_self,
                   EXISTS(SELECT 1 FROM follows fb WHERE fb.follower_id = u.id AND fb.followed_id = ?) AS is_mutual
            FROM follows f
            JOIN users u ON f.followed_id = u.id
            WHERE f.follower_id = ?
            UNION ALL
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.invite_code, u.timezone,
                   1 AS is_self, 1 AS is_mutual
            FROM users u
            WHERE u.id = ?
            ORDER BY streak_count DESC, display_name ASC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        $following = $stmt->fetchAll();

        $nudgesStmt = $db->prepare("
            SELECT receiver_id, MAX(nudge_date) AS last_nudge_date
            FROM friend_nudges
            WHERE sender_id = ?
            GROUP BY receiver_id
        ");
        $nudgesStmt->execute([$userId]);
        $nudgeMap = [];
        foreach ($nudgesStmt->fetchAll() as $n) {
            $nudgeMap[(string)$n['receiver_id']] = $n['last_nudge_date'];
        }

        sendJsonResponse([
            'success' => true,
            'friends' => array_map(function ($f) use ($nudgeMap) {
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
                    'is_self'          => (bool)$f['is_self'],
                    'is_mutual'        => (bool)$f['is_mutual'],
                ];
            }, $following)
        ]);
    }

    /**
     * Seguir a alguien por su codigo de invitacion. Instantaneo, sin aprobacion
     * (a diferencia del viejo flujo de solicitudes) — igual que seguir en Duolingo.
     */
    public static function follow(string $userId) {
        $input = getJsonInput();
        $inviteCode = strtoupper(trim($input['invite_code'] ?? ''));

        if (empty($inviteCode)) {
            sendJsonResponse(['error' => 'Código de invitación requerido.'], 400);
        }

        $db = getDbConnection();

        $targetStmt = $db->prepare("SELECT id, display_name FROM users WHERE invite_code = ?");
        $targetStmt->execute([$inviteCode]);
        $target = $targetStmt->fetch();

        if (!$target) {
            sendJsonResponse(['error' => 'No se encontró ningún usuario con ese código de invitación.'], 404);
        }

        $targetId = (string)$target['id'];

        if ($targetId === $userId) {
            sendJsonResponse(['error' => 'No puedes seguirte a ti mismo.'], 400);
        }

        if (self::isFollowing($db, $userId, $targetId)) {
            sendJsonResponse(['error' => "Ya sigues a {$target['display_name']}."], 400);
        }

        $meStmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $meStmt->execute([$userId]);
        $me = $meStmt->fetch();
        $myDisplayName = $me['display_name'] ?? 'Un usuario';

        $wasFollowedByTarget = self::isFollowing($db, $targetId, $userId);

        try {
            $db->beginTransaction();

            $insert = $db->prepare("INSERT IGNORE INTO follows (id, follower_id, followed_id) VALUES (?, ?, ?)");
            $insert->execute([SnowflakeId::nextId(), $userId, $targetId]);

            $event = new FriendAddedEvent($userId, $targetId, $myDisplayName);
            DomainEventStore::record($event, $db);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            sendJsonResponse(['error' => 'Error al seguir usuario: ' . $e->getMessage()], 500);
        }

        sendJsonResponse([
            'success'   => true,
            'is_mutual' => $wasFollowedByTarget,
            'message'   => $wasFollowedByTarget
                ? "¡Ahora tú y {$target['display_name']} se siguen mutuamente! 🎉"
                : "¡Ahora sigues a {$target['display_name']}! 👥",
            'friend'    => [
                'id'           => $targetId,
                'display_name' => $target['display_name']
            ]
        ]);
    }

    public static function unfollow(string $userId) {
        $input = getJsonInput();
        $friendId = $input['friend_id'] ?? ($_GET['friend_id'] ?? null);

        if (empty($friendId)) {
            sendJsonResponse(['error' => 'friend_id es requerido.'], 400);
        }

        $friendId = (string)$friendId;

        if ($friendId === $userId) {
            sendJsonResponse(['error' => 'No puedes dejar de seguirte a ti mismo.'], 400);
        }

        $db = getDbConnection();
        $del = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $del->execute([$userId, $friendId]);

        sendJsonResponse([
            'success'   => true,
            'message'   => 'Dejaste de seguir a este usuario.',
            'friend_id' => $friendId
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

        $nudgeCheck = $db->prepare("SELECT 1 FROM friend_nudges WHERE sender_id = ? AND receiver_id = ? AND nudge_date = ?");
        $nudgeCheck->execute([$userId, $friendId, $today]);
        if ($nudgeCheck->fetch()) {
            sendJsonResponse(['error' => 'Ya le enviaste un recordatorio a este amigo hoy. ⏳'], 400);
        }

        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.last_read_date, u.timezone,
                   EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = u.id) AS i_follow,
                   EXISTS(SELECT 1 FROM follows WHERE follower_id = u.id AND followed_id = ?) AS follows_me
            FROM users u
            WHERE u.id = ?
        ");
        $stmt->execute([$userId, $userId, $friendId]);
        $friend = $stmt->fetch();

        if (!$friend || empty($friend['id'])) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        if (!$friend['i_follow'] || !$friend['follows_me']) {
            sendJsonResponse(['error' => 'Solo puedes dar un toque a alguien que te sigue mutuamente.'], 403);
        }

        $friendTz = $friend['timezone'] ?? 'UTC';
        $friendToday = DateUtils::getUserToday($friendTz);

        if ($friendToday !== $today) {
            $preciseCheck = $db->prepare("SELECT 1 FROM friend_nudges WHERE sender_id = ? AND receiver_id = ? AND nudge_date = ?");
            $preciseCheck->execute([$userId, $friendId, $friendToday]);
            if ($preciseCheck->fetch()) {
                sendJsonResponse(['error' => "Ya le enviaste un recordatorio a {$friend['display_name']} hoy. ⏳"], 400);
            }
        }

        if (!empty($friend['last_read_date']) && $friend['last_read_date'] === $friendToday) {
            sendJsonResponse(['error' => "{$friend['display_name']} ya completó su lectura de hoy."], 400);
        }

        $meStmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $meStmt->execute([$userId]);
        $me = $meStmt->fetch();
        if (!$me) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }
        $myDisplayName = $me['display_name'];

        try {
            $db->beginTransaction();

            $nudgeId = SnowflakeId::nextId();
            $insertNudge = $db->prepare("INSERT INTO friend_nudges (id, sender_id, receiver_id, nudge_date) VALUES (?, ?, ?, ?)");
            $insertNudge->execute([$nudgeId, $userId, $friendId, $friendToday]);

            $event = new FriendNudgedEvent($userId, $friendId, $myDisplayName, $friendToday);
            DomainEventStore::record($event, $db);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            sendJsonResponse(['error' => "Ya le enviaste un recordatorio a {$friend['display_name']} hoy. ⏳"], 400);
        }

        sendJsonResponse([
            'success'   => true,
            'message'   => "¡Le enviaste un recordatorio a {$friend['display_name']}! 🔔",
            'friend_id' => $friendId
        ]);
    }

    /**
     * Perfil completo de un usuario (o el propio, si friendId === userId) en una sola
     * llamada: stats + historial de 30 dias para el tracker semanal + contadores de
     * seguidores/seguidos + amigos en comun.
     */
    public static function getFriendProfile(string $userId, string $friendId) {
        $db = getDbConnection();
        $isSelf = ($userId === $friendId);

        $isFollowing = $isSelf ? true : self::isFollowing($db, $userId, $friendId);
        $isFollowedBy = $isSelf ? true : self::isFollowing($db, $friendId, $userId);

        if (!$isSelf && !$isFollowing && !$isFollowedBy) {
            sendJsonResponse(['error' => 'No tienes permiso para ver el perfil de este usuario.'], 403);
        }

        $friend = self::fetchUserRow($db, $friendId);
        if (!$friend) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $status = StreakUtils::computeStatus($friend['last_read_date'], (int)$friend['streak_count'], $friend['timezone']);
        $isMutual = $isFollowing && $isFollowedBy;

        sendJsonResponse([
            'success' => true,
            'user' => [
                'id'               => $friendId,
                'display_name'     => $friend['display_name'],
                'streak_count'     => (int)$friend['streak_count'],
                'max_streak_count' => (int)$friend['max_streak_count'],
                'last_read_date'   => $friend['last_read_date'],
                'last_read_label'  => $status->lastReadLabel,
                'has_read_today'   => $status->hasReadToday,
                'is_streak_lost'   => $status->isStreakLost,
                'total_days_read'  => self::countTotalDaysRead($db, $friendId),
                'reaction_counts'  => self::countReactions($db, $friendId),
                'member_since'     => substr((string)$friend['created_at'], 0, 10),
                'followers_count'  => self::countFollowers($db, $friendId),
                'following_count'  => self::countFollowing($db, $friendId),
                'is_following'     => $isFollowing,
                'is_followed_by'   => $isFollowedBy,
                'is_mutual'        => $isMutual,
            ],
            'history'              => self::fetchHistory($db, $friendId, $status->today),
            'nudged_today'         => ($isSelf || !$isMutual) ? false : self::wasNudgedToday($db, $userId, $friendId, $status->today),
            'mutual_friends_count' => $isSelf ? 0 : self::countMutualFriends($db, $userId, $friendId),
        ]);
    }

    /**
     * Lista de seguidores o seguidos de cualquier usuario. Publica, como en Duolingo:
     * cualquier usuario autenticado puede consultar la lista de cualquier otro.
     */
    public static function getFollowList(string $userId, string $targetId, string $type) {
        if (!in_array($type, ['followers', 'following'], true)) {
            sendJsonResponse(['error' => 'type debe ser "followers" o "following".'], 400);
        }

        $db = getDbConnection();

        if ($type === 'followers') {
            $stmt = $db->prepare("
                SELECT u.id, u.display_name, u.streak_count
                FROM follows f
                JOIN users u ON f.follower_id = u.id
                WHERE f.followed_id = ?
                ORDER BY u.streak_count DESC, u.display_name ASC
            ");
        } else {
            $stmt = $db->prepare("
                SELECT u.id, u.display_name, u.streak_count
                FROM follows f
                JOIN users u ON f.followed_id = u.id
                WHERE f.follower_id = ?
                ORDER BY u.streak_count DESC, u.display_name ASC
            ");
        }
        $stmt->execute([$targetId]);
        $rows = $stmt->fetchAll();

        sendJsonResponse([
            'success' => true,
            'users' => array_map(function ($r) use ($db, $userId) {
                $id = (string)$r['id'];
                $isSelf = ($id === $userId);
                return [
                    'id'           => $id,
                    'display_name' => $r['display_name'],
                    'streak_count' => (int)$r['streak_count'],
                    'is_self'      => $isSelf,
                    'is_following' => $isSelf ? true : self::isFollowing($db, $userId, $id),
                ];
            }, $rows)
        ]);
    }

    public static function isFollowing(\PDO $db, string $followerId, string $followedId): bool {
        $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$followerId, $followedId]);
        return (bool)$stmt->fetch();
    }

    public static function countFollowers(\PDO $db, string $userId): int {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM follows WHERE followed_id = ?");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public static function countFollowing(\PDO $db, string $userId): int {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM follows WHERE follower_id = ?");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    private static function fetchUserRow(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT display_name, streak_count, max_streak_count, last_read_date, timezone, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    private static function countTotalDaysRead(\PDO $db, string $userId): int {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM reading_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    /** Conteo de reacciones registradas por dia de lectura, ej. {"loved": 12, "peaceful": 5}. */
    public static function countReactions(\PDO $db, string $userId): array {
        $stmt = $db->prepare("
            SELECT reaction, COUNT(*) AS total
            FROM reading_logs
            WHERE user_id = ? AND reaction IS NOT NULL
            GROUP BY reaction
        ");
        $stmt->execute([$userId]);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['reaction']] = (int)$row['total'];
        }
        return $counts;
    }

    /** Historial de 30 dias de lectura, usado para el tracker semanal. */
    private static function fetchHistory(\PDO $db, string $userId, string $today): array {
        $stmt = $db->prepare("
            SELECT read_date FROM reading_logs
            WHERE user_id = ? AND read_date >= DATE_SUB(?, INTERVAL 30 DAY)
            ORDER BY read_date DESC
        ");
        $stmt->execute([$userId, $today]);
        return array_column($stmt->fetchAll(), 'read_date');
    }

    private static function wasNudgedToday(\PDO $db, string $userId, string $friendId, string $today): bool {
        $stmt = $db->prepare("SELECT 1 FROM friend_nudges WHERE sender_id = ? AND receiver_id = ? AND nudge_date = ?");
        $stmt->execute([$userId, $friendId, $today]);
        return (bool)$stmt->fetch();
    }

    /** Cuenta cuantas personas tienen seguimiento mutuo con ambos usuarios ("amigos en comun"). */
    private static function countMutualFriends(\PDO $db, string $userId, string $friendId): int {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total FROM (
                SELECT f1.followed_id AS uid
                FROM follows f1
                JOIN follows f1b ON f1b.follower_id = f1.followed_id AND f1b.followed_id = f1.follower_id
                WHERE f1.follower_id = ?
            ) mutual_a
            JOIN (
                SELECT f2.followed_id AS uid
                FROM follows f2
                JOIN follows f2b ON f2b.follower_id = f2.followed_id AND f2b.followed_id = f2.follower_id
                WHERE f2.follower_id = ?
            ) mutual_b ON mutual_a.uid = mutual_b.uid
            WHERE mutual_a.uid NOT IN (?, ?)
        ");
        $stmt->execute([$userId, $friendId, $userId, $friendId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }
}
