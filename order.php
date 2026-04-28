<?php
session_start();

require_once __DIR__ . '/App/Model/Config.php';
require_once __DIR__ . '/App/Helpers/Tables.php';

$app = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');

$tableId = (int) ($_GET['table_id'] ?? 0);
$token = trim((string) ($_GET['token'] ?? ''));
$ordersApi = ($app !== '' ? $app : '') . '/App/Controller/OrdersController.php';

$validTable = false;
$tableNumber = '';
$shopId = 0;

if ($tableId > 0 && $token !== '') {
    $conn = Config::getConnection();
    if ($conn) {
        Tables::createTablesTable($conn);
        $t = Tables::getById($conn, $tableId);
        if ($t && (string) ($t['token'] ?? '') === $token) {
            $validTable = true;
            $tableNumber = (string) ($t['table_number'] ?? '');
            $shopId = (int) ($t['shop_id'] ?? 0);
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order<?php echo $validTable ? ' — Table ' . htmlspecialchars($tableNumber) : ''; ?></title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(($app !== '' ? $app : '') . '/Assets/Css/main.css', ENT_QUOTES, 'UTF-8'); ?>">
  <style>
    .order-shell { max-width: 980px; margin: 0 auto; padding: 18px; }
    .order-top { display:flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
    .order-grid { display:grid; grid-template-columns: 1.6fr 1fr; gap: 14px; margin-top: 14px; }
    @media (max-width: 900px){ .order-grid { grid-template-columns: 1fr; } }
    .card { background: var(--warm-white); border: 1px solid var(--border); border-radius: 16px; padding: 14px; box-shadow: 0 2px 8px rgba(26,18,8,0.04); }
    .menu-item { display:flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 0; border-top: 1px solid var(--border); }
    .menu-item:first-child { border-top: none; padding-top: 0; }
    .menu-title { font-weight: 900; color: var(--ink); }
    .menu-sub { color: var(--muted); font-size: 12px; margin-top: 3px; }
    .qty { display:inline-flex; align-items:center; gap: 8px; }
    .qty .btn-mini { padding: 6px 10px; }
    .cart-row { display:flex; justify-content: space-between; align-items: center; gap: 10px; padding: 8px 0; border-top: 1px solid var(--border); }
    .cart-row:first-child { border-top: none; padding-top: 0; }
    .status-box { display:flex; gap: 10px; align-items: center; }
    .pop {
      position: fixed;
      inset: 0;
      display: grid;
      place-items: center;
      background: rgba(26,18,8,0.45);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.18s ease;
      z-index: 2000;
    }
    .pop.is-open { opacity: 1; pointer-events: auto; }
    .pop__panel {
      width: min(420px, calc(100vw - 32px));
      background: var(--warm-white);
      border: 1px solid var(--border);
      border-radius: 18px;
      box-shadow: 0 24px 70px rgba(26,18,8,0.25);
      padding: 16px;
      transform: scale(0.96);
      opacity: 0;
      transition: transform 0.18s ease, opacity 0.18s ease;
    }
    .pop.is-open .pop__panel { transform: scale(1); opacity: 1; }
    .pop__row { display:flex; gap: 12px; align-items: flex-start; }
    .pop__icon { width: 38px; height: 38px; border-radius: 12px; display:grid; place-items:center; font-weight: 900; }
    .pop__icon--success { background: rgba(39,174,96,0.14); color: #1c7d48; border: 1px solid rgba(39,174,96,0.25); }
    .pop__icon--error { background: rgba(192,57,43,0.10); color: #8e2b22; border: 1px solid rgba(192,57,43,0.20); }
    .pop__title { font-weight: 900; color: var(--ink); margin: 0; }
    .pop__msg { color: var(--muted); margin-top: 6px; font-size: 0.95rem; }
    .pop__actions { margin-top: 14px; display:flex; justify-content: flex-end; gap: 10px; }
  </style>
</head>
<body>
<div class="order-shell">
  <div class="order-top">
    <div>
      <div class="dash-kicker">QR Order</div>
      <div class="dash-title">Table <?php echo $validTable ? htmlspecialchars($tableNumber) : '—'; ?></div>
      <div class="dash-sub"><?php echo $validTable ? 'Add items and place your order.' : 'Invalid or expired table QR.'; ?></div>
    </div>
    <div class="status-box">
      <button class="btn-soft" type="button" id="refreshBtn">Refresh</button>
      <button class="btn-soft" type="button" id="trackBtn">Track order</button>
    </div>
  </div>

  <?php if (!$validTable): ?>
    <div class="card" style="margin-top: 14px;">
      <div class="dash-title" style="margin:0;">Can’t open this table</div>
      <div class="dash-sub" style="margin-top:8px;">Please scan a valid QR code from the restaurant.</div>
    </div>
  <?php else: ?>
    <div class="order-grid">
      <div class="card">
        <div class="dash-title" style="margin:0;">Menu (Available now)</div>
        <div class="dash-sub" style="margin-top:8px;">Items are shown based on the current time slot.</div>
        <div id="menuList" style="margin-top: 10px;"></div>
      </div>
      <div class="card">
        <div class="dash-title" style="margin:0;">Your cart</div>
        <div class="dash-sub" style="margin-top:8px;">Review quantities and place the order.</div>
        <div id="cartList" style="margin-top: 10px;"></div>
        <div style="margin-top: 12px; display:flex; justify-content: space-between; align-items:center; gap: 10px;">
          <div style="font-weight:900;color:var(--ink);">Total: <span id="cartTotal">₹0</span></div>
          <button class="btn-soft btn-primary" type="button" id="placeOrderBtn">Place order</button>
        </div>
        <div class="dash-sub" id="cartHint" style="margin-top:10px;"></div>
      </div>
    </div>

    <div class="card" style="margin-top: 14px;">
      <div class="dash-title" style="margin:0;">Order tracking</div>
      <div class="dash-sub" style="margin-top:8px;">Scan the same QR again to track your latest order (stored for 1 hour).</div>
      <div id="trackBox" style="margin-top: 10px;"></div>
    </div>
  <?php endif; ?>
</div>

<div class="pop" id="popup" aria-hidden="true">
  <div class="pop__panel" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
    <div class="pop__row">
      <div class="pop__icon pop__icon--success" id="popupIcon">✔</div>
      <div style="flex:1;">
        <div class="dash-title" id="popupTitle" style="margin:0;font-size:1.1rem;">Done</div>
        <div class="pop__msg" id="popupMsg"></div>
      </div>
      <button class="icon-btn" type="button" id="popupClose" aria-label="Close">✕</button>
    </div>
    <div class="pop__actions">
      <button class="btn-soft btn-primary" type="button" id="popupOk">OK</button>
    </div>
  </div>
</div>

<script>
(() => {
  const valid = <?php echo $validTable ? 'true' : 'false'; ?>;
  if (!valid) return;

  const api = <?php echo json_encode($ordersApi, JSON_UNESCAPED_SLASHES); ?>;
  const tableId = <?php echo (int) $tableId; ?>;
  const token = <?php echo json_encode($token, JSON_UNESCAPED_SLASHES); ?>;

  const $ = (id) => document.getElementById(id);
  const fmt = (n) => '₹' + Number(n).toFixed(2).replace(/\.00$/, '');
  const popup = (() => {
    const el = $('popup');
    const icon = $('popupIcon');
    const title = $('popupTitle');
    const msg = $('popupMsg');
    const closeBtn = $('popupClose');
    const okBtn = $('popupOk');
    let timer = null;
    const close = () => {
      if (!el) return;
      el.classList.remove('is-open');
      el.setAttribute('aria-hidden', 'true');
      if (timer) { clearTimeout(timer); timer = null; }
    };
    const open = ({ type = 'success', heading = 'Success', message = '', autoCloseMs = 2200 } = {}) => {
      if (!el) return;
      if (type === 'error') {
        icon.textContent = '!';
        icon.classList.remove('pop__icon--success');
        icon.classList.add('pop__icon--error');
      } else {
        icon.textContent = '✔';
        icon.classList.remove('pop__icon--error');
        icon.classList.add('pop__icon--success');
      }
      title.textContent = heading;
      msg.textContent = message;
      el.classList.add('is-open');
      el.setAttribute('aria-hidden', 'false');
      if (autoCloseMs) timer = setTimeout(close, autoCloseMs);
    };
    closeBtn?.addEventListener('click', close);
    okBtn?.addEventListener('click', close);
    el?.addEventListener('click', (e) => { if (e.target === el) close(); });
    return { open, close };
  })();


  const cartKey = `cart:${tableId}`;
  const trackKey = `track:${tableId}`;

  const readCart = () => {
    try { return JSON.parse(localStorage.getItem(cartKey) || '{}') || {}; } catch { return {}; }
  };
  const writeCart = (c) => localStorage.setItem(cartKey, JSON.stringify(c));

  const now = () => Date.now();
  const addTrack = (orderId) => {
    const cur = getTrackList();
    const next = [{ order_id: String(orderId), expiry: now() + 60 * 60 * 1000 }, ...cur].slice(0, 10);
    localStorage.setItem(trackKey, JSON.stringify(next));
  };
  const getTrackList = () => {
    try {
      const v = JSON.parse(localStorage.getItem(trackKey) || '[]');
      const arr = Array.isArray(v) ? v : [];
      const fresh = arr.filter(x => x && x.order_id && x.expiry && now() <= Number(x.expiry));
      if (fresh.length !== arr.length) localStorage.setItem(trackKey, JSON.stringify(fresh));
      return fresh;
    } catch { return []; }
  };

  let menu = []; // [{id,name,price,description}]

  const renderMenu = () => {
    const el = $('menuList');
    if (!el) return;
    if (!menu.length) {
      el.innerHTML = `<div class="products-empty">No items available right now.</div>`;
      return;
    }
    const cart = readCart();
    el.innerHTML = menu.map(p => {
      const qty = Number(cart[p.id] || 0);
      return `
        <div class="menu-item">
          <div>
            <div class="menu-title">${p.name}</div>
            <div class="menu-sub">${p.description ? p.description : ''}</div>
          </div>
          <div style="text-align:right;">
            <div style="font-weight:900;color:var(--ink);">${fmt(p.price)}</div>
            <div class="qty" style="margin-top:8px;">
              <button class="btn-mini" type="button" data-dec="${p.id}">−</button>
              <div style="min-width: 24px; text-align:center; font-weight:900;">${qty}</div>
              <button class="btn-mini" type="button" data-inc="${p.id}">+</button>
            </div>
          </div>
        </div>
      `;
    }).join('');
  };

  const renderCart = () => {
    const el = $('cartList');
    const totalEl = $('cartTotal');
    const hint = $('cartHint');
    if (!el || !totalEl) return;
    const cart = readCart();
    const items = Object.entries(cart).map(([id, qty]) => ({ id: Number(id), qty: Number(qty) })).filter(x => x.qty > 0);
    if (!items.length) {
      el.innerHTML = `<div class="products-empty">Cart is empty.</div>`;
      totalEl.textContent = fmt(0);
      if (hint) hint.textContent = 'Add items from the menu to place an order.';
      return;
    }
    let total = 0;
    el.innerHTML = items.map(it => {
      const p = menu.find(x => Number(x.id) === Number(it.id));
      const name = p ? p.name : `Item #${it.id}`;
      const price = p ? Number(p.price) : 0;
      const line = price * it.qty;
      total += line;
      return `
        <div class="cart-row">
          <div>
            <div style="font-weight:900;color:var(--ink);">${name}</div>
            <div class="menu-sub">${fmt(price)} × ${it.qty}</div>
          </div>
          <div class="qty">
            <button class="btn-mini" type="button" data-dec="${it.id}">−</button>
            <div style="min-width: 24px; text-align:center; font-weight:900;">${it.qty}</div>
            <button class="btn-mini" type="button" data-inc="${it.id}">+</button>
            <button class="btn-mini danger" type="button" data-rem="${it.id}">Remove</button>
          </div>
        </div>
      `;
    }).join('');
    totalEl.textContent = fmt(total);
    if (hint) hint.textContent = 'Tip: you can adjust quantities before placing the order.';
  };

  const sync = () => { renderMenu(); renderCart(); };

  const loadMenu = async () => {
    const res = await fetch(`${api}?action=available&table_id=${encodeURIComponent(tableId)}&token=${encodeURIComponent(token)}`, { cache: 'no-store' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      $('menuList').innerHTML = `<div class="products-empty">Failed to load menu.</div>`;
      return;
    }
    menu = (data.products || []).map(p => ({
      id: Number(p.id),
      name: String(p.name || ''),
      price: Number(p.price || 0),
      description: String(p.description || ''),
    }));
    sync();
  };

  const placeOrder = async () => {
    const cart = readCart();
    const items = Object.entries(cart)
      .map(([id, qty]) => ({ product_id: Number(id), qty: Number(qty) }))
      .filter(x => x.qty > 0);
    if (!items.length) { popup.open({ type: 'error', heading: 'Cart is empty', message: 'Add items before placing an order.', autoCloseMs: 2400 }); return; }

    const fd = new FormData();
    fd.set('action', 'place');
    fd.set('table_id', String(tableId));
    fd.set('token', token);
    fd.set('items', JSON.stringify(items));
    const res = await fetch(api, { method: 'POST', body: fd, cache: 'no-store' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) { popup.open({ type: 'error', heading: 'Order failed', message: data.message || 'Failed to place order', autoCloseMs: 2600 }); return; }
    writeCart({});
    addTrack(data.order_id);
    sync();
    await trackOrder();
    popup.open({ type: 'success', heading: 'Order Placed Successfully', message: `Order #${data.order_id} has been sent to the kitchen.`, autoCloseMs: 2400 });
  };

  const cancelOrder = async (orderId) => {
    const fd = new FormData();
    fd.set('action', 'cancel');
    fd.set('order_id', String(orderId));
    fd.set('table_id', String(tableId));
    fd.set('token', token);
    const res = await fetch(api, { method: 'POST', body: fd, cache: 'no-store' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      popup.open({ type: 'error', heading: 'Cancel failed', message: data.message || 'Failed', autoCloseMs: 2400 });
      return;
    }
    popup.open({ type: 'success', heading: 'Order cancelled', message: `Order #${orderId} was cancelled.`, autoCloseMs: 2200 });
    trackOrder();
  };

  const trackOrder = async () => {
    const box = $('trackBox');
    if (!box) return;

    const track = getTrackList();
    const ids = track.map(x => Number(x.order_id)).filter(n => Number.isFinite(n) && n > 0);
    if (!ids.length) {
      box.innerHTML = `<div class="products-empty">No orders saved on this device (or they expired after 1 hour).</div>`;
      return;
    }

    const fd = new FormData();
    fd.set('action', 'trackOrders');
    fd.set('table_id', String(tableId));
    fd.set('token', token);
    fd.set('order_ids', JSON.stringify(ids.slice(0, 100)));
    const res = await fetch(api, { method: 'POST', body: fd, cache: 'no-store' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) { box.innerHTML = `<div class="products-empty">Could not load orders.</div>`; return; }
    const orders = data.orders || [];
    const itemsByOrder = data.itemsByOrder || {};
    if (!orders.length) { box.innerHTML = `<div class="products-empty">Your saved orders expired (after 1 hour) or were not found.</div>`; return; }

    box.innerHTML = orders.map(o => {
      const status = String(o.status || 'Pending');
      const pill = status === 'Served' ? 'pill--green' : (status === 'Completed' ? 'pill--green' : (status === 'Preparing' ? 'pill--amber' : (status === 'Cancelled' ? 'pill--amber' : '')));
      const items = itemsByOrder[String(o.id)] || [];
      const canCancel = status === 'Pending';
      return `
        <div style="border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px;">
          <div style="display:flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; align-items:center;">
            <div style="font-weight:900;color:var(--ink);">Order #${o.id}</div>
            <div style="display:flex; gap: 8px; align-items:center;">
              <span class="pill ${pill}">${status}</span>
              ${canCancel ? `<button class="btn-mini danger" type="button" data-cancel-order="${o.id}">Cancel</button>` : ``}
            </div>
          </div>
          <div class="dash-sub" style="margin-top:6px;">Total: <strong>${fmt(o.total_amount)}</strong> • ${o.created_at}</div>
          <div style="margin-top:10px;">
            ${items.map(it => `<div class="cart-row"><div>${it.name}</div><div>${it.qty} × ${fmt(it.price)}</div></div>`).join('')}
          </div>
        </div>
      `;
    }).join('');
  };

  document.addEventListener('click', (e) => {
    const inc = e.target.getAttribute('data-inc');
    const dec = e.target.getAttribute('data-dec');
    const rem = e.target.getAttribute('data-rem');
    if (inc || dec || rem) {
      const id = Number(inc || dec || rem);
      const cart = readCart();
      const cur = Number(cart[id] || 0);
      if (inc) cart[id] = cur + 1;
      if (dec) cart[id] = Math.max(0, cur - 1);
      if (rem) cart[id] = 0;
      if (cart[id] <= 0) delete cart[id];
      writeCart(cart);
      sync();
    }
    const cancelBtn = e.target.closest('[data-cancel-order]');
    if (cancelBtn) {
      const id = Number(cancelBtn.getAttribute('data-cancel-order'));
      popup.open({ type: 'success', heading: 'Confirm cancel', message: 'Cancelling…', autoCloseMs: 800 });
      cancelOrder(id);
    }
  });

  $('placeOrderBtn')?.addEventListener('click', placeOrder);
  $('refreshBtn')?.addEventListener('click', loadMenu);
  $('trackBtn')?.addEventListener('click', trackOrder);

  loadMenu().then(trackOrder);
})();
</script>
</body>
</html>

