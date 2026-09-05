<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Utils/SnowflakeId.php';
require_once __DIR__ . '/../src/Utils/DateUtils.php';
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

// Extraer ID de usuario desde Query String o Header/Body
$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    $input = getJsonInput();
    $userId = $input['user_id'] ?? null;
}

// Rutas API
if ($requestUri === '/api/auth/social' && $method === 'POST') {
    AuthController::handleSocialAuth();
} elseif ($requestUri === '/api/reading/status' && $method === 'GET') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    ReadingController::getStatus($userId);
} elseif ($requestUri === '/api/reading/log' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    $reaction = !empty($input['reaction']) ? (string)$input['reaction'] : null;
    ReadingController::logReading($userId, $reaction);
} elseif ($requestUri === '/api/friends' && $method === 'GET') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::getFriends($userId);
} elseif ($requestUri === '/api/friends/requests' && $method === 'GET') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::getFriendRequests($userId);
} elseif (($requestUri === '/api/friends/request' || $requestUri === '/api/friends/add') && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::sendFriendRequest($userId);
} elseif ($requestUri === '/api/friends/accept' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::acceptFriendRequest($userId);
} elseif ($requestUri === '/api/friends/reject' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::rejectFriendRequest($userId);
} elseif ($requestUri === '/api/friends/cancel' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::cancelFriendRequest($userId);
} elseif ($requestUri === '/api/friends/nudge' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::nudgeFriend($userId);
} elseif ((($requestUri === '/api/friends/remove' && $method === 'POST') || ($requestUri === '/api/friends' && $method === 'DELETE'))) {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::removeFriend($userId);
} elseif ($requestUri === '/api/user/update' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    UserController::updateProfile($userId);
} elseif ($requestUri === '/api/user/push-token' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    UserController::registerPushToken($userId);
} elseif ($requestUri === '/api/user/push-token' && $method === 'DELETE') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    UserController::unregisterPushToken($userId);
} else {
    sendJsonResponse([
        'error' => 'Ruta no encontrada',
        'uri'   => $requestUri,
        'app'   => 'Biblingo API v1.0'
    ], 404);
}
