<?php

declare(strict_types=1);

/**
 * Sesion por token Bearer. Antes del login se emite un token opaco (issueToken);
 * cada request posterior debe mandarlo en "Authorization: Bearer <token>" para que
 * el servidor sepa quien esta preguntando, en vez de confiar en un user_id enviado
 * por el cliente sin ninguna verificacion.
 */
class Auth {
    private const TOKEN_TTL_DAYS = 180;

    public static function issueToken(string $userId): string {
        $db = getDbConnection();

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_DAYS * 86400);

        $stmt = $db->prepare("INSERT INTO auth_tokens (id, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([SnowflakeId::nextId(), $userId, $tokenHash, $expiresAt]);

        return $rawToken;
    }

    private static function bearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? '';
        }
        if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
            return null;
        }
        return trim($matches[1]);
    }

    /** Devuelve el user_id autenticado, o null si el token falta/es invalido/expiro. */
    public static function authenticate(): ?string {
        $rawToken = self::bearerToken();
        if (!$rawToken) {
            return null;
        }

        $db = getDbConnection();
        $stmt = $db->prepare("SELECT user_id FROM auth_tokens WHERE token_hash = ? AND expires_at > NOW()");
        $stmt->execute([hash('sha256', $rawToken)]);
        $row = $stmt->fetch();

        return $row ? (string)$row['user_id'] : null;
    }

    /** Igual que authenticate(), pero corta la respuesta con 401 si no hay sesion valida. */
    public static function requireUser(): string {
        $userId = self::authenticate();
        if (!$userId) {
            sendJsonResponse(['error' => 'No autenticado.'], 401);
        }
        return $userId;
    }
}
