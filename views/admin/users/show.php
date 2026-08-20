<?php /** @var array $user */ ?>
<section class="page-head">
    <div>
        <h1 class="page-title">Профиль пользователя</h1>
        <p class="page-sub">Изменяй роль, статус и класс.</p>
    </div>
</section>

<form class="card card--pad stack" method="post" action="<?= url('/admin/users/' . $user['id']) ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <div class="field">
            <label>Имя</label>
            <input class="input" name="first_name" value="<?= e((string) $user['first_name']) ?>">
        </div>
        <div class="field">
            <label>Фамилия</label>
            <input class="input" name="last_name" value="<?= e((string) $user['last_name']) ?>">
        </div>
    </div>
    <div class="grid-2">
        <div class="field">
            <label>Класс</label>
            <input class="input" type="number" name="grade" value="<?= e((string) ($user['grade'] ?? '')) ?>">
        </div>
        <div class="field">
            <label>Роль</label>
            <select class="input select" name="role">
                <option value="student" <?= ($user['role'] === 'student') ? 'selected' : '' ?>>student</option>
                <option value="manager" <?= ($user['role'] === 'manager') ? 'selected' : '' ?>>manager</option>
                <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>admin</option>
                <option value="superadmin" <?= ($user['role'] === 'superadmin') ? 'selected' : '' ?>>superadmin</option>
            </select>
        </div>
    </div>
    <div class="field">
        <label>Статус</label>
        <select class="input select" name="status">
            <option value="active" <?= ($user['status'] === 'active') ? 'selected' : '' ?>>active</option>
            <option value="blocked" <?= ($user['status'] === 'blocked') ? 'selected' : '' ?>>blocked</option>
        </select>
    </div>
    <button class="btn btn--primary" type="submit">Сохранить</button>
</form>
