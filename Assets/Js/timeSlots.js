(() => {
  const $ = (sel, root = document) => root.querySelector(sel);

  const escapeHtml = (s) =>
    String(s ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');

  const moduleEl = () => $('#timeSlotsModule');
  const api = () => moduleEl()?.getAttribute('data-slots-api') || '';

  const state = { slots: [], loaded: false, loading: false, page: 1, perPage: 10, total: 0 };
  let mountedModule = null;
  const storageKey = 'timeslots:pager';
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

  const label = (slot) => `${slot.name} (${String(slot.start_time).slice(0, 5)}–${String(slot.end_time).slice(0, 5)})`;

  const assignedText = (n) => `Assigned (${n})`;

  const deleteBlockedMessage =
    'This time slot is assigned to one or more products. To delete it, you must either reassign the products to a different time slot or delete those products.';

  const popupEl = () => $('#timeSlotsPopup');
  const popupTitleEl = () => $('#timeSlotsPopupTitle');
  const popupBodyEl = () => $('#timeSlotsPopupBody');
  const popupOkBtn = () => $('[data-slots-popup-ok]', popupEl() || document);
  const popupCancelBtn = () => $('[data-slots-popup-cancel]', popupEl() || document);

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

    // replace handler each time
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
    p.querySelectorAll('[data-slots-popup-close]').forEach((el) => (el.onclick = closePopup));

    p.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  };

  const render = () => {
    const mod = moduleEl();
    if (!mod) return;
    const list = $('[data-slots-list]', mod);
    if (!list) return;

    if (state.slots.length === 0) {
      list.innerHTML = `<div class="products-empty">No time slots yet. Create one to start assigning products.</div>`;
      return;
    }

    list.innerHTML = state.slots
      .map((s) => {
        const active = Number(s.is_active) === 1;
        const assigned = Number(s.assigned_count || 0);
        const pill = active ? `<span class="pill pill--green">Active</span>` : `<span class="pill pill--amber">Disabled</span>`;
        const badge = assigned > 0 ? `<span class="pill pill--amber" title="${escapeHtml(assignedText(assigned))}">${escapeHtml(assignedText(assigned))}</span>` : '';
        const delTitle = assigned > 0 ? deleteBlockedMessage : 'Delete time slot';
        return `
          <div class="dash-table__row dash-table__row--5 products-row" data-id="${escapeHtml(s.id)}">
            <div class="products-name">
              <div class="products-name__title">${escapeHtml(s.name)}</div>
              <div class="products-name__desc">${escapeHtml(label(s))}</div>
            </div>
            <div>${escapeHtml(String(s.start_time).slice(0, 5))}</div>
            <div>${escapeHtml(String(s.end_time).slice(0, 5))}</div>
            <div>${pill} ${badge}</div>
            <div class="products-actions">
              <button type="button" class="btn-mini" data-slots-edit>Edit</button>
              <label class="toggle" title="${active ? 'Deactivate' : 'Activate'}">
                <input type="checkbox" ${active ? 'checked' : ''} data-slots-toggle />
                <span class="toggle__track" aria-hidden="true"><span class="toggle__thumb"></span></span>
              </label>
              <button type="button" class="btn-mini danger" data-slots-delete ${assigned > 0 ? 'disabled' : ''} title="${escapeHtml(delTitle)}">Delete</button>
            </div>
          </div>
        `;
      })
      .join('');
  };

  const load = async () => {
    const mod = moduleEl();
    if (state.loading || !mod || !api()) return;

    // If dashboard swapped DOM, treat as a fresh mount
    if (mountedModule !== mod) {
      mountedModule = mod;
      state.loaded = false;
    }

    state.loading = true;
    try {
      const list = $('[data-slots-list]', mod);
      if (list) list.innerHTML = `<div class="products-empty">Loading time slots…</div>`;
      const pager = $('[data-slots-pager]', mod);
      if (pager) pager.innerHTML = '';

      const res = await fetch(`${api()}?action=list&page=${encodeURIComponent(state.page)}&per_page=${encodeURIComponent(state.perPage)}`, {
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      state.slots = data.timeSlots || [];
      state.total = Number(data?.pagination?.total || 0);
      state.page = Number(data?.pagination?.page || state.page) || state.page;
      state.perPage = Number(data?.pagination?.per_page || state.perPage) || state.perPage;
      savePagerState();
      render();
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
      state.loaded = true;
    } catch (e) {
      console.error(e);
      const mod = moduleEl();
      const list = mod ? $('[data-slots-list]', mod) : null;
      if (list) list.innerHTML = `<div class="products-empty">Failed to load time slots.</div>`;
    } finally {
      state.loading = false;
    }
  };

  const modal = () => $('#timeSlotModal');
  const form = () => $('#timeSlotForm');

  const openModal = (mode, slot = null) => {
    const m = modal();
    const f = form();
    if (!m || !f) return;
    $('#timeSlotModalTitle')?.replaceChildren(document.createTextNode(mode === 'edit' ? 'Update time slot' : 'Create time slot'));
    f.reset();
    f.querySelector('input[name="id"]').value = '';
    f.querySelector('input[name="is_active"]').checked = true;
    if (slot) {
      f.querySelector('input[name="id"]').value = slot.id;
      f.querySelector('input[name="name"]').value = slot.name ?? '';
      f.querySelector('input[name="start_time"]').value = String(slot.start_time).slice(0, 5);
      f.querySelector('input[name="end_time"]').value = String(slot.end_time).slice(0, 5);
      f.querySelector('input[name="is_active"]').checked = Number(slot.is_active) === 1;
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

  const apiPost = async (action, fd) => {
    const res = await fetch(`${api()}?action=${encodeURIComponent(action)}`, {
      method: 'POST',
      credentials: 'same-origin',
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) throw new Error(data?.message || `Request failed (${res.status})`);
    return data;
  };

  const saveFromForm = async () => {
    const f = form();
    if (!f) return;
    const id = f.querySelector('input[name="id"]').value.trim();
    const fd = new FormData(f);
    fd.set('is_active', f.querySelector('input[name="is_active"]').checked ? '1' : '0');
    await apiPost(id ? 'update' : 'create', fd);
    closeModal();
    await load();
    document.dispatchEvent(new CustomEvent('timeslots:changed'));
  };

  document.addEventListener('click', async (e) => {
    if (e.target.closest('[data-slots-refresh]')) {
      e.preventDefault();
      state.loaded = false;
      await load();
      return;
    }
    if (e.target.closest('[data-slots-open]')) {
      e.preventDefault();
      await load();
      openModal('create');
      return;
    }
    if (e.target.closest('[data-slots-close]')) {
      e.preventDefault();
      closeModal();
      return;
    }
    if (e.target.closest('[data-slots-save]')) {
      e.preventDefault();
      try {
        await saveFromForm();
      } catch (err) {
        alert(err.message || 'Save failed');
      }
      return;
    }

    const row = e.target.closest('#timeSlotsModule .products-row');
    if (!row) return;
    const id = row.getAttribute('data-id');
    const slot = state.slots.find((s) => String(s.id) === String(id));

    if (e.target.closest('[data-slots-edit]')) {
      e.preventDefault();
      if (slot) openModal('edit', slot);
      return;
    }
    if (e.target.closest('[data-slots-delete]')) {
      e.preventDefault();
      if (slot && Number(slot.assigned_count || 0) > 0) {
        openPopup({ title: 'Cannot delete time slot', message: deleteBlockedMessage });
        return;
      }
      openPopup({
        title: 'Delete time slot?',
        message: 'Are you sure you want to delete this time slot? This cannot be undone.',
        confirm: true,
        okText: 'Delete',
        cancelText: 'Cancel',
        onOk: async () => {
          const fd = new FormData();
          fd.set('id', id);
          try {
            await apiPost('delete', fd);
            if (state.slots.length <= 1 && state.page > 1) state.page -= 1;
            await load();
            document.dispatchEvent(new CustomEvent('timeslots:changed'));
          } catch (err) {
            openPopup({ title: 'Delete failed', message: err.message || 'Failed' });
          }
        },
      });
    }

    const toggleInput = e.target.closest('input[data-slots-toggle]');
    if (toggleInput) {
      try {
        const fd = new FormData();
        fd.set('id', id);
        fd.set('is_active', toggleInput.checked ? '1' : '0');
        await apiPost('toggle', fd);
        await load();
        document.dispatchEvent(new CustomEvent('timeslots:changed'));
      } catch (err) {
        // revert UI if failed
        toggleInput.checked = !toggleInput.checked;
        alert(err.message || 'Failed');
      }
    }
  });

  // Capture submit early so the page never reloads
  document.addEventListener('submit', async (e) => {
    if (!e.target || e.target.id !== 'timeSlotForm') return;
    e.preventDefault();
    e.stopPropagation();
    try {
      await saveFromForm();
    } catch (err) {
      alert(err.message || 'Save failed');
    }
  }, true);

  const boot = () => {
    const mod = moduleEl();
    if (!mod) return;
    // Always load on mount; load() will early-exit if already loading
    load();
  };

  boot();
  document.addEventListener('timeslots:mounted', () => {
    state.loaded = false;
    boot();
  });
})();

