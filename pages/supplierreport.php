<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Supplier Report - Supply Analysis</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
   <link rel="stylesheet" href="../css/supplierreport.css"> 
  </head>
  <body>
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
          <button class="tab-btn active" onclick="switchTab('purchase-requests')">
            <i class="fas fa-file-alt"></i> Purchase Requests
          </button>
          <button class="tab-btn" onclick="switchTab('stock-in')">
            <i class="fas fa-arrow-down"></i> Stock In History
          </button>
          <button class="tab-btn" onclick="switchTab('supplier-items')">
            <i class="fas fa-boxes"></i> Items by Supplier
          </button>
          <button class="tab-btn" onclick="switchTab('performance')">
            <i class="fas fa-chart-bar"></i> Performance Analysis
          </button>
        </div>

        <!-- Purchase Requests Tab -->
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

        <!-- Stock In History Tab -->
        <div class="tab-content" id="stock-inTab">
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Item</th>
                  <th>Supplier</th>
                  <th class="text-right">Quantity</th>
                  <th class="text-right">Unit Price</th>
                  <th class="text-right">Total Value</th>
                  <th>Created By</th>
                  <th>Remark</th>
                </tr>
              </thead>
              <tbody id="stockInBody">
                <!-- Data will be inserted here -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Items by Supplier Tab -->
        <div class="tab-content" id="supplier-itemsTab">
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Supplier</th>
                  <th>Item Name</th>
                  <th>Category</th>
                  <th>Unit</th>
                  <th class="text-right">Current Stock</th>
                  <th class="text-right">Unit Price</th>
                  <th class="text-right">Stock Value</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="supplierItemsBody">
                <!-- Data will be inserted here -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Performance Analysis Tab -->
        <div class="tab-content" id="performanceTab">
          <div class="stats-grid" style="margin-bottom: 25px;">
            <div class="stat-card">
              <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="stat-content">
                <h3 id="receivedCount">0</h3>
                <p>Completed Orders</p>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
              </div>
              <div class="stat-content">
                <h3 id="pendingCount">0</h3>
                <p>Pending Orders</p>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon blue">
                <i class="fas fa-percent"></i>
              </div>
              <div class="stat-content">
                <h3 id="completionRate">0%</h3>
                <p>Completion Rate</p>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon purple">
                <i class="fas fa-chart-line"></i>
              </div>
              <div class="stat-content">
                <h3 id="avgOrderValue">$0.00</h3>
                <p>Avg Order Value</p>
              </div>
            </div>
          </div>

          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Supplier</th>
                  <th class="text-right">Total Orders</th>
                  <th class="text-right">Completed</th>
                  <th class="text-right">Pending</th>
                  <th class="text-right">Total Value</th>
                  <th class="text-right">Avg Order Value</th>
                  <th class="text-right">Completion Rate</th>
                </tr>
              </thead>
              <tbody id="performanceBody">
                <!-- Data will be inserted here -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <script src="../js/supplierreport.php"></script>
  </body>
</html>