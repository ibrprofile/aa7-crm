<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($title) ? htmlspecialchars($title) . ' · AA7 CRM' : 'AA7 CRM' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
    <?php if (!empty($_flash)): foreach ($_flash as $_f): ?>
        <div class="toast toast--<?= htmlspecialchars($_f['type']) ?> toast--fixed" data-role="toast">
            <span><?= htmlspecialchars($_f['message']) ?></span>
            <button class="toast__close" data-role="toast-close">×</button>
        </div>
    <?php endforeach; endif; ?>

    <?= $content ?>

    <script src="/assets/js/app.js" defer></script>
</body>
</html>
