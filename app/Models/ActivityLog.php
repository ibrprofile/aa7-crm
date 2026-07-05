<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Security;

final class ActivityLog
{
    public static function record(string $action, ?string $entity = null, ?int $entityId = null, ?string $description = null): void
    {
        Database::instance()->run(
            'INSERT INTO activity_log (user_id, action, entity, entity_id, description, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::id(),
                $action,
                $entity,
                $entityId,
                $description,
                Security::clientIp(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]
        );
    }

    public static function recent(int $limit = 40, ?int $userId = null): array
    {
        $sql = 'SELECT l.*, u.name AS user_name, u.avatar_color
                FROM activity_log l
                LEFT JOIN users u ON u.id = l.user_id';
        $params = [];
        if ($userId !== null) {
            $sql .= ' WHERE l.user_id = ?';
            $params[] = $userId;
        }
        $sql .= ' ORDER BY l.id DESC LIMIT ' . max(1, min(200, $limit));
        return Database::instance()->all($sql, $params);
    }
}
