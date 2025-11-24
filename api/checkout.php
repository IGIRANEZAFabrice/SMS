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
        $selling_price = (float)$item['price']; // Get the selling price from the frontend

        if ($qty <= 0) {
            throw new Exception('Invalid quantity for item ' . $item_id);
        }

        // Lock the row, get stock, item name, original price, AND min_price
        $stmt = $conn->prepare(
            'SELECT s.qty as stock, i.item_name as name, i.price as original_price, i.min_price
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
        $item_min_price = (float)$item_data['min_price'];

        // Server-side validation for min_price
        if ($selling_price < $item_min_price) {
            throw new Exception('Selling price for ' . $item_data['name'] . ' ($' . number_format($selling_price, 2) . ') cannot be less than its minimum price ($' . number_format($item_min_price, 2) . ').');
        }

        if ($current_stock < $qty) {
            throw new Exception('Not enough stock for ' . $item_data['name'] . '. Available: ' . $current_stock . ', Requested: ' . $qty);
        }

        $new_stock = $current_stock - $qty;

        // Update stock
        $stmt = $conn->prepare('UPDATE tbl_item_stock SET qty = ? WHERE item_id = ?');
        $stmt->bind_param('di', $new_stock, $item_id);
        $stmt->execute();
        $stmt->close();

        // Get the last new_price for the item (for tbl_progress)
        $price_stmt = $conn->prepare('SELECT new_price FROM tbl_progress WHERE item_id = ? AND new_price IS NOT NULL ORDER BY prog_id DESC LIMIT 1');
        $price_stmt->bind_param('i', $item_id);
        $price_stmt->execute();
        $price_res = $price_stmt->get_result();
        $last_price_data = $price_res->fetch_assoc();
        $price_stmt->close();
        
        $new_price = $last_price_data ? (float)$last_price_data['new_price'] : null;

        // Record progress
        $stmt = $conn->prepare('INSERT INTO tbl_progress (item_id, date, out_qty, last_qty, end_qty, new_price, created_by) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)');
        $stmt->bind_param('iddidi', $item_id, $qty, $current_stock, $new_stock, $new_price, $user_id);
        $stmt->execute();
        $stmt->close();
        
        $item_total = $selling_price * $qty; // Calculate total using the custom selling price
        $subtotal += $item_total;

        $receipt_items[] = [
            'item_id' => $item_id,
            'name' => $item_data['name'],
            'qty' => $qty,
            'price' => $selling_price, // Use the custom selling price
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