<?php
use App\Support\Icon;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$val = fn(string $key, $default = '') => $e($user[$key] ?? $default);
?>

<div class="page-header">
    <h1 class="page-title">Настройки профиля</h1>
</div>

<div class="settings-layout">
    <!-- Профиль -->
    <div class="card">
        <div class="card__section">
            <h3 class="card__section-title">Основная информация</h3>
            <form method="post" action="/settings/profile" class="settings-form" novalidate>
                <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">

                <div class="avatar-picker">
                    <span class="avatar avatar--xl" id="avatar-preview" style="--seed:<?= $val('avatar_color', '#3ecf8e') ?>">
                        <?= mb_substr($user['name'] ?? 'A', 0, 1) ?>
                    </span>
                    <div class="avatar-picker__colors">
                        <?php foreach (['#3ecf8e','#6366f1','#f59e0b','#ef4444','#0ea5e9','#8b5cf6','#ec4899','#14b8a6'] as $c): ?>
                            <button type="button" class="color-swatch <?= $val('avatar_color', '#3ecf8e') === $c ? 'is-active' : '' ?>"
                                    style="background:<?= $c ?>" data-color="<?= $c ?>" data-role="color-swatch"></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="avatar_color" id="avatar_color_input" value="<?= $val('avatar_color', '#3ecf8e') ?>">
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="name">Имя</label>
                        <input type="text" id="name" name="name" class="field__input" value="<?= $val('name') ?>" required>
                    </div>
                    <div class="field">
                        <label class="field__label">Логин</label>
                        <input type="text" class="field__input" value="<?= $val('login') ?>" disabled>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="field__input" value="<?= $val('email') ?>">
                    </div>
                    <div class="field">
                        <label class="field__label" for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" class="field__input" value="<?= $val('phone') ?>">
                    </div>
                </div>
                <div class="field">
                    <span class="field__label">Роль</span>
                    <span class="badge badge--blue"><?= $e($user['role'] ?? 'manager') ?></span>
                </div>
                <button type="submit" class="btn btn--primary">Сохранить профиль</button>
            </form>
        </div>
    </div>

    <!-- Смена пароля -->
    <div class="card">
        <div class="card__section">
            <h3 class="card__section-title">Сменить пароль</h3>
            <form method="post" action="/settings/password" class="settings-form" novalidate>
                <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                <div class="field">
                    <label class="field__label" for="current_password">Текущий пароль</label>
                    <input type="password" id="current_password" name="current_password" class="field__input" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label class="field__label" for="new_password">Новый пароль</label>
                    <input type="password" id="new_password" name="new_password" class="field__input" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field">
                    <label class="field__label" for="confirm_password">Подтвердить пароль</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="field__input" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn--primary">Изменить пароль</button>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-role="color-swatch"]').forEach(swatch => {
    swatch.addEventListener('click', function() {
        document.querySelectorAll('[data-role="color-swatch"]').forEach(s => s.classList.remove('is-active'));
        this.classList.add('is-active');
        const color = this.dataset.color;
        document.getElementById('avatar_color_input').value = color;
        const preview = document.getElementById('avatar-preview');
        preview.style.setProperty('--seed', color);
    });
});
</script>
