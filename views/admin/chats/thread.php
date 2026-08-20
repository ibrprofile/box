<?php /** @var array $thread @var array $messages */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Диалог</h1>
        <p class="page-sub">Поддерживайте общение с пользователем прямо из админки.</p>
    </div>
</section>

<div class="card card--pad stack">
    <?php foreach ($messages as $message): ?>
        <div class="card card--pad" style="background:<?= $message['sender_type'] === 'staff' ? 'var(--primary-sf)' : 'var(--surface-2)' ?>">
            <div class="muted" style="margin-bottom:6px"><?= $message['sender_type'] === 'staff' ? 'Менеджер' : 'Пользователь' ?></div>
            <div><?= e($message['body']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<form class="card card--pad" method="post" action="<?= url('/admin/chats/' . $thread['id'] . '/send') ?>">
    <?= csrf_field() ?>
    <div class="field">
        <label>Сообщение</label>
        <textarea class="textarea" name="message" rows="4" required></textarea>
    </div>
    <button class="btn btn--primary" type="submit">Отправить</button>
</form>
