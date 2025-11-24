<?php

require_once __DIR__ . '/../config/db.php';

// Authentication and Authorization checks (similar to user.php)
if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/index.php?page=login');
    exit;
}

// Assuming only admins can manage min prices, adjust ROLE_ADMIN as needed
if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== ROLE_ADMIN) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - Minimum Prices</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/minprice.css" /> <!-- Custom CSS for minprice -->
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
            <h1 class="header-title" id="pageTitle">Minimum Prices</h1>
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
              <a href="/SMS/logout.php" class="dropdown-item logout" style="text-decoration: none; color: inherit;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
              </a>
            </div>
          </div>
        </div>
        <div class="content">
            <div class="page-header">
                <h1><i class="fas fa-dollar-sign"></i> Manage Minimum Prices</h1>
                <p>Set and update minimum selling prices for items</p>
            </div>

            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Items Minimum Price List</h3>
                    </div>
                    <div class="toolbar">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input
                                type="text"
                                placeholder="Search items..."
                                id="searchMinPrice"
                                onkeyup="searchTable('minPriceTable', this.value)"
                            />
                        </div>
                    </div>
                    <div class="table-container">
                        <table id="minPriceTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item Name</th>
                                    <th>Current Price</th>
                                    <th>Minimum Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="minPriceTableBody">
                                <!-- Items with min prices will be populated here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
   <script src="../js/sidebar.js"></script>
   <script src="../js/minprice.js?v=<?php echo filemtime(__DIR__.'/../js/minprice.js'); ?>"></script> <!-- Custom JS for minprice -->
  </body>
</html>
