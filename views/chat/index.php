<?php /** @var array $thread @var array $messages */ ?>
<section class="page-head page-head--compact">
    <div>
        <p class="eyebrow">Комната поддержки</p>
        <h1 class="page-title">Чаты</h1>
        <p class="page-sub">Задай вопрос менеджеру и возвращайся к обучению без лишних переходов.</p>
    </div>
    <button class="btn btn--ghost" type="button" data-modal-open="new-chat">Новый чат</button>
</section>

<div class="messenger" data-chat-thread="<?= (int) $thread['id'] ?>" data-last-message="<?= (int) (end($messages)['id'] ?? 0) ?>">
    <aside class="messenger__sidebar">
        <div class="messenger__search">
            <label class="sr-only" for="chat-search">Поиск чатов</label>
            <input class="input" id="chat-search" type="search" placeholder="Поиск чатов">
        </div>
        <button class="conversation is-active" type="button">
            <span class="avatar" style="background:hsl(220 75% 60%)">К</span>
            <span class="conversation__body"><strong>Поддержка Кайфорда</strong><small>Ответим в рабочее время</small></span>
            <span class="status-dot" aria-label="Онлайн"></span>
        </button>
        <div class="messenger__recommendation">
            <span class="eyebrow">Рекомендуем</span>
            <p>Создай учебную группу, чтобы разбирать сложные темы вместе.</p>
            <button class="link-button" type="button" data-modal-open="new-chat">Создать группу</button>
        </div>
    </aside>

    <section class="messenger__thread">
        <header class="thread-head">
            <span class="avatar" style="background:hsl(220 75% 60%)">К</span>
            <div><strong>Поддержка Кайфорда</strong><small>Обычно отвечаем в течение дня</small></div>
            <button class="icon-button" type="button" aria-label="Информация о чате" data-toast="Здесь появится информация о чате">i</button>
        </header>
        <div class="message-list" data-message-list aria-live="polite">
            <?php if (!$messages): ?>
                <div class="chat-empty"><strong>Начни разговор</strong><span>Опиши вопрос — менеджер увидит его в рабочем кабинете.</span></div>
            <?php endif; ?>
            <?php foreach ($messages as $message): ?>
                <article class="message <?= $message['sender_type'] === 'user' ? 'message--outgoing' : 'message--incoming' ?>" data-message-id="<?= (int) $message['id'] ?>">
                    <div class="message__body"><?= nl2br(e($message['body'])) ?></div>
                    <time><?= e(date('H:i', strtotime($message['created_at']))) ?></time>
                </article>
            <?php endforeach; ?>
        </div>
        <form class="composer" method="post" action="<?= url('/chat/send') ?>" data-chat-form>
            <?= csrf_field() ?>
            <button class="icon-button" type="button" aria-label="Прикрепить файл" data-toast="Вложения станут доступны после подключения файлового хранилища">+</button>
            <label class="sr-only" for="chat-message">Сообщение</label>
            <textarea class="textarea" id="chat-message" name="message" rows="1" placeholder="Написать сообщение…" required></textarea>
            <button class="btn btn--primary" type="submit" data-submit>Отправить</button>
        </form>
    </section>
</div>

<div class="modal" id="new-chat" hidden role="dialog" aria-modal="true" aria-labelledby="new-chat-title">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog card card--pad">
        <button class="modal__close icon-button" type="button" aria-label="Закрыть" data-modal-close>×</button>
        <p class="eyebrow">Новая комната</p>
        <h2 id="new-chat-title">Создать учебную группу</h2>
        <p class="muted">Групповые чаты появятся после подключения серверного хранилища и модели участников.</p>
        <button class="btn btn--primary btn--block" type="button" data-modal-close>Понятно</button>
    </div>
</div>
