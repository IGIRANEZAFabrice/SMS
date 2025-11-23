<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Item Progress - Transaction History</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="stylesheet" href="../css/progress.css">
  </head>
  <body>
    <div class="page-header">
      <h1><i class="fas fa-chart-line"></i> Item Progress</h1>
      <p>View detailed transaction history for any item</p>
    </div>

    <div class="container">
      <!-- Item Selection -->
      <div class="selection-card">
        <div class="selection-grid">
          <div class="form-group">
            <label><i class="fas fa-box"></i> Select Item</label>
            <select class="form-control" id="itemSelect" onchange="loadItemProgress()">
              <option value="">Choose an item to view progress...</option>
            </select>
          </div>
          <button class="btn btn-success" onclick="exportProgress()">
            <i class="fas fa-file-excel"></i> Export
          </button>
        </div>
      </div>

      <!-- Content (Hidden until item selected) -->
      <div id="progressContent" style="display: none;">
        <!-- Item Info Card -->
        <div class="item-info-card">
          <div class="item-info-grid">
            <div class="info-item">
              <h3>Item Name</h3>
              <p id="itemName">-</p>
            </div>
            <div class="info-item">
              <h3>Category</h3>
              <p id="itemCategory">-</p>
            </div>
            <div class="info-item">
              <h3>Current Stock</h3>
              <p id="currentStock">-</p>
            </div>
            <div class="info-item">
              <h3>Unit Price</h3>
              <p id="itemPrice">-</p>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon green">
              <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-content">
              <h3 id="totalIn">0</h3>
              <p>Total Stock In</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon red">
              <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-content">
              <h3 id="totalOut">0</h3>
              <p>Total Stock Out</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon blue">
              <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stat-content">
              <h3 id="totalTransactions">0</h3>
              <p>Total Transactions</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon purple">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
              <h3 id="lastTransaction">-</h3>
              <p>Last Transaction</p>
            </div>
          </div>
        </div>

        <!-- Transaction History -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Transaction History</h3>
          </div>

          <div class="filters">
            <div class="filter-group">
              <label>Transaction Type</label>
              <select class="filter-select" id="typeFilter" onchange="filterTransactions()">
                <option value="">All Types</option>
                <option value="in">Stock In</option>
                <option value="out">Stock Out</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Date From</label>
              <input type="date" class="filter-select" id="dateFrom" onchange="filterTransactions()" />
            </div>
            <div class="filter-group">
              <label>Date To</label>
              <input type="date" class="filter-select" id="dateTo" onchange="filterTransactions()" />
            </div>
          </div>

          <div class="timeline" id="timeline">
            <!-- Timeline items will be inserted here -->
          </div>
        </div>
      </div>

      <!-- Empty State (Shown initially) -->
      <div id="emptyState" class="card">
        <div class="empty-state">
          <i class="fas fa-box-open"></i>
          <h3>No Item Selected</h3>
          <p>Please select an item from the dropdown above to view its progress</p>
        </div>
      </div>
    </div>

  <script src="../js/progress.js"></script>   
  </body>
</html>