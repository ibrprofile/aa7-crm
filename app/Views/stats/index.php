<?php
use App\Support\Format;
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$totalRevenue = (float)($overview['revenue_total'] ?? 0);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Статистика</h1>
        <p class="page-sub">Финансовая аналитика и ключевые метрики</p>
    </div>
    <div class="page-actions">
        <form method="get" action="/statistics" class="period-form">
            <select name="period" class="select select--sm" onchange="this.form.submit()">
                <option value="3" <?= $period == 3 ? 'selected' : '' ?>>3 месяца</option>
                <option value="6" <?= $period == 6 ? 'selected' : '' ?>>6 месяцев</option>
                <option value="12" <?= $period == 12 ? 'selected' : '' ?>>12 месяцев</option>
                <option value="24" <?= $period == 24 ? 'selected' : '' ?>>24 месяца</option>
            </select>
        </form>
    </div>
</div>

<!-- KPI -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--green"><?= Icon::render('coins', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Общая выручка</span>
            <span class="kpi-card__value"><?= Format::money($totalRevenue) ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--green"><?= Icon::render('trend-up', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">За текущий месяц</span>
            <span class="kpi-card__value"><?= Format::money($overview['revenue_month']) ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--amber"><?= Icon::render('wallet', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Дебиторка</span>
            <span class="kpi-card__value"><?= Format::money($overview['receivable']) ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--blue"><?= Icon::render('briefcase', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Заказов всего / активных</span>
            <span class="kpi-card__value"><?= $overview['orders_total'] ?> / <?= $overview['orders_active'] ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--teal"><?= Icon::render('target', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Конверсия лидов</span>
            <span class="kpi-card__value"><?= $conversion['rate'] ?>%</span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon kpi-card__icon--purple"><?= Icon::render('check-circle', 22) ?></div>
        <div class="kpi-card__body">
            <span class="kpi-card__label">Завершено заказов</span>
            <span class="kpi-card__value"><?= $overview['orders_done'] ?></span>
        </div>
    </div>
</div>

