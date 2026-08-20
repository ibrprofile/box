<?php /** @var array $words */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Слова тренажёра</h1>
        <p class="page-sub">Добавляй новые слова и управляй набором для ускоренного тренинга.</p>
    </div>
</section>

<form class="card card--pad stack" method="post" action="<?= url('/admin/words') ?>" style="margin-bottom:16px">
    <?= csrf_field() ?>
    <div class="grid-2">
        <div class="field">
            <label>Слово</label>
            <input class="input" name="word" required>
        </div>
        <div class="field">
            <label>Индекс ударной буквы</label>
            <input class="input" type="number" name="stress_index" value="0" min="0">
        </div>
    </div>
    <div class="grid-2">
        <div class="field">
            <label>Сложность</label>
            <input class="input" type="number" name="difficulty" value="2" min="1" max="5">
        </div>
        <div class="field">
            <label>Подсказка</label>
            <input class="input" name="hint" placeholder="Например: «слово из литературы»">
        </div>
    </div>
    <div class="field">
        <label>Опубликовано</label>
        <select class="input select" name="is_active">
            <option value="1">Да</option>
            <option value="0">Нет</option>
        </select>
    </div>
    <button class="btn btn--primary" type="submit">Добавить</button>
</form>

<div class="stack">
    <?php foreach ($words as $word): ?>
        <article class="card card--pad row row--wrap" style="justify-content:space-between">
            <div>
                <h3><?= e($word['word']) ?></h3>
                <div class="muted">Ударение: <?= (int) $word['stress_index'] ?> · <?= (int) $word['difficulty'] ?>/5</div>
            </div>
            <form method="post" action="<?= url('/admin/words/' . $word['id'] . '/delete') ?>">
                <?= csrf_field() ?>
                <button class="btn btn--danger" type="submit">Удалить</button>
            </form>
        </article>
    <?php endforeach; ?>
</div>
