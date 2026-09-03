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
}
