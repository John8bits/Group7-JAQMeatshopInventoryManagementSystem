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
    'supplier'  => ['title' => 'Supplier',         'sub' => 'Supplier contacts & sourcing details'],
    'reports'   => ['title' => 'Sales Report',     'sub' => 'Daily, weekly, monthly & yearly sales analytics'],
    'alerts'    => ['title' => 'Alerts',           'sub' => 'Low stock & expiry notifications'],
    'cashier'   => ['title' => 'Manage Cashier',   'sub' => 'Cashier accounts & access'],
];
$current = $pages[$page] ?? $pages['home'];

require_once '../DatabaseConnection/database.php';
$db = new Database();
$conn = $db->conn;

$threshold = 5;
$countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM product WHERE Status = 'Available' AND StockWeight < ?");
$countStmt->execute([$threshold]);
$alertCount = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

$inventoryStmt = $conn->prepare("SELECT COALESCE(SUM(StockWeight), 0) AS total_stock FROM product WHERE Status = 'Available'");
$inventoryStmt->execute();
$totalStock = (float)($inventoryStmt->fetch(PDO::FETCH_ASSOC)['total_stock'] ?? 0);

$productStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM product WHERE Status = 'Available'");
$productStmt->execute();
$productCount = (int)($productStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

$salesRanges = [
  'today' => [
    'label' => 'Today',
    'description' => "today's transactions",
    'sql' => "SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE DATE(DateTime) = CURDATE()",
  ],
  'weekly' => [
    'label' => 'Weekly',
    'description' => 'this week transactions',
    'sql' => "SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE YEARWEEK(DateTime, 1) = YEARWEEK(CURDATE(), 1)",
  ],
  'monthly' => [
    'label' => 'Monthly',
    'description' => 'this month transactions',
    'sql' => "SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE YEAR(DateTime) = YEAR(CURDATE()) AND MONTH(DateTime) = MONTH(CURDATE())",
  ],
  'yearly' => [
    'label' => 'Yearly',
    'description' => 'this year transactions',
    'sql' => "SELECT COALESCE(SUM(TotalPrice), 0) AS total FROM transactions WHERE YEAR(DateTime) = YEAR(CURDATE())",
  ],
];

$salesRange = $_GET['sales_range'] ?? 'today';
if (!isset($salesRanges[$salesRange])) {
  $salesRange = 'today';
}

$salesStmt = $conn->prepare($salesRanges[$salesRange]['sql']);
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
<style>
.stat-select {
  width: 100%;
  padding: 7px 28px 7px 9px;
  border-radius: 8px;
  border: 1.5px solid var(--border);
  background: #fff;
  color: var(--ink);
  font-family: sans-serif;
  font-size: 0.74rem;
  cursor: pointer;
}

.stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 0.35rem;
}

.range-control {
  width: 104px;
}
</style>
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
  </div>
 
  <div class="sidebar-footer">
    <div class="admin-pill">
      <div class="admin-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
      <div class="admin-info">
        <div class="admin-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="admin-role">Super Admin</div>
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
        <div class="stat-card sales">
          <div class="stat-icon">📊</div>
          <div class="stat-top">
            <div class="stat-label">Sales</div>
            <div class="range-control">
              <select id="salesRange" class="stat-select" onchange="onSalesRangeChange()">
                <?php foreach ($salesRanges as $key => $range): ?>
                  <option value="<?= htmlspecialchars($key) ?>" <?= $salesRange === $key ? 'selected' : '' ?>>
                    <?= htmlspecialchars($range['label']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="stat-value">₱<?= number_format($todaySales, 2) ?></div>
          <div class="stat-sub">Based on <?= htmlspecialchars($salesRanges[$salesRange]['description']) ?></div>
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
        <a href="?page=reports&period=<?= htmlspecialchars($salesRange) ?>" class="quick-card">
          <div class="quick-icon">📈</div>
          <div>
            <div class="quick-title">Sales Report</div>
            <div class="quick-desc">Daily, weekly, monthly & yearly sales</div>
          </div>
        </a>
        <a href="?page=supplier" class="quick-card">
          <div class="quick-icon"><i class="ti ti-truck-delivery"></i></div>
          <div>
            <div class="quick-title">Supplier</div>
            <div class="quick-desc">Manage supplier contact details</div>
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
        }
        ?>
      </div>
    <?php endif; ?>
  </div>
</div>
 
<script>
function onSalesRangeChange(){
  const v = document.getElementById('salesRange').value;
  const params = new URLSearchParams(window.location.search);
  params.set('sales_range', v);
  params.set('page', 'home');
  window.location.search = params.toString();
}
</script>
</body>
</html>
