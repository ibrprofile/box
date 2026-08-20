/* Кайфорд — общие клиентские утилиты */
(function () {
    'use strict';

    const base = (window.KAYFORD && window.KAYFORD.base) || '';
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

    window.Kayford = { api, post, get, url, toast, csrf, submit };
})();
