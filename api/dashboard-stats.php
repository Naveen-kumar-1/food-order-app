<?php

// Compatibility endpoint for dashboard stats.
// Returns the same stats as OrdersController?action=stats.

session_start();

require_once __DIR__ . '/../App/Model/Config.php';
require_once __DIR__ . '/../App/Helpers/Orders.php';

header('Content-Type: application/json; charset=UTF-8');

function respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

$uid = (int) ($_SESSION['user_id'] ?? 0);
if ($uid <= 0) respond(['success' => false, 'message' => 'Unauthorized'], 401);

$conn = Config::getConnection();
if (!$conn) respond(['success' => false, 'message' => 'Database connection failed'], 500);

Orders::createOrdersTables($conn);
$stats = Orders::statsByShop($conn, $uid);
$conn->close();

respond(['success' => true, 'stats' => $stats]);

