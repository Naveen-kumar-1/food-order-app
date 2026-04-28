<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$app = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))));
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$qrApi = ($app !== '' ? $app : '') . '/App/Controller/QRController.php';

// Build absolute URL for QR so phones can open it
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$base = ($host !== '') ? ($scheme . '://' . $host) : '';
$orderUrlBase = $base . (($app !== '' ? $app : '') . '/order.php');
?>

<div class="dash-hero dash-hero--compact">
  <div class="dash-hero__left">
    <div class="dash-kicker">QR Manage</div>
    <div class="dash-title">Make it easy to order</div>
    <div class="dash-sub">Generate, download, and manage your table QR codes.</div>
  </div>
</div>

<div id="qrModule" class="products" data-qr-api="<?php echo htmlspecialchars($qrApi, ENT_QUOTES, 'UTF-8'); ?>" data-order-base="<?php echo htmlspecialchars($orderUrlBase, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="dash-grid">
    <div class="dash-card">
      <div class="dash-card__label">Order page base</div>
      <div class="dash-card__value" style="font-size: 1rem; line-height: 1.3;"><?php echo htmlspecialchars($orderUrlBase, ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="dash-card__hint">Each QR opens the order page for a specific table.</div>
    </div>
    <div class="dash-card">
      <div class="dash-card__label">Generate QR codes</div>
      <div class="dash-actions-inline" style="margin-top: 12px;">
        <div class="input-wrap" style="min-width: 220px;">
          <span class="input-icon">🔢</span>
          <input class="input" type="number" min="1" max="200" value="10" data-qr-count />
        </div>
        <button class="btn-soft btn-primary" type="button" data-qr-generate>Generate</button>
        <button class="btn-soft" type="button" data-qr-trim>Reduce to N</button>
        <button class="btn-soft" type="button" data-qr-refresh>Refresh</button>
      </div>
      <div class="dash-card__hint">Enter number of tables (e.g., 3) → generates Table 1..N.</div>
    </div>
  </div>

  <div class="dash-table" style="margin-top: 14px;">
    <div class="dash-table__row dash-table__row--5 dash-table__head">
      <div>Table</div>
      <div>Order URL</div>
      <div>QR</div>
      <div>Copy</div>
      <div class="products-actions-col">Actions</div>
    </div>
    <div data-qr-list></div>
  </div>
</div>

<!-- Popup / Confirm modal -->
<div class="modal" id="qrPopup" aria-hidden="true">
  <div class="modal__backdrop" data-qr-popup-close></div>
  <div class="modal__panel modal__panel--sm" role="dialog" aria-modal="true" aria-labelledby="qrPopupTitle">
    <div class="modal__header">
      <div class="dash-title" id="qrPopupTitle" style="font-size:1.1rem;margin:0;">Message</div>
      <button class="icon-btn" type="button" aria-label="Close" data-qr-popup-close>✕</button>
    </div>
    <div class="modal__body" id="qrPopupBody" style="padding-top: 8px; padding-bottom: 12px;"></div>
    <div class="modal__footer">
      <button type="button" class="btn-soft" data-qr-popup-cancel style="display:none;">Cancel</button>
      <button type="button" class="btn-soft btn-primary" data-qr-popup-ok>OK</button>
    </div>
  </div>
</div>