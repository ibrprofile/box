<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Опыт, уровни, серия дней и достижения.
 * Единая точка начисления наград, чтобы логика не дублировалась.
 */
final class Gamification
{
    /** Совокупный XP, необходимый для достижения уровня $level. */
    public static function xpForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }
        // Плавно растущая кривая: сумма 60 * n^1.35.
        $total = 0;
        for ($n = 1; $n < $level; $n++) {
            $total += (int) round(60 * pow($n, 1.35));
        }
        return $total;
    }

    public static function levelForXp(int $xp): int
    {
        $level = 1;
        while ($xp >= self::xpForLevel($level + 1)) {
            $level++;
        }
        return $level;
    }

    /** Прогресс внутри текущего уровня для прогресс-бара. */
    public static function levelProgress(int $xp): array
    {
        $level = self::levelForXp($xp);
        $floor = self::xpForLevel($level);
        $ceil  = self::xpForLevel($level + 1);
        $span  = max(1, $ceil - $floor);
        return [
            'level'    => $level,
            'into'     => $xp - $floor,
            'need'     => $ceil - $floor,
            'percent'  => (int) round(($xp - $floor) / $span * 100),
            'next_at'  => $ceil,
        ];
    }

    /**
     * Начисляет XP, обновляет уровень и серию, проверяет достижения.
     * Возвращает список событий для показа пользователю.
     */
    public static function award(int $userId, int $xp): array
    {
        $events = [];

        $before = Database::first('SELECT xp, level FROM users WHERE id = ?', [$userId]);
        if ($before === null) {
            return $events;
        }

        $streak = self::updateStreak($userId);
        if ($streak['advanced']) {
            $events[] = ['type' => 'streak', 'value' => $streak['count']];
        }

        $newXp = (int) $before['xp'] + max(0, $xp);
        $newLevel = self::levelForXp($newXp);

        Database::run('UPDATE users SET xp = ?, level = ? WHERE id = ?', [$newXp, $newLevel, $userId]);

        if ($newLevel > (int) $before['level']) {
            $events[] = ['type' => 'level', 'value' => $newLevel];
        }
        if ($xp > 0) {
            $events[] = ['type' => 'xp', 'value' => $xp];
        }

        foreach (self::checkAchievements($userId, $newLevel) as $ach) {
            $events[] = ['type' => 'achievement', 'value' => $ach];
        }

        return $events;
    }

    /** Обновляет серию дней активности. */
    private static function updateStreak(int $userId): array
    {
        $u = Database::first('SELECT streak_count, streak_best, streak_day FROM users WHERE id = ?', [$userId]);
        $today = date('Y-m-d');
        $last  = $u['streak_day'] ?? null;

        if ($last === $today) {
            return ['advanced' => false, 'count' => (int) $u['streak_count']];
        }

        $count = 1;
        if ($last !== null && $last === date('Y-m-d', strtotime('-1 day'))) {
            $count = (int) $u['streak_count'] + 1;
        }
        $best = max((int) $u['streak_best'], $count);

        Database::run(
            'UPDATE users SET streak_count = ?, streak_best = ?, streak_day = ? WHERE id = ?',
            [$count, $best, $today, $userId]
        );
        return ['advanced' => true, 'count' => $count];
    }

    /** Проверяет и выдаёт новые достижения. Возвращает выданные. */
    private static function checkAchievements(int $userId, int $level): array
    {
        $metrics = self::metrics($userId, $level);

        $pending = Database::all(
            'SELECT a.* FROM achievements a
             WHERE a.id NOT IN (SELECT achievement_id FROM user_achievements WHERE user_id = ?)',
            [$userId]
        );

        $granted = [];
        foreach ($pending as $a) {
            $value = $metrics[$a['metric']] ?? 0;
            if ($value >= (int) $a['threshold']) {
                Database::run(
                    'INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)',
                    [$userId, $a['id']]
                );
                if ((int) $a['xp_reward'] > 0) {
                    Database::run('UPDATE users SET xp = xp + ? WHERE id = ?', [(int) $a['xp_reward'], $userId]);
                }
                $granted[] = ['title' => $a['title'], 'icon' => $a['icon']];
            }
        }
        return $granted;
    }

    private static function metrics(int $userId, int $level): array
    {
        return [
            'tasks_solved'  => (int) Database::value('SELECT COUNT(*) FROM user_task_stats WHERE user_id = ? AND solved = 1', [$userId]),
            'words_correct' => (int) Database::value('SELECT COALESCE(SUM(attempts - wrong),0) FROM user_word_stats WHERE user_id = ?', [$userId]),
            'streak_days'   => (int) Database::value('SELECT streak_best FROM users WHERE id = ?', [$userId]),
            'level'         => $level,
            'perfect_run'   => (int) Database::value('SELECT MAX(streak) FROM user_word_stats WHERE user_id = ?', [$userId]),
        ];
    }
}
