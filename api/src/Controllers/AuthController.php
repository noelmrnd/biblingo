<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class AuthController {
    public static function handleSocialAuth() {
        $input = getJsonInput();
        $provider    = $input['provider'] ?? ''; // 'apple' or 'google'
        $idToken     = $input['id_token'] ?? $input['user_id'] ?? '';
        $email       = $input['email'] ?? null;
        $displayName = !empty($input['display_name']) ? trim($input['display_name']) : 'Lector Libringo';
        $platform    = $input['platform'] ?? 'ios';
        $pushToken   = $input['push_token'] ?? null;
        $rawTz       = $input['timezone'] ?? ($_SERVER['HTTP_X_TIMEZONE'] ?? 'UTC');
        $timezone    = DateUtils::getSafeDateTimeZone($rawTz)->getName();

        if (empty($idToken)) {
            sendJsonResponse(['error' => 'Identificador o token requerido.'], 400);
        }

        // Si idToken es un JWT, extraer la clave 'sub' (User ID de Apple/Google)
        if (is_string($idToken) && strpos($idToken, '.') !== false) {
            $jwtParts = explode('.', $idToken);
            if (count($jwtParts) >= 2) {
                $payloadJson = base64_decode(strtr($jwtParts[1], '-_', '+/'));
                $jwtPayload = json_decode($payloadJson, true);
                if (!empty($jwtPayload['sub'])) {
                    $idToken = $jwtPayload['sub'];
                }
            }
        }
        $idToken = substr((string)$idToken, 0, 255);

        $db = getDbConnection();
        $user = null;

        if ($provider === 'apple') {
            $stmt = $db->prepare("SELECT * FROM users WHERE apple_id = ? OR email = ?");
            $stmt->execute([$idToken, $email]);
            $user = $stmt->fetch();
        } else if ($provider === 'google') {
            $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$idToken, $email]);
            $user = $stmt->fetch();
        } else {
            // Fallback genérico para desarrollo/pruebas
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR apple_id = ?");
            $stmt->execute([$email, $idToken]);
            $user = $stmt->fetch();
        }

        if (!$user) {
            // Generar invite_code único
            do {
                $inviteCode = generateInviteCode(8);
                $checkStmt = $db->prepare("SELECT id FROM users WHERE invite_code = ?");
                $checkStmt->execute([$inviteCode]);
            } while ($checkStmt->fetch());

            $appleId = ($provider === 'apple') ? $idToken : null;
            $googleId = ($provider === 'google') ? $idToken : null;

            $insertStmt = $db->prepare("
                INSERT INTO users (apple_id, google_id, email, display_name, invite_code, push_token, platform, timezone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$appleId, $googleId, $email, $displayName, $inviteCode, $pushToken, $platform, $timezone]);

            $userId = $db->lastInsertId();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } else {
            // Actualizar push_token, plataforma y timezone si han cambiado
            $updateStmt = $db->prepare("UPDATE users SET push_token = COALESCE(?, push_token), platform = ?, timezone = ? WHERE id = ?");
            $updateStmt->execute([$pushToken, $platform, $timezone, $user['id']]);
            $user['push_token'] = $pushToken ?: $user['push_token'];
            $user['platform'] = $platform;
            $user['timezone'] = $timezone;
        }

        // Token de sesión básico en base64
        $authToken = base64_encode($user['id'] . ':' . time() . ':' . bin2hex(random_bytes(8)));

        sendJsonResponse([
            'success' => true,
            'token'   => $authToken,
            'user'    => [
                'id'               => (int)$user['id'],
                'display_name'     => $user['display_name'],
                'email'            => $user['email'],
                'invite_code'      => $user['invite_code'],
                'streak_count'     => (int)$user['streak_count'],
                'max_streak_count' => (int)$user['max_streak_count'],
                'last_read_date'   => $user['last_read_date'],
                'push_token'       => $user['push_token'],
                'reminder_time'    => $user['reminder_time'] ?? '20:00',
                'timezone'         => $user['timezone'] ?? 'UTC',
                'platform'         => $user['platform']
            ]
        ]);
    }
}
