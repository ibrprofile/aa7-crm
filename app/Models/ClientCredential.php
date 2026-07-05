<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ClientCredential
{
    public static function find(int $clientId): ?array
    {
        return Database::instance()->first(
            'SELECT * FROM client_credentials WHERE client_id = ?', [$clientId]
        );
    }

    public static function findByLogin(string $login): ?array
    {
        return Database::instance()->first(
            'SELECT cc.*, c.name AS client_name, c.email, c.phone
             FROM client_credentials cc
             JOIN clients c ON c.id = cc.client_id
             WHERE cc.login = ?',
            [$login]
        );
    }

    public static function upsert(int $clientId, string $login, string $password): void
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
        $existing = self::find($clientId);
        if ($existing) {
            Database::instance()->run(
                'UPDATE client_credentials SET login = ?, password_hash = ? WHERE client_id = ?',
                [$login, $hash, $clientId]
            );
        } else {
            Database::instance()->run(
                'INSERT INTO client_credentials (client_id, login, password_hash) VALUES (?,?,?)',
                [$clientId, $login, $hash]
            );
        }
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function touch(int $clientId): void
    {
        Database::instance()->run(
            'UPDATE client_credentials SET last_login_at = NOW() WHERE client_id = ?',
            [$clientId]
        );
    }

    public static function loginExists(string $login, ?int $excludeClientId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM client_credentials WHERE login = ?';
        $params = [$login];
        if ($excludeClientId !== null) {
            $sql .= ' AND client_id != ?';
            $params[] = $excludeClientId;
        }
        return (int) Database::instance()->value($sql, $params) > 0;
    }
}
