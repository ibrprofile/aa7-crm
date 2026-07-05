<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class OrderMessage
{
    public static function forOrder(int $orderId): array
    {
        return Database::instance()->all(
            'SELECT m.*,
                u.name  AS sender_name,
                u.avatar_color AS sender_color,
                GROUP_CONCAT(f.id, ":", f.original_name, ":", f.stored_name, ":", f.mime_type SEPARATOR "|") AS files_raw
             FROM order_messages m
             LEFT JOIN users u ON u.id = m.sender_id AND m.from_client = 0
             LEFT JOIN order_files f ON f.message_id = m.id
             WHERE m.order_id = ?
             GROUP BY m.id
             ORDER BY m.created_at ASC',
            [$orderId]
        );
    }

    public static function forOrderAndClient(int $orderId, int $clientId): array
    {
        return Database::instance()->all(
            'SELECT m.*,
                u.name  AS sender_name,
                u.avatar_color AS sender_color,
                GROUP_CONCAT(f.id, ":", f.original_name, ":", f.stored_name, ":", f.mime_type SEPARATOR "|") AS files_raw
             FROM order_messages m
             LEFT JOIN users u ON u.id = m.sender_id AND m.from_client = 0
             LEFT JOIN order_files f ON f.message_id = m.id
             WHERE m.order_id = ? AND m.client_id = ?
             GROUP BY m.id
             ORDER BY m.created_at ASC',
            [$orderId, $clientId]
        );
    }

    public static function create(array $data): int
    {
        return Database::instance()->insert(
            'INSERT INTO order_messages (order_id, client_id, from_client, sender_id, body)
             VALUES (:order_id, :client_id, :from_client, :sender_id, :body)',
            $data
        );
    }

    public static function parseFiles(string $raw): array
    {
        if (!$raw) return [];
        $files = [];
        foreach (explode('|', $raw) as $chunk) {
            $parts = explode(':', $chunk, 4);
            if (count($parts) === 4) {
                $files[] = [
                    'id'            => (int)$parts[0],
                    'original_name' => $parts[1],
                    'stored_name'   => $parts[2],
                    'mime_type'     => $parts[3],
                ];
            }
        }
        return $files;
    }
}
