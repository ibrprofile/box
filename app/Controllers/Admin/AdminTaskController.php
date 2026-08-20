<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Subject;
use App\Models\Task;

final class AdminTaskController extends Controller
{
    public function __construct()
    {
        Auth::requireStaff();
    }

    public function index(Request $request, array $params = []): void
    {
        $page = max(1, $request->int('page', 1));
        $filters = [
            'q' => $request->string('q'),
            'subject_id' => $request->int('subject_id'),
        ];
        $payload = Task::paginate($page, 20, $filters);

        $this->view('admin/tasks/index', [
            'title' => 'Задачи',
            'rows' => $payload['rows'],
            'pagination' => $payload,
            'subjects' => Subject::all(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $this->view('admin/tasks/form', [
            'title' => 'Новая задача',
            'task' => null,
            'subjects' => Subject::all(),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        Task::create($this->payload($request));
        flash('success', 'Задача добавлена');
        Response::redirect('/admin/tasks');
    }

    public function edit(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $task = Task::find($id);
        if ($task === null) {
            $this->abort(404);
        }

        $this->view('admin/tasks/form', [
            'title' => 'Редактирование задачи',
            'task' => $task,
            'subjects' => Subject::all(),
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        if (Task::find($id) === null) {
            $this->abort(404);
        }
        Task::update($id, $this->payload($request));
        flash('success', 'Задача обновлена');
        Response::redirect('/admin/tasks');
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        Task::delete($id);
        flash('success', 'Задача удалена');
        Response::redirect('/admin/tasks');
    }

    private function payload(Request $request): array
    {
        return [
            'subject_id' => $request->int('subject_id'),
            'topic_id' => $request->int('topic_id') ?: null,
            'title' => $request->string('title'),
            'statement' => $request->string('statement'),
            'answer_type' => $request->string('answer_type', 'text'),
            'correct_answer' => $request->string('correct_answer'),
            'options' => $request->string('options'),
            'solution' => $request->string('solution'),
            'difficulty' => max(1, min(5, $request->int('difficulty', 2))),
            'xp_reward' => max(5, min(100, $request->int('xp_reward', 10))),
            'is_active' => $request->int('is_active', 1),
            'created_by' => Auth::id(),
        ];
    }
}
