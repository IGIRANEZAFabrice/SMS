<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// --- API HANDLING ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch'])) {
            // Fetch approved purchase requests
            if ($_GET['fetch'] === 'requests') {
                $stmt = $conn->prepare("
                    SELECT pr.request_id, s.supplier_name, u.fullname as requested_by, pr.request_date, pr.status
                    FROM purchase_request pr
                    JOIN suppliers s ON pr.supplier_id = s.supplier_id
                    JOIN users u ON pr.created_by = u.user_id
                    WHERE pr.status = 'approved'
                    ORDER BY pr.request_date DESC
                ");
                $stmt->execute();
                $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $result]);
            }
            // Fetch items for a specific request
            elseif ($_GET['fetch'] === 'items' && isset($_GET['request_id'])) {
                $request_id = (int)$_GET['request_id'];
                $stmt = $conn->prepare("
                    SELECT pri.item_id, i.item_name, pri.qty_requested
                    FROM purchase_request_items pri
                    JOIN tbl_items i ON pri.item_id = i.item_id
                    WHERE pri.request_id = ?
                ");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $result]);
            }
        }
        // Receive items for a purchase request
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
            $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

            if ($request_id <= 0 || empty($items)) {
                throw new Exception("Invalid data: Request ID and items are required.");
            }

            $conn->begin_transaction();

            foreach ($items as $item) {
                $item_id = (int)$item['item_id'];
                $new_qty = (float)$item['qty_received'];
                $new_price = (float)$item['price'];

                if ($new_qty <= 0 || $new_price <= 0) continue; // Skip items not received

                // 1. Get old quantity and old price
                $stmt_old = $conn->prepare("
                    SELECT s.qty as old_qty, i.price as old_price
                    FROM tbl_items i
                    LEFT JOIN tbl_item_stock s ON i.item_id = s.item_id
                    WHERE i.item_id = ? FOR UPDATE
                ");
                $stmt_old->bind_param("i", $item_id);
                $stmt_old->execute();
                $res_old = $stmt_old->get_result()->fetch_assoc();
                $old_qty = (float)($res_old['old_qty'] ?? 0);
                $old_price = (float)($res_old['old_price'] ?? 0);

                // 2. Calculate Weighted Average Cost
                $total_qty = $old_qty + $new_qty;
                $avg_cost = ($total_qty > 0) 
                    ? (($old_qty * $old_price) + ($new_qty * $new_price)) / $total_qty
                    : $new_price;

                // 3. Update item's main price (last received price) and average cost in tbl_items
                $stmt_update_item = $conn->prepare("UPDATE tbl_items SET price = ? WHERE item_id = ?");
                $stmt_update_item->bind_param("di", $new_price, $item_id);
                $stmt_update_item->execute();

                // 4. Update stock quantity
                $stmt_update_stock = $conn->prepare("
                    INSERT INTO tbl_item_stock (item_id, qty) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE qty = qty + ?
                ");
                $stmt_update_stock->bind_param("idi", $item_id, $new_qty, $new_qty);
                $stmt_update_stock->execute();
                
                // 5. Record in tbl_progress
                $end_qty = $old_qty + $new_qty;
                $remark = "Received from PO #{$request_id}";
                $stmt_progress = $conn->prepare("
                    INSERT INTO tbl_progress (item_id, date, in_qty, last_qty, end_qty, new_price, remark, created_by)
                    VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)
                ");
                $stmt_progress->bind_param("iddddsi", $item_id, $new_qty, $old_qty, $end_qty, $avg_cost, $remark, $user_id);
                $stmt_progress->execute();
            }

            // 6. Update purchase request status to 'received'
            $stmt_pr_status = $conn->prepare("UPDATE purchase_request SET status = 'received' WHERE request_id = ?");
            $stmt_pr_status->bind_param("i", $request_id);
            $stmt_pr_status->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Request #{$request_id} received successfully."]);
        }

    } catch (Exception $e) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
    exit;
}
// --- END API HANDLING ---


if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Deliveries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/delivery.css">
</head>
<body>
    <div class="dashboard">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>
            <div class="content">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-truck-loading"></i> Receive Deliveries</h1>
                    <p class="page-subtitle">Review approved purchase requests and receive items into stock.</p>
                </div>

                <div class="table-container">
                    <div class="toolbar">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search by request ID or supplier..." id="searchInput" />
                        </div>
                    </div>
                    <table id="requestsTable">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Supplier</th>
                                <th>Requested By</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestsTableBody">
                            <!-- Data will be loaded here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for viewing and receiving items -->
    <div id="receiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Receive Items for Request #<span id="modalRequestId"></span></h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modalItemsContainer">
                    <!-- Items will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-btn">Cancel</button>
                <button id="receiveSubmitBtn" class="btn btn-primary"><i class="fas fa-check-circle"></i> Receive Selected Items</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/delivery.js?v=<?php echo time(); ?>"></script>
</body>
</html>
