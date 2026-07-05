<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ClientSession
{
    private const TTL = 60 * 60 * 24 * 30; // 30 days

    public static function create(int $clientId): string
    {
        $id = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + self::TTL);
        Database::instance()->run(
            'INSERT INTO client_sessions (id, client_id, ip, user_agent, expires_at) VALUES (?,?,?,?,?)',
            [
                $id, $clientId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                $expires,
            ]
        );
        return $id;
    }

    public static function find(string $id): ?array
    {
        return Database::instance()->first(
            'SELECT cs.*, c.name AS client_name, c.email, c.type
             FROM client_sessions cs
             JOIN clients c ON c.id = cs.client_id
             WHERE cs.id = ? AND cs.expires_at > NOW()',
            [$id]
        );
    }

    public static function destroy(string $id): void
    {
        Database::instance()->run('DELETE FROM client_sessions WHERE id = ?', [$id]);
    }

    public static function purgeExpired(): void
    {
        Database::instance()->run('DELETE FROM client_sessions WHERE expires_at <= NOW()');
    }
}
