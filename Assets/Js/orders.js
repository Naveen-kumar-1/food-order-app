(() => {
  const $ = (sel, root = document) => root.querySelector(sel);

  const moduleEl = () => $('#ordersModule');
  const api = () => moduleEl()?.getAttribute('data-orders-api') || '';

  const fmt = (n) => `₹${Number(n).toFixed(2).replace(/\.00$/, '')}`;
  const pill = (s) =>
    s === 'Served' ? 'pill--green' : s === 'Completed' ? 'pill--green' : s === 'Preparing' ? 'pill--amber' : '';

  const state = {
    page: 1,
    perPage: 10,
    total: 0,
    mode: 'date', // 'date' | 'range'
    date: '', // YYYY-MM-DD
    from: '',
    to: '',
  };
  const storageKey = 'orders:state';
  const loadPagerState = () => {
    try {
      const raw = sessionStorage.getItem(storageKey);
      const v = raw ? JSON.parse(raw) : null;
      if (v && typeof v === 'object') {
        state.page = Math.max(1, Number(v.page || state.page));
        state.perPage = Math.max(1, Math.min(50, Number(v.perPage || state.perPage)));
        state.mode = v.mode === 'range' ? 'range' : 'date';
        state.date = String(v.date || state.date || '');
        state.from = String(v.from || state.from || '');
        state.to = String(v.to || state.to || '');
      }
    } catch {}
  };
  const savePagerState = () => {
    try {
      sessionStorage.setItem(
        storageKey,
        JSON.stringify({
          page: state.page,
          perPage: state.perPage,
          mode: state.mode,
          date: state.date,
          from: state.from,
          to: state.to,
        })
      );
    } catch {}
  };
  loadPagerState();

  const todayStr = () => new Date().toISOString().slice(0, 10);
  const shiftDay = (days) => {
    const d = new Date();
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
  };
  const ensureDefaults = () => {
    if (state.mode === 'range') {
      if (!state.from || !state.to) {
        state.mode = 'date';
        state.date = todayStr();
      }
    } else {
      if (!state.date) state.date = todayStr();
    }
  };
  ensureDefaults();

  const popupEl = () => $('#ordersPopup');
  const popupTitleEl = () => $('#ordersPopupTitle');
  const popupBodyEl = () => $('#ordersPopupBody');
  const popupOkBtn = () => $('[data-orders-popup-ok]', popupEl() || document);
  const popupCancelBtn = () => $('[data-orders-popup-cancel]', popupEl() || document);

  const closePopup = () => {
    const p = popupEl();
    if (!p) return;
    p.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  };

  const openPopup = ({ title, message, confirm = false, okText = 'OK', cancelText = 'Cancel', onOk = null }) => {
    const p = popupEl();
    if (!p) return;
    if (popupTitleEl()) popupTitleEl().textContent = title || 'Message';
    if (popupBodyEl()) popupBodyEl().textContent = message || '';

    const okBtn = popupOkBtn();
    const cancelBtn = popupCancelBtn();
    if (okBtn) okBtn.textContent = okText;
    if (cancelBtn) {
      cancelBtn.textContent = cancelText;
      cancelBtn.style.display = confirm ? '' : 'none';
    }

    if (okBtn) {
      okBtn.onclick = async () => {
        try {
          if (typeof onOk === 'function') await onOk();
        } finally {
          closePopup();
        }
      };
    }
    if (cancelBtn) cancelBtn.onclick = closePopup;
    p.querySelectorAll('[data-orders-popup-close]').forEach((el) => (el.onclick = closePopup));

    p.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  };

  const setLabel = () => {
    const mod = moduleEl();
    if (!mod) return;
    const label = $('[data-orders-date-label]', mod);
    if (!label) return;
    if (state.mode === 'range') label.textContent = `Orders for: ${state.from} → ${state.to}`;
    else label.textContent = `Orders for: ${state.date}`;
  };

  const syncDateInput = () => {
    const mod = moduleEl();
    if (!mod) return;
    const inp = $('[data-orders-date]', mod);
    if (!inp) return;
    inp.value = state.mode === 'date' ? state.date : state.to || state.from || '';
  };

  const groupByDate = (rows) => {
    const groups = new Map(); // date => []
    rows.forEach((o) => {
      const ts = String(o.created_at || '');
      const dateKey = ts ? ts.slice(0, 10) : 'Unknown';
      if (!groups.has(dateKey)) groups.set(dateKey, []);
      groups.get(dateKey).push(o);
    });
    return Array.from(groups.entries()); // keeps input order
  };

  const load = async () => {
    const mod = moduleEl();
    if (!mod || !api()) return;
    const list = $('[data-orders-list]', mod);
    if (!list) return;
    list.innerHTML = `<div class="products-empty">Loading…</div>`;
    const pager = $('[data-orders-pager]', mod);
    if (pager) pager.innerHTML = '';

    setLabel();
    syncDateInput();

    const qs = new URLSearchParams();
    qs.set('action', 'list');
    qs.set('page', String(state.page));
    qs.set('per_page', String(state.perPage));
    qs.set('include_items', '1');
    if (state.mode === 'range') {
      qs.set('from', state.from);
      qs.set('to', state.to);
    } else {
      qs.set('date', state.date);
    }

    const res = await fetch(`${api()}?${qs.toString()}`, { credentials: 'same-origin', cache: 'no-store' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      list.innerHTML = `<div class="products-empty">Failed to load orders.</div>`;
      return;
    }
    const rows = data.orders || [];
    const itemsByOrder = data.itemsByOrder || {};
    state.total = Number(data?.pagination?.total || 0);
    state.page = Number(data?.pagination?.page || state.page) || state.page;
    state.perPage = Number(data?.pagination?.per_page || state.perPage) || state.perPage;
    savePagerState();

    if (!rows.length) {
      list.innerHTML = `<div class="products-empty">No orders found for selected date.</div>`;
      if (pager && window.__Pager__?.render) {
        window.__Pager__.render(pager, {
          page: state.page,
          perPage: state.perPage,
          total: state.total,
          onPage: (p) => {
            state.page = Math.max(1, p);
            savePagerState();
            load();
          },
          onPerPage: (n) => {
            state.perPage = Math.max(1, Math.min(50, n));
            state.page = 1;
            savePagerState();
            load();
          },
        });
      }
      return;
    }

    const groups = groupByDate(rows);
    list.innerHTML = groups
      .map(([dateKey, items]) => {
        const header = `
          <div class="dash-table__row dash-table__row--5" style="background: rgba(26,18,8,0.03); font-weight: 900;">
            <div>${dateKey}</div>
            <div></div><div></div><div></div><div></div>
          </div>
        `;
        const body = items
          .map(
            (o) => `
          <div class="dash-table__row dash-table__row--5 products-row">
            <div class="products-name">
              <div class="products-name__title">#${o.id}</div>
              <div class="products-name__desc">Table ${o.table_id}</div>
              ${
                (itemsByOrder[String(o.id)] || []).length
                  ? `<div class="products-name__desc" style="margin-top:6px;">
                      ${(itemsByOrder[String(o.id)] || [])
                        .map((it) => `${it.name} × ${it.qty}`)
                        .join(' • ')}
                    </div>`
                  : ``
              }
            </div>
            <div>Table ${o.table_id}</div>
            <div><span class="pill ${pill(o.status)}">${o.status}</span></div>
            <div>${fmt(o.total_amount)}</div>
            <div class="products-actions-col" style="display:flex; gap: 8px; justify-content:flex-end; flex-wrap: wrap;">
              <span style="color: var(--muted); font-size: 12px; align-self:center;">${o.created_at}</span>
              <button type="button" class="btn-mini danger" data-order-delete="${o.id}">Delete</button>
            </div>
          </div>
        `
          )
          .join('');
        return header + body;
      })
      .join('');

    if (pager && window.__Pager__?.render) {
      window.__Pager__.render(pager, {
        page: state.page,
        perPage: state.perPage,
        total: state.total,
        onPage: (p) => {
          state.page = Math.max(1, p);
          savePagerState();
          load();
        },
        onPerPage: (n) => {
          state.perPage = Math.max(1, Math.min(50, n));
          state.page = 1;
          savePagerState();
          load();
        },
      });
    }
  };

  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-orders-refresh]')) {
      e.preventDefault();
      load();
    }
    if (e.target.closest('[data-orders-today]')) {
      e.preventDefault();
      state.mode = 'date';
      state.date = todayStr();
      state.page = 1;
      savePagerState();
      load();
    }
    if (e.target.closest('[data-orders-yesterday]')) {
      e.preventDefault();
      state.mode = 'date';
      state.date = shiftDay(-1);
      state.page = 1;
      savePagerState();
      load();
    }
    if (e.target.closest('[data-orders-last7]')) {
      e.preventDefault();
      state.mode = 'range';
      state.from = shiftDay(-6);
      state.to = todayStr();
      state.page = 1;
      savePagerState();
      load();
    }
    if (e.target.closest('[data-orders-reset]')) {
      e.preventDefault();
      state.mode = 'date';
      state.date = todayStr();
      state.from = '';
      state.to = '';
      state.page = 1;
      savePagerState();
      load();
    }

    const delBtn = e.target.closest('[data-order-delete]');
    if (delBtn) {
      e.preventDefault();
      const id = Number(delBtn.getAttribute('data-order-delete'));
      if (!id) return;
      openPopup({
        title: 'Delete order?',
        message: 'Are you sure you want to delete this order? This cannot be undone.',
        confirm: true,
        okText: 'Delete',
        cancelText: 'Cancel',
        onOk: async () => {
          const fd = new FormData();
          fd.set('action', 'delete');
          fd.set('order_id', String(id));
          const res = await fetch(api(), { method: 'POST', credentials: 'same-origin', body: fd });
          const data = await res.json().catch(() => ({}));
          if (!res.ok || !data.success) {
            openPopup({ title: 'Delete failed', message: data.message || 'Failed' });
            return;
          }
          if (state.total > 0) state.total -= 1;
          if (state.page > 1 && state.total <= (state.page - 1) * state.perPage) state.page -= 1;
          savePagerState();
          load();
        },
      });
    }
  });

  document.addEventListener('change', (e) => {
    const mod = moduleEl();
    if (!mod) return;
    const inp = e.target?.closest?.('[data-orders-date]');
    if (!inp) return;
    const v = String(inp.value || '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return;
    state.mode = 'date';
    state.date = v;
    state.from = '';
    state.to = '';
    state.page = 1;
    savePagerState();
    load();
  });

  const boot = () => {
    if (!moduleEl()) return;
    load();
  };

  boot();
  document.addEventListener('orders:mounted', boot);
})();

