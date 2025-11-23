<?php
require_once __DIR__ . '/../config/db.php';

$is_api = isset($_GET['api']) ? (string)$_GET['api'] : '';
if ($is_api === 'cogs-report') {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header('Content-Type: application/json');

    $date_from = isset($_GET['from']) ? $_GET['from'] : null;
    $date_to = isset($_GET['to']) ? $_GET['to'] : null;
    $cat_id = isset($_GET['category']) ? (int)$_GET['category'] : null;

    $where_clauses = [];
    if($date_from) $where_clauses[] = "r.created_at >= '$date_from 00:00:00'";
    if($date_to) $where_clauses[] = "r.created_at <= '$date_to 23:59:59'";
    if($cat_id) $where_clauses[] = "i.cat_id = $cat_id";
    $where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    $query = "
        SELECT
            ri.qty,
            ri.price as sale_price,
            i.price as cost_price, -- This assumes tbl_items.price is the cost.
            r.receipt_code,
            r.created_at as sale_date,
            u.fullname as created_by,
            i.item_id,
            i.item_name,
            c.cat_id,
            c.cat_name
        FROM receipt_items ri
        JOIN receipts r ON ri.receipt_code = r.receipt_code
        JOIN tbl_items i ON ri.item_id = i.item_id
        JOIN users u ON r.created_by = u.user_id
        LEFT JOIN tbl_categories c ON i.cat_id = c.cat_id
        $where_sql
    ";

    $res = $conn->query($query);
    $sales_data = [];
    if($res) {
        while($row = $res->fetch_assoc()) {
            $sales_data[] = $row;
        }
    }
    
    // Categories for filter
    $cat_res = $conn->query("SELECT cat_id, cat_name FROM tbl_categories ORDER BY cat_name");
    $categories = [];
    while($row = $cat_res->fetch_assoc()) $categories[] = $row;

    echo json_encode([
        'success' => true,
        'data' => [
            'sales' => $sales_data,
            'categories' => $categories
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
    <title>Cost of Goods Sold (COGS) Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/costreport.css" />
</head>
<body>
    <div class="dashboard">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>
            <div class="content">
                <div class="page-header">
                    <h1><i class="fas fa-chart-line"></i> Cost of Goods Sold (COGS) Report</h1>
                    <p>Comprehensive analysis of sales costs, revenue, and profitability</p>
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
                                <label><i class="fas fa-tags"></i> Category</label>
                                <select class="form-control" id="categoryFilter">
                                    <option value="">All Categories</option>
                                </select>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-primary" onclick="applyFilters()">
                                    <i class="fas fa-filter"></i> Apply
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
                            <div class="stat-header"><div class="stat-icon green"><i class="fas fa-dollar-sign"></i></div><div class="stat-title">Total Revenue</div></div>
                            <div class="stat-value" id="totalRevenue">$0.00</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header"><div class="stat-icon red"><i class="fas fa-shopping-cart"></i></div><div class="stat-title">Total COGS</div></div>
                            <div class="stat-value" id="totalCOGS">$0.00</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header"><div class="stat-icon blue"><i class="fas fa-chart-pie"></i></div><div class="stat-title">Gross Profit</div></div>
                            <div class="stat-value" id="grossProfit">$0.00</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header"><div class="stat-icon purple"><i class="fas fa-percentage"></i></div><div class="stat-title">Profit Margin</div></div>
                            <div class="stat-value" id="profitMargin">0%</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header"><div class="stat-icon orange"><i class="fas fa-receipt"></i></div><div class="stat-title">Total Receipts</div></div>
                            <div class="stat-value" id="totalReceipts">0</div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="charts-grid">
                        <div class="card"><div class="card-header"><h3 class="card-title">Revenue vs COGS Trend</h3></div><div class="chart-container"><canvas id="revenueVsCOGSChart"></canvas></div></div>
                        <div class="card"><div class="card-header"><h3 class="card-title">Profit Margin by Category</h3></div><div class="chart-container"><canvas id="profitByCategoryChart"></canvas></div></div>
                    </div>

                    <!-- Detailed Reports -->
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Detailed COGS Analysis</h3></div>
                        <div class="tabs">
                            <button class="tab-btn active" onclick="switchTab(event, 'by-receipt')"><i class="fas fa-receipt"></i> By Receipt</button>
                            <button class="tab-btn" onclick="switchTab(event, 'by-item')"><i class="fas fa-box"></i> By Item</button>
                            <button class="tab-btn" onclick="switchTab(event, 'by-category')"><i class="fas fa-tags"></i> By Category</button>
                        </div>
                        <div class="tab-content active" id="by-receiptTab"><div class="table-container"><table><thead><tr><th>Receipt Code</th><th>Date</th><th>Items Sold</th><th class="text-right">Total Revenue</th><th class="text-right">Total COGS</th><th class="text-right">Gross Profit</th><th class="text-right">Margin %</th><th>Created By</th></tr></thead><tbody id="receiptTableBody"></tbody></table></div></div>
                        <div class="tab-content" id="by-itemTab"><div class="table-container"><table><thead><tr><th>Item Name</th><th>Category</th><th class="text-right">Qty Sold</th><th class="text-right">Avg Sale Price</th><th class="text-right">Avg Cost Price</th><th class="text-right">Total Revenue</th><th class="text-right">Total COGS</th><th class="text-right">Gross Profit</th><th>Margin</th></tr></thead><tbody id="itemTableBody"></tbody></table></div></div>
                        <div class="tab-content" id="by-categoryTab"><div class="table-container"><table><thead><tr><th>Category</th><th class="text-right">Items Sold</th><th class="text-right">Qty Sold</th><th class="text-right">Total Revenue</th><th class="text-right">Total COGS</th><th class="text-right">Gross Profit</th><th class="text-right">Margin %</th><th>Performance</th></tr></thead><tbody id="categoryTableBody"></tbody></table></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/sidebar.js"></script>
    <script src="../js/costreport.js"></script>
</body>
</html>
