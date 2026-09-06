<?php

declare(strict_types=1);

namespace Biblingo\Entities;

/** Acceso a datos de la tabla `users`. Toda query SQL sobre usuarios vive aqui. */
class UserEntity {
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BANNED = 'banned';
    public const STATUS_DELETED = 'deleted';

    /**
     * Categorias de notificacion que el usuario puede prender/apagar desde Ajustes.
     * Todas empiezan en true; una fila sin notification_prefs (columna NULL, cuenta
     * vieja o nunca tocada) se trata como "todo prendido" via este default, y una
     * clave nueva que se agregue aca despues tambien nace prendida para todos.
     */
    public const DEFAULT_NOTIFICATION_PREFS = [
        'daily_reminder'  => true,
        'streak_at_risk'  => true,
        'freeze_used'     => true,
        'new_follower'    => true,
        'friend_activity' => true,
        'nudge'           => true,
        'news'            => true,
    ];

    public static function getNotificationPrefs(\PDO $db, string $userId): array {
        $stmt = $db->prepare("SELECT notification_prefs FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $raw = $stmt->fetchColumn();
        $stored = $raw ? (json_decode($raw, true) ?: []) : [];
        return array_merge(self::DEFAULT_NOTIFICATION_PREFS, $stored);
    }

    public static function updateNotificationPrefs(\PDO $db, string $userId, array $prefs): array {
        $merged = array_merge(self::getNotificationPrefs($db, $userId), $prefs);
        $stmt = $db->prepare("UPDATE users SET notification_prefs = ? WHERE id = ?");
        $stmt->execute([json_encode($merged), $userId]);
        return $merged;
    }

    public static function findByAppleId(\PDO $db, string $appleId): array|false {
        $stmt = $db->prepare("SELECT * FROM users WHERE apple_id = ?");
        $stmt->execute([$appleId]);
        return $stmt->fetch();
    }

    public static function findByGoogleId(\PDO $db, string $googleId): array|false {
        $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        return $stmt->fetch();
    }

    public static function findByEmail(\PDO $db, ?string $email): array|false {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function findById(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function existsId(\PDO $db, string $userId): bool {
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetch();
    }

    public static function usernameTaken(\PDO $db, string $username): bool {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return (bool)$stmt->fetch();
    }

    public static function usernameTakenByOther(\PDO $db, string $username, string $excludeUserId): bool {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $excludeUserId]);
        return (bool)$stmt->fetch();
    }

    /** Solo encuentra cuentas activas: no se puede seguir a un usuario banned/deleted. */
    public static function findByUsername(\PDO $db, string $username): array|false {
        $stmt = $db->prepare("SELECT id, display_name FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public static function getDisplayName(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function insert(
        \PDO $db,
        string $userId,
        ?string $appleId,
        ?string $googleId,
        ?string $email,
        string $displayName,
        string $username,
        string $platform,
        string $timezone
    ): void {
        $stmt = $db->prepare("
            INSERT INTO users (id, apple_id, google_id, email, display_name, username, platform, timezone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $appleId, $googleId, $email, $displayName, $username, $platform, $timezone]);
    }

    public static function updateLoginInfo(\PDO $db, string $userId, string $platform, string $timezone): void {
        $stmt = $db->prepare("UPDATE users SET platform = ?, timezone = ? WHERE id = ?");
        $stmt->execute([$platform, $timezone, $userId]);
    }

    public static function getReadingStatusRow(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT username, streak_count, max_streak_count, streak_freezes, streak_freezes_used, last_read_date, timezone, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function getStreakRow(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT streak_count, max_streak_count, streak_freezes, streak_freezes_used, last_read_date, timezone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function updateStreak(
        \PDO $db,
        string $userId,
        int $streakCount,
        int $maxStreakCount,
        int $streakFreezes,
        int $streakFreezesUsed,
        string $lastReadDate
    ): void {
        $stmt = $db->prepare("
            UPDATE users
            SET streak_count = ?, max_streak_count = ?, streak_freezes = ?, streak_freezes_used = ?, last_read_date = ?
            WHERE id = ?
        ");
        $stmt->execute([$streakCount, $maxStreakCount, $streakFreezes, $streakFreezesUsed, $lastReadDate, $userId]);
    }

    /** Solo devuelve cuentas activas: ver el perfil de un banned/deleted responde "no encontrado". */
    public static function getProfileRow(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT display_name, username, streak_count, max_streak_count, streak_freezes, last_read_date, timezone, created_at FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function getSettingsRow(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT display_name, username, email, timezone, reminder_time FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function updateFields(\PDO $db, string $userId, array $fields, array $params): void {
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    public static function getUpdatedProfileRow(\PDO $db, string $userId): array|false {
        $stmt = $db->prepare("SELECT id, display_name, email, username, streak_count, max_streak_count, streak_freezes, last_read_date, reminder_time, timezone, platform FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function updateStatus(\PDO $db, string $userId, string $status): void {
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);
    }
}
