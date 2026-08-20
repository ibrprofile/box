<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Chat;

final class AdminChatController extends Controller
{
    public function __construct()
    {
        Auth::requireStaff();
    }

    public function index(Request $request, array $params = []): void
    {
        $this->view('admin/chats/index', [
            'title' => 'Чаты',
            'threads' => Chat::allThreads(),
        ]);
    }

    public function thread(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $thread = Database::first('SELECT * FROM chat_threads WHERE id = ?', [$id]);
        if ($thread === null) {
            $this->abort(404);
        }
        $this->view('admin/chats/thread', [
            'title' => 'Диалог',
            'thread' => $thread,
            'messages' => Chat::messages($id),
        ]);
    }

    public function send(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        $body = trim($request->string('message'));
        if ($body === '') {
            Response::error('Введите сообщение', 422);
        }

        Chat::send($id, 'staff', (int) Auth::id(), $body);
        Response::ok();
    }

    public function poll(Request $request, array $params = []): void
    {
        $threadId = (int) $request->int('thread_id', 0);
        $since = max(0, (int) $request->int('since', 0));
        Response::ok(['messages' => Chat::poll($threadId, $since)]);
    }
}
