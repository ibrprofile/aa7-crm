<?php
use App\Support\Format;
use App\Support\Icon;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Уведомления</h1>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn--ghost" data-role="mark-all-read">
            <?= Icon::render('check', 16) ?> Отметить всё прочитанным
        </button>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="empty-page">
        <?= Icon::render('bell', 48) ?>
        <h2>Уведомлений нет</h2>
        <p>Все события будут отображаться здесь</p>
    </div>
<?php else: ?>
    <div class="notif-list">
        <?php foreach ($items as $n): ?>
            <div class="notif-item <?= !$n['is_read'] ? 'notif-item--unread' : '' ?>" data-id="<?= $n['id'] ?>">
                <div class="notif-item__icon notif-item__icon--<?= $e($n['type']) ?>">
                    <?= Icon::render($n['icon'] ?? 'bell', 18) ?>
                </div>
                <div class="notif-item__body">
                    <div class="notif-item__title"><?= $e($n['title']) ?></div>
                    <?php if ($n['body']): ?>
                        <div class="notif-item__desc"><?= $e($n['body']) ?></div>
                    <?php endif; ?>
                    <div class="notif-item__time"><?= Format::ago($n['created_at']) ?></div>
                </div>
                <?php if ($n['link']): ?>
                    <a href="<?= $e($n['link']) ?>" class="notif-item__link"><?= Icon::render('chevron-right', 16) ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.querySelector('[data-role="mark-all-read"]')?.addEventListener('click', function() {
    fetch('/notifications/read', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf=<?= $e($_csrf) ?>'
    }).then(() => {
        document.querySelectorAll('.notif-item--unread').forEach(el => el.classList.remove('notif-item--unread'));
    });
});
</script>
