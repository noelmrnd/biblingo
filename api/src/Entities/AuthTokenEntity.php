<?php

declare(strict_types=1);

namespace Biblingo\Entities;

/** Acceso a datos de la tabla `auth_tokens`. */
class AuthTokenEntity {
    public static function insert(\PDO $db, string $id, string $userId, string $tokenHash, string $expiresAt): void {
        $stmt = $db->prepare("INSERT INTO auth_tokens (id, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $userId, $tokenHash, $expiresAt]);
    }

    /** Solo devuelve el usuario si el token es valido y la cuenta sigue activa (ni bloqueada ni borrada). */
    public static function findValidUserIdByHash(\PDO $db, string $tokenHash): array|false {
        $stmt = $db->prepare("
            SELECT at.user_id
            FROM auth_tokens at
            JOIN users u ON u.id = at.user_id
            WHERE at.token_hash = ? AND at.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$tokenHash]);
        return $stmt->fetch();
    }
}
