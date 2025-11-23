<?php
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/sell.css">
  </head>
  <body>
    <div class="dashboard">
      <div class="sidebar-overlay" id="sidebarOverlay"></div>
      <div class="sidebar" id="sidebar">
        <div class="logo-container">
          <a href="index.php?resto=home" class="logo">
            <div class="logo-icon">WM</div>
            <span class="logo-text">Workshop Pro</span>
          </a>
        </div>
        <div class="menu">
          <a class="menu-item" href="index.php?resto=home">
            <div class="menu-item-content">
              <i class="fas fa-home"></i>
              <span class="menu-item-text">Home</span>
            </div>
          </a>
          <a class="menu-item active" href="index.php?resto=sell">
            <div class="menu-item-content">
              <i class="fas fa-shopping-cart"></i>
              <span class="menu-item-text">Sell</span>
            </div>
          </a>
          <div class="menu-item" data-dropdown="true" data-page="stock">
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
            <div class="submenu-item" data-page="add-stock">
              <i class="fas fa-plus-circle"></i>
              <span>Add Stock</span>
            </div>
            <div class="submenu-item" data-page="damaged-goods">
              <i class="fas fa-exclamation-triangle"></i>
              <span>Damaged Goods</span>
            </div>
          </div>
          <div class="menu-item" data-dropdown="true" data-page="reports">
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
      <div class="main-content">
        <div class="header">
          <div class="header-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
              <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title" id="pageTitle">Sell</h1>
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
    <div class="container">
        <!-- Alert Messages -->
        <div id="alert" class="alert"></div>

        <!-- Main POS Grid -->
        <div class="pos-grid">
            <!-- Left: Items Section -->
            <div>
                <!-- Search Section -->
                <div class="search-section">
                    <h2 style="margin-bottom: 15px; color: var(--blue-dark);">Select Items</h2>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search items...">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                    </div>
                </div>

                <!-- Items Grid -->
                <div class="items-section">
                    <div class="items-grid" id="itemsGrid">
                        <!-- Items will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Right: Cart Section -->
            <div class="cart-section">
                <div class="cart-header">
                    <h2>Cart</h2>
                    <span class="cart-count" id="cartCount">0</span>
                </div>

                <div class="cart-items" id="cartItems">
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart" style="font-size: 80px; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p>Cart is empty</p>
                    </div>
                </div>

                <div class="totals-section">
                    <div class="total-row">
                        <span class="total-label">Subtotal:</span>
                        <span class="total-value" id="subtotal">$0.00</span>
                    </div>
                    <input type="number" class="discount-input" id="discountInput" placeholder="Discount amount" min="0" step="0.01">
                    <div class="total-row grand-total">
                        <span>Grand Total:</span>
                        <span id="grandTotal">$0.00</span>
                    </div>
                </div>

                <button class="btn btn-primary" id="checkoutBtn" disabled>Complete Sale</button>
                <button class="btn btn-secondary" id="clearCartBtn">Clear Cart</button>
            </div>
        </div>
    </div>
        </div>
      </div>
    </div>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sell.js"></script>
</body>
</html>
