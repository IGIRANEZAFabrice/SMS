<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Authorization Check (assuming only admins can manage min prices)
// Ensure ROLE_ADMIN is defined in your config or elsewhere
if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== ROLE_ADMIN) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$is_api = isset($_GET['api']) ? (string)$_GET['api'] : '';

if ($is_api === 'items') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = "SELECT item_id, item_name, price, min_price FROM tbl_items ORDER BY item_id ASC";
        $res = $conn->query($query);
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'item_id' => (int)$row['item_id'],
                    'item_name' => (string)$row['item_name'],
                    'price' => (float)$row['price'],
                    'min_price' => (float)$row['min_price']
                ];
            }
            echo json_encode(['success' => true, 'data' => $out]);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
    }
} elseif ($is_api === 'updateMinPrice') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $item_id = isset($input['item_id']) ? (int)$input['item_id'] : 0;
        $min_price = isset($input['min_price']) ? (float)$input['min_price'] : 0.0;

        if (!$item_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Item ID is required.']);
            exit;
        }

        // Validate min_price - must be non-negative
        if ($min_price < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Minimum price cannot be negative.']);
            exit;
        }

        // Optionally, check if min_price is less than or equal to current price
        // You might want to add a business rule here, e.g., min_price cannot exceed price
        // $stmt = $conn->prepare("SELECT price FROM tbl_items WHERE item_id = ?");
        // $stmt->bind_param("i", $item_id);
        // $stmt->execute();
        // $result = $stmt->get_result()->fetch_assoc();
        // if ($result && $min_price > $result['price']) {
        //     http_response_code(400);
        //     echo json_encode(['success' => false, 'message' => 'Minimum price cannot be greater than current price.']);
        //     exit;
        // }
        // $stmt->close();

        $stmt = $conn->prepare("UPDATE tbl_items SET min_price = ? WHERE item_id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("di", $min_price, $item_id); // d for double, i for integer
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Minimum price updated.']);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update minimum price: ' . $conn->error]);
            exit;
        }
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid API endpoint.']);
    exit;
}
