<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Объект HTTP-запроса: доступ к параметрам, методу и пути.
 */
final class Request
{
    public string $method;
    public string $path;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->input($key);
        return $value === null || $value === '' ? $default : (int) $value;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->input($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return (float) str_replace([' ', ','], ['', '.'], (string) $value);
    }

    public function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requested = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return str_contains($accept, 'application/json')
            || strtolower($requested) === 'xmlhttprequest';
    }
}
