<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Leaderboard;

final class LeaderboardController extends Controller
{
    public function __construct()
    {
        Auth::requireAuth();
    }

    public function index(Request $req, array $params = []): void
    {
        $user = $this->user();
        $top  = Leaderboard::top(50);

        $this->view('leaderboard/index', [
            'title'   => 'Топ-50',
            'top'     => $top,
            'meId'    => (int) $user['id'],
            'myRank'  => Leaderboard::rankOf((int) $user['id'], (int) $user['xp']),
        ]);
    }
}
