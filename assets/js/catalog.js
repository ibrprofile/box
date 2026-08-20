(function () {
    'use strict';
    const root = document.querySelector('.session');
    if (!root) return;

    const K = window.Kayford;
    const subjectId = Number(root.dataset.subjectId);
    const card = document.getElementById('task-card');
    const tpl = document.getElementById('tpl-task');
    const done = document.getElementById('session-done');
    const topicSelect = document.getElementById('topic-filter');

    const el = {
        solved: document.getElementById('s-solved'),
        streak: document.getElementById('s-streak'),
        timer: document.getElementById('s-timer'),
    };

    const state = {
        seen: [],
        solved: 0,
        streak: 0,
        current: null,
        startedAt: 0,
        answered: false,
    };

    /* Таймер решения текущей задачи */
    let timerId = null;
    function startTimer() {
        state.startedAt = Date.now();
        stopTimer();
        timerId = setInterval(() => {
            const s = Math.floor((Date.now() - state.startedAt) / 1000);
            el.timer.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        }, 250);
    }
    function stopTimer() {
        if (timerId) { clearInterval(timerId); timerId = null; }
    }

    async function loadNext() {
        card.innerHTML = '<div class="task-card__loading">Подбираем задачу…</div>';
        const topicId = topicSelect ? topicSelect.value : '';
        const res = await K.post('/catalog/next', {
            subject_id: subjectId,
            topic_id: topicId,
            exclude: state.seen,
        });

        if (res.done || !res.task) {
            stopTimer();
            card.hidden = true;
            done.hidden = false;
            return;
        }
        renderTask(res.task);
    }

    function renderTask(task) {
        state.current = task;
        state.answered = false;
        state.seen.push(task.id);

        const node = tpl.content.cloneNode(true);
        node.querySelector('[data-title]').textContent = task.title;
        node.querySelector('[data-diff]').textContent = '★'.repeat(task.difficulty);
        node.querySelector('[data-statement]').innerHTML = task.statement;

        const host = node.querySelector('[data-input-host]');
        host.appendChild(task.answer_type === 'choice' ? buildChoices(task) : buildText());

        node.querySelector('[data-answer-form]').addEventListener('submit', onSubmit);
        node.querySelector('[data-next]').addEventListener('click', loadNext);

        card.innerHTML = '';
        card.appendChild(node);
        card.hidden = false;
        startTimer();

        const first = card.querySelector('input[type=text], input[type=radio]');
        if (first && first.type === 'text') first.focus();
    }

    function buildText() {
        const wrap = document.createElement('div');
        wrap.className = 'field';
        wrap.innerHTML = '<input type="text" name="answer" autocomplete="off" ' +
            'inputmode="text" placeholder="Введите ответ" class="input input--lg">';
        return wrap;
    }

    function buildChoices(task) {
        const wrap = document.createElement('div');
        wrap.className = 'choices';
        (task.options || []).forEach((opt, i) => {
            const id = 'opt-' + i;
            const label = document.createElement('label');
            label.className = 'choice';
            label.innerHTML =
                '<input type="radio" name="answer" value="' + i + '" id="' + id + '">' +
                '<span class="choice__mark"></span>' +
                '<span class="choice__text"></span>';
            label.querySelector('.choice__text').textContent = opt;
            wrap.appendChild(label);
        });
        return wrap;
    }

    async function onSubmit(e) {
        e.preventDefault();
        if (state.answered) return;

        const form = e.currentTarget;
        const field = form.querySelector('input[name=answer]:checked, input[name=answer]');
        const value = form.querySelector('input[name=answer]:checked')
            ? form.querySelector('input[name=answer]:checked').value
            : (field ? field.value.trim() : '');

        if (value === '') { K.toast('Введите ответ', 'error'); return; }

        const btn = form.querySelector('[data-submit]');
        const res = await K.submit(btn, () => K.post('/catalog/answer', {
            task_id: state.current.id,
            answer: value,
            time_ms: Date.now() - state.startedAt,
        }));

        state.answered = true;
        stopTimer();
        showVerdict(res);
    }

    function showVerdict(res) {
        const verdict = card.querySelector('[data-verdict]');
        const head = card.querySelector('[data-verdict-head]');
        const solution = card.querySelector('[data-solution]');
        const form = card.querySelector('[data-answer-form]');

        form.querySelectorAll('input').forEach((i) => (i.disabled = true));
        card.querySelector('[data-submit]').hidden = true;

        verdict.hidden = false;
        verdict.classList.toggle('is-correct', res.correct);
        verdict.classList.toggle('is-wrong', !res.correct);

        head.innerHTML = res.correct
            ? '<span class="verdict-badge verdict-badge--ok">Верно</span>'
            : '<span class="verdict-badge verdict-badge--no">Неверно</span>' +
              '<span class="verdict-answer">Правильный ответ: <b>' + escapeHtml(res.correct_answer) + '</b></span>';

        solution.innerHTML = res.solution
            ? '<div class="solution"><div class="solution__label">Разбор</div>' + res.solution + '</div>'
            : '';

        if (res.correct) {
            state.solved += 1;
            state.streak += 1;
            el.solved.textContent = state.solved;
            el.streak.textContent = state.streak;
            renderRewards(res.reward);
        } else {
            state.streak = 0;
            el.streak.textContent = '0';
        }

        renderMath(card);
        verdict.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function renderRewards(events) {
        if (!Array.isArray(events)) return;
        events.forEach((ev) => {
            if (ev.type === 'xp') K.toast('+' + ev.value + ' XP', 'success');
            if (ev.type === 'level') K.toast('Новый уровень: ' + ev.value, 'success');
            if (ev.type === 'streak') K.toast('Серия ' + ev.value + ' дн', 'success');
            if (ev.type === 'achievement') K.toast('Достижение: ' + ev.value.title, 'success');
        });
    }

    function renderMath(scope) {
        if (window.renderMathInElement) {
            window.renderMathInElement(scope, {
                delimiters: [
                    { left: '$$', right: '$$', display: true },
                    { left: '\\(', right: '\\)', display: false },
                    { left: '$', right: '$', display: false },
                ],
                throwOnError: false,
            });
        }
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    if (topicSelect) topicSelect.addEventListener('change', () => {
        state.seen = [];
        loadNext();
    });

    loadNext();
})();
