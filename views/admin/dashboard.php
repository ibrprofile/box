<?php /** @var array $stats */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Админка</h1>
        <p class="page-sub">Управляй контентом, пользователями и поддержкой в одном месте.</p>
    </div>
</section>

<div class="grid grid--auto">
    <div class="card card--pad">
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['users'] ?></div>
            <div class="stat__label">Пользователей</div>
        </div>
    </div>
    <div class="card card--pad">
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['tasks'] ?></div>
            <div class="stat__label">Задач</div>
        </div>
    </div>
    <div class="card card--pad">
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['words'] ?></div>
            <div class="stat__label">Слов в тренажёре</div>
        </div>
    </div>
    <div class="card card--pad">
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['threads'] ?></div>
            <div class="stat__label">Чат-диалогов</div>
        </div>
    </div>
</div>

<div class="grid" style="margin-top:18px">
    <a class="card card--pad" href="<?= url('/admin/tasks') ?>">📚 Управление задачами</a>
    <a class="card card--pad" href="<?= url('/admin/words') ?>">📝 Слова тренажёра</a>
    <a class="card card--pad" href="<?= url('/admin/users') ?>">👥 Пользователи</a>
    <a class="card card--pad" href="<?= url('/admin/chats') ?>">💬 Чаты</a>
</div>
