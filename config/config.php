<?php
declare(strict_types=1);

$env = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
};

return [
    'app' => [
        'name'     => 'Кайфорд',
        'env'      => $env('APP_ENV', 'production'),
        'debug'    => $env('APP_DEBUG', '0') === '1',
        'base_path' => rtrim($env('APP_BASE_PATH', ''), '/'),
        'url'      => $env('APP_URL', 'https://kayford.ru'),
    ],

    'db' => [
        'host'    => $env('DB_HOST', '127.0.0.1'),
        'port'    => (int) $env('DB_PORT', '3306'),
        'name'    => $env('DB_NAME', 'kayford'),
        'user'    => $env('DB_USER', 'root'),
        'pass'    => $env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'driver'    => $env('MAIL_DRIVER', 'smtp'),
        'host'      => $env('MAIL_HOST', 'smtp.yandex.ru'),
        'port'      => (int) $env('MAIL_PORT', '465'),
        'encryption'=> $env('MAIL_ENCRYPTION', 'ssl'),
        'username'  => $env('MAIL_USERNAME', ''),
        'password'  => $env('MAIL_PASSWORD', ''),
        'from'      => [
            'address' => $env('MAIL_FROM_ADDRESS', 'noreply@kayford.ru'),
            'name'    => $env('MAIL_FROM_NAME', 'Кайфорд'),
        ],
    ],

    'oauth' => [
        'vk' => [
            'client_id'     => $env('VK_CLIENT_ID', ''),
            'client_secret' => $env('VK_CLIENT_SECRET', ''),
            'redirect_uri'  => $env('VK_REDIRECT_URI', ($env('APP_URL', 'https://kayford.ru') ?: 'https://kayford.ru') . '/auth/callback/vk'),
            'scope'         => 'email vkid.personal_info',
        ],
        'yandex' => [
            'client_id'     => $env('YANDEX_CLIENT_ID', ''),
            'client_secret' => $env('YANDEX_CLIENT_SECRET', ''),
            'redirect_uri'  => $env('YANDEX_REDIRECT_URI', ($env('APP_URL', 'https://kayford.ru') ?: 'https://kayford.ru') . '/auth/callback/yandex'),
            'scope'         => 'login:email login:info login:avatar',
        ],
    ],
];
