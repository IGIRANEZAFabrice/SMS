<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['items']) || !is_array($input['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$items = $input['items'];
$discount = isset($input['discount']) ? (float)$input['discount'] : 0;
$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'N/A';

$conn->begin_transaction();

try {
    $receipt_code = 'RCPT-' . time() . '-' . uniqid();
    $receipt_items = [];
    $subtotal = 0;

    foreach ($items as $item) {
        $item_id = (int)$item['item_id'];
        $qty = (float)$item['qty'];

        if ($qty <= 0) {
            throw new Exception('Invalid quantity for item ' . $item_id);
        }

        // Lock the row, get stock, and get item details
        $stmt = $conn->prepare(
            'SELECT s.qty as stock, i.item_name as name, i.price 
             FROM tbl_item_stock s
             JOIN tbl_items i ON s.item_id = i.item_id
             WHERE s.item_id = ? FOR UPDATE'
        );
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $item_data = $res->fetch_assoc();
        $stmt->close();

        if (!$item_data) {
            throw new Exception('Item with ID ' . $item_id . ' not found');
        }

        $current_stock = (float)$item_data['stock'];

        if ($current_stock < $qty) {
            throw new Exception('Not enough stock for ' . $item_data['name'] . '. Available: ' . $current_stock . ', Requested: ' . $qty);
        }

        $new_stock = $current_stock - $qty;

        // Update stock
        $stmt = $conn->prepare('UPDATE tbl_item_stock SET qty = ? WHERE item_id = ?');
        $stmt->bind_param('di', $new_stock, $item_id);
        $stmt->execute();
        $stmt->close();

        // Record progress
        $stmt = $conn->prepare('INSERT INTO tbl_progress (item_id, date, out_qty, last_qty, end_qty, created_by) VALUES (?, CURDATE(), ?, ?, ?, ?)');
        $stmt->bind_param('iddii', $item_id, $qty, $current_stock, $new_stock, $user_id);
        $stmt->execute();
        $stmt->close();
        
        $item_total = $item_data['price'] * $qty;
        $subtotal += $item_total;

        $receipt_items[] = [
            'item_id' => $item_id,
            'name' => $item_data['name'],
            'qty' => $qty,
            'price' => (float)$item_data['price'],
            'total' => $item_total,
        ];
    }

    $grand_total = max(0, $subtotal - $discount);

    // Save receipt to the database
    $stmt = $conn->prepare('INSERT INTO receipts (receipt_code, total_amount, discount, grand_total, created_by) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sdddi', $receipt_code, $subtotal, $discount, $grand_total, $user_id);
    $stmt->execute();
    $stmt->close();

    // Save receipt items
    $stmt = $conn->prepare('INSERT INTO receipt_items (receipt_code, item_id, qty, price, total) VALUES (?, ?, ?, ?, ?)');
    foreach ($receipt_items as $receipt_item) {
        $stmt->bind_param(
            'sidds',
            $receipt_code,
            $receipt_item['item_id'],
            $receipt_item['qty'],
            $receipt_item['price'],
            $receipt_item['total']
        );
        $stmt->execute();
    }
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'receipt' => [
            'code' => $receipt_code,
            'date' => date('Y-m-d H:i:s'),
            'user' => $username,
            'items' => $receipt_items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'grand_total' => $grand_total,
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>