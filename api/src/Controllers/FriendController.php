<?php

declare(strict_types=1);

namespace Biblingo\Controllers;

use Biblingo\Entities\FollowEntity;
use Biblingo\Entities\FriendNudgeEntity;
use Biblingo\Entities\ReadingLogEntity;
use Biblingo\Entities\UserEntity;
use Biblingo\Events\FriendAddedEvent;
use Biblingo\Events\FriendNudgedEvent;
use Biblingo\Services\DomainEventStore;
use Biblingo\Utils\DateUtils;
use Biblingo\Utils\RateLimiter;
use Biblingo\Utils\SnowflakeId;
use Biblingo\Utils\StreakUtils;

/**
 * Modelo de autorizacion de este controller (todo requiere sesion valida via
 * Auth::requireUser(), eso ya lo filtra index.php; esto es sobre datos de OTROS
 * usuarios dentro de una request ya autenticada):
 *
 * - Publico entre usuarios autenticados, estilo Duolingo — no hace falta seguir
 *   a alguien ni que te siga para ver sus datos: getFriendProfile, getFollowList.
 * - Requiere seguimiento mutuo (i_follow Y follows_me): nudgeFriend.
 * - Solo el dueño de los datos: getFriends (propio ranking/lista de seguidos).
 *
 * follow()/unfollow() no exponen datos de otro usuario, solo actuan sobre la
 * relacion del propio $userId.
 */
