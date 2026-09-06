<?php

declare(strict_types=1);

namespace Biblingo\Controllers;

use Biblingo\Entities\ReadingLogEntity;
use Biblingo\Entities\UserEntity;
use Biblingo\Utils\Auth;
use Biblingo\Utils\DateUtils;
use Biblingo\Utils\JwtVerifier;
use Biblingo\Utils\RateLimiter;
use Biblingo\Utils\SnowflakeId;
use Biblingo\Utils\StreakUtils;

class AuthController {
    // Identificadores publicos de la app (no son secretos), usados como audiencia
    // esperada al verificar los id_tokens de Apple/Google.
    private const GOOGLE_AUDIENCES = [
        '56637027170-3k9bfjk1rh4vtfs3lm3ev8sp0tgv3aoi.apps.googleusercontent.com',
        '56637027170-5bckf6oali35ir6m2qisr9urm5qknncg.apps.googleusercontent.com',
    ];
    private const APPLE_AUDIENCES = ['me.biblingo.app', 'me.biblingo.app.service'];

    public static function handleSocialAuth() {
        // Por IP, no por usuario: antes de verificar nada todavia no sabemos quien es.
        // Frena fuerza bruta contra el login (incluido el panel "dev") sin bloquear
        // a un usuario legitimo que solo tuvo un par de intentos fallidos.
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::allow('auth_social', $clientIp, 60, 20)) {
            sendJsonResponse(['error' => 'Demasiados intentos. Espera un momento e intenta de nuevo.'], 429);
        }

        $input = getJsonInput();
        $provider    = $input['provider'] ?? '';
        $idToken     = (string)($input['id_token'] ?? '');
        $displayName = !empty($input['display_name']) ? trim($input['display_name']) : 'Lector Biblingo';
        $platform    = $input['platform'] ?? 'ios';
        $rawTz       = $input['timezone'] ?? ($_SERVER['HTTP_X_TIMEZONE'] ?? 'UTC');
        $timezone    = DateUtils::getSafeDateTimeZone($rawTz)->getName();

        if (empty($idToken)) {
            sendJsonResponse(['error' => 'Identificador o token requerido.'], 400);
        }

        // Verificar el id_token contra el proveedor real. Antes se confiaba en el
        // valor "sub" del JWT sin validar su firma, lo que permitia iniciar sesion
        // como cualquier usuario fabricando un token con su id.
        if ($provider === 'apple') {
            $verified = JwtVerifier::verifyAppleIdToken($idToken, self::APPLE_AUDIENCES);
        } elseif ($provider === 'google') {
            $verified = JwtVerifier::verifyGoogleIdToken($idToken, self::GOOGLE_AUDIENCES);
        } elseif ($provider === 'dev' && getEnvVar('APP_ENV') === 'dev') {
            // Login local sin OAuth real, solo habilitado explicitamente via APP_ENV=dev.
            $verified = ['sub' => substr($idToken, 0, 255), 'email' => $input['email'] ?? null];
        } else {
            sendJsonResponse(['error' => 'Proveedor de autenticación invalido o no verificable.'], 401);
        }

        if (!$verified || empty($verified['sub'])) {
            sendJsonResponse(['error' => 'No se pudo verificar la identidad con el proveedor.'], 401);
        }

        $providerId = substr((string)$verified['sub'], 0, 255);
        $verifiedEmail = $verified['email'] ?? null;

        $db = getDbConnection();

        $user = self::findByProvider($db, $provider, $providerId, $verifiedEmail);

        $isNewUser = !$user;
        if ($isNewUser) {
            $user = self::createUser($db, $provider, $providerId, $verifiedEmail, $displayName, $platform, $timezone);
        }
        $userId = (string)$user['id'];

        if ($user['status'] === UserEntity::STATUS_BANNED) {
            sendJsonResponse(['error' => 'Esta cuenta ha sido bloqueada.'], 403);
        }
        if ($user['status'] === UserEntity::STATUS_DELETED) {
            sendJsonResponse(['error' => 'Esta cuenta fue eliminada.'], 403);
        }

        if (!$isNewUser) {
            UserEntity::updateLoginInfo($db, $userId, $platform, $timezone);
            $user['platform'] = $platform;
            $user['timezone'] = $timezone;
        }

        $authToken = Auth::issueToken($userId);

