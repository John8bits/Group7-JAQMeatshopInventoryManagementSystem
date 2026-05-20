<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

$imageColumnStmt = $conn->prepare("SHOW COLUMNS FROM product LIKE 'ProductImage'");
$imageColumnStmt->execute();
if (!$imageColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE product ADD ProductImage VARCHAR(255) NULL");
}

$partColumnStmt = $conn->prepare("SHOW COLUMNS FROM product LIKE 'ProductPart'");
$partColumnStmt->execute();
if (!$partColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE product ADD ProductPart VARCHAR(100) NULL AFTER ProductName");
}

$typeColumnStmt = $conn->prepare("SHOW COLUMNS FROM product LIKE 'ProductType'");
$typeColumnStmt->execute();
if (!$typeColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE product ADD ProductType VARCHAR(50) NULL AFTER ProductPart");
}

$meatParts = [
    'Pork' => ['Shoulder', 'Hind Leg', 'Pork Belly', 'Loin', 'Ribs', 'Leg', 'Pig Feet', 'Bony Cuts', 'Head Parts', 'Organs'],
    'Beef' => ['Chuck', 'Brisket', 'Rib', 'Short Ribs', 'Loin', 'Sirloin', 'Tenderloin', 'Round', 'Shank', 'Flank'],
    'Chicken' => ['Whole Chicken', 'Breast', 'Thigh', 'Drumstick', 'Wings', 'Back', 'Neck', 'Feet', 'Liver', 'Egg'],
];
$processedFoods = ['Hotdog', 'Longganisa', 'Tocino', 'Bacon', 'Ham'];
$requiredCategories = array_merge(array_keys($meatParts), ['Processed Foods']);

$existingCategoryStmt = $conn->prepare("SELECT CategoryID, CategoryName FROM category");
$existingCategoryStmt->execute();
$categoryMap = [];
foreach ($existingCategoryStmt->fetchAll(PDO::FETCH_ASSOC) as $categoryRow) {
    $categoryMap[strtolower($categoryRow['CategoryName'])] = $categoryRow;
}

