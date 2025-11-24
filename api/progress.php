<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['item_id']) || empty($_GET['item_id'])) {
    echo json_encode(['error' => 'Item ID is required.']);
    exit;
}

$itemId = intval($_GET['item_id']);
$response = [];

// Fetch item details
$itemQuery = "
    SELECT 
        i.item_id,
        i.item_name,
        i.price,
        c.cat_name,
        u.unit_name,
        s.qty AS current_stock
    FROM tbl_items i
    LEFT JOIN tbl_categories c ON i.cat_id = c.cat_id
    LEFT JOIN tbl_units u ON i.unit_id = u.unit_id
    LEFT JOIN tbl_item_stock s ON i.item_id = s.item_id
    WHERE i.item_id = ?
";

$stmt = $conn->prepare($itemQuery);
$stmt->bind_param('i', $itemId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    echo json_encode(['error' => 'Item not found.']);
    exit;
}

$response['item_info'] = $item;

// Fetch transactions from tbl_progress
$progressQuery = "
    SELECT
        prog_id,
        item_id,
        date,
        in_qty,
        out_qty,
        end_qty,
        remark,
        created_at
    FROM tbl_progress
    WHERE item_id = ?
    ORDER BY date DESC, created_at DESC
";

$stmt = $conn->prepare($progressQuery);
$stmt->bind_param('i', $itemId);
$stmt->execute();
$result = $stmt->get_result();
$progressData = [];
while ($row = $result->fetch_assoc()) {
    $progressData[] = $row;
}

$transactions = [];
foreach ($progressData as $row) {
    $trans_type = $row['in_qty'] > 0 ? 'in' : 'out';
    $quantity = $row['in_qty'] > 0 ? $row['in_qty'] : $row['out_qty'];

    $transactions[] = [
        'trans_id' => $row['prog_id'],
        'item_id' => $row['item_id'],
        'trans_type' => $trans_type,
        'quantity' => floatval($quantity),
        'balance' => floatval($row['end_qty']),
        'trans_date' => $row['date'] . ' ' . date('H:i:s', strtotime($row['created_at'])),
        'reference' => $row['remark'], // Using remark as reference
        'remarks' => $row['remark']
    ];
}

$response['transactions'] = $transactions;

echo json_encode($response);
