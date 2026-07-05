<div class="cabinet-card">
    <div class="cabinet-brand">
        <span class="cabinet-brand__mark">AA7</span>
        <div>
            <div class="cabinet-brand__name">Личный кабинет</div>
            <div class="cabinet-brand__sub">Войдите чтобы просматривать заказы</div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background:var(--red-dim);border:1px solid rgba(248,113,113,.25);border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;color:var(--red);margin-bottom:18px">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/cabinet/login" class="auth-form">
        <input type="hidden" name="_csrf" value="<?= $_csrf ?>">
        <div class="field">
            <label class="field__label">Логин</label>
            <input type="text" name="login" class="field__input field__input--lg"
                   placeholder="Ваш логин" autocomplete="username" required autofocus>
        </div>
        <div class="field">
            <label class="field__label">Пароль</label>
            <div class="field__wrap">
                <input type="password" name="password" class="field__input field__input--lg"
                       placeholder="••••••••" autocomplete="current-password" required>
                <button type="button" class="field__toggle-pw" data-role="toggle-pw" style="top:50%;transform:translateY(-50%)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn--primary btn--full btn--lg" style="margin-top:4px">
            Войти
        </button>
    </form>
</div>
