<?php
/** @var array $user @var array $progress @var array $catalog @var array $trainer @var int $rank @var array $subjects */
$hour = (int) date('G');
$greet = $hour < 6 ? 'Доброй ночи' : ($hour < 12 ? 'Доброе утро' : ($hour < 18 ? 'Добрый день' : 'Добрый вечер'));
?>
<section class="dash-hero" style="--hue: <?= (int) $user['avatar_hue'] ?>">
    <div class="dash-hero__text">
        <p class="dash-hero__greet"><?= $greet ?>,</p>
        <h1 class="dash-hero__name"><?= e($user['first_name']) ?></h1>
        <div class="dash-hero__meta">
            <span class="chip chip--flame"><?= icon('flame') ?> <?= (int) $user['streak_count'] ?> дн</span>
            <span class="chip"><?= icon('trophy') ?> #<?= (int) $rank ?></span>
            <span class="chip"><?= icon('bolt') ?> <?= (int) $user['xp'] ?> XP</span>
        </div>
    </div>
    <div class="dash-hero__level">
        <div class="level-ring" style="--p: <?= (int) $progress['percent'] ?>">
            <span><?= (int) $progress['level'] ?></span>
        </div>
        <small><?= (int) $progress['into'] ?>/<?= (int) $progress['need'] ?> до <?= (int) $progress['level'] + 1 ?> ур.</small>
    </div>
</section>

<div class="dash-actions">
    <a class="action-card action-card--primary" href="<?= url('/catalog') ?>">
        <span class="action-card__ic"><?= icon('catalog') ?></span>
        <span class="action-card__t">Каталог задач</span>
        <span class="action-card__s"><?= (int) $catalog['solved'] ?> решено · <?= (int) $catalog['accuracy'] ?>% точность</span>
    </a>
    <a class="action-card action-card--accent" href="<?= url('/trainer/stress') ?>">
        <span class="action-card__ic"><?= icon('trainer') ?></span>
        <span class="action-card__t">Тренажёр ударений</span>
        <span class="action-card__s"><?= (int) $trainer['words'] ?> слов · <?= (int) $trainer['accuracy'] ?>% точность</span>
    </a>
</div>

<section class="panel">
    <div class="panel__head">
        <h2 class="panel__title">Предметы</h2>
        <a class="link" href="<?= url('/catalog') ?>">Все <?= icon('chevron-right') ?></a>
    </div>
    <div class="subject-grid subject-grid--compact">
        <?php foreach (array_slice($subjects, 0, 6) as $s): ?>
            <a class="subject-card" href="<?= url('/catalog/' . $s['slug']) ?>" style="--hue: <?= (int) $s['hue'] ?>">
                <span class="subject-card__icon" aria-hidden="true"><?= e($s['icon']) ?></span>
                <span class="subject-card__body">
                    <span class="subject-card__name"><?= e($s['name']) ?></span>
                    <span class="subject-card__count"><?= (int) $s['task_count'] ?> <?= plural((int) $s['task_count'], 'задача', 'задачи', 'задач') ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
