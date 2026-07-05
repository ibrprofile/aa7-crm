<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Компактный роутер с поддержкой параметров пути и middleware.
 */
final class Router
{
    private array $routes = [];

    /** @var string[] */
    private array $groupMiddleware = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(array $middleware, callable $callback): void
    {
        $prev = $this->groupMiddleware;
        $this->groupMiddleware = array_merge($prev, $middleware);
        $callback($this);
        $this->groupMiddleware = $prev;
    }

    private function add(string $method, string $path, string $handler, array $extra): void
    {
        $pattern = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $path);
        [$class, $action] = explode('@', $handler, 2);
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => '#^' . $pattern . '$#',
            'class'      => 'App\\Controllers\\' . $class,
            'action'     => $action,
            'middleware' => array_merge($this->groupMiddleware, $extra),
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $middleware) {
                (new $middleware())->handle($request);
            }

            $controller = new $route['class']();
            $action = $route['action'];
            $controller->$action($request, $params);
            return;
        }

        $this->notFound();
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>404 · AA7 CRM</title></head>'
            . '<body style="font-family:sans-serif;padding:4rem;text-align:center"><h1>404</h1><p>Страница не найдена</p>'
            . '<a href="/">← На главную</a></body></html>';
    }
}
