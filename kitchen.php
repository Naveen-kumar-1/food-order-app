<?php
session_start();
if (empty($_SESSION['user_id'])) {
    $app = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
    header('Location: ' . (($app !== '' ? $app : '') . '/index.php'));
    exit;
}

$app = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$ordersApi = ($app !== '' ? $app : '') . '/App/Controller/OrdersController.php';
$pagerJs = ($app !== '' ? $app : '') . '/Assets/Js/pagination.js';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kitchen Dashboard</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(($app !== '' ? $app : '') . '/Assets/Css/main.css', ENT_QUOTES, 'UTF-8'); ?>">
  <script src="<?php echo htmlspecialchars($pagerJs, ENT_QUOTES, 'UTF-8'); ?>"></script>
  <style>
    .k-shell { max-width: 1100px; margin: 0 auto; padding: 18px; }
    .k-toolbar { display:flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .k-cards { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
    @media (max-width: 1000px){ .k-cards{ grid-template-columns: 1fr; } }
    .k-order { background: var(--warm-white); border: 1px solid var(--border); border-radius: 16px; padding: 14px; box-shadow: 0 2px 8px rgba(26,18,8,0.04); }
    .k-head { display:flex; justify-content: space-between; gap: 10px; align-items:center; flex-wrap: wrap; }
    .k-items { margin-top: 10px; display:grid; gap: 6px; }
    .k-item { display:flex; justify-content: space-between; gap: 10px; color: var(--ink); }
    .k-actions { margin-top: 12px; display:flex; gap: 8px; flex-wrap: wrap; }
  </style>
</head>
<body>
<div class="k-shell">
  <div class="k-toolbar">
    <div>
      <div class="dash-kicker">Kitchen</div>
      <div class="dash-title">Incoming orders</div>
      <div class="dash-sub">Update statuses: Pending → Preparing → Completed → Served</div>
    </div>
    <div style="display:flex; gap: 10px; align-items:center; flex-wrap: wrap; justify-content: flex-end;">
      <div class="input-wrap" style="min-width: 220px;">
        <span class="input-icon">📅</span>
        <input class="input" type="date" id="kDate" />
      </div>
      <button class="btn-soft" type="button" id="refreshBtn">Refresh</button>
      <label class="toggle" style="margin:0;">
        <input type="checkbox" id="autoRefresh" checked />
        <span class="toggle__track" aria-hidden="true"><span class="toggle__thumb"></span></span>
        <span>Auto refresh (12s)</span>
      </label>
    </div>
  </div>

  <div id="kList" class="k-cards"></div>
  <div id="kPager" style="margin-top: 12px;"></div>
</div>

<div class="modal" id="kPopup" aria-hidden="true">
  <div class="modal__backdrop" data-k-close></div>
  <div class="modal__panel modal__panel--sm" role="dialog" aria-modal="true" aria-labelledby="kPopupTitle">
    <div class="modal__header">
      <div class="dash-title" id="kPopupTitle" style="font-size:1.1rem;margin:0;">Message</div>
      <button class="icon-btn" type="button" aria-label="Close" data-k-close>✕</button>
    </div>
    <div class="modal__body" id="kPopupBody" style="padding-top: 8px; padding-bottom: 12px;"></div>
    <div class="modal__footer">
      <button type="button" class="btn-soft" id="kPopupCancel" style="display:none;">Cancel</button>
      <button type="button" class="btn-soft btn-primary" id="kPopupOk">OK</button>
    </div>
  </div>
</div>

<script>
(() => {
  const api = <?php echo json_encode($ordersApi, JSON_UNESCAPED_SLASHES); ?>;
  const el = document.getElementById('kList');
  const pagerEl = document.getElementById('kPager');
  const dateEl = document.getElementById('kDate');
  const fmt = (n) => '₹' + Number(n).toFixed(2).replace(/\\.00$/, '');
  const pillClass = (s) => s === 'Served' ? 'pill--green' : (s === 'Completed' ? 'pill--green' : (s === 'Preparing' ? 'pill--amber' : ''));

  const state = { page: 1, perPage: 10, total: 0, date: '' };
  const todayStr = () => new Date().toISOString().slice(0, 10);
  state.date = todayStr();
  if (dateEl) dateEl.value = state.date;

  const popEl = document.getElementById('kPopup');
  const popTitle = document.getElementById('kPopupTitle');
  const popBody = document.getElementById('kPopupBody');
  const popOk = document.getElementById('kPopupOk');
  const popCancel = document.getElementById('kPopupCancel');
  const closePop = () => {
    if (!popEl) return;
    popEl.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  };
  const openPop = ({ title, message, confirm = false, okText = 'OK', cancelText = 'Cancel', onOk = null }) => {
    if (!popEl) return;
    popTitle.textContent = title || 'Message';
    popBody.textContent = message || '';
    popOk.textContent = okText;
    popCancel.textContent = cancelText;
    popCancel.style.display = confirm ? '' : 'none';
    popOk.onclick = async () => { try { if (typeof onOk === 'function') await onOk(); } finally { closePop(); } };
    popCancel.onclick = closePop;
    popEl.querySelectorAll('[data-k-close]').forEach(el => (el.onclick = closePop));
    popEl.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  };

  let timer = null;

  const load = async () => {
    if (!el) return;
    const qs = new URLSearchParams();
    qs.set('action', 'list');
    qs.set('include_items', '1');
    qs.set('page', String(state.page));
    qs.set('per_page', String(state.perPage));
    if (state.date) qs.set('date', state.date);

    const res = await fetch(`${api}?${qs.toString()}`, { credentials: 'same-origin', cache: 'no-store' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      el.innerHTML = `<div class="products-empty">Failed to load orders.</div>`;
      return;
    }
    const rows = data.orders || [];
    const itemsByOrder = data.itemsByOrder || {};
    state.total = Number(data?.pagination?.total || 0);
    state.page = Number(data?.pagination?.page || state.page) || state.page;
    state.perPage = Number(data?.pagination?.per_page || state.perPage) || state.perPage;
    if (!rows.length) {
      el.innerHTML = `<div class="products-empty">No orders yet.</div>`;
      if (pagerEl && window.__Pager__?.render) {
        window.__Pager__.render(pagerEl, {
          page: state.page,
          perPage: state.perPage,
          total: state.total,
          onPage: (p) => { state.page = Math.max(1, p); load(); },
          onPerPage: (n) => { state.perPage = Math.max(1, Math.min(50, n)); state.page = 1; load(); },
        });
      }
      return;
    }
    el.innerHTML = rows.map(o => `
      <div class="k-order" data-id="${o.id}">
        <div class="k-head">
          <div style="font-weight:900;color:var(--ink);">#${o.id} — Table ${o.table_id}</div>
          <div><span class="pill ${pillClass(o.status)}">${o.status}</span></div>
        </div>
        <div class="dash-sub" style="margin-top:6px;">Total: <strong>${fmt(o.total_amount)}</strong> • ${o.created_at}</div>
        <div class="k-items">
          ${(itemsByOrder[String(o.id)] || []).map(it => `<div class="k-item"><div>${it.name} × ${it.qty}</div><div>${fmt(it.price)}</div></div>`).join('') || `<div class="products-empty" style="padding:10px 0;">No items</div>`}
        </div>
        <div class="k-actions">
          <button type="button" class="btn-mini" data-status="Pending">Pending</button>
          <button type="button" class="btn-mini" data-status="Preparing">Preparing</button>
          <button type="button" class="btn-mini" data-status="Completed">Completed</button>
          <button type="button" class="btn-mini" data-status="Served">Served</button>
          <button type="button" class="btn-mini danger" data-status="Cancelled">Cancel</button>
          <button type="button" class="btn-mini danger" data-delete>Delete</button>
        </div>
      </div>
    `).join('');

    if (pagerEl && window.__Pager__?.render) {
      window.__Pager__.render(pagerEl, {
        page: state.page,
        perPage: state.perPage,
        total: state.total,
        onPage: (p) => { state.page = Math.max(1, p); load(); },
        onPerPage: (n) => { state.perPage = Math.max(1, Math.min(50, n)); state.page = 1; load(); },
      });
    }
  };

  const setStatus = async (orderId, status) => {
    const fd = new FormData();
    fd.set('action', 'status');
    fd.set('order_id', String(orderId));
    fd.set('status', status);
    const res = await fetch(api, { method: 'POST', credentials: 'same-origin', body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      openPop({ title: 'Failed', message: data.message || 'Failed' });
      return;
    }
    load();
  };

  const deleteOrder = async (orderId) => {
    const fd = new FormData();
    fd.set('action', 'delete');
    fd.set('order_id', String(orderId));
    const res = await fetch(api, { method: 'POST', credentials: 'same-origin', body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      openPop({ title: 'Delete failed', message: data.message || 'Failed' });
      return;
    }
    load();
  };

  document.addEventListener('click', (e) => {
    if (e.target && e.target.id === 'refreshBtn') load();
    const del = e.target.closest('[data-delete]');
    if (del) {
      const wrap = del.closest('[data-id]');
      if (!wrap) return;
      const id = Number(wrap.getAttribute('data-id'));
      openPop({
        title: 'Delete order?',
        message: 'Are you sure you want to delete this order? This cannot be undone.',
        confirm: true,
        okText: 'Delete',
        cancelText: 'Cancel',
        onOk: () => deleteOrder(id),
      });
      return;
    }
    const btn = e.target.closest('[data-status]');
    if (!btn) return;
    const wrap = btn.closest('[data-id]');
    if (!wrap) return;
    const id = Number(wrap.getAttribute('data-id'));
    const st = btn.getAttribute('data-status');
    if (st === 'Cancelled') {
      openPop({
        title: 'Cancel order?',
        message: 'Cancel this order? (Customer will see Cancelled status)',
        confirm: true,
        okText: 'Cancel order',
        cancelText: 'Back',
        onOk: () => setStatus(id, 'Cancelled'),
      });
      return;
    }
    setStatus(id, st);
  });

  const setAuto = (on) => {
    if (timer) { clearInterval(timer); timer = null; }
    if (on) timer = setInterval(load, 12000);
  };
  document.getElementById('autoRefresh')?.addEventListener('change', (e) => setAuto(e.target.checked));

  dateEl?.addEventListener('change', (e) => {
    const v = String(e.target.value || '').trim();
    state.date = v || todayStr();
    state.page = 1;
    load();
  });

  load();
  setAuto(true);
})();
</script>
</body>
</html>

