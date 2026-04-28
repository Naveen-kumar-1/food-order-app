<?php

class Products
{
    public static function createProductsTable($conn): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT,
            time_slot_id INT NULL,
            time_slot VARCHAR(32) NOT NULL DEFAULT '',
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_shop (shop_id),
            INDEX idx_slot_id (time_slot_id),
            INDEX idx_slot_str (time_slot),
            INDEX idx_enabled (is_enabled)
        )";

        return mysqli_query($conn, $sql) === true;
    }

    public static function ensureTimeSlotIdColumn($conn): void
    {
        // Backward compatible: add column if table existed before this change
        @mysqli_query($conn, "ALTER TABLE products ADD COLUMN time_slot_id INT NULL");
        @mysqli_query($conn, "ALTER TABLE products ADD INDEX (time_slot_id)");
    }

    // ── List ──────────────────────────────────────────────────────────────

    /** @return array<int, array<string,mixed>> */
    public static function listByShop($conn, int $shopId): array
    {
        $stmt = $conn->prepare(
            "SELECT id, shop_id, name, price, description,
                    time_slot_id, time_slot, is_enabled, created_at, updated_at
             FROM products
             WHERE shop_id = ?
             ORDER BY id DESC"
        );
        if (!$stmt) return [];

        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public static function countByShop($conn, int $shopId): int
    {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE shop_id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return (int) ($row['c'] ?? 0);
    }

    /** @return array<int, array<string,mixed>> */
    public static function listByShopPage($conn, int $shopId, int $limit, int $offset): array
    {
        $limit = max(1, min(50, (int) $limit));
        $offset = max(0, (int) $offset);

        // LIMIT/OFFSET cannot be bound in MySQLi reliably across setups; cast to int and interpolate
        $sql = "SELECT id, shop_id, name, price, description,
                       time_slot_id, time_slot, is_enabled, created_at, updated_at
                FROM products
                WHERE shop_id = ?
                ORDER BY id DESC
                LIMIT $limit OFFSET $offset";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    // ── Get by ID ─────────────────────────────────────────────────────────

    /** @return array<string,mixed>|null */
    public static function getById($conn, int $shopId, int $productId): ?array
    {
        $stmt = $conn->prepare(
            "SELECT id, shop_id, name, price, description,
                    time_slot_id, time_slot, is_enabled
             FROM products
             WHERE shop_id = ? AND id = ?
             LIMIT 1"
        );
        if (!$stmt) return null;

        $stmt->bind_param('ii', $shopId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // ── Create ────────────────────────────────────────────────────────────

    /**
     * @param int    $shopId
     * @param string $name
     * @param float  $price
     * @param string $description
     * @param int    $timeSlotId
     * @param string $legacyTimeSlot  the slot's name stored for backward compat
     * @param int    $isEnabled       1 or 0
     */
    public static function create(
        $conn,
        int    $shopId,
        string $name,
        float  $price,
        string $description,
        int    $timeSlotId,
        string $legacyTimeSlot,
        int    $isEnabled
    ): bool {
        $stmt = $conn->prepare(
            "INSERT INTO products (shop_id, name, price, description, time_slot_id, time_slot, is_enabled)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) return false;

        // i = int, s = string, d = double/float
        // shop_id(i) name(s) price(d) description(s) time_slot_id(i) time_slot(s) is_enabled(i)
        $stmt->bind_param('isdisis', $shopId, $name, $price, $description, $timeSlotId, $legacyTimeSlot, $isEnabled);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ── Update ────────────────────────────────────────────────────────────

    public static function update(
        $conn,
        int    $shopId,
        int    $productId,
        string $name,
        float  $price,
        string $description,
        int    $timeSlotId,
        string $legacyTimeSlot,
        int    $isEnabled
    ): bool {
        $stmt = $conn->prepare(
            "UPDATE products
             SET name = ?, price = ?, description = ?, time_slot_id = ?, time_slot = ?, is_enabled = ?
             WHERE shop_id = ? AND id = ?
             LIMIT 1"
        );
        if (!$stmt) return false;

        // name(s) price(d) description(s) time_slot_id(i) time_slot(s) is_enabled(i) shop_id(i) id(i)
        $stmt->bind_param('sdsisiii', $name, $price, $description, $timeSlotId, $legacyTimeSlot, $isEnabled, $shopId, $productId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ── Delete ────────────────────────────────────────────────────────────

    public static function delete($conn, int $shopId, int $productId): bool
    {
        $stmt = $conn->prepare("DELETE FROM products WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt) return false;

        $stmt->bind_param('ii', $shopId, $productId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ── Toggle enabled ───────────────────────────────────────────────────

    public static function toggle($conn, int $shopId, int $productId, int $isEnabled): bool
    {
        $stmt = $conn->prepare("UPDATE products SET is_enabled = ? WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt) return false;

        $stmt->bind_param('iii', $isEnabled, $shopId, $productId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ── Counts ───────────────────────────────────────────────────────────

    /** @return array<string,int> map time_slot_id => assigned products count */
    public static function assignedCountsByTimeSlotIds($conn, int $shopId, array $slotIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $slotIds), fn($v) => $v > 0));
        if (count($ids) === 0) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types        = 'i' . str_repeat('i', count($ids));
        $sql          = "SELECT time_slot_id, COUNT(*) AS c
                         FROM products
                         WHERE shop_id = ? AND time_slot_id IN ($placeholders)
                         GROUP BY time_slot_id";

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        $params    = array_merge([$shopId], $ids);
        $bindTypes = $types;
        // Build references for bind_param variadic call
        $refs   = [$bindTypes];
        foreach ($params as $k => $v) {
            $refs[] = &$params[$k];
        }
        $stmt->bind_param(...$refs);
        $stmt->execute();
        $result = $stmt->get_result();
        $out    = [];
        while ($row = $result->fetch_assoc()) {
            $out[(string) $row['time_slot_id']] = (int) $row['c'];
        }
        $stmt->close();
        return $out;
    }

    public static function countByTimeSlotId($conn, int $shopId, int $timeSlotId): int
    {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE shop_id = ? AND time_slot_id = ?");
        if (!$stmt) return 0;

        $stmt->bind_param('ii', $shopId, $timeSlotId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        return (int) ($row['c'] ?? 0);
    }

    // ── Customer-facing ──────────────────────────────────────────────────

    /** @return array<int, array<string,mixed>> */
    public static function listAvailableBySlotIds($conn, int $shopId, array $slotIds): array
    {
        if (count($slotIds) === 0) return [];

        $placeholders = implode(',', array_fill(0, count($slotIds), '?'));
        $types        = 'i' . str_repeat('i', count($slotIds));
        $sql          = "SELECT id, name, price, description, time_slot_id
                         FROM products
                         WHERE shop_id = ? AND is_enabled = 1 AND time_slot_id IN ($placeholders)
                         ORDER BY id DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        $params = array_merge([$shopId], array_map('intval', $slotIds));
        $refs   = [$types];
        foreach ($params as $k => $v) {
            $refs[] = &$params[$k];
        }
        $stmt->bind_param(...$refs);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
}