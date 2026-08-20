<?php
/**
 * @var array $user @var array $progress @var array $catalog
 * @var array $bySubject @var array $trainer @var array $achievements
 * @var int $unlocked @var int $rank @var array $socials
 */
?>
<section class="profile-hero" style="--hue: <?= (int) $user['avatar_hue'] ?>">
    <div class="profile-hero__id">
        <div class="avatar avatar--xl"><?= e(initials($user)) ?></div>
        <div>
            <h1 class="profile-hero__name"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h1>
            <p class="profile-hero__sub">
                <?= $user['grade'] ? (int) $user['grade'] . ' класс · ' : '' ?><?= e($user['email']) ?>
            </p>
        </div>
    </div>

    <div class="profile-hero__level">
        <div class="level-badge">
            <span class="level-badge__n"><?= (int) $progress['level'] ?></span>
            <span class="level-badge__l">уровень</span>
        </div>
        <div class="level-progress">
            <div class="level-progress__row">
                <span><?= (int) $progress['into'] ?> / <?= (int) $progress['need'] ?> XP</span>
                <span><?= (int) $user['xp'] ?> XP всего</span>
            </div>
            <div class="bar"><span style="width: <?= (int) $progress['percent'] ?>%"></span></div>
        </div>
    </div>
</section>

<div class="profile-metrics">
    <div class="metric">
        <span class="metric__ic"><?= icon('flame') ?></span>
        <span class="metric__v"><?= (int) $user['streak_count'] ?></span>
        <span class="metric__l">дней серия</span>
        <span class="metric__x">рекорд <?= (int) $user['streak_best'] ?></span>
    </div>
    <div class="metric">
        <span class="metric__ic"><?= icon('trophy') ?></span>
        <span class="metric__v">#<?= (int) $rank ?></span>
        <span class="metric__l">в рейтинге</span>
    </div>
    <div class="metric">
        <span class="metric__ic"><?= icon('check') ?></span>
        <span class="metric__v"><?= (int) $catalog['solved'] ?></span>
        <span class="metric__l">задач решено</span>
        <span class="metric__x"><?= (int) $catalog['accuracy'] ?>% точность</span>
    </div>
    <div class="metric">
        <span class="metric__ic"><?= icon('medal') ?></span>
        <span class="metric__v"><?= (int) $unlocked ?></span>
        <span class="metric__l">достижений</span>
    </div>
</div>

<div class="profile-cols">
    <section class="panel">
        <h2 class="panel__title">Точность по предметам</h2>
        <?php if (!$bySubject): ?>
            <p class="muted">Реши несколько задач в каталоге — здесь появится разбивка.</p>
        <?php else: ?>
            <ul class="subject-bars">
                <?php foreach ($bySubject as $row):
                    $pct = (int) $row['seen'] > 0 ? (int) round((int) $row['solved'] / (int) $row['seen'] * 100) : 0; ?>
                    <li style="--hue: <?= (int) $row['hue'] ?>">
                        <div class="subject-bars__head">
                            <span><?= e($row['name']) ?></span>
                            <span><?= $pct ?>%</span>
                        </div>
                        <div class="bar bar--hue"><span style="width: <?= $pct ?>%"></span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="mini-stats">
            <div><b><?= (int) $trainer['words'] ?></b><small>слов в тренажёре</small></div>
            <div><b><?= (int) $trainer['accuracy'] ?>%</b><small>точность ударений</small></div>
            <div><b><?= (int) $trainer['best_streak'] ?></b><small>лучшая серия слов</small></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head">
            <h2 class="panel__title">Достижения</h2>
            <span class="muted"><?= (int) $unlocked ?> / <?= count($achievements) ?></span>
        </div>
        <ul class="achievements">
            <?php foreach ($achievements as $a): $got = $a['unlocked_at'] !== null; ?>
                <li class="ach <?= $got ? 'is-on' : 'is-off' ?>" title="<?= e($a['description']) ?>">
                    <span class="ach__ic" aria-hidden="true"><?= e($a['icon']) ?></span>
                    <span class="ach__body">
                        <span class="ach__title"><?= e($a['title']) ?></span>
                        <span class="ach__desc"><?= e($a['description']) ?></span>
                    </span>
                    <?php if ($got): ?><span class="ach__done"><?= icon('check') ?></span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="panel">
    <div class="panel__head">
        <h2 class="panel__title">Вход и доступ</h2>
    </div>
    <div class="security-row">
        <div class="security-row__left">
            <p class="security-row__k">Почта для входа и уведомлений</p>
            <p class="muted"><?= e($user['email']) ?></p>
        </div>
    </div>
    <?php if ($socials): ?>
    <div class="security-title">Привязанные аккаунты</div>
    <ul class="social-list">
        <?php foreach ($socials as $acc): ?>
        <li class="social-list__row" data-provider="<?= e($acc['provider']) ?>">
            <div class="social-list__main">
                <span class="social-chip social-chip--<?= e($acc['provider']) ?>">
                    <?= $acc['provider'] === 'vk' ? 'VK ID' : 'Яндекс ID' ?>
                </span>
                <?php if (!empty($acc['email'])): ?>
                    <span class="muted"><?= e($acc['email']) ?></span>
                <?php endif; ?>
            </div>
            <div class="social-list__right">
                <?php if (!empty($acc['last_used_at'])): ?>
                    <span class="muted">был в <?= e(time_ago($acc['last_used_at'])) ?></span>
                <?php endif; ?>
                <button type="button" class="btn btn--ghost btn--sm" data-unlink="<?= e($acc['provider']) ?>" data-csrf="<?= csrf_token() ?>">Отвязать</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <div class="security-title">Выйти</div>
    <form method="post" action="<?= url('/auth/logout') ?>">
        <?= csrf_field() ?>
        <button class="btn btn--ghost" type="submit">Выйти из аккаунта</button>
    </form>
</section>
<script defer>
document.querySelectorAll('[data-unlink]').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const provider = btn.getAttribute('data-unlink');
    if (!confirm('Отвязать ' + (provider === 'vk' ? 'VK ID' : 'Яндекс ID') + '?')) return;
    const csrf = btn.getAttribute('data-csrf') || '';
    try {
      btn.disabled = true;
      const res = await fetch('<?= url('/profile/delete-social') ?>', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ provider: provider })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error((data && data.error) || 'Не удалось отвязать');
      btn.closest('.social-list__row').remove();
      Kayford.toast('Аккаунт отвязан', 'success');
    } catch (err) {
      Kayford.toast(err.message || 'Ошибка', 'error');
    } finally {
      btn.disabled = false;
    }
  });
});
</script>


