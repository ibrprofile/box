<?php
/** @var array $subject @var array $topics */
?>
<section class="session"
         data-subject-id="<?= (int) $subject['id'] ?>"
         style="--hue: <?= (int) $subject['hue'] ?>">

    <header class="session__bar">
        <a class="session__back" href="<?= url('/catalog') ?>" aria-label="К каталогу">
            <?= icon('arrow-right', 'flip') ?>
        </a>
        <div class="session__title">
            <span class="session__icon" aria-hidden="true"><?= e($subject['icon']) ?></span>
            <span><?= e($subject['name']) ?></span>
        </div>

        <?php if ($topics): ?>
            <label class="session__topic">
                <span class="sr-only">Тема</span>
                <select id="topic-filter">
                    <option value="">Все темы</option>
                    <?php foreach ($topics as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
    </header>

    <div class="session__stats">
        <div class="stat-chip"><span class="stat-chip__k" id="s-solved">0</span><span class="stat-chip__l">решено</span></div>
        <div class="stat-chip"><span class="stat-chip__k" id="s-streak">0</span><span class="stat-chip__l">подряд</span></div>
        <div class="stat-chip stat-chip--time">
            <?= icon('clock') ?>
            <span class="stat-chip__k" id="s-timer">0:00</span>
        </div>
    </div>

    <article class="task-card" id="task-card" aria-live="polite">
        <div class="task-card__loading">Подбираем задачу…</div>
    </article>
</section>

<template id="tpl-task">
    <div class="task-card__head">
        <span class="task-card__topic" data-title></span>
        <span class="task-card__diff" data-diff></span>
    </div>
    <div class="task-card__body" data-statement></div>

    <form class="task-answer" data-answer-form>
        <div data-input-host></div>
        <button type="submit" class="btn btn--primary btn--block" data-submit>Проверить</button>
    </form>

    <div class="task-verdict" data-verdict hidden>
        <div class="task-verdict__head" data-verdict-head></div>
        <div class="task-verdict__solution" data-solution></div>
        <button type="button" class="btn btn--primary btn--block" data-next>Следующая задача</button>
    </div>
</template>

<div class="session__done" id="session-done" hidden>
    <div class="session__done-emoji" aria-hidden="true"><?= icon('sparkles') ?></div>
    <h2>Задачи закончились</h2>
    <p>Ты прошёл всё, что у нас есть по этому предмету. Возвращайся позже за новыми.</p>
    <a class="btn btn--primary" href="<?= url('/catalog') ?>">К каталогу</a>
</div>
