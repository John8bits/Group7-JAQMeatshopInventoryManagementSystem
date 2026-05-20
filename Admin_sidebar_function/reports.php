<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

$periods = [
    'today' => [
        'label' => 'Sales Today',
        'short' => 'Today',
        'range' => 'Transactions from today',
        'where' => 'DATE(t.DateTime) = CURDATE()',
    ],
    'weekly' => [
        'label' => 'Weekly Sales',
        'short' => 'Weekly',
        'range' => 'Transactions from this week',
        'where' => 'YEARWEEK(t.DateTime, 1) = YEARWEEK(CURDATE(), 1)',
    ],
    'monthly' => [
        'label' => 'Monthly Sales',
        'short' => 'Monthly',
        'range' => 'Transactions from this month',
        'where' => 'YEAR(t.DateTime) = YEAR(CURDATE()) AND MONTH(t.DateTime) = MONTH(CURDATE())',
    ],
    'yearly' => [
        'label' => 'Yearly Sales',
        'short' => 'Yearly',
        'range' => 'Transactions from this year',
        'where' => 'YEAR(t.DateTime) = YEAR(CURDATE())',
    ],
];

$selectedPeriod = $_GET['period'] ?? 'today';
if (!isset($periods[$selectedPeriod])) {
    $selectedPeriod = 'today';
}

$period = $periods[$selectedPeriod];
$whereSql = $period['where'];

$summaryStmt = $conn->prepare("
    SELECT
        COALESCE(SUM(t.TotalPrice), 0) AS total_sales,
        COALESCE(SUM(t.WeightSold), 0) AS total_weight,
        COUNT(*) AS transaction_count,
        COALESCE(AVG(t.TotalPrice), 0) AS average_sale
    FROM transactions t
    WHERE {$whereSql}
");
$summaryStmt->execute();
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT t.*, COALESCE(p.ProductName, 'Deleted product') AS ProductName
    FROM transactions t
    LEFT JOIN product p ON t.ProductID = p.ProductID
    WHERE {$whereSql}
    ORDER BY t.DateTime DESC
");
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.report-container {
    font-family: Arial, sans-serif;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
}

.report-title h2 {
    color: #1A0F0A;
    margin: 0;
}

.report-title p {
    color: #6B4C3B;
    margin: 4px 0 0;
    font-size: 14px;
}

.period-form {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #FDF8F5;
    border: 1px solid #E8D5C8;
    border-radius: 10px;
    padding: 10px 12px;
}

.period-form label {
    color: #6B4C3B;
    font-size: 13px;
    font-weight: 700;
}

.period-form select {
    border: 1px solid #E8D5C8;
    border-radius: 8px;
    color: #1A0F0A;
    background: white;
    padding: 9px 34px 9px 10px;
    font-size: 14px;
    cursor: pointer;
}

.summary-card {
    background: linear-gradient(135deg, #091413, #1f2e2b);
    color: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
}

.summary-card h3 {
    margin: 0 0 6px;
    font-size: 21px;
}

.summary-card p {
    margin: 0;
    color: rgba(255, 255, 255, 0.75);
    font-size: 14px;
}

.total-sales {
    color: #FFB38A;
    font-size: 30px;
    font-weight: 800;
    white-space: nowrap;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}

.metric-box {
    background: #FDF8F5;
    border: 1px solid #E8D5C8;
    border-radius: 10px;
    padding: 14px;
}

.metric-label {
    color: #6B4C3B;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.metric-value {
    color: #1A0F0A;
    font-size: 20px;
    font-weight: 800;
}

.report-table-wrap {
    border: 1px solid #E8D5C8;
    border-radius: 10px;
    overflow: hidden;
    background: white;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th {
    background: #091413;
    color: white;
    padding: 13px 14px;
    text-align: left;
    font-size: 13px;
}

.report-table td {
    padding: 13px 14px;
    border-bottom: 1px solid #F0E2D8;
    color: #1A0F0A;
}

.report-table tr:last-child td {
    border-bottom: none;
}

.report-table tr:hover {
    background: #FDF8F5;
}

.price {
    color: #D53E0F;
    font-weight: 800;
}

.empty-report {
    text-align: center;
    color: #6B4C3B;
    padding: 28px !important;
}

@media (max-width: 760px) {
    .report-header,
    .summary-card {
        flex-direction: column;
    }

    .period-form,
    .period-form select {
        width: 100%;
    }

    .metric-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="report-container">
    <div class="report-header">
        <div class="report-title">
            <h2>Sales Report</h2>
            <p>Filter sales performance by day, week, month, or year</p>
        </div>

        <form class="period-form" method="GET">
            <input type="hidden" name="page" value="reports">
            <label for="period">Report</label>
            <select name="period" id="period" onchange="this.form.submit()">
                <?php foreach ($periods as $key => $option): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $selectedPeriod === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option['short']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="summary-card">
        <div>
            <h3><?= htmlspecialchars($period['label']) ?></h3>
            <p><?= htmlspecialchars($period['range']) ?></p>
        </div>
        <div class="total-sales">
            ₱<?= number_format((float)$summary['total_sales'], 2) ?>
        </div>
    </div>

    <div class="metric-grid">
        <div class="metric-box">
            <div class="metric-label">Transactions</div>
            <div class="metric-value"><?= number_format((int)$summary['transaction_count']) ?></div>
        </div>
        <div class="metric-box">
            <div class="metric-label">Weight Sold</div>
            <div class="metric-value"><?= number_format((float)$summary['total_weight'], 2) ?> kg</div>
        </div>
    </div>

    <div class="report-table-wrap">
        <table class="report-table">
            <tr>
                <th>Product</th>
                <th>Weight Sold</th>
                <th>Total Price</th>
                <th>Date</th>
            </tr>

            <?php if (empty($transactions)): ?>
                <tr>
                    <td class="empty-report" colspan="4">No sales found for this period.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ProductName']) ?></td>
                        <td><?= number_format((float)$row['WeightSold'], 2) ?> kg</td>
                        <td class="price">₱<?= number_format((float)$row['TotalPrice'], 2) ?></td>
                        <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['DateTime']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
