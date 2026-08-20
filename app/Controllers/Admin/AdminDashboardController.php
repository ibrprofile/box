<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class AdminDashboardController extends Controller
{
    public function __construct()
    {
        Auth::requireStaff();
    }

    public function index(Request $request, array $params = []): void
    {
        $stats = [
            'users' => (int) Database::value('SELECT COUNT(*) FROM users'),
            'tasks' => (int) Database::value('SELECT COUNT(*) FROM tasks'),
            'words' => (int) Database::value('SELECT COUNT(*) FROM stress_words'),
            'threads' => (int) Database::value('SELECT COUNT(*) FROM chat_threads'),
        ];

        $this->view('admin/dashboard', [
            'title' => 'Админка',
            'stats' => $stats,
        ]);
    }
}
