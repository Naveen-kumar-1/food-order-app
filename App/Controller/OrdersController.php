<?php

session_start();

require_once __DIR__ . '/../Model/Config.php';
require_once __DIR__ . '/../Helpers/Orders.php';
require_once __DIR__ . '/../Helpers/Products.php';
require_once __DIR__ . '/../Helpers/TimeSlots.php';
require_once __DIR__ . '/../Helpers/Tables.php';

header('Content-Type: application/json; charset=UTF-8');
ob_start();

function o_respond(array $payload, int $code = 200): void
{
    ob_end_clean();
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function o_require_owner(): int
{
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) o_respond(['success' => false, 'message' => 'Unauthorized'], 401);
    return $uid;
}

function o_post_str(string $k, string $d = ''): string
{
    return trim((string) ($_POST[$k] ?? $d));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string) ($_REQUEST['action'] ?? ''));

$conn = Config::getConnection();
if (!$conn) o_respond(['success' => false, 'message' => 'Database connection failed'], 500);

// Ensure tables exist
if (!Orders::createOrdersTables($conn)) {
    $conn->close();
    o_respond(['success' => false, 'message' => 'Failed to init orders tables'], 500);
}
if (!Products::createProductsTable($conn)) {
    $conn->close();
    o_respond(['success' => false, 'message' => 'Failed to init products table'], 500);
}
Products::ensureTimeSlotIdColumn($conn);
if (!TimeSlots::createTimeSlotsTable($conn)) {
    $conn->close();
    o_respond(['success' => false, 'message' => 'Failed to init time slots table'], 500);
}
if (!Tables::createTablesTable($conn)) {
    $conn->close();
    o_respond(['success' => false, 'message' => 'Failed to init tables'], 500);
}

// Customer: list available products for a table (uses shop_id from table)
if ($action === 'available') {
    $tableId = (int) ($_GET['table_id'] ?? 0);
    $token = trim((string) ($_GET['token'] ?? ''));
    if ($tableId <= 0 || $token === '') {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid table'], 422);
    }
    $t = Tables::getById($conn, $tableId);
    if (!$t || (string) ($t['token'] ?? '') !== $token) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid table'], 404);
    }
    $shopId = (int) $t['shop_id'];
    $slotIds = TimeSlots::currentSlotIds($conn, $shopId);
    $products = Products::listAvailableBySlotIds($conn, $shopId, $slotIds);
    // Cast numeric
    $products = array_map(function ($p) {
        $p['id'] = (int) $p['id'];
        $p['price'] = (float) $p['price'];
        $p['time_slot_id'] = $p['time_slot_id'] !== null ? (int) $p['time_slot_id'] : null;
        return $p;
    }, $products);
    $conn->close();
    o_respond(['success' => true, 'products' => $products, 'time_slot_ids' => $slotIds]);
}

// Customer: place order
if ($action === 'place' && $method === 'POST') {
    $tableId = (int) ($_POST['table_id'] ?? 0);
    $token = o_post_str('token');
    $itemsRaw = (string) ($_POST['items'] ?? '[]');

    if ($tableId <= 0 || $token === '') {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid table'], 422);
    }
    $t = Tables::getById($conn, $tableId);
    if (!$t || (string) ($t['token'] ?? '') !== $token) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid table'], 404);
    }
    $shopId = (int) $t['shop_id'];

    $decoded = json_decode($itemsRaw, true);
    if (!is_array($decoded) || count($decoded) === 0) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Cart is empty'], 422);
    }

    // Build qty map
    $qtyById = [];
    foreach ($decoded as $it) {
        $pid = (int) ($it['product_id'] ?? $it['id'] ?? 0);
        $qty = (int) ($it['qty'] ?? 0);
        if ($pid > 0 && $qty > 0) $qtyById[$pid] = ($qtyById[$pid] ?? 0) + $qty;
    }
    if (count($qtyById) === 0) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Cart is empty'], 422);
    }

    // Only allow ordering currently available products
    $slotIds = TimeSlots::currentSlotIds($conn, $shopId);
    $available = Products::listAvailableBySlotIds($conn, $shopId, $slotIds);
    $availMap = [];
    foreach ($available as $p) {
        $availMap[(int) $p['id']] = $p;
    }

    $items = [];
    $total = 0.0;
    foreach ($qtyById as $pid => $qty) {
        if (!isset($availMap[$pid])) continue;
        $p = $availMap[$pid];
        $price = (float) ($p['price'] ?? 0);
        $name = (string) ($p['name'] ?? '');
        if ($name === '' || $price < 0) continue;
        $items[] = ['product_id' => $pid, 'name' => $name, 'price' => $price, 'qty' => $qty];
        $total += $price * $qty;
    }
    if (count($items) === 0) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'No valid items in cart for current time slot'], 422);
    }

    $orderId = Orders::create($conn, $shopId, $tableId, $items, $total);
    if ($orderId <= 0) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Failed to place order'], 500);
    }
    $conn->close();
    o_respond(['success' => true, 'order_id' => $orderId, 'message' => 'Order placed']);
}

