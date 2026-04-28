<?php

class Orders
{
    public static function createOrdersTables($conn): bool
    {
        $sql1 = "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            table_id INT NOT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'Pending',
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_shop (shop_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at),
            INDEX idx_updated (updated_at)
        )";

        $sql2 = "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            qty INT NOT NULL,
            INDEX idx_order (order_id),
            INDEX idx_product (product_id)
        )";

        $ok1 = mysqli_query($conn, $sql1) === true;
        $ok2 = mysqli_query($conn, $sql2) === true;

        // Backward compatibility if table existed before updated_at was added
        @mysqli_query($conn, "ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        @mysqli_query($conn, "ALTER TABLE orders ADD INDEX idx_updated (updated_at)");

        return $ok1 && $ok2;
    }

    public static function create($conn, int $shopId, int $tableId, array $items, float $total): int
    {
        $stmt = $conn->prepare("INSERT INTO orders (shop_id, table_id, status, total_amount) VALUES (?, ?, 'Pending', ?)");
        if (!$stmt) return 0;
        $stmt->bind_param('iid', $shopId, $tableId, $total);
        $ok = $stmt->execute();
        $orderId = $ok ? (int) $stmt->insert_id : 0;
        $stmt->close();
        if ($orderId <= 0) return 0;

        $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, price, qty) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt2) return 0;

        foreach ($items as $it) {
            $pid = (int) ($it['product_id'] ?? 0);
            $name = (string) ($it['name'] ?? '');
            $price = (float) ($it['price'] ?? 0);
            $qty = (int) ($it['qty'] ?? 0);
            if ($pid <= 0 || $qty <= 0 || $name === '') continue;
            $stmt2->bind_param('iisdi', $orderId, $pid, $name, $price, $qty);
            $stmt2->execute();
        }
        $stmt2->close();
        return $orderId;
    }

    /** @return array<int, array<string,mixed>> */
    public static function listByShop($conn, int $shopId, int $limit = 50): array
    {
        $limit = max(1, min(200, (int) $limit));
        $stmt = $conn->prepare("SELECT id, shop_id, table_id, status, total_amount, created_at
                                FROM orders
                                WHERE shop_id = ?
                                ORDER BY id DESC
                                LIMIT $limit");
        if (!$stmt) return [];
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    public static function countByShop($conn, int $shopId): int
    {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE shop_id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return (int) ($row['c'] ?? 0);
    }

    public static function countByShopRange($conn, int $shopId, string $fromInclusive, string $toExclusive): int
    {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c
                                FROM orders
                                WHERE shop_id = ?
                                  AND created_at >= ?
                                  AND created_at < ?");
        if (!$stmt) return 0;
        $stmt->bind_param('iss', $shopId, $fromInclusive, $toExclusive);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return (int) ($row['c'] ?? 0);
    }

    /** @return array<int, array<string,mixed>> */
    public static function listByShopPage($conn, int $shopId, int $limit, int $offset): array
    {
        $limit = max(1, min(50, (int) $limit));
        $offset = max(0, (int) $offset);
        $stmt = $conn->prepare("SELECT id, shop_id, table_id, status, total_amount, created_at, updated_at
                                FROM orders
                                WHERE shop_id = ?
                                ORDER BY id DESC
                                LIMIT $limit OFFSET $offset");
        if (!$stmt) return [];
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** @return array<int, array<string,mixed>> */
    public static function listByShopPageRange($conn, int $shopId, string $fromInclusive, string $toExclusive, int $limit, int $offset): array
    {
        $limit = max(1, min(50, (int) $limit));
        $offset = max(0, (int) $offset);
        $stmt = $conn->prepare("SELECT id, shop_id, table_id, status, total_amount, created_at, updated_at
                                FROM orders
                                WHERE shop_id = ?
                                  AND created_at >= ?
                                  AND created_at < ?
                                ORDER BY id DESC
                                LIMIT $limit OFFSET $offset");
        if (!$stmt) return [];
        $stmt->bind_param('iss', $shopId, $fromInclusive, $toExclusive);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** @return array<string,int|float> */
    public static function statsByShop($conn, int $shopId): array
    {
        $stmt = $conn->prepare("SELECT
                                  COUNT(*) AS total_orders,
                                  SUM(CASE WHEN status IN ('Pending','Preparing') THEN 1 ELSE 0 END) AS active_orders,
                                  SUM(CASE WHEN status IN ('Completed','Served') THEN 1 ELSE 0 END) AS completed_orders,
                                  COALESCE(SUM(total_amount),0) AS revenue
                                FROM orders
                                WHERE shop_id = ?");
        if (!$stmt) return ['total_orders' => 0, 'active_orders' => 0, 'completed_orders' => 0, 'revenue' => 0.0];
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return [
            'total_orders' => (int) ($row['total_orders'] ?? 0),
            'active_orders' => (int) ($row['active_orders'] ?? 0),
            'completed_orders' => (int) ($row['completed_orders'] ?? 0),
            'revenue' => (float) ($row['revenue'] ?? 0),
        ];
    }

    /** @return array<string,mixed>|null */
    public static function getById($conn, int $shopId, int $orderId): ?array
    {
        $stmt = $conn->prepare("SELECT id, shop_id, table_id, status, total_amount, created_at, updated_at
                                FROM orders
                                WHERE shop_id = ? AND id = ?
                                LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('ii', $shopId, $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public static function itemsForOrder($conn, int $orderId): array
    {
        $stmt = $conn->prepare("SELECT product_id, name, price, qty
                                FROM order_items
                                WHERE order_id = ?
                                ORDER BY id ASC");
        if (!$stmt) return [];
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** @return array<string, array<int, array<string,mixed>>> map order_id => items[] */
    public static function itemsForOrders($conn, array $orderIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $orderIds), fn($v) => $v > 0));
        if (count($ids) === 0) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $sql = "SELECT order_id, product_id, name, price, qty
                FROM order_items
                WHERE order_id IN ($placeholders)
                ORDER BY order_id DESC, id ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        $refs = [$types];
        foreach ($ids as $k => $v) $refs[] = &$ids[$k];
        $stmt->bind_param(...$refs);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $oid = (string) ($row['order_id'] ?? '');
            if ($oid === '') continue;
            if (!isset($out[$oid])) $out[$oid] = [];
            $out[$oid][] = [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'price' => (float) ($row['price'] ?? 0),
                'qty' => (int) ($row['qty'] ?? 0),
            ];
        }
        $stmt->close();
        return $out;
    }

    /** @return array<int, array<string,mixed>> */
    public static function listByTable($conn, int $shopId, int $tableId, int $limit = 50): array
    {
        $limit = max(1, min(200, (int) $limit));
        $stmt = $conn->prepare("SELECT id, shop_id, table_id, status, total_amount, created_at, updated_at
                                FROM orders
                                WHERE shop_id = ? AND table_id = ?
                                ORDER BY id DESC
                                LIMIT $limit");
        if (!$stmt) return [];
        $stmt->bind_param('ii', $shopId, $tableId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    public static function deleteOrder($conn, int $shopId, int $orderId): bool
    {
        // Ensure belongs to shop
        $stmt = $conn->prepare("SELECT id FROM orders WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('ii', $shopId, $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        $okExists = (bool) ($res && $res->fetch_assoc());
        $stmt->close();
        if (!$okExists) return false;

        $stmt2 = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        if (!$stmt2) return false;
        $stmt2->bind_param('i', $orderId);
        $ok1 = $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("DELETE FROM orders WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt3) return false;
        $stmt3->bind_param('ii', $shopId, $orderId);
        $ok2 = $stmt3->execute();
        $stmt3->close();

        return $ok1 && $ok2;
    }

    public static function countActiveByTable($conn, int $shopId, int $tableId): int
    {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c
                                FROM orders
                                WHERE shop_id = ? AND table_id = ? AND status IN ('Pending','Preparing')");
        if (!$stmt) return 0;
        $stmt->bind_param('ii', $shopId, $tableId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return (int) ($row['c'] ?? 0);
    }

    public static function updateStatus($conn, int $shopId, int $orderId, string $status): bool
    {
        $allowed = ['Pending', 'Preparing', 'Completed', 'Served', 'Cancelled'];
        if (!in_array($status, $allowed, true)) return false;
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('sii', $status, $shopId, $orderId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

