<?php

require_once __DIR__ . '/../../config/db.php';

class UserController {
    public static function updateProfile($userId) {
        $input = getJsonInput();
        $displayName = isset($input['display_name']) ? trim($input['display_name']) : null;
        $pushToken   = isset($input['push_token']) ? trim($input['push_token']) : null;

        if (!$displayName && !$pushToken) {
            sendJsonResponse(['error' => 'Sin datos para actualizar.'], 400);
        }

        $db = getDbConnection();
        $fields = [];
        $params = [];

        if ($displayName) {
            $fields[] = 'display_name = ?';
            $params[] = $displayName;
        }

        if ($pushToken !== null) {
            $fields[] = 'push_token = ?';
            $params[] = $pushToken;
        }

        $params[] = $userId;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        sendJsonResponse([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.'
        ]);
    }
}
