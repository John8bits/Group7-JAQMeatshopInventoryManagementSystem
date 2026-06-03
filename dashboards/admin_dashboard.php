<?php
session_start();
 
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
 
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$pages = [
    'home'      => ['title' => 'Dashboard',       'sub' => 'Overview of your meatshop operations'],
    'inventory' => ['title' => 'Inventory',        'sub' => 'Track and manage meat stock'],
    'supplier'  => ['title' => 'Supplier',         'sub' => 'Supplier records & contact details'],
    'reports'   => ['title' => 'Sales Report',     'sub' => 'Daily sales & waste analytics'],
    'alerts'    => ['title' => 'Alerts',           'sub' => 'Low stock & expiry notifications'],
    'cashier'   => ['title' => 'Manage Cashier',   'sub' => 'Cashier accounts & access'],
    'database_backup' => ['title' => 'Database Backup', 'sub' => 'Save, recover, and manage database backups'],
];
$current = $pages[$page] ?? $pages['home'];

require_once '../DatabaseConnection/database.php';
$db = new Database();
$conn = $db->conn;

$deletedColumnStmt = $conn->prepare("SHOW COLUMNS FROM product LIKE 'DeletedAt'");
$deletedColumnStmt->execute();
if (!$deletedColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE product ADD DeletedAt DATETIME NULL");
}

$threshold = 5;
$countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM product WHERE Status = 'Available' AND DeletedAt IS NULL AND StockWeight < ?");
$countStmt->execute([$threshold]);
$alertCount = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

$inventoryStmt = $conn->prepare("SELECT COALESCE(SUM(StockWeight), 0) AS total_stock FROM product WHERE Status = 'Available' AND DeletedAt IS NULL");
$inventoryStmt->execute();
$totalStock = (float)($inventoryStmt->fetch(PDO::FETCH_ASSOC)['total_stock'] ?? 0);

$productStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM product WHERE Status = 'Available' AND DeletedAt IS NULL");
$productStmt->execute();
$productCount = (int)($productStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

$transactionStmt = $conn->prepare("SELECT COUNT(*) AS total_tx, COALESCE(SUM(WeightSold), 0) AS total_weight_sold FROM transactions WHERE DATE(DateTime) = CURDATE()");
$transactionStmt->execute();
$transactionData = $transactionStmt->fetch(PDO::FETCH_ASSOC);
$todayTransactions = (int)($transactionData['total_tx'] ?? 0);
$todayWeightSold = (float)($transactionData['total_weight_sold'] ?? 0);

$salesStmt = $conn->prepare("SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE DATE(DateTime) = CURDATE()");
$salesStmt->execute();
$todaySales = (float)($salesStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
// allow dashboard sales range selection
$salesRange = isset($_GET['sales_range']) ? $_GET['sales_range'] : 'today';
switch ($salesRange) {
  case 'weekly':
    $salesStmt = $conn->prepare("SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE DateTime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    break;
  case 'monthly':
    $salesStmt = $conn->prepare("SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE YEAR(DateTime) = YEAR(CURDATE()) AND MONTH(DateTime) = MONTH(CURDATE())");
    break;
  case 'yearly':
    $salesStmt = $conn->prepare("SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE YEAR(DateTime) = YEAR(CURDATE())");
    break;
  case 'today':
  default:
    $salesStmt = $conn->prepare("SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE DATE(DateTime) = CURDATE()");
    break;
}
$salesStmt->execute();
$todaySales = (float)($salesStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — JAQ Meatshop</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
 
<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name">JAQ <span>Meatshop</span></div>
    <div class="brand-sub">Admin Control Panel</div>
  </div>
 
  <div class="sidebar-nav">
    <div class="nav-label">Main</div>
    <a href="?page=home" class="nav-link <?= $page==='home' ? 'active' : '' ?>">
      <i class="ti ti-layout-dashboard"></i> Dashboard
    </a>
    <a href="?page=inventory" class="nav-link <?= $page==='inventory' ? 'active' : '' ?>">
      <i class="ti ti-building-warehouse"></i> Inventory
    </a>
    <a href="?page=supplier" class="nav-link <?= $page==='supplier' ? 'active' : '' ?>">
      <i class="ti ti-truck-delivery"></i> Supplier
    </a>
    <a href="?page=reports" class="nav-link <?= $page==='reports' ? 'active' : '' ?>">
      <i class="ti ti-chart-bar"></i> Reports
    </a>
 
    <div class="nav-label">System</div>
    <a href="?page=alerts" class="nav-link <?= $page==='alerts' ? 'active' : '' ?>">
      <i class="ti ti-bell"></i> Alerts
      <span class="badge"><?= $alertCount ?></span>
    </a>
    <a href="?page=cashier" class="nav-link <?= $page==='cashier' ? 'active' : '' ?>">
      <i class="ti ti-users"></i> Manage Cashier
    </a>
    <a href="?page=database_backup" class="nav-link <?= $page==='database_backup' ? 'active' : '' ?>">
      <i class="ti ti-database-export"></i> Database Backup
    </a>
  </div>
 
  <div class="sidebar-footer">
    <div class="admin-pill">
      <div class="admin-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
      <div class="admin-info">
        <div class="admin-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="admin-role">Admin</div>
      </div>
    </div>
    <a href="../Authentication/logout.php" class="btn-signout">
      <i class="ti ti-logout"></i> Sign Out
    </a>
  </div>
</div>
 
<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div>
      <div class="page-title"><?= htmlspecialchars($current['title']) ?></div>
      <div class="page-sub"><?= htmlspecialchars($current['sub']) ?></div>
    </div>
    <div class="date-badge"><?= date('D, M j, Y') ?></div>
  </div>
 
  <div class="content">
    <?php if ($page === 'home'): ?>
 
      <div class="section-label">At a Glance</div>
      <div class="stat-grid">
        <div class="stat-card beef">
          <div class="stat-icon">🥩</div>
          <div class="stat-label">Weight Inventory</div>
          <div class="stat-value"><?= number_format($totalStock, 2) ?> kg</div>
          <div class="stat-sub">Across <?= $productCount ?> product<?= $productCount === 1 ? '' : 's' ?></div>
        </div>
        <div class="stat-card transactions">
          <div class="stat-icon">🧾</div>
          <div class="stat-label">Transactions Today</div>
          <div class="stat-value"><?= number_format($todayTransactions) ?></div>
          <div class="stat-sub">Completed sales records</div>
        </div>
        <div class="stat-card weight">
          <div class="stat-icon">⚖️</div>
          <div class="stat-label">Weight Sold</div>
          <div class="stat-value"><?= number_format($todayWeightSold, 2) ?> kg</div>
          <div class="stat-sub">Total sold today</div>
        </div>
        <div class="stat-card summary">
          <div class="stat-icon">📊</div>
          <div class="stat-label">Today's Sales</div>
          <div class="stat-value">₱<?= number_format($todaySales, 2) ?></div>
          <div class="stat-sub">Revenue for today</div>
        </div>
        <div class="stat-card alert">
          <div class="stat-icon">🔔</div>
          <div class="stat-label">Active Alerts</div>
          <div class="stat-value"><?= $alertCount ?></div>
          <div class="stat-sub">Products below <?= $threshold ?> kg</div>
        </div>
      </div>
 
      <div class="section-label">Quick Access</div>
      <div class="quick-grid">
        <a href="?page=inventory" class="quick-card">
          <div class="quick-icon">🗃️</div>
          <div>
            <div class="quick-title">Inventory</div>
            <div class="quick-desc">Track meat stock in kg</div>
          </div>
        </a>
        <a href="?page=reports" class="quick-card">
          <div class="quick-icon">📈</div>
          <div>
            <div class="quick-title">Sales Report</div>
            <div class="quick-desc">Daily sales & waste logs</div>
          </div>
        </a>
        <a href="?page=supplier" class="quick-card">
          <div class="quick-icon"><i class="ti ti-truck-delivery"></i></div>
          <div>
            <div class="quick-title">Supplier</div>
            <div class="quick-desc">Manage supplier records</div>
          </div>
        </a>
        <a href="?page=alerts" class="quick-card">
          <div class="quick-icon">⚠️</div>
          <div>
            <div class="quick-title">Stock Alerts</div>
            <div class="quick-desc">Low stock & expiry notices</div>
          </div>
        </a>
        <a href="?page=cashier" class="quick-card">
          <div class="quick-icon">👥</div>
          <div>
            <div class="quick-title">Manage Cashier</div>
            <div class="quick-desc">Add or edit cashier accounts</div>
          </div>
        </a>
        <a href="?page=database_backup" class="quick-card">
          <div class="quick-icon"><i class="ti ti-database-export"></i></div>
          <div>
            <div class="quick-title">Database Backup</div>
            <div class="quick-desc">Save or recover database files</div>
          </div>
        </a>
      </div>
 
    <?php else: ?>
      <div class="page-body">
        <?php
        switch ($page) {
            case 'inventory': include '../Admin_sidebar_function/inventory.php'; break;
            case 'supplier':  include '../Admin_sidebar_function/supplier.php';  break;
            case 'reports':   include '../Admin_sidebar_function/reports.php';   break;
            case 'alerts':    include '../Admin_sidebar_function/alert.php';      break;
            case 'cashier':   include '../Admin_sidebar_function/manage_cashier.php'; break;
            case 'database_backup': include '../Admin_sidebar_function/database_backup.php'; break;
        }
        ?>
      </div>
    <?php endif; ?>
  </div>
</div>
 
</body>
</html>
