<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class EmailVerification
{
    private const TTL_SECONDS = 900;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN = 60;

    public static function issue(string $email, string $purpose = 'register'): string
    {
        $email = mb_strtolower(trim($email));
        self::purgeExpired();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Database::run(
            'INSERT INTO email_verification_codes (email, code, purpose, expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))',
            [$email, $code, $purpose, self::TTL_SECONDS]
        );

        return $code;
    }

    public static function canResend(string $email, string $purpose = 'register'): bool
    {
        $email = mb_strtolower(trim($email));
        $row = Database::first(
            'SELECT created_at FROM email_verification_codes
             WHERE email = ? AND purpose = ?
             ORDER BY id DESC LIMIT 1',
            [$email, $purpose]
        );
        if (!$row) {
            return true;
        }
        return (time() - (int) strtotime($row['created_at'])) >= self::RESEND_COOLDOWN;
    }

    public static function verify(string $email, string $purpose, string $code): bool
    {
        $email = mb_strtolower(trim($email));
        $code  = trim($code);
        self::purgeExpired();

        $row = Database::first(
            'SELECT id, code, attempts FROM email_verification_codes
             WHERE email = ? AND purpose = ? AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1',
            [$email, $purpose]
        );
        if (!$row) {
            return false;
        }

        Database::run(
            'UPDATE email_verification_codes SET attempts = attempts + 1 WHERE id = ?',
            [(int) $row['id']]
        );

        if ((int) $row['attempts'] >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (!hash_equals((string) $row['code'], $code)) {
            return false;
        }

        self::invalidate($email, $purpose);
        return true;
    }

    public static function invalidate(string $email, string $purpose): void
    {
        Database::run(
            'DELETE FROM email_verification_codes WHERE email = ? AND purpose = ?',
            [mb_strtolower(trim($email)), $purpose]
        );
    }

    private static function purgeExpired(): void
    {
        Database::run('DELETE FROM email_verification_codes WHERE expires_at < NOW()');
    }
}
