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

    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $stmt = $conn->prepare("
                SELECT 
                    d.id, 
                    i.item_name, 
                    c.cat_name as category_name, 
                    u.unit_name as unit, 
                    d.qty, 
                    d.message, 
                    DATE_FORMAT(d.created_at, '%Y-%m-%d %H:%i:%s') as created_at
                FROM damaged d
                JOIN tbl_items i ON d.item_id = i.item_id
                JOIN tbl_categories c ON i.cat_id = c.cat_id
                JOIN tbl_units u on i.unit_id = u.unit_id
                ORDER BY d.created_at DESC
            ");

            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'add') {
        $item_id = isset($input['item_id']) ? (int)$input['item_id'] : 0;
        $qty = isset($input['qty']) ? (float)$input['qty'] : 0;
        $message = isset($input['message']) ? trim($input['message']) : '';
        $created_at = isset($input['created_at']) ? $input['created_at'] : date('Y-m-d');
        $user_id = $_SESSION['user_id'];

        if ($item_id <= 0 || $qty <= 0 || empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            // Get current stock
            $stmt_stock = $conn->prepare("SELECT qty FROM tbl_item_stock WHERE item_id = ?");
            $stmt_stock->bind_param("i", $item_id);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();
            $last_qty = 0;
            if ($result_stock->num_rows > 0) {
                $last_qty = (float)$result_stock->fetch_assoc()['qty'];
            }

            // Insert into damaged table
            $stmt = $conn->prepare("INSERT INTO damaged (item_id, qty, message, created_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $item_id, $qty, $message, $created_at);
            $stmt->execute();

            // Update stock
            $stmt = $conn->prepare("UPDATE tbl_item_stock SET qty = qty - ? WHERE item_id = ?");
            $stmt->bind_param("di", $qty, $item_id);
            $stmt->execute();
            
            // Record progress
            $end_qty = $last_qty - $qty;
            $stmt_progress = $conn->prepare("
                INSERT INTO tbl_progress (item_id, date, out_qty, last_qty, end_qty, remark, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_progress->bind_param("isdddsi", $item_id, $created_at, $qty, $last_qty, $end_qty, $message, $user_id);
            $stmt_progress->execute();

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            // First, get the qty and item_id to revert the stock
            $stmt = $conn->prepare("SELECT item_id, qty FROM damaged WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $damaged_item = $result->fetch_assoc();

            if ($damaged_item) {
                $stmt_update = $conn->prepare("UPDATE tbl_item_stock SET qty = qty + ? WHERE item_id = ?");
                $stmt_update->bind_param("di", $damaged_item['qty'], $damaged_item['item_id']);
                $stmt_update->execute();

                $stmt_delete = $conn->prepare("DELETE FROM damaged WHERE id = ?");
                $stmt_delete->bind_param("i", $id);
                $stmt_delete->execute();

                $conn->commit();
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Record not found.');
            }
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/index.php?page=login');
    exit;
}

// Fetch items for the dropdown
$items = [];
$stmt = $conn->prepare("
    SELECT i.item_id as id, i.item_name as name, u.unit_name as unit 
    FROM tbl_items i 
    JOIN tbl_units u ON i.unit_id = u.unit_id 
    ORDER BY i.item_name
");
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Damaged Goods Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/damage.css" />
</head>
<body>
    <div class="dashboard">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>
            <div class="content">
                <div class="page-header">
                    <h1><i class="fas fa-exclamation-triangle"></i> Damaged Goods Management</h1>
                    <p>Record and track damaged inventory items</p>
                </div>

                <div class="container">
                    <!-- Record Damaged Item -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Record New Damaged Item</h3>
                        </div>

                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>
                                <strong>Note:</strong> Recording damaged goods will automatically reduce the stock quantity.
                                Make sure to verify the item and quantity before submitting.
                            </p>
                        </div>

                        <form id="damagedForm" method="POST" action="damage.php">
                            <input type="hidden" name="action" value="add">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Select Item <span class="required">*</span></label>
                                    <select class="form-control" id="itemSelect" name="item_id" required>
                                <option value="">Choose an item...</option>
                                <?php foreach ($items as $item): ?>
                                    <option value="<?= htmlspecialchars($item['id']) ?>">
                                        <?= htmlspecialchars($item['name'] . ' (' . $item['unit'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                                </div>

                                <div class="form-group">
                                    <label>Quantity Damaged <span class="required">*</span></label>
                                    <input type="number" step="0.01" class="form-control" name="qty" id="qtyDamaged" placeholder="Enter quantity" required min="0.01" />
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label>Reason / Message <span class="required">*</span></label>
                                    <textarea class="form-control" id="damageMessage" name="message" placeholder="Describe the reason for damage (e.g., broken during handling, expired, defective)" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Date <span class="required">*</span></label>
                                    <input type="date" class="form-control" name="created_at" id="damageDate" value="<?= date('Y-m-d') ?>" required />
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Record Damaged Item
                            </button>
                        </form>
                    </div>

                    <!-- Damaged Goods List -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Damaged Goods History</h3>
                        </div>

                        <div class="toolbar">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search damaged items..." id="searchDamaged" />
                            </div>
                        </div>

                        <div class="table-container">
                            <table id="damagedTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th class="text-right">Quantity</th>
                                        <th>Reason</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="damagedTableBody">
                                    <!-- Damaged items will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/damage.js"></script>
</body>
</html>
