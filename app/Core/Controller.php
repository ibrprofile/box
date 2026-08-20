<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'app'): void
    {
        View::render($template, $data, $layout);
    }

    /** Текущий пользователь (гарантированно есть за защищёнными маршрутами). */
    protected function user(): array
    {
        return Auth::user() ?? [];
    }

    protected function json(mixed $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function abort(int $status, string $message = ''): void
    {
        Response::abort($status, $message);
    }

    protected function verifyCsrf(Request $request): void
    {
        $token = $request->input('_csrf') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($token) || !csrf_verify($token)) {
            Response::error('Сессия устарела, обновите страницу', 419);
        }
    }
}
