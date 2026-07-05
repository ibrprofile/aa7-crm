<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function findByLogin(string $login): ?array
    {
        return Database::instance()->first(
            'SELECT * FROM users WHERE login = ? LIMIT 1',
            [$login]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::instance()->first('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    public static function all(): array
    {
        return Database::instance()->all('SELECT * FROM users ORDER BY role = \'admin\' DESC, name ASC');
    }

    public static function create(array $data): int
    {
        return Database::instance()->insert(
            'INSERT INTO users (login, name, email, phone, password_hash, role, avatar_color, is_active)
             VALUES (:login, :name, :email, :phone, :password_hash, :role, :avatar_color, :is_active)',
            $data
        );
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        Database::instance()->run(
            'UPDATE users SET name = :name, email = :email, phone = :phone,
                role = :role, avatar_color = :avatar_color, is_active = :is_active
             WHERE id = :id',
            $data
        );
    }

    public static function updatePassword(int $id, string $hash): void
    {
        Database::instance()->run('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public static function touchLastLogin(int $id): void
    {
        Database::instance()->run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function toggleActive(int $id, bool $active): void
    {
        Database::instance()->run('UPDATE users SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }
}
