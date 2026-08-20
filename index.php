<?php
declare(strict_types=1);

/**
 * Кайфорд — единая точка входа (front controller).
 * Все запросы направляются сюда через .htaccess.
 */

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;

/* --- Автозагрузчик классов (Composer vendor + собственный PSR-4 для App\ ----- */
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

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

require __DIR__ . '/app/Support/helpers.php';

/* --- Конфигурация и окружение ----------------------------------------- */
$GLOBALS['__config'] = require __DIR__ . '/config/config.php';

if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

/* --- Сессия ----------------------------------------------------------- */
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
]);
session_name('kayford_session');
session_start();

Database::connect(config('db'));

$router  = new Router();
$request = new Request();

require __DIR__ . '/routes.php';

$router->dispatch($request);
