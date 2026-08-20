<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\StressWord;

final class AdminWordController extends Controller
{
    public function __construct()
    {
        Auth::requireStaff();
    }

    public function index(Request $request, array $params = []): void
    {
        $this->view('admin/words/index', [
            'title' => 'Слова для тренажёра',
            'words' => StressWord::all(),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        StressWord::create([
            'word' => $request->string('word'),
            'stress_index' => max(0, $request->int('stress_index', 0)),
            'difficulty' => max(1, min(5, $request->int('difficulty', 2))),
            'hint' => $request->string('hint'),
            'is_active' => $request->int('is_active', 1),
        ]);
        flash('success', 'Слово добавлено');
        Response::redirect('/admin/words');
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        StressWord::delete($id);
        flash('success', 'Слово удалено');
        Response::redirect('/admin/words');
    }
}
