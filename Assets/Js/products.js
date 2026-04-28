(() => {
  const state = {
    products: [],
    timeSlots: {},
    loaded: false,
    loading: false,
    mountEl: null,
    page: 1,
    perPage: 10,
    total: 0,
  };

  const storageKey = 'products:pager';
  const loadPagerState = () => {
    try {
      const raw = sessionStorage.getItem(storageKey);
      const v = raw ? JSON.parse(raw) : null;
      if (v && typeof v === 'object') {
        state.page = Math.max(1, Number(v.page || state.page));
        state.perPage = Math.max(1, Math.min(50, Number(v.perPage || state.perPage)));
      }
    } catch {}
  };
  const savePagerState = () => {
    try {
      sessionStorage.setItem(storageKey, JSON.stringify({ page: state.page, perPage: state.perPage }));
    } catch {}
  };
  loadPagerState();

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const fmtMoney = (n) => {
    const v = Number(n);
    if (Number.isNaN(v)) return `${n}`;
    return `₹${v.toFixed(2).replace(/\.00$/, '')}`;
  };

  const escapeHtml = (s) =>
    String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');

  const getModule = () => $('#productsModule');
  const getApi = () => getModule()?.getAttribute('data-products-api') || '';

  const getFilters = () => {
    const mod = getModule();
    return {
      q: ($( '[data-products-search]', mod)?.value || '').trim().toLowerCase(),
      slot: $( '[data-products-slot]', mod)?.value || '',
      status: $( '[data-products-status]', mod)?.value || '',
    };
  };

  const applyFilters = (items) => {
    const { q, slot, status } = getFilters();
    return items.filter((p) => {
      const hay = `${p.name} ${p.description ?? ''}`.toLowerCase();
      if (q && !hay.includes(q)) return false;
      if (slot && String(p.time_slot_id) !== String(slot)) return false;
      if (status !== '' && String(p.is_enabled) !== String(status)) return false;
      return true;
    });
  };

  const render = () => {
    const mod = getModule();
    if (!mod) return;

    const list = $('[data-products-list]', mod);
    if (!list) return;

    const rows = applyFilters(state.products);
    if (rows.length === 0) {
      list.innerHTML = `<div class="products-empty">No products found.</div>`;
      return;
    }

    list.innerHTML = rows
      .map((p) => {
        const enabled = Number(p.is_enabled) === 1;
        const pill = enabled
          ? `<span class="pill pill--green">Enabled</span>`
          : `<span class="pill pill--amber">Disabled</span>`;
        const slotLabel = state.timeSlots[String(p.time_slot_id)] || p.time_slot || '';

        return `
          <div class="dash-table__row dash-table__row--5 products-row" data-id="${escapeHtml(p.id)}">
            <div class="products-name">
              <div class="products-name__title">${escapeHtml(p.name)}</div>
              ${p.description ? `<div class="products-name__desc">${escapeHtml(p.description)}</div>` : ``}
            </div>
            <div>${escapeHtml(slotLabel)}</div>
            <div>${escapeHtml(fmtMoney(p.price))}</div>
            <div>${pill}</div>
            <div class="products-actions">
              <button type="button" class="btn-mini" data-products-edit>Edit</button>
              <label class="toggle" title="${enabled ? 'Disable' : 'Enable'}">
                <input type="checkbox" ${enabled ? 'checked' : ''} data-products-toggle />
                <span class="toggle__track" aria-hidden="true"><span class="toggle__thumb"></span></span>
              </label>
              <button type="button" class="btn-mini danger" data-products-delete>Delete</button>
            </div>
          </div>
        `;
      })
      .join('');
  };

  const fillTimeSlots = () => {
    const mod = getModule();
    if (!mod) return;

    const slotSelect = $('[data-products-slot]', mod);
    // Modal lives outside #productsModule, so query it from the document
    const formSlotSelect = document.querySelector('#productModal [data-products-form-slot]') || document.querySelector('[data-products-form-slot]');
    if (!slotSelect || !formSlotSelect) return;

    // Keep current selection if possible
    const curFilter = slotSelect.value;
    const curForm = formSlotSelect.value;

    const options = Object.entries(state.timeSlots)
      .map(([key, label]) => `<option value="${escapeHtml(key)}">${escapeHtml(label)}</option>`)
      .join('');

    slotSelect.innerHTML = `<option value="">All time slots</option>${options}`;
    formSlotSelect.innerHTML = `<option value="">Select time slot</option>${options}`;

    if (curFilter) slotSelect.value = curFilter;
    if (curForm) formSlotSelect.value = curForm;
  };

  const apiCall = async (action, formData) => {
    const api = getApi();
    if (!api) throw new Error('Missing products API');

    const res = await fetch(`${api}?action=${encodeURIComponent(action)}`, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      const msg = data?.message || `Request failed (${res.status})`;
      throw new Error(msg);
    }
    return data;
  };

  const loadList = async () => {
    const mod = getModule();
    if (!mod || state.loading) return;

    // If dashboard swapped DOM, treat as a fresh mount
    if (state.mountEl !== mod) {
      state.mountEl = mod;
      state.loaded = false;
    }

    state.loading = true;
    const list = $('[data-products-list]', mod);
    if (list) list.innerHTML = `<div class="products-empty">Loading products…</div>`;
    const pager = $('[data-products-pager]', mod);
    if (pager) pager.innerHTML = '';

    const api = getApi();
    try {
      const res = await fetch(`${api}?action=list&page=${encodeURIComponent(state.page)}&per_page=${encodeURIComponent(state.perPage)}`, {
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!res.ok) {
        if (res.status === 401) {
          const base = window.__APP_BASE__ || '';
          window.location.href = `${base}/index.php`;
          return;
        }
        throw new Error(`HTTP ${res.status}`);
      }

      const data = await res.json();
      state.products = data.products || [];
      state.timeSlots = data.timeSlots || {};
      state.total = Number(data?.pagination?.total || 0);
      state.page = Number(data?.pagination?.page || state.page) || state.page;
      state.perPage = Number(data?.pagination?.per_page || state.perPage) || state.perPage;
      savePagerState();
      fillTimeSlots();
      render();
      if (pager && window.__Pager__?.render) {
        window.__Pager__.render(pager, {
          page: state.page,
          perPage: state.perPage,
          total: state.total,
          onPage: (p) => {
            state.page = Math.max(1, p);
            savePagerState();
            loadList();
          },
          onPerPage: (n) => {
            state.perPage = Math.max(1, Math.min(50, n));
            state.page = 1;
            savePagerState();
            loadList();
          },
        });
      }
      state.loaded = true;
    } catch (e) {
      console.error(e);
      if (list) list.innerHTML = `<div class="products-empty">Failed to load products.</div>`;
    } finally {
      state.loading = false;
    }
  };

  // If time slots are changed, refresh products list (dropdown + labels)
  document.addEventListener('timeslots:changed', () => {
    state.loaded = false;
    loadList();
  });

  // Modal
  const modal = () => $('#productModal');
  const form = () => $('#productForm');

  const openModal = (mode, product = null) => {
    const m = modal();
    const f = form();
    if (!m || !f) return;

    $('#productModalTitle')?.replaceChildren(document.createTextNode(mode === 'edit' ? 'Update product' : 'Create product'));
    f.reset();
    f.querySelector('input[name="id"]').value = '';
    f.querySelector('input[name="is_enabled"]').checked = true;

    if (product) {
      f.querySelector('input[name="id"]').value = product.id;
      f.querySelector('input[name="name"]').value = product.name ?? '';
      f.querySelector('input[name="price"]').value = product.price ?? '';
      f.querySelector('textarea[name="description"]').value = product.description ?? '';
      f.querySelector('select[name="time_slot_id"]').value = product.time_slot_id ?? '';
      f.querySelector('input[name="is_enabled"]').checked = Number(product.is_enabled) === 1;
    }

    m.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    f.querySelector('input[name="name"]')?.focus();
  };

  const closeModal = () => {
    const m = modal();
    if (!m) return;
    m.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  };

  const saveFromForm = async () => {
    const f = form();
    if (!f) return;
    const id = f.querySelector('input[name="id"]').value.trim();
    const fd = new FormData(f);

    // normalize checkbox
    const enabled = f.querySelector('input[name="is_enabled"]').checked ? '1' : '0';
    fd.set('is_enabled', enabled);

    // Basic validation (since we now use novalidate)
    const name = (f.querySelector('input[name="name"]')?.value || '').trim();
    const price = (f.querySelector('input[name="price"]')?.value || '').trim();
    const slotId = (f.querySelector('select[name="time_slot_id"]')?.value || '').trim();
    if (!name || !price || !slotId) {
      throw new Error('Name, price and time slot are required');
    }

    await apiCall(id ? 'update' : 'create', fd);
    closeModal();
    await loadList();
  };

  const getProduct = (id) => state.products.find((p) => String(p.id) === String(id));

  // Global event delegation (works after tab fetch inject)
  document.addEventListener('click', async (e) => {
    const refreshBtn = e.target.closest('[data-products-refresh]');
    if (refreshBtn) {
      e.preventDefault();
      state.loaded = false;
      await loadList();
      return;
    }
    const openBtn = e.target.closest('[data-products-open]');
    if (openBtn) {
      e.preventDefault();
      await loadList();
      openModal('create');
      return;
    }

    if (e.target.closest('[data-products-close]')) {
      e.preventDefault();
      closeModal();
      return;
    }

    if (e.target.closest('[data-products-save]')) {
      e.preventDefault();
      try {
        await saveFromForm();
      } catch (err) {
        alert(err.message || 'Save failed');
      }
      return;
    }

    const row = e.target.closest('.products-row');
    if (!row) return;

    const id = row.getAttribute('data-id');

    if (e.target.closest('[data-products-edit]')) {
      e.preventDefault();
      await loadList();
      const p = getProduct(id);
      if (p) openModal('edit', p);
      return;
    }

    if (e.target.closest('[data-products-delete]')) {
      e.preventDefault();
      if (!confirm('Delete this product?')) return;
      try {
        const fd = new FormData();
        fd.set('id', id);
        await apiCall('delete', fd);
        // If we deleted the last item on the page, step back one page.
        if (state.products.length <= 1 && state.page > 1) state.page -= 1;
        await loadList();
      } catch (err) {
        alert(err.message || 'Delete failed');
      }
      return;
    }

    const toggleInput = e.target.closest('input[data-products-toggle]');
    if (toggleInput) {
      try {
        const fd = new FormData();
        fd.set('id', id);
        fd.set('is_enabled', toggleInput.checked ? '1' : '0');
        await apiCall('toggle', fd);
        await loadList();
      } catch (err) {
        toggleInput.checked = !toggleInput.checked;
        alert(err.message || 'Update failed');
      }
    }
  });

  document.addEventListener('input', (e) => {
    const mod = getModule();
    if (!mod) return;
    if (e.target.matches('[data-products-search]')) render();
  });

  document.addEventListener('change', (e) => {
    const mod = getModule();
    if (!mod) return;
    if (e.target.matches('[data-products-slot], [data-products-status]')) render();
  });

  // Capture submit early so the page never reloads (e.g., Enter key)
  document.addEventListener('submit', async (e) => {
    if (!e.target || e.target.id !== 'productForm') return;
    e.preventDefault();
    e.stopPropagation();

    try {
      await saveFromForm();
    } catch (err) {
      alert(err.message || 'Save failed');
    }
  }, true);

  // Initial load when Products tab is injected
  const boot = () => {
    const mod = getModule();
    if (!mod) return;
    // Always load on mount; loadList will fast-exit if already loading.
    loadList();
  };

  // Run now + after dashboard swaps content
  boot();
  document.addEventListener('products:mounted', () => {
    // Navigation back to Products should always render immediately
    state.loaded = false;
    boot();
  });
})();

