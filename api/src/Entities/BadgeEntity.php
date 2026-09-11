<?php

declare(strict_types=1);

namespace Libringo\Entities;

use Libringo\Utils\SnowflakeId;

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
        ['id' => 'streak_730',        'category' => 'streak',   'threshold' => 730],
        ['id' => 'following_1',       'category' => 'following', 'threshold' => 1],
        ['id' => 'following_5',       'category' => 'following', 'threshold' => 5],
        ['id' => 'following_20',      'category' => 'following', 'threshold' => 20],
        ['id' => 'following_50',      'category' => 'following', 'threshold' => 50],
        ['id' => 'followers_5',       'category' => 'followers', 'threshold' => 5],
        ['id' => 'followers_20',      'category' => 'followers', 'threshold' => 20],
        ['id' => 'followers_50',      'category' => 'followers', 'threshold' => 50],
        ['id' => 'reaction_loved_10',      'category' => 'reaction_loved',      'threshold' => 10],
        ['id' => 'reaction_thoughtful_10', 'category' => 'reaction_thoughtful', 'threshold' => 10],
        ['id' => 'reaction_peaceful_10',   'category' => 'reaction_peaceful',   'threshold' => 10],
        ['id' => 'reaction_challenged_10', 'category' => 'reaction_challenged', 'threshold' => 10],
        ['id' => 'reaction_moved_10',      'category' => 'reaction_moved',      'threshold' => 10],
        ['id' => 'days_read_50',      'category' => 'days_read',     'threshold' => 50],
        ['id' => 'days_read_100',     'category' => 'days_read',     'threshold' => 100],
        ['id' => 'days_read_365',     'category' => 'days_read',     'threshold' => 365],
        ['id' => 'days_read_730',     'category' => 'days_read',     'threshold' => 730],
        ['id' => 'mutual_5',          'category' => 'mutual',        'threshold' => 5],
        ['id' => 'mutual_20',         'category' => 'mutual',        'threshold' => 20],
        ['id' => 'mutual_50',         'category' => 'mutual',        'threshold' => 50],
        ['id' => 'nudge_sent_10',     'category' => 'nudge_sent',     'threshold' => 10],
        ['id' => 'nudge_sent_50',     'category' => 'nudge_sent',     'threshold' => 50],
        ['id' => 'nudge_sent_100',    'category' => 'nudge_sent',     'threshold' => 100],
        ['id' => 'nudge_received_10', 'category' => 'nudge_received', 'threshold' => 10],
        ['id' => 'nudge_received_50', 'category' => 'nudge_received', 'threshold' => 50],
        ['id' => 'nudge_received_100', 'category' => 'nudge_received', 'threshold' => 100],
        ['id' => 'founder',           'category' => 'founder',        'threshold' => 1],
        ['id' => 'pages_100',         'category' => 'pages',          'threshold' => 100],
        ['id' => 'pages_500',         'category' => 'pages',          'threshold' => 500],
        ['id' => 'pages_1000',        'category' => 'pages',          'threshold' => 1000],
        ['id' => 'pages_2500',        'category' => 'pages',          'threshold' => 2500],
        ['id' => 'pages_5000',        'category' => 'pages',          'threshold' => 5000],
        ['id' => 'books_finished_1',  'category' => 'books_finished', 'threshold' => 1],
        ['id' => 'books_finished_5',  'category' => 'books_finished', 'threshold' => 5],
        ['id' => 'books_finished_10', 'category' => 'books_finished', 'threshold' => 10],
        ['id' => 'books_finished_25', 'category' => 'books_finished', 'threshold' => 25],
    ];

    /**
     * $currentValues: mapa "categoria" (ej. "reaction_loved") => valor
     * numerico actual. El caller decide que es barato calcular en su
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
            $key = $badge['category'];
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
