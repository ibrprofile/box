<?php
declare(strict_types=1);

namespace App\Services\OAuth;

final class YandexOAuth
{
    private const AUTH_URL = 'https://oauth.yandex.ru/authorize';
    private const TOKEN_URL = 'https://oauth.yandex.ru/token';
    private const USER_INFO_URL = 'https://login.yandex.ru/info';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        private readonly string $scope = 'login:email login:info login:avatar'
    ) {}

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    /** @return array{url:string, state:string} */
    public function buildAuthUrl(): array
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_yandex_state'] = $state;

        $params = [
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'state'         => $state,
            'display'       => 'page',
            'force_confirm' => 'no',
        ];
        if ($this->scope !== '') {
            $params['scope'] = $this->scope;
        }

        return [
            'url'   => self::AUTH_URL . '?' . http_build_query($params),
            'state' => $state,
        ];
    }

    public function exchangeCode(string $code, string $state): ?array
    {
        $stored = $_SESSION['oauth_yandex_state'] ?? '';
        unset($_SESSION['oauth_yandex_state']);

        if ($stored === '' || !hash_equals($stored, $state)) {
            return null;
        }

        $body = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
        ];

        $resp = $this->post(self::TOKEN_URL, $body);
        return is_array($resp) && isset($resp['access_token']) ? $resp : null;
    }

    /** @return array{uid:string,first_name:string,last_name:string,email:string,avatar:string|null}|null */
    public function getUserInfo(string $accessToken): ?array
    {
        $url = self::USER_INFO_URL . '?' . http_build_query([
            'format' => 'json',
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: OAuth ' . $accessToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        curl_close($ch);
        if ($errno || $result === false) {
            return null;
        }
        $resp = json_decode($result, true);
        if (!is_array($resp)) {
            return null;
        }
        return [
            'uid'        => (string) ($resp['id'] ?? ''),
            'first_name' => (string) ($resp['first_name'] ?? ''),
            'last_name'  => (string) ($resp['last_name'] ?? ''),
            'email'      => (string) ($resp['default_email'] ?? ($resp['emails'][0] ?? '')),
            'avatar'     => $this->avatarUrl($resp),
        ];
    }

    private function avatarUrl(array $resp): ?string
    {
        if (!empty($resp['default_avatar_id'])) {
            return null;
        }
        return 'https://avatars.yandex.net/get-yapic/' . $resp['default_avatar_id'] . '/islands-200';
    }

    private function post(string $url, array $body, array $extraHeaders = []): mixed
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($body),
            CURLOPT_HTTPHEADER     => array_merge([
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ], $extraHeaders),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        curl_close($ch);
        if ($errno || $result === false) {
            return null;
        }
        return json_decode($result, true);
    }
}
