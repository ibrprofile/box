<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $status = 400): void
    {
        self::json(['ok' => false, 'error' => $message], $status);
    }

    public static function ok(array $data = []): void
    {
        self::json(['ok' => true] + $data);
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    public static function abort(int $status, string $message = ''): void
    {
        http_response_code($status);
        View::render('errors/error', [
            'status'  => $status,
            'message' => $message ?: 'Что-то пошло не так',
        ], 'auth');
        exit;
    }
}
