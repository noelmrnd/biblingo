<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Utils/SnowflakeId.php';
require_once __DIR__ . '/../src/Utils/DateUtils.php';
require_once __DIR__ . '/../src/Utils/Auth.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/ReadingController.php';
require_once __DIR__ . '/../src/Controllers/FriendController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';

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

// Rutas publicas que no requieren sesion (el login todavia no tiene token que mandar)
$publicRoutes = ['/api/auth/social'];

// El resto de rutas exige "Authorization: Bearer <token>". $userId ya NO se toma del
// cliente (query/body) para saber quien hace la peticion — antes cualquiera podia
// mandar el user_id de otra persona y actuar como ella sin ninguna credencial.
$userId = in_array($requestUri, $publicRoutes, true) ? null : Auth::requireUser();

// Rutas API
if ($requestUri === '/api/auth/social' && $method === 'POST') {
    AuthController::handleSocialAuth();
} elseif ($requestUri === '/api/reading/status' && $method === 'GET') {
    ReadingController::getStatus($userId);
} elseif ($requestUri === '/api/reading/log' && $method === 'POST') {
    $reaction = !empty($input['reaction']) ? (string)$input['reaction'] : null;
    ReadingController::logReading($userId, $reaction);
} elseif ($requestUri === '/api/friends' && $method === 'GET') {
    FriendController::getFriends($userId);
} elseif ($requestUri === '/api/friends/requests' && $method === 'GET') {
    FriendController::getFriendRequests($userId);
} elseif ($requestUri === '/api/friends/history' && $method === 'GET') {
    $friendId = $_GET['friend_id'] ?? null;
    if (!$friendId) sendJsonResponse(['error' => 'friend_id requerido'], 400);
    FriendController::getFriendHistory($userId, $friendId);
} elseif (($requestUri === '/api/friends/request' || $requestUri === '/api/friends/add') && $method === 'POST') {
    FriendController::sendFriendRequest($userId);
} elseif ($requestUri === '/api/friends/accept' && $method === 'POST') {
    FriendController::acceptFriendRequest($userId);
} elseif ($requestUri === '/api/friends/reject' && $method === 'POST') {
    FriendController::rejectFriendRequest($userId);
} elseif ($requestUri === '/api/friends/cancel' && $method === 'POST') {
    FriendController::cancelFriendRequest($userId);
} elseif ($requestUri === '/api/friends/nudge' && $method === 'POST') {
    FriendController::nudgeFriend($userId);
} elseif ((($requestUri === '/api/friends/remove' && $method === 'POST') || ($requestUri === '/api/friends' && $method === 'DELETE'))) {
    FriendController::removeFriend($userId);
} elseif ($requestUri === '/api/user/update' && $method === 'POST') {
    UserController::updateProfile($userId);
} elseif ($requestUri === '/api/user/push-token' && $method === 'POST') {
    UserController::registerPushToken($userId);
} elseif ($requestUri === '/api/user/push-token' && $method === 'DELETE') {
    UserController::unregisterPushToken($userId);
} else {
    sendJsonResponse([
        'error' => 'Ruta no encontrada',
        'uri'   => $requestUri,
        'app'   => 'Biblingo API v1.0'
    ], 404);
}
