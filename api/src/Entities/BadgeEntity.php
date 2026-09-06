<?php

declare(strict_types=1);

namespace Biblingo\Entities;

use Biblingo\Utils\SnowflakeId;

/**
 * Motor generico de medallas: no sabe nada de "racha" ni "amigos" en
 * particular, solo compara un valor numerico contra un umbral por categoria.
 * Agregar un tipo de medalla nuevo a futuro es agregar una fila a CATALOG,
 * no tocar checkAndAward().
 */
class BadgeEntity {
    private const CATALOG = [
        ['id' => 'streak_1',          'category' => 'streak',   'threshold' => 1],
        ['id' => 'streak_7',          'category' => 'streak',   'threshold' => 7],
        ['id' => 'streak_30',         'category' => 'streak',   'threshold' => 30],
        ['id' => 'streak_100',        'category' => 'streak',   'threshold' => 100],
        ['id' => 'streak_365',        'category' => 'streak',   'threshold' => 365],
        ['id' => 'following_1',       'category' => 'following', 'threshold' => 1],
        ['id' => 'following_5',       'category' => 'following', 'threshold' => 5],
        ['id' => 'following_20',      'category' => 'following', 'threshold' => 20],
        ['id' => 'followers_5',       'category' => 'followers', 'threshold' => 5],
        ['id' => 'followers_20',      'category' => 'followers', 'threshold' => 20],
        ['id' => 'reaction_loved_10',      'category' => 'reaction', 'threshold' => 10, 'reaction' => 'loved'],
        ['id' => 'reaction_thoughtful_10', 'category' => 'reaction', 'threshold' => 10, 'reaction' => 'thoughtful'],
        ['id' => 'reaction_peaceful_10',   'category' => 'reaction', 'threshold' => 10, 'reaction' => 'peaceful'],
        ['id' => 'reaction_challenged_10', 'category' => 'reaction', 'threshold' => 10, 'reaction' => 'challenged'],
        ['id' => 'days_read_50',      'category' => 'days_read',     'threshold' => 50],
        ['id' => 'days_read_365',     'category' => 'days_read',     'threshold' => 365],
        ['id' => 'reactions_all_4',   'category' => 'reactions_all', 'threshold' => 4],
        ['id' => 'mutual_5',          'category' => 'mutual',        'threshold' => 5],
        ['id' => 'nudge_sent_10',     'category' => 'nudge_sent',     'threshold' => 10],
        ['id' => 'nudge_received_10', 'category' => 'nudge_received', 'threshold' => 10],
    ];

    /**
     * $currentValues: mapa "categoria" (o "categoria:extra", ej. "reaction:loved")
     * => valor numerico actual. El caller decide que es barato calcular en su
     * contexto (streak_count ya en mano en logReading, friends count tras un
     * follow, etc) — esta funcion no consulta nada por su cuenta, solo compara.
     *
     * Otorga TODAS las medallas elegibles de esas categorias que el usuario
     * aun no tenga (no solo "la que calza exacto"): asi un usuario que ya
     * estaba por encima del umbral ANTES de que existiera la medalla la recibe
     * la proxima vez que dispare el chequeo, sin necesitar backfill aparte.
     * INSERT IGNORE + UNIQUE KEY hacen esto idempotente: llamar dos veces con
     * el mismo estado no vuelve a otorgar nada.
     *
     * Devuelve los badge_id recien otorgados en esta llamada (vacio si ninguno).
     */
    public static function checkAndAward(\PDO $db, string $userId, array $currentValues): array {
        $newlyAwarded = [];

        foreach (self::CATALOG as $badge) {
            $key = $badge['category'] . (isset($badge['reaction']) ? ":{$badge['reaction']}" : '');
            if (!isset($currentValues[$key]) || $currentValues[$key] < $badge['threshold']) {
                continue;
            }

            $stmt = $db->prepare("INSERT IGNORE INTO user_badges (id, user_id, badge_id) VALUES (?, ?, ?)");
            $stmt->execute([(string)SnowflakeId::nextId(), $userId, $badge['id']]);

            if ($stmt->rowCount() > 0) {
                $newlyAwarded[] = $badge['id'];
            }
        }

        return $newlyAwarded;
    }

    /** @return array<int, array{badge_id: string, earned_at: string}> */
    public static function listForUser(\PDO $db, string $userId): array {
        $stmt = $db->prepare("SELECT badge_id, earned_at FROM user_badges WHERE user_id = ? ORDER BY earned_at ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
