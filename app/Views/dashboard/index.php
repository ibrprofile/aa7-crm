<?php
use App\Support\Format;
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Дашборд</h1>
        <p class="page-sub">Обзор на <?= date('d F Y') ?></p>
    </div>
    <div class="page-actions">
        <a href="/orders/create" class="btn btn--primary">
            <?= Icon::render('plus', 18) ?>
            Новый заказ
        </a>
        <a href="/clients/create" class="btn btn--ghost">
            <?= Icon::render('user-plus', 18) ?>
            Клиент
        </a>
    </div>
</div>

<!-- KPI метрики -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--green"><?= Icon::render('coins', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Выручка за месяц</span>
            <span class="kpi-card__value"><?= Format::money($overview['revenue_month']) ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--blue"><?= Icon::render('briefcase', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Активных заказов</span>
            <span class="kpi-card__value"><?= $overview['orders_active'] ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--purple"><?= Icon::render('wallet', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">В пайплайне</span>
            <span class="kpi-card__value"><?= Format::money($overview['pipeline']) ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--amber"><?= Icon::render('clients', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Клиентов</span>
            <span class="kpi-card__value"><?= $overview['clients_total'] ?></span>
        </div>
    </div>
    <?php if ($overview['overdue'] > 0): ?>
    <div class="kpi-card kpi-card--warn">
        <div class="kpi-card__icon kpi-card__icon--red"><?= Icon::render('clock', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Просрочено</span>
            <span class="kpi-card__value"><?= $overview['overdue'] ?></span>
        </div>
    </div>
    <?php endif; ?>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--teal"><?= Icon::render('target', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Конверсия лидов</span>
            <span class="kpi-card__value"><?= $conversion['rate'] ?>%</span>
        </div>
    </div>
</div>

<div class="dash-grid">
    <!-- График выручки -->
    <div class="card dash-grid__chart">
        <div class="card__head">
            <span class="card__title">Выручка по месяцам</span>
            <a href="/statistics" class="card__link">Подробнее <?= Icon::render('chevron-right', 16) ?></a>
        </div>
        <div class="chart-wrap">
            <canvas id="revenueChart" height="180"
                data-labels="<?= $e(implode(',', array_column($byMonth, 'bucket'))) ?>"
                data-values="<?= $e(implode(',', array_column($byMonth, 'total'))) ?>">
            </canvas>
        </div>
    </div>

    <!-- Статусы заказов -->
    <div class="card dash-grid__donut">
        <div class="card__head">
            <span class="card__title">Заказы по статусу</span>
        </div>
        <?php
        $statusData = [];
        foreach ($byStatus as $s) { $statusData[] = $s['total']; }
        $statusLabels = [];
        foreach ($byStatus as $s) { $statusLabels[] = Labels::ORDER_STATUS[$s['status']][0] ?? $s['status']; }
        ?>
        <div class="donut-wrap">
            <canvas id="statusChart" height="180"
                data-labels="<?= $e(implode('|', $statusLabels)) ?>"
                data-values="<?= $e(implode(',', $statusData)) ?>">
            </canvas>
        </div>
        <div class="status-legend">
            <?php foreach ($byStatus as $s): ?>
                <?php [$label, $color] = Labels::get(Labels::ORDER_STATUS, $s['status']); ?>
                <div class="legend-item">
                    <span class="legend-dot badge--<?= $e($color) ?>"></span>
                    <span class="legend-label"><?= $e($label) ?></span>
                    <span class="legend-val"><?= $s['total'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Последние заказы -->
    <div class="card dash-grid__orders">
        <div class="card__head">
            <span class="card__title">Последние заказы</span>
            <a href="/orders" class="card__link">Все заказы <?= Icon::render('chevron-right', 16) ?></a>
        </div>
        <?php if (empty($recent)): ?>
            <div class="empty-state">
                <?= Icon::render('briefcase', 36) ?>
                <p>Заказов пока нет</p>
                <a href="/orders/create" class="btn btn--primary btn--sm">Создать первый</a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($recent as $o): ?>
                    <?php [$slabel, $scolor] = Labels::get(Labels::ORDER_STATUS, $o['status']); ?>
                    <?php [$plabel, $pcolor] = Labels::get(Labels::PAYMENT_STATUS, $o['payment_status']); ?>
                    <a href="/orders/<?= $o['id'] ?>" class="order-row">
                        <div class="order-row__main">
                            <span class="order-row__num"><?= $e($o['number']) ?></span>
                            <span class="order-row__title"><?= $e($o['title']) ?></span>
                            <?php if ($o['client_name']): ?>
                                <span class="order-row__client"><?= $e($o['client_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="order-row__meta">
                            <span class="badge badge--<?= $scolor ?>"><?= $e($slabel) ?></span>
                            <span class="order-row__amount"><?= Format::money($o['amount_rub']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Топ клиенты -->
    <div class="card dash-grid__clients">
        <div class="card__head">
            <span class="card__title">Топ клиенты</span>
            <a href="/clients" class="card__link">Все <?= Icon::render('chevron-right', 16) ?></a>
        </div>
        <?php if (empty($topClients)): ?>
            <div class="empty-state"><p>Данных пока нет</p></div>
        <?php else: ?>
            <div class="client-rank">
                <?php foreach ($topClients as $i => $c): ?>
                    <a href="/clients/<?= $c['id'] ?>" class="rank-row">
                        <span class="rank-num"><?= $i + 1 ?></span>
                        <span class="rank-name"><?= $e($c['name']) ?></span>
                        <span class="rank-meta"><?= $c['orders_count'] ?> зак.</span>
                        <span class="rank-amount"><?= Format::compact($c['revenue']) ?> ₽</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Последние платежи -->
    <div class="card dash-grid__payments">
        <div class="card__head">
            <span class="card__title">Последние платежи</span>
            <a href="/statistics" class="card__link">Отчёт <?= Icon::render('chevron-right', 16) ?></a>
        </div>
        <?php if (empty($payments)): ?>
            <div class="empty-state"><p>Платежей пока нет</p></div>
        <?php else: ?>
            <div class="pay-list">
                <?php foreach ($payments as $p): ?>
                    <div class="pay-row">
                        <div class="pay-row__info">
                            <span class="pay-row__order"><?= $e($p['order_number'] ?? '—') ?></span>
                            <span class="pay-row__client"><?= $e($p['client_name'] ?? '—') ?></span>
                        </div>
                        <div class="pay-row__right">
                            <span class="pay-row__amount"><?= Format::money($p['amount_rub']) ?></span>
                            <span class="pay-row__date"><?= Format::ago($p['paid_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Лента активности -->
    <div class="card dash-grid__activity">
        <div class="card__head">
            <span class="card__title">Активность</span>
            <a href="/team/logs" class="card__link">Полный лог <?= Icon::render('chevron-right', 16) ?></a>
        </div>
        <div class="activity-feed">
            <?php foreach ($activity as $log): ?>
                <div class="activity-item">
                    <span class="avatar avatar--sm" style="--seed: <?= $e($log['avatar_color'] ?? '#3ecf8e') ?>">
                        <?= mb_substr($log['user_name'] ?? 'S', 0, 1) ?>
                    </span>
                    <div class="activity-item__body">
                        <span class="activity-item__who"><?= $e($log['user_name'] ?? 'Система') ?></span>
                        <span class="activity-item__text"><?= $e($log['description'] ?? $log['action']) ?></span>
                    </div>
                    <span class="activity-item__time"><?= Format::ago($log['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
