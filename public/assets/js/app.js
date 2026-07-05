/* AA7 CRM — application script v2 */

(function () {
  'use strict';

  /* ── CSRF token helper ───────────────────── */
  const csrf = () => document.querySelector('meta[name="csrf"]')?.content
    || document.querySelector('input[name="_csrf"]')?.value || '';

  /* ── Sidebar toggle (mobile) ─────────────── */
  const sidebar = document.querySelector('[data-role="sidebar"]');
  const menuBtn = document.querySelector('[data-role="menu-toggle"]');
  const scrim   = document.querySelector('[data-role="scrim"]');

  function openSidebar() {
    sidebar?.classList.add('is-open');
    scrim?.classList.add('is-visible');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar?.classList.remove('is-open');
    scrim?.classList.remove('is-visible');
    document.body.style.overflow = '';
  }

  menuBtn?.addEventListener('click', () =>
    sidebar?.classList.contains('is-open') ? closeSidebar() : openSidebar());
  scrim?.addEventListener('click', closeSidebar);

  /* ── Keyboard shortcuts ──────────────────── */
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) {
      e.preventDefault();
      document.querySelector('[data-role="global-search"]')?.focus();
    }
    if (e.key === 'Escape') {
      closeSidebar();
      closeAllPanels();
      document.querySelectorAll('.modal.is-open').forEach(closeModal);
    }
  });

  /* ── Global search ───────────────────────── */
  const globalSearch = document.querySelector('[data-role="global-search"]');
  if (globalSearch) {
    let searchTimer;
    globalSearch.addEventListener('input', function () {
      clearTimeout(searchTimer);
      const q = this.value.trim();
      if (q.length < 2) return;
      searchTimer = setTimeout(() => {
        const path = window.location.pathname;
        if (!path.startsWith('/orders') && !path.startsWith('/clients') && !path.startsWith('/leads')) {
          window.location.href = '/orders?q=' + encodeURIComponent(q);
        }
      }, 700);
    });
  }

  /* ── Toast auto-dismiss ──────────────────── */
  function initToasts() {
    document.querySelectorAll('[data-role="toast"]').forEach(toast => {
      toast.querySelector('[data-role="toast-close"]')?.addEventListener('click', () => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
      });
      setTimeout(() => { toast.style.transition = 'opacity .4s'; toast.style.opacity = '0'; }, 4000);
      setTimeout(() => toast.remove(), 4500);
    });
  }
  initToasts();

  /* ── Show a toast programmatically ──────── */
  function showToast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `toast toast--${type} toast--fixed`;
    el.setAttribute('data-role', 'toast');
    el.innerHTML = `<span>${msg}</span><button class="toast__close" data-role="toast-close" aria-label="Закрыть"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg></button>`;
    document.body.appendChild(el);
    initToasts();
  }

  /* ── Modal system (CSS-animated) ────────── */
  function openModal(modal) {
    if (!modal) return;
    modal.style.display = 'flex';
    requestAnimationFrame(() => modal.classList.add('is-open'));
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    setTimeout(() => { modal.style.display = ''; document.body.style.overflow = ''; }, 250);
  }

  document.querySelectorAll('[data-role="modal-trigger"]').forEach(btn => {
    btn.addEventListener('click', () => openModal(document.getElementById(btn.dataset.modal)));
  });
  document.querySelectorAll('[data-role="modal-close"]').forEach(el => {
    el.addEventListener('click', () => closeModal(el.closest('.modal')));
  });
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', e => { if (e.target === modal || e.target.classList.contains('modal__backdrop')) closeModal(modal); });
  });

  /* ── Side panel system ───────────────────── */
  let panelBackdrop = null;

  function ensureBackdrop() {
    if (panelBackdrop) return panelBackdrop;
    panelBackdrop = document.createElement('div');
    panelBackdrop.className = 'panel-backdrop';
    document.body.appendChild(panelBackdrop);
    panelBackdrop.addEventListener('click', closeAllPanels);
    return panelBackdrop;
  }

  function openPanel(panel) {
    if (!panel) return;
    closeAllPanels();
    const bd = ensureBackdrop();
    requestAnimationFrame(() => {
      panel.classList.add('is-open');
      bd.classList.add('is-visible');
      document.body.style.overflow = 'hidden';
    });
  }

  function closeAllPanels() {
    document.querySelectorAll('.side-panel.is-open').forEach(p => p.classList.remove('is-open'));
    if (panelBackdrop) panelBackdrop.classList.remove('is-visible');
    document.body.style.overflow = '';
  }

  /* ── Order quick-panel ───────────────────── */
  function buildOrderPanel() {
    if (document.getElementById('order-panel')) return;
    const panel = document.createElement('div');
    panel.id = 'order-panel';
    panel.className = 'side-panel';
    panel.innerHTML = `
      <div class="side-panel__head">
        <a id="op-open-link" href="#" class="icon-btn" title="Открыть полностью">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M14 10l6.1-6.1M9 21H3v-6M10 14l-6.1 6.1"/></svg>
        </a>
        <h3 id="op-title" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></h3>
        <button class="icon-btn" id="op-close-btn" aria-label="Закрыть">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="side-panel__tabs">
        <button class="panel-tab is-active" data-panel-tab="info">Информация</button>
        <button class="panel-tab" data-panel-tab="activity">Активность</button>
        <button class="panel-tab" data-panel-tab="chat">Чат</button>
        <button class="panel-tab" data-panel-tab="pay">Оплата</button>
      </div>
      <div class="side-panel__body" id="op-body">
        <div style="display:flex;align-items:center;justify-content:center;height:200px">
          <div class="spinner"></div>
        </div>
      </div>`;
    document.body.appendChild(panel);

    panel.querySelector('#op-close-btn').addEventListener('click', closeAllPanels);
    panel.querySelectorAll('[data-panel-tab]').forEach(tab => {
      tab.addEventListener('click', () => {
        panel.querySelectorAll('[data-panel-tab]').forEach(t => t.classList.remove('is-active'));
        tab.classList.add('is-active');
        const pane = tab.dataset.panelTab;
        panel.querySelectorAll('[data-panel-pane]').forEach(p => {
          p.style.display = p.dataset.panelPane === pane ? '' : 'none';
        });
        if (pane === 'chat') {
          initPanelChat(panel.dataset.orderId);
        }
      });
    });
  }

  function loadOrderPanel(orderId) {
    buildOrderPanel();
    panelChatLoaded = false;
    const panel = document.getElementById('order-panel');
    const body  = document.getElementById('op-body');
    const link  = document.getElementById('op-open-link');
    link.href = `/orders/${orderId}`;
    body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:200px"><div class="spinner"></div></div>';
    openPanel(panel);

    fetch(`/api/orders/${orderId}/panel`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => renderOrderPanel(data, orderId))
      .catch(() => { body.innerHTML = '<div class="empty-state"><p>Не удалось загрузить заказ</p></div>'; });
  }

  function renderOrderPanel(d, orderId) {
    document.getElementById('op-title').textContent = `${d.number} · ${d.title}`;

    const statusColors = {
      new: 'blue', negotiation: 'amber', in_progress: 'accent',
      review: 'purple', done: 'green', cancelled: 'muted'
    };
    const statusLabels = {
      new: 'Новый', negotiation: 'Переговоры', in_progress: 'В работе',
      review: 'Проверка', done: 'Завершён', cancelled: 'Отменён'
    };

    const statusBtns = Object.entries(statusLabels).map(([k, v]) => {
      const active = k === d.status;
      const col = statusColors[k] || 'muted';
      return `<button class="status-pill badge--${col}${active ? ' badge--active' : ''}"
        data-status="${k}" ${active ? 'disabled' : ''} style="border-color:currentColor;opacity:${active?1:.6}">
        ${active ? '· ' : ''}${v}
      </button>`;
    }).join('');

    const paid    = parseFloat(d.paid_rub) || 0;
    const total   = parseFloat(d.amount_rub) || 0;
    const debt    = Math.max(0, total - paid);
    const pct     = total > 0 ? Math.round(paid / total * 100) : 0;

    const eventsHtml = (d.events || []).slice(0, 20).map(ev => `
      <div class="event-item event-item--${ev.type}">
        <span class="avatar avatar--sm" style="--seed:${ev.avatar_color||'#6366f1'}">${(ev.user_name||'S')[0]}</span>
        <div class="event-item__body">
          <span class="event-item__who">${ev.user_name||'Система'}</span>
          <span class="event-item__text">${ev.message}</span>
        </div>
        <span class="event-item__time">${ev.ago||ev.created_at}</span>
      </div>`).join('') || '<p class="muted" style="padding:12px">Нет активности</p>';

    const paymentsHtml = (d.payments || []).map(p => `
      <div class="pay-row">
        <div class="pay-row__info">
          <span class="pay-row__order">${p.method||'—'}</span>
          <span class="pay-row__client">${p.note||'Платёж'}</span>
        </div>
        <div class="pay-row__right">
          <span class="pay-row__amount">${fmtMoney(p.amount_rub)}</span>
          <span class="pay-row__date">${p.paid_at_fmt||''}</span>
        </div>
      </div>`).join('') || '<p class="muted" style="padding:12px 20px">Платежей нет</p>';

    const panel = document.getElementById('order-panel');
    if (panel) panel.dataset.orderId = orderId;

    document.getElementById('op-body').innerHTML = `
      <div data-panel-pane="info">
        <div class="panel-section">
          <div style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:6px">${statusBtns}</div>
          ${d.description ? `<p style="font-size:13px;color:var(--text-secondary);line-height:1.65;margin-bottom:14px">${d.description}</p>` : ''}
          <div class="progress-wrap" style="margin-bottom:14px">
            <div class="progress-bar" style="flex:1"><div class="progress-bar__fill" style="width:${pct}%"></div></div>
            <span class="progress-label">${pct}%</span>
          </div>
        </div>
        <div class="panel-section">
          <div class="finance-grid">
            <div class="fin-cell"><span class="fin-cell__label">Стоимость</span><span class="fin-cell__value">${fmtMoney(total)}</span></div>
            <div class="fin-cell"><span class="fin-cell__label">Оплачено</span><span class="fin-cell__value fin-cell__value--green">${fmtMoney(paid)}</span></div>
            <div class="fin-cell"><span class="fin-cell__label">Остаток</span><span class="fin-cell__value ${debt>0?'fin-cell__value--red':''}">${fmtMoney(debt)}</span></div>
            ${d.due_at ? `<div class="fin-cell"><span class="fin-cell__label">Срок</span><span class="fin-cell__value" style="font-size:14px">${d.due_at}</span></div>` : ''}
          </div>
        </div>
        ${d.client_name ? `
        <div class="panel-section">
          <a href="/clients/${d.client_id}" class="client-chip">
            <span class="avatar" style="--seed:#6366f1">${d.client_name[0]}</span>
            <div><span class="client-chip__name">${d.client_name}</span><span class="client-chip__type">Клиент</span></div>
          </a>
        </div>` : ''}
      </div>
      <div data-panel-pane="activity" style="display:none">
        <div class="panel-section">
          <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:16px">
            <textarea id="op-comment" class="field__input field__input--textarea" rows="2" placeholder="Комментарий…" style="flex:1;min-height:60px"></textarea>
            <button class="btn btn--primary btn--sm" id="op-comment-send">${svgSend()}</button>
          </div>
          <div class="event-feed" id="op-events">${eventsHtml}</div>
        </div>
      </div>
      <div data-panel-pane="chat" style="display:none;height:calc(100vh - 130px)">
        <div class="panel-chat" id="panel-chat-wrap">
          <div class="panel-chat__messages" id="panel-chat-messages">
            <div style="display:flex;align-items:center;justify-content:center;height:80px"><div class="spinner"></div></div>
          </div>
          <form class="panel-chat__form" id="panel-chat-form" enctype="multipart/form-data">
            <div class="panel-chat__row">
              <label class="panel-chat__attach" title="Прикрепить файл">
                <input type="file" id="panel-chat-file" style="display:none" name="file">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
              </label>
              <textarea id="panel-chat-textarea" class="panel-chat__textarea" rows="1" placeholder="Написать клиенту…"></textarea>
              <button type="submit" class="panel-chat__send" aria-label="Отправить">${svgSend()}</button>
            </div>
          </form>
        </div>
      </div>
      <div data-panel-pane="pay" style="display:none">
        <div class="pay-list">${paymentsHtml}</div>
      </div>`;

    /* Status change buttons */
    document.getElementById('op-body').querySelectorAll('[data-status]').forEach(btn => {
      btn.addEventListener('click', () => changeOrderStatus(orderId, btn.dataset.status));
    });

    /* Comment send */
    const commentArea = document.getElementById('op-comment');
    const commentBtn  = document.getElementById('op-comment-send');
    commentBtn?.addEventListener('click', () => sendOrderComment(orderId, commentArea));
    commentArea?.addEventListener('keydown', e => {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey) && !e.nativeEvent?.isComposing) {
        sendOrderComment(orderId, commentArea);
      }
    });
  }

  function changeOrderStatus(orderId, status) {
    fetch(`/api/orders/${orderId}/status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ status, _csrf: csrf() })
    })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(() => {
        showToast('Статус обновлён');
        loadOrderPanel(orderId);
        /* update badge in table row if exists */
        const row = document.querySelector(`[data-order-id="${orderId}"]`);
        if (row) row.setAttribute('data-status', status);
      })
      .catch(() => showToast('Ошибка при обновлении статуса', 'error'));
  }

  function sendOrderComment(orderId, textarea) {
    const text = textarea.value.trim();
    if (!text) return;
    textarea.disabled = true;
    fetch(`/api/orders/${orderId}/comment`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ message: text, _csrf: csrf() })
    })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(ev => {
        textarea.value = '';
        textarea.disabled = false;
        const feed = document.getElementById('op-events');
        if (feed) {
          const el = document.createElement('div');
          el.className = 'event-item event-item--comment animate-in';
          el.innerHTML = `
            <span class="avatar avatar--sm" style="--seed:${ev.avatar_color||'#6366f1'}">${(ev.user_name||'A')[0]}</span>
            <div class="event-item__body">
              <span class="event-item__who">${ev.user_name||'Вы'}</span>
              <span class="event-item__text">${ev.message}</span>
            </div>
            <span class="event-item__time">только что</span>`;
          feed.prepend(el);
        }
      })
      .catch(() => { textarea.disabled = false; showToast('Ошибка', 'error'); });
  }

  /* Hook order rows: click on row title opens panel instead of navigating */
  function hookOrderRows() {
    document.querySelectorAll('[data-order-id]').forEach(trigger => {
      if (trigger.dataset.panelHooked) return;
      trigger.dataset.panelHooked = '1';
      trigger.addEventListener('click', e => {
        if (e.target.closest('a[href]:not([data-panel])') || e.target.closest('form') || e.target.closest('button')) return;
        e.preventDefault();
        loadOrderPanel(trigger.dataset.orderId);
      });
    });
  }

  /* ── Panel chat (CRM side-panel) ────────── */
  let panelChatLoaded = false;

  function initPanelChat(orderId) {
    if (!orderId) return;
    const msgs    = document.getElementById('panel-chat-messages');
    const form    = document.getElementById('panel-chat-form');
    const ta      = document.getElementById('panel-chat-textarea');
    if (!msgs) return;

    if (!panelChatLoaded) {
      panelChatLoaded = true;
      fetch(`/api/orders/${orderId}/messages`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(r => r.ok ? r.json() : [])
        .then(messages => {
          if (!messages.length) {
            msgs.innerHTML = '<div class="panel-chat__empty">Сообщений пока нет.</div>';
            return;
          }
          msgs.innerHTML = messages.map(m => buildMsgBubble(m)).join('');
          msgs.scrollTop = msgs.scrollHeight;
        })
        .catch(() => { msgs.innerHTML = '<div class="panel-chat__empty">Ошибка загрузки.</div>'; });
    }

    if (ta) {
      ta.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
      });
      ta.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.nativeEvent?.isComposing && e.keyCode !== 229) {
          e.preventDefault();
          form?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
      });
    }

    if (form && !form.dataset.bound) {
      form.dataset.bound = '1';
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = ta?.value.trim() || '';
        const fileInput = document.getElementById('panel-chat-file');
        if (!text && !fileInput?.files?.length) return;

        const fd = new FormData();
        fd.append('_csrf', csrf());
        fd.append('body', text);
        if (fileInput?.files?.length) fd.append('file', fileInput.files[0]);

        if (ta) ta.disabled = true;
        fetch(`/api/orders/${orderId}/messages`, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: fd
        })
          .then(r => r.ok ? r.json() : Promise.reject())
          .then(msg => {
            if (ta) { ta.value = ''; ta.style.height = ''; ta.disabled = false; }
            if (fileInput) fileInput.value = '';
            const el = document.createElement('div');
            el.innerHTML = buildMsgBubble(msg);
            msgs.append(el.firstElementChild);
            msgs.scrollTop = msgs.scrollHeight;
          })
          .catch(() => { if (ta) ta.disabled = false; showToast('Ошибка отправки', 'error'); });
      });
    }
  }

  function buildMsgBubble(m) {
    const isRight = !m.from_client;
    const filesHtml = (m.files || []).map(f =>
      `<a class="chat-msg__file" href="/uploads/${f.stored_name}" download="${f.original_name}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        ${f.original_name}
      </a>`
    ).join('');
    const avatar = isRight ? '' :
      `<span class="chat-msg__avatar" style="background:${m.sender_color||'#6366f1'}">${(m.sender_name||'A')[0]}</span>`;
    return `<div class="chat-msg ${isRight ? 'chat-msg--right' : ''}">
      ${avatar}
      <div class="chat-msg__bubble">
        ${!isRight && m.sender_name ? `<div class="chat-msg__name">${m.sender_name}</div>` : ''}
        ${m.body ? `<div class="chat-msg__text">${m.body.replace(/</g,'&lt;').replace(/\n/g,'<br>')}</div>` : ''}
        ${filesHtml ? `<div class="chat-msg__files">${filesHtml}</div>` : ''}
        <div class="chat-msg__time">${m.created_at}</div>
      </div>
    </div>`;
  }

  /* ── Toggle password ─────────────────────── */
  document.querySelectorAll('[data-role="toggle-pw"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.field__wrap').querySelector('input');
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });

  /* ── Charts ──────────────────────────────── */
  function initCharts() {
    if (typeof Chart === 'undefined') return;

    const palette = ['#6366f1','#38bdf8','#34d399','#fbbf24','#f472b6','#f87171','#a78bfa','#2dd4bf'];

    Chart.defaults.color = '#8891a8';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.05)';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size   = 12;

    /* Revenue bar chart */
    const revCanvas = document.getElementById('revenueChart');
    if (revCanvas) {
      const labels = (revCanvas.dataset.labels||'').split(',').filter(Boolean).map(l => {
        const [y, m] = l.split('-');
        return new Date(+y, +m - 1).toLocaleString('ru-RU', {month: 'short', year: '2-digit'});
      });
      const values = (revCanvas.dataset.values||'').split(',').filter(Boolean).map(Number);

      new Chart(revCanvas, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Выручка',
            data: values,
            backgroundColor: 'rgba(99,102,241,0.2)',
            borderColor: '#6366f1',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
            hoverBackgroundColor: 'rgba(99,102,241,0.35)',
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: true,
          animation: { duration: 800, easing: 'easeOutQuart' },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#1c2030',
              borderColor: 'rgba(255,255,255,.08)',
              borderWidth: 1,
              padding: 10,
              callbacks: { label: ctx => '  ' + Number(ctx.raw).toLocaleString('ru-RU') + ' ₽' }
            }
          },
          scales: {
            x: { grid: { display: false }, ticks: { maxRotation: 0 } },
            y: {
              grid: { color: 'rgba(255,255,255,0.04)' },
              ticks: { callback: v => v >= 1e6 ? (v/1e6).toFixed(1)+'M' : v >= 1e3 ? (v/1e3).toFixed(0)+'K' : v }
            }
          }
        }
      });
    }

    /* Donut charts */
    function buildDonut(id, opts = {}) {
      const canvas = document.getElementById(id);
      if (!canvas) return;
      const labels = (canvas.dataset.labels||'').split('|').filter(Boolean);
      const values = (canvas.dataset.values||'').split(',').filter(Boolean).map(Number);
      new Chart(canvas, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{ data: values, backgroundColor: palette, borderWidth: 2, borderColor: '#161922', hoverOffset: 6 }]
        },
        options: {
          responsive: true, cutout: '70%',
          animation: { animateRotate: true, duration: 900, easing: 'easeOutQuart' },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#1c2030',
              borderColor: 'rgba(255,255,255,.08)',
              borderWidth: 1,
              callbacks: { label: ctx => '  ' + ctx.label + ': ' + ctx.raw }
            },
            ...opts
          }
        }
      });
    }

    buildDonut('statusChart');
    buildDonut('currencyChart');
  }

  if (document.querySelector('canvas[id$="Chart"]')) {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js';
    s.onload = initCharts;
    document.head.appendChild(s);
  }

  /* ── Chat auto-resize textarea ───────────── */
  document.querySelectorAll('.chat-input-row textarea').forEach(ta => {
    ta.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
  });

  /* ── Init order row hooks on page ────────── */
  hookOrderRows();

  /* ── SVG helpers ─────────────────────────── */
  function svgSend() {
    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-11 11M22 2 15 22l-4-9-9-4 20-7z"/></svg>';
  }

  /* ── Money formatter ─────────────────────── */
  function fmtMoney(v) {
    const n = parseFloat(v) || 0;
    if (n === 0) return '0 ₽';
    if (Math.abs(n) >= 1e6) return (n/1e6).toFixed(2).replace('.', ',') + ' M ₽';
    if (Math.abs(n) >= 1e3) return (n/1e3).toFixed(0) + ' K ₽';
    return n.toLocaleString('ru-RU') + ' ₽';
  }

  /* ── File upload preview ─────────────────── */
  document.querySelectorAll('.upload-zone').forEach(zone => {
    const input = zone.querySelector('input[type="file"]');
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
      e.preventDefault(); zone.classList.remove('drag-over');
      if (input && e.dataTransfer.files.length) {
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
    zone.addEventListener('click', () => input?.click());
  });

  /* ── Cabinet chat auto-scroll ─────────────── */
  const chatMessages = document.querySelector('.chat-messages');
  if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

})();
