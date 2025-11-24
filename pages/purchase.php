<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// API Handling
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Handle GET requests for fetching initial data
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['fetch'])) {
                if ($_GET['fetch'] === 'suppliers') {
                    $stmt = $conn->prepare("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
                    $stmt->execute();
                    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    echo json_encode(['success' => true, 'data' => $data]);
                } elseif ($_GET['fetch'] === 'items') {
                    $stmt = $conn->prepare("SELECT i.item_id, i.item_name, s.qty as stock FROM tbl_items i LEFT JOIN tbl_item_stock s ON i.item_id = s.item_id ORDER BY i.item_name");
                    $stmt->execute();
                    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    echo json_encode(['success' => true, 'data' => $data]);
                }
            }
        }

        // Handle POST request to create a purchase request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            $supplier_id = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
            $request_date = isset($input['request_date']) ? $input['request_date'] : null;
            $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
            $user_id = $_SESSION['user_id'];

            if ($supplier_id <= 0 || empty($request_date) || empty($items)) {
                throw new Exception("Invalid data provided. Supplier, date, and at least one item are required.");
            }

            // 1. Insert into purchase_request
            $stmt = $conn->prepare("INSERT INTO purchase_request (supplier_id, request_date, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $supplier_id, $request_date, $user_id);
            $stmt->execute();
            $request_id = $conn->insert_id;

            if ($request_id <= 0) {
                throw new Exception("Failed to create purchase request header.");
            }

            // 2. Insert into purchase_request_items
            $stmt_items = $conn->prepare("INSERT INTO purchase_request_items (request_id, item_id, qty_requested) VALUES (?, ?, ?)");
            foreach ($items as $item) {
                $item_id = isset($item['item_id']) ? (int)$item['item_id'] : 0;
                $qty = isset($item['qty_requested']) ? (float)$item['qty_requested'] : 0;
                if ($item_id > 0 && $qty > 0) {
                    $stmt_items->bind_param("iid", $request_id, $item_id, $qty);
                    $stmt_items->execute();
                }
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Purchase request created successfully!', 'request_id' => $request_id]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
    exit;
}

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
    <title>Create Purchase Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/purchase.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
    <div class="dashboard">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>
            <div class="content">
                <div class="container">
                    <!-- Alert -->
                    <div id="alert" class="alert"></div>

                    <!-- Page Header -->
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class="fas fa-plus-circle"></i>
                            Create Purchase Request
                        </h1>
                        <p class="page-subtitle">Fill in the details below to create a new purchase request</p>
                    </div>

                    <!-- Form Grid -->
                    <div class="form-grid">
                        <!-- Left: Form Details -->
                        <div>
                            <!-- Basic Information -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-info-circle"></i>
                                    Basic Information
                                </h3>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-truck"></i>
                                        Supplier <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="supplierSelect" required>
                                        <option value="">-- Select Supplier --</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar"></i>
                                        Request Date <span class="required">*</span>
                                    </label>
                                    <input type="date" class="form-input" id="requestDate" required>
                                </div>
                            </div>

                            <!-- Items Selection -->
                            <div class="form-section" style="margin-top: 20px;">
                                <h3 class="section-title">
                                    <i class="fas fa-boxes"></i>
                                    Select Items
                                </h3>

                                <div class="items-search">
                                    <input type="text" class="form-input" id="itemSearch" placeholder="Search items...">
                                    <span class="search-icon"><i class="fas fa-search"></i></span>
                                </div>

                                <div class="items-list" id="itemsList">
                                    <!-- Items will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Right: Selected Items -->
                        <div class="selected-items">
                            <div class="selected-header">
                                <h3 style="font-size: 18px; color: var(--blue-dark);">
                                    <i class="fas fa-shopping-cart"></i> Selected Items
                                </h3>
                                <span class="selected-count" id="selectedCount">0</span>
                            </div>

                            <div class="selected-items-list" id="selectedItemsList">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No items selected</p>
                                </div>
                            </div>

                            <button class="btn btn-primary" id="submitBtn" disabled>
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                            <button class="btn btn-secondary" id="clearBtn">
                                <i class="fas fa-redo"></i> Clear All
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal-overlay" style="display: none;">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Purchase Order Receipt</h2>
          <button id="closeModal" class="close-modal-btn">×</button>
        </div>
        <div class="modal-body" id="receiptDetails">
          <!-- Receipt content will be injected by JS -->
        </div>
        <div class="modal-footer">
          <button id="printReceiptBtn" class="btn btn-primary">
            <i class="fas fa-print"></i> Print
          </button>
        </div>
      </div>
    </div>

   <script src="../js/sidebar.js"></script>
   <script src="../js/purchase.js?v=<?php echo time(); ?>"></script>
</body>
</html>
