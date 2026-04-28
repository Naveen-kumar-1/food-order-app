<?php

session_start();

require_once __DIR__ . '/../Model/Config.php';
require_once __DIR__ . '/../Helpers/Tables.php';
require_once __DIR__ . '/../Helpers/Orders.php';

header('Content-Type: application/json; charset=UTF-8');
ob_start();

function qr_respond(array $payload, int $code = 200): void
{
    ob_end_clean();
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function qr_require_owner(): int
{
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) qr_respond(['success' => false, 'message' => 'Unauthorized'], 401);
    return $uid;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string) ($_REQUEST['action'] ?? ''));

$conn = Config::getConnection();
if (!$conn) qr_respond(['success' => false, 'message' => 'Database connection failed'], 500);

if (!Tables::createTablesTable($conn)) {
    $conn->close();
    qr_respond(['success' => false, 'message' => 'Failed to init tables'], 500);
}

// Optional safety: do not delete tables with active orders
Orders::createOrdersTables($conn);

if ($action === 'list') {
    $shopId = qr_require_owner();
    $rows = Tables::listByShop($conn, $shopId);
    $conn->close();
    qr_respond(['success' => true, 'tables' => $rows]);
}

if ($action === 'delete' && $method === 'POST') {
    $shopId = qr_require_owner();
    $tableId = (int) ($_POST['table_id'] ?? 0);
    if ($tableId <= 0) {
        $conn->close();
        qr_respond(['success' => false, 'message' => 'Invalid table'], 422);
    }
    $t = Tables::getByIdForShop($conn, $shopId, $tableId);
    if (!$t) {
        $conn->close();
        qr_respond(['success' => false, 'message' => 'Table not found'], 404);
    }
    $active = Orders::countActiveByTable($conn, $shopId, (int) $t['id']);
    if ($active > 0) {
        $conn->close();
        qr_respond(['success' => false, 'message' => 'This table has active orders and cannot be deleted right now.'], 409);
    }
    $ok = Tables::deleteById($conn, $shopId, $tableId);
    $conn->close();
    if (!$ok) qr_respond(['success' => false, 'message' => 'Failed to delete QR code'], 500);
    qr_respond(['success' => true, 'message' => 'QR code deleted']);
}

if ($action === 'trim' && $method === 'POST') {
    $shopId = qr_require_owner();
    $count = (int) ($_POST['count'] ?? 0);
    if ($count < 0 || $count > 200) {
        $conn->close();
        qr_respond(['success' => false, 'message' => 'Invalid table count'], 422);
    }

    // Identify tables that would be removed
    $all = Tables::listByShop($conn, $shopId);
    $toDelete = array_values(array_filter($all, fn($r) => (int) ($r['table_number'] ?? 0) > $count));
    $blocked = [];
    foreach ($toDelete as $r) {
        $tid = (int) ($r['id'] ?? 0);
        if ($tid <= 0) continue;
        if (Orders::countActiveByTable($conn, $shopId, $tid) > 0) {
            $blocked[] = ['id' => $tid, 'table_number' => (int) ($r['table_number'] ?? 0)];
        }
    }
    if (count($blocked) > 0) {
        $conn->close();
        qr_respond([
            'success' => false,
            'message' => 'Some tables have active orders and cannot be deleted.',
            'blocked' => $blocked,
        ], 409);
    }

    $deletedIds = Tables::deleteAboveTableNumber($conn, $shopId, $count);
    $conn->close();
    qr_respond(['success' => true, 'deleted_ids' => $deletedIds, 'message' => 'Tables trimmed']);
}

if ($action === 'generate' && $method === 'POST') {
    $shopId = qr_require_owner();
    $count = (int) ($_POST['count'] ?? 0);
    if ($count <= 0 || $count > 200) {
        $conn->close();
        qr_respond(['success' => false, 'message' => 'Invalid table count'], 422);
    }
    $tables = [];
    for ($i = 1; $i <= $count; $i++) {
        $row = Tables::ensureTableRow($conn, $shopId, $i);
        if (!$row['ok']) {
            $conn->close();
            qr_respond(['success' => false, 'message' => 'Failed to generate tables'], 500);
        }
        $tables[] = [
            'id' => (int) $row['id'],
            'table_number' => $i,
            'token' => (string) $row['token'],
        ];
    }
    $conn->close();
    qr_respond(['success' => true, 'tables' => $tables, 'message' => 'QR tables generated']);
}

$conn->close();
qr_respond(['success' => false, 'message' => 'Invalid action'], 400);

