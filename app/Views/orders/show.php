<?php
use App\Core\Auth;
use App\Support\Format;
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
[$slabel, $scolor] = Labels::get(Labels::ORDER_STATUS, $order['status']);
[$plabel, $pcolor] = Labels::get(Labels::PAYMENT_STATUS, $order['payment_status']);
[$prlabel, $prcolor] = Labels::get(Labels::PRIORITY, $order['priority']);
$paid    = (float)($order['paid_rub'] ?? 0);
$total   = (float)($order['amount_rub'] ?? 0);
$progress = (int)($order['progress'] ?? 0);
$debt    = max(0, $total - $paid);
?>

<div class="page-header">
    <div class="page-header__back">
        <a href="/orders" class="back-link"><?= Icon::render('arrow-left', 18) ?> Заказы</a>
        <h1 class="page-title">
            <span class="mono"><?= $e($order['number']) ?></span>
            <?= $e($order['title']) ?>
        </h1>
    </div>
    <div class="page-actions">
        <a href="/orders/<?= $order['id'] ?>/edit" class="btn btn--ghost">
            <?= Icon::render('edit', 16) ?> Редактировать
        </a>
    </div>
</div>

<div class="order-layout">
    <div class="order-layout__main">
        <!-- Карточка заказа -->
        <div class="card">
            <div class="card__section">
                <div class="order-meta-row">
                    <?= Labels::badge(Labels::ORDER_STATUS, $order['status']) ?>
                    <?= Labels::badge(Labels::PRIORITY, $order['priority']) ?>
                    <?= Labels::badge(Labels::PAYMENT_STATUS, $order['payment_status']) ?>
                    <?php if ($order['due_at']): ?>
                        <?php $overdue = strtotime($order['due_at']) < time() && !in_array($order['status'], ['done','cancelled']); ?>
                        <span class="badge badge--<?= $overdue ? 'red' : 'muted' ?>">
                            <?= Icon::render('clock', 14) ?> <?= Format::date($order['due_at']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($order['description']): ?>
                    <p class="order-desc"><?= nl2br($e($order['description'])) ?></p>
                <?php endif; ?>

                <?php if ($order['progress'] !== null): ?>
                    <div class="progress-wrap">
                        <div class="progress-bar">
                            <div class="progress-bar__fill" style="width:<?= $progress ?>%"></div>
                        </div>
                        <span class="progress-label"><?= $progress ?>%</span>
                    </div>
                <?php endif; ?>

                <?php if ($order['tags']): ?>
                    <div class="tag-list">
                        <?php foreach (explode(',', $order['tags']) as $tag): ?>
                            <span class="tag"><?= $e(trim($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Финансы -->
            <div class="card__section card__section--bordered">
                <h3 class="card__section-title">Финансы</h3>
                <div class="finance-grid">
                    <div class="fin-cell">
                        <span class="fin-cell__label">Стоимость</span>
                        <span class="fin-cell__value"><?= Format::money($order['amount_input']) ?> <?= $e($order['currency']) ?></span>
                        <?php if ($order['currency'] !== 'RUB'): ?>
                            <span class="fin-cell__sub">= <?= Format::money($order['amount_rub']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="fin-cell">
                        <span class="fin-cell__label">Оплачено</span>
                        <span class="fin-cell__value fin-cell__value--green"><?= Format::money($paid) ?></span>
                    </div>
                    <div class="fin-cell">
                        <span class="fin-cell__label">Остаток</span>
                        <span class="fin-cell__value <?= $debt > 0 ? 'fin-cell__value--red' : '' ?>"><?= Format::money($debt) ?></span>
                    </div>
                    <?php if ($order['currency'] !== 'RUB'): ?>
                    <div class="fin-cell">
                        <span class="fin-cell__label">Курс на момент ввода</span>
                        <span class="fin-cell__value fin-cell__value--muted">1 <?= $e($order['currency']) ?> = <?= number_format((float)$order['fx_rate'], 2, ',', ' ') ?> ₽</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Платежи -->
                <?php if (!empty($payments)): ?>
                    <div class="payment-table">
                        <table class="table table--compact">
                            <thead><tr><th>Дата</th><th>Сумма</th><th>Метод</th><th>Примечание</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td><?= Format::date($p['paid_at'], 'd.m.Y H:i') ?></td>
                                        <td class="mono"><?= Format::money($p['amount_rub']) ?></td>
                                        <td><?= $e($p['method'] ?? '—') ?></td>
                                        <td><?= $e($p['note'] ?? '') ?></td>
                                        <td>
                                            <form method="post" action="/payments/<?= $p['id'] ?>/delete" style="display:inline">
                                                <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                                                <button type="submit" class="icon-btn icon-btn--danger" title="Удалить"
                                                        onclick="return confirm('Удалить платёж?')"><?= Icon::render('trash', 14) ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Добавить платёж -->
                <details class="collapsible">
                    <summary class="collapsible__trigger">
                        <?= Icon::render('plus', 16) ?> Добавить платёж
                    </summary>
                    <form method="post" action="/orders/<?= $order['id'] ?>/payments" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                        <div class="field-row">
                            <div class="field">
                                <label class="field__label">Сумма</label>
                                <input type="number" name="amount_input" class="field__input" min="0" step="0.01" required placeholder="0">
                            </div>
                            <div class="field field--narrow">
                                <label class="field__label">Валюта</label>
                                <select name="currency" class="select">
                                    <option value="RUB">₽ RUB</option>
                                    <option value="USD">$ USD</option>
                                </select>
                            </div>
                            <div class="field">
                                <label class="field__label">Метод</label>
                                <select name="method" class="select">
                                    <option value="bank">Банк</option>
                                    <option value="card">Карта</option>
                                    <option value="cash">Наличные</option>
                                    <option value="crypto">Крипто</option>
                                </select>
                            </div>
                            <div class="field">
                                <label class="field__label">Дата</label>
                                <input type="datetime-local" name="paid_at" class="field__input" value="<?= date('Y-m-d\TH:i') ?>">
                            </div>
                        </div>
                        <div class="field">
                            <label class="field__label">Примечание</label>
                            <input type="text" name="note" class="field__input" placeholder="Аванс, milestone 1…">
                        </div>
                        <button type="submit" class="btn btn--primary btn--sm">Добавить</button>
                    </form>
                </details>
            </div>

            <!-- Быстрая смена статуса -->
            <div class="card__section card__section--bordered">
                <h3 class="card__section-title">Изменить статус</h3>
                <div class="status-actions">
                    <?php foreach (Labels::ORDER_STATUS as $key => [$label, $color]): ?>
                        <?php if ($key === $order['status']) continue; ?>
                        <form method="post" action="/orders/<?= $order['id'] ?>/status" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                            <input type="hidden" name="status" value="<?= $key ?>">
                            <button type="submit" class="btn btn--ghost btn--sm">→ <?= $label ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Лента событий и комментарии -->
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Активность и комментарии</h3>

                <form method="post" action="/orders/<?= $order['id'] ?>/comment" class="comment-form">
                    <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                    <div class="comment-form__row">
                        <textarea name="message" class="field__input field__input--textarea" rows="2"
                                  placeholder="Напишите комментарий…" required></textarea>
                        <button type="submit" class="btn btn--primary btn--sm"><?= Icon::render('send', 16) ?></button>
                    </div>
                </form>

                <div class="event-feed">
                    <?php foreach ($events as $ev): ?>
                        <div class="event-item event-item--<?= $e($ev['type']) ?>">
                            <span class="avatar avatar--sm" style="--seed: <?= $e($ev['avatar_color'] ?? '#3ecf8e') ?>">
                                <?= mb_substr($ev['user_name'] ?? 'S', 0, 1) ?>
                            </span>
                            <div class="event-item__body">
                                <span class="event-item__who"><?= $e($ev['user_name'] ?? 'Система') ?></span>
                                <span class="event-item__text"><?= $e($ev['message']) ?></span>
                            </div>
                            <span class="event-item__time"><?= Format::ago($ev['created_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="order-layout__side">
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Клиент</h3>
                <?php if ($order['client_name']): ?>
                    <a href="/clients/<?= $order['client_id'] ?>" class="client-chip">
                        <span class="avatar" style="--seed: #3ecf8e"><?= mb_substr($order['client_name'], 0, 1) ?></span>
                        <div>
                            <span class="client-chip__name"><?= $e($order['client_name']) ?></span>
                            <span class="client-chip__type"><?= $order['client_type'] === 'company' ? 'Компания' : 'Физлицо' ?></span>
                        </div>
                        <?= Icon::render('chevron-right', 16) ?>
                    </a>
                <?php else: ?>
                    <p class="muted">Клиент не указан</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Детали</h3>
                <dl class="details-list">
                    <dt>Ответственный</dt>
                    <dd><?= $e($order['owner_name'] ?? '—') ?></dd>
                    <dt>Создан</dt>
                    <dd><?= Format::date($order['created_at'], 'd.m.Y H:i') ?></dd>
                    <dt>Обновлён</dt>
                    <dd><?= Format::ago($order['updated_at'] ?? null) ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
