<?php
use App\Core\Security;
$_csrf = Security::csrfToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0c0e14">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($title) ? htmlspecialchars($title) . ' · Кабинет' : 'Кабинет' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body style="background:var(--bg-base);color:var(--text-primary)">

    <?php if (isset($client)): ?>
    <nav class="cabinet-nav">
        <div class="cabinet-nav__brand">
            <span class="brand-mark" style="font-size:14px;padding:3px 7px">AA7</span>
            <span style="font-size:13px;font-weight:600;color:var(--text-secondary)">Кабинет</span>
        </div>
        <div class="cabinet-nav__links">
            <a href="/cabinet" class="cabinet-nav__link<?= str_starts_with($_SERVER['REQUEST_URI'], '/cabinet/orders') ? '' : ' is-active' ?>">Заказы</a>
        </div>
        <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
            <span style="font-size:13px;color:var(--text-muted)"><?= htmlspecialchars($client['client_name'] ?? '') ?></span>
            <form method="post" action="/cabinet/logout" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= $_csrf ?>">
                <button type="submit" class="btn btn--ghost btn--sm">Выйти</button>
            </form>
        </div>
    </nav>
    <?php endif; ?>

    <main <?= isset($client) ? 'class="cabinet-main"' : 'class="cabinet-auth"' ?>>
        <?= $content ?>
    </main>

    <script src="/assets/js/app.js" defer></script>
</body>
</html>
