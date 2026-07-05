<?php use App\Support\Icon; ?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">AA7</span>
            <span class="brand-label">CRM</span>
        </div>
        <h1 class="auth-title">Вход в систему</h1>
        <p class="auth-sub">Профессиональная платформа управления проектами</p>

        <form method="post" action="/login" class="auth-form" autocomplete="off" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
                <label class="field__label" for="login">Логин</label>
                <div class="field__wrap">
                    <?= Icon::render('user', 18, 'field__icon') ?>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        class="field__input field__input--icon"
                        placeholder="admin"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>
            </div>

            <div class="field">
                <label class="field__label" for="password">Пароль</label>
                <div class="field__wrap">
                    <?= Icon::render('lock', 18, 'field__icon') ?>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="field__input field__input--icon"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="field__toggle-pw" aria-label="Показать пароль" data-role="toggle-pw">
                        <?= Icon::render('eye', 16) ?>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn--primary btn--full">
                Войти
            </button>
        </form>
    </div>
</div>
