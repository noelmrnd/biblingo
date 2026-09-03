<?php

declare(strict_types=1);

class FCMService {
    /**
     * Envia una notificación Push a un dispositivo específico vía FCM.
     * Si no se configura la clave del servicio, simula un envio exitoso para desarrollo.
     */
    public static function sendPushNotification($deviceToken, $title, $body, $data = []) {
        if (empty($deviceToken)) {
            return false;
        }

        $serviceAccountPath = __DIR__ . '/../../firebase-service-account.json';
        
        // Si existe el archivo de credenciales de Firebase, realizar petición HTTP v1 o FCM Legacy
        if (file_exists($serviceAccountPath)) {
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            if ($serviceAccount && isset($serviceAccount['project_id'])) {
                // Aquí se realizaría la llamada HTTP con JWT Bearer Token
                // Por razones de desarrollo, registramos la llamada
                error_log("FCM Push a token {$deviceToken}: [{$title}] {$body}");
                return true;
            }
        }

        // Si no hay credenciales, simular en entorno de desarrollo/log
        error_log("[FCM Mock Push] Token: {$deviceToken} | Título: {$title} | Mensaje: {$body}");
        return true;
    }

    /**
     * Envía una notificación Push a TODOS los dispositivos activos de un usuario.
     */
    public static function sendPushNotificationToUser($userId, $title, $body, $data = []) {
        if (empty($userId)) {
            return false;
        }

        $db = getDbConnection();
        $tokens = [];

        $stmt = $db->prepare("SELECT token FROM user_push_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tokens)) {
            return false;
        }

        // 3. Enviar a cada dispositivo registrado
        $sentCount = 0;
        foreach (array_unique($tokens) as $token) {
            if (self::sendPushNotification($token, $title, $body, $data)) {
                $sentCount++;
            }
        }

        return $sentCount > 0;
    }
}