$insertCategoryStmt = $conn->prepare("INSERT INTO category (CategoryName) VALUES (?)");
foreach ($requiredCategories as $categoryName) {
    if (!isset($categoryMap[strtolower($categoryName)])) {
        $insertCategoryStmt->execute([$categoryName]);
        $categoryMap[strtolower($categoryName)] = [
            'CategoryID' => $conn->lastInsertId(),
            'CategoryName' => $categoryName,
        ];
    }
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
    $productName = trim($_POST['name'] ?? '');
    $productPart = trim($_POST['part'] ?? '');
    $productType = ($_POST['product_type'] ?? '') === 'Processed Foods' ? 'Processed Foods' : 'Meat';
    if ($productName === '') {
        $productName = $productPart;
    }

    $stmt = $conn->prepare("
        INSERT INTO product 
        (ProductName, ProductPart, ProductType, CategoryID, PricePerKg, StockWeight, ProductImage, DateAdded, Status)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Available')
    ");
    $stmt->execute([
        $productName,
        $productPart,
        $productType,
        $_POST['category'],
        $_POST['price'],
        $_POST['stock'],
        $productImage
    ]);
}

if (isset($_POST['update'])) {
    $productImage = uploadProductImage('image');
    $productName = trim($_POST['name'] ?? '');
    $productPart = trim($_POST['part'] ?? '');
    $productType = ($_POST['product_type'] ?? '') === 'Processed Foods' ? 'Processed Foods' : 'Meat';
    if ($productName === '') {
        $productName = $productPart;
    }

    $params = [
        $productName,
        $productPart,
        $productType,
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
        SET ProductName = ?, ProductPart = ?, ProductType = ?, CategoryID = ?, PricePerKg = ?, StockWeight = ?{$imageSql}
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
    WHERE p.Status = 'Available'
    ORDER BY COALESCE(p.ProductType, 'Meat'), c.CategoryName, p.ProductPart, p.ProductName
");

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$catStmt = $conn->prepare("SELECT * FROM category WHERE CategoryName IN ('Beef', 'Pork', 'Chicken', 'Processed Foods') ORDER BY FIELD(CategoryName, 'Pork', 'Beef', 'Chicken', 'Processed Foods')");
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
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(9, 20, 19, 0.55);
    z-index: 100;
    padding: 24px;
}

.modal.open {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    width: min(460px, 100%);
    background: white;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 18px 45px rgba(0,0,0,0.18);
}

.modal-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.modal-title-row h3 {
    color: #1A0F0A;
    margin: 0;
}

.modal-close {
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 8px;
    background: #F5E6DF;
    color: #7A3520;
    cursor: pointer;
    font-size: 22px;
    line-height: 1;
}

.modal-form {
    display: grid;
    gap: 12px;
}

.modal-form label {
    display: grid;
    gap: 6px;
    color: #6B4C3B;
    font-size: 13px;
    font-weight: 700;
}

.modal-form input,
.modal-form select {
    width: 100%;
    border: 1px solid #E8D5C8;
    border-radius: 8px;
    padding: 11px 12px;
    font-size: 14px;
    color: #1A0F0A;
}

.modal-form input:focus,
.modal-form select:focus {
    outline: none;
    border-color: #B85C38;
    box-shadow: 0 0 0 3px rgba(184, 92, 56, 0.14);
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 6px;
}

.btn-secondary {
    background: #F5E6DF;
    color: #7A3520;
}

.btn-secondary:hover {
    background: #E8D5C8;
}

.close {
    display: none;
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
            <th>Type</th>
            <th>Category</th>
            <th>Part</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php foreach ($products as $row): ?>
        <?php $rowType = $row['ProductType'] ?: ($row['CategoryName'] === 'Processed Foods' ? 'Processed Foods' : 'Meat'); ?>
        <tr>
            <td><?= htmlspecialchars($row['ProductName']) ?></td>
            <td>
                <?php if (!empty($row['ProductImage'])): ?>
                    <img class="product-thumb" src="../<?= htmlspecialchars($row['ProductImage']) ?>" alt="<?= htmlspecialchars($row['ProductName']) ?>">
                <?php else: ?>
                    No photo
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($rowType) ?></td>
            <td><?= htmlspecialchars($row['CategoryName']) ?></td>
            <td><?= htmlspecialchars($row['ProductPart'] ?? '') ?></td>
            <td>₱<?= number_format((float)$row['PricePerKg'], 2) ?></td>
            <td><?= number_format((float)$row['StockWeight'], 2) ?> kg</td>
            <td><?= htmlspecialchars($row['Status']) ?></td>
            <td>
                <button
                   type="button"
                   class="edit"
                   data-id="<?= htmlspecialchars($row['ProductID']) ?>"
                   data-name="<?= htmlspecialchars($row['ProductName']) ?>"
                   data-part="<?= htmlspecialchars($row['ProductPart'] ?? '') ?>"
                   data-type="<?= htmlspecialchars($rowType) ?>"
                   data-category="<?= htmlspecialchars($row['CategoryID']) ?>"
                   data-price="<?= htmlspecialchars($row['PricePerKg']) ?>"
                   data-stock="<?= htmlspecialchars($row['StockWeight']) ?>"
                   onclick="openEditModal(this)">
                   Edit
                </button>
                <a class="del" href="?page=inventory&delete=<?= $row['ProductID'] ?>" 
                   data-name="<?= htmlspecialchars($row['ProductName'], ENT_QUOTES) ?>"
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
        <div class="modal-title-row">
            <h3>Add Product</h3>
            <button class="modal-close" type="button" onclick="closeModal()">&times;</button>
        </div>

        <form class="modal-form" method="POST" enctype="multipart/form-data" onsubmit="return validateProductForm(this)">
            <input type="hidden" name="name" class="product-name-input">
            <label>
                Category
                <select name="product_type" class="product-type-select" required onchange="syncProductChoices(this.form)">
                    <option value="">Select Category</option>
                    <option value="Meat">Meat</option>
                    <option value="Processed Foods">Processed Foods</option>
                </select>
            </label>
            <label class="animal-category-field">
                Meat Type
                <select name="category" class="animal-category-select" required onchange="syncProductChoices(this.form)">
                    <option value="">Select Meat Type</option>
                    <?php foreach ($categories as $c): ?>
                        <?php if ($c['CategoryName'] !== 'Processed Foods'): ?>
                            <option value="<?= $c['CategoryID'] ?>" data-name="<?= htmlspecialchars($c['CategoryName']) ?>">
                                <?= htmlspecialchars($c['CategoryName']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php foreach ($categories as $c): ?>
                        <?php if ($c['CategoryName'] === 'Processed Foods'): ?>
                            <option value="<?= $c['CategoryID'] ?>" data-name="Processed Foods" data-processed="1" hidden>
                                Processed Foods
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Product / Cut
                <select name="part" class="part-select" required onchange="syncProductName(this.form)">
                    <option value="">Select product</option>
                </select>
            </label>
            <label>
                Price per kg
                <input type="number" name="price" placeholder="Price/kg" min="0" step="0.01" required>
            </label>
            <label>
                Stock (kg)
                <input type="number" name="stock" placeholder="Stock (kg)" min="0" step="0.01" required>
            </label>
            <label>
                Product Image
                <input type="file" name="image" accept="image/*">
            </label>
            <div class="modal-actions">
                
                <button class="btn" name="add" type="submit">Save Product</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-title-row">
            <h3>Edit Product</h3>
            <button class="modal-close" type="button" onclick="closeEditModal()">&times;</button>
        </div>

        <form class="modal-form" method="POST" enctype="multipart/form-data" onsubmit="return validateProductForm(this)">
            <input type="hidden" name="product_id" id="editProductId">
            <input type="hidden" name="name" id="editName" class="product-name-input">
            <label>
                Category
                <select name="product_type" id="editProductType" class="product-type-select" required onchange="syncProductChoices(this.form)">
                    <option value="">Select Category</option>
                    <option value="Meat">Meat</option>
                    <option value="Processed Foods">Processed Foods</option>
                </select>
            </label>
            <label class="animal-category-field">
                Meat Type
                <select name="category" id="editCategory" class="animal-category-select" required onchange="syncProductChoices(this.form)">
                    <option value="">Select Meat Type</option>
                    <?php foreach ($categories as $c): ?>
                        <?php if ($c['CategoryName'] !== 'Processed Foods'): ?>
                            <option value="<?= $c['CategoryID'] ?>" data-name="<?= htmlspecialchars($c['CategoryName']) ?>">
                                <?= htmlspecialchars($c['CategoryName']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php foreach ($categories as $c): ?>
                        <?php if ($c['CategoryName'] === 'Processed Foods'): ?>
                            <option value="<?= $c['CategoryID'] ?>" data-name="Processed Foods" data-processed="1" hidden>
                                Processed Foods
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Product / Cut
                <select name="part" id="editPart" class="part-select" required onchange="syncProductName(this.form)">
                    <option value="">Select product</option>
                </select>
            </label>
            <label>
                Price per kg
                <input type="number" name="price" id="editPrice" placeholder="Price/kg" min="0" step="0.01" required>
            </label>
            <label>
                Stock (kg)
                <input type="number" name="stock" id="editStock" placeholder="Stock (kg)" min="0" step="0.01" required>
            </label>
            <label>
                Product Image
                <input type="file" name="image" accept="image/*">
            </label>
            <div class="modal-actions">
                
                <button class="btn" name="update" type="submit">Update Product</button>
            </div>
        </form>
    </div>
</div>

<script>
const meatParts = <?= json_encode($meatParts) ?>;
const processedFoods = <?= json_encode($processedFoods) ?>;

function validateProductForm(form) {
    const price = parseFloat(form.querySelector('input[name="price"]').value);
    const stock = parseFloat(form.querySelector('input[name="stock"]').value);
    syncProductName(form);
    const type = form.querySelector('select[name="product_type"]').value;
    const category = form.querySelector('select[name="category"]').value;
    const part = form.querySelector('select[name="part"]').value.trim();

    if (!type || !category || !part) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Missing information',
                text: 'Please select category, meat type if needed, and product/cut.',
                icon: 'warning',
                confirmButtonColor: '#B85C38'
            });
        }
        return false;
    }

    if (Number.isNaN(price) || price <= 0 || Number.isNaN(stock) || stock <= 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Invalid values',
                text: 'Price and stock must be positive numbers.',
                icon: 'error',
                confirmButtonColor: '#B85C38'
            });
        }
        return false;
    }

    return true;
}

