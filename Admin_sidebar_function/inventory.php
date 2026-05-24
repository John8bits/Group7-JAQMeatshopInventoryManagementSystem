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

function ensureCategory(PDO $conn, $categoryName) {
    $stmt = $conn->prepare("SELECT CategoryID FROM category WHERE CategoryName = ? LIMIT 1");
    $stmt->execute([$categoryName]);
    $categoryId = $stmt->fetchColumn();

    if ($categoryId) {
        return (int)$categoryId;
    }

    $stmt = $conn->prepare("INSERT INTO category (CategoryName, Status) VALUES (?, 'Active')");
    $stmt->execute([$categoryName]);
    return (int)$conn->lastInsertId();
}

$addCategories = [
    'Meat' => ensureCategory($conn, 'Meat'),
    'Processed Food' => ensureCategory($conn, 'Processed Foods'),
];

if (isset($_POST['add'])) {
    $productImage = uploadProductImage('image');
    $categoryStmt = $conn->prepare("SELECT CategoryName FROM category WHERE CategoryID = ?");
    $categoryStmt->execute([$_POST['category']]);
    $categoryName = trim((string)$categoryStmt->fetchColumn());
    $productGroup = trim((string)($_POST['product_group'] ?? ''));
    $productVariant = trim((string)($_POST['product_variant'] ?? ''));
    $productPart = trim((string)($_POST['part'] ?? ''));
    $productName = trim((string)($_POST['name'] ?? ''));

    if ($productName === '') {
        if ($categoryName === 'Meat' && $productVariant !== '') {
            $productName = stripos($productVariant, $productGroup) !== false
                ? $productVariant
                : trim($productGroup . ' ' . $productVariant);
            $productPart = $productVariant;
        } elseif ($productGroup !== '') {
            $productName = $productGroup;
            $productPart = $productPart !== '' ? $productPart : $productGroup;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO product 
        (ProductName, ProductPart, CategoryID, PricePerKg, StockWeight, ProductImage, DateAdded, Status)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Available')
    ");
    $stmt->execute([
        $productName,
        $productPart,
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
        $_POST['part'],
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
        SET ProductName = ?, ProductPart = ?, CategoryID = ?, PricePerKg = ?, StockWeight = ?{$imageSql}
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
    ORDER BY p.ProductName
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
            <th>Category</th>
            <th>Part</th>
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
            <label>
                Category
                <select name="category" id="addCategory" required onchange="handleAddCategoryChange()">
                    <option value="">Select Category</option>
                    <?php foreach ($addCategories as $label => $categoryId): ?>
                        <option value="<?= htmlspecialchars($categoryId) ?>" data-name="<?= htmlspecialchars($label, ENT_QUOTES) ?>">
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Product Type
                <select name="product_group" id="addProductGroup" required onchange="handleAddProductGroupChange()">
                    <option value="">Select Category First</option>
                </select>
            </label>
            <label id="addVariantLabel">
                Cut / Variant
                <select name="product_variant" id="addProductVariant" onchange="updateAddProductName()">
                    <option value="">Select Product Type First</option>
                </select>
            </label>
            <label>
                Product Name
                <input type="text" name="name" id="addName" placeholder="Generated from category and meat part" readonly>
            </label>
            <input type="hidden" name="part" id="addPart">
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
            <label>
                Product Name
                <input type="text" name="name" id="editName" placeholder="Product Name" required>
            </label>
            <label>
                Meat Part / Cut
                <input type="text" name="part" id="editPart" placeholder="e.g. Pig feet, Belly, Rib, Leg" required>
            </label>
            <label>
                Animal Category
                <select name="category" id="editCategory" required>
                    <option value="">Select Animal Category</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['CategoryID'] ?>">
                            <?= $c['CategoryName'] ?>
                        </option>
                    <?php endforeach; ?>
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

function validateProductForm(form) {
    const price = parseFloat(form.querySelector('input[name="price"]').value);
    const stock = parseFloat(form.querySelector('input[name="stock"]').value);
    const nameInput = form.querySelector('input[name="name"]');
    const name = nameInput ? nameInput.value.trim() : '';
    const part = form.querySelector('input[name="part"]').value.trim();
    const category = form.querySelector('select[name="category"]').value;

    if (!part || !category || (nameInput && !name)) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Missing information',
                text: 'Please select a category, product type, and cut or variant when required.',
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

const addProductChoices = {
    Meat: {
        Pork: ['Shoulder', 'Hind Leg', 'Pork Belly', 'Loin', 'Ribs', 'Leg', 'Bony Cuts', 'Head Parts', 'Organs'],
        Beef: ['Chuck', 'Brisket', 'Rib', 'Short Ribs', 'Loin', 'Sirloin', 'Tenderloin', 'Round', 'Shank', 'Flank'],
        Chicken: ['Whole Chicken', 'Breast', 'Thigh', 'Drumstick', 'Wings', 'Back', 'Neck', 'Feet'],
        Liver: [],
        Egg: []
    },
    'Processed Food': {
        Hotdog: [],
        Longganisa: [],
        Tocino: [],
        Bacon: [],
        Ham: []
    }
};

function setSelectOptions(select, options, placeholder) {
    select.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = placeholder;
    select.appendChild(defaultOption);

    options.forEach(function(optionText) {
        const option = document.createElement('option');
        option.value = optionText;
        option.textContent = optionText;
        select.appendChild(option);
    });
}

function selectedAddCategoryName() {
    const category = document.getElementById('addCategory');
    const selected = category ? category.options[category.selectedIndex] : null;
    return selected ? (selected.dataset.name || selected.textContent).trim() : '';
}

function handleAddCategoryChange() {
    const group = document.getElementById('addProductGroup');
    const variant = document.getElementById('addProductVariant');
    const categoryName = selectedAddCategoryName();
    const groupNames = Object.keys(addProductChoices[categoryName] || {});

    setSelectOptions(group, groupNames, categoryName ? 'Select Product Type' : 'Select Category First');
    setSelectOptions(variant, [], 'Select Product Type First');
    handleAddProductGroupChange();
}

function handleAddProductGroupChange() {
    const group = document.getElementById('addProductGroup');
    const variant = document.getElementById('addProductVariant');
    const variantLabel = document.getElementById('addVariantLabel');
    const categoryName = selectedAddCategoryName();
    const groupName = group.value;
    const variants = (addProductChoices[categoryName] && addProductChoices[categoryName][groupName]) || [];

    setSelectOptions(variant, variants, variants.length ? 'Select Cut / Variant' : 'No cut needed');
    variant.disabled = variants.length === 0;
    variantLabel.style.display = variants.length ? 'grid' : 'none';
    updateAddProductName();
}

function updateAddProductName() {
    const category = document.getElementById('addCategory');
    const group = document.getElementById('addProductGroup');
    const variant = document.getElementById('addProductVariant');
    const part = document.getElementById('addPart');
    const name = document.getElementById('addName');

    if (!category || !group || !variant || !part || !name) {
        return;
    }

    const groupName = group.value;
    const variantName = variant.disabled ? '' : variant.value;
    const requiresVariant = !variant.disabled;

    if (!category.value || !groupName || (requiresVariant && !variantName)) {
        name.value = '';
        part.value = '';
        return;
    }

    name.value = variantName
        ? (variantName.toLowerCase().includes(groupName.toLowerCase()) ? variantName : groupName + ' ' + variantName)
        : groupName;
    part.value = variantName || groupName;
}

function openModal() {
    handleAddCategoryChange();
    document.getElementById('addModal').classList.add('open');
}

function closeModal() {
    document.getElementById('addModal').classList.remove('open');
}

function openEditModal(button) {
    document.getElementById('editProductId').value = button.dataset.id;
    document.getElementById('editName').value = button.dataset.name;
    document.getElementById('editPart').value = button.dataset.part;
    document.getElementById('editCategory').value = button.dataset.category;
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

</script>
