<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;

/**
 * AA7 CRM — единая точка входа.
 */

$config = require __DIR__ . '/config/config.php';

$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (preg_match('#^/assets/(.+)$#', $uri, $m)) {
    $asset = __DIR__ . '/public/assets/' . $m[1];
    if (is_file($asset)) {
        $ext = pathinfo($asset, PATHINFO_EXTENSION);
        $mime = match ($ext) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        readfile($asset);
        exit;
    }
}

date_default_timezone_set($config['app']['timezone']);

error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');

// Автозагрузка классов App\* из каталога /app
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Безопасные параметры сессии
session_name($config['session']['name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
]);
session_start();

Security::sendSecurityHeaders();
Database::boot($config['db']);

$GLOBALS['config'] = $config;

$router = new Router();
$routes = require __DIR__ . '/app/routes.php';
$routes($router);

try {
    $router->dispatch(new Request());
} catch (\Throwable $e) {
    if ($config['app']['debug']) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Ошибка: {$e->getMessage()}\n{$e->getFile()}:{$e->getLine()}\n\n{$e->getTraceAsString()}";
        exit;
    }
    error_log('[AA7] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>500 · AA7 CRM</title>'
        . '<style>body{font-family:sans-serif;background:#0d100f;color:#e8f0ec;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
        . '.box{text-align:center}.code{font-size:72px;font-weight:800;color:#3ecf8e;font-family:monospace}</style></head>'
        . '<body><div class="box"><div class="code">500</div><h1>Внутренняя ошибка</h1><p>Что-то пошло не так. Попробуйте позже.</p>'
        . '<a href="/" style="color:#3ecf8e">← На главную</a></div></body></html>';
}
