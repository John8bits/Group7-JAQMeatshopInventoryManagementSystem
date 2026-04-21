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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

    body {
        margin: 0;
        font-family: Arial;
        background: #f4f6f9;
    }

    .sidebar {
        width: 220px;
        height: 100vh;
        background: #091413;
        color: white;
        position: fixed;
        padding: 20px;
    }

    .sidebar h2 {
        color: #D53E0F;
        margin-left:22px;
        /* font-size: 30px; */
    }

    .sidebar a {
        display: block;
        color: #ccc;
        text-decoration: none;
        margin: 15px 0;
        font-size:20px;
    }

    .sidebox{
        margin:40px 0;
    }

    .sidebar a:hover {
        color: white;
    }

    .main {
        margin-left: 240px;
        padding: 20px;
    }

    .card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        /* box-shadow: 0 2px 5px rgba(0,0,0,0.1); */
        box-shadow:2px 3px 2px red;
    }

    .card:hover{
        scale: 0.99;
        /* transform: translate(-2px); */
        background: #eee;
        transition: 0.2s;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin:0 4px 0 20px;
    }

    .icon {
        font-size: 30px;
        color: #D53E0F;
    }

    h1{
        margin-left:20px;
    }

</style>

</head>
<body>

<div class="sidebar">
    <h2>JAQ Meatshop</h2>
    
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
    </div>
</div>

<div class="main">

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

</div>

</body>
</html>