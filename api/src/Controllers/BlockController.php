<?php

declare(strict_types=1);

namespace Libringo\Controllers;

use Libringo\Entities\BlockEntity;
use Libringo\Entities\FollowEntity;
use Libringo\Entities\UserEntity;
use Libringo\Utils\SnowflakeId;

/**
 * Bloquear es distinto de dejar de seguir: ademas de cortar el follow en ambos
 * sentidos, impide que la otra persona vuelva a seguirte o vea tu perfil (y
 * viceversa) mientras el bloqueo exista. reason es un selector opcional, no
 * hay reportes ni moderacion adjunta en esta v1.
 */
class BlockController {
    public static function blockUser(string $userId) {
        $input = getJsonInput();
        $targetId = (string)($input['user_id'] ?? '');
        $reason = $input['reason'] ?? null;

        if (empty($targetId)) {
            sendJsonResponse(['error' => 'user_id es requerido.'], 400);
        }

        if ($targetId === $userId) {
            sendJsonResponse(['error' => 'No puedes bloquearte a ti mismo.'], 400);
        }

        if ($reason !== null && !in_array($reason, BlockEntity::REASONS, true)) {
            sendJsonResponse(['error' => 'Razón de bloqueo inválida.'], 400);
        }

        $db = getDbConnection();

        $target = UserEntity::getProfileRow($db, $targetId);
        if (!$target) {
            sendJsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        try {
            $db->beginTransaction();

            $inserted = BlockEntity::insertBlock($db, (string)SnowflakeId::nextId(), $userId, $targetId, $reason);

            // Corta el follow en ambos sentidos: bloquear a alguien que te sigue
            // no tiene sentido si sigue viendo tu actividad en su ranking.
            FollowEntity::deleteFollow($db, $userId, $targetId);
            FollowEntity::deleteFollow($db, $targetId, $userId);

            $db->commit();
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[BlockController::blockUser] ' . $e->getMessage());
            sendJsonResponse(['error' => 'Error al bloquear usuario.'], 500);
        }

        sendJsonResponse([
            'success' => true,
            'message' => $inserted
                ? "Bloqueaste a {$target['display_name']}."
                : "Ya habías bloqueado a {$target['display_name']}.",
            'blocked_id' => $targetId
        ]);
    }

    public static function unblockUser(string $userId) {
        $input = getJsonInput();
        $targetId = (string)($input['user_id'] ?? ($_GET['user_id'] ?? ''));

        if (empty($targetId)) {
            sendJsonResponse(['error' => 'user_id es requerido.'], 400);
        }

        $db = getDbConnection();
        BlockEntity::deleteBlock($db, $userId, $targetId);

        sendJsonResponse([
            'success' => true,
            'message' => 'Usuario desbloqueado.',
            'unblocked_id' => $targetId
        ]);
    }

    public static function listBlocked(string $userId) {
        $db = getDbConnection();
        $rows = BlockEntity::fetchBlockedByUser($db, $userId);

        sendJsonResponse([
            'success' => true,
            'blocked_users' => array_map(fn($r) => [
                'id'           => (string)$r['id'],
                'display_name' => $r['display_name'],
                'username'     => $r['username'],
                'reason'       => $r['reason'],
                'blocked_at'   => $r['created_at'],
            ], $rows)
        ]);
    }
}
