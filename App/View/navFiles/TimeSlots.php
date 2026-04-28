<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$app = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))));
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$timeSlotsApi = ($app !== '' ? $app : '') . '/App/Controller/TimeSlotsController.php';
?>

<div class="dash-hero dash-hero--compact">
  <div class="dash-hero__left">
    <div class="dash-kicker">Time Slots</div>
    <div class="dash-title">Control availability windows</div>
    <div class="dash-sub">Create time slots like Morning (09:00–12:00) and assign products to them.</div>
  </div>
  <div class="dash-hero__right">
    <button class="btn-soft" type="button" data-slots-refresh>Refresh</button>
    <button class="btn-soft btn-primary" type="button" data-slots-open>Create time slot</button>
  </div>
</div>

<div id="timeSlotsModule" class="products" data-slots-api="<?php echo htmlspecialchars($timeSlotsApi, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="dash-table" style="margin-top: 14px;">
    <div class="dash-table__row dash-table__row--5 dash-table__head">
      <div>Name</div>
      <div>Start</div>
      <div>End</div>
      <div>Status</div>
      <div class="products-actions-col">Actions</div>
    </div>
    <div data-slots-list></div>
  </div>

  <div data-slots-pager></div>
</div>

<div class="modal" id="timeSlotModal" aria-hidden="true">
  <div class="modal__backdrop" data-slots-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="timeSlotModalTitle">
    <div class="modal__header">
      <div>
        <div class="dash-kicker" style="margin:0;">Time Slots</div>
        <div class="dash-title" id="timeSlotModalTitle" style="margin:6px 0 0;">Create time slot</div>
      </div>
      <button class="icon-btn" type="button" aria-label="Close" data-slots-close>✕</button>
    </div>
    <form class="modal__body" id="timeSlotForm" method="post" action="#" novalidate>
      <input type="hidden" name="id" value="" />
      <div class="field">
        <label>Name <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">🕒</span>
          <input class="input" name="name" type="text" placeholder="e.g. Morning" required />
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label>Start <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">⏱</span>
            <input class="input" name="start_time" type="time" required />
          </div>
        </div>
        <div class="field">
          <label>End <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">⏱</span>
            <input class="input" name="end_time" type="time" required />
          </div>
        </div>
      </div>
      <div class="field" style="margin-top: 8px;">
        <label class="toggle">
          <input type="checkbox" name="is_active" value="1" checked />
          <span class="toggle__track" aria-hidden="true"><span class="toggle__thumb"></span></span>
          <span>Active</span>
        </label>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn-soft" data-slots-close>Cancel</button>
        <button type="button" class="btn-soft btn-primary" data-slots-save>Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Popup / Confirm modal -->
<div class="modal" id="timeSlotsPopup" aria-hidden="true">
  <div class="modal__backdrop" data-slots-popup-close></div>
  <div class="modal__panel modal__panel--sm" role="dialog" aria-modal="true" aria-labelledby="timeSlotsPopupTitle">
    <div class="modal__header">
      <div class="dash-title" id="timeSlotsPopupTitle" style="font-size:1.1rem;margin:0;">Message</div>
      <button class="icon-btn" type="button" aria-label="Close" data-slots-popup-close>✕</button>
    </div>
    <div class="modal__body" id="timeSlotsPopupBody" style="padding-top: 8px; padding-bottom: 12px;"></div>
    <div class="modal__footer">
      <button type="button" class="btn-soft" data-slots-popup-cancel style="display:none;">Cancel</button>
      <button type="button" class="btn-soft btn-primary" data-slots-popup-ok>OK</button>
    </div>
  </div>
</div>

