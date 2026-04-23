<?php
    session_start();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
        header("Location: ../Authentication/login.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/cashier_dashb_style.css">
</head>

<body>

<h1>Welcome <?=$_SESSION['username'];?></h1>
<div class="container">

    <h2>Quick Sale</h2>

    <form>
        <select>
            <option>Beef</option>
            <option>Pork</option>
            <option>Chicken</option>
        </select>

        <input type="number" class="weight" placeholder="Weight (kg)">

        <button type="submit">Process Sale</button>
    </form>

    <div class="logout_cashier">
        <!-- <img src="logout.png"> -->
        <button class="logout"><i class="fa fa-sign-out-alt"></i> <a href="../Authentication/logout.php">Sign out</a></button>
    </div>

</div>

</body>
</html>