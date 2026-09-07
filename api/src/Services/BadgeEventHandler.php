<?php

declare(strict_types=1);

namespace Biblingo\Services;

use Biblingo\Entities\BadgeEntity;
use Biblingo\Entities\FollowEntity;
use Biblingo\Entities\FriendNudgeEntity;

/**
 * Reacciona a eventos de dominio para otorgar medallas. Desacoplado a
 * proposito: FriendController solo registra FriendAdded/FriendNudged en el
 * outbox, no sabe que esto existe ni que categorias de medallas dispara.
 * Agregar un chequeo de medallas nuevo a un evento existente es agregar
 * lineas aca, no tocar el controller que originó el evento.
 */
class BadgeEventHandler {
    public static function onFriendAdded(array $payload): void {
        $db = getDbConnection();
        $followerId = (string)$payload['sender_id'];
        $followedId = (string)$payload['receiver_id'];

        BadgeEntity::checkAndAward($db, $followerId, [
            'following' => FollowEntity::countFollowing($db, $followerId),
            'mutual'    => FollowEntity::countTotalMutualFriends($db, $followerId),
        ]);

        BadgeEntity::checkAndAward($db, $followedId, [
            'followers' => FollowEntity::countFollowers($db, $followedId),
            'mutual'    => FollowEntity::countTotalMutualFriends($db, $followedId),
        ]);
    }

    public static function onFriendNudged(array $payload): void {
        $db = getDbConnection();
        $senderId = (string)$payload['sender_id'];
        $receiverId = (string)$payload['receiver_id'];

        BadgeEntity::checkAndAward($db, $senderId, [
            'nudge_sent' => FriendNudgeEntity::countSentByUser($db, $senderId),
        ]);

        BadgeEntity::checkAndAward($db, $receiverId, [
            'nudge_received' => FriendNudgeEntity::countReceivedByUser($db, $receiverId),
        ]);
    }
}
