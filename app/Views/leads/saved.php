<?php
use App\Support\Format;
use App\Support\Icon;
use App\Support\Labels;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$countMap = [];
foreach ($counts as $row) { $countMap[$row['source']] = $row['total']; }
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Сохранённые лиды</h1>
        <p class="page-sub">
            2ГИС: <?= $countMap['2gis'] ?? 0 ?> &nbsp;·&nbsp;
            Яндекс: <?= $countMap['yandex'] ?? 0 ?>
        </p>
    </div>
    <div class="page-actions">
        <a href="/leads" class="btn btn--primary"><?= Icon::render('search', 16) ?> Новый поиск</a>
    </div>
</div>

<div class="toolbar">
    <form method="get" action="/leads/saved" class="toolbar__filters">
        <select name="source" class="select select--sm" onchange="this.form.submit()">
            <option value="">Все источники</option>
            <option value="2gis" <?= ($filters['source'] ?? '') === '2gis' ? 'selected' : '' ?>>2ГИС</option>
            <option value="yandex" <?= ($filters['source'] ?? '') === 'yandex' ? 'selected' : '' ?>>Яндекс</option>
        </select>
        <select name="status" class="select select--sm" onchange="this.form.submit()">
            <option value="">Все статусы</option>
            <?php foreach (Labels::LEAD_STATUS as $key => [$label]): ?>
                <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (empty($leads)): ?>
    <div class="empty-page">
        <?= Icon::render('inbox', 48) ?>
        <h2>Сохранённых лидов нет</h2>
        <a href="/leads" class="btn btn--primary"><?= Icon::render('search', 16) ?> Найти клиентов</a>
    </div>
<?php else: ?>
    <div class="card table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Источник</th>
                    <th>Категория / Город</th>
                    <th>Контакты</th>
                    <th>Рейтинг</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $l): ?>
                    <tr>
                        <td class="td-bold"><?= $e($l['name']) ?></td>
                        <td>
                            <span class="source-pill source-pill--<?= $e($l['source']) ?>">
                                <?= $l['source'] === '2gis' ? '2ГИС' : 'Яндекс' ?>
                            </span>
                        </td>
                        <td>
                            <?= $e($l['category'] ?? '') ?>
                            <?php if ($l['city']): ?><br><span class="muted"><?= Icon::render('map-pin', 12) ?> <?= $e($l['city']) ?></span><?php endif; ?>
                        </td>
                        <td class="contact-cell">
                            <?php if ($l['phone']): ?><a href="tel:<?= $e($l['phone']) ?>" class="contact-link"><?= Icon::render('phone', 14) ?></a><?php endif; ?>
                            <?php if ($l['website']): ?><a href="<?= $e($l['website']) ?>" target="_blank" class="contact-link"><?= Icon::render('globe', 14) ?></a><?php endif; ?>
                            <?php if ($l['instagram']): ?><a href="https://instagram.com/<?= ltrim($e($l['instagram']), '@') ?>" target="_blank" class="contact-link" style="color:#c13584"><?= Icon::render('external', 14) ?></a><?php endif; ?>
                            <?php if ($l['whatsapp']): ?><a href="https://wa.me/<?= preg_replace('/\D/', '', $l['whatsapp']) ?>" target="_blank" class="contact-link" style="color:#25d366"><?= Icon::render('message', 14) ?></a><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($l['rating']): ?>
                                <span class="star-row"><?= Icon::render('star', 12) ?> <?= number_format((float)$l['rating'], 1) ?></span>
                            <?php else: ?><span class="muted">—</span><?php endif; ?>
                        </td>
                        <td><?= Labels::badge(Labels::LEAD_STATUS, $l['status']) ?></td>
                        <td><?= Format::ago($l['created_at']) ?></td>
                        <td>
                            <div class="row-actions">
                                <form method="post" action="/leads/<?= $l['id'] ?>/convert" style="display:inline">
                                    <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                                    <button type="submit" class="icon-btn" title="Конвертировать в клиента" onclick="return confirm('Создать клиента из этого лида?')"><?= Icon::render('user-plus', 14) ?></button>
                                </form>
                                <div class="dropdown" data-role="status-dropdown">
                                    <button type="button" class="icon-btn" title="Изменить статус"><?= Icon::render('chevron-down', 14) ?></button>
                                    <div class="dropdown__menu">
                                        <?php foreach (Labels::LEAD_STATUS as $key => [$label]): ?>
                                            <form method="post" action="/leads/<?= $l['id'] ?>/status">
                                                <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                                                <input type="hidden" name="status" value="<?= $key ?>">
                                                <button type="submit" class="dropdown__item"><?= $label ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <form method="post" action="/leads/<?= $l['id'] ?>/delete" style="display:inline">
                                    <input type="hidden" name="_csrf" value="<?= $e($_csrf) ?>">
                                    <button type="submit" class="icon-btn icon-btn--danger" title="Удалить" onclick="return confirm('Удалить лид?')"><?= Icon::render('trash', 14) ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
