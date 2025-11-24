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
    if ($is_api === 'categories') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $res = $conn->query('SELECT cat_id, cat_name, description, created_at FROM tbl_categories ORDER BY cat_id DESC');
            $out = [];
            if ($res) { while ($row = $res->fetch_assoc()) { $out[] = [ 'cat_id' => (int)$row['cat_id'], 'cat_name' => (string)$row['cat_name'], 'description' => $row['description'] !== null ? (string)$row['description'] : null, 'created_at' => (string)$row['created_at'] ]; } }
            echo json_encode(['success' => true, 'data' => $out]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = $_POST; }
        $action = isset($input['action']) ? (string)$input['action'] : '';
        if ($action === 'add') {
            $name = isset($input['cat_name']) ? trim((string)$input['cat_name']) : '';
            $desc = isset($input['description']) ? trim((string)$input['description']) : null;
            if ($name === '') { http_response_code(400); echo json_encode(['success' => false]); exit; }
            $stmt = $conn->prepare('INSERT INTO tbl_categories(cat_name, description) VALUES (?, ?)');
            if (!$stmt) { http_response_code(500); echo json_encode(['success' => false]); exit; }
            $stmt->bind_param('ss', $name, $desc);
            $ok = $stmt->execute(); $id = $stmt->insert_id; $stmt->close();
            echo json_encode(['success' => (bool)$ok, 'cat_id' => (int)$id]); exit;
        }
        if ($action === 'update') {
            $id = isset($input['cat_id']) ? (int)$input['cat_id'] : 0;
            $name = isset($input['cat_name']) ? trim((string)$input['cat_name']) : '';
            $desc = isset($input['description']) ? trim((string)$input['description']) : null;
            if (!$id || $name === '') { http_response_code(400); echo json_encode(['success' => false]); exit; }
            $stmt = $conn->prepare('UPDATE tbl_categories SET cat_name = ?, description = ? WHERE cat_id = ?');
            if (!$stmt) { http_response_code(500); echo json_encode(['success' => false]); exit; }
            $stmt->bind_param('ssi', $name, $desc, $id);
            $ok = $stmt->execute(); $stmt->close(); echo json_encode(['success' => (bool)$ok]); exit;
        }
        if ($action === 'delete') {
            $id = isset($input['cat_id']) ? (int)$input['cat_id'] : 0; if (!$id) { http_response_code(400); echo json_encode(['success' => false]); exit; }
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM tbl_items WHERE cat_id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $res = $stmt->get_result(); $row = $res ? $res->fetch_assoc() : null; $stmt->close();
            if ($row && (int)$row['c'] > 0) { http_response_code(409); echo json_encode(['success' => false, 'message' => 'Category in use']); exit; }
            $stmt = $conn->prepare('DELETE FROM tbl_categories WHERE cat_id = ?'); if (!$stmt) { http_response_code(500); echo json_encode(['success' => false]); exit; }
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close(); echo json_encode(['success' => (bool)$ok]); exit;
        }
        http_response_code(400); echo json_encode(['success' => false]); exit;
    } elseif ($is_api === 'items') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $sql = 'SELECT i.item_id,i.item_name,i.cat_id,i.supplier_id,i.unit_id,u.unit_name,i.price,i.min_price,i.item_status,c.cat_name,s.supplier_name 
                    FROM tbl_items i 
                    LEFT JOIN tbl_categories c ON c.cat_id=i.cat_id 
                    LEFT JOIN suppliers s ON s.supplier_id=i.supplier_id 
                    LEFT JOIN tbl_units u ON u.unit_id = i.unit_id 
                    ORDER BY i.item_id DESC';
            $res = $conn->query($sql); $out = [];
            if ($res) { while ($row = $res->fetch_assoc()) { $out[] = [ 'item_id' => (int)$row['item_id'], 'item_name' => (string)$row['item_name'], 'cat_id' => (int)$row['cat_id'], 'cat_name' => (string)($row['cat_name'] ?? ''), 'supplier_id' => (int)$row['supplier_id'], 'supplier_name' => (string)($row['supplier_name'] ?? ''), 'unit_id' => (int)$row['unit_id'], 'unit_name' => (string)($row['unit_name'] ?? ''), 'price' => (float)$row['price'], 'min_price' => (float)$row['min_price'], 'item_status' => (int)$row['item_status'] ]; } }
            echo json_encode(['success' => true, 'data' => $out]); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true); if (!is_array($input)) { $input = $_POST; }
        $action = isset($input['action']) ? (string)$input['action'] : '';
        if ($action === 'add') {
            $name = isset($input['item_name']) ? trim((string)$input['item_name']) : '';
            $cat_id = isset($input['cat_id']) ? (int)$input['cat_id'] : 0;
            $supplier_id = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
            $unit_id = isset($input['unit_id']) ? (int)$input['unit_id'] : 0;
            $price = isset($input['price']) ? (float)$input['price'] : 0;
            $min_price = isset($input['min_price']) ? (float)$input['min_price'] : 0;
            $status = isset($input['item_status']) ? (int)$input['item_status'] : 1;
            if ($name === '' || !$cat_id || !$supplier_id || !$unit_id || $price <= 0 || $min_price <= 0) { 
                http_response_code(400); 
                echo json_encode(['success' => false, 'message' => 'All fields are required']); 
                exit; 
            }
            // Check if unit exists and is active
            $stmt = $conn->prepare('SELECT status FROM tbl_units WHERE unit_id = ?');
            $stmt->bind_param('i', $unit_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $unit = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            
            if (!$unit || $unit['status'] != 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Selected unit is not available']);
                exit;
            }
            
            $stmt = $conn->prepare('INSERT INTO tbl_items(item_name, unit_id, item_status, price, min_price, cat_id, supplier_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            if (!$stmt) { 
                http_response_code(500); 
                echo json_encode(['success' => false, 'message' => 'Database error']); 
                exit; 
            }
            $created_by = (int)$_SESSION['user_id'];
            $stmt->bind_param('siiddiii', $name, $unit_id, $status, $price, $min_price, $cat_id, $supplier_id, $created_by);
            $ok = $stmt->execute();
            $id = $stmt->insert_id; $stmt->close();
            echo json_encode(['success' => (bool)$ok, 'item_id' => (int)$id]); exit;
        }
        if ($action === 'update') {
            $id = isset($input['item_id']) ? (int)$input['item_id'] : 0;
            $name = isset($input['item_name']) ? trim((string)$input['item_name']) : '';
            $cat_id = isset($input['cat_id']) ? (int)$input['cat_id'] : 0;
            $supplier_id = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
            $unit_id = isset($input['unit_id']) ? (int)$input['unit_id'] : 0;
            $price = isset($input['price']) ? (float)$input['price'] : 0;
            $status = isset($input['item_status']) ? (int)$input['item_status'] : 1;
            if (!$id || $name === '' || !$cat_id || !$supplier_id || !$unit_id || $price <= 0) { 
                http_response_code(400); 
                echo json_encode(['success' => false, 'message' => 'All fields are required']); 
                exit; 
            }
            // Check if unit exists and is active
            $stmt = $conn->prepare('SELECT status FROM tbl_units WHERE unit_id = ?');
            $stmt->bind_param('i', $unit_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $unit = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            
            if (!$unit || $unit['status'] != 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Selected unit is not available']);
                exit;
            }
            
            $stmt = $conn->prepare('UPDATE tbl_items SET item_name = ?, unit_id = ?, item_status = ?, price = ?, cat_id = ?, supplier_id = ? WHERE item_id = ?');
            if (!$stmt) { 
                http_response_code(500); 
                echo json_encode(['success' => false, 'message' => 'Database error']); 
                exit; 
            }
            $stmt->bind_param('siidiii', $name, $unit_id, $status, $price, $cat_id, $supplier_id, $id);
            $ok = $stmt->execute(); $stmt->close(); echo json_encode(['success' => (bool)$ok]); exit;
        }
        if ($action === 'delete') {
            $id = isset($input['item_id']) ? (int)$input['item_id'] : 0; if (!$id) { http_response_code(400); echo json_encode(['success' => false]); exit; }
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM tbl_progress WHERE item_id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $res = $stmt->get_result(); $row = $res ? $res->fetch_assoc() : null; $stmt->close();
            if ($row && (int)$row['c'] > 0) { http_response_code(409); echo json_encode(['success' => false, 'message' => 'Item has transactions']); exit; }
            $conn->query('DELETE FROM tbl_item_stock WHERE item_id = '.(int)$id);
            $stmt = $conn->prepare('DELETE FROM tbl_items WHERE item_id = ?'); if (!$stmt) { http_response_code(500); echo json_encode(['success' => false]); exit; }
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close(); echo json_encode(['success' => (bool)$ok]); exit;
        }
        http_response_code(400); echo json_encode(['success' => false]); exit;
    } elseif ($is_api === 'items-for-stock') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $sql = 'SELECT i.item_id,i.item_name,i.cat_id,i.supplier_id,i.unit_id,u.unit_name,i.price,i.item_status,c.cat_name,s.supplier_name 
                    FROM tbl_items i 
                    LEFT JOIN tbl_categories c ON c.cat_id=i.cat_id 
                    LEFT JOIN suppliers s ON s.supplier_id=i.supplier_id 
                    LEFT JOIN tbl_units u ON u.unit_id = i.unit_id 
                    LEFT JOIN tbl_progress p ON p.item_id = i.item_id
                    WHERE p.item_id IS NULL
                    ORDER BY i.item_id DESC';
            $res = $conn->query($sql); $out = [];
            if ($res) { while ($row = $res->fetch_assoc()) { $out[] = [ 'item_id' => (int)$row['item_id'], 'item_name' => (string)$row['item_name'], 'cat_id' => (int)$row['cat_id'], 'cat_name' => (string)($row['cat_name'] ?? ''), 'supplier_id' => (int)$row['supplier_id'], 'supplier_name' => (string)($row['supplier_name'] ?? ''), 'unit_id' => (int)$row['unit_id'], 'unit_name' => (string)($row['unit_name'] ?? ''), 'price' => (float)$row['price'], 'item_status' => (int)$row['item_status'] ]; } }
            echo json_encode(['success' => true, 'data' => $out]); exit;
        }
    } elseif ($is_api === 'units') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $res = $conn->query('SELECT unit_id, unit_name, status, created_at FROM tbl_units ORDER BY unit_id DESC');
            $out = [];
            if ($res) { 
                while ($row = $res->fetch_assoc()) { 
                    $out[] = [
                        'unit_id' => (int)$row['unit_id'], 
                        'unit_name' => (string)$row['unit_name'], 
                        'status' => (int)$row['status'],
                        'created_at' => (string)$row['created_at']
                    ]; 
                } 
            }
            echo json_encode(['success' => true, 'data' => $out]);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = $_POST; }
        $action = isset($input['action']) ? (string)$input['action'] : '';
        
        if ($action === 'add') {
            $name = isset($input['unit_name']) ? trim((string)$input['unit_name']) : '';
            $status = isset($input['status']) ? (int)$input['status'] : 1;
            
            if ($name === '') { 
                http_response_code(400); 
                echo json_encode(['success' => false, 'message' => 'Unit name is required']); 
                exit; 
            }
            
            // Check if unit already exists
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM tbl_units WHERE unit_name = ?');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            
            if ($row && (int)$row['c'] > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Unit already exists']);
                exit;
            }
            
            $stmt = $conn->prepare('INSERT INTO tbl_units(unit_name, status) VALUES (?, ?)');
            if (!$stmt) { 
                http_response_code(500); 
                echo json_encode(['success' => false, 'message' => 'Database error']); 
                exit; 
            }
            $stmt->bind_param('si', $name, $status);
            $ok = $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => (bool)$ok, 
                'unit_id' => (int)$id,
                'unit_name' => $name,
                'status' => $status
            ]);
            exit;
        }
        
        if ($action === 'update') {
            $id = isset($input['unit_id']) ? (int)$input['unit_id'] : 0;
            $name = isset($input['unit_name']) ? trim((string)$input['unit_name']) : '';
            $status = isset($input['status']) ? (int)$input['status'] : 1;
            
            if (!$id || $name === '') { 
                http_response_code(400); 
                echo json_encode(['success' => false, 'message' => 'Invalid input']); 
                exit; 
            }
            
            // Check if unit with same name exists (excluding current unit)
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM tbl_units WHERE unit_name = ? AND unit_id != ?');
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            
            if ($row && (int)$row['c'] > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Another unit with this name already exists']);
                exit;
            }
            
            $stmt = $conn->prepare('UPDATE tbl_units SET unit_name = ?, status = ? WHERE unit_id = ?');
            if (!$stmt) { 
                http_response_code(500); 
                echo json_encode(['success' => false, 'message' => 'Database error']); 
                exit; 
            }
            $stmt->bind_param('sii', $name, $status, $id);
            $ok = $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => (bool)$ok,
                'unit_id' => $id,
                'unit_name' => $name,
                'status' => $status
            ]);
            exit;
        }
        
        if ($action === 'delete') {
            $id = isset($input['unit_id']) ? (int)$input['unit_id'] : 0;
            if (!$id) { 
                http_response_code(400); 
                echo json_encode(['success' => false, 'message' => 'Invalid unit ID']); 
                exit; 
            }
            
            // Check if unit is in use
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM tbl_items WHERE unit_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            
            if ($row && (int)$row['c'] > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Cannot delete: Unit is in use by one or more items']);
                exit;
            }
            
            $stmt = $conn->prepare('DELETE FROM tbl_units WHERE unit_id = ?');
            if (!$stmt) { 
                http_response_code(500); 
                echo json_encode(['success' => false, 'message' => 'Database error']); 
                exit; 
            }
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    } elseif ($is_api === 'stock') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $sql = 'SELECT s.stock_id, s.item_id, s.qty, s.last_update, i.item_name, u.unit_name AS item_unit
        FROM tbl_item_stock s 
        LEFT JOIN tbl_items i ON i.item_id = s.item_id 
        LEFT JOIN tbl_units u ON u.unit_id = i.unit_id 
        ORDER BY s.stock_id DESC';
            $res = $conn->query($sql); $out = [];
            if ($res) { while ($row = $res->fetch_assoc()) { $out[] = [ 'stock_id' => (int)$row['stock_id'], 'item_id' => (int)$row['item_id'], 'qty' => (float)$row['qty'], 'last_update' => (string)$row['last_update'], 'item_name' => (string)$row['item_name'], 'item_unit' => (string)$row['item_unit'] ]; } }
            echo json_encode(['success' => true, 'data' => $out]); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true); if (!is_array($input)) { $input = $_POST; }
        $action = isset($input['action']) ? (string)$input['action'] : '';
        if ($action === 'add') {
            $item_id = isset($input['item_id']) ? (int)$input['item_id'] : 0; $qty = isset($input['qty']) ? (float)$input['qty'] : 0;
            if (!$item_id || $qty <= 0) { http_response_code(400); echo json_encode(['success' => false]); exit; }
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM tbl_progress WHERE item_id = ?'); $stmt->bind_param('i', $item_id); $stmt->execute(); $res = $stmt->get_result(); $row = $res ? $res->fetch_assoc() : null; $stmt->close();
            if ($row && (int)$row['c'] > 0) { http_response_code(409); echo json_encode(['success' => false, 'message' => 'Item already has transactions']); exit; }
            $stmt = $conn->prepare('INSERT INTO tbl_item_stock(item_id, qty) VALUES (?, ?)'); if (!$stmt) { http_response_code(500); echo json_encode(['success' => false]); exit; }
            $stmt->bind_param('id', $item_id, $qty); $ok1 = $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare('INSERT INTO tbl_progress(item_id, date, in_qty, out_qty, last_qty, end_qty, created_by) VALUES (?, CURDATE(), ?, 0, 0, ?, ?)'); if (!$stmt) { http_response_code(500); echo json_encode(['success' => false]); exit; }
            $created_by = (int)$_SESSION['user_id']; $stmt->bind_param('iddi', $item_id, $qty, $qty, $created_by); $ok2 = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => (bool)($ok1 && $ok2)]); exit;
        }
        http_response_code(400); echo json_encode(['success' => false]); exit;
    } else {
        http_response_code(404); echo json_encode(['success' => false]); exit;
    }
}
if (!isset($_SESSION['user_id'])) { header('Location: /SMS/index.php?page=login'); exit; }
if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== ROLE_ADMIN) { http_response_code(403); echo 'Access denied'; exit; }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Categories, Items & Opening Stock</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <link rel="stylesheet" href="../css/sidebar.css">
   <link rel="stylesheet" href="../css/item.css">
  </head>
  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/sidebar.php'; ?>
      <div class="main-content">
      <?php include __DIR__ . '/header.php'; ?>  
        <div class="content">
    <div class="page-header">
      <h1><i class="fas fa-plus-circle"></i> Add Items & Categories</h1>
      <p>Manage categories, items, and opening stock</p>
    </div>

    <div class="container">
      <!-- Tabs -->
      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('categories')">
          <i class="fas fa-tags"></i> Categories
        </button>
        <button class="tab-btn" onclick="switchTab('units')">
          <i class="fas fa-ruler"></i> Units
        </button>
        <button class="tab-btn" onclick="switchTab('items')">
          <i class="fas fa-box"></i> Items
        </button>
        <button class="tab-btn" onclick="switchTab('opening-stock')">
          <i class="fas fa-warehouse"></i> Opening Stock
        </button>
      </div>

      <!-- Categories Tab -->
      <div class="tab-content active" id="categoriesTab">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Add New Category</h3>
          </div>
          <form id="addCategoryForm">
            <div class="form-grid">
              <div class="form-group">
                <label>Category Name <span class="required">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  id="catName"
                  placeholder="Enter category name"
                  required
                />
              </div>
              <div class="form-group" style="grid-column: 1 / -1;">
                <label>Description</label>
                <textarea
                  class="form-control"
                  id="catDescription"
                  placeholder="Enter category description (optional)"
                ></textarea>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-plus"></i> Add Category
            </button>
          </form>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Categories List</h3>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Category Name</th>
                  <th>Description</th>
                  <th>Created At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="categoriesTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Units Tab -->
      <div class="tab-content" id="unitsTab">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Add New Unit</h3>
          </div>
          <form id="addUnitForm">
            <div class="form-grid">
              <div class="form-group">
                <label>Unit Name <span class="required">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  id="unitName"
                  placeholder="e.g., PCS, KG, LITER"
                  required
                />
              </div>
              <div class="form-group">
                <label>Status</label>
                <select class="form-control" id="unitStatus">
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
              <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-plus"></i> Add Unit
                </button>
              </div>
            </div>
          </form>
        </div>

        <div class="card mt-4">
          <div class="card-header">
            <h3 class="card-title">Units List</h3>
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Unit Name</th>
                  <th>Status</th>
                  <th>Created At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="unitsTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Items Tab -->
      <div class="tab-content" id="itemsTab">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Add New Item</h3>
          </div>
          <form id="addItemForm">
            <div class="form-grid">
              <div class="form-group">
                <label>Item Name <span class="required">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  id="itemName"
                  placeholder="Enter item name"
                  required
                />
              </div>
              <div class="form-group">
                <label>Category <span class="required">*</span></label>
                <select class="form-control" id="itemCategory" required>
                  <option value="">Select category</option>
                </select>
              </div>
              <div class="form-group">
                <label>Supplier <span class="required">*</span></label>
                <select class="form-control" id="itemSupplier" required>
                  <option value="">Select supplier</option>
                </select>
              </div>
              <div class="form-group">
                <label>Unit <span class="required">*</span></label>
                <select class="form-control" id="itemUnit" required>
                  <option value="">Select unit</option>
                </select>
              </div>
              <div class="form-group">
                <label>Price <span class="required">*</span></label>
                <input
                  type="number"
                  step="0.01"
                  class="form-control"
                  id="itemPrice"
                  placeholder="0.00"
                  required
                />
              </div>
              <div class="form-group">
                <label>Minimum Price <span class="required">*</span></label>
                <input
                  type="number"
                  step="0.01"
                  class="form-control"
                  id="itemMinPrice"
                  placeholder="0.00"
                  required
                />
              </div>
              <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <select class="form-control" id="itemStatus" required>
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-plus"></i> Add Item
            </button>
          </form>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Items List</h3>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Item Name</th>
                  <th>Category</th>
                  <th>Unit</th>
                  <th>Price</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="itemsTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Opening Stock Tab -->
      <div class="tab-content" id="opening-stockTab">
        <div class="warning-box">
          <i class="fas fa-exclamation-triangle"></i>
          <p><strong>Important:</strong> Opening stock can only be added for NEW items that have no transactions in the system. If an item already has transactions, you cannot add opening stock.</p>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Add Opening Stock</h3>
          </div>
          <form id="addOpeningStockForm">
            <div class="form-grid">
              <div class="form-group">
                <label>Select Item <span class="required">*</span></label>
                <select class="form-control" id="stockItem" required onchange="checkItemTransactions()">
                  <option value="">Select an item</option>
                </select>
              </div>
              <div class="form-group">
                <label>Opening Quantity <span class="required">*</span></label>
                <input
                  type="number"
                  step="0.01"
                  class="form-control"
                  id="stockQty"
                  placeholder="0.00"
                  required
                />
              </div>
            </div>
            <div id="stockWarning" class="info-box" style="display: none;">
              <i class="fas fa-info-circle"></i>
              <p id="stockWarningText"></p>
            </div>
            <button type="submit" class="btn btn-primary" id="submitStockBtn">
              <i class="fas fa-save"></i> Add Opening Stock
            </button>
          </form>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Items with Opening Stock</h3>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Item ID</th>
                  <th>Item Name</th>
                  <th>Opening Quantity</th>
                  <th>Unit</th>
                  <th>Date Added</th>
                </tr>
              </thead>
              <tbody id="openingStockTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
        </div>
      </div>
    </div>

    <script src="../js/sidebar.js"></script>
    <script src="../js/item.js"></script>
    
  </body>
</html>

