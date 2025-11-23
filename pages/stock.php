<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stock Display - Inventory Overview</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="stylesheet" href="../css/stock.css">
  </head>
  <body>
    <div class="page-header">
      <h1><i class="fas fa-warehouse"></i> Stock Overview</h1>
      <p>View all items in inventory with quantities and values</p>
    </div>

    <div class="container">
      <!-- Statistics -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <i class="fas fa-boxes"></i>
          </div>
          <div class="stat-content">
            <h3 id="totalItems">0</h3>
            <p>Total Items</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <div class="stat-content">
            <h3 id="totalValue">$0.00</h3>
            <p>Total Stock Value</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon orange">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div class="stat-content">
            <h3 id="lowStockItems">0</h3>
            <p>Low Stock Items</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon red">
            <i class="fas fa-times-circle"></i>
          </div>
          <div class="stat-content">
            <h3 id="outOfStock">0</h3>
            <p>Out of Stock</p>
          </div>
        </div>
      </div>

      <!-- Stock Table -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Items in Stock</h3>
          <button class="btn btn-success" onclick="exportStock()">
            <i class="fas fa-file-excel"></i> Export
          </button>
        </div>

        <div class="toolbar">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input
              type="text"
              placeholder="Search items..."
              id="searchStock"
              onkeyup="searchTable(this.value)"
            />
          </div>
          <select class="filter-select" id="categoryFilter" onchange="filterByCategory()">
            <option value="">All Categories</option>
          </select>
          <select class="filter-select" id="statusFilter" onchange="filterByStatus()">
            <option value="">All Status</option>
            <option value="in-stock">In Stock</option>
            <option value="low-stock">Low Stock</option>
            <option value="out-of-stock">Out of Stock</option>
          </select>
        </div>

        <div class="table-container">
          <table id="stockTable">
            <thead>
              <tr>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Category</th>
                <th class="text-right">Quantity</th>
                <th>Unit</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total Value</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="stockTableBody">
              <!-- Stock data will be populated here -->
            </tbody>
            <tfoot>
              <tr style="background: var(--light-bg); font-weight: bold;">
                <td colspan="6" style="text-align: right; padding-right: 20px;">Grand Total:</td>
                <td class="text-right" id="grandTotal">$0.00</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

  <script src="../js/stock.js"></script>
  </body>
</html>