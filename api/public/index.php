<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/ReadingController.php';
require_once __DIR__ . '/../src/Controllers/FriendController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';

// Manejo de preflight CORS (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
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
    ReadingController::logReading($userId);
} elseif ($requestUri === '/api/friends' && $method === 'GET') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::getFriends($userId);
} elseif ($requestUri === '/api/friends/add' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::addFriend($userId);
} elseif ($requestUri === '/api/friends/nudge' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    FriendController::nudgeFriend($userId);
} elseif ($requestUri === '/api/user/update' && $method === 'POST') {
    if (!$userId) sendJsonResponse(['error' => 'user_id requerido'], 400);
    UserController::updateProfile($userId);
} else {
    sendJsonResponse([
        'error' => 'Ruta no encontrada',
        'uri'   => $requestUri,
        'app'   => 'Biblingo API v1.0'
    ], 404);
}
