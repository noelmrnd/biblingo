#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Services/FCMService.php';

echo "=================================================\n";
echo "🔔 Biblingo - Diagnóstico y Prueba de Notificaciones Push\n";
echo "=================================================\n\n";

// 1. Verificar credenciales de Firebase
$saPath = dirname(__DIR__) . '/config/firebase-service-account.json';
if (!file_exists($saPath)) {
    echo "⚠️  [Firebase] Archivo 'config/firebase-service-account.json' NO encontrado.\n";
    echo "    Las notificaciones remotas vía FCM requieren este archivo.\n\n";
} else {
    $sa = @json_decode((string)file_get_contents($saPath), true);
    if ($sa && !empty($sa['project_id'])) {
        echo "✅ [Firebase] Credenciales encontradas para el proyecto: " . $sa['project_id'] . "\n\n";
    } else {
        echo "⚠️  [Firebase] 'config/firebase-service-account.json' existe pero su formato es inválido.\n\n";
    }
}

// 2. Verificar tokens registrados en la base de datos
try {
    $db = getDbConnection();
    $stmt = $db->query("
        SELECT upt.id, upt.user_id, upt.platform, upt.token, upt.updated_at, u.display_name, u.email
        FROM user_push_tokens upt
        JOIN users u ON upt.user_id = u.id
        ORDER BY upt.updated_at DESC
    ");
    $tokens = $stmt->fetchAll();

    echo "📱 Dispositivos registrados en 'user_push_tokens' (" . count($tokens) . "):\n";
    if (empty($tokens)) {
        echo "   (No hay tokens registrados aún. Inicia sesión en un dispositivo móvil para sincronizar uno).\n\n";
    } else {
        foreach ($tokens as $idx => $t) {
            $maskedToken = substr($t['token'], 0, 14) . '...' . substr($t['token'], -8);
            echo sprintf(
                "   [%d] Usuario: %s (ID: %s) | Plataforma: %s | Actualizado: %s\n       Token: %s\n",
                $idx + 1,
                $t['display_name'] ?? 'Sin nombre',
                $t['user_id'],
                strtoupper($t['platform']),
                $t['updated_at'],
                $maskedToken
            );
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Error al conectar con la base de datos: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 3. Opcional: Enviar notificación de prueba si se pasa argumento
$targetUserId = $argv[1] ?? null;

if (!$targetUserId) {
    echo "💡 USO PARA ENVIAR UNA PUSH DE PRUEBA DESDE LA TERMINAL:\n";
    echo "   php api/bin/test_push.php <user_id>\n\n";
    if (!empty($tokens)) {
        echo "   Ejemplo con el primer dispositivo de la lista:\n";
        echo "   php api/bin/test_push.php " . $tokens[0]['user_id'] . "\n\n";
    }
    exit(0);
}

echo "🚀 Enviando notificación Push de prueba al usuario ID: {$targetUserId}...\n";
$success = FCMService::sendPushNotificationToUser(
    (string)$targetUserId,
    '📖 Biblingo: Notificación de prueba',
    '¡La conexión Push del servidor está funcionando al 100%! 🔥 Tu hábito de lectura sigue firme.',
    [
        'type' => 'test_notification',
        'timestamp' => (string)time()
    ]
);

if ($success) {
    echo "✅ ¡Notificación push enviada con éxito al dispositivo!\n";
} else {
    echo "⚠️  No se pudo entregar la notificación push. Revisa el log o la validez del token/credenciales FCM.\n";
}
