<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$app = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))));
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$productsApi = ($app !== '' ? $app : '') . '/App/Controller/ProductsController.php';
?>

<div class="dash-hero dash-hero--compact">
  <div class="dash-hero__left">
    <div class="dash-kicker">Products</div>
    <div class="dash-title">Manage your menu</div>
    <div class="dash-sub">Add items, schedule them by time slot, and enable/disable visibility instantly.</div>
  </div>
  <div class="dash-hero__right">
    <button class="btn-soft" type="button" data-products-refresh>Refresh</button>
    <button class="btn-soft btn-primary" type="button" data-products-open>Create product</button>
  </div>
</div>

<div id="productsModule" class="products" data-products-api="<?php echo htmlspecialchars($productsApi, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="products-toolbar">
    <div class="products-search">
      <input type="text" class="input" placeholder="Search products…" data-products-search>
    </div>
    <div class="products-filter">
      <select class="input" data-products-slot>
        <option value="">All time slots</option>
      </select>
      <select class="input" data-products-status>
        <option value="">All statuses</option>
        <option value="1">Enabled</option>
        <option value="0">Disabled</option>
      </select>
    </div>
  </div>

  <div class="dash-table" style="margin-top: 14px;">
    <div class="dash-table__row dash-table__row--5 dash-table__head products-head">
      <div>Product</div>
      <div>Time slot</div>
      <div>Price</div>
      <div>Status</div>
      <div class="products-actions-col">Actions</div>
    </div>
    <div data-products-list></div>
  </div>

  <div data-products-pager></div>
</div>

<!-- Modal -->
<div class="modal" id="productModal" aria-hidden="true">
  <div class="modal__backdrop" data-products-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="productModalTitle">
    <div class="modal__header">
      <div>
        <div class="dash-kicker" style="margin:0;">Products</div>
        <div class="dash-title" id="productModalTitle" style="margin:6px 0 0;">Create product</div>
      </div>
      <button class="icon-btn" type="button" aria-label="Close" data-products-close>✕</button>
    </div>

    <form class="modal__body" id="productForm" method="post" action="#" novalidate>
      <input type="hidden" name="id" value="" />

      <div class="field">
        <label>Product name <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">🍽</span>
          <input class="input" name="name" type="text" placeholder="e.g. Chicken Burger" required />
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label>Price <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">₹</span>
            <input class="input" name="price" type="number" min="0" step="0.01" placeholder="e.g. 199" required />
          </div>
        </div>
        <div class="field">
          <label>Time slot <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">⏱</span>
            <select class="input" name="time_slot_id" required data-products-form-slot>
              <option value="">Select time slot</option>
            </select>
          </div>
        </div>
      </div>

      <div class="field">
        <label>Description</label>
        <div class="input-wrap textarea-wrap">
          <span class="input-icon">📝</span>
          <textarea class="input" name="description" placeholder="Short description (optional)"></textarea>
        </div>
      </div>

      <div class="field" style="margin-top: 8px;">
        <label class="toggle">
          <input type="checkbox" name="is_enabled" value="1" checked />
          <span class="toggle__track" aria-hidden="true"><span class="toggle__thumb"></span></span>
          <span>Enabled (visible to customers)</span>
        </label>
      </div>

      <div class="modal__footer">
        <button type="button" class="btn-soft" data-products-close>Cancel</button>
        <button type="button" class="btn-soft btn-primary" data-products-save>Save</button>
      </div>
    </form>
  </div>
</div>

<?php
// This view is injected via dashboard innerHTML; keep it HTML-only.
// If any legacy duplicated markup exists below, stop rendering it.
return;
?>

<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$app = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))));
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$productsApi = ($app !== '' ? $app : '') . '/App/Controller/ProductsController.php';
?>

<div class="dash-hero dash-hero--compact">
  <div class="dash-hero__left">
    <div class="dash-kicker">Products</div>
    <div class="dash-title">Manage your menu</div>
    <div class="dash-sub">Add items, schedule them by time slot, and enable/disable visibility instantly.</div>
  </div>
  <div class="dash-hero__right">
    <button class="btn-soft" type="button" data-products-refresh>Refresh</button>
    <button class="btn-soft btn-primary" type="button" data-products-open>Create product</button>
  </div>
