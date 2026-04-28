<?php

class TimeSlots
{
    public static function createTimeSlotsTable($conn): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS time_slots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            name VARCHAR(80) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (shop_id),
            INDEX (is_active)
        )";

        return mysqli_query($conn, $sql) === true;
    }

    /** @return array<int, array<string,mixed>> */
    public static function listByShop($conn, int $shopId, bool $onlyActive = false): array
    {
        $sql = "SELECT id, shop_id, name, start_time, end_time, is_active, created_at, updated_at
                FROM time_slots
                WHERE shop_id = ?" . ($onlyActive ? " AND is_active = 1" : "") . "
                ORDER BY start_time ASC, id ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $rows = [];
        $id = $shop_id = $name = $start_time = $end_time = $is_active = $created_at = $updated_at = null;
        $stmt->bind_result($id, $shop_id, $name, $start_time, $end_time, $is_active, $created_at, $updated_at);
        while ($stmt->fetch()) {
            $rows[] = [
                'id' => $id,
                'shop_id' => $shop_id,
                'name' => $name,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'is_active' => $is_active,
                'created_at' => $created_at,
                'updated_at' => $updated_at,
            ];
        }
        $stmt->close();
        return $rows;
    }

    public static function countByShop($conn, int $shopId, bool $onlyActive = false): int
    {
        $sql = "SELECT COUNT(*) AS c
                FROM time_slots
                WHERE shop_id = ?" . ($onlyActive ? " AND is_active = 1" : "");
        $stmt = $conn->prepare($sql);
        if (!$stmt) return 0;
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return (int) ($row['c'] ?? 0);
    }

    /** @return array<int, array<string,mixed>> */
    public static function listByShopPage($conn, int $shopId, int $limit, int $offset, bool $onlyActive = false): array
    {
        $limit = max(1, min(50, (int) $limit));
        $offset = max(0, (int) $offset);

        $sql = "SELECT id, shop_id, name, start_time, end_time, is_active, created_at, updated_at
                FROM time_slots
                WHERE shop_id = ?" . ($onlyActive ? " AND is_active = 1" : "") . "
                ORDER BY start_time ASC, id ASC
                LIMIT $limit OFFSET $offset";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $rows = [];
        $id = $shop_id = $name = $start_time = $end_time = $is_active = $created_at = $updated_at = null;
        $stmt->bind_result($id, $shop_id, $name, $start_time, $end_time, $is_active, $created_at, $updated_at);
        while ($stmt->fetch()) {
            $rows[] = [
                'id' => $id,
                'shop_id' => $shop_id,
                'name' => $name,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'is_active' => $is_active,
                'created_at' => $created_at,
                'updated_at' => $updated_at,
            ];
        }
        $stmt->close();
        return $rows;
    }

    /** @return array<string,mixed>|null */
    public static function getById($conn, int $shopId, int $id): ?array
    {
        $stmt = $conn->prepare("SELECT id, shop_id, name, start_time, end_time, is_active
                                FROM time_slots
                                WHERE shop_id = ? AND id = ?
                                LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('ii', $shopId, $id);
        $stmt->execute();
        $rid = $shop_id = $name = $start_time = $end_time = $is_active = null;
        $stmt->bind_result($rid, $shop_id, $name, $start_time, $end_time, $is_active);
        $row = null;
        if ($stmt->fetch()) {
            $row = [
                'id' => $rid,
                'shop_id' => $shop_id,
                'name' => $name,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'is_active' => $is_active,
            ];
        }
        $stmt->close();
        return $row;
    }

    public static function create($conn, int $shopId, string $name, string $startTime, string $endTime, int $isActive): bool
    {
        $stmt = $conn->prepare("INSERT INTO time_slots (shop_id, name, start_time, end_time, is_active)
                                VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param('isssi', $shopId, $name, $startTime, $endTime, $isActive);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function update($conn, int $shopId, int $id, string $name, string $startTime, string $endTime, int $isActive): bool
    {
        $stmt = $conn->prepare("UPDATE time_slots
                                SET name = ?, start_time = ?, end_time = ?, is_active = ?
                                WHERE shop_id = ? AND id = ?
                                LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('sssiii', $name, $startTime, $endTime, $isActive, $shopId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function softDelete($conn, int $shopId, int $id): bool
    {
        $stmt = $conn->prepare("UPDATE time_slots SET is_active = 0 WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('ii', $shopId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function delete($conn, int $shopId, int $id): bool
    {
        $stmt = $conn->prepare("DELETE FROM time_slots WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('ii', $shopId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function toggle($conn, int $shopId, int $id, int $isActive): bool
    {
        $stmt = $conn->prepare("UPDATE time_slots SET is_active = ? WHERE shop_id = ? AND id = ? LIMIT 1");
        if (!$stmt) return false;
        $isActive = $isActive ? 1 : 0;
        $stmt->bind_param('iii', $isActive, $shopId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** returns slots matching current server time */
    public static function currentSlotIds($conn, int $shopId): array
    {
        // Use TIME(NOW()) and handle wrap-around slots (e.g. 21:00–02:00)
        $sql = "SELECT id
                FROM time_slots
                WHERE shop_id = ? AND is_active = 1
                  AND (
                    (start_time <= end_time AND TIME(NOW()) BETWEEN start_time AND end_time)
                    OR
                    (start_time > end_time AND (TIME(NOW()) >= start_time OR TIME(NOW()) <= end_time))
                  )";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $ids = [];
        $id = null;
        $stmt->bind_result($id);
        while ($stmt->fetch()) {
            $ids[] = (int) $id;
        }
        $stmt->close();
        return $ids;
    }

    public static function formatLabel(array $slot): string
    {
        $name = (string) ($slot['name'] ?? '');
        $start = (string) ($slot['start_time'] ?? '');
        $end = (string) ($slot['end_time'] ?? '');
        return trim($name) . " (" . substr($start, 0, 5) . "–" . substr($end, 0, 5) . ")";
    }
}

