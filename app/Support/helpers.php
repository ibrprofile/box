<?php
declare(strict_types=1);

use App\Core\Auth;

/**
 * Глобальные хелперы приложения.
 */

require __DIR__ . '/icons.php';

$GLOBALS['__config'] = $GLOBALS['__config'] ?? [];

function config(string $key, mixed $default = null): mixed
{
    $segments = explode('.', $key);
    $value = $GLOBALS['__config'];
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/** Абсолютный путь внутри приложения с учётом base_path. */
function url(string $path = '/'): string
{
    $base = config('app.base_path', '');
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}

/** Путь к статическому ресурсу. */
function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

/** Экранирование для вывода в HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function auth(): ?array
{
    return Auth::user();
}

/** Рендер партиала из /views/partials в строку. */
function partial(string $template, array $data = []): string
{
    return \App\Core\View::partial($template, $data);
}

function current_path(): string
{
    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = config('app.base_path', '');
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = '/' . trim($uri, '/');
    return $uri === '/' ? '/' : rtrim($uri, '/');
}

/** Активна ли ссылка (по точному совпадению или префиксу секции). */
function nav_active(string $prefix): bool
{
    $path = current_path();
    if ($prefix === '/app' || $prefix === '/admin') {
        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
    return $path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/');
}

/* --- CSRF ------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_verify(string $token): bool
{
    return !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/* --- Flash ------------------------------------------------------------ */

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $out = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $out;
}

/* --- Форматирование --------------------------------------------------- */

/** Человеко-читаемое «сколько времени назад». */
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60)    return 'только что';
    if ($diff < 3600)  return intdiv($diff, 60) . ' мин назад';
    if ($diff < 86400) return intdiv($diff, 3600) . ' ч назад';
    if ($diff < 604800) return intdiv($diff, 86400) . ' дн назад';
    return date('d.m.Y', $ts);
}

function initials(array $user): string
{
    return mb_strtoupper(mb_substr($user['first_name'] ?? '', 0, 1) . mb_substr($user['last_name'] ?? '', 0, 1));
}

/** Склонение существительного: plural(5, 'задача','задачи','задач'). */
function plural(int $n, string $one, string $few, string $many): string
{
    $mod10 = $n % 10;
    $mod100 = $n % 100;
    if ($mod10 === 1 && $mod100 !== 11) return $one;
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) return $few;
    return $many;
}