</div>

<div id="productsModule" class="products" data-products-api="<?php echo htmlspecialchars($productsApi, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="products-toolbar">
    <div class="products-search">
      <input type="text" class="input" placeholder="Search products…" data-products-search>
    </div>
    <div class="products-filter">
      <select class="input" data-products-slot>
        <option value="">All time slots</option>
      </select>
      <select class="input" data-products-status>
        <option value="">All statuses</option>
        <option value="1">Enabled</option>
        <option value="0">Disabled</option>
      </select>
    </div>
  </div>

  <div class="dash-table" style="margin-top: 14px;">
    <div class="dash-table__row dash-table__row--5 dash-table__head products-head">
      <div>Product</div>
      <div>Time slot</div>
      <div>Price</div>
      <div>Status</div>
      <div class="products-actions-col">Actions</div>
    </div>
    <div data-products-list></div>
  </div>
</div>

<!-- Modal -->
<div class="modal" id="productModal" aria-hidden="true">
  <div class="modal__backdrop" data-products-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="productModalTitle">
    <div class="modal__header">
      <div>
        <div class="dash-kicker" style="margin:0;">Products</div>
        <div class="dash-title" id="productModalTitle" style="margin:6px 0 0;">Create product</div>
      </div>
      <button class="icon-btn" type="button" aria-label="Close" data-products-close>✕</button>
    </div>

    <form class="modal__body" id="productForm" method="post" action="#" novalidate>
      <input type="hidden" name="id" value="" />

      <div class="field">
        <label>Product name <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">🍽</span>
          <input class="input" name="name" type="text" placeholder="e.g. Chicken Burger" required />
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label>Price <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">₹</span>
            <input class="input" name="price" type="number" min="0" step="0.01" placeholder="e.g. 199" required />
          </div>
        </div>
        <div class="field">
          <label>Time slot <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">⏱</span>
            <select class="input" name="time_slot_id" required data-products-form-slot>
              <option value="">Select time slot</option>
            </select>
          </div>
        </div>
      </div>

      <div class="field">
        <label>Description</label>
        <div class="input-wrap textarea-wrap">
          <span class="input-icon">📝</span>
          <textarea class="input" name="description" placeholder="Short description (optional)"></textarea>
        </div>
      </div>

      <div class="field" style="margin-top: 8px;">
        <label class="toggle">
          <input type="checkbox" name="is_enabled" value="1" checked />
          <span class="toggle__track" aria-hidden="true"><span class="toggle__thumb"></span></span>
          <span>Enabled (visible to customers)</span>
        </label>
      </div>

      <div class="modal__footer">
        <button type="button" class="btn-soft" data-products-close>Cancel</button>
        <button type="button" class="btn-soft btn-primary" data-products-save>Save</button>
      </div>
    </form>
  </div>
</div>

<?php
// Stop here. Legacy duplicated markup exists below and breaks dashboard AJAX injection.
return;

session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$app = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))));
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$productsApi = ($app !== '' ? $app : '') . '/App/Controller/ProductsController.php';
?>

<!-- ───────── Toast Container ───────── -->
<div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

<div class="dash-hero dash-hero--compact">
  <div class="dash-hero__left">
    <div class="dash-kicker">Products</div>
    <div class="dash-title">Manage your menu</div>
    <div class="dash-sub">Add items, schedule them by time slot, and enable/disable visibility instantly.</div>
  </div>
  <div class="dash-hero__right">
    <button class="btn-soft" type="button" data-products-refresh>Refresh</button>
    <button class="btn-soft btn-primary" type="button" data-products-open>Create product</button>
  </div>
</div>

