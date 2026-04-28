<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/');
// Works whether this file is included (Dashboard.php) or fetched directly (navFiles/*.php)
$pos = strpos($script, '/App/View/');
$app = $pos !== false ? substr($script, 0, $pos) : dirname($script);
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$ordersApi = ($app !== '' ? $app : '') . '/App/Controller/OrdersController.php';
?>

<div id="dashboardHomeModule" data-orders-api="<?php echo htmlspecialchars($ordersApi, ENT_QUOTES, 'UTF-8'); ?>">
<div class="dash-hero">
  <div class="dash-hero__left">
    <div class="dash-kicker">Welcome back</div>
    <div class="dash-title">Manage your shop smoothly</div>
    <div class="dash-sub">Quick overview of your activity and shortcuts.</div>
  </div>
</div>

<div class="dash-grid">
  <div class="dash-card">
    <div class="dash-card__label">Total Orders</div>
    <div class="dash-card__value" id="statTotalOrders">—</div>
    <div class="dash-card__hint">All time</div>
  </div>
  <div class="dash-card">
    <div class="dash-card__label">Active Orders</div>
    <div class="dash-card__value" id="statActiveOrders">—</div>
    <div class="dash-card__hint">Pending + Preparing</div>
  </div>
  <div class="dash-card">
    <div class="dash-card__label">Completed Orders</div>
    <div class="dash-card__value" id="statCompletedOrders">—</div>
    <div class="dash-card__hint">Completed + Served</div>
  </div>
</div>

<div class="dash-grid" style="margin-top: 14px;">
  <div class="dash-card">
    <div class="dash-card__label">Revenue</div>
    <div class="dash-card__value" id="statRevenue">—</div>
    <div class="dash-card__hint">All time</div>
  </div>
  <div class="dash-card">
    <div class="dash-card__label">Shortcuts</div>
    <div class="dash-actions-inline" style="margin-top: 12px;">
      <a class="btn-soft" href="<?php echo htmlspecialchars(($app !== '' ? $app : '') . '/kitchen.php', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Kitchen</a>
      <a class="btn-soft" href="<?php echo htmlspecialchars(($app !== '' ? $app : '') . '/order.php', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Order page</a>
    </div>
    <div class="dash-card__hint">Kitchen is staff-only. Order page requires a table QR.</div>
  </div>
  <div class="dash-card">
    <div class="dash-card__label">Tips</div>
    <div class="dash-card__hint" style="margin-top: 10px;">
      Keep time slots updated so the menu matches current availability.
    </div>
  </div>
</div>

</div>

