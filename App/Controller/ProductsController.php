<?php

session_start();

require_once __DIR__ . '/../Model/Config.php';
require_once __DIR__ . '/../Helpers/Products.php';
require_once __DIR__ . '/../Helpers/TimeSlots.php';

header('Content-Type: application/json; charset=UTF-8');
// Prevent any accidental output from breaking JSON
ob_start();

/* ── Helpers ──────────────────────────────────────────────────────────── */

function respond(array $payload, int $code = 200): void
{
    ob_end_clean(); // discard any stray output
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function requireOwner(): int
{
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) {
        respond(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    return $uid;
}

function postStr(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function postInt(string $key, int $default = 0): int
{
    return (int) ($_POST[$key] ?? $default);
}

/* ── Bootstrap ────────────────────────────────────────────────────────── */

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
// Support both GET and POST for action param
$action = postStr('action') ?: trim((string) ($_GET['action'] ?? ''));

$conn = Config::getConnection();
if (!$conn) {
    respond(['success' => false, 'message' => 'Database connection failed'], 500);
}

// Ensure tables exist
if (!Products::createProductsTable($conn)) {
    $conn->close();
    respond(['success' => false, 'message' => 'Failed to init products table'], 500);
}
Products::ensureTimeSlotIdColumn($conn);

if (!TimeSlots::createTimeSlotsTable($conn)) {
    $conn->close();
    respond(['success' => false, 'message' => 'Failed to init time slots table'], 500);
}

/* ── Actions ──────────────────────────────────────────────────────────── */

// ── LIST ──────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $shopId = requireOwner();

    $page = (int) ($_GET['page'] ?? 1);
    $perPage = (int) ($_GET['per_page'] ?? 10);
    $page = max(1, $page);
    $perPage = max(1, min(50, $perPage));
    $offset = ($page - 1) * $perPage;

    $total = Products::countByShop($conn, $shopId);
    $rows  = Products::listByShopPage($conn, $shopId, $perPage, $offset);
    $slots = TimeSlots::listByShop($conn, $shopId, true);

    // Build label map: { "slot_id" => "Slot label" }
    $slotLabels = [];
    foreach ($slots as $s) {
        $slotLabels[(string) $s['id']] = TimeSlots::formatLabel($s);
    }

    // Cast numeric fields so JS receives correct types
    $rows = array_map(function ($p) {
        $p['id']           = (int)   $p['id'];
        $p['shop_id']      = (int)   $p['shop_id'];
        $p['price']        = (float) $p['price'];
        $p['time_slot_id'] = $p['time_slot_id'] !== null ? (int) $p['time_slot_id'] : null;
        $p['is_enabled']   = (int)   $p['is_enabled'];
        return $p;
    }, $rows);

    $conn->close();
    respond([
        'success' => true,
        'products' => $rows,
        'timeSlots' => $slotLabels,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ],
    ]);
}

// ── CREATE ────────────────────────────────────────────────────────────────
if ($action === 'create') {
    if ($method !== 'POST') {
        $conn->close();
        respond(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $shopId = requireOwner();

    $name        = postStr('name');
    $priceRaw    = postStr('price');
    $description = postStr('description');
    $timeSlotId  = postInt('time_slot_id');
    $isEnabled   = postStr('is_enabled') !== '' ? (int) (bool) postStr('is_enabled') : 1;

    // Validate required fields
    $errors = [];
    if ($name === '')                                           $errors[] = 'Product name is required';
    if ($priceRaw === '')                                       $errors[] = 'Price is required';
    elseif (!is_numeric($priceRaw) || (float)$priceRaw < 0)   $errors[] = 'Price must be a valid number (≥ 0)';
    if ($timeSlotId <= 0)                                      $errors[] = 'Time slot is required';

    if (!empty($errors)) {
        $conn->close();
        respond(['success' => false, 'message' => implode('. ', $errors)], 422);
    }

    // Validate time slot belongs to this shop and is active
    $slot = TimeSlots::getById($conn, $shopId, $timeSlotId);
    if (!$slot) {
        $conn->close();
        respond(['success' => false, 'message' => 'Selected time slot does not exist'], 422);
    }
    if ((int) $slot['is_active'] !== 1) {
        $conn->close();
        respond(['success' => false, 'message' => 'Selected time slot is inactive. Please choose an active slot.'], 422);
    }

    $legacy = (string) ($slot['name'] ?? '');
    $ok     = Products::create($conn, $shopId, $name, (float) $priceRaw, $description, $timeSlotId, $legacy, $isEnabled);
    $conn->close();

    if (!$ok) respond(['success' => false, 'message' => 'Failed to create product. Please try again.'], 500);
    respond(['success' => true, 'message' => 'Product created successfully']);
}

// ── UPDATE ────────────────────────────────────────────────────────────────
if ($action === 'update') {
    if ($method !== 'POST') {
        $conn->close();
        respond(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $shopId = requireOwner();

    $id          = postInt('id');
    $name        = postStr('name');
    $priceRaw    = postStr('price');
    $description = postStr('description');
    $timeSlotId  = postInt('time_slot_id');
    $isEnabled   = postStr('is_enabled') !== '' ? (int) (bool) postStr('is_enabled') : 1;

    // Validate
    $errors = [];
    if ($id <= 0)                                              $errors[] = 'Invalid product ID';
    if ($name === '')                                          $errors[] = 'Product name is required';
    if ($priceRaw === '')                                      $errors[] = 'Price is required';
    elseif (!is_numeric($priceRaw) || (float)$priceRaw < 0)  $errors[] = 'Price must be a valid number (≥ 0)';
    if ($timeSlotId <= 0)                                     $errors[] = 'Time slot is required';

    if (!empty($errors)) {
        $conn->close();
        respond(['success' => false, 'message' => implode('. ', $errors)], 422);
    }

    // Verify the product exists and belongs to this shop
    if (!Products::getById($conn, $shopId, $id)) {
        $conn->close();
        respond(['success' => false, 'message' => 'Product not found'], 404);
    }

    // Validate time slot
    $slot = TimeSlots::getById($conn, $shopId, $timeSlotId);
    if (!$slot) {
        $conn->close();
        respond(['success' => false, 'message' => 'Selected time slot does not exist'], 422);
    }
    if ((int) $slot['is_active'] !== 1) {
        $conn->close();
        respond(['success' => false, 'message' => 'Selected time slot is inactive. Please choose an active slot.'], 422);
    }

    $legacy = (string) ($slot['name'] ?? '');
    $ok     = Products::update($conn, $shopId, $id, $name, (float) $priceRaw, $description, $timeSlotId, $legacy, $isEnabled);
    $conn->close();

    if (!$ok) respond(['success' => false, 'message' => 'Failed to update product. Please try again.'], 500);
    respond(['success' => true, 'message' => 'Product updated successfully']);
}

// ── DELETE ────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    if ($method !== 'POST') {
        $conn->close();
        respond(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $shopId = requireOwner();
    $id     = postInt('id');

    if ($id <= 0) {
        $conn->close();
        respond(['success' => false, 'message' => 'Invalid product ID'], 422);
    }

    // Verify the product exists and belongs to this shop before deleting
    if (!Products::getById($conn, $shopId, $id)) {
        $conn->close();
        respond(['success' => false, 'message' => 'Product not found'], 404);
    }

    $ok = Products::delete($conn, $shopId, $id);
    $conn->close();

    if (!$ok) respond(['success' => false, 'message' => 'Failed to delete product. Please try again.'], 500);
    respond(['success' => true, 'message' => 'Product deleted successfully']);
}

// ── TOGGLE ────────────────────────────────────────────────────────────────
if ($action === 'toggle') {
    if ($method !== 'POST') {
        $conn->close();
        respond(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $shopId    = requireOwner();
    $id        = postInt('id');
    $isEnabled = postInt('is_enabled') ? 1 : 0;

    if ($id <= 0) {
        $conn->close();
        respond(['success' => false, 'message' => 'Invalid product ID'], 422);
    }

    // Verify product exists and belongs to this shop
    if (!Products::getById($conn, $shopId, $id)) {
        $conn->close();
        respond(['success' => false, 'message' => 'Product not found'], 404);
    }

    $ok = Products::toggle($conn, $shopId, $id, $isEnabled);
    $conn->close();

    if (!$ok) respond(['success' => false, 'message' => 'Failed to update product status'], 500);
    respond(['success' => true, 'message' => 'Status updated']);
}

// ── AVAILABLE (customer-facing) ───────────────────────────────────────────
if ($action === 'available') {
    $shopId = (int) ($_GET['shop_id'] ?? 0);
    if ($shopId <= 0) {
        $conn->close();
        respond(['success' => false, 'message' => 'shop_id is required'], 422);
    }

    $slotIds = TimeSlots::currentSlotIds($conn, $shopId);
    $rows    = Products::listAvailableBySlotIds($conn, $shopId, $slotIds);
    $conn->close();
    respond([
        'success'      => true,
        'time_slot_ids' => $slotIds,
        'products'     => $rows,
    ]);
}

/* ── Fallback ─────────────────────────────────────────────────────────── */
$conn->close();
respond(['success' => false, 'message' => 'Invalid or missing action'], 400);