<div id="productsModule" class="products" data-products-api="<?php echo htmlspecialchars($productsApi, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="products-toolbar">
    <div class="products-search">
      <input type="text" class="input" placeholder="Search products…" data-products-search>
    </div>
    <div class="products-filter">
      <select class="input" data-products-slot>
        <option value="">All time slots</option>
      </select>
      <select class="input" data-products-status>
        <option value="">All statuses</option>
        <option value="1">Enabled</option>
        <option value="0">Disabled</option>
      </select>
    </div>
  </div>

  <div class="dash-table" style="margin-top: 14px;">
    <div class="dash-table__row dash-table__row--5 dash-table__head products-head">
      <div>Product</div>
      <div>Time slot</div>
      <div>Price</div>
      <div>Status</div>
      <div class="products-actions-col">Actions</div>
    </div>
    <div data-products-list>
      <div class="products-empty">Loading products…</div>
    </div>
  </div>
</div>

<!-- ───────── Modal ───────── -->
<div class="modal" id="productModal" aria-hidden="true">
  <div class="modal__backdrop" data-products-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="productModalTitle">
    <div class="modal__header">
      <div>
        <div class="dash-kicker" style="margin:0;">Products</div>
        <div class="dash-title" id="productModalTitle" style="margin:6px 0 0;">Create product</div>
      </div>
      <button class="icon-btn" type="button" aria-label="Close" data-products-close>✕</button>
    </div>

    <div class="modal__body" id="productFormWrap">
      <!-- Inline form error banner -->
      <div class="form-error-banner" id="productFormError" style="display:none;"></div>

      <input type="hidden" id="productId" value="" />

      <div class="field">
        <label for="productName">Product name <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">🍽</span>
          <input class="input" id="productName" name="name" type="text" placeholder="e.g. Chicken Burger" autocomplete="off" />
        </div>
        <span class="field-err" id="errName"></span>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="productPrice">Price <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">₹</span>
            <input class="input" id="productPrice" name="price" type="number" min="0" step="0.01" placeholder="e.g. 199" />
          </div>
          <span class="field-err" id="errPrice"></span>
        </div>
        <div class="field">
          <label for="productSlot">Time slot <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">⏱</span>
            <select class="input" id="productSlot" name="time_slot_id">
              <option value="">Select time slot</option>
            </select>
          </div>
          <span class="field-err" id="errSlot"></span>
        </div>
      </div>

      <div class="field">
        <label for="productDesc">Description</label>
        <div class="input-wrap textarea-wrap">
          <span class="input-icon">📝</span>
          <textarea class="input" id="productDesc" name="description" placeholder="Short description (optional)"></textarea>
        </div>
      </div>

      <div class="field" style="margin-top: 8px;">
        <label class="toggle">
          <input type="checkbox" id="productEnabled" name="is_enabled" value="1" checked />
          <span class="toggle__track" aria-hidden="true"><span class="toggle__thumb"></span></span>
          <span>Enabled (visible to customers)</span>
        </label>
      </div>

      <div class="modal__footer">
        <button type="button" class="btn-soft" data-products-close>Cancel</button>
        <button type="button" class="btn-soft btn-primary" id="productSaveBtn" data-products-save>
          <span class="btn-label">Save</span>
          <span class="btn-spinner" style="display:none;">⏳</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ───────── Delete Confirm Modal ───────── -->
<div class="modal" id="deleteModal" aria-hidden="true">
  <div class="modal__backdrop" id="deleteModalBackdrop"></div>
  <div class="modal__panel modal__panel--sm" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal__header">
      <div class="dash-title" id="deleteModalTitle" style="font-size:1.1rem;">Delete product?</div>
      <button class="icon-btn" type="button" aria-label="Close" id="deleteModalClose">✕</button>
    </div>
    <div class="modal__body" style="padding-top:0;">
      <p style="margin:0 0 20px;color:var(--text-muted,#666);">
        Are you sure you want to delete <strong id="deleteProductName"></strong>? This cannot be undone.
      </p>
      <div class="modal__footer" style="padding:0;">
        <button type="button" class="btn-soft" id="deleteCancelBtn">Cancel</button>
        <button type="button" class="btn-soft btn-danger" id="deleteConfirmBtn">
          <span class="btn-label">Delete</span>
          <span class="btn-spinner" style="display:none;">⏳</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ───────── Styles ───────── -->
