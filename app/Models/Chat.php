<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Chat
{
    public static function ensureThread(int $userId): array
    {
        $existing = self::threadForUser($userId);
        if ($existing !== null) {
            return $existing;
        }

        $id = Database::insert('INSERT INTO chat_threads (user_id) VALUES (?)', [$userId]);
        return self::threadForUser($userId) ?? ['id' => $id, 'user_id' => $userId, 'status' => 'open'];
    }

    public static function threadForUser(int $userId): ?array
    {
        return Database::first('SELECT * FROM chat_threads WHERE user_id = ?', [$userId]);
    }

    public static function messages(int $threadId): array
    {
        return Database::all(
            'SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY id ASC LIMIT 200',
            [$threadId]
        );
    }

    public static function poll(int $threadId, int $sinceId): array
    {
        return Database::all(
            'SELECT * FROM chat_messages WHERE thread_id = ? AND id > ? ORDER BY id ASC',
            [$threadId, $sinceId]
        );
    }

    public static function send(int $threadId, string $senderType, int $senderId, string $body): void
    {
        Database::run(
            'INSERT INTO chat_messages (thread_id, sender_type, sender_id, body) VALUES (?, ?, ?, ?)',
            [$threadId, $senderType, $senderId, trim($body)]
        );

        if ($senderType === 'user') {
            Database::run('UPDATE chat_threads SET last_message_at = NOW(), unread_staff = unread_staff + 1 WHERE id = ?', [$threadId]);
            return;
        }

        Database::run('UPDATE chat_threads SET last_message_at = NOW(), unread_user = unread_user + 1 WHERE id = ?', [$threadId]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function allThreads(): array
    {
        return Database::all(
            'SELECT ct.*, u.first_name, u.last_name, u.email, u.avatar_hue
               FROM chat_threads ct
               JOIN users u ON u.id = ct.user_id
              ORDER BY COALESCE(ct.last_message_at, ct.id) DESC'
        );
    }
}
