<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/index.php?page=login');
    exit;
}

$view = (isset($_GET['resto']) && $_GET['resto'] !== '') ? $_GET['resto'] : '';

$title = 'Home';
$page = 'sell.php';

switch ($view) {
    case 'home':
        $title = 'Dashboard';
        $page = 'home.php';
        break;
    case 'damage':
        $title = 'Damaged items';
        $page = 'damage.php';
        break;
    case 'sell':
        $title = 'Point of Sale';
        $page = 'sell.php';
        break;
    case 'sellingprice':
        $title = 'selling price';
        $page = 'minprice.php';
        break;

    case 'supplier':
        $title = 'Manage Suppliers';
        $page = 'supplier.php';
        break;

    case 'receipt':
        $title = 'Manage Receipts';
        $page = 'receipt.php';
        break;
    case 'add':
        $title = 'Add New Items In stock';
        $page = 'additem.php';
        break;
    case 'purchase':
        $title = 'Purchase Request';
        $page = 'purchase.php';
        break;
    case 'stock':
        $title = 'Stock';
        $page = 'stock.php';
        break;
    case 'cogs':
        $title = 'Cost of Goods Sold';
        $page = 'costreport.php';
        break;
    case 'supplier-report':
        $title = 'Supplier Report';
        $page = 'supplierreport.php';
        break;
    case 'derivery':
        $title = 'derivery';
        $page = 'derivery.php';
        break;
    case 'user':
        $title = 'Admin Management';
        $page = 'user.php';
        break;
    case 'request':
        $title = 'Purchase Requests';
        $page = 'request.php';
        break;
    default:
        $title = 'Home';
        $page = 'sell.php';
}

include __DIR__ . '/' . $page;
?>
