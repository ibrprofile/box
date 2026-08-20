<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Chat;

final class ChatController extends Controller
{
    public function __construct()
    {
        Auth::requireAuth();
    }

    public function index(Request $request, array $params = []): void
    {
        $user = $this->user();
        $thread = Chat::ensureThread((int) $user['id']);

        $this->view('chat/index', [
            'title' => 'Чат с менеджером',
            'thread' => $thread,
            'messages' => Chat::messages((int) $thread['id']),
        ]);
    }

    public function send(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        $body = trim($request->string('message'));
        if ($body === '') {
            Response::error('Введите сообщение', 422);
        }

        $user = $this->user();
        $thread = Chat::ensureThread((int) $user['id']);
        Chat::send((int) $thread['id'], 'user', (int) $user['id'], $body);

        Response::ok(['ok' => true]);
    }

    public function poll(Request $request, array $params = []): void
    {
        $thread = Chat::ensureThread((int) $this->user()['id']);
        $since = max(0, (int) $request->int('since', 0));
        Response::ok(['messages' => Chat::poll((int) $thread['id'], $since)]);
    }
}
