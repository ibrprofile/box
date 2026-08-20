<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Leaderboard
{
    /** Топ-50 по опыту. */
    public static function top(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        return Database::all(
            "SELECT id, first_name, last_name, grade, xp, level, streak_best, avatar_hue
               FROM users
              WHERE status = 'active' AND role = 'student'
              ORDER BY xp DESC, level DESC, id ASC
              LIMIT {$limit}"
        );
    }

    /** Ранг конкретного пользователя (1-based). */
    public static function rankOf(int $userId, int $xp): int
    {
        $ahead = (int) Database::value(
            "SELECT COUNT(*) FROM users
              WHERE status = 'active' AND role = 'student' AND xp > ?",
            [$xp]
        );
        return $ahead + 1;
    }
}
