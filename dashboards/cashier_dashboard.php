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

<style>

    body {
        font-family: Arial;
        background: #f4f6f9;
        margin: 0;
    }

    .container {
        max-width: 500px;
        margin: 50px auto;
        background: white;
        padding: 20px;
        border-radius: 10px;
    }

    input, select, button {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
    }

    button {
        background: #D53E0F;
        color: white;
        border: none;
        cursor: pointer;
    }

    .logout_cashier{
        position:absolute;
        top:20px;
        right:40px;
    }

    .logout{
        font-size: 18px;
    }
    
    /* .logout_cashier img{
        width:30px;
        position:absolute;
    } */

    .logout i{
        padding-right:5px;
    }

    a{
        text-decoration: none;
        color:white;
    }

    .logout:hover{
        background:darkred;
    }

    .weight{
        width:95%;
    }

</style>

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