<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

if (isset($_POST['add'])) {
    $stmt = $conn->prepare("
        INSERT INTO product 
        (ProductName, CategoryID, PricePerKg, StockWeight, DateAdded, Status)
        VALUES (?, ?, ?, ?, NOW(), 'Available')
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['category'],
        $_POST['price'],
        $_POST['stock']
    ]);
}

if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM product WHERE ProductID = ?");
    $stmt->execute([$_GET['delete']]);
}

$stmt = $conn->prepare("
    SELECT p.*, c.CategoryName 
    FROM product p
    JOIN category c ON p.CategoryID = c.CategoryID
");

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$catStmt = $conn->prepare("SELECT * FROM category");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
body { font-family: Arial; }

.container {
    padding: 20px;
}

h2{
    color:white;
}

.header {
    display:flex;
    justify-content: space-between;
    align-items:center;
}

.btn {
    background:#D53E0F;
    color:white;
    padding:10px 15px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.btn:hover {
    background:#b7320c;
}


table {
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
    background:white;
    border-radius:10px;
    overflow:hidden;
}

th {
    background:#091413;
    color:white;
    padding:10px;
}

td {
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
    color:black;
}


.modal {
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
}

.modal-content {
    background:white;
    width:400px;
    margin:10% auto;
    padding:20px;
    border-radius:10px;
}

.modal input, .modal select {
    width:94%;
    margin:5px 0;
    padding:10px;
}
.modal input{
    width:94%;
}

.close {
    float:right;
    cursor:pointer;
    font-size:20px;
}

.del{
    color: white;
    background: red;
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
}

.del:hover{
    background:#b7320c;
}


</style>

<div class="container">

    <div class="header">
        <h2> Inventory</h2>
        <button class="btn" onclick="openModal()">+ Add Product</button>
    </div>
   
    <table>
        <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Action</th>
        </tr>

        <?php foreach ($products as $row): ?>
        <tr>
            <td><?= $row['ProductName'] ?></td>
            <td><?= $row['CategoryName'] ?></td>
            <td>₱<?= $row['PricePerKg'] ?></td>
            <td><?= $row['StockWeight'] ?> kg</td>
            <td>
                <a class="del" href="?page=inventory&delete=<?= $row['ProductID'] ?>" 
                   onclick="return confirm('Delete this product?')"
                   style="color:white; background-color: red;">
                   Delete
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 style="color:black;">Add Product</h3>

        <form method="POST">
            <input type="text" name="name" placeholder="Product Name" required>

            <select name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['CategoryID'] ?>">
                        <?= $c['CategoryName'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="number" name="price" placeholder="Price/kg" required>
            <input type="number" name="stock" placeholder="Stock (kg)" required>

            <button class="btn" name="add">Save</button>
        </form>
    </div>
</div>

<script>

function openModal() {
    document.getElementById("addModal").style.display = "block";
}

function closeModal() {
    document.getElementById("addModal").style.display = "none";
}

window.onclick = function(e) {
    let modal = document.getElementById("addModal");
    if (e.target == modal) {
        modal.style.display = "none";
    }
}

</script>