function getProcessedCategoryOption(form) {
    return form.querySelector('select[name="category"] option[data-processed="1"]');
}

function syncProductName(form) {
    const partSelect = form.querySelector('select[name="part"]');
    const nameInput = form.querySelector('input[name="name"]');
    const part = partSelect.value || '';

    nameInput.value = part;
}

function setPartOptions(partSelect, parts, selectedPart = '') {
    partSelect.innerHTML = '<option value="">Select product</option>';
    parts.forEach(function(part) {
        const option = document.createElement('option');
        option.value = part;
        option.textContent = part;
        if (part === selectedPart) {
            option.selected = true;
        }
        partSelect.appendChild(option);
    });
}

function syncProductChoices(form, selectedPart = '') {
    const typeSelect = form.querySelector('select[name="product_type"]');
    const categorySelect = form.querySelector('select[name="category"]');
    const partSelect = form.querySelector('select[name="part"]');
    const animalField = form.querySelector('.animal-category-field');
    const processedOption = getProcessedCategoryOption(form);
    const type = typeSelect.value;

    if (type === 'Processed Foods') {
        if (processedOption) {
            categorySelect.value = processedOption.value;
        }
        animalField.style.display = 'none';
        categorySelect.required = false;
        setPartOptions(partSelect, processedFoods, selectedPart);
    } else {
        animalField.style.display = '';
        categorySelect.required = true;
        if (categorySelect.selectedOptions[0]?.dataset.processed === '1') {
            categorySelect.value = '';
        }
        const categoryName = categorySelect.selectedOptions[0]?.dataset.name || '';
        setPartOptions(partSelect, meatParts[categoryName] || [], selectedPart);
    }

    syncProductName(form);
}

