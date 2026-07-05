<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Базовый контроллер: рендер вью в layout, редиректы и JSON-ответы.
 */
class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $data['_flash'] = Flash::pull();
        $data['_csrf']  = Security::csrfToken();

        extract($data, EXTR_SKIP);

        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';
        if (!is_file($viewPath)) {
            throw new \RuntimeException("Вью не найдена: {$view}");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        $layoutPath = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
        require $layoutPath;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function requireCsrf(Request $request): void
    {
        if ($request->isPost() && !Security::verifyCsrf($request->input('_csrf'))) {
            if ($request->wantsJson()) {
                $this->json(['error' => 'Недействительный токен запроса.'], 419);
            }
            http_response_code(419);
            exit('Недействительный токен запроса.');
        }
    }
}
