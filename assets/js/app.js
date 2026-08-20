/* Кайфорд — общие клиентские утилиты */
(function () {
    'use strict';

    const base = (window.KAYFORD && window.KAYFORD.base) || '';
    let KayfordApi = null;
    const csrf = () => {
        const el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    };

    /** URL с учётом base_path. */
    const url = (path) => base + (path.startsWith('/') ? path : '/' + path);

    /** JSON-запрос с CSRF-заголовком. */
    async function api(path, options = {}) {
        const opts = Object.assign({ method: 'GET', headers: {} }, options);
        opts.headers = Object.assign(
            {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrf(),
            },
            opts.headers
        );
        if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(opts.body);
        }
        const res = await fetch(url(path), opts);
        let data = null;
        try { data = await res.json(); } catch (e) { /* пустой ответ */ }
        if (!res.ok) {
            const message = (data && data.error) || 'Ошибка запроса';
            throw new Error(message);
        }
        return data;
    }

    /** Всплывающее уведомление. */
    function toast(message, type = 'info') {
        let host = document.querySelector('.toast-host');
        if (!host) {
            host = document.createElement('div');
            host.className = 'toast-host';
            document.body.appendChild(host);
        }
        const el = document.createElement('div');
        el.className = 'toast toast--' + type;
        el.textContent = message;
        host.appendChild(el);
        requestAnimationFrame(() => el.classList.add('is-in'));
        setTimeout(() => {
            el.classList.remove('is-in');
            setTimeout(() => el.remove(), 250);
        }, 3200);
    }

    const post = (path, body) => api(path, { method: 'POST', body: body || {} });
    const get = (path) => api(path, { method: 'GET' });

    /**
     * Обёртка над асинхронным действием кнопки: блокирует кнопку,
     * показывает состояние загрузки и выводит ошибку тостом.
     */
    async function submit(button, action) {
        if (button) { button.disabled = true; button.classList.add('is-loading'); }
        try {
            return await action();
        } catch (err) {
            toast(err.message || 'Что-то пошло не так', 'error');
            throw err;
        } finally {
            if (button) { button.disabled = false; button.classList.remove('is-loading'); }
        }
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('has-modal');
    }

    function initModals() {
        document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const modal = document.getElementById(trigger.dataset.modalOpen);
                if (!modal) return;
                modal.hidden = false;
                document.body.classList.add('has-modal');
                modal.querySelector('button, input, textarea')?.focus();
            });
        });
        document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
            trigger.addEventListener('click', () => closeModal(trigger.closest('.modal')));
        });
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('.modal:not([hidden])').forEach(closeModal);
        });
    }

    function initToasts() {
        document.querySelectorAll('[data-toast]').forEach((el) => {
            el.addEventListener('click', () => toast(el.dataset.toast));
        });
    }

    function initChat() {
        const root = document.querySelector('[data-chat-thread]');
        const form = document.querySelector('[data-chat-form]');
        const list = document.querySelector('[data-message-list]');
        if (!root || !form || !list) return;
        let lastId = Number(root.dataset.lastMessage || 0);
        const render = (message) => {
            if (list.querySelector(`[data-message-id="${message.id}"]`)) return;
            list.querySelector('.chat-empty')?.remove();
            const article = document.createElement('article');
            article.className = 'message message--incoming';
            article.dataset.messageId = message.id;
            article.innerHTML = `<div class="message__body"></div><time></time>`;
            article.querySelector('.message__body').textContent = message.body;
            article.querySelector('time').textContent = new Date(message.created_at.replace(' ', 'T')).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
            list.appendChild(article);
            list.scrollTop = list.scrollHeight;
            lastId = Math.max(lastId, Number(message.id));
        };
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const textarea = form.querySelector('textarea');
            const body = textarea.value.trim();
            if (!body) return;
            try {
                await submit(form.querySelector('[data-submit]'), async () => {
                    await post('/chat/send', { message: body });
                    textarea.value = '';
                    toast('Сообщение отправлено', 'success');
                    window.location.reload();
                });
            } catch (error) {
                textarea.focus();
            }
        });
        window.setInterval(async () => {
            try {
                const data = await get('/chat/poll?since=' + lastId);
                (data.messages || []).forEach(render);
            } catch (error) { /* следующий цикл повторит запрос */ }
        }, 10000);
    }

    initModals();
    initToasts();
    initChat();
    window.Kayford = { api, post, get, url, toast, csrf, submit };
})();
