<?php

declare(strict_types=1);

namespace Biblingo\Entities;

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
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.username, u.timezone,
                   0 AS is_self,
                   EXISTS(SELECT 1 FROM follows fb WHERE fb.follower_id = u.id AND fb.followed_id = ?) AS is_mutual
            FROM follows f
            JOIN users u ON f.followed_id = u.id
            WHERE f.follower_id = ? AND u.status = 'active'
            UNION ALL
            SELECT u.id, u.display_name, u.streak_count, u.max_streak_count, u.last_read_date, u.username, u.timezone,
                   1 AS is_self, 1 AS is_mutual
            FROM users u
            WHERE u.id = ?
            ORDER BY streak_count DESC, display_name ASC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    /** Oculta seguidores banned/deleted. */
    public static function fetchFollowers(\PDO $db, string $targetId): array {
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.streak_count
            FROM follows f
            JOIN users u ON f.follower_id = u.id
            WHERE f.followed_id = ? AND u.status = 'active'
            ORDER BY u.streak_count DESC, u.display_name ASC
        ");
        $stmt->execute([$targetId]);
        return $stmt->fetchAll();
    }

    /** Oculta seguidos banned/deleted. */
    public static function fetchFollowing(\PDO $db, string $targetId): array {
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.streak_count
            FROM follows f
            JOIN users u ON f.followed_id = u.id
            WHERE f.follower_id = ? AND u.status = 'active'
            ORDER BY u.streak_count DESC, u.display_name ASC
        ");
        $stmt->execute([$targetId]);
        return $stmt->fetchAll();
    }

    public static function insertFollow(\PDO $db, string $id, string $followerId, string $followedId): void {
        $stmt = $db->prepare("INSERT IGNORE INTO follows (id, follower_id, followed_id) VALUES (?, ?, ?)");
        $stmt->execute([$id, $followerId, $followedId]);
    }

    public static function deleteFollow(\PDO $db, string $followerId, string $followedId): void {
        $stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$followerId, $followedId]);
    }

    /** Cuenta cuantas personas activas tienen seguimiento mutuo con ambos usuarios ("amigos en comun"). */
    public static function countMutualFriends(\PDO $db, string $userId, string $friendId): int {
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
            JOIN users u ON u.id = mutual_a.uid AND u.status = 'active'
            WHERE mutual_a.uid NOT IN (?, ?)
        ");
        $stmt->execute([$userId, $friendId, $userId, $friendId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }
}
