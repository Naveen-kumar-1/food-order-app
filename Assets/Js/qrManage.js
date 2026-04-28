(() => {
  const $ = (sel, root = document) => root.querySelector(sel);

  const moduleEl = () => $('#qrModule');
  const api = () => moduleEl()?.getAttribute('data-qr-api') || '';
  const orderBase = () => moduleEl()?.getAttribute('data-order-base') || '';

  const popupEl = () => $('#qrPopup');
  const popupTitleEl = () => $('#qrPopupTitle');
  const popupBodyEl = () => $('#qrPopupBody');
  const popupOkBtn = () => $('[data-qr-popup-ok]', popupEl() || document);
  const popupCancelBtn = () => $('[data-qr-popup-cancel]', popupEl() || document);

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
    p.querySelectorAll('[data-qr-popup-close]').forEach((el) => (el.onclick = closePopup));

    p.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  };

  const qrImgUrl = (data) =>
    `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(data)}`;

  const render = (tables) => {
    const mod = moduleEl();
    if (!mod) return;
    const list = $('[data-qr-list]', mod);
    if (!list) return;

    if (!tables || tables.length === 0) {
      list.innerHTML = `<div class="products-empty">No tables yet. Generate to create QR codes.</div>`;
      return;
    }

    const base = orderBase();
    list.innerHTML = tables
      .map((t) => {
        const url = `${base}?table_id=${encodeURIComponent(t.id)}&token=${encodeURIComponent(t.token)}`;
        return `
          <div class="dash-table__row dash-table__row--5 products-row">
            <div class="products-name">
              <div class="products-name__title">Table ${String(t.table_number)}</div>
              <div class="products-name__desc">ID: ${String(t.id)}</div>
            </div>
            <div style="word-break: break-all; font-size: 12px; color: var(--muted);">${url}</div>
            <div><img src="${qrImgUrl(url)}" alt="QR for Table ${String(t.table_number)}" width="64" height="64" style="border-radius: 10px; border: 1px solid var(--border);" /></div>
            <div><button type="button" class="btn-mini" data-qr-copy="${encodeURIComponent(url)}">Copy</button></div>
            <div class="products-actions">
              <a class="btn-mini" href="${qrImgUrl(url)}" download="table-${String(t.table_number)}.png">Download</a>
              <button type="button" class="btn-mini danger" data-qr-delete="${String(t.id)}">Delete</button>
            </div>
          </div>
        `;
      })
      .join('');
  };

  const load = async () => {
    const mod = moduleEl();
    if (!mod || !api()) return;
    const list = $('[data-qr-list]', mod);
    if (list) list.innerHTML = `<div class="products-empty">Loading…</div>`;

    const res = await fetch(`${api()}?action=list`, { credentials: 'same-origin', cache: 'no-store' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      if (list) list.innerHTML = `<div class="products-empty">Failed to load tables.</div>`;
      return;
    }
    render(data.tables || []);
  };

  const generate = async () => {
    const mod = moduleEl();
    if (!mod || !api()) return;
    const count = Number($('[data-qr-count]', mod)?.value || 0);
    if (!count || count < 1) return;

    const fd = new FormData();
    fd.set('count', String(count));
    const res = await fetch(`${api()}?action=generate`, {
      method: 'POST',
      credentials: 'same-origin',
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      openPopup({ title: 'Failed', message: data.message || 'Failed' });
      return;
    }
    await load();
  };

  const trim = async () => {
    const mod = moduleEl();
    if (!mod || !api()) return;
    const count = Number($('[data-qr-count]', mod)?.value || 0);
    if (Number.isNaN(count) || count < 0) return;

    openPopup({
      title: 'Reduce tables?',
      message: `This will delete tables above ${count}. Continue?`,
      confirm: true,
      okText: 'Delete extra tables',
      cancelText: 'Cancel',
      onOk: async () => {
        const fd = new FormData();
        fd.set('table_id', '');
        fd.set('count', String(count));
        const res = await fetch(`${api()}?action=trim`, { method: 'POST', credentials: 'same-origin', body: fd });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
          const msg = data?.message || 'Failed';
          openPopup({ title: 'Trim failed', message: msg });
          return;
        }
        openPopup({ title: 'Done', message: 'Tables reduced successfully.' });
        await load();
      },
    });
  };

  const deleteOne = async (tableId) => {
    openPopup({
      title: 'Delete QR code?',
      message: 'Are you sure you want to delete this QR code? This cannot be undone.',
      confirm: true,
      okText: 'Delete',
      cancelText: 'Cancel',
      onOk: async () => {
        const fd = new FormData();
        fd.set('table_id', String(tableId));
        const res = await fetch(`${api()}?action=delete`, { method: 'POST', credentials: 'same-origin', body: fd });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
          openPopup({ title: 'Delete failed', message: data.message || 'Failed' });
          return;
        }
        openPopup({ title: 'Deleted', message: 'QR code deleted.' });
        await load();
      },
    });
  };

  document.addEventListener('click', (e) => {
    const mod = moduleEl();
    if (!mod) return;

    if (e.target.closest('[data-qr-refresh]')) {
      e.preventDefault();
      load();
      return;
    }
    if (e.target.closest('[data-qr-generate]')) {
      e.preventDefault();
      generate();
      return;
    }
    if (e.target.closest('[data-qr-trim]')) {
      e.preventDefault();
      trim();
      return;
    }
    const copyBtn = e.target.closest('[data-qr-copy]');
    if (copyBtn) {
      e.preventDefault();
      const u = decodeURIComponent(copyBtn.getAttribute('data-qr-copy') || '');
      navigator.clipboard?.writeText(u);
      copyBtn.textContent = 'Copied';
      setTimeout(() => (copyBtn.textContent = 'Copy'), 900);
    }

    const delBtn = e.target.closest('[data-qr-delete]');
    if (delBtn) {
      e.preventDefault();
      const id = Number(delBtn.getAttribute('data-qr-delete'));
      if (!id) return;
      deleteOne(id);
    }
  });

  const boot = () => {
    if (!moduleEl()) return;
    load();
  };

  boot();
  document.addEventListener('qr:mounted', boot);
})();

