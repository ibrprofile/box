<?php /** @var array $subjects */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Каталог задач</h1>
        <p class="page-sub">Выбирай предмет и решай — платформа сама подберёт задания там, где ты чаще ошибаешься.</p>
    </div>
</section>

<?php if (!$subjects): ?>
    <div class="empty">
        <p>Пока нет ни одного предмета. Загляни позже.</p>
    </div>
<?php else: ?>
    <div class="subject-grid">
        <?php foreach ($subjects as $s): ?>
            <a class="subject-card" href="<?= url('/catalog/' . $s['slug']) ?>"
               style="--hue: <?= (int) $s['hue'] ?>">
                <span class="subject-card__icon" aria-hidden="true"><?= e($s['icon']) ?></span>
                <span class="subject-card__body">
                    <span class="subject-card__name"><?= e($s['name']) ?></span>
                    <span class="subject-card__count"><?= (int) $s['task_count'] ?> <?= plural((int) $s['task_count'], 'задача', 'задачи', 'задач') ?></span>
                </span>
                <span class="subject-card__go" aria-hidden="true"><?= icon('arrow-right') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
