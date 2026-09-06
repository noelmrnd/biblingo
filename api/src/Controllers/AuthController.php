<?php

declare(strict_types=1);

namespace Biblingo\Controllers;

use Biblingo\Utils\Auth;
use Biblingo\Utils\DateUtils;
use Biblingo\Utils\JwtVerifier;
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

        if ($provider === 'apple') {
            $stmt = $db->prepare("SELECT * FROM users WHERE apple_id = ?");
            $stmt->execute([$providerId]);
        } elseif ($provider === 'google') {
            $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
            $stmt->execute([$providerId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$verifiedEmail]);
        }
        $user = $stmt->fetch();

        if (!$user) {
            $baseUsername = slugifyUsername($displayName);
            $username = $baseUsername;
            $suffix = 1;
            $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->execute([$username]);
            while ($checkStmt->fetch()) {
                $suffix++;
                $username = $baseUsername . $suffix;
                $checkStmt->execute([$username]);
            }

            $appleId = ($provider === 'apple') ? $providerId : null;
            $googleId = ($provider === 'google') ? $providerId : null;

            $userId = (string)SnowflakeId::nextId();

            $insertStmt = $db->prepare("
                INSERT INTO users (id, apple_id, google_id, email, display_name, username, platform, timezone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$userId, $appleId, $googleId, $verifiedEmail, $displayName, $username, $platform, $timezone]);

            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } else {
            $userId = (string)$user['id'];
            $updateStmt = $db->prepare("UPDATE users SET platform = ?, timezone = ? WHERE id = ?");
            $updateStmt->execute([$platform, $timezone, $userId]);
            $user['platform'] = $platform;
            $user['timezone'] = $timezone;
        }

        $authToken = Auth::issueToken($userId);

        $userTz = $user['timezone'] ?? 'UTC';
        $lastRead = $user['last_read_date'];
        $status = StreakUtils::computeStatus($lastRead, (int)$user['streak_count'], $userTz);

        $totalDaysStmt = $db->prepare("SELECT COUNT(*) AS total FROM reading_logs WHERE user_id = ?");
        $totalDaysStmt->execute([$userId]);
        $totalDaysRead = (int)($totalDaysStmt->fetch()['total'] ?? 0);

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
}
