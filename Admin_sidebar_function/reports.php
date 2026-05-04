<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;


$stmt = $conn->prepare("
    SELECT SUM(TotalPrice) as total 
    FROM transactions 
    WHERE DATE(DateTime) = CURDATE()
");
$stmt->execute();
$daily = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt = $conn->prepare("
    SELECT t.*, p.ProductName
    FROM transactions t
    JOIN product p ON t.ProductID = p.ProductID
    ORDER BY t.DateTime DESC
");
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
body {
    font-family: Arial;
}


.report-container {
    padding: 20px;
}

.summary-card {
    background: linear-gradient(135deg, #091413, #1f2e2b);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-card h2 {
    margin: 0;
    font-size: 22px;
}

.total-sales {
    font-size: 28px;
    font-weight: bold;
    color: #D53E0F;
}


table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

th {
    background: #091413;
    color: white;
    padding: 12px;
    text-align: center;
}

td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background: #f5f5f5;
}

.price {
    color: #D53E0F;
    font-weight: bold;
}
</style>

<div class="report-container">

   
    <div class="summary-card">
        <h2> Sales Report (Today)</h2>
        <div class="total-sales">
            ₱<?= number_format($daily['total'] ?? 0, 2) ?>
        </div>
    </div>

  
    <table>
        <tr>
            <th>Product</th>
            <th>Weight Sold</th>
            <th>Total Price</th>
            <th>Date</th>
        </tr>

        <?php foreach ($transactions as $row): ?>
        <tr>
            <td><?= $row['ProductName'] ?></td>
            <td><?= $row['WeightSold'] ?> kg</td>
            <td class="price">₱<?= number_format($row['TotalPrice'], 2) ?></td>
            <td><?= $row['DateTime'] ?></td>
        </tr>
        <?php endforeach; ?>

    </table>

</div>