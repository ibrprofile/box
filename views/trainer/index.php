<?php /** @var array $summary */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Тренажёры</h1>
        <p class="page-sub">Короткие интенсивные сессии. Слова с ошибками возвращаются чаще, пока не закрепишь.</p>
    </div>
</section>

<div class="trainer-grid">
    <a class="trainer-card trainer-card--stress" href="<?= url('/trainer/stress') ?>">
        <div class="trainer-card__glyph" aria-hidden="true">удАрение</div>
        <div class="trainer-card__meta">
            <h2>Ударения</h2>
            <p>Выбери букву, на которую падает ударение. Быстро, на скорость.</p>
        </div>
        <div class="trainer-card__stats">
            <span><b><?= (int) $summary['words'] ?></b> слов</span>
            <span><b><?= (int) $summary['accuracy'] ?>%</b> точность</span>
            <span><b><?= (int) $summary['best_streak'] ?></b> серия</span>
        </div>
        <span class="trainer-card__go"><?= icon('arrow-right') ?></span>
    </a>

    <div class="trainer-card trainer-card--soon" aria-disabled="true">
        <div class="trainer-card__glyph" aria-hidden="true"><?= icon('sparkles') ?></div>
        <div class="trainer-card__meta">
            <h2>Скоро</h2>
            <p>Новые тренажёры по орфографии и пунктуации уже в работе.</p>
        </div>
    </div>
</div>
