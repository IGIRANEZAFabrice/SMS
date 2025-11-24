<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getStockData':
        $sql = "SELECT
                    i.item_id,
                    i.item_name,
                    i.cat_id,
                    s.qty,
                    u.unit_name AS item_unit,
                    i.price,
                    i.min_price
                FROM
                    tbl_items i
                LEFT JOIN
                    tbl_item_stock s ON i.item_id = s.item_id
                LEFT JOIN
                    tbl_units u ON i.unit_id = u.unit_id
                WHERE
                    i.item_status = 1";

        $res = $conn->query($sql);
        $stockData = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $stockData[] = [
                    'item_id' => (int)$row['item_id'],
                    'item_name' => (string)$row['item_name'],
                    'cat_id' => (int)$row['cat_id'],
                    'qty' => (float)($row['qty'] ?? 0),
                    'item_unit' => (string)($row['item_unit'] ?? 'N/A'),
                    'price' => (float)$row['price'],
                    'min_price' => (float)($row['min_price'] ?? $row['price'])
                ];
            }
        }
        echo json_encode(['success' => true, 'stockData' => $stockData]);
        break;

    case 'getCategories':
        $sql = "SELECT cat_id, cat_name FROM tbl_categories";
        $res = $conn->query($sql);
        $categories = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $categories[] = [
                    'cat_id' => (int)$row['cat_id'],
                    'cat_name' => (string)$row['cat_name']
                ];
            }
        }
        echo json_encode(['success' => true, 'categories' => $categories]);
        break;

    case 'getStockStats':
        // Total Items
        $sql_total_items = "SELECT COUNT(*) as total_items FROM tbl_items WHERE item_status = 1";
        $res_total_items = $conn->query($sql_total_items);
        $totalItems = $res_total_items->fetch_assoc()['total_items'] ?? 0;

        // Total Value
        $sql_total_value = "SELECT SUM(s.qty * i.price) as total_value
                            FROM tbl_item_stock s
                            JOIN tbl_items i ON s.item_id = i.item_id
                            WHERE i.item_status = 1";
        $res_total_value = $conn->query($sql_total_value);
        $totalValue = $res_total_value->fetch_assoc()['total_value'] ?? 0;

        // Low Stock Items (assuming low stock threshold <= 10)
        $low_stock_threshold = 10;
        $sql_low_stock = "SELECT COUNT(*) as low_stock_items
                          FROM tbl_item_stock
                          WHERE qty > 0 AND qty <= $low_stock_threshold";
        $res_low_stock = $conn->query($sql_low_stock);
        $lowStockItems = $res_low_stock->fetch_assoc()['low_stock_items'] ?? 0;

        // Out of Stock Items
        $sql_out_of_stock = "SELECT COUNT(*) as out_of_stock_items
                             FROM tbl_item_stock
                             WHERE qty = 0";
        $res_out_of_stock = $conn->query($sql_out_of_stock);
        $outOfStock = $res_out_of_stock->fetch_assoc()['out_of_stock_items'] ?? 0;

        echo json_encode([
            'success' => true,
            'stats' => [
                'totalItems' => (int)$totalItems,
                'totalValue' => (float)$totalValue,
                'lowStockItems' => (int)$lowStockItems,
                'outOfStock' => (int)$outOfStock
            ]
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>