<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Кибербезопасность: HTTP-заголовки, CSRF, экранирование,
 * простое ограничение частоты и контроль попыток входа.
 */
final class Security
{
    public static function sendSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
        header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "img-src 'self' data: https:; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com data:; "
            . "script-src 'self' 'unsafe-inline'; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Оконное ограничение частоты на базе сессии.
     */
    public static function rateLimit(string $bucket, int $max, int $window): bool
    {
        $now = time();
        $key = '_rl_' . $bucket;
        $data = $_SESSION[$key] ?? ['count' => 0, 'reset' => $now + $window];

        if ($now > $data['reset']) {
            $data = ['count' => 0, 'reset' => $now + $window];
        }

        $data['count']++;
        $_SESSION[$key] = $data;

        return $data['count'] <= $max;
    }

    public static function clientIp(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
