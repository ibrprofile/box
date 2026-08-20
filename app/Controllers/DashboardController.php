<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Attempt;
use App\Models\Leaderboard;
use App\Models\StressWord;
use App\Models\Subject;
use App\Services\Gamification;

final class DashboardController extends Controller
{
    public function __construct()
    {
        Auth::requireAuth();
    }

    public function index(Request $req, array $params = []): void
    {
        $user = $this->user();
        $uid  = (int) $user['id'];

        $this->view('dashboard/index', [
            'title'    => 'Кабинет',
            'user'     => $user,
            'progress' => Gamification::levelProgress((int) $user['xp']),
            'catalog'  => Attempt::summary($uid),
            'trainer'  => StressWord::summary($uid),
            'rank'     => Leaderboard::rankOf($uid, (int) $user['xp']),
            'subjects' => Subject::allWithCounts(),
        ]);
    }
}
