<?php require_once __DIR__ . '/../config/db.php'; ?>
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
    <a class="menu-item" href="index.php?resto=sell">
      <div class="menu-item-content">
        <i class="fas fa-shopping-cart"></i>
        <span class="menu-item-text">Sell</span>
      </div>
    </a>
    <?php if (isset($_SESSION['role_id']) && (int)$_SESSION['role_id'] === ROLE_ADMIN) { ?>
    <a class="menu-item" href="index.php?resto=sellingprice">
      <div class="menu-item-content">
        <i class="fas fa-tags"></i>
        <span class="menu-item-text">Selling Price</span>
      </div>
    </a>
     <?php } ?>
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
      <?php if (isset($_SESSION['role_id']) && (int)$_SESSION['role_id'] === ROLE_ADMIN) { ?>
      <a class="submenu-item" href="index.php?resto=delivery">
        <i class="fas fa-file-import"></i>
        <span>Receive New Stock</span>
      </a>
      <?php } ?>
      <a class="submenu-item" href="index.php?resto=add">
        <i class="fas fa-plus-circle"></i>
        <span>Add Stock</span>
      </a>
      <a class="submenu-item" href="index.php?resto=supplier">
        <i class="fas fa-user"></i>
        <span>Manage Suppliers</span>
      </a>
      <a class="submenu-item" href="index.php?resto=damage">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Damaged Goods</span>
      </a>
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
      <a class="submenu-item" href="index.php?resto=supplier-report">
        <i class="fas fa-users"></i>
        <span>Supplier Report</span>
      </a>
      <a class="submenu-item" href="index.php?resto=receipt">
        <i class="fas fa-receipt"></i>
        <span>Sales Receipts</span>
      </a>
    </div>
    <?php if (isset($_SESSION['role_id']) && (int)$_SESSION['role_id'] === ROLE_ADMIN) { ?>
    <a class="menu-item" href="index.php?resto=user">
      <div class="menu-item-content">
        <i class="fas fa-cog"></i>
        <span class="menu-item-text"> Users</span>
      </div>
    </a>
    <?php } ?>
    
  </div>
  <div class="toggle-btn" id="toggleBtn">
    <i class="fas fa-bars"></i>
  </div>
</div>
