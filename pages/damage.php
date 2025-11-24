<?php
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
        $stmt = $conn->prepare("
            SELECT d.id, i.name AS item_name, c.name AS category_name, i.unit, d.qty, d.message, d.created_at
            FROM damaged d
            JOIN items i ON d.item_id = i.id
            JOIN categories c ON i.category_id = c.id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'add') {
        $item_id = isset($input['item_id']) ? (int)$input['item_id'] : 0;
        $qty = isset($input['qty']) ? (float)$input['qty'] : 0;
        $message = isset($input['message']) ? trim($input['message']) : '';
        $created_at = isset($input['created_at']) ? $input['created_at'] : date('Y-m-d');

        if ($item_id <= 0 || $qty <= 0 || empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO damaged (item_id, qty, message, created_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $item_id, $qty, $message, $created_at);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
            $stmt->bind_param("di", $qty, $item_id);
            $stmt->execute();

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

        // First, get the qty and item_id to revert the stock
        $stmt = $conn->prepare("SELECT item_id, qty FROM damaged WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $damaged_item = $result->fetch_assoc();

        if ($damaged_item) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
                $stmt->bind_param("di", $damaged_item['qty'], $damaged_item['item_id']);
                $stmt->execute();

                $stmt = $conn->prepare("DELETE FROM damaged WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $conn->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Record not found.']);
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

                        <form id="damagedForm">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Select Item <span class="required">*</span></label>
                                    <select class="form-control" id="itemSelect" required>
                                        <option value="">Choose an item...</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Quantity Damaged <span class="required">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="qtyDamaged" placeholder="Enter quantity" required min="0.01" />
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label>Reason / Message <span class="required">*</span></label>
                                    <textarea class="form-control" id="damageMessage" placeholder="Describe the reason for damage (e.g., broken during handling, expired, defective)" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Date <span class="required">*</span></label>
                                    <input type="date" class="form-control" id="damageDate" value="<?php echo date('Y-m-d'); ?>" required />
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
    <script src="../js/sidebar.js"></script>
    <script src="../js/damage.js"></script>
</body>
</html>
it is not selecting items from the items table ALTER TABLE tbl_items 
    DROP COLUMN item_unit,
    ADD COLUMN unit_id INT AFTER item_name,
    ADD CONSTRAINT fk_item_unit FOREIGN KEY (unit_id) REFERENCES tbl_units(unit_id);



 ALTER TABLE tbl_items
ADD COLUMN min_price DOUBLE DEFAULT 0 AFTER price;
 CREATE TABLE `damaged` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `qty` float NOT NULL,
  `message` varchar(200) NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
and it must reduce the stock size :CREATE TABLE tbl_item_stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    qty DOUBLE NOT NULL DEFAULT 0,
    last_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id) REFERENCES tbl_items(item_id)
);

and record it in the pprogress:CREATE TABLE tbl_progress (
    prog_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,

    date DATE NOT NULL,

    in_qty DOUBLE DEFAULT 0,     -- Added stock
    out_qty DOUBLE DEFAULT 0,    -- Sold / removed

    last_qty DOUBLE DEFAULT 0,   -- Before movement
    end_qty DOUBLE NOT NULL,     -- After movement

    new_price DOUBLE DEFAULT NULL,
    remark TEXT,

    created_by INT NOT NULL,     -- Who did it
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id) REFERENCES tbl_items(item_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);