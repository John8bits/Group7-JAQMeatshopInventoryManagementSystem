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
    <link rel="stylesheet" href="cashier_dashboard.css">
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #1a1a1a;
    color: #f0f0f0;
    display: flex;
    min-height: 100vh;
}


.sidebar {
    width: 200px;
    background: #111;
    border-right: 1px solid #2a2a2a;
    display: flex;
    flex-direction: column;
    padding: 20px 0;
    position: fixed;
    height: 100vh;
}

.sidebar h2 {
    color: #e24b4a;
    font-size: 16px;
    font-weight: 500;
    padding: 0 20px 20px;
    border-bottom: 1px solid #2a2a2a;
}

.sidebox {
    display: flex;
    flex-direction: column;
    padding: 12px 0;
}

.sidebox a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    color: #aaa;
    font-size: 13px;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
}

.sidebox a:hover,
.sidebox a.active {
    background: #2a1010;
    color: #e24b4a;
    border-left: 3px solid #e24b4a;
}

.sidebox a i {
    font-size: 13px;
    width: 16px;
    text-align: center;
}


.main {
    margin-left: 200px;
    flex: 1;
    padding: 24px;
    background: white;
}

.main h1 {
    font-size: 18px;
    font-weight: 500;
    color: #f0f0f0;
    margin-bottom: 20px;
    border-bottom: 1px solid #2a2a2a;
    padding-bottom: 12px;
}


.sale-card {
    background: #222;
    border: 1px solid #2a2a2a;
    border-radius: 6px;
    padding: 24px;
    max-width: 400px;
}

.sale-card h2 {
    font-size: 16px;
    font-weight: 500;
    color: #e24b4a;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #2a2a2a;
}

.sale-card label {
    display: block;
    font-size: 13px;
    color: #aaa;
    margin-bottom: 4px;
    margin-top: 12px;
}

.sale-card select,
.sale-card input {
    width: 100%;
    padding: 10px;
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 6px;
    color: #f0f0f0;
    font-size: 13px;
    box-sizing: border-box;
}

.sale-card select:focus,
.sale-card input:focus {
    outline: none;
    border-color: #e24b4a;
}


.btn {
    background: #e24b4a;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    margin-top: 16px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn:hover {
    background: #c03a39;
}

.result {
    margin-top: 16px;
    padding: 12px;
    background: #2a1010;
    border: 1px solid #e24b4a;
    border-radius: 6px;
    color: #e24b4a;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}
    </style>
</head>

<body>

<div class="sidebar">
    <h2>JAQ Meatshop</h2>
    <div class="sidebox">
        <a href="#" class="active"><i class="fa fa-home"></i> Dashboard</a>
        <a href="../Authentication/logout.php"><i class="fa fa-sign-out-alt"></i> Sign Out</a>
    </div>
</div>

<div class="main">

    <h1 style="color:black;">Welcome, <?= htmlspecialchars($_SESSION['username']); ?></h1>

    <div class="sale-card">
        <h2>Quick Sale</h2>

        <form method="POST">
            <label>Product</label>
            <select name="product" required>
                <option value="">Select Product</option>
                <option value="Beef">Beef (₱350/kg)</option>
                <option value="Pork">Pork (₱280/kg)</option>
                <option value="Chicken">Chicken (₱180/kg)</option>
            </select>

            <label>Weight (kg)</label>
            <input type="number" name="weight" step="0.01" min="0" placeholder="Enter weight in kg" required>

            <button type="submit" class="btn">
                <i class="fa fa-cash-register"></i> Process Sale
            </button>
        </form>

        <?php if (!empty($result)): ?>
            <div class="result">
                <i class="fa fa-check-circle"></i> <?= $result; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>