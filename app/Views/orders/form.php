<?php
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$val = fn(string $key, $default = '') => $e($order[$key] ?? $default);
$isEdit = $order !== null;
?>

<div class="page-header">
    <div class="page-header__back">
        <a href="/orders" class="back-link"><?= Icon::render('arrow-left', 18) ?> Заказы</a>
        <h1 class="page-title"><?= $isEdit ? 'Редактировать заказ' : 'Новый заказ' ?></h1>
    </div>
</div>

<form method="post" action="<?= $isEdit ? '/orders/' . $order['id'] : '/orders' ?>" class="form-layout" novalidate>
    <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">

    <div class="form-layout__main">
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Основная информация</h3>
                <div class="field">
                    <label class="field__label" for="title">Название заказа <span class="req">*</span></label>
                    <input type="text" id="title" name="title" class="field__input"
                           value="<?= $val('title') ?>" required placeholder="Разработка сайта для…">
                </div>
                <div class="field">
                    <label class="field__label" for="description">Описание</label>
                    <textarea id="description" name="description" class="field__input field__input--textarea"
                              rows="4" placeholder="Детали проекта, требования…"><?= $val('description') ?></textarea>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="priority">Приоритет</label>
                        <select id="priority" name="priority" class="select">
                            <?php foreach (Labels::PRIORITY as $key => [$label]): ?>
                                <option value="<?= $key ?>" <?= $val('priority', 'normal') === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="field">
                        <label class="field__label" for="status">Статус</label>
                        <select id="status" name="status" class="select">
                            <?php foreach (Labels::ORDER_STATUS as $key => [$label]): ?>
                                <option value="<?= $key ?>" <?= $val('status', 'new') === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="field">
                        <label class="field__label" for="progress">Прогресс, %</label>
                        <input type="number" id="progress" name="progress" class="field__input"
                               value="<?= $val('progress', '0') ?>" min="0" max="100">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="due_at">Дедлайн</label>
                        <input type="date" id="due_at" name="due_at" class="field__input"
                               value="<?= $val('due_at') ?>">
                    </div>
                    <div class="field">
                        <label class="field__label" for="tags">Теги</label>
                        <input type="text" id="tags" name="tags" class="field__input"
                               value="<?= $val('tags') ?>" placeholder="сайт, дизайн, поддержка">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Сумма</h3>
                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="amount_input">Сумма</label>
                        <input type="number" id="amount_input" name="amount_input" class="field__input"
                               value="<?= $val('amount_input', '0') ?>" min="0" step="0.01"
                               data-role="amount-input" placeholder="0">
                    </div>
                    <div class="field field--narrow">
                        <label class="field__label" for="currency">Валюта</label>
                        <select id="currency" name="currency" class="select" data-role="currency-select">
                            <option value="RUB" <?= $val('currency', 'RUB') === 'RUB' ? 'selected' : '' ?>>₽ RUB</option>
                            <option value="USD" <?= $val('currency', 'RUB') === 'USD' ? 'selected' : '' ?>>$ USD</option>
                        </select>
                    </div>
                </div>
                <div class="fx-preview" data-role="fx-preview" <?= $val('currency', 'RUB') === 'RUB' ? 'style="display:none"' : '' ?>>
                    <span class="fx-preview__icon"><?= Icon::render('refresh', 14) ?></span>
                    <span class="fx-preview__text">
                        1 USD = <strong data-role="fx-rate"><?= !empty($rates['USD']) ? $rates['USD'] : '…' ?></strong> ₽
                        &nbsp;→&nbsp;
                        <strong data-role="fx-result">—</strong> ₽
                    </span>
                </div>
                <input type="hidden" name="fx_rate" data-role="fx-rate-input" value="<?= $val('fx_rate', '1') ?>">
                <input type="hidden" name="amount_rub" data-role="fx-rub-input" value="<?= $val('amount_rub', '0') ?>">
            </div>
        </div>
    </div>

    <div class="form-layout__side">
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Клиент</h3>
                <div class="field" data-role="client-selector">
                    <label class="field__label" for="client_id">Выбрать клиента</label>
                    <select id="client_id" name="client_id" class="select" data-role="client-select">
                        <option value="">— Новый клиент —</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int)($order['client_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= $e($c['name']) ?> <?= $c['type'] === 'company' ? '(компания)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="new-client-fields" data-role="new-client-fields" style="display:none">
                    <div class="field">
                        <label class="field__label" for="new_client_name">Имя / компания</label>
                        <input type="text" id="new_client_name" name="new_client_name" class="field__input" placeholder="Иван Иванов">
                    </div>
                    <div class="field">
                        <label class="field__label" for="new_client_phone">Телефон</label>
                        <input type="tel" id="new_client_phone" name="new_client_phone" class="field__input" placeholder="+7 (777) 000-00-00">
                    </div>
                    <div class="field">
                        <label class="field__label" for="new_client_email">Email</label>
                        <input type="email" id="new_client_email" name="new_client_email" class="field__input" placeholder="client@example.com">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__section">
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary btn--full">
                        <?= $isEdit ? 'Сохранить изменения' : 'Создать заказ' ?>
                    </button>
                    <a href="<?= $isEdit ? '/orders/' . $order['id'] : '/orders' ?>" class="btn btn--ghost btn--full">Отмена</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function() {
    const rates = <?= json_encode($rates, JSON_UNESCAPED_UNICODE) ?>;
    const currencySelect = document.querySelector('[data-role="currency-select"]');
    const amountInput    = document.querySelector('[data-role="amount-input"]');
    const fxPreview      = document.querySelector('[data-role="fx-preview"]');
    const fxRateEl       = document.querySelector('[data-role="fx-rate"]');
    const fxResultEl     = document.querySelector('[data-role="fx-result"]');
    const fxRateInput    = document.querySelector('[data-role="fx-rate-input"]');
    const fxRubInput     = document.querySelector('[data-role="fx-rub-input"]');
    const clientSelect   = document.querySelector('[data-role="client-select"]');
    const newFields      = document.querySelector('[data-role="new-client-fields"]');

    function updateFx() {
        const cur = currencySelect.value;
        if (cur === 'RUB') {
            fxPreview.style.display = 'none';
            const v = parseFloat(amountInput.value) || 0;
            fxRateInput.value = '1';
            fxRubInput.value  = v.toFixed(2);
            return;
        }
        const rate = rates[cur] || 1;
        fxPreview.style.display = '';
        fxRateEl.textContent = rate.toFixed(2);
        const v = parseFloat(amountInput.value) || 0;
        const rub = (v * rate).toFixed(2);
        fxResultEl.textContent = parseFloat(rub).toLocaleString('ru-RU');
        fxRateInput.value = rate;
        fxRubInput.value  = rub;
    }

    if (currencySelect) { currencySelect.addEventListener('change', updateFx); }
    if (amountInput)    { amountInput.addEventListener('input', updateFx); updateFx(); }

    if (clientSelect) {
        clientSelect.addEventListener('change', function() {
            newFields.style.display = this.value === '' ? '' : 'none';
        });
    }
})();
</script>
