<?php
declare(strict_types=1);

namespace App\Services\OAuth;

final class VKOAuth
{
    private const AUTH_URL = 'https://id.vk.ru/authorize';
    private const TOKEN_URL = 'https://id.vk.ru/oauth2/auth';
    private const USER_INFO_URL = 'https://id.vk.ru/oauth2/user_info';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        private readonly string $scope = 'email vkid.personal_info'
    ) {}

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    /** @return array{url:string, code_verifier:string, state:string} */
    public function buildAuthUrl(): array
    {
        [$codeVerifier, $codeChallenge] = $this->pkcePair();
        $state = bin2hex(random_bytes(16));

        $_SESSION['oauth_vk_verifier'] = $codeVerifier;
        $_SESSION['oauth_vk_state']    = $state;

        $params = [
            'response_type'        => 'code',
            'client_id'            => $this->clientId,
            'redirect_uri'         => $this->redirectUri,
            'scope'                => $this->scope,
            'state'                => $state,
            'code_challenge'       => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt'               => 'login',
        ];

        return [
            'url'           => self::AUTH_URL . '?' . http_build_query($params),
            'code_verifier' => $codeVerifier,
            'state'         => $state,
        ];
    }

    public function exchangeCode(string $code, string $state): ?array
    {
        $storedState = $_SESSION['oauth_vk_state'] ?? '';
        $verifier    = $_SESSION['oauth_vk_verifier'] ?? '';
        unset($_SESSION['oauth_vk_state'], $_SESSION['oauth_vk_verifier']);

        if ($storedState === '' || !hash_equals($storedState, $state)) {
            return null;
        }

        $body = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'code_verifier' => $verifier,
        ];

        $resp = $this->post(self::TOKEN_URL, $body);
        return is_array($resp) && isset($resp['access_token']) ? $resp : null;
    }

    /** @return array{uid:string,first_name:string,last_name:string,email:string,avatar:string|null}|null */
    public function getUserInfo(string $accessToken): ?array
    {
        $body = [
            'client_id' => $this->clientId,
        ];
        $resp = $this->post(self::USER_INFO_URL, $body, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        if (!is_array($resp) || !isset($resp['user'])) {
            return null;
        }
        $u = $resp['user'];
        return [
            'uid'        => (string) ($u['id'] ?? ''),
            'first_name' => (string) ($u['first_name'] ?? ''),
            'last_name'  => (string) ($u['last_name'] ?? ''),
            'email'      => (string) ($resp['email'] ?? ''),
            'avatar'     => isset($u['avatar']) ? (string) $u['avatar'] : null,
        ];
    }

    /** @return array{0:string,1:string} [code_verifier, code_challenge (S256, base64url)] */
    private function pkcePair(): array
    {
        $verifier = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        return [$verifier, $challenge];
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