function openModal() {
    const modal = document.getElementById('addModal');
    const form = modal.querySelector('form');
    form.reset();
    syncProductChoices(form);
    modal.classList.add('open');
}

function closeModal() {
    document.getElementById('addModal').classList.remove('open');
}

function openEditModal(button) {
    const modal = document.getElementById('editModal');
    const form = modal.querySelector('form');
    document.getElementById('editProductId').value = button.dataset.id;
    document.getElementById('editName').value = button.dataset.name;
    document.getElementById('editProductType').value = button.dataset.type;
    document.getElementById('editCategory').value = button.dataset.category;
    syncProductChoices(form, button.dataset.part);
    document.getElementById('editPrice').value = button.dataset.price;
    document.getElementById('editStock').value = button.dataset.stock;
    document.getElementById('editModal').classList.add('open');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('open');
}

document.querySelectorAll('.modal').forEach(function(modal) {
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('open');
        }
    });
});

const deleteButtons = document.querySelectorAll('.del');
if (deleteButtons.length) {
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            if (typeof Swal === 'undefined') {
                return;
            }

            Swal.fire({
                title: 'Delete product?',
                text: button.dataset.name + ' will be removed from inventory.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#D53E0F',
                cancelButtonColor: '#6B4C3B',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.location.href = button.href;
                }
            });
        });
    });
}

document.querySelectorAll('.modal-form').forEach(function(form) {
    syncProductChoices(form);
});

</script>
