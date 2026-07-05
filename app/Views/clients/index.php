<?php
use App\Support\Format;
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Клиенты</h1>
        <p class="page-sub"><?= count($clients) ?> записей</p>
    </div>
    <div class="page-actions">
        <a href="/clients/create" class="btn btn--primary">
            <?= Icon::render('user-plus', 18) ?> Добавить клиента
        </a>
    </div>
</div>

<div class="toolbar">
    <form method="get" action="/clients" class="toolbar__filters">
        <div class="field field--inline">
            <?= Icon::render('search', 16, 'field__icon') ?>
            <input type="search" name="q" class="field__input field__input--icon field__input--sm"
                   placeholder="Поиск клиентов…" value="<?= $e($filters['q'] ?? '') ?>">
        </div>
        <select name="type" class="select select--sm" onchange="this.form.submit()">
            <option value="">Все типы</option>
            <option value="person" <?= ($filters['type'] ?? '') === 'person' ? 'selected' : '' ?>>Физлица</option>
            <option value="company" <?= ($filters['type'] ?? '') === 'company' ? 'selected' : '' ?>>Компании</option>
        </select>
        <select name="status" class="select select--sm" onchange="this.form.submit()">
            <option value="">Все статусы</option>
            <?php foreach (Labels::CLIENT_STATUS as $key => [$label]): ?>
                <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--ghost btn--sm"><?= Icon::render('filter', 16) ?></button>
        <?php if (!empty($filters['type']) || !empty($filters['status']) || !empty($filters['q'])): ?>
            <a href="/clients" class="btn btn--ghost btn--sm"><?= Icon::render('x', 16) ?></a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($clients)): ?>
    <div class="empty-page">
        <?= Icon::render('clients', 48) ?>
        <h2>Клиентов пока нет</h2>
        <p>Добавьте первого клиента или конвертируйте лид</p>
        <a href="/clients/create" class="btn btn--primary">Добавить клиента</a>
    </div>
<?php else: ?>
    <div class="clients-grid">
        <?php foreach ($clients as $c): ?>
            <?php [$slabel, $scolor] = Labels::get(Labels::CLIENT_STATUS, $c['status']); ?>
            <a href="/clients/<?= $c['id'] ?>" class="client-card">
                <div class="client-card__head">
                    <span class="avatar avatar--lg" style="--seed: <?= ['#3ecf8e','#6366f1','#f59e0b','#0ea5e9','#8b5cf6','#ec4899'][$c['id'] % 6] ?>">
                        <?= mb_substr($c['name'], 0, 1) ?>
                    </span>
                    <div>
                        <div class="client-card__name"><?= $e($c['name']) ?></div>
                        <?php if ($c['type'] === 'company'): ?>
                            <span class="client-type-badge"><?= Icon::render('building', 12) ?> Компания</span>
                        <?php else: ?>
                            <span class="client-type-badge"><?= Icon::render('user', 12) ?> Физлицо</span>
                        <?php endif; ?>
                    </div>
                    <?= Labels::badge(Labels::CLIENT_STATUS, $c['status']) ?>
                </div>
                <?php if ($c['company_name']): ?>
                    <div class="client-card__company"><?= Icon::render('building', 12) ?> <?= $e($c['company_name']) ?></div>
                <?php endif; ?>
                <div class="client-card__contacts">
                    <?php if ($c['phone']): ?><span><?= Icon::render('phone', 12) ?> <?= $e($c['phone']) ?></span><?php endif; ?>
                    <?php if ($c['city']): ?><span><?= Icon::render('map-pin', 12) ?> <?= $e($c['city']) ?></span><?php endif; ?>
                </div>
                <div class="client-card__stats">
                    <span><?= $c['orders_count'] ?> зак.</span>
                    <span><?= Format::compact((float)$c['revenue']) ?> ₽</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
