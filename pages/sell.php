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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Point of Sale</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/sell.css" />
  </head>
  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/sidebar.php'; ?>
      <div class="main-content">
       <?php include __DIR__ . '/header.php'; ?>
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
                        <span class="total-value" id="subtotal">RWF 0.00</span>
                    </div>
                    <input type="number" class="discount-input" id="discountInput" placeholder="Discount amount" min="0" step="0.01">
                    <div class="total-row grand-total">
                        <span>Grand Total:</span>
                        <span id="grandTotal">RWF 0.00</span>
                    </div>
                </div>

                <button class="btn btn-primary" id="checkoutBtn" disabled>Complete Sale</button>
                <button class="btn btn-secondary" id="clearCartBtn">Clear Cart</button>
                <br><br>
            </div>
        </div>
          </div>
        </div>
      </div>
    </div>
    <script src="../js/sidebar.js"></script>
    <script src="../js/sell.js"></script>
    
    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal-overlay" style="display: none;">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Sale Receipt</h2>
          <button id="closeModal" class="close-modal-btn">×</button>
        </div>
        <div class="modal-body" id="receiptDetails">
          <!-- Receipt content will be injected by JS -->
        </div>
        <div class="modal-footer">
          <button id="printReceiptBtn" class="btn btn-primary">
            <i class="fas fa-print"></i> Print
          </button>
        </div>
      </div>
    </div>
  </body>
</html>
