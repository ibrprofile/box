<?php ?>
<section class="auth-screen">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand__mark">K</span>
            <span class="auth-wordmark">Kayford</span>
        </div>
        <p class="auth-tagline">Подготовка к ЕГЭ с задачами, тренажёрами, ростом уровня и живым прогрессом.</p>

        <div class="stack">
            <a class="btn btn--primary btn--block" href="<?= url('/auth') ?>">Начать обучение</a>
            <a class="btn btn--ghost btn--block" href="<?= url('/auth') ?>">Войти в аккаунт</a>
        </div>

        <div class="grid" style="margin-top:8px">
            <div class="card card--pad">
                <h3>Каталог задач</h3>
                <p class="muted">Переходи к темам, где чаще ошибаешься, и решай в удобном темпе.</p>
            </div>
            <div class="card card--pad">
                <h3>Тренажёр ударений</h3>
                <p class="muted">Быстрые сессии, адаптивная подборка слов и мгновенная обратная связь.</p>
            </div>
        </div>
    </div>
</section>
