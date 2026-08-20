<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class StressWord
{
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM stress_words WHERE id = ?', [$id]);
    }

    /**
     * Пачка слов для тренажёра. Приоритет — слова, где пользователь чаще
     * ошибается (высокий weight) и новые, ещё не встречавшиеся.
     */
    public static function batchFor(int $userId, int $limit = 12, array $excludeIds = []): array
    {
        $params = [$userId];
        $where  = 'w.is_active = 1';

        if ($excludeIds) {
            $where  .= ' AND w.id NOT IN (' . implode(',', array_fill(0, count($excludeIds), '?')) . ')';
            $params  = array_merge($params, $excludeIds);
        }

        $limit = max(1, min(50, $limit));

        return Database::all(
            "SELECT w.id, w.word, w.stress_index, w.hint, w.difficulty
               FROM stress_words w
               LEFT JOIN user_word_stats uws
                      ON uws.word_id = w.id AND uws.user_id = ?
              WHERE {$where}
              ORDER BY
                    (CASE WHEN uws.user_id IS NULL THEN 0 ELSE 1 END),
                    COALESCE(uws.weight, 1) DESC,
                    COALESCE(uws.last_seen_at, '2000-01-01') ASC,
                    RAND()
              LIMIT {$limit}",
            $params
        );
    }

    /**
     * Фиксирует результат по слову и корректирует вес показа.
     * Ошибка: weight += 1.0. Верно: weight *= 0.55 (не ниже 0.15), streak++.
     */
    public static function record(int $userId, int $wordId, bool $correct): void
    {
        $stat = Database::first(
            'SELECT weight, streak FROM user_word_stats WHERE user_id = ? AND word_id = ?',
            [$userId, $wordId]
        );

        if ($stat === null) {
            Database::run(
                'INSERT INTO user_word_stats
                    (user_id, word_id, attempts, wrong, streak, weight, last_seen_at)
                 VALUES (?,?,1,?,?,?,NOW())',
                [$userId, $wordId, $correct ? 0 : 1, $correct ? 1 : 0, $correct ? 0.55 : 2.0]
            );
            return;
        }

        $weight = $correct ? max(0.15, (float) $stat['weight'] * 0.55) : (float) $stat['weight'] + 1.0;
        $streak = $correct ? (int) $stat['streak'] + 1 : 0;

        Database::run(
            'UPDATE user_word_stats SET
                attempts = attempts + 1,
                wrong    = wrong + ?,
                streak   = ?,
                weight   = ?,
                last_seen_at = NOW()
             WHERE user_id = ? AND word_id = ?',
            [$correct ? 0 : 1, $streak, $weight, $userId, $wordId]
        );
    }

    public static function summary(int $userId): array
    {
        $row = Database::first(
            'SELECT COUNT(*) AS words, SUM(attempts) AS attempts, SUM(wrong) AS wrong, MAX(streak) AS best_streak
               FROM user_word_stats WHERE user_id = ?',
            [$userId]
        ) ?? [];

        $attempts = (int) ($row['attempts'] ?? 0);
        $wrong    = (int) ($row['wrong'] ?? 0);

        return [
            'words'       => (int) ($row['words'] ?? 0),
            'attempts'    => $attempts,
            'accuracy'    => $attempts > 0 ? (int) round(($attempts - $wrong) / $attempts * 100) : 0,
            'best_streak' => (int) ($row['best_streak'] ?? 0),
        ];
    }

    /* --- Админка --------------------------------------------------------- */

    public static function all(): array
    {
        return Database::all('SELECT * FROM stress_words ORDER BY word');
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO stress_words (word, stress_index, difficulty, hint, is_active) VALUES (?,?,?,?,?)',
            [$d['word'], $d['stress_index'], $d['difficulty'], $d['hint'] ?: null, $d['is_active']]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::run(
            'UPDATE stress_words SET word = ?, stress_index = ?, difficulty = ?, hint = ?, is_active = ? WHERE id = ?',
            [$d['word'], $d['stress_index'], $d['difficulty'], $d['hint'] ?: null, $d['is_active'], $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM stress_words WHERE id = ?', [$id]);
    }
}
