<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class HomeController extends Controller
{
    public function index(Request $req, array $params = []): void
    {
        if (Auth::check()) {
            Response::redirect('/app');
        }
        $this->view('home/index', ['title' => 'Кайфорд — подготовка к ЕГЭ'], 'auth');
    }
}
