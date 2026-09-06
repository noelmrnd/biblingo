<?php

declare(strict_types=1);

namespace Biblingo\Entities;

/** Acceso a datos de la tabla `auth_tokens`. */
class AuthTokenEntity {
    public static function insert(\PDO $db, string $id, string $userId, string $tokenHash, string $expiresAt): void {
        $stmt = $db->prepare("INSERT INTO auth_tokens (id, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $userId, $tokenHash, $expiresAt]);
    }

    public static function findValidUserIdByHash(\PDO $db, string $tokenHash): array|false {
        $stmt = $db->prepare("SELECT user_id FROM auth_tokens WHERE token_hash = ? AND expires_at > NOW()");
        $stmt->execute([$tokenHash]);
        return $stmt->fetch();
    }
}
