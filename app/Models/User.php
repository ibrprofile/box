<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::first('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    }

    public static function emailExists(string $email): bool
    {
        return (bool) Database::value('SELECT 1 FROM users WHERE email = ?', [mb_strtolower($email)]);
    }

    public static function create(array $data): int
    {
        return Database::insert(
            'INSERT INTO users (first_name, last_name, grade, email, password_hash, role, avatar_hue)
             VALUES (:first_name, :last_name, :grade, :email, :password_hash, :role, :hue)',
            [
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'grade'         => $data['grade'] ?? null,
                'email'         => mb_strtolower($data['email']),
                'password_hash' => $data['password_hash'] ?? null,
                'role'          => $data['role'] ?? 'student',
                'hue'           => $data['avatar_hue'] ?? random_int(0, 359),
            ]
        );
    }

    public static function touchLastSeen(int $id): void
    {
        Database::run('UPDATE users SET last_seen_at = NOW() WHERE id = ?', [$id]);
    }

    public static function setPassword(int $id, string $hash): void
    {
        Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public static function update(int $id, array $fields): void
    {
        $allowed = ['first_name', 'last_name', 'grade', 'role', 'status', 'avatar_hue'];
        $set = [];
        $params = [];
        foreach ($fields as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $set[] = "$key = ?";
                $params[] = $value;
            }
        }
        if ($set === []) {
            return;
        }
        $params[] = $id;
        Database::run('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?', $params);
    }
}
