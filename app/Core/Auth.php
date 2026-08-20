<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;
        self::$loaded = false;
        self::$user = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        self::$user = null;
        self::$loaded = true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;

        $id = $_SESSION['uid'] ?? null;
        if ($id !== null) {
            self::$user = User::find((int) $id);
            if (self::$user === null) {
                self::logout();
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function is(string ...$roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    public static function isStaff(): bool
    {
        return self::is('manager', 'admin', 'superadmin');
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Response::redirect('/auth');
        }
    }

    public static function requireStaff(): void
    {
        self::requireAuth();
        if (!self::isStaff()) {
            Response::abort(403, 'Доступ только для сотрудников');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if (!self::is(...$roles)) {
            Response::abort(403, 'Недостаточно прав');
        }
    }
}
