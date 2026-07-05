<?php
use App\Support\Icon;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Поиск клиентов</h1>
        <p class="page-sub">Реальные данные из OpenStreetMap — только компании без сайта</p>
    </div>
    <div class="page-actions">
        <a href="/leads/saved" class="btn btn--ghost">
            <?= Icon::render('inbox', 16) ?> Сохранённые лиды
        </a>
    </div>
</div>

<div class="leads-layout">

    <!-- ─── Левая колонка: поиск + результаты ─── -->
    <div class="leads-main">

        <!-- Панель поиска -->
        <div class="card lead-search-card">
            <div class="lead-search__form">
                <div class="source-tabs" data-role="source-tabs">
                    <?php foreach ($sources as $key => $label): ?>
                        <button type="button"
                                class="source-tab <?= $key === 'osm' ? 'is-active' : '' ?>"
                                data-source="<?= $e($key) ?>">
                            <span class="source-tab__dot source-tab__dot--<?= $e($key) ?>"></span>
                            <?= $e($label) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="lead-search__inputs">
                    <div class="field">
                        <label class="field__label" for="ls-category">Ниша / тип бизнеса</label>
                        <div class="field__wrap">
                            <?= Icon::render('briefcase', 16, 'field__icon') ?>
                            <input type="text" id="ls-category" class="field__input field__input--icon field__input--lg"
                                   placeholder="Рестораны, Стоматологии, Салоны красоты…"
                                   data-role="ls-category" autocomplete="off">
                        </div>
                        <div class="search-suggestions" data-role="category-suggestions">
                            <?php foreach (['Рестораны','Стоматологии','Салоны красоты','Автосервисы','Фитнес-клубы','Кафе','Аптеки','Гостиницы'] as $s): ?>
                                <button type="button" class="suggestion" data-val="<?= $e($s) ?>"><?= $e($s) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field__label" for="ls-city">Город</label>
                        <div class="field__wrap">
                            <?= Icon::render('map-pin', 16, 'field__icon') ?>
                            <input type="text" id="ls-city" class="field__input field__input--icon field__input--lg"
                                   placeholder="Москва, Санкт-Петербург, Казань…"
                                   data-role="ls-city" autocomplete="off">
                        </div>
                    </div>
                    <div class="field field--narrow">
                        <label class="field__label" for="ls-limit">Лимит</label>
                        <select id="ls-limit" class="select select--lg" data-role="ls-limit">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <!-- Фильтр без сайта + кнопки -->
                <div class="lead-search__footer">
                    <label class="toggle-switch">
                        <input type="checkbox" id="ls-no-website" data-role="ls-no-website" checked>
                        <span class="toggle-switch__track"></span>
                        <span class="toggle-switch__label">
                            <?= Icon::render('globe-off', 14) ?>
                            Только без сайта + есть контакты <span class="badge badge--amber">рекомендуем</span>
                        </span>
                    </label>
                    <div class="lead-search__actions">
                        <button type="button" class="btn btn--primary btn--lg" data-role="ls-submit">
                            <?= Icon::render('search', 18) ?> Найти компании
                        </button>
                        <button type="button" class="btn btn--ghost btn--sm" data-role="ls-reset-seen" title="Сбросить список уже показанных компаний">
                            <?= Icon::render('refresh', 14) ?> Сбросить виденных
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div class="lead-loading" data-role="ls-loading" style="display:none">
            <div class="spinner"></div>
            <p>Ищем компании в OpenStreetMap…</p>
        </div>

        <!-- Ошибка -->
        <div class="lead-error" data-role="ls-error" style="display:none">
            <?= Icon::render('alert', 24) ?>
            <p data-role="ls-error-msg"></p>
        </div>

        <!-- Результаты -->
        <div data-role="ls-results" style="display:none">
            <div class="leads-results-header">
                <div class="leads-results-meta" data-role="ls-meta"></div>
                <button type="button" class="btn btn--ghost btn--sm" data-role="ls-save-all">
                    <?= Icon::render('download', 16) ?> Сохранить все
                </button>
            </div>
            <div class="leads-grid" data-role="ls-grid"></div>
        </div>

        <!-- Пусто после фильтрации -->
        <div class="lead-empty" data-role="ls-empty" style="display:none">
            <?= Icon::render('check-circle', 32) ?>
            <p>Все найденные компании уже были показаны ранее.<br>Попробуйте другой город или нишу, либо <button type="button" class="link-btn" data-role="ls-reset-seen-inline">сбросьте историю</button>.</p>
        </div>

    </div>

    <!-- ─── Правая колонка: история поисков ─── -->
    <div class="leads-sidebar">
        <div class="card">
            <div class="card__head">
                <span class="card__title">История поисков</span>
                <span class="badge badge--muted"><?= count($history) ?></span>
            </div>

            <?php if (empty($history)): ?>
                <div class="card__section" style="color:var(--text-muted);font-size:13px">
                    Поиски ещё не выполнялись
                </div>
            <?php else: ?>
                <div class="history-list" id="history-list">
                    <?php foreach ($history as $h): ?>
                        <div class="history-item" data-file="<?= $e($h['file']) ?>">
                            <div class="history-item__top">
                                <span class="history-item__query"><?= $e($h['category']) ?>, <?= $e($h['city']) ?></span>
                                <span class="badge badge--muted"><?= (int)$h['count'] ?></span>
                            </div>
                            <div class="history-item__meta">
                                <span class="history-item__source"><?= $e(strtoupper($h['source'])) ?></span>
                                <?php if ($h['no_website_only']): ?>
                                    <span class="badge badge--amber" style="font-size:10px">без сайта</span>
                                <?php endif; ?>
                                <span class="history-item__date"><?= $e(substr($h['searched_at'], 0, 16)) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Шаблон карточки -->
