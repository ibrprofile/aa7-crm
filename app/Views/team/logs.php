<?php
use App\Support\Format;
use App\Support\Icon;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header">
    <div class="page-header__back">
        <a href="/team" class="back-link"><?= Icon::render('arrow-left', 18) ?> Команда</a>
        <h1 class="page-title">Журнал действий</h1>
    </div>
</div>

<div class="toolbar">
    <form method="get" action="/team/logs" class="toolbar__filters">
        <select name="user_id" class="select select--sm" onchange="this.form.submit()">
            <option value="">Все сотрудники</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (int)$userId === (int)$u['id'] ? 'selected' : '' ?>><?= $e($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($userId): ?>
            <a href="/team/logs" class="btn btn--ghost btn--sm"><?= Icon::render('x', 14) ?> Сбросить</a>
        <?php endif; ?>
    </form>
</div>

<div class="card table-card">
    <table class="table">
        <thead>
            <tr><th>Дата</th><th>Сотрудник</th><th>Действие</th><th>Описание</th><th>IP</th></tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="mono"><?= Format::date($log['created_at'], 'd.m.Y H:i') ?></td>
                    <td>
                        <div class="user-row">
                            <span class="avatar avatar--sm" style="--seed:<?= $e($log['avatar_color'] ?? '#3ecf8e') ?>"><?= mb_substr($log['user_name'] ?? 'S', 0, 1) ?></span>
                            <span><?= $e($log['user_name'] ?? 'Система') ?></span>
                        </div>
                    </td>
                    <td><span class="tag"><?= $e($log['action']) ?></span></td>
                    <td><?= $e($log['description'] ?? '') ?></td>
                    <td class="mono muted"><?= $e($log['ip'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
