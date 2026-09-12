<?php

declare(strict_types=1);

namespace Libringo\Entities;

/** Acceso a datos de la tabla `blocks`. */
class BlockEntity {
    public const REASONS = ['spam', 'inappropriate_content', 'harassment', 'other'];

    /** true si $userId bloqueo a $otherId o viceversa (bloqueo es efectivo en ambos sentidos). */
    public static function existsEitherWay(\PDO $db, string $userId, string $otherId): bool {
        $stmt = $db->prepare("
            SELECT 1 FROM blocks
            WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)
        ");
        $stmt->execute([$userId, $otherId, $otherId, $userId]);
        return (bool)$stmt->fetch();
    }

    public static function isBlockedBy(\PDO $db, string $blockerId, string $blockedId): bool {
        $stmt = $db->prepare("SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$blockerId, $blockedId]);
        return (bool)$stmt->fetch();
    }

    /** Devuelve false si el bloqueo ya existia (INSERT IGNORE no inserto fila nueva). */
    public static function insertBlock(\PDO $db, string $id, string $blockerId, string $blockedId, ?string $reason): bool {
        $stmt = $db->prepare("INSERT IGNORE INTO blocks (id, blocker_id, blocked_id, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $blockerId, $blockedId, $reason]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteBlock(\PDO $db, string $blockerId, string $blockedId): void {
        $stmt = $db->prepare("DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$blockerId, $blockedId]);
    }

    /** Lista de usuarios que el propio usuario bloqueo, mas reciente primero. */
    public static function fetchBlockedByUser(\PDO $db, string $userId): array {
        $stmt = $db->prepare("
            SELECT u.id, u.display_name, u.username, b.reason, b.created_at
            FROM blocks b
            JOIN users u ON u.id = b.blocked_id
            WHERE b.blocker_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