        $userTz = $user['timezone'] ?? 'UTC';
        $lastRead = $user['last_read_date'];
        $status = StreakUtils::computeStatus($lastRead, (int)$user['streak_count'], $userTz, (int)$user['streak_freezes']);

        $totalDaysRead = ReadingLogEntity::countTotalDaysRead($db, $userId);

        sendJsonResponse([
            'success' => true,
            'token'   => $authToken,
            'user'    => [
                'id'               => (string)$userId,
                'display_name'     => $user['display_name'],
                'email'            => $user['email'],
                'username'         => $user['username'],
                'streak_count'     => (int)$user['streak_count'],
                'max_streak_count' => (int)$user['max_streak_count'],
                'streak_freezes'   => (int)$user['streak_freezes'],
                'streak_freezes_used' => (int)$user['streak_freezes_used'],
                'total_days_read'  => $totalDaysRead,
                'reaction_counts'  => FriendController::countReactions($db, $userId),
                'member_since'     => substr((string)$user['created_at'], 0, 10),
                'followers_count'  => FriendController::countFollowers($db, $userId),
                'following_count'  => FriendController::countFollowing($db, $userId),
                'last_read_date'   => $lastRead,
                'last_read_label'  => $status->lastReadLabel,
                'has_read_today'   => $status->hasReadToday,
                'is_streak_lost'   => $status->isStreakLost,
                'reminder_time'    => $user['reminder_time'] ?? '20:00',
                'timezone'         => $userTz,
                'platform'         => $user['platform']
            ]
        ]);
    }

    /**
     * Crea el usuario nuevo. El check-then-insert de username y de apple_id/google_id
     * no es atomico, asi que dos requests concurrentes con el mismo provider id nuevo
     * pueden chocar en el INSERT (ambos UNIQUE en schema). Si eso pasa: si choco por
     * provider id/email, alguien mas ya gano la carrera y devolvemos ese usuario en vez
     * de fallar; si choco por username, reintentamos una vez con otro sufijo.
     */
    private static function createUser(
        \PDO $db,
        string $provider,
        string $providerId,
        ?string $verifiedEmail,
        string $displayName,
        string $platform,
        string $timezone
    ): array {
        $baseUsername = slugifyUsername($displayName);
        $username = $baseUsername;
        $suffix = 1;
        while (UserEntity::usernameTaken($db, $username)) {
            $suffix++;
            $username = $baseUsername . $suffix;
        }

        $appleId = ($provider === 'apple') ? $providerId : null;
        $googleId = ($provider === 'google') ? $providerId : null;
        $userId = (string)SnowflakeId::nextId();

        try {
            UserEntity::insert($db, $userId, $appleId, $googleId, $verifiedEmail, $displayName, $username, $platform, $timezone);
        } catch (\PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            $existing = self::findByProvider($db, $provider, $providerId, $verifiedEmail);
            if ($existing) {
                return $existing;
            }

            // No fue el provider id/email: choco el username contra una carrera. Un reintento basta.
            $suffix++;
            $userId = (string)SnowflakeId::nextId();
            UserEntity::insert($db, $userId, $appleId, $googleId, $verifiedEmail, $displayName, $baseUsername . $suffix, $platform, $timezone);
        }

        return UserEntity::findById($db, $userId);
    }

    private static function findByProvider(\PDO $db, string $provider, string $providerId, ?string $verifiedEmail): array|false {
        if ($provider === 'apple') {
            return UserEntity::findByAppleId($db, $providerId);
        }
        if ($provider === 'google') {
            return UserEntity::findByGoogleId($db, $providerId);
        }
        return UserEntity::findByEmail($db, $verifiedEmail);
    }

    /**
     * Cierra sesion revocando el token Bearer usado en esta request. Con
     * {"all": true} revoca todos los tokens del usuario (todos los dispositivos).
     */
    public static function logout(string $userId) {
        $input = getJsonInput();
        $everywhere = !empty($input['all']);

        if ($everywhere) {
            Auth::revokeAllTokens($userId);
        } else {
            Auth::revokeCurrentToken();
        }

        sendJsonResponse([
            'success' => true,
            'message' => $everywhere ? 'Sesión cerrada en todos los dispositivos.' : 'Sesión cerrada.'
        ]);
    }
}
