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
    case 'getRequests':
        $requests = [];
        $sql = "SELECT
                    pr.request_id,
                    pr.supplier_id,
                    s.supplier_name,
                    pr.request_date,
                    pr.status,
                    pr.created_by,
                    u.fullname AS created_by_name,
                    pr.created_at
                FROM
                    purchase_request pr
                JOIN
                    suppliers s ON pr.supplier_id = s.supplier_id
                JOIN
                    users u ON pr.created_by = u.user_id
                ORDER BY
                    pr.created_at DESC";

        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $request_id = $row['request_id'];
                $row['items'] = [];

                // Fetch items for each request
                $item_sql = "SELECT
                                pri.item_id,
                                ti.item_name,
                                pri.qty_requested
                            FROM
                                purchase_request_items pri
                            JOIN
                                tbl_items ti ON pri.item_id = ti.item_id
                            WHERE
                                pri.request_id = $request_id";
                $item_result = $conn->query($item_sql);
                if ($item_result) {
                    while ($item_row = $item_result->fetch_assoc()) {
                        $row['items'][] = [
                            'item_id' => (int)$item_row['item_id'],
                            'item_name' => (string)$item_row['item_name'],
                            'qty_requested' => (float)$item_row['qty_requested']
                        ];
                    }
                }
                $requests[] = $row;
            }
        }
        echo json_encode(['success' => true, 'purchaseRequests' => $requests]);
        break;

    case 'approveRequest':
        $data = json_decode(file_get_contents('php://input'), true);
        $request_id = $data['request_id'] ?? 0;

        if ($request_id > 0) {
            $stmt = $conn->prepare("UPDATE purchase_request SET status = 'approved' WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Request approved.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to approve request.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        }
        break;

    case 'markReceived':
        $data = json_decode(file_get_contents('php://input'), true);
        $request_id = $data['request_id'] ?? 0;

        if ($request_id > 0) {
            // Start a transaction
            $conn->begin_transaction();
            try {
                // Update request status
                $stmt = $conn->prepare("UPDATE purchase_request SET status = 'received' WHERE request_id = ?");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $stmt->close();

                // Get items from the request
                $item_sql = "SELECT pri.item_id, pri.qty_requested FROM purchase_request_items pri WHERE pri.request_id = ?";
                $item_stmt = $conn->prepare($item_sql);
                $item_stmt->bind_param("i", $request_id);
                $item_stmt->execute();
                $item_result = $item_stmt->get_result();

                while ($item_row = $item_result->fetch_assoc()) {
                    $item_id = $item_row['item_id'];
                    $qty_received = $item_row['qty_requested'];

                    // Update tbl_item_stock
                    // Check if item exists in stock, if not, insert, else update
                    $stock_check_sql = "SELECT stock_id FROM tbl_item_stock WHERE item_id = ?";
                    $stock_check_stmt = $conn->prepare($stock_check_sql);
                    $stock_check_stmt->bind_param("i", $item_id);
                    $stock_check_stmt->execute();
                    $stock_check_result = $stock_check_stmt->get_result();

                    if ($stock_check_result->num_rows > 0) {
                        // Update existing stock
                        $update_stock_sql = "UPDATE tbl_item_stock SET qty = qty + ? WHERE item_id = ?";
                        $update_stock_stmt = $conn->prepare($update_stock_sql);
                        $update_stock_stmt->bind_param("di", $qty_received, $item_id);
                        $update_stock_stmt->execute();
                        $update_stock_stmt->close();
                    } else {
                        // Insert new stock entry
                        $insert_stock_sql = "INSERT INTO tbl_item_stock (item_id, qty) VALUES (?, ?)";
                        $insert_stock_stmt = $conn->prepare($insert_stock_sql);
                        $insert_stock_stmt->bind_param("id", $item_id, $qty_received);
                        $insert_stock_stmt->execute();
                        $insert_stock_stmt->close();
                    }
                    $stock_check_stmt->close();

                    // Insert into tbl_progress (as 'in_qty')
                    $created_by = $_SESSION['user_id'];
                    $date = date('Y-m-d'); // Current date
                    // Get current stock quantity to set 'last_qty' and 'end_qty'
                    $current_stock_sql = "SELECT qty FROM tbl_item_stock WHERE item_id = ?";
                    $current_stock_stmt = $conn->prepare($current_stock_sql);
                    $current_stock_stmt->bind_param("i", $item_id);
                    $current_stock_stmt->execute();
                    $current_stock_result = $current_stock_stmt->get_result();
                    $current_stock_row = $current_stock_result->fetch_assoc();
                    $end_qty = $current_stock_row['qty'] ?? 0;
                    $last_qty = $end_qty - $qty_received; // Last qty before this receipt

                    $insert_progress_sql = "INSERT INTO tbl_progress (item_id, date, in_qty, out_qty, last_qty, end_qty, created_by, remark) VALUES (?, ?, ?, 0, ?, ?, ?, ?)";
                    $remark = "Received from purchase request #".$request_id;
                    $progress_stmt = $conn->prepare($insert_progress_sql);
                    $progress_stmt->bind_param("isdddis", $item_id, $date, $qty_received, $last_qty, $end_qty, $created_by, $remark);
                    $progress_stmt->execute();
                    $progress_stmt->close();
                    $current_stock_stmt->close();
                }
                $item_stmt->close();
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Request marked as received and stock updated.']);
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error marking request as received: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to mark request as received. Error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>