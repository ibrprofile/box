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
            const target = document.activeElement?.classList.contains('editor') ? document.activeElement : editor;
            target?.focus();
            if (cmd === 'createLink') {
                const href = window.prompt('Ссылка', value || 'https://');
                if (href) document.execCommand('createLink', false, href);
            } else if (cmd === 'insertImage') {
                const src = window.prompt('URL изображения', 'https://');
                if (src) document.execCommand('insertImage', false, src);
            } else if (cmd === 'insertTable') {
                document.execCommand('insertHTML', false, '<table><tbody><tr><td>Ячейка</td><td>Ячейка</td></tr><tr><td>Ячейка</td><td>Ячейка</td></tr></tbody></table>');
            } else if (cmd === 'insertFormula') {
                const formula = window.prompt('Формула LaTeX', 'x^2 + y^2 = z^2');
                if (formula) document.execCommand('insertHTML', false, '<span class="formula">\\(' + formula.replace(/[<>]/g, '') + '\\)</span>');
            } else {
                document.execCommand(cmd, false, value || null);
            }
        });
    });

    [editor, solutionEditor].forEach((target) => {
        target?.addEventListener('paste', (event) => {
            const html = event.clipboardData?.getData('text/html');
            if (!html) return;
            event.preventDefault();
            document.execCommand('insertHTML', false, html);
        });
    });

    document.querySelectorAll('.editor').forEach((target) => {
        target.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;
            event.preventDefault();
            const reader = new FileReader();
            reader.onload = () => {
                target.focus();
                document.execCommand('insertImage', false, reader.result);
            };
            reader.readAsDataURL(file);
        });
    });
})();
