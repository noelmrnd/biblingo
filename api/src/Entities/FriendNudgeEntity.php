<?php

declare(strict_types=1);

namespace Biblingo\Entities;

/** Acceso a datos de la tabla `friend_nudges`. */
class FriendNudgeEntity {
    public static function wasNudgedOn(\PDO $db, string $senderId, string $receiverId, string $date): bool {
        $stmt = $db->prepare("SELECT 1 FROM friend_nudges WHERE sender_id = ? AND receiver_id = ? AND nudge_date = ?");
        $stmt->execute([$senderId, $receiverId, $date]);
        return (bool)$stmt->fetch();
    }

    /** Ultimo toque enviado por el usuario a cada receptor. */
    public static function fetchLastNudgeMap(\PDO $db, string $senderId): array {
        $stmt = $db->prepare("
            SELECT receiver_id, MAX(nudge_date) AS last_nudge_date
            FROM friend_nudges
            WHERE sender_id = ?
            GROUP BY receiver_id
        ");
        $stmt->execute([$senderId]);
        return $stmt->fetchAll();
    }

    public static function insert(\PDO $db, string $id, string $senderId, string $receiverId, string $date): void {
        $stmt = $db->prepare("INSERT INTO friend_nudges (id, sender_id, receiver_id, nudge_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $senderId, $receiverId, $date]);
    }
}
