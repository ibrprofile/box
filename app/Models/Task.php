<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Task
{
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM tasks WHERE id = ?', [$id]);
    }

    /**
     * Умный подбор следующей задачи.
     *
     * Логика веса показа (user_task_stats.weight):
     *   - новые задачи (нет строки статистики) идут первыми;
     *   - затем — с наибольшим весом (растёт при ошибках);
     *   - решённые верно имеют низкий вес и давнюю дату показа.
     * RAND() внутри равных групп убирает предсказуемость.
     */
    public static function nextFor(int $userId, int $subjectId, ?int $topicId = null, array $excludeIds = []): ?array
    {
        $params = [$userId, $subjectId];
        $where  = 't.subject_id = ? AND t.is_active = 1';

        if ($topicId) {
            $where   .= ' AND t.topic_id = ?';
            $params[] = $topicId;
        }
        if ($excludeIds) {
            $where  .= ' AND t.id NOT IN (' . implode(',', array_fill(0, count($excludeIds), '?')) . ')';
            $params  = array_merge($params, $excludeIds);
        }

        $sql = "
            SELECT t.*, uts.weight, uts.solved, uts.wrong, uts.last_seen_at
              FROM tasks t
              LEFT JOIN user_task_stats uts
                     ON uts.task_id = t.id AND uts.user_id = ?
             WHERE {$where}
             ORDER BY
                   (CASE WHEN uts.user_id IS NULL THEN 0 ELSE 1 END),
                   COALESCE(uts.weight, 1) DESC,
                   COALESCE(uts.last_seen_at, '2000-01-01') ASC,
                   RAND()
             LIMIT 1";

        return Database::first($sql, array_merge([$userId], $params));
    }

    /** Публичное представление задачи (без ответа) для браузера. */
    public static function toClient(array $task): array
    {
        $options = null;
        if ($task['answer_type'] === 'choice' && !empty($task['options'])) {
            $decoded = json_decode((string) $task['options'], true);
            $options = is_array($decoded) ? array_values($decoded) : null;
        }

        return [
            'id'          => (int) $task['id'],
            'title'       => $task['title'],
            'difficulty'  => (int) $task['difficulty'],
            'answer_type' => $task['answer_type'],
            'statement'   => $task['statement'],
            'options'     => $options,
            'xp_reward'   => (int) $task['xp_reward'],
        ];
    }

    /**
     * Проверка ответа.
     * text  — сверяем нормализованные варианты, перечисленные через "|".
     * choice — correct_answer хранит индексы вариантов через запятую.
     */
    public static function check(array $task, string $given): array
    {
        if ($task['answer_type'] === 'choice') {
            $correct = self::normKey($given) === self::normKey((string) $task['correct_answer']);
        } else {
            $variants = array_map([self::class, 'norm'], explode('|', (string) $task['correct_answer']));
            $correct  = in_array(self::norm($given), $variants, true);
        }

        return [
            'correct'        => $correct,
            'solution'       => $task['solution'],
            'correct_answer' => $task['correct_answer'],
        ];
    }

    private static function norm(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/\s+/u', ' ', $s);
        return str_replace('ё', 'е', $s);
    }

    /** Нормализация набора индексов (для choice): "2,0" == "0,2". */
    private static function normKey(string $s): string
    {
        $parts = array_filter(array_map('trim', explode(',', $s)), static fn($v) => $v !== '');
        sort($parts, SORT_STRING);
        return implode(',', $parts);
    }

    /* --- Админка --------------------------------------------------------- */

    public static function paginate(int $page, int $perPage, array $filters = []): array
    {
        $where  = '1=1';
        $params = [];

        if (!empty($filters['subject_id'])) {
            $where   .= ' AND t.subject_id = ?';
            $params[] = (int) $filters['subject_id'];
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where   .= ' AND t.is_active = ?';
            $params[] = (int) $filters['is_active'];
        }
        if (!empty($filters['q'])) {
            $where   .= ' AND (t.title LIKE ? OR t.statement LIKE ?)';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }

        $total  = (int) Database::value("SELECT COUNT(*) FROM tasks t WHERE {$where}", $params);
        $offset = ($page - 1) * $perPage;

        $rows = Database::all(
            "SELECT t.*, s.name AS subject_name, tp.name AS topic_name
               FROM tasks t
               JOIN subjects s ON s.id = t.subject_id
               LEFT JOIN topics tp ON tp.id = t.topic_id
              WHERE {$where}
              ORDER BY t.updated_at DESC
              LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'pages' => (int) ceil($total / max(1, $perPage))];
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO tasks
                (subject_id, topic_id, title, statement, answer_type, correct_answer,
                 options, solution, difficulty, xp_reward, is_active, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $d['subject_id'], $d['topic_id'] ?: null, $d['title'], $d['statement'],
                $d['answer_type'], $d['correct_answer'], $d['options'], $d['solution'],
                $d['difficulty'], $d['xp_reward'], $d['is_active'], $d['created_by'],
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::run(
            'UPDATE tasks SET
                subject_id = ?, topic_id = ?, title = ?, statement = ?, answer_type = ?,
                correct_answer = ?, options = ?, solution = ?, difficulty = ?,
                xp_reward = ?, is_active = ?
             WHERE id = ?',
            [
                $d['subject_id'], $d['topic_id'] ?: null, $d['title'], $d['statement'],
                $d['answer_type'], $d['correct_answer'], $d['options'], $d['solution'],
                $d['difficulty'], $d['xp_reward'], $d['is_active'], $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM tasks WHERE id = ?', [$id]);
    }
}
