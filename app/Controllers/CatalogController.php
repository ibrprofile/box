<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Attempt;
use App\Services\Gamification;

final class CatalogController extends Controller
{
    public function __construct()
    {
        \App\Core\Auth::requireAuth();
    }

    /** Экран выбора предмета. */
    public function index(Request $req, array $params = []): void
    {
        $this->view('catalog/index', [
            'title'    => 'Каталог задач',
            'subjects' => Subject::allWithCounts(),
        ]);
    }

    /** Сессия решения по предмету. */
    public function subject(Request $req, array $params = []): void
    {
        $subject = Subject::findBySlug((string) ($params['slug'] ?? ''));
        if (!$subject || !$subject['is_active']) {
            $this->abort(404);
        }

        $this->view('catalog/session', [
            'title'   => (string) $subject['name'],
            'subject' => $subject,
            'topics'  => Subject::topics((int) $subject['id']),
        ]);
    }

    /** AJAX: следующая задача с учётом истории ошибок. */
    public function next(Request $req, array $params = []): void
    {
        $subjectId = (int) $req->input('subject_id');
        $topicId   = (int) $req->input('topic_id') ?: null;
        $exclude   = array_slice(array_filter(array_map('intval', (array) $req->input('exclude', []))), -50);

        $task = Task::nextFor($this->user()['id'], $subjectId, $topicId, $exclude);

        $this->json($task ? ['task' => Task::toClient($task)] : ['done' => true]);
    }

    /** AJAX: проверка ответа, запись попытки, начисление опыта. */
    public function answer(Request $req, array $params = []): void
    {
        $taskId = (int) $req->input('task_id');
        $given  = (string) $req->input('answer', '');
        $timeMs = (int) $req->input('time_ms', 0);
        $task   = Task::find($taskId);

        if (!$task || !$task['is_active']) {
            $this->json(['error' => 'Задача недоступна'], 404);
        }

        $result = Task::check($task, $given);
        Attempt::record($this->user()['id'], $taskId, $given, $result['correct'], $timeMs);

        $reward = null;
        if ($result['correct']) {
            $reward = Gamification::award($this->user()['id'], (int) $task['xp_reward']);
        }

        $this->json([
            'correct'        => $result['correct'],
            'solution'       => $result['solution'],
            'correct_answer' => $result['correct_answer'],
            'reward'         => $reward,
        ]);
    }
}
