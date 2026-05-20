<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

$imageColumnStmt = $conn->prepare("SHOW COLUMNS FROM product LIKE 'ProductImage'");
$imageColumnStmt->execute();
if (!$imageColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE product ADD ProductImage VARCHAR(255) NULL");
}

function uploadProductImage($fieldName) {
    if (empty($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mimeType = mime_content_type($_FILES[$fieldName]['tmp_name']);
    if (!isset($allowedTypes[$mimeType])) {
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/products';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = uniqid('product_', true) . '.' . $allowedTypes[$mimeType];
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return null;
    }

    return 'uploads/products/' . $fileName;
}

if (isset($_POST['add'])) {
    $productImage = uploadProductImage('image');
    $stmt = $conn->prepare("
        INSERT INTO product 
        (ProductName, CategoryID, PricePerKg, StockWeight, ProductImage, DateAdded, Status)
        VALUES (?, ?, ?, ?, ?, NOW(), 'Available')
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['category'],
        $_POST['price'],
        $_POST['stock'],
        $productImage
    ]);
}

if (isset($_POST['update'])) {
    $productImage = uploadProductImage('image');
    $params = [
        $_POST['name'],
        $_POST['category'],
        $_POST['price'],
        $_POST['stock'],
    ];
    $imageSql = '';

    if ($productImage !== null) {
        $imageSql = ', ProductImage = ?';
        $params[] = $productImage;
    }

    $params[] = $_POST['product_id'];

    $stmt = $conn->prepare("
        UPDATE product
        SET ProductName = ?, CategoryID = ?, PricePerKg = ?, StockWeight = ?{$imageSql}
        WHERE ProductID = ?
    ");
    $stmt->execute($params);
}

if (isset($_GET['delete'])) {
    $productId = (int)$_GET['delete'];

    $transactionStmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE ProductID = ?");
    $transactionStmt->execute([$productId]);
    $transactionCount = (int)$transactionStmt->fetchColumn();

    if ($transactionCount > 0) {
        $stmt = $conn->prepare("UPDATE product SET Status = 'Unavailable' WHERE ProductID = ?");
        $stmt->execute([$productId]);
    } else {
        $stmt = $conn->prepare("DELETE FROM product WHERE ProductID = ?");
        $stmt->execute([$productId]);
    }
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

.edit{
    color:white;
    background:#1f6f55;
    padding:6px 10px;
    border-radius:6px;
    text-decoration:none;
    border:none;
    cursor:pointer;
    margin-right:6px;
}

.edit:hover{
    background:#175640;
}

.product-thumb{
    width:56px;
    height:44px;
    object-fit:cover;
    border-radius:6px;
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
            <th>Photo</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php foreach ($products as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['ProductName']) ?></td>
            <td>
                <?php if (!empty($row['ProductImage'])): ?>
                    <img class="product-thumb" src="../<?= htmlspecialchars($row['ProductImage']) ?>" alt="<?= htmlspecialchars($row['ProductName']) ?>">
                <?php else: ?>
                    No photo
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['CategoryName']) ?></td>
            <td>₱<?= number_format((float)$row['PricePerKg'], 2) ?></td>
            <td><?= number_format((float)$row['StockWeight'], 2) ?> kg</td>
            <td><?= htmlspecialchars($row['Status']) ?></td>
            <td>
                <button
                   type="button"
                   class="edit"
                   data-id="<?= htmlspecialchars($row['ProductID']) ?>"
                   data-name="<?= htmlspecialchars($row['ProductName']) ?>"
                   data-category="<?= htmlspecialchars($row['CategoryID']) ?>"
                   data-price="<?= htmlspecialchars($row['PricePerKg']) ?>"
                   data-stock="<?= htmlspecialchars($row['StockWeight']) ?>"
                   onclick="openEditModal(this)">
                   Edit
                </button>
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

        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Product Name" required>

            <select name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['CategoryID'] ?>">
                        <?= $c['CategoryName'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="number" name="price" placeholder="Price/kg" min="0" step="0.01" required>
            <input type="number" name="stock" placeholder="Stock (kg)" min="0" step="0.01" required>
            <input type="file" name="image" accept="image/*">

            <button class="btn" name="add">Save</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3 style="color:black;">Edit Product</h3>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" id="editProductId">
            <input type="text" name="name" id="editName" placeholder="Product Name" required>

            <select name="category" id="editCategory" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['CategoryID'] ?>">
                        <?= $c['CategoryName'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="number" name="price" id="editPrice" placeholder="Price/kg" min="0" step="0.01" required>
            <input type="number" name="stock" id="editStock" placeholder="Stock (kg)" min="0" step="0.01" required>
            <input type="file" name="image" accept="image/*">

            <button class="btn" name="update">Update</button>
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

function openEditModal(button) {
    document.getElementById("editProductId").value = button.dataset.id;
    document.getElementById("editName").value = button.dataset.name;
    document.getElementById("editCategory").value = button.dataset.category;
    document.getElementById("editPrice").value = button.dataset.price;
    document.getElementById("editStock").value = button.dataset.stock;
    document.getElementById("editModal").style.display = "block";
}

function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

window.onclick = function(e) {
    let addModal = document.getElementById("addModal");
    let editModal = document.getElementById("editModal");
    if (e.target == addModal) {
        addModal.style.display = "none";
    }
    if (e.target == editModal) {
        editModal.style.display = "none";
    }
}

</script>
