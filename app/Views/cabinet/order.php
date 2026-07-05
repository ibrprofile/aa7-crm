<?php
use App\Support\Labels;
use App\Support\Format;

$isImage = fn(string $mime) => str_starts_with($mime, 'image/');
$isVideo = fn(string $mime) => str_starts_with($mime, 'video/');
$isPdf   = fn(string $mime) => $mime === 'application/pdf';
?>
<div class="cab-page cab-page--order">

    <div class="cab-order-header">
        <a href="/cabinet" class="cab-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
            Все заказы
        </a>
        <div class="cab-order-header__info">
            <div>
                <span class="cab-order-header__number"><?= htmlspecialchars($order['number']) ?></span>
                <h1 class="cab-order-header__title"><?= htmlspecialchars($order['title']) ?></h1>
            </div>
            <span class="status-badge status-badge--<?= $order['status'] ?> status-badge--lg"><?= Labels::status($order['status']) ?></span>
        </div>
    </div>

    <div class="cab-order-body">

        <!-- Левая колонка: инфо + файлы -->
        <div class="cab-order-aside">

            <?php if ($order['description']): ?>
            <div class="cab-widget">
                <div class="cab-widget__label">Описание</div>
                <p class="cab-widget__text"><?= nl2br(htmlspecialchars($order['description'])) ?></p>
            </div>
            <?php endif; ?>

            <div class="cab-widget cab-widget--grid2">
                <?php if ($order['amount_rub']): ?>
                <div>
                    <div class="cab-widget__label">Стоимость</div>
                    <div class="cab-widget__value"><?= Format::money($order['amount_rub']) ?> ₽</div>
                </div>
                <?php endif; ?>
                <?php if ($order['paid_rub']): ?>
                <div>
                    <div class="cab-widget__label">Оплачено</div>
                    <div class="cab-widget__value" style="color:var(--green)"><?= Format::money($order['paid_rub']) ?> ₽</div>
                </div>
                <?php endif; ?>
                <?php if ($order['due_at']): ?>
                <div>
                    <div class="cab-widget__label">Срок</div>
                    <div class="cab-widget__value"><?= Format::date($order['due_at'], 'd.m.Y') ?></div>
                </div>
                <?php endif; ?>
                <?php if ($order['progress'] > 0): ?>
                <div>
                    <div class="cab-widget__label">Прогресс</div>
                    <div class="cab-widget__value"><?= (int)$order['progress'] ?>%</div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($order['progress'] > 0): ?>
            <div class="cab-progress-wrap">
                <div class="cab-progress-bar" style="width:<?= (int)$order['progress'] ?>%"></div>
            </div>
            <?php endif; ?>

            <!-- Файлы из CRM (загружены менеджером) -->
            <?php
            $managerFiles = array_filter($files, fn($f) => !(bool)$f['from_client']);
            if (!empty($managerFiles)):
            ?>
            <div class="cab-widget">
                <div class="cab-widget__label">Файлы проекта</div>
                <div class="cab-files">
                    <?php foreach ($managerFiles as $f): ?>
                        <a href="/cabinet/files/<?= $f['id'] ?>/download" class="cab-file" download>
                            <?php if ($isImage($f['mime_type'] ?? '')): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <?php elseif ($isPdf($f['mime_type'] ?? '')): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <?php else: ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <?php endif; ?>
                            <span class="cab-file__name"><?= htmlspecialchars($f['original_name']) ?></span>
                            <?php if ($f['size_bytes']): ?>
                                <span class="cab-file__size"><?= Format::fileSize($f['size_bytes']) ?></span>
                            <?php endif; ?>
                            <svg class="cab-file__dl" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Правая колонка: чат -->
        <div class="cab-order-chat">
            <div class="cab-chat">
                <div class="cab-chat__head">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Переписка
                </div>

                <div class="cab-chat__body" id="chat-body">
                    <?php if (empty($messages)): ?>
                        <div class="cab-chat__empty">Сообщений пока нет. Напишите нам!</div>
                    <?php endif; ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="cab-msg <?= $msg['from_client'] ? 'cab-msg--client' : 'cab-msg--admin' ?>">
                            <?php if (!$msg['from_client']): ?>
                                <div class="cab-msg__avatar" style="background:<?= htmlspecialchars($msg['sender_color'] ?? '#6366f1') ?>">
                                    <?= mb_substr($msg['sender_name'] ?? 'A', 0, 1) ?>
                                </div>
                            <?php endif; ?>
                            <div class="cab-msg__bubble">
                                <?php if (!$msg['from_client'] && $msg['sender_name']): ?>
                                    <div class="cab-msg__name"><?= htmlspecialchars($msg['sender_name']) ?></div>
                                <?php endif; ?>
                                <?php if ($msg['body']): ?>
                                    <div class="cab-msg__text"><?= nl2br(htmlspecialchars($msg['body'])) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($msg['files'])): ?>
                                    <div class="cab-msg__files">
                                        <?php foreach ($msg['files'] as $f): ?>
                                            <a href="/cabinet/files/<?= $f['id'] ?>/download" class="cab-msg__file" download>
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                <?= htmlspecialchars($f['original_name']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="cab-msg__time"><?= date('d.m H:i', strtotime($msg['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="cab-chat__form" method="post"
                      action="/cabinet/orders/<?= $order['id'] ?>/message"
                      enctype="multipart/form-data" id="cab-chat-form">
                    <input type="hidden" name="_csrf" value="<?= $_csrf ?>">

                    <div class="cab-chat__attach-preview" id="attach-preview" style="display:none"></div>

                    <div class="cab-chat__input-row">
                        <label class="cab-chat__attach-btn" title="Прикрепить файл">
                            <input type="file" name="file" id="cab-file-input" style="display:none" accept="image/*,video/*,application/pdf,.zip,.rar,.7z,.txt,.doc,.docx,.xls,.xlsx">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                        </label>
                        <textarea name="body" class="cab-chat__textarea" id="cab-textarea"
                                  placeholder="Написать сообщение…" rows="1"></textarea>
                        <button type="submit" class="cab-chat__send" aria-label="Отправить">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    // Auto-scroll chat to bottom
    var body = document.getElementById('chat-body');
    if (body) body.scrollTop = body.scrollHeight;

    // Auto-resize textarea
    var ta = document.getElementById('cab-textarea');
    if (ta) {
        ta.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 160) + 'px';
        });
        ta.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey && !e.nativeEvent?.isComposing && e.keyCode !== 229) {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    }

    // File attach preview
    var fileInput = document.getElementById('cab-file-input');
    var preview   = document.getElementById('attach-preview');
    if (fileInput && preview) {
        fileInput.addEventListener('change', function () {
            var f = this.files[0];
            if (!f) { preview.style.display = 'none'; return; }
            preview.style.display = 'flex';
            preview.innerHTML = '<span class="cab-chat__attach-name">'
                + f.name + '</span>'
                + '<button type="button" class="cab-chat__attach-remove" id="remove-attach">'
                + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
                + '</button>';
            document.getElementById('remove-attach').addEventListener('click', function () {
                fileInput.value = '';
                preview.style.display = 'none';
                preview.innerHTML = '';
            });
        });
    }
})();
</script>