class FriendController {
    /**
     * Lista de personas que el usuario sigue (ranking), mas su propia fila. El
     * seguimiento es asimetrico (estilo Duolingo): is_mutual indica si tambien
     * lo siguen de vuelta, condicion que habilita el toque y otras funciones sociales.
     */
    public static function getFriends(string $userId) {
        $db = getDbConnection();

        $following = FollowEntity::fetchFollowingWithSelf($db, $userId);

        $nudgeRows = FriendNudgeEntity::fetchLastNudgeMap($db, $userId);
        $nudgeMap = [];
        foreach ($nudgeRows as $n) {
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
                    'username'         => $f['username'],
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
     * Seguir a alguien por su username. Instantaneo, sin aprobacion (a diferencia
     * del viejo flujo de solicitudes) — igual que seguir en Duolingo.
     */
    public static function follow(string $userId) {
        $input = getJsonInput();
        $username = strtolower(trim($input['username'] ?? ''));

        if (empty($username)) {
            sendJsonResponse(['error' => 'Nombre de usuario requerido.'], 400);
        }

        $db = getDbConnection();

        $target = UserEntity::findByUsername($db, $username);

        if (!$target) {
            sendJsonResponse(['error' => 'El usuario no fue encontrado.'], 404);
        }

        $targetId = (string)$target['id'];

        if ($targetId === $userId) {
            sendJsonResponse(['error' => 'No puedes seguirte a ti mismo.'], 400);
        }

        if (self::isFollowing($db, $userId, $targetId)) {
            sendJsonResponse(['error' => "Ya sigues a {$target['display_name']}."], 400);
        }

        $me = UserEntity::getDisplayName($db, $userId);
        $myDisplayName = $me['display_name'] ?? 'Un usuario';

        $wasFollowedByTarget = self::isFollowing($db, $targetId, $userId);

        try {
            $db->beginTransaction();

            FollowEntity::insertFollow($db, (string)SnowflakeId::nextId(), $userId, $targetId);

            $event = new FriendAddedEvent($userId, $targetId, $myDisplayName);
            DomainEventStore::record($event, $db);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('[FriendController::follow] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error al seguir usuario.'], 500);
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
        FollowEntity::deleteFollow($db, $userId, $friendId);

        sendJsonResponse([
            'success'   => true,
            'message'   => 'Dejaste de seguir a este usuario.',
            'friend_id' => $friendId
        ]);
    }

    public static function nudgeFriend(string $userId) {
        // El limite de "1 toque por amigo por dia" ya lo impone friend_nudges, pero
        // no frena a alguien con muchos amigos mandando decenas en rafaga. Tope aparte
        // por volumen total.
        if (!RateLimiter::allow('nudge', $userId, 3600, 30)) {
            sendJsonResponse(['error' => 'Enviaste demasiados toques. Espera un momento.'], 429);
        }

        $input = getJsonInput();
        $friendId = $input['friend_id'] ?: null;

        if (!$friendId) {
            sendJsonResponse(['error' => 'friend_id es requerido.'], 400);
        }

        $db = getDbConnection();
        $today = date('Y-m-d');

        if (FriendNudgeEntity::wasNudgedOn($db, $userId, $friendId, $today)) {
            sendJsonResponse(['error' => 'Ya le enviaste un recordatorio a este amigo hoy. ⏳'], 400);
        }

        $friend = self::fetchNudgeTargetRow($db, $userId, $friendId);

        if (!$friend || empty($friend['id'])) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        if (!$friend['i_follow'] || !$friend['follows_me']) {
            sendJsonResponse(['error' => 'Solo puedes dar un toque a alguien que te sigue mutuamente.'], 403);
        }

        $friendTz = $friend['timezone'] ?? 'UTC';
        $friendToday = DateUtils::getUserToday($friendTz);

        if ($friendToday !== $today && FriendNudgeEntity::wasNudgedOn($db, $userId, $friendId, $friendToday)) {
            sendJsonResponse(['error' => "Ya le enviaste un recordatorio a {$friend['display_name']} hoy. ⏳"], 400);
        }

        if (!empty($friend['last_read_date']) && $friend['last_read_date'] === $friendToday) {
            sendJsonResponse(['error' => "{$friend['display_name']} ya completó su lectura de hoy."], 400);
        }

        $me = UserEntity::getDisplayName($db, $userId);
        if (!$me) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }
        $myDisplayName = $me['display_name'];

        try {
            $db->beginTransaction();

            $nudgeId = (string)SnowflakeId::nextId();
            FriendNudgeEntity::insert($db, $nudgeId, $userId, $friendId, $friendToday);

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

    private static function fetchNudgeTargetRow(\PDO $db, string $userId, string $friendId): array|false {
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.last_read_date, u.timezone,
                   EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = u.id) AS i_follow,
                   EXISTS(SELECT 1 FROM follows WHERE follower_id = u.id AND followed_id = ?) AS follows_me
            FROM users u
            WHERE u.id = ? AND u.status = 'active'
        ");
        $stmt->execute([$userId, $userId, $friendId]);
        return $stmt->fetch();
    }

    /**
     * Perfil completo de un usuario (o el propio, si friendId === userId) en una sola
     * llamada: stats + historial de 30 dias para el tracker semanal + contadores de
     * seguidores/seguidos + amigos en comun.
     */
    public static function getFriendProfile(string $userId, string $friendId) {
        $db = getDbConnection();
        $isSelf = ($userId === $friendId);

        // Publico, como en Duolingo: cualquier usuario autenticado puede ver el perfil
        // de cualquier otro, siga o no lo siga (igual que getFollowList).
        $isFollowing = $isSelf ? true : self::isFollowing($db, $userId, $friendId);
        $isFollowedBy = $isSelf ? true : self::isFollowing($db, $friendId, $userId);

        $friend = UserEntity::getProfileRow($db, $friendId);
        if (!$friend) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $status = StreakUtils::computeStatus($friend['last_read_date'], (int)$friend['streak_count'], $friend['timezone']);
        $isMutual = $isFollowing && $isFollowedBy;

        sendJsonResponse([
            'success' => true,
            'user' => [
                'id'                  => $friendId,
                'display_name'        => $friend['display_name'],
                'username'            => $friend['username'],
                'streak_count'        => (int)$friend['streak_count'],
                'max_streak_count'    => (int)$friend['max_streak_count'],
                'last_read_date'      => $friend['last_read_date'],
                'last_read_label'     => $status->lastReadLabel,
                'has_read_today'      => $status->hasReadToday,
                'is_streak_lost'      => $status->isStreakLost,
                'total_days_read'     => ReadingLogEntity::countTotalDaysRead($db, $friendId),
                'reaction_counts'     => self::countReactions($db, $friendId),
                'member_since'        => substr((string)$friend['created_at'], 0, 10),
                'followers_count'     => self::countFollowers($db, $friendId),
                'following_count'     => self::countFollowing($db, $friendId),
                'is_following'        => $isFollowing,
                'is_followed_by'      => $isFollowedBy,
                'is_mutual'           => $isMutual,
            ],
            'history'              => $isSelf ? ReadingLogEntity::fetchHistoryDates($db, $friendId, $status->today, 30) : null,
            'nudged_today'         => ($isSelf || !$isMutual) ? false : FriendNudgeEntity::wasNudgedOn($db, $userId, $friendId, $status->today),
            'mutual_friends_count' => $isSelf ? 0 : FollowEntity::countMutualFriends($db, $userId, $friendId),
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

        $rows = $type === 'followers'
            ? FollowEntity::fetchFollowers($db, $targetId)
            : FollowEntity::fetchFollowing($db, $targetId);

        // Una sola query con los IDs que sigo, en vez de un isFollowing() por fila.
        $followingIds = array_flip(FollowEntity::fetchFollowingIds($db, $userId));

        sendJsonResponse([
            'success' => true,
            'users' => array_map(function ($r) use ($followingIds, $userId) {
                $id = (string)$r['id'];
                $isSelf = ($id === $userId);
                return [
                    'id'           => $id,
                    'display_name' => $r['display_name'],
                    'streak_count' => (int)$r['streak_count'],
                    'is_self'      => $isSelf,
                    'is_following' => $isSelf || isset($followingIds[$id]),
                ];
            }, $rows)
        ]);
    }

    public static function isFollowing(\PDO $db, string $followerId, string $followedId): bool {
        return FollowEntity::isFollowing($db, $followerId, $followedId);
    }

    public static function countFollowers(\PDO $db, string $userId): int {
        return FollowEntity::countFollowers($db, $userId);
    }

    public static function countFollowing(\PDO $db, string $userId): int {
        return FollowEntity::countFollowing($db, $userId);
    }

    /** Conteo de reacciones registradas por dia de lectura, ej. {"loved": 12, "peaceful": 5}. */
    public static function countReactions(\PDO $db, string $userId): array {
        $counts = [];
        foreach (ReadingLogEntity::countReactionsGrouped($db, $userId) as $row) {
            $counts[$row['reaction']] = (int)$row['total'];
        }
        return $counts;
    }
}
