<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string,array<int,array{pattern:string,handler:array,params:array<int,string>}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $params = [];
        $pattern = preg_replace_callback('#\{(\w+)\}#', static function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'params'  => $params,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri    = $this->currentPath();

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);
                $args = array_combine($route['params'], $matches) ?: [];

                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->$action($request, $args);
                return;
            }
        }

        Response::abort(404, 'Страница не найдена');
    }

    private function currentPath(): string
    {
        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = config('app.base_path');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }
}
