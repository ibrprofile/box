<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Attempt
{
    /**
     * Записывает попытку и пересчитывает адаптивный вес показа задачи.
     * Вес: ошибка увеличивает (+0.8), верный ответ уменьшает (×0.5, но не ниже 0.2).
     */
    public static function record(int $userId, int $taskId, string $answer, bool $correct, int $timeMs): void
    {
        Database::run(
            'INSERT INTO task_attempts (user_id, task_id, user_answer, is_correct, time_spent_ms)
             VALUES (?,?,?,?,?)',
            [$userId, $taskId, mb_substr($answer, 0, 500), $correct ? 1 : 0, max(0, $timeMs)]
        );

        $stat = Database::first(
            'SELECT weight FROM user_task_stats WHERE user_id = ? AND task_id = ?',
            [$userId, $taskId]
        );

        if ($stat === null) {
            $weight = $correct ? 0.5 : 1.8;
            Database::run(
                'INSERT INTO user_task_stats
                    (user_id, task_id, attempts, correct, wrong, weight, solved, last_seen_at)
                 VALUES (?,?,1,?,?,?,?,NOW())',
                [$userId, $taskId, $correct ? 1 : 0, $correct ? 0 : 1, $weight, $correct ? 1 : 0]
            );
            return;
        }

        $weight = $correct
            ? max(0.2, (float) $stat['weight'] * 0.5)
            : (float) $stat['weight'] + 0.8;

        Database::run(
            'UPDATE user_task_stats SET
                attempts = attempts + 1,
                correct  = correct + ?,
                wrong    = wrong + ?,
                weight   = ?,
                solved   = GREATEST(solved, ?),
                last_seen_at = NOW()
             WHERE user_id = ? AND task_id = ?',
            [$correct ? 1 : 0, $correct ? 0 : 1, $weight, $correct ? 1 : 0, $userId, $taskId]
        );
    }

    /** Сводка каталога для профиля. */
    public static function summary(int $userId): array
    {
        $row = Database::first(
            'SELECT
                COUNT(*)                 AS seen,
                SUM(solved)              AS solved,
                SUM(attempts)            AS attempts,
                SUM(correct)             AS correct
             FROM user_task_stats WHERE user_id = ?',
            [$userId]
        ) ?? [];

        $attempts = (int) ($row['attempts'] ?? 0);
        $correct  = (int) ($row['correct'] ?? 0);

        return [
            'seen'     => (int) ($row['seen'] ?? 0),
            'solved'   => (int) ($row['solved'] ?? 0),
            'attempts' => $attempts,
            'accuracy' => $attempts > 0 ? (int) round($correct / $attempts * 100) : 0,
        ];
    }

    /** Точность по предметам для профиля. */
    public static function bySubject(int $userId): array
    {
        return Database::all(
            'SELECT s.name, s.hue,
                    COUNT(uts.task_id) AS seen,
                    SUM(uts.solved)    AS solved
               FROM user_task_stats uts
               JOIN tasks t    ON t.id = uts.task_id
               JOIN subjects s ON s.id = t.subject_id
              WHERE uts.user_id = ?
              GROUP BY s.id
              ORDER BY seen DESC',
            [$userId]
        );
    }
}
