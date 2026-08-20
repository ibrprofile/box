(function () {
    'use strict';
    const editor = document.getElementById('task-statement-editor');
    const textarea = document.getElementById('task-statement');
    const solutionEditor = document.getElementById('task-solution-editor');
    const solutionTextarea = document.getElementById('task-solution');

    function sync(editorEl, textEl) {
        if (editorEl && textEl) {
            textEl.value = editorEl.innerHTML;
        }
    }

    function bind(editorEl, textEl) {
        if (!editorEl || !textEl) return;
        editorEl.addEventListener('input', () => sync(editorEl, textEl));
        textEl.form?.addEventListener('submit', () => sync(editorEl, textEl));
    }

    bind(editor, textarea);
    bind(solutionEditor, solutionTextarea);

    document.querySelectorAll('[data-editor-command]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const cmd = btn.getAttribute('data-editor-command');
            const value = btn.getAttribute('data-value') || btn.getAttribute('data-link');
            if (cmd === 'createLink') {
                const href = window.prompt('Ссылка', value || 'https://');
                if (href) document.execCommand('createLink', false, href);
                return;
            }
            document.execCommand(cmd, false, value || null);
        });
    });
})();
