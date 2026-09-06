<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

use Biblingo\Controllers\AuthController;
use Biblingo\Controllers\FriendController;
use Biblingo\Controllers\ReadingController;
use Biblingo\Controllers\UserController;
use Biblingo\Utils\Auth;

// Cualquier error/excepcion no capturada devuelve JSON en vez del HTML por
// defecto de PHP (que expone stack traces con rutas del servidor al cliente).
set_exception_handler(function (\Throwable $e) {
    error_log('Uncaught exception: ' . $e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode(['error' => 'Error interno del servidor']);
    exit;
});

set_error_handler(function (int $severity, string $message, string $file, int $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// Manejo de cabeceras CORS globales
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method     = $_SERVER['REQUEST_METHOD'];

$input = getJsonInput();

// Rutas publicas que no requieren sesion (el login todavia no tiene token que mandar).
// Se comparan por "METODO /ruta", igual que la tabla de rutas real de abajo, para que
// agregar otro metodo/handler bajo el mismo path no quede exento de auth por accidente.
$publicRoutes = ['POST /api/auth/social'];

// El resto de rutas exige "Authorization: Bearer <token>". $userId ya NO se toma del
// cliente (query/body) para saber quien hace la peticion — antes cualquiera podia
// mandar el user_id de otra persona y actuar como ella sin ninguna credencial.
$userId = in_array("$method $requestUri", $publicRoutes, true) ? null : Auth::requireUser();

// Tabla de rutas: "METODO /ruta" => handler. Cada handler extrae los parametros
// que le corresponden de $_GET/$input y llama al controller.
$routes = [
    'POST /api/auth/social' => fn() => AuthController::handleSocialAuth(),
    'GET /api/auth/me' => fn() => AuthController::me($userId),
    'POST /api/auth/logout' => fn() => AuthController::logout($userId),

    'GET /api/reading/status' => fn() => ReadingController::getStatus($userId),
    'GET /api/reading/calendar' => function () use ($userId) {
        $year = (int)($_GET['year'] ?? 0);
        $month = (int)($_GET['month'] ?? 0);
        ReadingController::getCalendar($userId, $year, $month);
    },
    'POST /api/reading/log' => function () use ($userId, $input) {
        $reaction = !empty($input['reaction']) ? (string)$input['reaction'] : null;
        ReadingController::logReading($userId, $reaction);
    },

    'GET /api/friends' => fn() => FriendController::getFriends($userId),
    'DELETE /api/friends' => fn() => FriendController::unfollow($userId),
    'GET /api/friends/profile' => function () use ($userId) {
        $friendId = $_GET['friend_id'] ?? null;
        if (!$friendId) {
            sendJsonResponse(['error' => 'friend_id requerido'], 400);
        }
        FriendController::getFriendProfile($userId, $friendId);
    },
    'GET /api/friends/list' => function () use ($userId) {
        $targetId = $_GET['user_id'] ?? null;
        $type = $_GET['type'] ?? null;
        if (!$targetId || !$type) {
            sendJsonResponse(['error' => 'user_id y type son requeridos'], 400);
        }
        FriendController::getFollowList($userId, $targetId, $type);
    },
    'POST /api/friends/follow' => fn() => FriendController::follow($userId),
    'POST /api/friends/unfollow' => fn() => FriendController::unfollow($userId),
    'POST /api/friends/nudge' => fn() => FriendController::nudgeFriend($userId),

    'GET /api/user/settings' => fn() => UserController::getSettings($userId),
    'POST /api/user/update' => fn() => UserController::updateProfile($userId),
    'POST /api/user/notification-prefs' => fn() => UserController::updateNotificationPrefs($userId),
    'POST /api/user/push-token' => fn() => UserController::registerPushToken($userId),
    'DELETE /api/user/push-token' => fn() => UserController::unregisterPushToken($userId),
    'POST /api/user/feedback' => fn() => UserController::submitFeedback($userId),
    'DELETE /api/user/account' => fn() => UserController::deleteAccount($userId),
];

$routeKey = "$method $requestUri";

if (isset($routes[$routeKey])) {
    $routes[$routeKey]();
} else {
    sendJsonResponse([
        'error' => 'Ruta no encontrada',
        'uri'   => $requestUri,
        'app'   => 'Biblingo API v1.0'
    ], 404);
}
