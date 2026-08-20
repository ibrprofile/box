<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

final class AdminUserController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin', 'superadmin');
    }

    public function index(Request $request, array $params = []): void
    {
        $rows = Database::all(
            'SELECT id, first_name, last_name, email, role, status, xp, level, streak_best, created_at
               FROM users
              ORDER BY xp DESC, id ASC'
        );

        $this->view('admin/users/index', [
            'title' => 'Пользователи',
            'rows' => $rows,
        ]);
    }

    public function show(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $user = User::find($id);
        if ($user === null) {
            $this->abort(404);
        }

        $this->view('admin/users/show', [
            'title' => 'Профиль пользователя',
            'user' => $user,
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        $user = User::find($id);
        if ($user === null) {
            $this->abort(404);
        }

        User::update($id, [
            'role' => $request->string('role', 'student'),
            'status' => $request->string('status', 'active'),
            'grade' => $request->int('grade', 0),
            'first_name' => $request->string('first_name'),
            'last_name' => $request->string('last_name'),
        ]);

        flash('success', 'Пользователь обновлён');
        Response::redirect('/admin/users');
    }
}
