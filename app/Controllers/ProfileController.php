<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Achievement;
use App\Models\Attempt;
use App\Models\Leaderboard;
use App\Models\SocialAccount;
use App\Models\StressWord;
use App\Services\Gamification;

final class ProfileController extends Controller
{
    public function __construct()
    {
        Auth::requireAuth();
    }

    public function index(Request $req, array $params = []): void
    {
        $user = $this->user();
        $uid  = (int) $user['id'];
        $xp   = (int) $user['xp'];

        $socials = Database::all(
            'SELECT provider, email, last_used_at FROM social_accounts WHERE user_id = ? ORDER BY id',
            [$uid]
        );

        $this->view('profile/index', [
            'title'        => 'Профиль',
            'user'         => $user,
            'progress'     => Gamification::levelProgress($xp),
            'catalog'      => Attempt::summary($uid),
            'bySubject'    => Attempt::bySubject($uid),
            'trainer'      => StressWord::summary($uid),
            'achievements' => Achievement::forUser($uid),
            'unlocked'     => Achievement::unlockedCount($uid),
            'rank'         => Leaderboard::rankOf($uid, $xp),
            'socials'      => $socials,
        ]);
    }

    public function deleteSocial(Request $request): void
    {
        $this->verifyCsrf($request);
        $user = $this->user();
        $uid  = (int) $user['id'];
        $provider = $request->string('provider');
        if (!in_array($provider, ['vk', 'yandex'], true)) {
            Response::error('Некорректный провайдер', 422);
        }
        Database::run('DELETE FROM social_accounts WHERE user_id = ? AND provider = ?', [$uid, $provider]);
        Response::ok();
    }
}
