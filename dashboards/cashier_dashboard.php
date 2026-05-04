<?php
    session_start();


    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../Authentication/login.php");
    exit;
}

$result = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product = $_POST['product'];
    $weight = floatval($_POST['weight']);

    // Prices
    $prices = [
        "Beef" => 350,
        "Pork" => 280,
        "Chicken" => 180
    ];

    if (isset($prices[$product])) {
        $price = $prices[$product];
        $total = $price * $weight;

        $result = "Product: <strong>$product</strong><br>
                   Weight: <strong>$weight kg</strong><br>
                   Price per kg: ₱$price<br>
                   <hr>
                   Total: <strong>₱" . number_format($total, 2) . "</strong>";
    } else {
        $result = "Invalid product selected.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="../css/cashier_dashboard.css">

</head>

<body>

<h1>Welcome <?= htmlspecialchars($_SESSION['username']); ?></h1>

<div class="container">

    <h2>Quick Sale</h2>

    <form method="POST">
        <label>Product</label>
        <select name="product" required>
            <option value="Beef">Beef (₱350/kg)</option>
            <option value="Pork">Pork (₱280/kg)</option>
            <option value="Chicken">Chicken (₱180/kg)</option>
        </select>

        <label>Weight (kg)</label>
        <input type="number" name="weight" step="0.01" min="0" required>

        <button type="submit">Process Sale</button>
    </form>

    <?php if ($result): ?>
        <div class="result"><?= $result; ?></div>
    <?php endif; ?>

    <div class="logout_cashier">
        <button class="logout">
            <i class="fa fa-sign-out-alt"></i>
            <a href="../Authentication/logout.php">Sign out</a>
        </button>
    </div>

</div>

</body>
</html>
