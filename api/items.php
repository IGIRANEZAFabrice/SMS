<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sql = 'SELECT 
            i.item_id,
            i.item_name AS name,
            i.price,
            i.min_price,
            s.qty AS stock
        FROM 
            tbl_items i
        LEFT JOIN 
            tbl_item_stock s ON i.item_id = s.item_id
        WHERE 
            i.item_status = 1';

$res = $conn->query($sql);
$items = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            'item_id' => (int)$row['item_id'],
            'name' => (string)$row['name'],
            'price' => (float)$row['price'],
            'min_price' => (float)$row['min_price'],
            'stock' => (float)($row['stock'] ?? 0)
        ];
    }
}

echo json_encode(['success' => true, 'items' => $items]);
?>