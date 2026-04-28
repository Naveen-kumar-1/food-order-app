<?php

session_start();

require_once __DIR__ . '/../Model/Config.php';
require_once __DIR__ . '/../Helpers/TimeSlots.php';
require_once __DIR__ . '/../Helpers/Products.php';

header('Content-Type: application/json; charset=UTF-8');

function ts_respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function ts_require_owner(): int
{
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) ts_respond(['success' => false, 'message' => 'Unauthorized'], 401);
    return $uid;
}

function is_time(string $v): bool
{
    return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $v);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_REQUEST['action'] ?? '';

$conn = Config::getConnection();
if (!$conn) ts_respond(['success' => false, 'message' => 'Database connection failed'], 500);

if (!TimeSlots::createTimeSlotsTable($conn)) {
    $conn->close();
    ts_respond(['success' => false, 'message' => 'Failed to init time slots table'], 500);
}

// Ensure products table exists for assignment checks
if (!Products::createProductsTable($conn)) {
    $conn->close();
    ts_respond(['success' => false, 'message' => 'Failed to init products table'], 500);
}
Products::ensureTimeSlotIdColumn($conn);

if ($action === 'list') {
    $shopId = ts_require_owner();
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = (int) ($_GET['per_page'] ?? 10);
    $page = max(1, $page);
    $perPage = max(1, min(50, $perPage));
    $offset = ($page - 1) * $perPage;

    $total = TimeSlots::countByShop($conn, $shopId, false);
    $rows = TimeSlots::listByShopPage($conn, $shopId, $perPage, $offset, false);
    $ids = array_map(fn($r) => (int) ($r['id'] ?? 0), $rows);
    $counts = Products::assignedCountsByTimeSlotIds($conn, $shopId, $ids);
    foreach ($rows as &$r) {
        $rid = (string) ($r['id'] ?? '');
        $r['assigned_count'] = (int) ($counts[$rid] ?? 0);
    }
    unset($r);
    $conn->close();
    ts_respond([
        'success' => true,
        'timeSlots' => $rows,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ],
    ]);
}

if ($action === 'listActive') {
    $shopId = ts_require_owner();
    $rows = TimeSlots::listByShop($conn, $shopId, true);
    $ids = array_map(fn($r) => (int) ($r['id'] ?? 0), $rows);
    $counts = Products::assignedCountsByTimeSlotIds($conn, $shopId, $ids);
    foreach ($rows as &$r) {
        $rid = (string) ($r['id'] ?? '');
        $r['assigned_count'] = (int) ($counts[$rid] ?? 0);
    }
    unset($r);
    $conn->close();
    ts_respond(['success' => true, 'timeSlots' => $rows]);
}

if ($action === 'create' && $method === 'POST') {
    $shopId = ts_require_owner();
    $name = trim($_POST['name'] ?? '');
    $start = trim($_POST['start_time'] ?? '');
    $end = trim($_POST['end_time'] ?? '');
    $isActive = isset($_POST['is_active']) ? (int) !!$_POST['is_active'] : 1;

    if ($name === '' || $start === '' || $end === '') {
        $conn->close();
        ts_respond(['success' => false, 'message' => 'Name, start time and end time are required'], 422);
    }
    if (!is_time($start) || !is_time($end)) {
        $conn->close();
        ts_respond(['success' => false, 'message' => 'Invalid time format (use HH:MM)'], 422);
    }

    $ok = TimeSlots::create($conn, $shopId, $name, $start, $end, $isActive);
    $conn->close();
    if (!$ok) ts_respond(['success' => false, 'message' => 'Failed to create time slot'], 500);
    ts_respond(['success' => true, 'message' => 'Time slot created']);
}

if ($action === 'update' && $method === 'POST') {
    $shopId = ts_require_owner();
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $start = trim($_POST['start_time'] ?? '');
    $end = trim($_POST['end_time'] ?? '');
    $isActive = isset($_POST['is_active']) ? (int) !!$_POST['is_active'] : 1;

    if ($id <= 0) {
        $conn->close();
        ts_respond(['success' => false, 'message' => 'Invalid time slot id'], 422);
    }
    if ($name === '' || $start === '' || $end === '') {
        $conn->close();
        ts_respond(['success' => false, 'message' => 'Name, start time and end time are required'], 422);
    }
    if (!is_time($start) || !is_time($end)) {
        $conn->close();
        ts_respond(['success' => false, 'message' => 'Invalid time format (use HH:MM)'], 422);
    }
    if (!TimeSlots::getById($conn, $shopId, $id)) {
        $conn->close();
        ts_respond(['success' => false, 'message' => 'Time slot not found'], 404);
    }

    $ok = TimeSlots::update($conn, $shopId, $id, $name, $start, $end, $isActive);
    $conn->close();
    if (!$ok) ts_respond(['success' => false, 'message' => 'Failed to update time slot'], 500);
    ts_respond(['success' => true, 'message' => 'Time slot updated']);
}

if ($action === 'delete' && $method === 'POST') {
    $shopId = ts_require_owner();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        $conn->close();
        ts_respond(['success' => false, 'message' => 'Invalid time slot id'], 422);
    }

    // Do not allow deleting if assigned to products
    $assigned = Products::countByTimeSlotId($conn, $shopId, $id);
    if ($assigned > 0) {
        $conn->close();
        ts_respond([
            'success' => false,
            'message' => 'This time slot is assigned to one or more products. To delete it, you must either reassign the products to a different time slot or delete those products.',
        ], 409);
    }

    // Hard delete from DB when safe (no assigned products)
    $ok = TimeSlots::delete($conn, $shopId, $id);
    $conn->close();
    if (!$ok) ts_respond(['success' => false, 'message' => 'Failed to delete time slot'], 500);
    ts_respond(['success' => true, 'message' => 'Time slot deleted']);
}

if ($action === 'toggle' && $method === 'POST') {
    $shopId = ts_require_owner();
    $id = (int) ($_POST['id'] ?? 0);
    $isActive = (int) ($_POST['is_active'] ?? 0);
    $isActive = $isActive ? 1 : 0;
    if ($id <= 0) {
        $conn->close();
        ts_respond(['success' => false, 'message' => 'Invalid time slot id'], 422);
    }
    $ok = TimeSlots::toggle($conn, $shopId, $id, $isActive);
    $conn->close();
    if (!$ok) ts_respond(['success' => false, 'message' => 'Failed to update status'], 500);
    ts_respond(['success' => true, 'message' => 'Status updated']);
}

$conn->close();
ts_respond(['success' => false, 'message' => 'Invalid action'], 400);

