<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../src/Utils/SnowflakeId.php';

function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $host = getEnvVar('MAIN_DB_HOST', 'localhost');
        $port = getEnvVar('MAIN_DB_PORT', '3306');
        $db   = getEnvVar('MAIN_DB_NAME', 'biblingo');
        $user = getEnvVar('MAIN_DB_USERNAME', 'root');
        $pass = getEnvVar('MAIN_DB_PASSWORD', 'root');
        $charset = getEnvVar('MAIN_DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            sendJsonResponse(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()], 500);
            exit;
        }
    }
    return $pdo;
}

function sendJsonResponse($data, $statusCode = 200) {
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getJsonInput() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

function generateInviteCode($length = 8) {
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // No confusing chars like 0, O, 1, I
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
