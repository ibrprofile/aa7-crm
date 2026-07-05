<?php
use App\Support\Labels;
use App\Support\Format;
?>
<div class="cab-page">

    <div class="cab-hero">
        <div>
            <h1 class="cab-hero__title">Мои заказы</h1>
            <p class="cab-hero__sub">Все ваши проекты и их статусы</p>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="cab-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
            </svg>
            <p>Заказов пока нет</p>
        </div>
    <?php else: ?>
        <div class="cab-orders">
            <?php foreach ($orders as $order): ?>
                <a href="/cabinet/orders/<?= $order['id'] ?>" class="cab-order-card">
                    <div class="cab-order-card__head">
                        <span class="cab-order-card__number"><?= htmlspecialchars($order['number']) ?></span>
                        <span class="status-badge status-badge--<?= $order['status'] ?>"><?= Labels::status($order['status']) ?></span>
                    </div>
                    <div class="cab-order-card__title"><?= htmlspecialchars($order['title']) ?></div>
                    <?php if ($order['description']): ?>
                        <div class="cab-order-card__desc"><?= htmlspecialchars(mb_substr($order['description'], 0, 100)) . (mb_strlen($order['description']) > 100 ? '…' : '') ?></div>
                    <?php endif; ?>
                    <div class="cab-order-card__foot">
                        <?php if ($order['amount_rub']): ?>
                            <span class="cab-order-card__amount"><?= Format::money($order['amount_rub']) ?> ₽</span>
                        <?php endif; ?>
                        <?php if ($order['due_at']): ?>
                            <span class="cab-order-card__due">до <?= Format::date($order['due_at'], 'd.m.Y') ?></span>
                        <?php endif; ?>
                        <span class="cab-order-card__arrow">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                    </div>
                    <?php if ($order['progress'] > 0): ?>
                        <div class="cab-order-card__progress">
                            <div class="cab-order-card__progress-bar" style="width:<?= (int)$order['progress'] ?>%"></div>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
