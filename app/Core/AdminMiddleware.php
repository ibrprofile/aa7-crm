<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Ограничивает доступ разделами администратора.
 */
final class AdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::isAdmin()) {
            Flash::error('Недостаточно прав для доступа к разделу.');
            header('Location: /');
            exit;
        }
    }
}
