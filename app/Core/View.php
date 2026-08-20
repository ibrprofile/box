<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private const BASE = __DIR__ . '/../../views/';

    /**
     * Рендерит страницу внутри выбранного макета.
     *
     * @param string $template  путь шаблона относительно /views без расширения
     * @param array  $data      переменные шаблона
     * @param string $layout    имя макета в /views/layouts ('app', 'auth', 'admin' или '' без макета)
     */
    public static function render(string $template, array $data = [], string $layout = 'app'): void
    {
        $content = self::capture($template, $data);

        if ($layout === '') {
            echo $content;
            return;
        }

        echo self::capture('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function capture(string $template, array $data = []): string
    {
        $file = self::BASE . $template . '.php';
        if (!is_file($file)) {
            if ($template !== 'errors/error') {
                return self::capture('errors/error', ['status' => 500, 'message' => 'Шаблон не найден: ' . $template]);
            }
            throw new \RuntimeException("Шаблон не найден: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** Рендерит партиал и возвращает строку. */
    public static function partial(string $template, array $data = []): string
    {
        return self::capture('partials/' . $template, $data);
    }
}
