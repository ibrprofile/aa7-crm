<?php
use App\Support\Format;
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header">
    <div class="page-header__back">
        <a href="/clients" class="back-link"><?= Icon::render('arrow-left', 18) ?> Клиенты</a>
        <h1 class="page-title">
            <?= $e($client['name']) ?>
            <?= Labels::badge(Labels::CLIENT_STATUS, $client['status']) ?>
        </h1>
    </div>
    <div class="page-actions">
        <a href="/orders/create?client_id=<?= $client['id'] ?>" class="btn btn--primary">
            <?= Icon::render('plus', 16) ?> Новый заказ
        </a>
        <a href="/clients/<?= $client['id'] ?>/edit" class="btn btn--ghost">
            <?= Icon::render('edit', 16) ?> Редактировать
        </a>
    </div>
</div>

<div class="client-layout">
    <div class="client-layout__main">
        <!-- Сводка по клиенту -->
        <div class="kpi-grid kpi-grid--sm">
            <div class="kpi-card">
                <div class="kpi-card__icon kpi-card__icon--blue"><?= Icon::render('briefcase', 18) ?></div>
                <div class="kpi-card__body">
                    <span class="kpi-card__label">Заказов</span>
                    <span class="kpi-card__value"><?= $summary['orders'] ?></span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card__icon kpi-card__icon--green"><?= Icon::render('coins', 18) ?></div>
                <div class="kpi-card__body">
                    <span class="kpi-card__label">Выручка</span>
                    <span class="kpi-card__value"><?= Format::compact($summary['revenue']) ?> ₽</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card__icon kpi-card__icon--amber"><?= Icon::render('wallet', 18) ?></div>
                <div class="kpi-card__body">
                    <span class="kpi-card__label">Оплачено</span>
                    <span class="kpi-card__value"><?= Format::compact($summary['paid']) ?> ₽</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card__icon kpi-card__icon--purple"><?= Icon::render('activity', 18) ?></div>
                <div class="kpi-card__body">
                    <span class="kpi-card__label">Активных</span>
                    <span class="kpi-card__value"><?= $summary['active'] ?></span>
                </div>
            </div>
        </div>

        <!-- Заказы клиента -->
        <div class="card">
            <div class="card__head">
                <span class="card__title">Заказы</span>
                <a href="/orders/create?client_id=<?= $client['id'] ?>" class="card__link">
                    <?= Icon::render('plus', 14) ?> Новый
                </a>
            </div>
            <?php if (empty($orders)): ?>
                <div class="empty-state"><p>Заказов пока нет</p></div>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Номер</th><th>Название</th><th>Статус</th><th class="th-right">Сумма</th><th>Дата</th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <?php [$sl, $sc] = Labels::get(Labels::ORDER_STATUS, $o['status']); ?>
                            <tr>
                                <td><span class="mono"><?= $e($o['number']) ?></span></td>
                                <td><a href="/orders/<?= $o['id'] ?>" class="table-link"><?= $e($o['title']) ?></a></td>
                                <td><?= Labels::badge(Labels::ORDER_STATUS, $o['status']) ?></td>
                                <td class="td-right mono"><?= Format::money($o['amount_rub']) ?></td>
                                <td><?= Format::date($o['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Контактные лица (для компании) -->
        <?php if ($client['type'] === 'company' && !empty($contacts)): ?>
        <div class="card">
            <div class="card__head">
                <span class="card__title">Контактные лица</span>
                <a href="/clients/create" class="card__link"><?= Icon::render('user-plus', 14) ?> Добавить</a>
            </div>
            <div class="contacts-grid">
                <?php foreach ($contacts as $p): ?>
                    <a href="/clients/<?= $p['id'] ?>" class="contact-card">
                        <span class="avatar" style="--seed: #3ecf8e"><?= mb_substr($p['name'], 0, 1) ?></span>
                        <div>
                            <span class="contact-card__name"><?= $e($p['name']) ?></span>
                            <?php if ($p['position']): ?>
                                <span class="contact-card__pos"><?= $e($p['position']) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="client-layout__side">
        <!-- Контактная информация -->
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Контакты</h3>
                <div class="contact-info">
                    <?php if ($client['phone']): ?>
                        <a href="tel:<?= $e($client['phone']) ?>" class="contact-info__row">
                            <?= Icon::render('phone', 16) ?>
                            <span><?= $e($client['phone']) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($client['email']): ?>
                        <a href="mailto:<?= $e($client['email']) ?>" class="contact-info__row">
                            <?= Icon::render('mail', 16) ?>
                            <span><?= $e($client['email']) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($client['telegram']): ?>
                        <a href="https://t.me/<?= ltrim($e($client['telegram']), '@') ?>" target="_blank" class="contact-info__row">
                            <?= Icon::render('send', 16) ?>
                            <span><?= $e($client['telegram']) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($client['whatsapp']): ?>
                        <a href="https://wa.me/<?= preg_replace('/\D/', '', $client['whatsapp']) ?>" target="_blank" class="contact-info__row">
                            <?= Icon::render('message', 16) ?>
                            <span><?= $e($client['whatsapp']) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($client['website']): ?>
                        <a href="<?= $e($client['website']) ?>" target="_blank" class="contact-info__row">
                            <?= Icon::render('globe', 16) ?>
                            <span><?= $e($client['website']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Информация</h3>
                <dl class="details-list">
                    <?php if ($client['type'] === 'person' && $client['position']): ?>
                        <dt>Должность</dt><dd><?= $e($client['position']) ?></dd>
                    <?php endif; ?>
                    <?php if ($client['inn']): ?>
                        <dt>ИНН/РНН</dt><dd><?= $e($client['inn']) ?></dd>
                    <?php endif; ?>
                    <?php if ($client['industry']): ?>
                        <dt>Отрасль</dt><dd><?= $e($client['industry']) ?></dd>
                    <?php endif; ?>
                    <?php if ($client['country'] || $client['city']): ?>
                        <dt>Город</dt><dd><?= $e(implode(', ', array_filter([$client['city'], $client['country']]))) ?></dd>
                    <?php endif; ?>
                    <?php if ($client['source']): ?>
                        <dt>Источник</dt><dd><?= $e($client['source']) ?></dd>
                    <?php endif; ?>
                    <dt>Добавлен</dt><dd><?= Format::date($client['created_at']) ?></dd>
                </dl>
            </div>
        </div>

        <?php if ($client['notes']): ?>
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Заметки</h3>
                <p class="notes-text"><?= nl2br($e($client['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Кабинет клиента -->
        <div class="card">
            <div class="card__section">
                <h3 class="card__section-title">Кабинет клиента</h3>
                <?php if (!empty($credential)): ?>
                    <div style="margin-bottom:14px">
                        <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--bg-surface);border-radius:var(--radius-sm);border:1px solid var(--border);margin-bottom:8px">
                            <?= Icon::render('user', 14) ?>
                            <span style="font-size:13px;font-weight:600"><?= $e($credential['login']) ?></span>
                            <a href="/cabinet/login" target="_blank" class="btn btn--ghost btn--sm" style="margin-left:auto">
                                <?= Icon::render('external-link', 13) ?> Открыть
                            </a>
                        </div>
                        <?php if ($credential['last_login_at']): ?>
                            <p style="font-size:12px;color:var(--text-muted)">Последний вход: <?= \App\Support\Format::date($credential['last_login_at'], 'd.m.Y H:i') ?></p>
                        <?php else: ?>
                            <p style="font-size:12px;color:var(--text-muted)">Ещё не заходил</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="/clients/<?= $client['id'] ?>/cabinet">
                    <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                    <div class="field-row" style="margin-bottom:10px">
                        <div class="field">
                            <label class="field__label">Логин</label>
                            <input type="text" name="cab_login" class="field__input" required
                                   value="<?= $e($credential['login'] ?? '') ?>" placeholder="Логин для входа" autocomplete="off">
                        </div>
                        <div class="field">
                            <label class="field__label"><?= !empty($credential) ? 'Новый пароль' : 'Пароль' ?></label>
                            <div class="field__wrap">
                                <input type="password" name="cab_password" class="field__input"
                                       <?= empty($credential) ? 'required' : '' ?>
                                       placeholder="<?= !empty($credential) ? 'Оставьте пустым чтобы не менять' : 'Установить пароль' ?>" autocomplete="new-password">
                                <button type="button" class="field__toggle-pw" data-role="toggle-pw">
                                    <?= Icon::render('eye', 14) ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary btn--sm">
                        <?= Icon::render('save', 14) ?> <?= !empty($credential) ? 'Сохранить' : 'Создать доступ' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