<style>
/* ── Toast ── */
.toast-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}
.toast {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 18px;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  min-width: 240px;
  max-width: 360px;
  box-shadow: 0 4px 18px rgba(0,0,0,.18);
  pointer-events: all;
  animation: toastIn .28s cubic-bezier(.34,1.56,.64,1) both;
  transition: opacity .3s, transform .3s;
}
.toast--success { background: #1a7f4e; }
.toast--error   { background: #c0392b; }
.toast--info    { background: #1a5fa8; }
.toast--out     { opacity: 0; transform: translateX(40px); }
@keyframes toastIn {
  from { opacity:0; transform:translateX(40px); }
  to   { opacity:1; transform:translateX(0); }
}
.toast__icon { font-size: 1.1em; flex-shrink: 0; }
.toast__msg  { flex: 1; line-height: 1.4; }
.toast__close {
  background: none; border: none; color: rgba(255,255,255,.75);
  cursor: pointer; font-size: 1rem; padding: 0 0 0 6px; flex-shrink: 0;
}
.toast__close:hover { color: #fff; }

/* ── Form errors ── */
.form-error-banner {
  background: #fdf0ef;
  border: 1px solid #f5c6c2;
  color: #b91c1c;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: .85rem;
  margin-bottom: 14px;
}
.field-err {
  display: block;
  font-size: .78rem;
  color: #c0392b;
  margin-top: 4px;
  min-height: 1em;
}
.input--error { border-color: #e74c3c !important; }

/* ── Table rows ── */
.products-empty {
  text-align: center;
  padding: 48px 20px;
  color: var(--text-muted, #888);
  font-size: .9rem;
}
.dash-table__row--5 {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1fr 1fr 120px;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
}
.dash-table__head { font-weight: 600; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6; }
.dash-table__body .dash-table__row--5 { border-top: 1px solid var(--border, #eee); }
.dash-table__body .dash-table__row--5:hover { background: var(--row-hover, #f9f9f9); }

.product-name { font-weight: 600; font-size: .92rem; }
.product-desc { font-size: .78rem; color: var(--text-muted,#888); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:260px; }

.badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 9px; border-radius: 20px; font-size: .75rem; font-weight: 600;
}
.badge--on  { background: #d1fae5; color: #065f46; }
.badge--off { background: #fee2e2; color: #991b1b; }

.actions-row { display: flex; gap: 6px; flex-wrap: nowrap; }
.act-btn {
  padding: 5px 10px; border-radius: 6px; font-size: .75rem; font-weight: 600;
  border: 1px solid var(--border,#ddd); background: var(--surface,#fff);
  cursor: pointer; transition: background .15s, border-color .15s;
  white-space: nowrap;
}
.act-btn:hover  { background: var(--surface-hover,#f0f0f0); }
.act-btn--edit  { color: #1a5fa8; }
.act-btn--del   { color: #c0392b; }
.act-btn--tog   { color: #555; }

/* ── Delete modal danger btn ── */
.btn-danger { background: #c0392b !important; color: #fff !important; border-color: #c0392b !important; }
.btn-danger:hover { background: #a93226 !important; }

.modal__panel--sm { max-width: 400px !important; }

/* ── Spinner in button ── */
.btn-spinner { animation: spin 1s linear infinite; display:inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<!-- ───────── JS Module ───────── -->
<script>
(function () {
  'use strict';

  /* ── Helpers ── */
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

  // ── Toast ────────────────────────────────────────────────────────────────
  function showToast(msg, type = 'info', duration = 3500) {
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    const container = $('#toastContainer');
    const t = document.createElement('div');
    t.className = `toast toast--${type}`;
    t.innerHTML = `
      <span class="toast__icon">${icons[type] || 'ℹ️'}</span>
      <span class="toast__msg">${escHtml(msg)}</span>
      <button class="toast__close" aria-label="Dismiss">✕</button>`;
    container.appendChild(t);

    const dismiss = () => {
      t.classList.add('toast--out');
      t.addEventListener('transitionend', () => t.remove(), { once: true });
    };
    t.querySelector('.toast__close').addEventListener('click', dismiss);
    setTimeout(dismiss, duration);
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── State ────────────────────────────────────────────────────────────────
  const module    = $('#productsModule');
  const API       = module.dataset.productsApi;
  const listEl    = $('[data-products-list]', module);
  const slotFilter  = $('[data-products-slot]', module);
  const statusFilter = $('[data-products-status]', module);
  const searchInput  = $('[data-products-search]', module);

  let allProducts = [];
  let timeSlotLabels = {};  // { "id": "label" }
  let timeSlotOptions = []; // [{id, label}]

  // ── Fetch helpers ────────────────────────────────────────────────────────
  async function apiFetch(params) {
    const fd = new FormData();
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null) fd.append(k, v);
    });
    const res = await fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' });
    let data;
    try { data = await res.json(); } catch { data = { success: false, message: 'Server error (invalid JSON)' }; }
    return data;
  }

  // ── Load list ────────────────────────────────────────────────────────────
  async function loadProducts() {
    listEl.innerHTML = '<div class="products-empty">Loading…</div>';
    const data = await apiFetch({ action: 'list' });

    if (!data.success) {
      showToast(data.message || 'Failed to load products', 'error');
      listEl.innerHTML = `<div class="products-empty">${escHtml(data.message || 'Error')}</div>`;
      return;
    }

    allProducts   = data.products   || [];
    timeSlotLabels = data.timeSlots || {};
    timeSlotOptions = Object.entries(timeSlotLabels).map(([id, label]) => ({ id, label }));

    // Populate slot filter dropdown
    const curFilterVal = slotFilter.value;
    slotFilter.innerHTML = '<option value="">All time slots</option>';
    timeSlotOptions.forEach(({ id, label }) => {
      const o = document.createElement('option');
      o.value = id; o.textContent = label;
      if (String(id) === String(curFilterVal)) o.selected = true;
      slotFilter.appendChild(o);
    });

    // Populate form slot dropdown
    populateFormSlots();
    renderList();
  }

  function populateFormSlots(selectedId = '') {
    const sel = $('#productSlot');
    sel.innerHTML = '<option value="">Select time slot</option>';
    timeSlotOptions.forEach(({ id, label }) => {
      const o = document.createElement('option');
      o.value = id; o.textContent = label;
      if (String(id) === String(selectedId)) o.selected = true;
      sel.appendChild(o);
    });
  }

  // ── Render ───────────────────────────────────────────────────────────────
  function renderList() {
    const search = searchInput.value.trim().toLowerCase();
    const slot   = slotFilter.value;
    const status = statusFilter.value;

    const filtered = allProducts.filter(p => {
      if (search && !p.name.toLowerCase().includes(search)) return false;
      if (slot   && String(p.time_slot_id) !== String(slot))  return false;
      if (status !== '' && String(p.is_enabled) !== String(status)) return false;
      return true;
    });

    if (filtered.length === 0) {
      listEl.innerHTML = '<div class="products-empty">No products found.</div>';
      return;
    }

    const wrap = document.createElement('div');
    wrap.className = 'dash-table__body';

    filtered.forEach(p => {
      const slotLabel = timeSlotLabels[String(p.time_slot_id)] || p.time_slot || '—';
      const price     = parseFloat(p.price).toLocaleString('en-IN', { minimumFractionDigits: 2 });
      const enabled   = parseInt(p.is_enabled, 10) === 1;

      const row = document.createElement('div');
      row.className = 'dash-table__row dash-table__row--5';
      row.dataset.productId = p.id;
      row.innerHTML = `
        <div>
          <div class="product-name">${escHtml(p.name)}</div>
          ${p.description ? `<div class="product-desc">${escHtml(p.description)}</div>` : ''}
        </div>
        <div>${escHtml(slotLabel)}</div>
        <div>₹${price}</div>
        <div>
          <span class="badge ${enabled ? 'badge--on' : 'badge--off'}">
            ${enabled ? '● Enabled' : '○ Disabled'}
          </span>
        </div>
        <div class="actions-row">
          <button class="act-btn act-btn--edit"  data-action="edit"   data-id="${p.id}">Edit</button>
          <button class="act-btn act-btn--tog"   data-action="toggle" data-id="${p.id}" data-enabled="${p.is_enabled}">${enabled ? 'Disable' : 'Enable'}</button>
          <button class="act-btn act-btn--del"   data-action="delete" data-id="${p.id}" data-name="${escHtml(p.name)}">Delete</button>
        </div>`;
      wrap.appendChild(row);
    });

    listEl.innerHTML = '';
    listEl.appendChild(wrap);

    // Bind row actions
    $$('[data-action]', listEl).forEach(btn => {
      btn.addEventListener('click', handleRowAction);
    });
  }

  // ── Row actions ──────────────────────────────────────────────────────────
  function handleRowAction(e) {
    const btn = e.currentTarget;
    const action = btn.dataset.action;
    const id     = parseInt(btn.dataset.id, 10);

    if (action === 'edit') {
      const product = allProducts.find(p => p.id == id);
      if (product) openModal(product);
      return;
    }
    if (action === 'toggle') {
      const currentEnabled = parseInt(btn.dataset.enabled, 10);
      doToggle(id, currentEnabled === 1 ? 0 : 1);
      return;
    }
    if (action === 'delete') {
      openDeleteConfirm(id, btn.dataset.name);
      return;
    }
  }

  // ── Toggle ───────────────────────────────────────────────────────────────
  async function doToggle(id, newEnabled) {
    const data = await apiFetch({ action: 'toggle', id, is_enabled: newEnabled });
    if (data.success) {
      // Update local state and re-render (no full reload needed)
      const p = allProducts.find(x => x.id == id);
      if (p) p.is_enabled = newEnabled;
      renderList();
      showToast(newEnabled ? 'Product enabled' : 'Product disabled', 'success');
    } else {
      showToast(data.message || 'Failed to update status', 'error');
    }
  }

  // ── Delete confirm ───────────────────────────────────────────────────────
  let pendingDeleteId = null;

  function openDeleteConfirm(id, name) {
    pendingDeleteId = id;
    $('#deleteProductName').textContent = name;
    const modal = $('#deleteModal');
    modal.setAttribute('aria-hidden', 'false');
    modal.style.display = 'flex';
  }

  function closeDeleteModal() {
    pendingDeleteId = null;
    const modal = $('#deleteModal');
    modal.setAttribute('aria-hidden', 'true');
    modal.style.display = '';
  }

  async function doDelete() {
    if (!pendingDeleteId) return;
    const btn = $('#deleteConfirmBtn');
    setBtnLoading(btn, true);

    const data = await apiFetch({ action: 'delete', id: pendingDeleteId });
    setBtnLoading(btn, false);
    closeDeleteModal();

    if (data.success) {
      allProducts = allProducts.filter(p => p.id != pendingDeleteId);
      renderList();
      showToast('Product deleted', 'success');
    } else {
      showToast(data.message || 'Failed to delete product', 'error');
    }
  }

  $('#deleteConfirmBtn').addEventListener('click', doDelete);
  $('#deleteCancelBtn').addEventListener('click', closeDeleteModal);
  $('#deleteModalClose').addEventListener('click', closeDeleteModal);
  $('#deleteModalBackdrop').addEventListener('click', closeDeleteModal);

  // ── Create / Edit modal ──────────────────────────────────────────────────
  const productModal = $('#productModal');
  const modalTitle   = $('#productModalTitle');
  const saveBtn      = $('#productSaveBtn');
  const formError    = $('#productFormError');

  function openModal(product = null) {
    clearFormErrors();
    formError.style.display = 'none';

    const isEdit = !!product;
    modalTitle.textContent = isEdit ? 'Edit product' : 'Create product';

    $('#productId').value           = isEdit ? product.id    : '';
    $('#productName').value         = isEdit ? product.name  : '';
    $('#productPrice').value        = isEdit ? product.price : '';
    $('#productDesc').value         = isEdit ? (product.description || '') : '';
    $('#productEnabled').checked    = isEdit ? parseInt(product.is_enabled, 10) === 1 : true;
    populateFormSlots(isEdit ? product.time_slot_id : '');

    productModal.setAttribute('aria-hidden', 'false');
    productModal.style.display = 'flex';
    setTimeout(() => $('#productName').focus(), 60);
  }

  function closeModal() {
    productModal.setAttribute('aria-hidden', 'true');
    productModal.style.display = '';
    clearFormErrors();
    formError.style.display = 'none';
  }

  // ── Validation ───────────────────────────────────────────────────────────
  function clearFormErrors() {
    ['errName','errPrice','errSlot'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = '';
    });
    ['productName','productPrice','productSlot'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.classList.remove('input--error');
    });
  }

  function validateForm() {
    clearFormErrors();
    let valid = true;

    const name  = $('#productName').value.trim();
    const price = $('#productPrice').value.trim();
    const slot  = $('#productSlot').value;

    if (!name) {
      setFieldError('productName', 'errName', 'Product name is required');
      valid = false;
    }
    if (!price || isNaN(parseFloat(price)) || parseFloat(price) < 0) {
      setFieldError('productPrice', 'errPrice', 'Enter a valid price (≥ 0)');
      valid = false;
    }
    if (!slot) {
      setFieldError('productSlot', 'errSlot', 'Please select a time slot');
      valid = false;
    }
    return valid;
  }

  function setFieldError(inputId, errId, msg) {
    const input = document.getElementById(inputId);
    const err   = document.getElementById(errId);
    if (input) input.classList.add('input--error');
    if (err)   err.textContent = msg;
  }

  // ── Save ─────────────────────────────────────────────────────────────────
  async function doSave() {
    if (!validateForm()) return;

    const id          = $('#productId').value;
    const isEdit      = id !== '';
    const name        = $('#productName').value.trim();
    const price       = parseFloat($('#productPrice').value).toFixed(2);
    const description = $('#productDesc').value.trim();
    const time_slot_id = $('#productSlot').value;
    const is_enabled  = $('#productEnabled').checked ? '1' : '0';

    setBtnLoading(saveBtn, true);
    formError.style.display = 'none';

    const params = { action: isEdit ? 'update' : 'create', name, price, description, time_slot_id, is_enabled };
    if (isEdit) params.id = id;

    const data = await apiFetch(params);
    setBtnLoading(saveBtn, false);

    if (data.success) {
      closeModal();
      showToast(isEdit ? 'Product updated' : 'Product created', 'success');
      await loadProducts(); // Reload to get fresh data from DB
    } else {
      formError.textContent = data.message || 'Something went wrong. Please try again.';
      formError.style.display = 'block';
    }
  }

  // ── Button loading state ─────────────────────────────────────────────────
  function setBtnLoading(btn, loading) {
    const label   = btn.querySelector('.btn-label');
    const spinner = btn.querySelector('.btn-spinner');
    btn.disabled = loading;
    if (label)   label.style.display  = loading ? 'none'   : '';
    if (spinner) spinner.style.display = loading ? 'inline' : 'none';
  }

  // ── Event bindings ───────────────────────────────────────────────────────
  $('[data-products-open]').addEventListener('click', () => openModal());
  $('[data-products-refresh]').addEventListener('click', loadProducts);
  saveBtn.addEventListener('click', doSave);

  // Close modal
  $$('[data-products-close]').forEach(el => el.addEventListener('click', closeModal));

  // Close on backdrop click
  $('.modal__backdrop', productModal)?.addEventListener('click', closeModal);

  // ESC key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      if (productModal.getAttribute('aria-hidden') === 'false') closeModal();
      if ($('#deleteModal').getAttribute('aria-hidden') === 'false') closeDeleteModal();
    }
  });

  // Filters / search — live filter without API call
  searchInput.addEventListener('input', renderList);
  slotFilter.addEventListener('change', renderList);
  statusFilter.addEventListener('change', renderList);

  // ── Init ─────────────────────────────────────────────────────────────────
  loadProducts();

})();
</script>