<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Требует аутентифицированную сессию и завершает простаивающие сессии.
 */
final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            Flash::error('Требуется вход в систему.');
            header('Location: /login');
            exit;
        }

        $config = $GLOBALS['config'] ?? [];
        $idle   = (int) ($config['session']['idle'] ?? 1800);

        if (isset($_SESSION['last_seen']) && (time() - $_SESSION['last_seen']) > $idle) {
            Auth::logout();
            session_start();
            Flash::info('Сессия завершена из-за неактивности.');
            header('Location: /login');
            exit;
        }
        $_SESSION['last_seen'] = time();
    }
}
