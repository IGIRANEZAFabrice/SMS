<?php
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/auth/login.php');
    exit;
}

$stats = [];

// Total Users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $result->fetch_assoc()['total'] ?? 0;

// Total Items
$result = $conn->query("SELECT COUNT(*) as total FROM tbl_items");
$stats['items'] = $result->fetch_assoc()['total'] ?? 0;

// Total Suppliers
$result = $conn->query("SELECT COUNT(*) as total FROM suppliers");
$stats['suppliers'] = $result->fetch_assoc()['total'] ?? 0;

// Total Stock Quantity
$result = $conn->query("SELECT SUM(qty) as total FROM tbl_item_stock");
$stats['stock'] = $result->fetch_assoc()['total'] ?? 0;

// Pending Requests
$result = $conn->query("SELECT COUNT(*) as total FROM purchase_request WHERE status = 'pending'");
$stats['pending_requests'] = $result->fetch_assoc()['total'] ?? 0;

// Damaged Items
$result = $conn->query("SELECT SUM(qty) as total FROM damaged");
$stats['damaged'] = $result->fetch_assoc()['total'] ?? 0;

// Total Revenue
$result = $conn->query("SELECT SUM(grand_total) as total FROM receipts");
$stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Low Stock Items
$low_stock_threshold = 10;
$result = $conn->query("SELECT COUNT(*) as total FROM tbl_item_stock WHERE qty <= $low_stock_threshold");
$stats['low_stock'] = $result->fetch_assoc()['total'] ?? 0;


// Chart Data (Sales for the last 7 days)
$sales_data = [];
$sales_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sales_labels[] = date('D, M j', strtotime("-$i days"));
    $query = "SELECT SUM(grand_total) as daily_total FROM receipts WHERE DATE(created_at) = '$date'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $sales_data[] = $row['daily_total'] ?? 0;
}

// Top Selling Products
$top_products = [];
$result = $conn->query("SELECT i.item_name, SUM(ri.qty) as total_sold, SUM(ri.total) as revenue 
                        FROM receipt_items ri 
                        JOIN tbl_items i ON ri.item_id = i.item_id 
                        GROUP BY i.item_id 
                        ORDER BY total_sold DESC 
                        LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $top_products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Stock Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/color.css" />
    <link rel="stylesheet" href="../css/home.css" />
</head>

<body>
    <div class="dashboard">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/header.php'; ?>
            <div class="content">
                <div class="page-header">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    <p>Welcome back! Here's a summary of your stock management system.</p>
                </div>

                <div class="stat-cards">
                    <div class="stat-card blue revenue-card">
                        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="stat-card-info">
                            <h3>Total Revenue</h3>
                            <div class="value">FRW<?php echo number_format($stats['revenue']); ?></div>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="icon"><i class="fas fa-boxes-stacked"></i></div>
                        <div class="stat-card-info">
                            <h3>Total Stock</h3>
                            <div class="value"><?php echo number_format($stats['stock']); ?></div>
                        </div>
                    </div>
                    <div class="stat-card orange">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <div class="stat-card-info">
                            <h3>Users</h3>
                            <div class="value"><?php echo $stats['users']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card red">
                        <div class="icon"><i class="fas fa-truck"></i></div>
                        <div class="stat-card-info">
                            <h3>Suppliers</h3>
                            <div class="value"><?php echo $stats['suppliers']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card blue">
                        <div class="icon"><i class="fas fa-box-open"></i></div>
                        <div class="stat-card-info">
                            <h3>Products</h3>
                            <div class="value"><?php echo $stats['items']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-card-info">
                            <h3>Pending Requests</h3>
                            <div class="value"><?php echo $stats['pending_requests']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card orange">
                        <div class="icon"><i class="fas fa-sort-amount-down"></i></div>
                        <div class="stat-card-info">
                            <h3>Low Stock</h3>
                            <div class="value"><?php echo $stats['low_stock']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card red">
                        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-card-info">
                             <h3>Damaged Items</h3>
                            <div class="value"><?php echo number_format($stats['damaged']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="left-column">
                        <div class="chart-container">
                            <h2>Daily Sales (Last 7 Days)</h2>
                            <div class="chart-wrapper">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="right-column">
                        <div class="top-products">
                            <h2><i class="fas fa-fire"></i> Top Selling Products</h2>
                            <?php if (!empty($top_products)) : ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Units Sold</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product) : ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['item_name']); ?></td>
                                                <td><?php echo $product['total_sold']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <p>No product data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (mobileMenuBtn && sidebar && sidebarOverlay) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.add('active');
                    sidebarOverlay.classList.add('active');
                });

                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                });
            }

            // Sales Chart
            const salesCtx = document.getElementById('salesChart')?.getContext('2d');
            if (salesCtx) {
                const salesChart = new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($sales_labels); ?>,
                        datasets: [{
                            label: 'Daily Revenue',
                            data: <?php echo json_encode($sales_data); ?>,
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                            pointBorderColor: '#fff',
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgba(59, 130, 246, 1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'RWF' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    callback: function(value, index, values) {
                                        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'RWF', notation: 'compact' }).format(value);
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    <script src="../js/sidebar.js"></script>
</body>

</html>
