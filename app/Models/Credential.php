<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Credential
{
    public static function store(int $userId, string $credentialId, string $publicKey, int $signCount, ?string $label = null): void
    {
        Database::run(
            'INSERT INTO webauthn_credentials (user_id, credential_id, public_key, sign_count, label)
             VALUES (?, ?, ?, ?, ?)',
            [$userId, $credentialId, $publicKey, $signCount, $label]
        );
    }

    public static function findByCredentialId(string $credentialId): ?array
    {
        return Database::first('SELECT * FROM webauthn_credentials WHERE credential_id = ?', [$credentialId]);
    }

    /** @return array<int,string> credential_id (base64url) пользователя */
    public static function idsForUser(int $userId): array
    {
        $rows = Database::all('SELECT credential_id FROM webauthn_credentials WHERE user_id = ?', [$userId]);
        return array_column($rows, 'credential_id');
    }

    public static function countForUser(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM webauthn_credentials WHERE user_id = ?', [$userId]);
    }

    public static function markUsed(int $id, int $signCount): void
    {
        Database::run('UPDATE webauthn_credentials SET last_used_at = NOW(), sign_count = ? WHERE id = ?', [$signCount, $id]);
    }
}
