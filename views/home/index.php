<?php ?>
<section class="auth-screen">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand__mark">K</span>
            <span class="auth-wordmark">Kayford</span>
        </div>
        <p class="auth-tagline">Подготовка к ЕГЭ с задачами, тренажёрами, понятным прогрессом и поддержкой в чате.</p>

        <div class="stack">
            <a class="btn btn--primary btn--block" href="<?= url('/auth') ?>">Начать обучение <span aria-hidden="true">→</span></a>
            <a class="btn btn--ghost btn--block" href="<?= url('/auth') ?>">Войти в аккаунт</a>
        </div>

        <div class="grid" style="margin-top:8px">
            <a class="card card--pad" href="<?= url('/auth') ?>">
                <h3>Каталог задач</h3>
                <p class="muted">Темы, уровень сложности и задания для точечной практики.</p>
            </a>
            <a class="card card--pad" href="<?= url('/auth') ?>">
                <h3>Тренажёры</h3>
                <p class="muted">Короткие сессии с мгновенной проверкой и объяснением ошибок.</p>
            </a>
        </div>
        <p class="auth-note">После входа сохраняются прогресс, серия дней и результаты.</p>
    </div>
</section>
