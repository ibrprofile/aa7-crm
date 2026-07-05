<?php
use App\Support\Format;
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$statuses = ['', 'new', 'negotiation', 'in_progress', 'review', 'done', 'cancelled'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Заказы</h1>
        <p class="page-sub"><?= count($orders) ?> <?= \App\Support\Format::plural(count($orders), 'заказ', 'заказа', 'заказов') ?></p>
    </div>
    <div class="page-actions">
        <a href="/orders/create" class="btn btn--primary">
            <?= Icon::render('plus', 18) ?> Новый заказ
        </a>
    </div>
</div>

<div class="toolbar">
    <form method="get" action="/orders" class="toolbar__filters">
        <div class="field field--inline">
            <?= Icon::render('search', 16, 'field__icon') ?>
            <input type="search" name="q" class="field__input field__input--icon field__input--sm"
                   placeholder="Поиск по заказам…" value="<?= $e($filters['q'] ?? '') ?>">
        </div>
        <select name="status" class="select select--sm" onchange="this.form.submit()">
            <option value="">Все статусы</option>
            <?php foreach (Labels::ORDER_STATUS as $key => [$label]): ?>
                <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--ghost btn--sm"><?= Icon::render('filter', 16) ?> Фильтр</button>
        <?php if (!empty($filters['status']) || !empty($filters['q'])): ?>
            <a href="/orders" class="btn btn--ghost btn--sm"><?= Icon::render('x', 16) ?> Сбросить</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-page">
        <?= Icon::render('briefcase', 48) ?>
        <h2>Заказов пока нет</h2>
        <p>Создайте первый заказ чтобы начать работу</p>
        <a href="/orders/create" class="btn btn--primary">Создать заказ</a>
    </div>
<?php else: ?>
    <div class="card table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Номер</th>
                    <th>Название</th>
                    <th>Клиент</th>
                    <th>Статус</th>
                    <th>Оплата</th>
                    <th class="th-right">Сумма</th>
                    <th>Срок</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <?php [$sl, $sc] = Labels::get(Labels::ORDER_STATUS, $o['status']); ?>
                    <?php [$pl, $pc] = Labels::get(Labels::PAYMENT_STATUS, $o['payment_status']); ?>
                    <?php $overdue = $o['due_at'] && strtotime($o['due_at']) < time() && !in_array($o['status'], ['done','cancelled']); ?>
                    <tr class="table-row<?= $overdue ? ' table-row--warn' : '' ?>" data-order-id="<?= $o['id'] ?>" style="cursor:pointer">
                        <td><span class="mono"><?= $e($o['number']) ?></span></td>
                        <td>
                            <span class="table-link" data-panel><?= $e($o['title']) ?></span>
                            <?php if ($o['tags']): ?>
                                <div class="tag-list">
                                    <?php foreach (explode(',', $o['tags']) as $tag): ?>
                                        <span class="tag"><?= $e(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= $o['client_name'] ? '<a href="/clients/'.(int)$o['client_id'].'" class="table-link">'.$e($o['client_name']).'</a>' : '<span class="muted">—</span>' ?></td>
                        <td><?= Labels::badge(Labels::ORDER_STATUS, $o['status']) ?></td>
                        <td><?= Labels::badge(Labels::PAYMENT_STATUS, $o['payment_status']) ?></td>
                        <td class="td-right mono"><?= Format::money($o['amount_rub']) ?></td>
                        <td><?php if ($o['due_at']): ?>
                            <span class="<?= $overdue ? 'text-red' : '' ?>"><?= Format::date($o['due_at']) ?></span>
                        <?php else: ?><span class="muted">—</span><?php endif; ?></td>
                        <td><a href="/orders/<?= $o['id'] ?>" class="icon-btn" title="Открыть"><?= Icon::render('eye', 16) ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
