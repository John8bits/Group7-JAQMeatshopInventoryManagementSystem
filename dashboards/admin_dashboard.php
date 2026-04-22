<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Authentication/login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
<<<<<<< HEAD
    <link rel="stylesheet" href="admin_dashboard.css">
=======
    <link rel="stylesheet" href="admin_dashboard.css">
>>>>>>> 3b40e406f2ced1c35734451982e958a962b5ff77

</head>
<body>

<div class="sidebar">
    <h2>JAQ Meatshop</h2>
<<<<<<< HEAD

    <div class="sidebox">
        <a href="?page=home" class="<?= ($page=='home')?'active':'' ?>"><i class="fa fa-home"></i> Dashboard</a>
        <a href="?page=inventory" class="<?= ($page=='inventory')?'active':'' ?>"><i class="fa fa-box"></i> Inventory</a>
        <a href="?page=reports" class="<?= ($page=='reports')?'active':'' ?>"><i class="fa fa-chart-line"></i> Reports</a>
        <a href="?page=alerts" class="<?= ($page=='alerts')?'active':'' ?>"><i class="fa fa-exclamation-triangle"></i> Alerts</a>
        <a href="?page=cashier" class="<?= ($page=='cashier')?'active':'' ?>"><i class="fa fa-users"></i> Manage Cashier</a>
        <a href="../Authentication/logout.php"><i class="fa fa-sign-out-alt"></i> Sign Out</a>
=======
    
    <div class="sidebox">
        <!-- <a href="../Admin_sidebar_function/inventory.php"><i class="fa fa-box"></i> Inventory</a>
        <a href="../Admin_sidebar_function/reports.php"><i class="fa fa-chart-line"></i> Reports</a>
        <a href="../Admin_sidebar_function/alert.php"><i class="fa fa-exclamation-triangle"></i> Alerts</a>
        <a href="../Admin_sidebar_function/manage_cashier.php"><i class="fa fa-users"></i> Manage Cashier</a>
        <a href="../Authentication/logout.php"><i class="fa fa-sign-out-alt" id="log"></i> Sign Out</a> -->

        <a href="?page=inventory"><i class="fa fa-box"></i> Inventory</a>
        <a href="?page=reports"><i class="fa fa-chart-line"></i> Reports</a>
        <a href="?page=alerts"><i class="fa fa-exclamation-triangle"></i> Alerts</a>
        <a href="?page=cashier"><i class="fa fa-users"></i> Manage Cashier</a>
        <a href="../Authentication/logout.php"><i class="fa fa-sign-out-alt" id="log"></i> Sign Out</a> 
>>>>>>> 3b40e406f2ced1c35734451982e958a962b5ff77
    </div>
</div>

<div class="main">

<<<<<<< HEAD
<h1>Welcome, <?= htmlspecialchars($_SESSION['username']); ?></h1>

<?php
switch ($page) {

    case 'inventory':
        include '../Admin_sidebar_function/inventory.php';
        break;

    case 'reports':
        include '../Admin_sidebar_function/reports.php';
        break;

    case 'alerts':
        include '../Admin_sidebar_function/alert.php';
        break;

    case 'cashier':
        include '../Admin_sidebar_function/manage_cashier.php';
        break;

    case 'home':
    default:
        echo "
        <div class='grid'>
            <div class='card'>
                <div class='icon'><i class='fa fa-weight-scale'></i></div>
                <h3>Weight Inventory</h3>
                <p>Track meat in kilograms</p>
            </div>

            <div class='card'>
                <div class='icon'><i class='fa fa-chart-bar'></i></div>
                <h3>Sales Report</h3>
                <p>Daily sales & waste</p>
            </div>

            <div class='card'>
                <div class='icon'><i class='fa fa-bell'></i></div>
                <h3>Stock Alerts</h3>
                <p>Low stock & expiry alerts</p>
            </div>
        </div>";
        break;
}
?>
=======
    <h1>Welcome, <?= $_SESSION['username']; ?> </h1>

    <!-- <div class="grid">

        <div class="card">
            <div class="icon"><i class="fa fa-weight-scale"></i></div>
            <h3>Weight Inventory</h3>
            <p>Track meat in kilograms</p>
        </div>

        <div class="card">
            <div class="icon"><i class="fa fa-chart-bar"></i></div>
            <h3>Sales Report</h3>
            <p>Daily sales & waste</p>
        </div>

        <div class="card">
            <div class="icon"><i class="fa fa-bell"></i></div>
            <h3>Stock Alerts</h3>
            <p>Low stock & expiry alerts</p>
        </div>

    </div> -->

    <?php
    $page = $_GET['page'] ?? 'home';

    switch ($page) {
        case 'inventory':
            include '../Admin_sidebar_function/inventory.php';
            break;

        case 'reports':
            include '../Admin_sidebar_function/reports.php';
            break;

        case 'alerts':
            include '../Admin_sidebar_function/alert.php';
            break;

        case 'cashier':
            include '../Admin_sidebar_function/manage_cashier.php';
            break;
        
        default:
        
            echo "
            <div class='grid'>
                <div class='card'>
                    <div class='icon'><i class='fa fa-weight-scale'></i></div>
                    <h3>Weight Inventory</h3>
                    <p>Track meat in kilograms</p>
                </div>

                <div class='card'>
                    <div class='icon'><i class='fa fa-chart-bar'></i></div>
                    <h3>Sales Report</h3>
                    <p>Daily sales & waste</p>
                </div>

                <div class='card'>
                    <div class='icon'><i class='fa fa-bell'></i></div>
                    <h3>Stock Alerts</h3>
                    <p>Low stock & expiry alerts</p>
                </div>
            </div>";
            break;
    }
    ?>
>>>>>>> 3b40e406f2ced1c35734451982e958a962b5ff77

</div>

</body>
</html>