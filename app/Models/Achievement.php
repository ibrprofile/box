<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Achievement
{
    /** Все достижения с отметкой, разблокировано ли и когда. */
    public static function forUser(int $userId): array
    {
        return Database::all(
            'SELECT a.*, ua.unlocked_at
               FROM achievements a
               LEFT JOIN user_achievements ua
                      ON ua.achievement_id = a.id AND ua.user_id = ?
              ORDER BY (ua.unlocked_at IS NULL), a.sort_order, a.threshold',
            [$userId]
        );
    }

    public static function unlockedCount(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM user_achievements WHERE user_id = ?', [$userId]);
    }

    public static function total(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM achievements');
    }
}
