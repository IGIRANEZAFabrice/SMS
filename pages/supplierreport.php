<?php
require_once __DIR__ . '/../config/db.php';

$is_api = isset($_GET['api']) ? (string)$_GET['api'] : '';
if ($is_api === 'supplier-report') {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header('Content-Type: application/json');

    $date_from = isset($_GET['from']) ? $_GET['from'] : null;
    $date_to = isset($_GET['to']) ? $_GET['to'] : null;
    $supplier_id = isset($_GET['supplier']) ? (int)$_GET['supplier'] : null;

    $where_clauses = [];
    if($date_from) $where_clauses[] = "pr.request_date >= '$date_from'";
    if($date_to) $where_clauses[] = "pr.request_date <= '$date_to'";
    if($supplier_id) $where_clauses[] = "pr.supplier_id = $supplier_id";
    $where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    // General Stats
    $stats_query = "
        SELECT
            (SELECT COUNT(DISTINCT supplier_id) FROM purchase_request) as totalSuppliers,
            (SELECT COUNT(*) FROM purchase_request) as totalRequests,
            (SELECT SUM(qty_requested) FROM purchase_request_items) as totalItems,
            (SELECT SUM(pri.qty_requested * i.price) FROM purchase_request_items pri JOIN tbl_items i ON pri.item_id = i.item_id) as totalValue
    ";
    $stats = $conn->query($stats_query)->fetch_assoc();

    // Top Suppliers
    $top_suppliers_query = "
        SELECT s.supplier_name, s.phone, COUNT(pr.request_id) as total_requests, SUM(pri.qty_requested * i.price) as total_value
        FROM suppliers s
        JOIN purchase_request pr ON s.supplier_id = pr.supplier_id
        JOIN purchase_request_items pri ON pr.request_id = pri.request_id
        JOIN tbl_items i ON pri.item_id = i.item_id
        GROUP BY s.supplier_id
        ORDER BY total_value DESC
        LIMIT 5
    ";
    $top_suppliers_res = $conn->query($top_suppliers_query);
    $top_suppliers = [];
    while($row = $top_suppliers_res->fetch_assoc()) $top_suppliers[] = $row;
    
    // Purchase Requests
    $purchase_requests_query = "
        SELECT pr.request_id, s.supplier_name, pr.request_date, pr.status, u.fullname as created_by,
               (SELECT COUNT(*) FROM purchase_request_items WHERE request_id = pr.request_id) as items_count,
               (SELECT SUM(qty_requested) FROM purchase_request_items WHERE request_id = pr.request_id) as total_quantity,
               (SELECT SUM(pri.qty_requested * i.price) FROM purchase_request_items pri JOIN tbl_items i ON pri.item_id = i.item_id WHERE pri.request_id = pr.request_id) as estimated_value
        FROM purchase_request pr
        JOIN suppliers s ON pr.supplier_id = s.supplier_id
        JOIN users u ON pr.created_by = u.user_id
        $where_sql
        ORDER BY pr.request_date DESC
    ";
    $purchase_requests_res = $conn->query($purchase_requests_query);
    $purchase_requests = [];
    while($row = $purchase_requests_res->fetch_assoc()) $purchase_requests[] = $row;
    
    // Supplier list for filter
    $suppliers_res = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
    $suppliers = [];
    while($row = $suppliers_res->fetch_assoc()) $suppliers[] = $row;

    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => $stats,
            'topSuppliers' => $top_suppliers,
            'purchaseRequests' => $purchase_requests,
            'suppliers' => $suppliers,
        ]
    ]);
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
    <title>Supplier Report - Supply Analysis</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/supplierreport.css" />
