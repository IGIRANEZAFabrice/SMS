<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_errors.log');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// --- API HANDLING ---
if (isset($_GET['api'])) {
    // Set JSON content type
    header('Content-Type: application/json');
    
    // Debug log
    error_log("API Request: " . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);
    
    // Check session
    if (!isset($_SESSION['user_id'])) {
        error_log("API Error: Unauthorized access - No user_id in session");
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Please login again']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch'])) {
            // Fetch approved purchase requests
            if ($_GET['fetch'] === 'requests') {
                $stmt = $conn->prepare("
                    SELECT 
                        pr.request_id, 
                        s.supplier_name, 
                        u.fullname as requested_by, 
                        pr.request_date, 
                        pr.status,
                        COUNT(pri.item_id) as item_count,
                        SUM(pri.qty_requested) as total_qty
                    FROM purchase_request pr
                    JOIN suppliers s ON pr.supplier_id = s.supplier_id
                    JOIN users u ON pr.created_by = u.user_id
                    LEFT JOIN purchase_request_items pri ON pr.request_id = pri.request_id
                    GROUP BY pr.request_id
                    ORDER BY 
                        FIELD(pr.status, 'pending', 'approved', 'received') ASC,
                        pr.request_date DESC
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

            // Prepare statements outside the loop for efficiency
            $stmt_old = $conn->prepare("
                SELECT s.qty as old_qty, i.price as old_price
                FROM tbl_items i
                LEFT JOIN tbl_item_stock s ON i.item_id = s.item_id
                WHERE i.item_id = ? FOR UPDATE
            ");
            if (!$stmt_old) throw new Exception("Prepare failed (stmt_old): " . $conn->error);

            $stmt_update_item = $conn->prepare("UPDATE tbl_items SET price = ? WHERE item_id = ?");
            if (!$stmt_update_item) throw new Exception("Prepare failed (stmt_update_item): " . $conn->error);

            $stmt_update_stock = $conn->prepare("UPDATE tbl_item_stock SET qty = ? WHERE item_id = ?");
            if (!$stmt_update_stock) throw new Exception("Prepare failed (stmt_update_stock): " . $conn->error);

            $stmt_insert_stock = $conn->prepare("INSERT INTO tbl_item_stock (item_id, qty) VALUES (?, ?)");
            if (!$stmt_insert_stock) throw new Exception("Prepare failed (stmt_insert_stock): " . $conn->error);

            $stmt_progress = $conn->prepare("
                INSERT INTO tbl_progress (item_id, date, in_qty, last_qty, end_qty, new_price, remark, created_by)
                VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt_progress) throw new Exception("Prepare failed (stmt_progress): " . $conn->error);


            foreach ($items as $item) {
                $item_id = (int)$item['item_id'];
                $new_qty = (float)$item['qty_received'];
                $new_price = (float)$item['price'];

                if ($new_qty <= 0 || $new_price <= 0) continue; // Skip items not received

                // 1. Get old quantity and old price
                $stmt_old->bind_param("i", $item_id);
                $stmt_old->execute();
                $res_old = $stmt_old->get_result()->fetch_assoc();
                $old_qty = (float)($res_old['old_qty'] ?? 0);
                $old_price = (float)($res_old['old_price'] ?? 0);
                $stock_exists = isset($res_old['old_qty']);
                $end_qty = $old_qty + $new_qty;

                // 2. Calculate Weighted Average Cost
                $total_qty = $old_qty + $new_qty;
                $avg_cost = ($total_qty > 0) 
                    ? (($old_qty * $old_price) + ($new_qty * $new_price)) / $total_qty
                    : $new_price;

                // 3. Update item's main price to be the new average cost
                $stmt_update_item->bind_param("di", $avg_cost, $item_id);
                $stmt_update_item->execute();

                // 4. Update or Insert stock quantity
                if ($stock_exists) {
                    $stmt_update_stock->bind_param("di", $end_qty, $item_id);
                    $stmt_update_stock->execute();
                } else {
                    $stmt_insert_stock->bind_param("id", $item_id, $end_qty);
                    $stmt_insert_stock->execute();
                }
                
                // 5. Record in tbl_progress
                $remark = "Received from PO #{$request_id}";
                $stmt_progress->bind_param("iddddsi", $item_id, $new_qty, $old_qty, $end_qty, $new_price, $remark, $user_id);
                $stmt_progress->execute();
            }

            // 6. Update purchase request status to 'received'
            $stmt_pr_status = $conn->prepare("UPDATE purchase_request SET status = 'received' WHERE request_id = ?");
            if (!$stmt_pr_status) throw new Exception("Prepare failed (stmt_pr_status): " . $conn->error);
            $stmt_pr_status->bind_param("i", $request_id);
            $stmt_pr_status->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Request #{$request_id} received successfully."]);
        }

    } catch (Exception $e) {
        error_log("Error in API: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        if ($conn && $conn->in_transaction) {
            $conn->rollback();
        }
        
        http_response_code(500);
        error_log("Sending 500 error response");
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

                <div class="toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search by request ID, supplier, or status..." id="searchInput" />
                    </div>
                </div>

                <div class="delivery-grid" id="deliveryGrid">
                    <!-- Delivery cards will be loaded here by JavaScript -->
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
                <button class="btn btn-outline-secondary close-btn">Cancel</button>
                <button id="receiveSubmitBtn" class="btn btn-primary"><i class="fas fa-check-circle"></i> Receive Items</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/delivery.js?v=<?php echo time(); ?>"></script>
</body>
</html>
