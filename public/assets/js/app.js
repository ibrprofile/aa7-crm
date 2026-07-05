/* AA7 CRM — main application script */

(function () {
  'use strict';

  /* ── Sidebar toggle (mobile) ────────────── */
  const sidebar  = document.querySelector('[data-role="sidebar"]');
  const menuBtn  = document.querySelector('[data-role="menu-toggle"]');
  const scrim    = document.querySelector('[data-role="scrim"]');

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

  menuBtn?.addEventListener('click', () => {
    sidebar?.classList.contains('is-open') ? closeSidebar() : openSidebar();
  });
  scrim?.addEventListener('click', closeSidebar);

  /* ── Global keyboard shortcut: / → focus search ─── */
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) {
      e.preventDefault();
      document.querySelector('[data-role="global-search"]')?.focus();
    }
    if (e.key === 'Escape') {
      closeSidebar();
      document.querySelectorAll('[data-role="modal"]').forEach(m => m.style.display = 'none');
    }
  });

  /* ── Global search (basic navigation) ─────────── */
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

  /* ── Toast auto-dismiss ──────────────────────── */
  document.querySelectorAll('[data-role="toast"]').forEach(toast => {
    const closeBtn = toast.querySelector('[data-role="toast-close"]');
    closeBtn?.addEventListener('click', () => toast.remove());
    setTimeout(() => toast.classList.add('toast--fade'), 4000);
    setTimeout(() => toast.remove(), 4600);
  });

  /* ── Modal system ────────────────────────────── */
  document.querySelectorAll('[data-role="modal-trigger"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modalId = btn.dataset.modal;
      const modal = document.getElementById(modalId);
      if (modal) modal.style.display = 'flex';
    });
  });

  document.querySelectorAll('[data-role="modal-close"]').forEach(el => {
    el.addEventListener('click', () => {
      el.closest('[data-role="modal"]').style.display = 'none';
    });
  });

  /* ── Toggle password visibility ──────────────── */
  document.querySelectorAll('[data-role="toggle-pw"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.field__wrap').querySelector('input');
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });

  /* ── Charts (Chart.js from CDN, loaded lazily) ─ */
  function initCharts() {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.color = '#8fa898';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
    Chart.defaults.font.family = "'Inter', sans-serif";

    /* Revenue bar chart */
    const revCanvas = document.getElementById('revenueChart');
    if (revCanvas) {
      const labels = revCanvas.dataset.labels?.split(',').map(l => {
        const [y, m] = l.split('-');
        return new Date(+y, +m - 1).toLocaleString('ru-RU', {month: 'short', year: '2-digit'});
      }) || [];
      const values = revCanvas.dataset.values?.split(',').map(Number) || [];

      new Chart(revCanvas, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Выручка',
            data: values,
            backgroundColor: 'rgba(62,207,142,0.25)',
            borderColor: '#3ecf8e',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { display: false }, tooltip: {
            callbacks: {
              label: ctx => ' ' + Number(ctx.raw).toLocaleString('ru-RU') + ' ₽'
            }
          }},
          scales: {
            x: { grid: { display: false } },
            y: { grid: { color: 'rgba(255,255,255,0.04)' },
              ticks: { callback: v => v >= 1e6 ? (v/1e6).toFixed(1)+'M' : v >= 1e3 ? (v/1e3).toFixed(0)+'K' : v }
            }
          }
        }
      });
    }

    /* Status donut chart */
    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas) {
      const labels = statusCanvas.dataset.labels?.split('|') || [];
      const values = statusCanvas.dataset.values?.split(',').map(Number) || [];
      const palette = ['#4f8ef7','#f59e0b','#3ecf8e','#8b5cf6','#14b8a6','#ef4444'];

      new Chart(statusCanvas, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{ data: values, backgroundColor: palette, borderWidth: 2, borderColor: '#181f1c', hoverOffset: 4 }]
        },
        options: {
          responsive: true, cutout: '68%',
          plugins: { legend: { display: false }, tooltip: { callbacks: {
            label: ctx => ' ' + ctx.label + ': ' + ctx.raw
          }}}
        }
      });
    }

    /* Currency donut chart */
    const currCanvas = document.getElementById('currencyChart');
    if (currCanvas) {
      const labels = currCanvas.dataset.labels?.split('|') || [];
      const values = currCanvas.dataset.values?.split(',').map(Number) || [];
      const palette = ['#3ecf8e','#4f8ef7','#f59e0b','#8b5cf6'];

      new Chart(currCanvas, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{ data: values, backgroundColor: palette, borderWidth: 2, borderColor: '#181f1c', hoverOffset: 4 }]
        },
        options: { responsive: true, cutout: '68%', plugins: { legend: { display: false } } }
      });
    }
  }

  /* Load Chart.js from CDN only when a canvas is present */
  if (document.querySelector('canvas[id$="Chart"]')) {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js';
    script.onload = initCharts;
    document.head.appendChild(script);
  }

  /* ── Toast fade CSS ──────────────────────────── */
  const fadeStyle = document.createElement('style');
  fadeStyle.textContent = '.toast--fade { transition: opacity .5s; opacity: 0; }';
  document.head.appendChild(fadeStyle);

})();
