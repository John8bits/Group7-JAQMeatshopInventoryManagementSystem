<?php

// use Dom\Document;
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
    <link rel="stylesheet" href="../css/admin_dashb_style.css">
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

        <a href="?page=dashboard"><i class="fa fa-home" ></i> Dashboards</a>
        <a href="?page=inventory"><i class="fa fa-box" ></i> Inventory</a>
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
            include "../Admin_sidebar_function/dashboards.php";
            break;
    }
    ?>

</div>

</body>
</html>