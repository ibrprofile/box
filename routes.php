<?php
declare(strict_types=1);

use App\Controllers\Admin\AdminChatController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminTaskController;
use App\Controllers\Admin\AdminUploadController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\Admin\AdminWordController;
use App\Controllers\AuthController;
use App\Controllers\CatalogController;
use App\Controllers\ChatController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\LeaderboardController;
use App\Controllers\ProfileController;
use App\Controllers\TrainerController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);

/* ---------------- Аутентификация ---------------- */

$router->get('/auth', [AuthController::class, 'show']);
$router->post('/auth/logout', [AuthController::class, 'logout']);

// Авторизация через почту с кодом подтверждения (регистрация + вход)
$router->post('/auth/email/request',  [AuthController::class, 'emailRequest']);
$router->post('/auth/email/verify',   [AuthController::class, 'emailVerify']);

// OAuth: VK ID
$router->get('/auth/vk/start',         [AuthController::class, 'vkStart']);
$router->get('/auth/callback/vk',      [AuthController::class, 'vkCallback']);

// OAuth: Яндекс ID
$router->get('/auth/yandex/start',     [AuthController::class, 'yandexStart']);
$router->get('/auth/callback/yandex',  [AuthController::class, 'yandexCallback']);

/* ---------------- Кабинет ученика ---------------- */

$router->get('/app', [DashboardController::class, 'index']);

/* ---------------- Каталог задач ---------------- */

$router->get('/catalog', [CatalogController::class, 'index']);
$router->get('/catalog/{slug}', [CatalogController::class, 'subject']);
$router->post('/catalog/next',   [CatalogController::class, 'next']);
$router->post('/catalog/answer', [CatalogController::class, 'answer']);

/* ---------------- Тренажёры ---------------- */

$router->get('/trainer', [TrainerController::class, 'index']);
$router->get('/trainer/stress', [TrainerController::class, 'stress']);
$router->post('/trainer/stress/batch', [TrainerController::class, 'stressBatch']);
$router->post('/trainer/stress/check', [TrainerController::class, 'stressCheck']);

/* ---------------- Профиль и рейтинг ---------------- */

$router->get('/profile', [ProfileController::class, 'index']);
$router->post('/profile/delete-social', [ProfileController::class, 'deleteSocial']);
$router->get('/leaderboard', [LeaderboardController::class, 'index']);

/* ---------------- Чат с менеджером ---------------- */

$router->get('/chat', [ChatController::class, 'index']);
$router->post('/chat/send', [ChatController::class, 'send']);
$router->get('/chat/poll', [ChatController::class, 'poll']);

/* ---------------- Админка ---------------- */

$router->get('/admin', [AdminDashboardController::class, 'index']);

$router->get('/admin/tasks', [AdminTaskController::class, 'index']);
$router->get('/admin/tasks/new', [AdminTaskController::class, 'create']);
$router->post('/admin/tasks', [AdminTaskController::class, 'store']);
$router->get('/admin/tasks/{id}/edit', [AdminTaskController::class, 'edit']);
$router->post('/admin/tasks/{id}', [AdminTaskController::class, 'update']);
$router->post('/admin/tasks/{id}/delete', [AdminTaskController::class, 'destroy']);
$router->post('/admin/upload', [AdminUploadController::class, 'store']);

$router->get('/admin/words', [AdminWordController::class, 'index']);
$router->post('/admin/words', [AdminWordController::class, 'store']);
$router->post('/admin/words/{id}/delete', [AdminWordController::class, 'destroy']);

$router->get('/admin/users', [AdminUserController::class, 'index']);
$router->get('/admin/users/{id}', [AdminUserController::class, 'show']);
$router->post('/admin/users/{id}', [AdminUserController::class, 'update']);

$router->get('/admin/chats', [AdminChatController::class, 'index']);
$router->get('/admin/chats/{id}', [AdminChatController::class, 'thread']);
$router->post('/admin/chats/{id}/send', [AdminChatController::class, 'send']);
$router->get('/admin/chats/poll', [AdminChatController::class, 'poll']);
