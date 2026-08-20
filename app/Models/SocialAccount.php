<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SocialAccount
{
    public static function findByProvider(string $provider, string $uid): ?array
    {
        return Database::first(
            'SELECT * FROM social_accounts WHERE provider = ? AND provider_uid = ?',
            [$provider, (string) $uid]
        );
    }

    public static function findByUser(int $userId, string $provider): ?array
    {
        return Database::first(
            'SELECT * FROM social_accounts WHERE user_id = ? AND provider = ?',
            [$userId, $provider]
        );
    }

    public static function link(int $userId, string $provider, string $uid, ?string $accessToken = null, ?string $refreshToken = null, ?string $email = null): void
    {
        $existing = self::findByProvider($provider, $uid);
        if ($existing !== null) {
            Database::run(
                'UPDATE social_accounts
                 SET access_token = ?, refresh_token = ?, email = ?, last_used_at = NOW()
                 WHERE id = ?',
                [$accessToken, $refreshToken, $email, (int) $existing['id']]
            );
            return;
        }
        Database::run(
            'INSERT INTO social_accounts (user_id, provider, provider_uid, access_token, refresh_token, email, last_used_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$userId, $provider, (string) $uid, $accessToken, $refreshToken, $email]
        );
    }
}
