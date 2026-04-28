<?php

class Tables
{
    public static function createTablesTable($conn): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS shop_tables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            table_number INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_shop_table (shop_id, table_number),
            INDEX idx_shop (shop_id),
            INDEX idx_token (token)
        )";
        return mysqli_query($conn, $sql) === true;
    }

    public static function ensureTableRow($conn, int $shopId, int $tableNumber): array
    {
        $token = bin2hex(random_bytes(16));

        // Insert or update token (rotate token on regenerate)
        $stmt = $conn->prepare("INSERT INTO shop_tables (shop_id, table_number, token)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE token = VALUES(token)");
        if (!$stmt) return ['ok' => false, 'id' => 0, 'token' => ''];
        $stmt->bind_param('iis', $shopId, $tableNumber, $token);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) return ['ok' => false, 'id' => 0, 'token' => ''];

        $stmt2 = $conn->prepare("SELECT id, token FROM shop_tables WHERE shop_id = ? AND table_number = ? LIMIT 1");
        if (!$stmt2) return ['ok' => false, 'id' => 0, 'token' => ''];
        $stmt2->bind_param('ii', $shopId, $tableNumber);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt2->close();
        if (!$row) return ['ok' => false, 'id' => 0, 'token' => ''];
        return ['ok' => true, 'id' => (int) $row['id'], 'token' => (string) $row['token']];
    }

    /** @return array<int, array<string,mixed>> */
    public static function listByShop($conn, int $shopId): array
    {
        $stmt = $conn->prepare("SELECT id, shop_id, table_number, token, created_at
                                FROM shop_tables
                                WHERE shop_id = ?
                                ORDER BY table_number ASC, id ASC");
        if (!$stmt) return [];
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /** @return array<string,mixed>|null */
    public static function getById($conn, int $tableId): ?array
    {
        $stmt = $conn->prepare("SELECT id, shop_id, table_number, token FROM shop_tables WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('i', $tableId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function getByIdForShop($conn, int $shopId, int $tableId): ?array
    {
        $stmt = $conn->prepare("SELECT id, shop_id, table_number, token
                                FROM shop_tables
                                WHERE shop_id = ? AND id = ?
                                LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('ii', $shopId, $tableId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    public static function deleteById($conn, int $shopId, int $tableId): bool
    {
        $stmt = $conn->prepare("DELETE FROM shop_tables WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('ii', $shopId, $tableId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** @return array<int,int> list of deleted table ids */
    public static function deleteAboveTableNumber($conn, int $shopId, int $maxTableNumber): array
    {
        $maxTableNumber = max(0, (int) $maxTableNumber);
        $stmt = $conn->prepare("SELECT id FROM shop_tables WHERE shop_id = ? AND table_number > ? ORDER BY table_number ASC");
        if (!$stmt) return [];
        $stmt->bind_param('ii', $shopId, $maxTableNumber);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $ids[] = (int) ($row['id'] ?? 0);
        }
        $stmt->close();
        if (count($ids) === 0) return [];

        $stmt2 = $conn->prepare("DELETE FROM shop_tables WHERE shop_id = ? AND table_number > ?");
        if (!$stmt2) return [];
        $stmt2->bind_param('ii', $shopId, $maxTableNumber);
        $stmt2->execute();
        $stmt2->close();
        return $ids;
    }
}

