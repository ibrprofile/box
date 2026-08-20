<?php
/** @var string $content */
$user  = auth();
$title = $title ?? 'Кайфорд';
$pageTitle = $pageTitle ?? '';
$unread = $unread ?? 0;
$css = $css ?? [];
?>
<!DOCTYPE html>
<html lang="ru" class="bg-background">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#6c4cf1">
    <title><?= e($title) ?> — Кайфорд</title>
    <meta name="description" content="Кайфорд — платформа для подготовки к ЕГЭ: каталог задач, тренажёры, рейтинг и достижения.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?php foreach ($css as $file): ?>
        <link rel="stylesheet" href="<?= asset('css/' . $file . '.css') ?>">
    <?php endforeach; ?>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <script>window.KAYFORD = { base: <?= json_encode(config('app.base_path', '')) ?> };</script>
</head>
<body>
<div class="shell">
    <?= partial('nav', ['user' => $user, 'unread' => $unread]) ?>
    <div class="main">
        <header class="topbar">
            <?php if ($pageTitle !== ''): ?>
                <h1 class="topbar__title"><?= e($pageTitle) ?></h1>
            <?php endif; ?>
            <div class="topbar__spacer"></div>
            <a href="<?= url('/profile') ?>" class="flame hide-xs" title="Серия" aria-label="Серия дней">
                <?= icon('flame') ?><?= (int) $user['streak_count'] ?>
            </a>
            <span class="badge badge--primary"><?= icon('bolt') ?><?= (int) $user['xp'] ?> XP</span>
        </header>
        <main class="content">
            <?php if ($msg = flash('success')): ?><div class="flash flash--success"><?= e($msg) ?></div><?php endif; ?>
            <?php if ($msg = flash('error')): ?><div class="flash flash--error"><?= e($msg) ?></div><?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
<?php foreach (($js ?? []) as $file): ?>
    <script src="<?= asset('js/' . $file . '.js') ?>"></script>
<?php endforeach; ?>
</body>
</html>