// Customer: get order status (requires same table token)
if ($action === 'get') {
    $orderId = (int) ($_GET['order_id'] ?? 0);
    $tableId = (int) ($_GET['table_id'] ?? 0);
    $token = trim((string) ($_GET['token'] ?? ''));
    if ($orderId <= 0 || $tableId <= 0 || $token === '') {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid request'], 422);
    }
    $t = Tables::getById($conn, $tableId);
    if (!$t || (string) ($t['token'] ?? '') !== $token) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid table'], 404);
    }
    $shopId = (int) $t['shop_id'];
    $order = Orders::getById($conn, $shopId, $orderId);
    if (!$order || (int) $order['table_id'] !== $tableId) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Order not found'], 404);
    }

    // Customer visibility rule:
    // Completed/Served remain visible for 1 hour after completion (updated_at).
    $status = (string) ($order['status'] ?? '');
    if ($status === 'Completed' || $status === 'Served') {
        $updatedAt = (string) ($order['updated_at'] ?? $order['created_at'] ?? '');
        $ts = $updatedAt ? strtotime($updatedAt) : 0;
        if ($ts > 0 && (time() - $ts) > 3600) {
            $conn->close();
            o_respond(['success' => false, 'message' => 'Order expired'], 410);
        }
    }

    $items = Orders::itemsForOrder($conn, $orderId);
    $conn->close();
    o_respond(['success' => true, 'order' => $order, 'items' => $items]);
}

// Customer: list all visible orders for a table (latest first)
if ($action === 'tableOrders') {
    // This endpoint is privacy-breaking (it reveals other devices' orders for the same table).
    // Use action=trackOrders instead, which requires explicit order IDs from the client.
    $conn->close();
    o_respond(['success' => false, 'message' => 'Deprecated. Use action=trackOrders'], 410);
}

// Customer: track only specific orders (privacy-safe; requires same table token)
if ($action === 'trackOrders' && $method === 'POST') {
    $tableId = (int) ($_POST['table_id'] ?? 0);
    $token = o_post_str('token');
    $idsRaw = (string) ($_POST['order_ids'] ?? '[]');
    if ($tableId <= 0 || $token === '') {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid table'], 422);
    }
    $t = Tables::getById($conn, $tableId);
    if (!$t || (string) ($t['token'] ?? '') !== $token) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid table'], 404);
    }
    $shopId = (int) $t['shop_id'];

    $decoded = json_decode($idsRaw, true);
    $ids = [];
    if (is_array($decoded)) {
        foreach ($decoded as $v) {
            $id = (int) $v;
            if ($id > 0) $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));
    if (count($ids) === 0) {
        $conn->close();
        o_respond(['success' => true, 'orders' => [], 'itemsByOrder' => []]);
    }

    $rows = Orders::listByIdsForTable($conn, $shopId, $tableId, $ids);

    // Customer visibility rule:
    // Only show orders from the last 1 hour (by created_at), regardless of status.
    $cutoff = time() - 3600;
    $visible = [];
    foreach ($rows as $o) {
        $createdAt = (string) ($o['created_at'] ?? '');
        $ts = $createdAt ? strtotime($createdAt) : 0;
        if ($ts > 0 && $ts < $cutoff) continue;
        $o['id'] = (int) $o['id'];
        $o['table_id'] = (int) $o['table_id'];
        $o['total_amount'] = (float) $o['total_amount'];
        $visible[] = $o;
    }
    $ids2 = array_map(fn($r) => (int) $r['id'], $visible);
    $itemsMap = Orders::itemsForOrders($conn, $ids2);
    $conn->close();
    o_respond(['success' => true, 'orders' => $visible, 'itemsByOrder' => $itemsMap]);
}

