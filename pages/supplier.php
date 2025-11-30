<?php
require_once __DIR__ . '/../config/db.php';
$is_api = isset($_GET['api']) ? (string)$_GET['api'] : '';
if ($is_api !== '') {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== ROLE_ADMIN) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    header('Content-Type: application/json');
    if ($is_api === 'suppliers') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $res = $conn->query('SELECT supplier_id,supplier_name,phone,address,created_at FROM suppliers ORDER BY supplier_id DESC');
            $out = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out[] = [
                        'supplier_id' => (int)$row['supplier_id'],
                        'supplier_name' => (string)$row['supplier_name'],
                        'phone' => $row['phone'] !== null ? (string)$row['phone'] : null,
                        'address' => $row['address'] !== null ? (string)$row['address'] : null,
                        'created_at' => (string)$row['created_at']
                    ];
                }
            }
            echo json_encode(['success' => true, 'data' => $out]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }
        $action = isset($input['action']) ? (string)$input['action'] : '';
        if ($action === 'add') {
            $name = isset($input['supplier_name']) ? trim((string)$input['supplier_name']) : '';
            $phone = isset($input['phone']) ? trim((string)$input['phone']) : null;
            $address = isset($input['address']) ? trim((string)$input['address']) : null;
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('INSERT INTO suppliers(supplier_name,phone,address) VALUES (?,?,?)');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('sss', $name, $phone, $address);
            $ok = $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            echo json_encode(['success' => (bool)$ok, 'supplier_id' => (int)$id]);
            exit;
        }
        if ($action === 'update') {
            $id = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
            $name = isset($input['supplier_name']) ? trim((string)$input['supplier_name']) : '';
            $phone = isset($input['phone']) ? trim((string)$input['phone']) : null;
            $address = isset($input['address']) ? trim((string)$input['address']) : null;
            if (!$id || $name === '') {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('UPDATE suppliers SET supplier_name = ?, phone = ?, address = ? WHERE supplier_id = ?');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('sssi', $name, $phone, $address, $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        if ($action === 'delete') {
            $id = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM tbl_items WHERE supplier_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && (int)$row['c'] > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Supplier in use']);
                exit;
            }
            $stmt = $conn->prepare('DELETE FROM suppliers WHERE supplier_id = ?');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        http_response_code(400);
        echo json_encode(['success' => false]);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false]);
        exit;
    }
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/index.php?page=login');
    exit;
}
if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== ROLE_ADMIN) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Suppliers Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/supplier.css" />
  </head>
  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/sidebar.php'; ?>
      <div class="main-content">
       <?php include __DIR__ . '/header.php'; ?>
        <div class="content">
          <div class="page-header">
            <h1><i class="fas fa-truck"></i> Suppliers Management</h1>
            <p>Manage your suppliers and their information</p>
          </div>

      <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <i class="fas fa-truck"></i>
          </div>
          <div class="stat-content">
            <h3 id="totalSuppliers">0</h3>
            <p>Total Suppliers</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <i class="fas fa-calendar-plus"></i>
          </div>
          <div class="stat-content">
            <h3 id="newThisMonth">0</h3>
            <p>Added This Month</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon orange">
            <i class="fas fa-handshake"></i>
          </div>
          <div class="stat-content">
            <h3 id="activeSuppliers">0</h3>
            <p>Active Suppliers</p>
          </div>
        </div>
      </div>

      <!-- Add Supplier Card -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Add New Supplier</h3>
        </div>
        <form id="addSupplierForm">
          <div class="form-grid">
            <div class="form-group">
              <label>Supplier Name <span class="required">*</span></label>
              <input
                type="text"
                class="form-control"
                id="supplierName"
                placeholder="Enter supplier name"
                required
              />
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input
                type="tel"
                class="form-control"
                id="supplierPhone"
                placeholder="Enter phone number"
              />
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
              <label>Address</label>
              <textarea
                class="form-control"
                id="supplierAddress"
                placeholder="Enter supplier address"
              ></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Supplier
          </button>
        </form>
      </div>

      <!-- Suppliers List -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Suppliers List</h3>
          <button class="btn btn-success" onclick="exportSuppliers()">
            <i class="fas fa-file-excel"></i> Export
          </button>
        </div>
        <div class="toolbar">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input
              type="text"
              placeholder="Search suppliers..."
              id="searchSuppliers"
              onkeyup="searchTable(this.value)"
            />
          </div>
        </div>
        <div class="table-container">
          <table id="suppliersTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Supplier Name</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Created At</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="suppliersTableBody">
              <!-- Suppliers will be populated here -->
            </tbody>
          </table>
        </div>
      </div>
        </div>
      </div>
    </div>
    <script src="../js/sidebar.js"></script>
    <script src="../js/supplier.js"></script>
  </body>
</html>