</head>
<body>
    <div class="dashboard">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>
            <div class="content">
                <div class="page-header">
                  <h1><i class="fas fa-truck-loading"></i> Supplier Report</h1>
                  <p>Comprehensive supplier performance and supply analysis</p>
                </div>

                <div class="container">
                  <!-- Filters -->
                  <div class="filters-card">
                    <div class="filters-grid">
                      <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date From</label>
                        <input type="date" class="form-control" id="dateFrom" />
                      </div>
                      <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date To</label>
                        <input type="date" class="form-control" id="dateTo" />
                      </div>
                      <div class="form-group">
                        <label><i class="fas fa-truck"></i> Supplier</label>
                        <select class="form-control" id="supplierFilter">
                          <option value="">All Suppliers</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label><i class="fas fa-tasks"></i> Status</label>
                        <select class="form-control" id="statusFilter">
                          <option value="">All Status</option>
                          <option value="pending">Pending</option>
                          <option value="approved">Approved</option>
                          <option value="received">Received</option>
                        </select>
                      </div>
                      <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="applyFilters()">
                          <i class="fas fa-filter"></i> Apply
                        </button>
                        <button class="btn btn-secondary" onclick="resetFilters()">
                          <i class="fas fa-redo"></i> Reset
                        </button>
                        <button class="btn btn-success" onclick="exportReport()">
                          <i class="fas fa-file-excel"></i> Export
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Statistics -->
                  <div class="stats-grid">
                    <div class="stat-card">
                      <div class="stat-icon blue">
                        <i class="fas fa-truck"></i>
                      </div>
                      <div class="stat-content">
                        <h3 id="totalSuppliers">0</h3>
                        <p>Active Suppliers</p>
                      </div>
                    </div>

                    <div class="stat-card">
                      <div class="stat-icon green">
                        <i class="fas fa-file-invoice"></i>
                      </div>
                      <div class="stat-content">
                        <h3 id="totalRequests">0</h3>
                        <p>Purchase Requests</p>
                      </div>
                    </div>

                    <div class="stat-card">
                      <div class="stat-icon orange">
                        <i class="fas fa-box"></i>
                      </div>
                      <div class="stat-content">
                        <h3 id="totalItems">0</h3>
                        <p>Items Supplied</p>
                      </div>
                    </div>

                    <div class="stat-card">
                      <div class="stat-icon purple">
                        <i class="fas fa-dollar-sign"></i>
                      </div>
                      <div class="stat-content">
                        <h3 id="totalValue">$0.00</h3>
                        <p>Total Supply Value</p>
                      </div>
                    </div>
                  </div>

                  <!-- Supplier Cards Overview -->
                  <div class="card">
                    <div class="card-header">
                      <h3 class="card-title"><i class="fas fa-star"></i> Top Suppliers</h3>
                      <span id="suppliersCount" style="color: var(--gray-dark); font-size: 14px;"></span>
                    </div>
                    <div class="suppliers-grid" id="suppliersGrid">
                      <!-- Supplier cards will be inserted here -->
                    </div>
                  </div>

                  <!-- Detailed Reports -->
                  <div class="card">
                    <div class="card-header">
                      <h3 class="card-title"><i class="fas fa-chart-line"></i> Detailed Supply Reports</h3>
                    </div>

                    <div class="tabs">
                      <button class="tab-btn active" onclick="switchTab(event, 'purchase-requests')">
                        <i class="fas fa-file-alt"></i> Purchase Requests
                      </button>
                    </div>

                    <div class="tab-content active" id="purchase-requestsTab">
                      <div class="table-container">
                        <table>
                          <thead>
                            <tr>
                              <th>Request ID</th>
                              <th>Supplier</th>
                              <th>Request Date</th>
                              <th>Items</th>
                              <th class="text-right">Total Quantity</th>
                              <th class="text-right">Estimated Value</th>
                              <th>Status</th>
                              <th>Created By</th>
                            </tr>
                          </thead>
                          <tbody id="purchaseRequestsBody">
                            <!-- Data will be inserted here -->
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/sidebar.js"></script>
    <script src="../js/supplierreport.js"></script>
</body>
</html>
