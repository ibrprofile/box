<?php
/** @var array $user */
$unread = $unread ?? 0;
$items = [
    ['/app',        'home',    'Главная'],
    ['/catalog',    'catalog', 'Каталог задач'],
    ['/trainer',    'trainer', 'Тренажёры'],
    ['/leaderboard','trophy',  'Рейтинг'],
    ['/chat',       'chat',    'Чат'],
    ['/profile',    'user',    'Профиль'],
];
?>
<aside class="sidebar">
    <a href="<?= url('/app') ?>" class="brand">
        <span class="brand__mark">К</span>
        <span class="brand__name">Кайфорд</span>
    </a>

    <nav class="stack" style="gap:2px">
        <?php foreach ($items as [$href, $ic, $label]): ?>
            <a href="<?= url($href) ?>" class="nav-link <?= nav_active($href) ? 'is-active' : '' ?>">
                <?= icon($ic) ?>
                <span><?= $label ?></span>
                <?php if ($href === '/chat' && $unread > 0): ?>
                    <span class="nav-link__badge"><?= (int) $unread ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <?php if (\App\Core\Auth::isStaff()): ?>
            <div class="nav-group-label">Управление</div>
            <a href="<?= url('/admin') ?>" class="nav-link <?= nav_active('/admin') ? 'is-active' : '' ?>">
                <?= icon('shield') ?><span>Админка</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar__foot">
        <a href="<?= url('/profile') ?>" class="nav-link" style="background:var(--surface-2)">
            <span class="avatar avatar--sm" style="background:hsl(<?= (int) $user['avatar_hue'] ?> 70% 55%)"><?= e(initials($user)) ?></span>
            <span style="min-width:0">
                <span style="display:block;font-weight:700;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($user['first_name']) ?></span>
                <span style="display:block;font-size:12px" class="muted">Уровень <?= (int) $user['level'] ?></span>
            </span>
        </a>
    </div>
</aside>

<nav class="mobile-nav">
    <?php
    $mobile = [
        ['/app',        'home',    'Главная'],
        ['/catalog',    'catalog', 'Задачи'],
        ['/trainer',    'trainer', 'Тренажёр'],
        ['/leaderboard','trophy',  'Рейтинг'],
        ['/profile',    'user',    'Профиль'],
    ];
    foreach ($mobile as [$href, $ic, $label]): ?>
        <a href="<?= url($href) ?>" class="<?= nav_active($href) ? 'is-active' : '' ?>">
            <?= icon($ic) ?>
            <span><?= $label ?></span>
        </a>
    <?php endforeach; ?>
</nav>
