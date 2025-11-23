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
            $sql = 'SELECT i.item_id,i.item_name,i.cat_id,i.supplier_id,i.item_unit,i.price,i.item_status,c.cat_name,s.supplier_name FROM tbl_items i LEFT JOIN tbl_categories c ON c.cat_id=i.cat_id LEFT JOIN suppliers s ON s.supplier_id=i.supplier_id ORDER BY i.item_id DESC';
            $res = $conn->query($sql); $out = [];
            if ($res) { while ($row = $res->fetch_assoc()) { $out[] = [ 'item_id' => (int)$row['item_id'], 'item_name' => (string)$row['item_name'], 'cat_id' => (int)$row['cat_id'], 'cat_name' => (string)($row['cat_name'] ?? ''), 'supplier_id' => (int)$row['supplier_id'], 'supplier_name' => (string)($row['supplier_name'] ?? ''), 'item_unit' => (string)$row['item_unit'], 'price' => (float)$row['price'], 'item_status' => (int)$row['item_status'] ]; } }
            echo json_encode(['success' => true, 'data' => $out]); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true); if (!is_array($input)) { $input = $_POST; }
        $action = isset($input['action']) ? (string)$input['action'] : '';
        if ($action === 'add') {
            $name = isset($input['item_name']) ? trim((string)$input['item_name']) : '';
            $cat_id = isset($input['cat_id']) ? (int)$input['cat_id'] : 0;
            $supplier_id = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
            $unit = isset($input['item_unit']) ? trim((string)$input['item_unit']) : '';
            $price = isset($input['price']) ? (float)$input['price'] : 0;
            $status = isset($input['item_status']) ? (int)$input['item_status'] : 1;
            if ($name === '' || !$cat_id || !$supplier_id || $unit === '' || $price <= 0) { http_response_code(400); echo json_encode(['success' => false]); exit; }
            $stmt = $conn->prepare('INSERT INTO tbl_items(item_name,item_unit,item_status,price,cat_id,supplier_id,created_by) VALUES (?,?,?,?,?,?,?)'); if (!$stmt) { http_response_code(500); echo json_encode(['success' => false]); exit; }
            $created_by = (int)$_SESSION['user_id'];
            $stmt->bind_param('ssidiii', $name, $unit, $status, $price, $cat_id, $supplier_id, $created_by);
            $ok = $stmt->execute();
            $id = $stmt->insert_id; $stmt->close();
            echo json_encode(['success' => (bool)$ok, 'item_id' => (int)$id]); exit;
        }
        if ($action === 'update') {
            $id = isset($input['item_id']) ? (int)$input['item_id'] : 0;
            $name = isset($input['item_name']) ? trim((string)$input['item_name']) : '';
            $cat_id = isset($input['cat_id']) ? (int)$input['cat_id'] : 0;
            $supplier_id = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
            $unit = isset($input['item_unit']) ? trim((string)$input['item_unit']) : '';
            $price = isset($input['price']) ? (float)$input['price'] : 0;
            $status = isset($input['item_status']) ? (int)$input['item_status'] : 1;
            if (!$id || $name === '' || !$cat_id || !$supplier_id || $unit === '' || $price <= 0) { http_response_code(400); echo json_encode(['success' => false]); exit; }
            $stmt = $conn->prepare('UPDATE tbl_items SET item_name = ?, item_unit = ?, item_status = ?, price = ?, cat_id = ?, supplier_id = ? WHERE item_id = ?'); if (!$stmt) { http_response_code(500); echo json_encode(['success' => false]); exit; }
            $stmt->bind_param('ssidiii', $name, $unit, $status, $price, $cat_id, $supplier_id, $id);
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
    } elseif ($is_api === 'stock') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $sql = 'SELECT s.stock_id,s.item_id,s.qty,s.last_update,i.item_name,i.item_unit FROM tbl_item_stock s LEFT JOIN tbl_items i ON i.item_id=s.item_id ORDER BY s.stock_id DESC';
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
        <div class="header">
          <div class="header-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
              <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title" id="pageTitle">Add Items & Categories</h1>
          </div>
          <div class="profile-container">
            <div class="profile-icon">
              <i class="fas fa-user"></i>
            </div>
            <div class="profile-dropdown">
              <div class="dropdown-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
              </div>
              <div class="dropdown-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
              </div>
            </div>
          </div>
        </div>
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
                <input
                  type="text"
                  class="form-control"
                  id="itemUnit"
                  placeholder="e.g., PCS, KG, LITER"
                  required
                />
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
    <script>
      let categories = [];
      let suppliers = [];
      let items = [];
      let stock = [];
      const apiBase = '/SMS/pages/additem.php';

      // Tab switching
      function switchTab(tab) {
        const tabs = document.querySelectorAll(".tab-btn");
        const contents = document.querySelectorAll(".tab-content");

        tabs.forEach(t => t.classList.remove("active"));
        contents.forEach(c => c.classList.remove("active"));

        // Ensure proper activation without relying on event
        const btns = Array.from(document.querySelectorAll('.tab-btn'));
        if (tab === 'categories') btns[0]?.classList.add('active');
        else if (tab === 'items') btns[1]?.classList.add('active');
        else btns[2]?.classList.add('active');
        document.getElementById(tab + "Tab").classList.add("active");
      }

      // Load dropdowns
      function loadDropdowns() {
        // Category dropdown
        const catSelect = document.getElementById("itemCategory");
        catSelect.innerHTML = '<option value="">Select category</option>';
        categories.forEach(cat => {
          catSelect.innerHTML += `<option value="${cat.cat_id}">${cat.cat_name}</option>`;
        });

        // Supplier dropdown
        const suppSelect = document.getElementById("itemSupplier");
        suppSelect.innerHTML = '<option value="">Select supplier</option>';
        suppliers.forEach(sup => {
          suppSelect.innerHTML += `<option value="${sup.supplier_id}">${sup.supplier_name}</option>`;
        });

        // Stock item dropdown (only items without transactions)
        const stockSelect = document.getElementById("stockItem");
        stockSelect.innerHTML = '<option value="">Select an item</option>';
        items.forEach(item => {
          stockSelect.innerHTML += `<option value="${item.item_id}">${item.item_name} (${item.item_unit})</option>`;
        });
      }

      // Load categories table
      function loadCategories() {
        const tbody = document.getElementById("categoriesTableBody");
        tbody.innerHTML = "";

        if (categories.length === 0) { return; }

        categories.forEach(cat => {
          tbody.innerHTML += `
            <tr>
              <td>${cat.cat_id}</td>
              <td><strong>${cat.cat_name}</strong></td>
              <td>${cat.description || '<span style="color: var(--gray-mid)">N/A</span>'}</td>
              <td>${cat.created_at}</td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-primary" onclick="editCategory(${cat.cat_id})">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="deleteCategory(${cat.cat_id})">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
        });
      }

      // Load items table
      function loadItems() {
        const tbody = document.getElementById("itemsTableBody");
        tbody.innerHTML = "";

        if (items.length === 0) { return; }

        items.forEach(item => {
          const cat = categories.find(c => c.cat_id === item.cat_id);
          const status = item.item_status === 1 
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-danger">Inactive</span>';

          tbody.innerHTML += `
            <tr>
              <td>${item.item_id}</td>
              <td><strong>${item.item_name}</strong></td>
              <td>${cat ? cat.cat_name : 'N/A'}</td>
              <td>${item.item_unit}</td>
              <td>$${item.price.toFixed(2)}</td>
              <td>${status}</td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-primary" onclick="editItem(${item.item_id})">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="deleteItem(${item.item_id})">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
        });
      }

      // Load opening stock table
      function loadOpeningStock() {
        const tbody = document.getElementById("openingStockTableBody");
        tbody.innerHTML = "";

        if (stock.length === 0) { return; }

        stock.forEach(s => {
          const item = items.find(i => i.item_id === s.item_id);
          if (item) {
            tbody.innerHTML += `
              <tr>
                <td>${item.item_id}</td>
                <td><strong>${item.item_name}</strong></td>
                <td>${s.qty}</td>
                <td>${item.item_unit}</td>
                <td>${s.last_update}</td>
              </tr>
            `;
          }
        });
      }

      // Check if item has transactions
      function checkItemTransactions() {
        const itemId = parseInt(document.getElementById("stockItem").value);
        const warning = document.getElementById("stockWarning");
        const warningText = document.getElementById("stockWarningText");
        const submitBtn = document.getElementById("submitStockBtn");

        if (!itemId) {
          warning.style.display = "none";
          submitBtn.disabled = false;
          return;
        }

        warning.style.display = "none";
        submitBtn.disabled = false;
      }

      // Add Category
      document.getElementById("addCategoryForm").addEventListener("submit", (e) => {
        e.preventDefault();
        const name = document.getElementById("catName").value.trim();
        const desc = document.getElementById("catDescription").value.trim();

        if (!name) return;
        fetch(`${apiBase}?api=categories`, {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'add', cat_name: name, description: desc })
        }).then(r => r.json()).then(resp => {
          if (resp && resp.success) {
            document.getElementById("addCategoryForm").reset();
            fetchCategories();
            loadDropdowns();
            Swal.fire({ icon: 'success', title: 'Category Added!', timer: 2000, showConfirmButton: false });
          }
        });
      });

      // Add Item
      document.getElementById("addItemForm").addEventListener("submit", (e) => {
        e.preventDefault();
        
        const payload = {
          item_name: document.getElementById('itemName').value.trim(),
          cat_id: parseInt(document.getElementById('itemCategory').value),
          supplier_id: parseInt(document.getElementById('itemSupplier').value),
          item_unit: document.getElementById('itemUnit').value.trim(),
          price: parseFloat(document.getElementById('itemPrice').value),
          item_status: parseInt(document.getElementById('itemStatus').value)
        };
        if (!payload.item_name || !payload.cat_id || !payload.supplier_id || !payload.item_unit || !payload.price) return;
        fetch(`${apiBase}?api=items`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'add', ...payload }) })
          .then(r => r.json()).then(resp => {
            if (resp && resp.success) {
              document.getElementById('addItemForm').reset();
              fetchItems();
              fetchStock();
              loadDropdowns();
              Swal.fire({ icon: 'success', title: 'Item Added!', text: 'You can now add opening stock for this item', timer: 2000, showConfirmButton: false });
            }
          });
      });

      // Add Opening Stock
      document.getElementById("addOpeningStockForm").addEventListener("submit", (e) => {
        e.preventDefault();
        
        const itemId = parseInt(document.getElementById('stockItem').value);
        const qty = parseFloat(document.getElementById('stockQty').value);
        if (!itemId || !qty || qty <= 0) return;
        fetch(`${apiBase}?api=stock`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'add', item_id: itemId, qty }) })
          .then(r => r.json()).then(resp => {
            if (resp && resp.success) {
              document.getElementById('addOpeningStockForm').reset();
              document.getElementById('stockWarning').style.display = 'none';
              fetchStock();
              Swal.fire({ icon: 'success', title: 'Opening Stock Added!', timer: 2000, showConfirmButton: false });
            } else {
              Swal.fire({ icon: 'error', title: 'Cannot Add Stock', text: resp && resp.message ? resp.message : 'Error' });
            }
          });
      });

      // Edit/Delete functions (simplified)
      function editCategory(id) {
        Swal.fire({ icon: "info", title: "Edit Category", text: "Edit functionality - connect to your API" });
      }

      function deleteCategory(id) {
        Swal.fire({ icon: "warning", title: "Delete Category", text: "Delete functionality - connect to your API" });
      }

      function editItem(id) {
        Swal.fire({ icon: "info", title: "Edit Item", text: "Edit functionality - connect to your API" });
      }

      function deleteItem(id) {
        Swal.fire({ icon: "warning", title: "Delete Item", text: "Delete functionality - connect to your API" });
      }

      async function fetchCategories() {
        const r = await fetch(`${apiBase}?api=categories`); const d = await r.json(); categories = d && d.success ? d.data : []; loadCategories();
      }
      async function fetchItems() {
        const r = await fetch(`${apiBase}?api=items`); const d = await r.json(); items = d && d.success ? d.data : []; loadItems();
      }
      async function fetchStock() {
        const r = await fetch(`${apiBase}?api=stock`); const d = await r.json(); stock = d && d.success ? d.data : []; loadOpeningStock();
      }
      async function fetchSuppliers() {
        const r = await fetch('/SMS/pages/supplier.php?api=suppliers'); const d = await r.json(); suppliers = d && d.success ? d.data : []; loadDropdowns();
      }
      (async function init(){
        await fetchCategories();
        await fetchSuppliers();
        await fetchItems();
        await fetchStock();
      })();
    </script>
  </body>
</html>
