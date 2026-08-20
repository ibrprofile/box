(function () {
    'use strict';
    const root = document.getElementById('stress');
    if (!root) return;

    const K = window.Kayford;
    const SESSION_LEN = 12;

    const wordBox = document.getElementById('word');
    const hintEl = document.getElementById('hint');
    const feedback = document.getElementById('feedback');
    const progressBar = document.getElementById('progress-bar');
    const comboWrap = document.getElementById('combo');
    const comboN = document.getElementById('combo-n');
    const done = document.getElementById('stress-done');

    const ui = {
        right: document.getElementById('ss-right'),
        total: document.getElementById('ss-total'),
        acc: document.getElementById('ss-acc'),
    };

    const state = {
        queue: [],
        seen: [],
        current: null,
        locked: false,
        right: 0,
        total: 0,
        combo: 0,
        bestCombo: 0,
        played: 0,
    };

    async function fill() {
        const res = await K.post('/trainer/stress/batch', { exclude: state.seen });
        if (res.words && res.words.length) {
            state.queue.push(...res.words);
            res.words.forEach((w) => state.seen.push(w.id));
        }
    }

    async function nextWord() {
        if (state.played >= SESSION_LEN) return finish();
        if (state.queue.length === 0) await fill();
        if (state.queue.length === 0) return finish();

        state.current = state.queue.shift();
        state.locked = false;
        render(state.current);
        updateProgress();
    }

    function render(word) {
        feedback.hidden = true;
        feedback.className = 'stress__feedback';
        wordBox.innerHTML = '';

        const vowels = new Set(word.vowels);
        const singleVowel = word.vowels.length === 1;

        word.letters.forEach((ch, i) => {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'letter';
            cell.textContent = ch;

            if (vowels.has(i)) {
                cell.classList.add('letter--vowel');
                cell.addEventListener('click', () => pick(i, cell));
                // слова с единственной гласной не тренируют навык — пропускаем автоматически
                if (singleVowel) cell.classList.add('letter--auto');
            } else {
                cell.disabled = true;
                cell.tabIndex = -1;
            }
            wordBox.appendChild(cell);
        });

        if (word.hint) {
            hintEl.textContent = word.hint;
            hintEl.hidden = false;
        } else {
            hintEl.hidden = true;
        }
    }

    async function pick(index, cell) {
        if (state.locked) return;
        state.locked = true;

        let res;
        try {
            res = await K.post('/trainer/stress/check', { word_id: state.current.id, index });
        } catch (e) {
            state.locked = false;
            return;
        }

        state.total += 1;
        state.played += 1;

        const cells = wordBox.querySelectorAll('.letter');
        if (res.correct) {
            cell.classList.add('is-correct');
            state.right += 1;
            state.combo += 1;
            state.bestCombo = Math.max(state.bestCombo, state.combo);
            flash('Верно', true);
            reward(res.reward);
        } else {
            cell.classList.add('is-wrong');
            if (cells[res.stress_index]) cells[res.stress_index].classList.add('is-answer');
            state.combo = 0;
            flash('Ударение на другую букву', false);
        }

        updateCombo();
        updateStats();
        setTimeout(nextWord, res.correct ? 620 : 1150);
    }

    function flash(text, ok) {
        feedback.textContent = text;
        feedback.hidden = false;
        feedback.classList.toggle('is-ok', ok);
        feedback.classList.toggle('is-no', !ok);
    }

    function updateStats() {
        ui.right.textContent = state.right;
        ui.total.textContent = state.total;
        ui.acc.textContent = (state.total ? Math.round((state.right / state.total) * 100) : 100) + '%';
    }

    function updateCombo() {
        if (state.combo >= 2) {
            comboWrap.hidden = false;
            comboN.textContent = state.combo;
            comboWrap.classList.remove('pulse');
            void comboWrap.offsetWidth;
            comboWrap.classList.add('pulse');
        } else {
            comboWrap.hidden = true;
        }
    }

    function updateProgress() {
        progressBar.style.width = Math.min(100, (state.played / SESSION_LEN) * 100) + '%';
    }

    function reward(events) {
        if (Array.isArray(events)) {
            events.forEach((ev) => {
                if (ev.type === 'level') K.toast('Новый уровень: ' + ev.value, 'success');
                if (ev.type === 'achievement') K.toast('Достижение: ' + ev.value.title, 'success');
            });
        }
    }

    function finish() {
        updateProgress();
        root.hidden = true;
        done.hidden = false;
        const acc = state.total ? Math.round((state.right / state.total) * 100) : 100;
        document.getElementById('done-acc').textContent = acc + '%';
        document.getElementById('done-right').textContent = state.right;
        document.getElementById('done-best').textContent = state.bestCombo;
    }

    document.getElementById('again').addEventListener('click', () => {
        Object.assign(state, { queue: [], current: null, locked: false, right: 0, total: 0, combo: 0, bestCombo: 0, played: 0 });
        done.hidden = true;
        root.hidden = false;
        updateStats();
        updateCombo();
        nextWord();
    });

    nextWord();
})();
