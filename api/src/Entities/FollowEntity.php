<?php

declare(strict_types=1);

namespace Libringo\Entities;

/** Acceso a datos de la tabla `follows`. */
class FollowEntity {
    public static function isFollowing(\PDO $db, string $followerId, string $followedId): bool {
        $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$followerId, $followedId]);
        return (bool)$stmt->fetch();
    }

    /** Solo cuenta seguidores con cuenta activa (oculta banned/deleted). */
    public static function countFollowers(\PDO $db, string $userId): int {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total
            FROM follows f
            JOIN users u ON u.id = f.follower_id
            WHERE f.followed_id = ? AND u.status = 'active'
        ");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    /** Solo cuenta seguidos con cuenta activa (oculta banned/deleted). */
    public static function countFollowing(\PDO $db, string $userId): int {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total
            FROM follows f
            JOIN users u ON u.id = f.followed_id
            WHERE f.follower_id = ? AND u.status = 'active'
        ");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    /** Lista de seguidos del usuario mas su propia fila, ordenados por racha. Oculta seguidos banned/deleted. */
    public static function fetchFollowingWithSelf(\PDO $db, string $userId): array {
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.streak_freezes, u.last_read_date, u.username, u.timezone,
                   0 AS is_self,
                   EXISTS(SELECT 1 FROM follows fb WHERE fb.follower_id = u.id AND fb.followed_id = ?) AS is_mutual
            FROM follows f
            JOIN users u ON f.followed_id = u.id
            WHERE f.follower_id = ? AND u.status = 'active'
            UNION ALL
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.streak_freezes, u.last_read_date, u.username, u.timezone,
                   1 AS is_self, 1 AS is_mutual
            FROM users u
            WHERE u.id = ?
            ORDER BY streak_count DESC, display_name ASC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Oculta seguidores banned/deleted, y a cualquiera con quien exista un bloqueo
     * (en cualquier sentido) con $viewerId — no con $targetId, dueño de la lista.
     */
    public static function fetchFollowers(\PDO $db, string $targetId, string $viewerId): array {
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.username
            FROM follows f
            JOIN users u ON f.follower_id = u.id
            WHERE f.followed_id = ? AND u.status = 'active'
              AND NOT EXISTS (
                  SELECT 1 FROM blocks b
                  WHERE (b.blocker_id = ? AND b.blocked_id = u.id) OR (b.blocker_id = u.id AND b.blocked_id = ?)
              )
            ORDER BY u.display_name ASC
        ");
        $stmt->execute([$targetId, $viewerId, $viewerId]);
        return $stmt->fetchAll();
    }

    /**
     * Oculta seguidos banned/deleted, y a cualquiera con quien exista un bloqueo
     * (en cualquier sentido) con $viewerId — no con $targetId, dueño de la lista.
     */
    public static function fetchFollowing(\PDO $db, string $targetId, string $viewerId): array {
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.username
            FROM follows f
            JOIN users u ON f.followed_id = u.id
            WHERE f.follower_id = ? AND u.status = 'active'
              AND NOT EXISTS (
                  SELECT 1 FROM blocks b
                  WHERE (b.blocker_id = ? AND b.blocked_id = u.id) OR (b.blocker_id = u.id AND b.blocked_id = ?)
              )
            ORDER BY u.display_name ASC
        ");
        $stmt->execute([$targetId, $viewerId, $viewerId]);
        return $stmt->fetchAll();
    }

    /** IDs de todos a quienes sigue el usuario, para chequear pertenencia en memoria en vez de una query por fila. */
    public static function fetchFollowingIds(\PDO $db, string $userId): array {
        $stmt = $db->prepare("SELECT followed_id FROM follows WHERE follower_id = ?");
        $stmt->execute([$userId]);
        return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** Devuelve false si el follow ya existia (INSERT IGNORE no inserto fila nueva). */
    public static function insertFollow(\PDO $db, string $id, string $followerId, string $followedId): bool {
        $stmt = $db->prepare("INSERT IGNORE INTO follows (id, follower_id, followed_id) VALUES (?, ?, ?)");
        $stmt->execute([$id, $followerId, $followedId]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteFollow(\PDO $db, string $followerId, string $followedId): void {
        $stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$followerId, $followedId]);
    }

    /** Cuenta el total de amigos mutuos del propio usuario (se siguen entre si), no en comun con otro. */
    public static function countTotalMutualFriends(\PDO $db, string $userId): int {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total
            FROM follows f1
            JOIN follows f2 ON f2.follower_id = f1.followed_id AND f2.followed_id = f1.follower_id
            JOIN users u ON u.id = f1.followed_id AND u.status = 'active'
            WHERE f1.follower_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }
}