<div class="stats-grid">
    <!-- Выручка по месяцам -->
    <div class="card stats-grid__wide">
        <div class="card__head">
            <span class="card__title">Выручка по месяцам (<?= $period ?> мес.)</span>
        </div>
        <?php
        $monthLabels = array_column($byMonth, 'bucket');
        $monthValues = array_column($byMonth, 'total');
        ?>
        <div class="chart-wrap chart-wrap--tall">
            <canvas id="revenueChart" height="240"
                data-labels="<?= $e(implode(',', $monthLabels)) ?>"
                data-values="<?= $e(implode(',', $monthValues)) ?>">
            </canvas>
        </div>
    </div>

    <!-- Статусы заказов -->
    <div class="card">
        <div class="card__head"><span class="card__title">Заказы по статусу</span></div>
        <div class="donut-wrap">
            <?php
            $sLabels = array_map(fn($s) => Labels::ORDER_STATUS[$s['status']][0] ?? $s['status'], $byStatus);
            $sValues = array_column($byStatus, 'total');
            ?>
            <canvas id="statusChart" height="200"
                data-labels="<?= $e(implode('|', $sLabels)) ?>"
                data-values="<?= $e(implode(',', $sValues)) ?>">
            </canvas>
        </div>
        <div class="status-legend">
            <?php foreach ($byStatus as $s): ?>
                <?php [$label, $color] = Labels::get(Labels::ORDER_STATUS, $s['status']); ?>
                <div class="legend-item">
                    <span class="legend-dot badge--<?= $color ?>"></span>
                    <span class="legend-label"><?= $e($label) ?></span>
                    <span class="legend-val"><?= $s['total'] ?></span>
                    <span class="legend-amount"><?= Format::compact((float)$s['amount']) ?> ₽</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Валюты -->
    <div class="card">
        <div class="card__head"><span class="card__title">Разбивка по валютам</span></div>
        <div class="donut-wrap">
            <?php
            $cLabels = array_column($currency, 'currency');
            $cValues = array_column($currency, 'total');
            ?>
            <canvas id="currencyChart" height="200"
                data-labels="<?= $e(implode('|', $cLabels)) ?>"
                data-values="<?= $e(implode(',', $cValues)) ?>">
            </canvas>
        </div>
        <div class="status-legend">
            <?php foreach ($currency as $c): ?>
                <div class="legend-item">
                    <span class="legend-dot"></span>
                    <span class="legend-label"><?= $e($c['currency']) ?></span>
                    <span class="legend-val"><?= $c['total'] ?> зак.</span>
                    <span class="legend-amount"><?= Format::compact((float)$c['amount']) ?> ₽</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Топ клиенты -->
    <div class="card stats-grid__wide">
        <div class="card__head"><span class="card__title">Топ-10 клиентов по выручке</span></div>
        <?php if (empty($topClients)): ?>
            <div class="empty-state"><p>Данных пока нет</p></div>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>#</th><th>Клиент</th><th>Тип</th><th>Заказов</th><th class="th-right">Выручка</th><th>Доля</th></tr></thead>
                <tbody>
                    <?php foreach ($topClients as $i => $c): ?>
                        <tr>
                            <td class="muted"><?= $i + 1 ?></td>
                            <td><a href="/clients/<?= $c['id'] ?>" class="table-link"><?= $e($c['name']) ?></a></td>
                            <td><?= $c['type'] === 'company' ? 'Компания' : 'Физлицо' ?></td>
                            <td><?= $c['orders_count'] ?></td>
                            <td class="td-right mono"><?= Format::money($c['revenue']) ?></td>
                            <td>
                                <?php $pct = $totalRevenue > 0 ? round($c['revenue'] / $totalRevenue * 100, 1) : 0; ?>
                                <div class="bar-cell"><div class="bar-cell__fill" style="width:<?= $pct ?>%"></div><span><?= $pct ?>%</span></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- По странам -->
    <div class="card">
        <div class="card__head"><span class="card__title">Активность по странам</span></div>
        <?php if (empty($byCountry)): ?>
            <div class="empty-state"><p>Нет данных</p></div>
        <?php else: ?>
            <div class="country-list">
                <?php $maxRev = max(array_column($byCountry, 'revenue') ?: [1]); ?>
                <?php foreach ($byCountry as $row): ?>
                    <?php $pct = $maxRev > 0 ? round($row['revenue'] / $maxRev * 100) : 0; ?>
                    <div class="country-row">
                        <span class="country-row__name"><?= $e($row['country']) ?></span>
                        <div class="country-row__bar">
                            <div class="country-row__fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="country-row__val"><?= Format::compact((float)$row['revenue']) ?> ₽</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Команда -->
    <div class="card">
        <div class="card__head"><span class="card__title">Эффективность команды</span></div>
        <?php if (empty($team)): ?>
            <div class="empty-state"><p>Нет данных</p></div>
        <?php else: ?>
            <div class="team-perf">
                <?php foreach ($team as $u): ?>
                    <div class="team-row">
                        <span class="avatar" style="--seed:<?= $e($u['avatar_color'] ?? '#3ecf8e') ?>"><?= mb_substr($u['name'], 0, 1) ?></span>
                        <div class="team-row__info">
                            <span class="team-row__name"><?= $e($u['name']) ?></span>
                            <span class="team-row__stats"><?= $u['orders_count'] ?> зак. · <?= $u['done_count'] ?> завершено</span>
                        </div>
                        <span class="team-row__rev"><?= Format::compact((float)$u['revenue']) ?> ₽</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Последние платежи -->
    <div class="card stats-grid__wide">
        <div class="card__head"><span class="card__title">Последние 20 платежей</span></div>
        <?php if (empty($recentPay)): ?>
            <div class="empty-state"><p>Платежей пока нет</p></div>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Дата</th><th>Заказ</th><th>Клиент</th><th>Метод</th><th class="th-right">Сумма</th></tr></thead>
                <tbody>
                    <?php foreach ($recentPay as $p): ?>
                        <tr>
                            <td><?= Format::date($p['paid_at'], 'd.m.Y') ?></td>
                            <td><?= $p['order_number'] ? '<a href="/orders/'.$p['order_id'].'" class="table-link">'.$e($p['order_number']).'</a>' : '—' ?></td>
                            <td><?= $e($p['client_name'] ?? '—') ?></td>
                            <td><?= $e($p['method'] ?? '—') ?></td>
                            <td class="td-right mono"><?= Format::money($p['amount_rub']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
