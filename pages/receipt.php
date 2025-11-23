<?php
require_once __DIR__ . '/../config/db.php';

$is_api = isset($_GET['api']) ? (string)$_GET['api'] : '';
if ($is_api !== '') {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    header('Content-Type: application/json');
    
    if ($is_api === 'receipts') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $sql = "SELECT r.receipt_id, r.receipt_code, r.total_amount, r.discount, r.grand_total, 
                           r.created_at, u.fullname as cashier_name
                    FROM receipts r
                    JOIN users u ON r.created_by = u.user_id
                    ORDER BY r.created_at DESC";
            
            $res = $conn->query($sql);
            $out = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out[] = [
                        'receipt_id' => (int)$row['receipt_id'],
                        'receipt_code' => $row['receipt_code'],
                        'total_amount' => (float)$row['total_amount'],
                        'discount' => (float)$row['discount'],
                        'grand_total' => (float)$row['grand_total'],
                        'created_at' => $row['created_at'],
                        'cashier_name' => $row['cashier_name']
                    ];
                }
            }
            echo json_encode(['success' => true, 'data' => $out]);
            exit;
        }
    } elseif ($is_api === 'receipt_items') {
        if (isset($_GET['receipt_code'])) {
            $receipt_code = $conn->real_escape_string($_GET['receipt_code']);
            
            $sql = "SELECT ri.*, i.item_name, i.price as current_price 
                    FROM receipt_items ri
                    JOIN tbl_items i ON ri.item_id = i.item_id
                    WHERE ri.receipt_code = '$receipt_code'";
                    
            $res = $conn->query($sql);
            $items = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $items[] = [
                        'item_name' => $row['item_name'],
                        'qty' => (float)$row['qty'],
                        'price' => (float)$row['price'],
                        'total' => (float)$row['total'],
                        'current_price' => (float)$row['current_price']
                    ];
                }
            }
            
            // Get receipt details
            $sql = "SELECT r.*, u.fullname as cashier_name
                    FROM receipts r
                    JOIN users u ON r.created_by = u.user_id
                    WHERE r.receipt_code = '$receipt_code'";
            $receipt = $conn->query($sql)->fetch_assoc();
            
            echo json_encode([
                'success' => true, 
                'receipt' => [
                    'receipt_code' => $receipt['receipt_code'],
                    'date' => $receipt['created_at'],
                    'cashier' => $receipt['cashier_name'],
                    'total_amount' => (float)$receipt['total_amount'],
                    'discount' => (float)$receipt['discount'],
                    'grand_total' => (float)$receipt['grand_total']
                ],
                'items' => $items
            ]);
            exit;
        }
    }
    
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not found']);
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
    <title>Receipts - Sales Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/receipt.css" />
  </head>
  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/sidebar.php'; ?>
      <div class="main-content">
        <?php include __DIR__ . '/header.php'; ?>
        <div class="content">
          <div class="page-header">
            <h1><i class="fas fa-receipt"></i> Sales Receipts</h1>
            <p>View and manage all sales transactions</p>
          </div>

          <div class="container">
            <!-- Statistics -->
            <div class="stats-grid">
              <div class="stat-card">
                <div class="stat-icon blue">
                  <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-content">
                  <h3 id="totalReceipts">0</h3>
                  <p>Total Receipts</p>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon green">
                  <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                  <h3 id="totalSales">$0.00</h3>
                  <p>Total Sales</p>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon purple">
                  <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                  <h3 id="avgSale">$0.00</h3>
                  <p>Average Sale</p>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon orange">
                  <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                  <h3 id="todaySales">$0.00</h3>
                  <p>Today's Sales</p>
                </div>
              </div>
            </div>

            <!-- Receipts List -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">All Receipts</h3>
                <button class="btn btn-success" onclick="exportReceipts()">
                  <i class="fas fa-file-excel"></i> Export
                </button>
              </div>

              <div class="toolbar">
                <div class="search-box">
                  <i class="fas fa-search"></i>
                  <input
                    type="text"
                    placeholder="Search receipts..."
                    id="searchReceipts"
                    onkeyup="searchTable(this.value)"
                  />
                </div>
              </div>

              <div class="table-container">
                <table id="receiptsTable">
                  <thead>
                    <tr>
                      <th>Receipt Code</th>
                      <th>Date</th>
                      <th>Items</th>
                      <th class="text-right">Total Amount</th>
                      <th class="text-right">Discount</th>
                      <th class="text-right">Grand Total</th>
                      <th>Created By</th>
                    </tr>
                  </thead>
                  <tbody id="receiptsTableBody">
                    <!-- Receipts will be populated here -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Receipt Detail Modal -->
    <div class="modal" id="receiptModal">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <div class="modal-title" id="modalReceiptCode">Receipt Details</div>
          </div>
          <button class="close-btn" onclick="closeModal()">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <!-- Receipt Info -->
          <div class="receipt-info">
            <div class="info-item">
              <span class="info-label">Receipt Code</span>
              <span class="info-value" id="detailReceiptCode">-</span>
            </div>
            <div class="info-item">
              <span class="info-label">Date & Time</span>
              <span class="info-value" id="detailDate">-</span>
            </div>
            <div class="info-item">
              <span class="info-label">Created By</span>
              <span class="info-value" id="detailCreatedBy">-</span>
            </div>
            <div class="info-item">
              <span class="info-label">Total Items</span>
              <span class="info-value" id="detailItemCount">-</span>
            </div>
          </div>

          <!-- Items Table -->
          <div class="items-table">
            <h3 style="margin-bottom: 15px; color: var(--blue-dark);">Items</h3>
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Item Name</th>
                  <th>Unit</th>
                  <th class="text-right">Quantity</th>
                  <th class="text-right">Unit Price</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody id="detailItemsBody">
                <!-- Items will be populated here -->
              </tbody>
            </table>
          </div>

          <!-- Summary -->
          <div class="receipt-summary">
            <div class="summary-row">
              <span>Subtotal:</span>
              <strong id="detailSubtotal">$0.00</strong>
            </div>
            <div class="summary-row">
              <span>Discount:</span>
              <strong id="detailDiscount">$0.00</strong>
            </div>
            <div class="summary-row total">
              <span>Grand Total:</span>
              <strong id="detailGrandTotal">$0.00</strong>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-print" onclick="printReceipt()">
            <i class="fas fa-print"></i> Print
          </button>
          <button class="btn btn-success" onclick="closeModal()">
            <i class="fas fa-check"></i> Close
          </button>
        </div>
      </div>
    </div>
      <script src="../js/sidebar.js"></script>
      <script src="../js/receipt.js"></script>
    
  </body>
</html>