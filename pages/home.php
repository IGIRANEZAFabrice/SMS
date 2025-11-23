<?php require_once __DIR__ . '/../config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Workshop Management System</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="stylesheet" href="../css/sidebar.css" />
  </head>
  <body>
    <div class="dashboard">
      <!-- Mobile Overlay -->
      <div class="sidebar-overlay" id="sidebarOverlay"></div>

      <!-- Sidebar -->
      <div class="sidebar" id="sidebar">
        <div class="logo-container">
          <a href="index.php?resto=home" class="logo">
            <div class="logo-icon">WM</div>
            <span class="logo-text">Workshop Pro</span>
          </a>
        </div>

        <div class="menu">
          <a class="menu-item active" href="index.php?resto=home">
            <div class="menu-item-content">
              <i class="fas fa-home"></i>
              <span class="menu-item-text">Home</span>
            </div>
          </a>

          <a class="menu-item" href="index.php?resto=sell">
            <div class="menu-item-content">
              <i class="fas fa-shopping-cart"></i>
              <span class="menu-item-text">Sell</span>
            </div>
          </a>

          <div class="menu-item" data-dropdown="true">
            <div class="menu-item-content">
              <i class="fas fa-box"></i>
              <span class="menu-item-text">Stock</span>
            </div>
            <i class="fas fa-chevron-right menu-item-arrow"></i>
          </div>
          <div class="submenu" id="stock-submenu">
            <a class="submenu-item" href="index.php?resto=purchase">
              <i class="fas fa-file-import"></i>
              <span>Purchase Request</span>
            </a>
            <div class="submenu" id="stock-submenu" data-page="add-stock">
              <a class="submenu-item" href="index.php?resto=add">
                <i class="fas fa-plus-circle"></i>
                <span>Add Stock</span>
              </a>
            </div>
            <div class="submenu-item" data-page="damaged-goods">
              <a class="submenu-item" href="index.php?resto=damage">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Damaged Goods</span>
              </a>
            </div>
          </div>

          <div class="menu-item" data-dropdown="true">
            <div class="menu-item-content">
              <i class="fas fa-chart-bar"></i>
              <span class="menu-item-text">Reports</span>
            </div>
            <i class="fas fa-chevron-right menu-item-arrow"></i>
          </div>
          <div class="submenu" id="reports-submenu">
            <a class="submenu-item" href="index.php?resto=cogs">
              <i class="fas fa-dollar-sign"></i>
              <span>Cost of Goods Sold</span>
            </a>
            <div class="submenu-item" data-page="damaged-report">
              <i class="fas fa-exclamation-triangle"></i>
              <span>Damaged Goods</span>
            </div>
            <a class="submenu-item" href="index.php?resto=supplier-report">
              <i class="fas fa-users"></i>
              <span>Supplier Report</span>
            </a>
          </div>

          <?php if (isset($_SESSION['role_id']) && (int)$_SESSION['role_id'] === ROLE_ADMIN) { ?>
          <a class="menu-item" href="index.php?resto=user">
            <div class="menu-item-content">
              <i class="fas fa-cog"></i>
              <span class="menu-item-text">System Admin</span>
            </div>
          </a>
          <?php } ?>
        </div>

        <div class="toggle-btn" id="toggleBtn">
          <i class="fas fa-bars"></i>
        </div>
      </div>

      <!-- Main Content -->
      <div class="main-content">
        <div class="header">
          <div class="header-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
              <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title" id="pageTitle">Home</h1>
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
          <div class="content-card">
            <h2>Welcome to Workshop Management System</h2>
            <p>Current page: <strong id="currentPage">Home</strong></p>

            <div class="stats-grid">
              <div class="stat-card blue">
                <h3>Quick Stats</h3>
                <div class="stat-value">125</div>
                <div class="stat-label">Total Items</div>
              </div>
              <div class="stat-card green">
                <h3>Sales Today</h3>
                <div class="stat-value">$1,245</div>
                <div class="stat-label">Revenue</div>
              </div>
              <div class="stat-card orange">
                <h3>Low Stock</h3>
                <div class="stat-value">8</div>
                <div class="stat-label">Items Need Restock</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="../js/sidebar.js"></script>
  </body>
</html>
