<?php
use App\Support\Format;
use App\Support\Icon;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$roles = ['admin' => 'Администратор', 'manager' => 'Менеджер', 'viewer' => 'Наблюдатель'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Команда</h1>
        <p class="page-sub"><?= count($users) ?> сотрудников</p>
    </div>
    <div class="page-actions">
        <a href="/team/logs" class="btn btn--ghost">
            <?= Icon::render('activity', 16) ?> Журнал действий
        </a>
        <button type="button" class="btn btn--primary" data-role="modal-trigger" data-modal="add-user">
            <?= Icon::render('user-plus', 16) ?> Добавить сотрудника
        </button>
    </div>
</div>

<div class="card table-card">
    <table class="table">
        <thead>
            <tr><th>Сотрудник</th><th>Логин</th><th>Роль</th><th>Email</th><th>Последний вход</th><th>Статус</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="user-row">
                            <span class="avatar" style="--seed:<?= $e($u['avatar_color'] ?? '#3ecf8e') ?>"><?= mb_substr($u['name'], 0, 1) ?></span>
                            <span><?= $e($u['name']) ?></span>
                        </div>
                    </td>
                    <td class="mono"><?= $e($u['login']) ?></td>
                    <td>
                        <form method="post" action="/team/<?= $u['id'] ?>/role" class="inline-form--select">
                            <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                            <select name="role" class="select select--sm" onchange="this.form.submit()">
                                <?php foreach ($roles as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $u['role'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><?= $e($u['email'] ?? '—') ?></td>
                    <td><?= $u['last_login_at'] ? Format::ago($u['last_login_at']) : '<span class="muted">никогда</span>' ?></td>
                    <td>
                        <span class="badge badge--<?= $u['is_active'] ? 'green' : 'muted' ?>">
                            <?= $u['is_active'] ? 'Активен' : 'Отключён' ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" action="/team/<?= $u['id'] ?>/toggle">
                            <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                            <input type="hidden" name="active" value="<?= $u['is_active'] ? '0' : '1' ?>">
                            <button type="submit" class="btn btn--ghost btn--sm"
                                    onclick="return confirm('<?= $u['is_active'] ? 'Деактивировать' : 'Активировать' ?> сотрудника?')">
                                <?= $u['is_active'] ? Icon::render('lock', 14).' Отключить' : Icon::render('check', 14).' Включить' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Модальное окно добавления -->
<div class="modal" id="add-user" data-role="modal" style="display:none">
    <div class="modal__backdrop" data-role="modal-close"></div>
    <div class="modal__box">
        <div class="modal__head">
            <h3>Новый сотрудник</h3>
            <button type="button" class="icon-btn" data-role="modal-close"><?= Icon::render('x', 18) ?></button>
        </div>
        <form method="post" action="/team" class="modal__body" novalidate>
            <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
            <div class="field">
                <label class="field__label" for="t-name">Имя</label>
                <input type="text" id="t-name" name="name" class="field__input" required>
            </div>
            <div class="field-row">
                <div class="field">
                    <label class="field__label" for="t-login">Логин <span class="req">*</span></label>
                    <input type="text" id="t-login" name="login" class="field__input" required autocomplete="off">
                </div>
                <div class="field">
                    <label class="field__label" for="t-pass">Пароль <span class="req">*</span></label>
                    <input type="password" id="t-pass" name="password" class="field__input" required minlength="8" autocomplete="new-password">
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label class="field__label" for="t-email">Email</label>
                    <input type="email" id="t-email" name="email" class="field__input">
                </div>
                <div class="field">
                    <label class="field__label" for="t-role">Роль</label>
                    <select id="t-role" name="role" class="select">
                        <option value="manager">Менеджер</option>
                        <option value="admin">Администратор</option>
                        <option value="viewer">Наблюдатель</option>
                    </select>
                </div>
            </div>
            <div class="modal__foot">
                <button type="button" class="btn btn--ghost" data-role="modal-close">Отмена</button>
                <button type="submit" class="btn btn--primary">Создать аккаунт</button>
            </div>
        </form>
    </div>
</div>
