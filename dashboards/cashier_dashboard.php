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
    <link rel="stylesheet" href="cashier_dashboard.css">

</head>

<body>

<<<<<<< HEAD
<h1>Welcome <?= htmlspecialchars($_SESSION['username']); ?></h1>

=======
<h1>Welcome <?=$_SESSION['username'];?></h1>
>>>>>>> 3b40e406f2ced1c35734451982e958a962b5ff77
<div class="container">

    <h2>Quick Sale</h2>

<<<<<<< HEAD
    <form method="POST">
        <label>Product</label>
        <select name="product" required>
            <option value="Beef">Beef (₱350/kg)</option>
            <option value="Pork">Pork (₱280/kg)</option>
            <option value="Chicken">Chicken (₱180/kg)</option>
        </select>

        <label>Weight (kg)</label>
        <input type="number" name="weight" step="0.01" min="0" required>
=======
    <form>
        <select>
            <option>Beef</option>
            <option>Pork</option>
            <option>Chicken</option>
        </select>

        <input type="number" class="weight" placeholder="Weight (kg)">
>>>>>>> 3b40e406f2ced1c35734451982e958a962b5ff77

        <button type="submit">Process Sale</button>
    </form>

<<<<<<< HEAD
    <?php if ($result): ?>
        <div class="result"><?= $result; ?></div>
    <?php endif; ?>

    <div class="logout_cashier">
        <button class="logout">
            <i class="fa fa-sign-out-alt"></i>
            <a href="../Authentication/logout.php">Sign out</a>
        </button>
=======
    <div class="logout_cashier">
        <!-- <img src="logout.png"> -->
        <button class="logout"><i class="fa fa-sign-out-alt"></i> <a href="../Authentication/logout.php">Sign out</a></button>
>>>>>>> 3b40e406f2ced1c35734451982e958a962b5ff77
    </div>

</div>

</body>
</html>