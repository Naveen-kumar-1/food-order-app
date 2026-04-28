(() => {
  const $ = (sel, root = document) => root.querySelector(sel);
  const moduleEl = () => $('#dashboardHomeModule');

  const fmtMoney = (n) => `₹${Number(n).toFixed(2).replace(/\\.00$/, '')}`;

  const load = async () => {
    const mod = moduleEl();
    if (!mod) return;
    const api = mod.getAttribute('data-orders-api') || '';
    if (!api) return;

    const set = (id, v) => {
      const el = document.getElementById(id);
      if (el) el.textContent = String(v);
    };

    const res = await fetch(`${api}?action=stats`, { credentials: 'same-origin', cache: 'no-store' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) return;

    const s = data.stats || {};
    set('statTotalOrders', s.total_orders ?? 0);
    set('statActiveOrders', s.active_orders ?? 0);
    set('statCompletedOrders', s.completed_orders ?? 0);
    set('statRevenue', fmtMoney(s.revenue ?? 0));
  };

  const boot = () => {
    if (!moduleEl()) return;
    load();
  };

  boot();
  document.addEventListener('dashboard:mounted', boot);
})();