<template id="lead-card-tpl">
    <div class="lead-card" data-id="" data-source="">
        <div class="lead-card__head">
            <div class="lead-card__name"></div>
            <div class="lead-card__category"></div>
        </div>
        <div class="lead-card__rating">
            <span class="star-icon"><?= Icon::render('star', 14) ?></span>
            <span class="lead-card__stars"></span>
            <span class="lead-card__reviews"></span>
        </div>
        <div class="lead-card__contacts"></div>
        <div class="lead-card__stats"></div>
        <div class="lead-card__actions">
            <button type="button" class="btn btn--primary btn--sm lead-card__save-btn">
                <?= Icon::render('download', 14) ?> Сохранить
            </button>
        </div>
        <span class="lead-card__source-badge"></span>
    </div>
</template>

<script>
(function () {
    const csrf = <?= json_encode($_csrf) ?>;
    let currentSource = 'osm';
    let lastResults   = [];

    const $ = s => document.querySelector(s);
    const tabs     = document.querySelectorAll('[data-role="source-tabs"] .source-tab');
    const submit   = $('[data-role="ls-submit"]');
    const grid     = $('[data-role="ls-grid"]');
    const results  = $('[data-role="ls-results"]');
    const loading  = $('[data-role="ls-loading"]');
    const errorBox = $('[data-role="ls-error"]');
    const emptyBox = $('[data-role="ls-empty"]');
    const meta     = $('[data-role="ls-meta"]');
    const saveAll  = $('[data-role="ls-save-all"]');
    const tpl      = document.getElementById('lead-card-tpl');
    const catInput = $('[data-role="ls-category"]');
    const cityInput = $('[data-role="ls-city"]');
    const limitSel  = $('[data-role="ls-limit"]');
    const noWebCb   = $('[data-role="ls-no-website"]');
    const suggestions = document.querySelectorAll('[data-role="category-suggestions"] .suggestion');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('is-active'));
            tab.classList.add('is-active');
            currentSource = tab.dataset.source;
        });
    });

    suggestions.forEach(s => {
        s.addEventListener('click', () => { catInput.value = s.dataset.val; catInput.focus(); });
    });

    submit.addEventListener('click', doSearch);
    [catInput, cityInput].forEach(el => {
        el?.addEventListener('keydown', e => { if (e.key === 'Enter') doSearch(); });
    });

    // Сброс виденных
    document.addEventListener('click', e => {
        if (e.target.closest('[data-role="ls-reset-seen"], [data-role="ls-reset-seen-inline"]')) {
            if (!confirm('Сбросить список уже показанных компаний? Они снова начнут появляться в поиске.')) return;
            fetch('/leads/reset-seen', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({_csrf: csrf})
            }).then(() => { showMsg('История «виденных» сброшена.'); });
        }
    });

    // Клик по истории — загрузить результаты из файла
    document.querySelectorAll('.history-item').forEach(item => {
        item.addEventListener('click', () => {
            const file = item.dataset.file;
            showLoading();
            fetch('/leads/history?' + new URLSearchParams({file}))
                .then(r => r.json())
                .then(data => {
                    hideLoading();
                    if (data.error) { showError(data.error); return; }
                    currentSource = data.source || 'osm';
                    renderResults(data.items || [], data.category || '', data.city || '', data.source || 'osm', data.no_website_only || false, true);
                })
                .catch(() => { hideLoading(); showError('Не удалось загрузить файл поиска.'); });
        });
    });

    function doSearch() {
        const category = catInput.value.trim();
        const city     = cityInput.value.trim();
        const limit    = limitSel.value;
        const noWeb    = noWebCb.checked ? '1' : '0';

        if (!category || !city) { showError('Укажите нишу и город.'); return; }

        hideAll();
        showLoading();

        fetch('/leads/search', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({_csrf: csrf, source: currentSource, category, city, limit, no_website: noWeb})
        })
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.error) { showError(data.error); return; }
            if (!data.items || data.items.length === 0) {
                emptyBox.style.display = '';
                return;
            }
            renderResults(data.items, category, city, currentSource, noWeb === '1', false);
            refreshHistory();
        })
        .catch(() => { hideLoading(); showError('Ошибка запроса. Попробуйте снова.'); });
    }

    function renderResults(items, category, city, source, noWebOnly, fromHistory) {
        lastResults = items;

        const sourceLabel = source === '2gis' ? '2ГИС' : (source === 'osm' ? 'OpenStreetMap' : 'Яндекс.Карты');
        const noWebBadge  = noWebOnly ? ' <span class="badge badge--amber" style="font-size:11px">без сайта</span>' : '';
        const histBadge   = fromHistory ? ' <span class="badge badge--muted" style="font-size:11px">из архива</span>' : '';

        meta.innerHTML = `<strong>${items.length}</strong> компаний · <strong>${sourceLabel}</strong> · <em>${escHtml(category)}</em>, ${escHtml(city)}` + noWebBadge + histBadge;

        grid.innerHTML = '';
        items.forEach(item => grid.appendChild(buildCard(item, source)));
        results.style.display = '';
    }

    function buildCard(item, source) {
        const el = tpl.content.cloneNode(true).firstElementChild;
        el.dataset.id     = item.external_id;
        el.dataset.source = source;
        el.dataset.item   = JSON.stringify(item);

        el.querySelector('.lead-card__name').textContent     = item.name;
        el.querySelector('.lead-card__category').textContent = item.category || '';

        if (item.rating > 0) {
            el.querySelector('.lead-card__stars').textContent   = item.rating.toFixed(1);
            el.querySelector('.lead-card__reviews').textContent = `(${item.reviews} отзывов)`;
        } else {
            el.querySelector('.lead-card__rating').style.display = 'none';
        }

        const contacts = el.querySelector('.lead-card__contacts');
        if (item.phone)    contacts.insertAdjacentHTML('beforeend', `<a href="tel:${escHtml(item.phone)}" class="lead-contact"><?= Icon::render('phone', 14) ?> ${escHtml(item.phone)}</a>`);
        if (item.website)  contacts.insertAdjacentHTML('beforeend', `<a href="${escHtml(item.website)}" target="_blank" class="lead-contact"><?= Icon::render('globe', 14) ?> Сайт</a>`);
        if (!item.website) contacts.insertAdjacentHTML('beforeend', `<span class="lead-contact lead-no-website"><?= Icon::render('globe-off', 14) ?> Сайта нет — потенциальный клиент!</span>`);
        if (item.instagram) contacts.insertAdjacentHTML('beforeend', `<a href="https://instagram.com/${item.instagram.replace('@','')}" target="_blank" class="lead-contact lead-contact--insta">IG ${escHtml(item.instagram)}</a>`);
        if (item.whatsapp)  contacts.insertAdjacentHTML('beforeend', `<a href="https://wa.me/${item.whatsapp.replace(/\D/g,'')}" target="_blank" class="lead-contact lead-contact--wa">WhatsApp</a>`);
        if (item.telegram)  contacts.insertAdjacentHTML('beforeend', `<a href="https://t.me/${item.telegram.replace('@','')}" target="_blank" class="lead-contact lead-contact--tg">TG ${escHtml(item.telegram)}</a>`);
        if (item.vk)        contacts.insertAdjacentHTML('beforeend', `<a href="${escHtml(item.vk)}" target="_blank" class="lead-contact lead-contact--vk">ВКонтакте</a>`);
        if (item.address)   contacts.insertAdjacentHTML('beforeend', `<span class="lead-contact lead-contact--addr"><?= Icon::render('map-pin', 12) ?> ${escHtml(item.address)}</span>`);

        const stats = el.querySelector('.lead-card__stats');
        if (item.stats) {
            const s = item.stats;
            stats.innerHTML = `
                <div class="stat-bar"><span>Активность</span><div class="mini-bar"><div style="width:${s.activity}%"></div></div><span>${s.activity}%</span></div>
                <div class="stat-bar"><span>Спрос</span><div class="mini-bar"><div style="width:${s.demand}%"></div></div><span>${s.demand}%</span></div>
                <span class="lead-segment">${escHtml(s.segment)}</span>
            `;
        }

        el.querySelector('.lead-card__source-badge').textContent = source === '2gis' ? '2ГИС' : (source === 'osm' ? 'OSM' : 'Яндекс');
        el.querySelector('.lead-card__save-btn').addEventListener('click', function() {
            saveLead(item, source, this, el);
        });

        return el;
    }

    function saveLead(item, source, btn, card) {
        btn.disabled = true;
        btn.textContent = 'Сохраняем…';
        const params = new URLSearchParams({
            _csrf: csrf, source,
            external_id: item.external_id,
            name: item.name, category: item.category || '',
            city: item.city || '', address: item.address || '',
            phone: item.phone || '', website: item.website || '',
            instagram: item.instagram || '', whatsapp: item.whatsapp || '',
            rating: item.rating || '', reviews: item.reviews || '',
        });
        fetch('/leads/save', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params})
        .then(r => r.json())
        .then(res => {
            if (res.saved || res.reason === 'already_saved') {
                btn.textContent = '✓ Сохранён';
                card.classList.add('is-saved');
            } else {
                btn.disabled = false;
                btn.textContent = 'Сохранить';
            }
        });
    }

    saveAll.addEventListener('click', () => {
        document.querySelectorAll('.lead-card:not(.is-saved) .lead-card__save-btn').forEach(btn => btn.click());
    });

    // Обновить блок истории после нового поиска (без перезагрузки страницы)
    function refreshHistory() {
        fetch('/leads/history?' + new URLSearchParams({file: '__list__'}))
            .catch(() => {}); // просто перезагрузим sidebar при следующей загрузке
    }

    function showLoading() { loading.style.display = ''; }
    function hideLoading()  { loading.style.display = 'none'; }
    function hideAll()      { results.style.display = 'none'; errorBox.style.display = 'none'; emptyBox.style.display = 'none'; }
    function showError(msg) { errorBox.style.display = ''; $('[data-role="ls-error-msg"]').textContent = msg; }
    function showMsg(msg)   { const t = document.createElement('div'); t.className='toast toast--success'; t.style.cssText='position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:8px;padding:12px 16px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg)'; t.textContent=msg; document.body.appendChild(t); setTimeout(()=>t.remove(),3000); }
    function escHtml(v)     { return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
})();
</script>
