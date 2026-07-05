<?php

use App\Core\Auth;
use App\Models\Notification;
use App\Support\Icon;

$user   = Auth::check() ? Auth::user() : null;
$unread = ($user && isset($user['id'])) ? Notification::unreadCount((int) $user['id']) : 0;
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$nav = [
    ['/dashboard', 'Дашборд', 'dashboard'],
    ['/orders', 'Заказы', 'orders'],
    ['/clients', 'Клиенты', 'clients'],
    ['/leads', 'Поиск клиентов', 'target'],
    ['/statistics', 'Статистика', 'stats'],
    ['/notifications', 'Уведомления', 'bell'],
];
$adminNav = [
    ['/team', 'Сотрудники', 'team'],
    ['/team/logs', 'Журнал действий', 'activity'],
];

$isActive = static function (string $path) use ($current): bool {
    if ($path === '/dashboard') {
        return $current === '/' || str_starts_with($current, '/dashboard');
    }
    return str_starts_with($current, $path);
};
?>
<!DOCTYPE html>
<html lang="ru" class="bg-base">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d100f">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($title) ? htmlspecialchars($title) . ' · AA7 CRM' : 'AA7 CRM' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-shell" data-theme="dark">
    <div class="scrim" data-role="scrim"></div>

    <aside class="sidebar" data-role="sidebar">
        <div class="sidebar__brand">
            <span class="brand-mark">AA7</span>
            <span class="brand-text">CRM</span>
        </div>

        <nav class="sidebar__nav" aria-label="Основная навигация">
            <?php foreach ($nav as [$path, $label, $icon]): ?>
                <a href="<?= $path ?>" class="nav-item<?= $isActive($path) ? ' is-active' : '' ?>">
                    <?= Icon::render($icon) ?>
                    <span><?= $label ?></span>
                    <?php if ($path === '/notifications' && $unread > 0): ?>
                        <span class="nav-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <?php if (Auth::isAdmin()): ?>
                <div class="nav-section">Администрирование</div>
                <?php foreach ($adminNav as [$path, $label, $icon]): ?>
                    <a href="<?= $path ?>" class="nav-item<?= $isActive($path) ? ' is-active' : '' ?>">
                        <?= Icon::render($icon) ?>
                        <span><?= $label ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>

        <div class="sidebar__foot">
            <a href="/settings" class="nav-item<?= $isActive('/settings') ? ' is-active' : '' ?>">
                <?= Icon::render('settings') ?>
                <span>Настройки</span>
            </a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn topbar__menu" data-role="menu-toggle" aria-label="Меню">
                <?= Icon::render('menu') ?>
            </button>

            <div class="topbar__search">
                <?= Icon::render('search') ?>
                <input type="search" placeholder="Поиск по заказам, клиентам, лидам…" data-role="global-search" autocomplete="off">
                <kbd>/</kbd>
            </div>

            <div class="topbar__actions">
                <a href="/orders/create" class="btn btn--primary btn--sm">
                    <?= Icon::render('plus') ?>
                    <span>Новый заказ</span>
                </a>
                <a href="/notifications" class="icon-btn icon-btn--badge" aria-label="Уведомления">
                    <?= Icon::render('bell') ?>
                    <?php if ($unread > 0): ?><span class="dot"></span><?php endif; ?>
                </a>
                <div class="user-chip" data-role="user-menu">
                    <span class="avatar" style="--seed: <?= htmlspecialchars($user['avatar_color'] ?? '#3ecf8e') ?>">
                        <?= mb_substr($user['name'] ?? 'A', 0, 1) ?>
                    </span>
                    <span class="user-chip__name"><?= htmlspecialchars($user['name'] ?? '') ?></span>
                    <div class="user-menu">
                        <div class="user-menu__head">
                            <strong><?= htmlspecialchars($user['name'] ?? '') ?></strong>
                            <span><?= htmlspecialchars($user['email'] ?? '') ?></span>
                        </div>
                        <a href="/settings">Настройки профиля</a>
                        <form method="post" action="/logout">
                            <input type="hidden" name="_csrf" value="<?= $_csrf ?>">
                            <button type="submit">Выйти</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <?php if (!empty($_flash)): foreach ($_flash as $_f): ?>
                <div class="toast toast--<?= htmlspecialchars($_f['type']) ?>" data-role="toast">
                    <?= Icon::render($_f['type'] === 'error' ? 'alert' : 'check') ?>
                    <span><?= htmlspecialchars($_f['message']) ?></span>
                    <button class="toast__close" data-role="toast-close" aria-label="Закрыть"><?= Icon::render('x') ?></button>
                </div>
            <?php endforeach; endif; ?>

            <?= $content ?>
        </main>
    </div>

    <script src="/assets/js/app.js" defer></script>
</body>
</html>
