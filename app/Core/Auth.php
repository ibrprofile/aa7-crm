<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Управление сессией пользователя, ролями и попытками входа.
 */
final class Auth
{
    public static function attempt(string $login, string $password): ?array
    {
        $user = User::findByLogin($login);
        if ($user === null || (int) $user['is_active'] !== 1) {
            return null;
        }
        if (!Security::verifyPassword($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['last_seen']  = time();
        User::touchLastLogin((int) $user['id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function name(): string
    {
        return $_SESSION['user_name'] ?? 'Пользователь';
    }

    public static function user(): ?array
    {
        $id = self::id();
        return $id === null ? null : User::find($id);
    }
}
