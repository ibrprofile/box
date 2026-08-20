<?php /** @var array|null $task @var array $subjects */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title"><?= $task ? 'Редактирование задачи' : 'Новая задача' ?></h1>
        <p class="page-sub">Сохраняйте текст, решение, варианты ответа и формулы — всё в одном редакторе.</p>
    </div>
</section>

<form class="stack" method="post" action="<?= $task ? url('/admin/tasks/' . $task['id']) : url('/admin/tasks') ?>">
    <?= csrf_field() ?>
    <div class="card card--pad stack">
        <div class="editor-toolbar" role="toolbar" aria-label="Форматирование текста">
            <button type="button" class="btn btn--ghost btn--sm" data-editor-command="bold"><b>Ж</b></button>
            <button type="button" class="btn btn--ghost btn--sm" data-editor-command="italic"><i>К</i></button>
            <button type="button" class="btn btn--ghost btn--sm" data-editor-command="insertUnorderedList">• Список</button>
            <button type="button" class="btn btn--ghost btn--sm" data-editor-command="formatBlock" data-value="h3">Заголовок</button>
            <button type="button" class="btn btn--ghost btn--sm" data-editor-command="createLink" data-link="https://example.com">Ссылка</button>
        </div>
        <div class="grid-2">
            <div class="field">
                <label>Предмет</label>
                <select class="input select" name="subject_id" required>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= ($task && (int) $task['subject_id'] === (int) $s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Тема (по желанию)</label>
                <input class="input" name="topic_id" value="<?= e((string) ($task['topic_id'] ?? '')) ?>" placeholder="ID темы">
            </div>
        </div>
        <div class="field">
            <label>Название</label>
            <input class="input" name="title" value="<?= e((string) ($task['title'] ?? '')) ?>" required>
        </div>
        <div class="field">
            <label>Условие</label>
            <div class="editor" contenteditable="true" id="task-statement-editor"><?= $task ? $task['statement'] : '' ?></div>
            <textarea class="textarea hide" name="statement" id="task-statement" rows="8" required><?= e((string) ($task['statement'] ?? '')) ?></textarea>
        </div>
        <div class="grid-2">
            <div class="field">
                <label>Тип ответа</label>
                <select class="input select" name="answer_type">
                    <option value="text" <?= (!$task || $task['answer_type'] === 'text') ? 'selected' : '' ?>>Текст</option>
                    <option value="choice" <?= ($task && $task['answer_type'] === 'choice') ? 'selected' : '' ?>>Выбор из вариантов</option>
                </select>
            </div>
            <div class="field">
                <label>Сложность</label>
                <input class="input" type="number" name="difficulty" min="1" max="5" value="<?= e((string) ($task['difficulty'] ?? 2)) ?>">
            </div>
        </div>
        <div class="field">
            <label>Правильный ответ</label>
            <input class="input" name="correct_answer" value="<?= e((string) ($task['correct_answer'] ?? '')) ?>" required>
        </div>
        <div class="field">
            <label>Варианты ответа (для выбора)</label>
            <textarea class="textarea" name="options" rows="4" placeholder="Один вариант на строку"><?= e((string) ($task['options'] ?? '')) ?></textarea>
        </div>
        <div class="field">
            <label>Разбор</label>
            <div class="editor" contenteditable="true" id="task-solution-editor"><?= $task ? ($task['solution'] ?? '') : '' ?></div>
            <textarea class="textarea hide" name="solution" id="task-solution" rows="6" placeholder="Подробное решение и объяснение"><?= e((string) ($task['solution'] ?? '')) ?></textarea>
        </div>
        <div class="grid-2">
            <div class="field">
                <label>XP за решение</label>
                <input class="input" type="number" name="xp_reward" min="5" max="100" value="<?= e((string) ($task['xp_reward'] ?? 10)) ?>">
            </div>
            <div class="field">
                <label>Опубликовано</label>
                <select class="input select" name="is_active">
                    <option value="1" <?= (!$task || (int) $task['is_active'] === 1) ? 'selected' : '' ?>>Да</option>
                    <option value="0" <?= ($task && (int) $task['is_active'] === 0) ? 'selected' : '' ?>>Нет</option>
                </select>
            </div>
        </div>
        <button class="btn btn--primary" type="submit">Сохранить</button>
    </div>
</form>

<script src="<?= asset('js/admin-editor.js') ?>" defer></script>
