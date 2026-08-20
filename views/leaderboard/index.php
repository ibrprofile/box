<?php
/** @var array $top @var int $meId @var int $myRank */
$podium = array_slice($top, 0, 3);
$rest   = array_slice($top, 3);
$order  = [1, 0, 2]; // визуальный порядок пьедестала: 2-1-3
?>
<section class="page-head">
    <div>
        <h1 class="page-title">Топ-50</h1>
        <p class="page-sub">Лучшие по опыту. Решай задачи и тренируйся, чтобы подняться выше.</p>
    </div>
    <?php if ($myRank > 0): ?>
        <div class="rank-pill">Твоё место: <b>#<?= (int) $myRank ?></b></div>
    <?php endif; ?>
</section>

<?php if (count($podium) === 3): ?>
    <div class="podium">
        <?php foreach ($order as $slot): $u = $podium[$slot]; $place = $slot + 1; ?>
            <div class="podium__col podium__col--<?= $place ?> <?= (int) $u['id'] === $meId ? 'is-me' : '' ?>"
                 style="--hue: <?= (int) $u['avatar_hue'] ?>">
                <div class="podium__place"><?= $place ?></div>
                <div class="avatar avatar--lg"><?= e(initials($u)) ?></div>
                <div class="podium__name"><?= e($u['first_name'] . ' ' . mb_substr($u['last_name'], 0, 1) . '.') ?></div>
                <div class="podium__xp"><?= (int) $u['xp'] ?> XP</div>
                <div class="podium__lvl">ур. <?= (int) $u['level'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<ol class="board" start="4">
    <?php foreach ($rest as $i => $u): $place = $i + 4; ?>
        <li class="board__row <?= (int) $u['id'] === $meId ? 'is-me' : '' ?>" style="--hue: <?= (int) $u['avatar_hue'] ?>">
            <span class="board__place"><?= $place ?></span>
            <span class="avatar avatar--sm"><?= e(initials($u)) ?></span>
            <span class="board__name">
                <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                <?php if ($u['grade']): ?><small><?= (int) $u['grade'] ?> кл.</small><?php endif; ?>
            </span>
            <span class="board__streak"><?= icon('flame') ?> <?= (int) $u['streak_best'] ?></span>
            <span class="board__lvl">ур. <?= (int) $u['level'] ?></span>
            <span class="board__xp"><?= (int) $u['xp'] ?> XP</span>
        </li>
    <?php endforeach; ?>
</ol>

<?php if (!$top): ?>
    <div class="empty"><p>Пока никто не в рейтинге. Стань первым!</p></div>
<?php endif; ?>