// Customer: cancel an order (only while Pending) for this table
if ($action === 'cancel' && $method === 'POST') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $tableId = (int) ($_POST['table_id'] ?? 0);
    $token = o_post_str('token');
    if ($orderId <= 0 || $tableId <= 0 || $token === '') {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid request'], 422);
    }
    $t = Tables::getById($conn, $tableId);
    if (!$t || (string) ($t['token'] ?? '') !== $token) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid table'], 404);
    }
    $shopId = (int) $t['shop_id'];
    $order = Orders::getById($conn, $shopId, $orderId);
    if (!$order || (int) $order['table_id'] !== $tableId) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Order not found'], 404);
    }
    if ((string) ($order['status'] ?? '') !== 'Pending') {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Order can only be cancelled while Pending'], 409);
    }
    $ok = Orders::updateStatus($conn, $shopId, $orderId, 'Cancelled');
    $conn->close();
    if (!$ok) o_respond(['success' => false, 'message' => 'Failed to cancel'], 500);
    o_respond(['success' => true, 'message' => 'Order cancelled']);
}

// Admin: list orders
if ($action === 'list') {
    $shopId = o_require_owner();
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = (int) ($_GET['per_page'] ?? 10);
    $page = max(1, $page);
    $perPage = max(1, min(50, $perPage));
    $offset = ($page - 1) * $perPage;

    $date = trim((string) ($_GET['date'] ?? ''));
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));

    $useRange = false;
    $fromInclusive = '';
    $toExclusive = '';

    // date=YYYY-MM-DD (single day)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $useRange = true;
        $fromInclusive = $date . ' 00:00:00';
        $toExclusive = date('Y-m-d H:i:s', strtotime($date . ' +1 day'));
    }
    // from/to date range (inclusive from, inclusive to)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $useRange = true;
        $fromInclusive = $from . ' 00:00:00';
        $toExclusive = date('Y-m-d H:i:s', strtotime($to . ' +1 day'));
    }

    $includeItems = (int) ($_GET['include_items'] ?? 0) ? 1 : 0;

    if ($useRange) {
        $total = Orders::countByShopRange($conn, $shopId, $fromInclusive, $toExclusive);
        $rows = Orders::listByShopPageRange($conn, $shopId, $fromInclusive, $toExclusive, $perPage, $offset);
    } else {
        $total = Orders::countByShop($conn, $shopId);
        $rows = Orders::listByShopPage($conn, $shopId, $perPage, $offset);
    }
    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['table_id'] = (int) $r['table_id'];
        $r['total_amount'] = (float) $r['total_amount'];
    }
    unset($r);
    $itemsByOrder = [];
    if ($includeItems) {
        $ids = array_map(fn($r) => (int) $r['id'], $rows);
        $itemsByOrder = Orders::itemsForOrders($conn, $ids);
    }
    $conn->close();
    o_respond([
        'success' => true,
        'orders' => $rows,
        'itemsByOrder' => $itemsByOrder,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ],
        'filter' => [
            'date' => $date,
            'from' => $from,
            'to' => $to,
        ],
    ]);
}

// Admin: dashboard stats
if ($action === 'stats') {
    $shopId = o_require_owner();
    $stats = Orders::statsByShop($conn, $shopId);
    $conn->close();
    o_respond(['success' => true, 'stats' => $stats]);
}

// Admin/Kitchen: update status
if ($action === 'status' && $method === 'POST') {
    $shopId = o_require_owner();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = o_post_str('status');
    if ($orderId <= 0 || $status === '') {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid request'], 422);
    }
    $ok = Orders::updateStatus($conn, $shopId, $orderId, $status);
    $conn->close();
    if (!$ok) o_respond(['success' => false, 'message' => 'Failed to update status'], 500);
    o_respond(['success' => true, 'message' => 'Status updated']);
}

// Admin/Kitchen: delete an order permanently
if ($action === 'delete' && $method === 'POST') {
    $shopId = o_require_owner();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    if ($orderId <= 0) {
        $conn->close();
        o_respond(['success' => false, 'message' => 'Invalid request'], 422);
    }
    $ok = Orders::deleteOrder($conn, $shopId, $orderId);
    $conn->close();
    if (!$ok) o_respond(['success' => false, 'message' => 'Failed to delete order'], 500);
    o_respond(['success' => true, 'message' => 'Order deleted']);
}

$conn->close();
o_respond(['success' => false, 'message' => 'Invalid action'], 400);

