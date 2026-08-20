<?php
/**
 * @var array $__data
 * @var bool  $vkEnabled
 * @var bool  $yandexEnabled
 */
?>
<div class="auth-card" data-auth>
  <div class="auth-brand">
    <span class="brand__mark">K</span>
    <span class="auth-wordmark">Kayford</span>
  </div>
  <p class="auth-tagline">Готовься к ЕГЭ каждый день — задачи, тренажёры и живой прогресс.</p>

  <div class="seg" role="tablist" aria-label="Вход или регистрация">
    <button type="button" class="seg__btn is-active" data-tab="login" role="tab" aria-selected="true">Вход</button>
    <button type="button" class="seg__btn" data-tab="register" role="tab" aria-selected="false">Регистрация</button>
  </div>

  <?php if ($msg = flash('error')): ?>
    <div class="auth-flash auth-flash--error" role="alert"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('success')): ?>
    <div class="auth-flash auth-flash--success" role="status"><?= e($msg) ?></div>
  <?php endif; ?>

  <!-- ====================== Social buttons ====================== -->
  <?php if ($vkEnabled || $yandexEnabled): ?>
    <div class="social-row" role="group" aria-label="Быстрый вход">
      <?php if ($vkEnabled): ?>
        <a class="social-btn social-btn--vk" href="<?= url('/auth/vk/start') ?>" rel="nofollow">
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M13.166 15.396c-.543 0-.77-.384-1.414-1.01-.612-.6-.888-.89-1.192-.89-.26 0-.32.136-.32.515v.728c0 .32-.113.507-.982.507-1.31 0-3.224-.77-4.552-2.766 0 0-.784-1.366-.784-2.486 0-.413.21-.6.386-.6.261 0 .514.13.714.53.648 1.31 1.653 2.488 2.235 2.488.28 0 .36-.12.36-.593V9.533c0-.416-.165-.585.322-.585.38 0 1.09.096 1.508.553.387.423.412.874.412.978v2.074c0 .268.133.412.25.412.268 0 .575-.165.96-.604.512-.584 1.04-1.54 1.04-1.54.154-.269.307-.413.534-.413.312 0 .466.237.466.584v1.182c0 .32.125.472.365.472.256 0 .728-.278 1.12-.772.52-.655.964-1.57.964-1.57.15-.266.32-.41.55-.41.276 0 .415.252.415.637 0 1.03-.658 2.462-2.048 3.624-1.02.855-1.85 1.037-2.138 1.037z"/></svg>
          <span>Войти через VK</span>
        </a>
      <?php endif; ?>
      <?php if ($yandexEnabled): ?>
        <a class="social-btn social-btn--ya" href="<?= url('/auth/yandex/start') ?>" rel="nofollow">
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12.75 2h-1.5C5.595 2 2 5.595 2 11.25v1.5C2 18.405 5.595 22 11.25 22h1.5c5.655 0 9.25-3.595 9.25-9.25v-1.5C22 5.595 18.405 2 12.75 2zm1.73 14.98h-1.13c-.22 0-.36-.1-.51-.37l-3.1-6.38h.95l1.94 4.44c.04.1.08.15.15.15.03 0 .07-.03.1-.1l2.25-4.49h.91l-2.56 6.75z"/></svg>
          <span>Войти через Яндекс</span>
        </a>
      <?php endif; ?>
    </div>

    <div class="divider" role="separator" aria-label="или"><span>или по почте</span></div>
  <?php endif; ?>

  <!-- ====================== Вход ====================== -->
  <section class="auth-pane" data-pane="login">
    <form class="stack" data-form="login-start" novalidate>
      <div class="field">
        <label for="login-email">Почта</label>
        <input id="login-email" class="input" type="email" name="email" autocomplete="email" required placeholder="you@example.com">
      </div>
      <button class="btn btn--primary btn--block" type="submit">
        Получить код для входа
      </button>
    </form>

    <form class="stack hide" data-form="login-code" novalidate>
      <div class="code-sent">
        <p class="code-sent__title">Код отправлен</p>
        <p class="code-sent__email" data-email-mask></p>
      </div>
      <div class="field">
        <label for="login-code">Код из письма</label>
        <input id="login-code" class="input input--code" type="text" inputmode="numeric" pattern="\d{6}" name="code" autocomplete="one-time-code" maxlength="6" placeholder="000000" required>
      </div>
      <button class="btn btn--primary btn--block" type="submit">Войти</button>
      <div class="code-actions">
        <button type="button" class="linklike" data-action="resend-login">Отправить код повторно</button>
        <button type="button" class="linklike" data-back="login">Сменить почту</button>
      </div>
    </form>
  </section>

  <!-- ====================== Регистрация ====================== -->
  <section class="auth-pane hide" data-pane="register">
    <form class="stack" data-form="register-start" novalidate>
      <div class="grid-2">
        <div class="field">
          <label for="reg-first">Имя</label>
          <input id="reg-first" class="input" type="text" name="first_name" autocomplete="given-name" required>
        </div>
        <div class="field">
          <label for="reg-last">Фамилия</label>
          <input id="reg-last" class="input" type="text" name="last_name" autocomplete="family-name" required>
        </div>
      </div>
      <div class="field">
        <label for="reg-grade">Класс</label>
        <select id="reg-grade" class="input select" name="grade" required>
          <option value="" disabled selected>Выберите класс</option>
          <?php for ($g = 11; $g >= 5; $g--): ?>
            <option value="<?= $g ?>"><?= $g ?> класс</option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="field">
        <label for="reg-email">Почта</label>
        <input id="reg-email" class="input" type="email" name="email" autocomplete="email" required placeholder="you@example.com">
        <span class="field__hint">Сюда будем присылать уведомления и код подтверждения. Почта должна быть уникальной.</span>
      </div>
      <button class="btn btn--primary btn--block" type="submit">
        Отправить код подтверждения
      </button>
    </form>

    <form class="stack hide" data-form="register-code" novalidate>
      <div class="code-sent">
        <p class="code-sent__title">Код отправлен</p>
        <p class="code-sent__email" data-email-mask></p>
      </div>
      <div class="field">
        <label for="reg-code">Код из письма</label>
        <input id="reg-code" class="input input--code" type="text" inputmode="numeric" pattern="\d{6}" name="code" autocomplete="one-time-code" maxlength="6" placeholder="000000" required>
      </div>
      <button class="btn btn--primary btn--block" type="submit">Завершить регистрацию</button>
      <div class="code-actions">
        <button type="button" class="linklike" data-action="resend-register">Отправить код повторно</button>
        <button type="button" class="linklike" data-back="register">Изменить данные</button>
      </div>
    </form>
  </section>
</div>

<script src="<?= asset('js/auth.js') ?>" defer></script>
