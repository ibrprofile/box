<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ошибка</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="page page--center">
    <div class="card card--pad stack" style="max-width: 560px;">
        <p class="eyebrow">Ошибка</p>
        <h1><?= (int) ($status ?? 500) ?></h1>
        <p><?= e($message ?? 'Что-то пошло не так') ?></p>
        <a class="btn" href="<?= url('/') ?>">На главную</a>
    </div>
</body>
</html>
