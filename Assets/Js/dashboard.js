(() => {
  const nav = document.querySelector('.nav');
  const content = document.getElementById('tabContent');
  const titleEl = document.getElementById('pageTitle');

  if (!nav || !content) return;

  const setActive = (link) => {
    nav.querySelectorAll('.nav-item').forEach(a => a.classList.remove('active'));
    link.classList.add('active');
  };

  const setLoading = () => {
    content.innerHTML = `
      <div class="dash-loading">
        <div class="spinner" aria-hidden="true"></div>
        <div>
          <div class="dash-title" style="font-size:1.05rem;margin:0;">Loading…</div>
          <div class="dash-sub" style="margin:6px 0 0;">Please wait</div>
        </div>
      </div>
    `;
  };

  const loadPage = async (link) => {
    const page = link.getAttribute('data-page');
    const pageTitle = link.getAttribute('data-title') || link.textContent.trim();

    if (!page) return;

    setActive(link);
    if (titleEl) titleEl.textContent = pageTitle;
    setLoading();

    try {
      const res = await fetch(page, { credentials: 'same-origin' });
      if (!res.ok) {
        if (res.status === 401) {
          const base = (window.__APP_BASE__ || '');
          window.location.href = `${base}/index.php`;
          return;
        }
        throw new Error(`HTTP ${res.status}`);
      }

      const html = await res.text();
      content.innerHTML = html;
      // allow modules (products, etc.) to boot after content injection
      document.dispatchEvent(new CustomEvent('products:mounted'));
      document.dispatchEvent(new CustomEvent('timeslots:mounted'));
      document.dispatchEvent(new CustomEvent('qr:mounted'));
      document.dispatchEvent(new CustomEvent('orders:mounted'));
      document.dispatchEvent(new CustomEvent('dashboard:mounted'));
    } catch (e) {
      console.error(e);
      content.innerHTML = `
        <div class="dash-error">
          <div class="dash-title" style="font-size:1.05rem;margin:0;">Couldn’t load the page</div>
          <div class="dash-sub" style="margin:6px 0 0;">Please try again.</div>
        </div>
      `;
    }
  };

  nav.addEventListener('click', (e) => {
    const link = e.target.closest('.nav-item');
    if (!link) return;
    e.preventDefault();
    loadPage(link);
  });
})();

