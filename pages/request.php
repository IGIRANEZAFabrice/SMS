<?php
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/index.php?page=login');
    exit;
}
if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== ROLE_ADMIN) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Requests Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/request.css">
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
            <h1 class="header-title" id="pageTitle">Purchase Requests</h1>
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
        <!-- Alert -->
        <div id="alert" class="alert"></div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-clipboard-list"></i>
                Purchase Requests
            </h1>
            <div class="filters">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-border-all"></i> All
                </button>
                <button class="filter-btn" data-filter="pending">
                    <i class="fas fa-clock"></i> Pending
                </button>
                <button class="filter-btn" data-filter="approved">
                    <i class="fas fa-check"></i> Approved
                </button>
                <button class="filter-btn" data-filter="received">
                    <i class="fas fa-box-open"></i> Received
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-label"><i class="fas fa-clock"></i> Pending Requests</div>
                <div class="stat-value" id="pendingCount">0</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-label"><i class="fas fa-check-circle"></i> Approved</div>
                <div class="stat-value" id="approvedCount">0</div>
            </div>
            <div class="stat-card received">
                <div class="stat-label"><i class="fas fa-box-open"></i> Received</div>
                <div class="stat-value" id="receivedCount">0</div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="requests-section">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Request ID</th>
                            <th><i class="fas fa-truck"></i> Supplier</th>
                            <th><i class="fas fa-calendar"></i> Request Date</th>
                            <th><i class="fas fa-user"></i> Created By</th>
                            <th><i class="fas fa-boxes"></i> Items</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                            <th><i class="fas fa-cog"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTableBody">
                        <!-- Requests will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-invoice"></i> Request Details</h3>
                <button class="close-modal" onclick="closeModal('detailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer" id="modalFooter">
                <!-- Action buttons will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content confirm-modal">
            <div class="modal-body">
                <div class="confirm-icon" id="confirmIcon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="confirm-title" id="confirmTitle">Confirm Action</div>
                <div class="confirm-message" id="confirmMessage">Are you sure?</div>
                <div class="confirm-actions">
                    <button class="btn btn-success" id="confirmBtn">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                    <button class="btn btn-secondary" onclick="closeModal('confirmModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar.js"></script>
    <script src="../js/request.js"></script>
</body>
</html>
