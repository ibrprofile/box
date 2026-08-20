<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Subject
{
    /** Активные предметы со счётчиком опубликованных задач. */
    public static function allWithCounts(): array
    {
        return Database::all(
            'SELECT s.*, COUNT(t.id) AS task_count
               FROM subjects s
               LEFT JOIN tasks t ON t.subject_id = s.id AND t.is_active = 1
              WHERE s.is_active = 1
              GROUP BY s.id
              ORDER BY s.sort_order, s.name'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM subjects WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::first('SELECT * FROM subjects WHERE slug = ?', [$slug]);
    }

    /** Темы предмета для фильтра каталога. */
    public static function topics(int $subjectId): array
    {
        return Database::all(
            'SELECT id, name FROM topics WHERE subject_id = ? ORDER BY sort_order, name',
            [$subjectId]
        );
    }

    public static function all(): array
    {
        return Database::all('SELECT * FROM subjects ORDER BY sort_order, name');
    }
}
