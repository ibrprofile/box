<?php /** @var array $threads */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Чаты</h1>
        <p class="page-sub">Отвечайте клиентам и следите за запросами поддержки.</p>
    </div>
</section>

<div class="stack">
    <?php foreach ($threads as $thread): ?>
        <a class="card card--pad" href="<?= url('/admin/chats/' . $thread['id']) ?>">
            <div class="row row--wrap" style="justify-content:space-between;gap:12px">
                <div>
                    <h3><?= e($thread['first_name'] . ' ' . $thread['last_name']) ?></h3>
                    <div class="muted"><?= e($thread['email']) ?> · <?= e($thread['status']) ?></div>
                </div>
                <span class="badge badge--accent">Открыть</span>
            </div>
        </a>
    <?php endforeach; ?>
</div>
