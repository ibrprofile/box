<?php /** @var array $rows @var array $pagination @var array $subjects @var array $filters */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Управление задачами</h1>
        <p class="page-sub">Создавай и редактируй задачи с удобным редактором и быстрым форматированием.</p>
    </div>
    <a class="btn btn--primary" href="<?= url('/admin/tasks/new') ?>">+ Новая задача</a>
</section>

<form class="card card--pad" method="get" style="margin-bottom:16px">
    <div class="grid-2">
        <div class="field">
            <label>Поиск</label>
            <input class="input" name="q" value="<?= e((string) $filters['q']) ?>" placeholder="Название, текст задачи">
        </div>
        <div class="field">
            <label>Предмет</label>
            <select class="input select" name="subject_id">
                <option value="">Все</option>
                <?php foreach ($subjects as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= ((int) ($filters['subject_id'] ?? 0) === (int) $s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button class="btn btn--secondary" type="submit" style="margin-top:12px">Применить</button>
</form>

<div class="stack">
    <?php foreach ($rows as $row): ?>
        <article class="card card--pad">
            <div class="row row--wrap" style="justify-content:space-between;gap:12px">
                <div>
                    <h3><?= e($row['title']) ?></h3>
                    <div class="muted">Предмет: <?= e($row['subject_name'] ?? '') ?> · <?= e($row['topic_name'] ?? '') ?></div>
                </div>
                <div class="row">
                    <a class="btn btn--ghost" href="<?= url('/admin/tasks/' . $row['id'] . '/edit') ?>">Редактировать</a>
                    <form method="post" action="<?= url('/admin/tasks/' . $row['id'] . '/delete') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn--danger" type="submit">Удалить</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
