<?php

declare(strict_types=1);

namespace Biblingo\Services;

class FCMService {
    private static ?string $cachedAccessToken = null;
    private static int $tokenExpiresAt = 0;

    /**
     * Localiza y lee el archivo de credenciales de Firebase (Service Account).
     * Busca exclusivamente en la carpeta config del backend.
     */
    private static function getServiceAccount(): ?array {
        $configPath = dirname(__DIR__, 2) . '/config/firebase-service-account.json';

        if (file_exists($configPath)) {
            $content = @file_get_contents($configPath);
            if ($content) {
                $json = json_decode($content, true);
                if ($json && isset($json['project_id'], $json['client_email'], $json['private_key'])) {
                    return $json;
                }
            }
        }

        return null;
    }

    /**
     * Codificación Base64 URL-safe requerida para JWT.
     */
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Obtiene un OAuth2 Access Token de Google usando la clave privada de la Service Account.
     * Almacena en caché el token en memoria y en un archivo temporal en disco (dura ~1 hora)
     * para evitar peticiones repetidas a Google entre peticiones web y workers CLI.
     */
    private static function getAccessToken(array $serviceAccount): ?string {
        $now = time();
        $cacheFile = sys_get_temp_dir() . '/biblingo_fcm_token.json';

        // 1. Reutilizar token en memoria si aún es válido
        if (self::$cachedAccessToken !== null && $now < (self::$tokenExpiresAt - 60)) {
            return self::$cachedAccessToken;
        }

        // 2. Reutilizar token guardado en archivo de caché si aún es válido
        if (file_exists($cacheFile)) {
            $cached = @json_decode((string)file_get_contents($cacheFile), true);
            if ($cached && isset($cached['access_token'], $cached['expires_at'])) {
                $expiresAt = (int)$cached['expires_at'];
                if ($now < ($expiresAt - 60)) {
                    self::$cachedAccessToken = (string)$cached['access_token'];
                    self::$tokenExpiresAt = $expiresAt;
                    return self::$cachedAccessToken;
                }
            }
        }

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss'   => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ];

        $encodedHeader = self::base64UrlEncode((string)json_encode($header));
        $encodedClaims = self::base64UrlEncode((string)json_encode($claims));
        $signatureInput = $encodedHeader . '.' . $encodedClaims;

        $binarySignature = '';
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        if (!$privateKey) {
            error_log('[FCMService] Clave privada inválida en firebase-service-account.json');
            return null;
        }

        $signed = openssl_sign($signatureInput, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$signed) {
            error_log('[FCMService] Error firmando el JWT con OpenSSL.');
            return null;
        }

        $jwt = $signatureInput . '.' . self::base64UrlEncode($binarySignature);

        // Solicitar token a Google OAuth2
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200) {
            error_log("[FCMService] Error solicitando OAuth2 token (HTTP {$httpCode}): {$rawResponse} - {$curlError}");
            return null;
        }

        $res = json_decode((string)$rawResponse, true);
        if (isset($res['access_token'])) {
            self::$cachedAccessToken = (string)$res['access_token'];
            self::$tokenExpiresAt = $now + (int)($res['expires_in'] ?? 3600);

            // Guardar en disco para que otras peticiones HTTP o workers CLI lo reutilicen
            @file_put_contents($cacheFile, json_encode([
                'access_token' => self::$cachedAccessToken,
                'expires_at'   => self::$tokenExpiresAt,
            ]), LOCK_EX);

            return self::$cachedAccessToken;
        }

        error_log('[FCMService] Respuesta inesperada de OAuth2: ' . $rawResponse);
        return null;
    }

    /**
     * Envía una notificación Push real a un dispositivo específico vía Firebase HTTP v1 API.
     * Si no se configuran credenciales, emite un log simulado sin romper el flujo.
     */
    public static function sendPushNotification(string $deviceToken, string $title, string $body, array $data = []): bool {
        if (empty(trim($deviceToken))) {
            return false;
        }

        $serviceAccount = self::getServiceAccount();

        // Si no existen credenciales de Firebase, operar en modo Mock de desarrollo
        if ($serviceAccount === null) {
            error_log("[FCM Mock Push] Token: {$deviceToken} | Título: {$title} | Mensaje: {$body}");
            return true;
        }

        $accessToken = self::getAccessToken($serviceAccount);
        if (!$accessToken) {
            error_log("[FCMService] No se pudo obtener el Access Token para enviar notificación.");
            return false;
        }

        $projectId = $serviceAccount['project_id'];

        // Sanitizar payload data para que todos los valores sean strings (requisito FCM v1)
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string)$k] = is_scalar($v) ? (string)$v : (string)json_encode($v);
        }

        $message = [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                ],
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body'  => $body,
                        ],
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ],
        ];

        if (!empty($stringData)) {
            $message['data'] = $stringData;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; UTF-8',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("[FCMService] Error cURL enviando push: {$curlError}");
            return false;
        }

        if ($httpCode === 200) {
            error_log("[FCM Push Exitoso] Entregado a FCM para token: " . substr($deviceToken, 0, 15) . "...");
            return true;
        }

        // Si el token es inválido o no está registrado (desinstalaron la app)
        if ($httpCode === 404 || str_contains((string)$response, 'UNREGISTERED') || str_contains((string)$response, 'NOT_FOUND')) {
            error_log("[FCMService] Token no registrado o expirado. Limpiando token: " . substr($deviceToken, 0, 15) . "...");
            self::removeStaleToken($deviceToken);
        } else {
            error_log("[FCMService] Error FCM HTTP {$httpCode}: {$response}");
        }

        return false;
    }

    /**
     * Elimina un token expirado o desinstalado de la base de datos.
     */
    private static function removeStaleToken(string $token): void {
        try {
            if (function_exists('getDbConnection')) {
                $db = getDbConnection();
                $stmt = $db->prepare("DELETE FROM user_push_tokens WHERE token = ?");
                $stmt->execute([$token]);
            }
        } catch (\Throwable $e) {
            error_log("[FCMService] Error al eliminar token desactualizado: " . $e->getMessage());
        }
    }

    /**
     * Envía una notificación Push a TODOS los dispositivos activos de un usuario.
     */
    public static function sendPushNotificationToUser(string $userId, string $title, string $body, array $data = []): bool {
        if (empty($userId)) {
            return false;
        }

        $db = getDbConnection();
        $stmt = $db->prepare("SELECT token FROM user_push_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        $tokens = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($tokens)) {
            return false;
        }

        $sentCount = 0;
        foreach (array_unique($tokens) as $token) {
            if (self::sendPushNotification($token, $title, $body, $data)) {
                $sentCount++;
            }
        }

        return $sentCount > 0;
    }
}
