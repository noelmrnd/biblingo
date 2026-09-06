<?php

declare(strict_types=1);

namespace Biblingo\Entities;

/** Acceso a datos de la tabla `feedback`. */
class FeedbackEntity {
    public static function insert(\PDO $db, string $id, string $userId, string $type, string $message): void {
        $stmt = $db->prepare("INSERT INTO feedback (id, user_id, type, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $userId, $type, $message]);
    }
}
