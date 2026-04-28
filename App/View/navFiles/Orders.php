<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$app = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))));
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$ordersApi = ($app !== '' ? $app : '') . '/App/Controller/OrdersController.php';
?>

<div class="dash-hero dash-hero--compact">
  <div class="dash-hero__left">
    <div class="dash-kicker">Orders</div>
    <div class="dash-title">Track and fulfill orders</div>
    <div class="dash-sub">View all incoming orders and current statuses.</div>
  </div>
  <div class="dash-hero__right">
    <button class="btn-soft" type="button" data-orders-refresh>Refresh</button>
    <a class="btn-soft btn-primary" href="<?php echo htmlspecialchars(($app !== '' ? $app : '') . '/kitchen.php', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Open kitchen</a>
  </div>
</div>

<div id="ordersModule" class="products" data-orders-api="<?php echo htmlspecialchars($ordersApi, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="products-toolbar" style="margin-top: 14px;">
    <div class="products-search" style="flex: 0 0 auto; min-width: 260px;">
      <div class="input-wrap">
        <span class="input-icon">📅</span>
        <input class="input" type="date" data-orders-date />
      </div>
    </div>
    <div class="products-filter">
      <button class="btn-soft" type="button" data-orders-today>Today</button>
      <button class="btn-soft" type="button" data-orders-yesterday>Yesterday</button>
      <button class="btn-soft" type="button" data-orders-last7>Last 7 days</button>
      <button class="btn-soft" type="button" data-orders-reset>Reset</button>
    </div>
  </div>

  <div class="dash-sub" data-orders-date-label style="margin-top: 10px;"></div>

  <div class="dash-table" style="margin-top: 14px;">
    <div class="dash-table__row dash-table__row--5 dash-table__head">
      <div>Order</div>
      <div>Table</div>
      <div>Status</div>
      <div>Total</div>
      <div class="products-actions-col">Time</div>
    </div>
    <div data-orders-list></div>
  </div>
  <div data-orders-pager></div>
</div>

<!-- Popup / Confirm modal -->
<div class="modal" id="ordersPopup" aria-hidden="true">
  <div class="modal__backdrop" data-orders-popup-close></div>
  <div class="modal__panel modal__panel--sm" role="dialog" aria-modal="true" aria-labelledby="ordersPopupTitle">
    <div class="modal__header">
      <div class="dash-title" id="ordersPopupTitle" style="font-size:1.1rem;margin:0;">Message</div>
      <button class="icon-btn" type="button" aria-label="Close" data-orders-popup-close>✕</button>
    </div>
    <div class="modal__body" id="ordersPopupBody" style="padding-top: 8px; padding-bottom: 12px;"></div>
    <div class="modal__footer">
      <button type="button" class="btn-soft" data-orders-popup-cancel style="display:none;">Cancel</button>
      <button type="button" class="btn-soft btn-primary" data-orders-popup-ok>OK</button>
    </div>
  </div>
</div>