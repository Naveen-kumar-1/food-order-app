(() => {
  const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

  const range = (start, end) => {
    const out = [];
    for (let i = start; i <= end; i++) out.push(i);
    return out;
  };

  const buildPages = (page, totalPages) => {
    if (totalPages <= 7) return range(1, totalPages);
    const pages = new Set([1, totalPages, page, page - 1, page + 1, page - 2, page + 2]);
    const list = Array.from(pages)
      .filter((p) => p >= 1 && p <= totalPages)
      .sort((a, b) => a - b);

    // Insert gaps as null markers
    const out = [];
    for (let i = 0; i < list.length; i++) {
      const p = list[i];
      const prev = out.length ? out[out.length - 1] : null;
      if (prev !== null && p - prev > 1) out.push(null);
      out.push(p);
    }
    return out;
  };

  function render(container, opts) {
    if (!container) return;
    const page = Math.max(1, Number(opts.page || 1));
    const perPage = clamp(Number(opts.perPage || 10), 1, 50);
    const total = Math.max(0, Number(opts.total || 0));
    const totalPages = Math.max(1, Math.ceil(total / perPage) || 1);
    const cur = clamp(page, 1, totalPages);

    const from = total === 0 ? 0 : (cur - 1) * perPage + 1;
    const to = total === 0 ? 0 : Math.min(total, cur * perPage);

    const pages = buildPages(cur, totalPages);

    container.innerHTML = `
      <div class="pager">
        <div class="pager__left">
          <div class="pager__meta">Showing <strong>${from}</strong>–<strong>${to}</strong> of <strong>${total}</strong></div>
        </div>
        <div class="pager__right">
          <label class="pager__size">
            <span>Rows</span>
            <select class="input pager__select" data-pager-size>
              ${[5, 10, 20].map((n) => `<option value="${n}" ${n === perPage ? 'selected' : ''}>${n}</option>`).join('')}
            </select>
          </label>
          <div class="pager__nav" role="navigation" aria-label="Pagination">
            <button type="button" class="btn-mini" data-pager-prev ${cur <= 1 ? 'disabled' : ''}>Prev</button>
            <div class="pager__pages">
              ${pages
                .map((p) =>
                  p === null
                    ? `<span class="pager__gap">…</span>`
                    : `<button type="button" class="pager__page ${p === cur ? 'is-active' : ''}" data-pager-page="${p}">${p}</button>`
                )
                .join('')}
            </div>
            <button type="button" class="btn-mini" data-pager-next ${cur >= totalPages ? 'disabled' : ''}>Next</button>
          </div>
        </div>
      </div>
    `;

    const onPage = typeof opts.onPage === 'function' ? opts.onPage : () => {};
    const onPerPage = typeof opts.onPerPage === 'function' ? opts.onPerPage : () => {};

    container.querySelector('[data-pager-prev]')?.addEventListener('click', () => onPage(cur - 1));
    container.querySelector('[data-pager-next]')?.addEventListener('click', () => onPage(cur + 1));
    container.querySelectorAll('[data-pager-page]')?.forEach((btn) => {
      btn.addEventListener('click', () => onPage(Number(btn.getAttribute('data-pager-page'))));
    });
    container.querySelector('[data-pager-size]')?.addEventListener('change', (e) => {
      onPerPage(Number(e.target.value || perPage));
    });
  }

  window.__Pager__ = { render };
})();

