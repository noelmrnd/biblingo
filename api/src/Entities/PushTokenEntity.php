<?php

declare(strict_types=1);

namespace Biblingo\Entities;

/** Acceso a datos de la tabla `user_push_tokens`. */
class PushTokenEntity {
    public static function upsert(\PDO $db, string $id, string $userId, string $token, string $platform): void {
        $stmt = $db->prepare("
            INSERT INTO user_push_tokens (id, user_id, token, platform)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$id, $userId, $token, $platform]);
    }

    public static function deleteByToken(\PDO $db, string $userId, string $token): void {
        $stmt = $db->prepare("DELETE FROM user_push_tokens WHERE user_id = ? AND token = ?");
        $stmt->execute([$userId, $token]);
    }

    public static function deleteAllForUser(\PDO $db, string $userId): void {
        $stmt = $db->prepare("DELETE FROM user_push_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Solo trae tokens de cuentas activas: un usuario banned/deleted no debe seguir
     * recibiendo push (ban se aplica manualmente en la DB, sin pasar por la app, asi
     * que este filtro es el unico lugar que lo hace efectivo para notificaciones).
     */
    public static function fetchTokensForUser(\PDO $db, string $userId): array {
        $stmt = $db->prepare("
            SELECT t.token
            FROM user_push_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE t.user_id = ? AND u.status = 'active'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function deleteByTokenValue(\PDO $db, string $token): void {
        $stmt = $db->prepare("DELETE FROM user_push_tokens WHERE token = ?");
        $stmt->execute([$token]);
    }
}
