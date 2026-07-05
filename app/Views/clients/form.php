<?php
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$val = fn(string $key, $default = '') => $e($client[$key] ?? $default);
$isEdit = $client !== null;
$type   = $val('type', 'person');
?>

<div class="page-header">
    <div class="page-header__back">
        <a href="/clients" class="back-link"><?= Icon::render('arrow-left', 18) ?> Клиенты</a>
        <h1 class="page-title"><?= $isEdit ? 'Редактировать клиента' : 'Новый клиент' ?></h1>
    </div>
</div>

<form method="post" action="<?= $isEdit ? '/clients/' . $client['id'] : '/clients' ?>" class="form-layout" novalidate>
    <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">

    <div class="form-layout__main">
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Тип</h3>
                <div class="type-toggle" data-role="type-toggle">
                    <label class="type-toggle__option">
                        <input type="radio" name="type" value="person" <?= $type === 'person' ? 'checked' : '' ?>>
                        <span><?= Icon::render('user', 16) ?> Физическое лицо</span>
                    </label>
                    <label class="type-toggle__option">
                        <input type="radio" name="type" value="company" <?= $type === 'company' ? 'checked' : '' ?>>
                        <span><?= Icon::render('building', 16) ?> Компания</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Основная информация</h3>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="name">Имя / Название <span class="req">*</span></label>
                        <input type="text" id="name" name="name" class="field__input" value="<?= $val('name') ?>" required>
                    </div>
                    <div class="field" data-show-when="company">
                        <label class="field__label" for="legal_name">Юридическое название</label>
                        <input type="text" id="legal_name" name="legal_name" class="field__input" value="<?= $val('legal_name') ?>">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field" data-show-when="person">
                        <label class="field__label" for="position">Должность</label>
                        <input type="text" id="position" name="position" class="field__input" value="<?= $val('position') ?>">
                    </div>
                    <div class="field" data-show-when="company">
                        <label class="field__label" for="inn">ИНН / РНН</label>
                        <input type="text" id="inn" name="inn" class="field__input" value="<?= $val('inn') ?>">
                    </div>
                    <div class="field" data-show-when="company">
                        <label class="field__label" for="industry">Отрасль</label>
                        <input type="text" id="industry" name="industry" class="field__input" value="<?= $val('industry') ?>" placeholder="IT, Ритейл, Строительство…">
                    </div>
                </div>
                <?php if ($isEdit && $client['type'] === 'person' || !$isEdit): ?>
                <div class="field" data-show-when="person">
                    <label class="field__label" for="company_id">Компания</label>
                    <select id="company_id" name="company_id" class="select">
                        <option value="">— Без компании —</option>
                        <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['id'] ?>" <?= (int)($client['company_id'] ?? 0) === (int)$comp['id'] ? 'selected' : '' ?>><?= $e($comp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Контакты</h3>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" class="field__input" value="<?= $val('phone') ?>">
                    </div>
                    <div class="field">
                        <label class="field__label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="field__input" value="<?= $val('email') ?>">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="telegram">Telegram</label>
                        <input type="text" id="telegram" name="telegram" class="field__input" value="<?= $val('telegram') ?>" placeholder="@username">
                    </div>
                    <div class="field">
                        <label class="field__label" for="whatsapp">WhatsApp</label>
                        <input type="tel" id="whatsapp" name="whatsapp" class="field__input" value="<?= $val('whatsapp') ?>">
                    </div>
                    <div class="field">
                        <label class="field__label" for="website">Сайт</label>
                        <input type="url" id="website" name="website" class="field__input" value="<?= $val('website') ?>" placeholder="https://…">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Местоположение</h3>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="country">Страна</label>
                        <input type="text" id="country" name="country" class="field__input" value="<?= $val('country') ?>" placeholder="Россия, Казахстан…">
                    </div>
                    <div class="field">
                        <label class="field__label" for="city">Город</label>
                        <input type="text" id="city" name="city" class="field__input" value="<?= $val('city') ?>">
                    </div>
                </div>
                <div class="field">
                    <label class="field__label" for="address">Адрес</label>
                    <input type="text" id="address" name="address" class="field__input" value="<?= $val('address') ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Дополнительно</h3>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="status">Статус</label>
                        <select id="status" name="status" class="select">
                            <?php foreach (Labels::CLIENT_STATUS as $key => [$label]): ?>
                                <option value="<?= $key ?>" <?= $val('status', 'lead') === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="field__label" for="source">Источник</label>
                        <input type="text" id="source" name="source" class="field__input" value="<?= $val('source') ?>" placeholder="Реферал, 2ГИС, Яндекс…">
                    </div>
                </div>
                <div class="field">
                    <label class="field__label" for="notes">Заметки</label>
                    <textarea id="notes" name="notes" class="field__input field__input--textarea" rows="3"><?= $val('notes') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-layout__side">
        <div class="card">
            <div class="card__section">
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary btn--full">
                        <?= $isEdit ? 'Сохранить' : 'Создать клиента' ?>
                    </button>
                    <a href="<?= $isEdit ? '/clients/' . $client['id'] : '/clients' ?>" class="btn btn--ghost btn--full">Отмена</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function() {
    const radios    = document.querySelectorAll('[data-role="type-toggle"] input[type="radio"]');
    const personFields  = document.querySelectorAll('[data-show-when="person"]');
    const companyFields = document.querySelectorAll('[data-show-when="company"]');

    function update(type) {
        personFields.forEach(el => el.style.display = type === 'person' ? '' : 'none');
        companyFields.forEach(el => el.style.display = type === 'company' ? '' : 'none');
    }
    radios.forEach(r => {
        r.addEventListener('change', () => update(r.value));
        if (r.checked) update(r.value);
    });
})();
</script>
