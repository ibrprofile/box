(function () {
  'use strict';

  const root = document.querySelector('[data-auth]');
  if (!root) return;

  const panes = root.querySelectorAll('[data-pane]');
  const tabs  = root.querySelectorAll('[data-tab]');

  function activateTab(name) {
    tabs.forEach((t) => {
      const on = t.dataset.tab === name;
      t.classList.toggle('is-active', on);
      t.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    panes.forEach((p) => p.classList.toggle('hide', p.dataset.pane !== name));
    ['login-start', 'login-code', 'register-start', 'register-code'].forEach((k) => {
      const f = root.querySelector(`[data-form="${k}"]`);
      if (!f) return;
      if (k.endsWith('-code')) f.classList.add('hide');
      else f.classList.remove('hide');
    });
  }

  tabs.forEach((t) => t.addEventListener('click', () => activateTab(t.dataset.tab)));

  function showCodeForm(flow, mask) {
    const start = root.querySelector(`[data-form="${flow}-start"]`);
    const code  = root.querySelector(`[data-form="${flow}-code"]`);
    if (!start || !code) return;
    start.classList.add('hide');
    code.classList.remove('hide');
    const maskEl = code.querySelector('[data-email-mask]');
    if (maskEl) maskEl.textContent = 'На ' + mask;
    const input = code.querySelector('input[name="code"]');
    if (input) {
      setTimeout(() => input.focus(), 50);
    }
  }

  async function requestCode(flow) {
    const form = root.querySelector(`[data-form="${flow}-start"]`);
    if (!form) return;
    const btn = form.querySelector('button[type="submit"]');
    const payload = { purpose: flow === 'login' ? 'login' : 'register' };

    if (flow === 'register') {
      payload.first_name = form.first_name.value.trim();
      payload.last_name  = form.last_name.value.trim();
      payload.grade      = form.grade.value;
      payload.email      = form.email.value.trim();
    } else {
      payload.email = form.email.value.trim();
    }

    await Kayford.submit(btn, async () => {
      const res = await Kayford.post('/auth/email/request', payload);
      showCodeForm(flow, res.email_mask);
    });
  }

  async function verifyCode(flow) {
    const form = root.querySelector(`[data-form="${flow}-code"]`);
    if (!form) return;
    const btn = form.querySelector('button[type="submit"]');
    const code = form.code.value.trim();

    await Kayford.submit(btn, async () => {
      const done = await Kayford.post('/auth/email/verify', { code });
      window.location.href = done.redirect;
    });
  }

  async function resendCode(flow) {
    const start = root.querySelector(`[data-form="${flow}-start"]`);
    const payload = { purpose: flow };
    if (flow === 'register') {
      payload.first_name = start.first_name.value.trim();
      payload.last_name  = start.last_name.value.trim();
      payload.grade      = start.grade.value;
      payload.email      = start.email.value.trim();
    } else {
      payload.email = start.email.value.trim();
    }
    const btn = root.querySelector(`[data-action="resend-${flow}"]`);
    await Kayford.submit(btn, async () => {
      const res = await Kayford.post('/auth/email/request', payload);
      Kayford.toast('Код отправлен повторно', 'info');
      const mask = root.querySelector(`[data-form="${flow}-code"] [data-email-mask]`);
      if (mask) mask.textContent = 'На ' + res.email_mask;
    });
  }

  root.querySelector('[data-form="login-start"]').addEventListener('submit', (e) => {
    e.preventDefault();
    requestCode('login');
  });
  root.querySelector('[data-form="login-code"]').addEventListener('submit', (e) => {
    e.preventDefault();
    verifyCode('login');
  });
  root.querySelector('[data-form="register-start"]').addEventListener('submit', (e) => {
    e.preventDefault();
    requestCode('register');
  });
  root.querySelector('[data-form="register-code"]').addEventListener('submit', (e) => {
    e.preventDefault();
    verifyCode('register');
  });

  root.querySelector('[data-back="login"]').addEventListener('click', () => {
    root.querySelector('[data-form="login-code"]').classList.add('hide');
    root.querySelector('[data-form="login-start"]').classList.remove('hide');
  });
  root.querySelector('[data-back="register"]').addEventListener('click', () => {
    root.querySelector('[data-form="register-code"]').classList.add('hide');
    root.querySelector('[data-form="register-start"]').classList.remove('hide');
  });

  root.querySelector('[data-action="resend-login"]').addEventListener('click', () => resendCode('login'));
  root.querySelector('[data-action="resend-register"]').addEventListener('click', () => resendCode('register'));

  root.querySelectorAll('input.input--code').forEach((el) => {
    el.addEventListener('input', () => {
      el.value = el.value.replace(/\D/g, '').slice(0, 6);
    });
    el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') e.preventDefault();
    });
  });
})();
