<?php /** @var array $rows */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Пользователи</h1>
        <p class="page-sub">Смотрите активность и управляйте ролями и статусом.</p>
    </div>
</section>

<div class="stack">
    <?php foreach ($rows as $row): ?>
        <article class="card card--pad">
            <div class="row row--wrap" style="justify-content:space-between;gap:12px">
                <div>
                    <h3><?= e($row['first_name'] . ' ' . $row['last_name']) ?></h3>
                    <div class="muted"><?= e($row['email']) ?> · <?= (int) $row['xp'] ?> XP · ур. <?= (int) $row['level'] ?></div>
                </div>
                <div class="row">
                    <a class="btn btn--ghost" href="<?= url('/admin/users/' . $row['id']) ?>">Открыть</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
