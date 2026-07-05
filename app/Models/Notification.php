<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Notification
{
    public static function forUser(int $userId, int $limit = 50): array
    {
        return Database::instance()->all(
            'SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL
             ORDER BY id DESC LIMIT ' . max(1, min(100, $limit)),
            [$userId]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::instance()->value(
            'SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0',
            [$userId]
        );
    }

    public static function push(?int $userId, string $type, string $icon, string $title, ?string $body = null, ?string $link = null): int
    {
        return Database::instance()->insert(
            'INSERT INTO notifications (user_id, type, icon, title, body, link) VALUES (?,?,?,?,?,?)',
            [$userId, $type, $icon, $title, $body, $link]
        );
    }

    public static function markAllRead(int $userId): void
    {
        Database::instance()->run(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? OR user_id IS NULL',
            [$userId]
        );
    }

    public static function markRead(int $id, int $userId): void
    {
        Database::instance()->run(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)',
            [$id, $userId]
        );
    }
}
