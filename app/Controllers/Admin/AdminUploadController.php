<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class AdminUploadController extends Controller
{
    public function __construct()
    {
        Auth::requireStaff();
    }

    public function store(Request $request, array $params = []): void
    {
        $this->verifyCsrf($request);
        if (!isset($_FILES['upload']) || !is_uploaded_file($_FILES['upload']['tmp_name'])) {
            Response::error('Файл не загружен', 422);
        }

        $tmp = $_FILES['upload']['tmp_name'];
        $name = basename((string) $_FILES['upload']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safeName = 'upload-' . time() . '-' . bin2hex(random_bytes(4)) . ($ext !== '' ? '.' . $ext : '');

        $dir = __DIR__ . '/../../../uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $target = $dir . '/' . $safeName;
        if (!move_uploaded_file($tmp, $target)) {
            Response::error('Не удалось сохранить файл', 500);
        }

        Response::ok(['url' => url('/uploads/' . $safeName)]);
    }
}
