<section class="stress" id="stress">
    <header class="stress__bar">
        <a class="session__back" href="<?= url('/trainer') ?>" aria-label="К тренажёрам"><?= icon('arrow-right', 'flip') ?></a>
        <h1 class="stress__title">Ударения</h1>
        <div class="stress__score">
            <span class="stress__combo" id="combo" hidden><?= icon('flame') ?> <b id="combo-n">0</b></span>
        </div>
    </header>

    <div class="stress__progress"><span id="progress-bar"></span></div>

    <div class="stress__stage" aria-live="polite">
        <p class="stress__prompt">Нажми на ударную гласную</p>

        <div class="stress__word" id="word" role="group" aria-label="Слово по буквам"></div>

        <p class="stress__hint" id="hint" hidden></p>
        <p class="stress__feedback" id="feedback" hidden></p>
    </div>

    <div class="stress__footer">
        <div class="stress-stat"><span id="ss-right">0</span><small>верно</small></div>
        <div class="stress-stat"><span id="ss-total">0</span><small>всего</small></div>
        <div class="stress-stat"><span id="ss-acc">100%</span><small>точность</small></div>
    </div>
</section>

<div class="stress__done" id="stress-done" hidden>
    <div class="stress__done-num" id="done-acc">100%</div>
    <p>точность за сессию</p>
    <div class="stress__done-row">
        <div><b id="done-right">0</b><small>верно</small></div>
        <div><b id="done-best">0</b><small>лучшая серия</small></div>
    </div>
    <button type="button" class="btn btn--primary" id="again">Ещё сессия</button>
    <a class="btn btn--ghost" href="<?= url('/trainer') ?>">Выйти</a>
</div>
