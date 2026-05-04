<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

$stmt = $conn->prepare("
    SELECT ProductName, StockWeight 
    FROM product 
    WHERE StockWeight < 5
");
$stmt->execute();
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.container {
    padding: 20px;
    font-family: Arial;
}


.header {
    background: linear-gradient(135deg, #091413, #1f2e2b);
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.header h2 {
    margin: 0;
}


.alert-box {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-left: 6px solid #D53E0F;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    transition: 0.2s;
}

.alert-box:hover {
    transform: translateX(5px);
}


.low {
    color: #D53E0F;
    font-weight: bold;
}

.icon {
    font-size: 20px;
    color: #D53E0F;
    margin-right: 10px;
}


.empty {
    text-align: center;
    color: gray;
    margin-top: 30px;
}
</style>

<div class="container">

    
    <div class="header">
        <h2>Stock Alerts</h2>
        <p>Products below critical stock level (5kg)</p>
    </div>

    <?php if ($alerts): ?>
        <?php foreach ($alerts as $row): ?>
            <div class="alert-box">

                <div>
                    <span class="icon"></span>
                    <span class="low">
                        <?= $row['ProductName'] ?>
                    </span>
                </div>

                <div class="low">
                    <?= $row['StockWeight'] ?> kg left
                </div>

            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty">
             No low stock alerts. Everything is stable.
        </div>
    <?php endif; ?>

</div>