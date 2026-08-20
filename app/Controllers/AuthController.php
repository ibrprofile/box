<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\EmailVerification;
use App\Services\Mailer;
use App\Services\OAuth\VKOAuth;
use App\Services\OAuth\YandexOAuth;

final class AuthController extends Controller
{
    public function show(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('/app');
        }
        $this->view('auth/index', [
            'title'         => 'Вход',
            'vkEnabled'     => $this->vk()->isConfigured(),
            'yandexEnabled' => $this->yandex()->isConfigured(),
        ], 'auth');
    }

    public function logout(Request $request): void
    {
        $this->verifyCsrf($request);
        Auth::logout();
        Response::redirect('/');
    }

    /* ====================== Авторизация по почте с кодом ====================== */

    public function emailRequest(Request $request): void
    {
        $this->verifyCsrf($request);

        $purpose = $request->string('purpose');
        if (!in_array($purpose, ['register', 'login'], true)) {
            Response::error('Некорректный тип запроса', 422);
        }

        $email = mb_strtolower(trim($request->string('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Некорректная почта', 422);
        }

        $existing = User::findByEmail($email);

        if ($purpose === 'register') {
            if ($existing !== null) {
                Response::error('Эта почта уже зарегистрирована. Войдите.', 422);
            }
            $profile = [
                'first_name' => trim($request->string('first_name')),
                'last_name'  => trim($request->string('last_name')),
                'grade'      => $request->int('grade'),
            ];
            if (mb_strlen($profile['first_name']) < 2 || mb_strlen($profile['last_name']) < 2) {
                Response::error('Укажите имя и фамилию', 422);
            }
            if ($profile['grade'] < 5 || $profile['grade'] > 11) {
                Response::error('Выберите класс (5–11)', 422);
            }
            $_SESSION['pending_profile'] = $profile;
        } else {
            if ($existing === null) {
                Response::error('Пользователь с такой почтой не найден', 404);
            }
            if ($existing['status'] === 'blocked') {
                Response::error('Аккаунт заблокирован', 403);
            }
            unset($_SESSION['pending_profile']);
        }

        if (!EmailVerification::canResend($email, $purpose)) {
            Response::error('Подождите минуту перед повторной отправкой', 429);
        }

        $_SESSION['pending_email']   = $email;
        $_SESSION['pending_purpose'] = $purpose;

        $code = EmailVerification::issue($email, $purpose);
        $name = $purpose === 'register' && isset($profile['first_name']) ? $profile['first_name'] : '';

        $sent = Mailer::send(
            $email,
            $name,
            ($purpose === 'login' ? 'Вход в Кайфорд' : 'Завершите регистрацию в Кайфорде'),
            Mailer::emailCodeHtml($code, $purpose, $name)
        );

        if (!$sent) {
            EmailVerification::invalidate($email, $purpose);
            Response::error('Не удалось отправить письмо. Попробуйте позже.', 500);
        }

        Response::ok([
            'purpose'   => $purpose,
            'email_mask' => $this->maskEmail($email),
        ]);
    }

    public function emailVerify(Request $request): void
    {
        $this->verifyCsrf($request);

        $email   = $_SESSION['pending_email']   ?? null;
        $purpose = $_SESSION['pending_purpose'] ?? null;
        if (!is_string($email) || !in_array($purpose, ['register', 'login'], true)) {
            Response::error('Сессия истекла, начните заново', 422);
        }

        $code = trim($request->string('code'));
        if (!preg_match('/^\d{6}$/', $code)) {
            Response::error('Код состоит из 6 цифр', 422);
        }

        if (!EmailVerification::verify($email, $purpose, $code)) {
            Response::error('Неверный код', 422);
        }

        if ($purpose === 'register') {
            $profile = $_SESSION['pending_profile'] ?? null;
            if (!is_array($profile)) {
                Response::error('Сессия регистрации истекла', 422);
            }
            if (User::emailExists($email)) {
                Response::error('Эта почта уже зарегистрирована', 422);
            }
            $userId = User::create([
                'first_name'    => $profile['first_name'],
                'last_name'     => $profile['last_name'],
                'email'         => $email,
                'grade'         => $profile['grade'],
                'password_hash' => null,
                'avatar_hue'    => random_int(0, 359),
            ]);
            unset($_SESSION['pending_profile']);
        } else {
            $user = User::findByEmail($email);
            if ($user === null) {
                Response::error('Пользователь не найден', 404);
            }
            if ($user['status'] === 'blocked') {
                Response::error('Аккаунт заблокирован', 403);
            }
            $userId = (int) $user['id'];
        }

        unset($_SESSION['pending_email'], $_SESSION['pending_purpose']);
        Auth::login($userId);
        Response::ok(['redirect' => url('/app')]);
    }

    /* ====================== VK ID ====================== */

    public function vkStart(Request $request): void
    {
        $vk = $this->vk();
        if (!$vk->isConfigured()) {
            Response::abort(503, 'Вход через VK не настроен');
        }
        $result = $vk->buildAuthUrl();
        Response::redirect($result['url']);
    }

    public function vkCallback(Request $request): void
    {
        $vk = $this->vk();
        if (!$vk->isConfigured()) {
            Response::abort(503, 'Вход через VK не настроен');
        }

        $error = $request->string('error');
        if ($error !== '') {
            $this->authError('Отмена входа через VK.');
        }

        $code  = $request->string('code');
        $state = $request->string('state');

        $tokenResp = $vk->exchangeCode($code, $state);
        if ($tokenResp === null) {
            $this->authError('Не удалось завершить вход через VK.');
        }

        $info = $vk->getUserInfo($tokenResp['access_token']);
        if ($info === null || $info['uid'] === '') {
            $this->authError('Не удалось получить данные профиля VK.');
        }

        $this->finishSocial(
            'vk',
            $info['uid'],
            $info['first_name'],
            $info['last_name'],
            $info['email'],
            $tokenResp['access_token'] ?? null,
            $tokenResp['refresh_token'] ?? null
        );
    }

    /* ====================== Яндекс ID ====================== */

    public function yandexStart(Request $request): void
    {
        $ya = $this->yandex();
        if (!$ya->isConfigured()) {
            Response::abort(503, 'Вход через Яндекс не настроен');
        }
        $result = $ya->buildAuthUrl();
        Response::redirect($result['url']);
    }

    public function yandexCallback(Request $request): void
    {
        $ya = $this->yandex();
        if (!$ya->isConfigured()) {
            Response::abort(503, 'Вход через Яндекс не настроен');
        }

        $error = $request->string('error');
        if ($error !== '') {
            $this->authError('Отмена входа через Яндекс.');
        }

        $code  = $request->string('code');
        $state = $request->string('state');

        $tokenResp = $ya->exchangeCode($code, $state);
        if ($tokenResp === null) {
            $this->authError('Не удалось завершить вход через Яндекс.');
        }

        $info = $ya->getUserInfo($tokenResp['access_token']);
        if ($info === null || $info['uid'] === '') {
            $this->authError('Не удалось получить данные профиля Яндекса.');
        }

        $this->finishSocial(
            'yandex',
            $info['uid'],
            $info['first_name'],
            $info['last_name'],
            $info['email'],
            $tokenResp['access_token'] ?? null,
            $tokenResp['refresh_token'] ?? null
        );
    }

    /* ====================== Общее: социальный вход ====================== */

    private function finishSocial(string $provider, string $providerUid, string $firstName, string $lastName, string $email, ?string $accessToken, ?string $refreshToken): void
    {
        $social = SocialAccount::findByProvider($provider, $providerUid);
        if ($social !== null) {
            $user = User::find((int) $social['user_id']);
            if ($user === null || $user['status'] === 'blocked') {
                $this->authError('Аккаунт недоступен.');
            }
            SocialAccount::link((int) $user['id'], $provider, $providerUid, $accessToken, $refreshToken, $email);
            Auth::login((int) $user['id']);
            Response::redirect('/app');
        }

        $emailClean = mb_strtolower(trim($email));

        if ($emailClean !== '' && filter_var($emailClean, FILTER_VALIDATE_EMAIL)) {
            $existingUser = User::findByEmail($emailClean);
            if ($existingUser !== null) {
                if ($existingUser['status'] === 'blocked') {
                    $this->authError('Аккаунт заблокирован.');
                }
                SocialAccount::link((int) $existingUser['id'], $provider, $providerUid, $accessToken, $refreshToken, $emailClean);
                Auth::login((int) $existingUser['id']);
                Response::redirect('/app');
            }
            $userEmail = $emailClean;
        } else {
            $userEmail = sprintf('%s+%s@%s.kayford.local', $provider, $providerUid, $provider);
        }

        $fn = trim($firstName) ?: 'Ученик';
        $ln = trim($lastName)  ?: '';
        if ($ln === '') {
            $ln = 'Кайфорд';
        }

        $userId = User::create([
            'first_name'    => $fn,
            'last_name'     => $ln,
            'email'         => $userEmail,
            'grade'         => null,
            'password_hash' => null,
            'avatar_hue'    => random_int(0, 359),
        ]);

        SocialAccount::link($userId, $provider, $providerUid, $accessToken, $refreshToken, $emailClean);
        Auth::login($userId);
        Response::redirect('/app');
    }

    private function authError(string $message): never
    {
        flash('error', $message);
        Response::redirect('/auth');
    }

    /* ====================== Вспомогательное ====================== */

    private function vk(): VKOAuth
    {
        $cfg = config('oauth.vk', []);
        return new VKOAuth(
            (string) ($cfg['client_id'] ?? ''),
            (string) ($cfg['client_secret'] ?? ''),
            (string) ($cfg['redirect_uri'] ?? ''),
            (string) ($cfg['scope'] ?? 'email vkid.personal_info')
        );
    }

    private function yandex(): YandexOAuth
    {
        $cfg = config('oauth.yandex', []);
        return new YandexOAuth(
            (string) ($cfg['client_id'] ?? ''),
            (string) ($cfg['client_secret'] ?? ''),
            (string) ($cfg['redirect_uri'] ?? ''),
            (string) ($cfg['scope'] ?? 'login:email login:info login:avatar')
        );
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return '***@***';
        }
        $showLocal = mb_substr($local, 0, min(2, mb_strlen($local)));
        $showDomain = mb_substr($domain, 0, 1);
        return $showLocal . '***@' . $showDomain . '***';
    }
}
