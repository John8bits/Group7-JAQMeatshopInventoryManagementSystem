<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

$requiredColumns = [
    'SupplierName'   => "VARCHAR(120) NOT NULL",
    'ContactPerson'  => "VARCHAR(120) NULL",
    'Phone'          => "VARCHAR(40) NULL",
    'Address'        => "VARCHAR(255) NULL",
    'Status'         => "VARCHAR(30) NOT NULL DEFAULT 'Active'",
    'DateAdded'      => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    'DeletedAt'      => "DATETIME NULL",
];

$tableExists = $conn->query("SHOW TABLES LIKE 'supplier'")->fetch(PDO::FETCH_ASSOC);
if (!$tableExists) {
    $conn->exec("CREATE TABLE supplier (
        SupplierID INT AUTO_INCREMENT PRIMARY KEY,
        SupplierName VARCHAR(120) NOT NULL,
        ContactPerson VARCHAR(120) NULL,
        Phone VARCHAR(40) NULL,
        Address VARCHAR(255) NULL,
        Status VARCHAR(30) NOT NULL DEFAULT 'Active',
        DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        DeletedAt DATETIME NULL
    )");
} else {
    $existingColumns = $conn->query("SHOW COLUMNS FROM supplier")->fetchAll(PDO::FETCH_COLUMN, 0);
    $existingColumns = array_map('strtolower', $existingColumns);
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array(strtolower($column), $existingColumns, true)) {
            $conn->exec("ALTER TABLE supplier ADD $column $definition");
        }
    }
}

$supplierDeleted = false;

if (isset($_POST['add'])) {
    $stmt = $conn->prepare("
        INSERT INTO supplier (SupplierName, ContactPerson, Phone, Address, Status)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        trim($_POST['supplier_name']),
        trim($_POST['contact_person']),
        trim($_POST['phone']),
        trim($_POST['address']),
        $_POST['status'],
    ]);
}

if (isset($_POST['update'])) {
    $stmt = $conn->prepare("
        UPDATE supplier
        SET SupplierName = ?, ContactPerson = ?, Phone = ?, Address = ?, Status = ?
        WHERE SupplierID = ?
    ");
    $stmt->execute([
        trim($_POST['supplier_name']),
        trim($_POST['contact_person']),
        trim($_POST['phone']),
        trim($_POST['address']),
        $_POST['status'],
        (int)$_POST['supplier_id'],
    ]);
}

if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("UPDATE supplier SET Status = 'Inactive', DeletedAt = NOW() WHERE SupplierID = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $supplierDeleted = true;
}

if (isset($_GET['restore'])) {
    $stmt = $conn->prepare("UPDATE supplier SET Status = 'Active', DeletedAt = NULL WHERE SupplierID = ?");
    $stmt->execute([(int)$_GET['restore']]);
}

$stmt = $conn->prepare("
    SELECT *
    FROM supplier
    WHERE DeletedAt IS NULL
    ORDER BY SupplierName
");
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$deletedStmt = $conn->prepare("
    SELECT *
    FROM supplier
    WHERE DeletedAt IS NOT NULL
    ORDER BY DeletedAt DESC, SupplierName
");
$deletedStmt->execute();
$deletedSuppliers = $deletedStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.supplier-page {
    font-family: Arial, sans-serif;
}

.supplier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
}

.supplier-header h2 {
    color: #1A0F0A;
    margin: 0;
}

.supplier-header p {
    color: #6B4C3B;
    margin: 4px 0 0;
    font-size: 14px;
}

.supplier-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #B85C38;
    color: white;
    padding: 10px 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    text-decoration: none;
}

.supplier-btn:hover {
    background: #7A3520;
}

.supplier-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border: 1px solid #E8D5C8;
    border-radius: 10px;
    overflow: hidden;
}

.supplier-table th {
    background: #091413;
    color: white;
    padding: 13px 14px;
    text-align: left;
    font-size: 13px;
}

.supplier-table td {
    padding: 14px;
    border-bottom: 1px solid #F0E2D8;
    color: #1A0F0A;
    vertical-align: middle;
}

.supplier-table tr:last-child td {
    border-bottom: none;
}

.supplier-table tr:hover {
    background: #FDF8F5;
}

.supplier-name {
    font-weight: 700;
}

.supplier-muted {
    color: #6B4C3B;
}

.supplier-status {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 4px 10px;
    background: #EAF5EE;
    color: #2D7A4F;
    font-size: 12px;
    font-weight: 700;
}

.supplier-status.inactive {
    background: #FEF0EF;
    color: #C0392B;
}

.supplier-actions {
    display: flex;
    gap: 8px;
}

.btn-edit,
.btn-delete {
    border: none;
    border-radius: 7px;
    padding: 7px 10px;
    color: white;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
}

.btn-edit {
    background: #2D7A4F;
}

.btn-edit:hover {
    background: #225f3d;
}

.btn-delete {
    background: #D53E0F;
}

.btn-delete:hover {
    background: #9f2e0b;
}

.btn-restore {
    border: none;
    border-radius: 7px;
    padding: 7px 10px;
    color: white;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    background: #2D7A4F;
}

.btn-restore:hover {
    background: #225f3d;
}

.deleted-supplier-section {
    margin-top: 26px;
}

.deleted-supplier-section h3 {
    color: #1A0F0A;
    margin: 0 0 12px;
}

.empty-row {
    text-align: center;
    color: #6B4C3B;
    padding: 28px !important;
}

.supplier-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(9, 20, 19, 0.55);
    z-index: 100;
    padding: 24px;
}

.supplier-modal.open {
    display: flex;
    align-items: center;
    justify-content: center;
}

.supplier-modal-content {
    width: min(500px, 100%);
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

.supplier-form {
    display: grid;
    gap: 12px;
}

.supplier-form label {
    display: grid;
    gap: 6px;
    color: #6B4C3B;
    font-size: 13px;
    font-weight: 700;
}

.supplier-form input,
.supplier-form select,
.supplier-form textarea {
    width: 100%;
    border: 1px solid #E8D5C8;
    border-radius: 8px;
    padding: 11px 12px;
    font-size: 14px;
    color: #1A0F0A;
}

.supplier-form textarea {
    min-height: 80px;
    resize: vertical;
}

.supplier-form input:focus,
.supplier-form select:focus,
.supplier-form textarea:focus {
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
</style>

<div class="supplier-page">
    <div class="supplier-header">
        <div>
            <h2>Supplier</h2>
            <p>Supplier contacts and sourcing details</p>
        </div>
        <button class="supplier-btn" type="button" onclick="openAddSupplierModal()">
            <i class="ti ti-truck-delivery"></i>
            Add Supplier
        </button>
    </div>

    <table class="supplier-table">
        <tr>
            <th>Supplier</th>
            <th>Contact Person</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if (empty($suppliers)): ?>
            <tr>
                <td class="empty-row" colspan="6">No suppliers added yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($suppliers as $row): ?>
                <?php $isInactive = strtolower($row['Status']) !== 'active'; ?>
                <tr>
                    <td class="supplier-name"><?= htmlspecialchars($row['SupplierName']) ?></td>
                    <td class="supplier-muted"><?= htmlspecialchars($row['ContactPerson'] ?: '-') ?></td>
                    <td class="supplier-muted"><?= htmlspecialchars($row['Phone'] ?: '-') ?></td>
                    <td class="supplier-muted"><?= htmlspecialchars($row['Address'] ?: '-') ?></td>
                    <td>
                        <span class="supplier-status <?= $isInactive ? 'inactive' : '' ?>">
                            <?= htmlspecialchars($row['Status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="supplier-actions">
                            <button
                                class="btn-edit"
                                type="button"
                                data-id="<?= htmlspecialchars($row['SupplierID'], ENT_QUOTES) ?>"
                                data-name="<?= htmlspecialchars($row['SupplierName'], ENT_QUOTES) ?>"
                                data-contact="<?= htmlspecialchars($row['ContactPerson'], ENT_QUOTES) ?>"
                                data-phone="<?= htmlspecialchars($row['Phone'], ENT_QUOTES) ?>"
                                data-address="<?= htmlspecialchars($row['Address'], ENT_QUOTES) ?>"
                                data-status="<?= htmlspecialchars($row['Status'], ENT_QUOTES) ?>"
                                onclick="openEditSupplierModal(this)">
                                Edit
                            </button>
                            <a class="btn-delete"
                               href="?page=supplier&delete=<?= htmlspecialchars($row['SupplierID']) ?>"
                               data-name="<?= htmlspecialchars($row['SupplierName'], ENT_QUOTES) ?>">
                               Delete
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <div class="deleted-supplier-section">
        <h3>Deleted Suppliers</h3>
        <table class="supplier-table">
            <tr>
                <th>Supplier</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th>Deleted At</th>
                <th>Action</th>
            </tr>
            <?php if (empty($deletedSuppliers)): ?>
                <tr>
                    <td class="empty-row" colspan="5">No deleted suppliers.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($deletedSuppliers as $row): ?>
                    <tr>
                        <td class="supplier-name"><?= htmlspecialchars($row['SupplierName']) ?></td>
                        <td class="supplier-muted"><?= htmlspecialchars($row['ContactPerson'] ?: '-') ?></td>
                        <td class="supplier-muted"><?= htmlspecialchars($row['Phone'] ?: '-') ?></td>
                        <td class="supplier-muted"><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['DeletedAt']))) ?></td>
                        <td>
                            <a class="btn-restore"
                               href="?page=supplier&restore=<?= htmlspecialchars($row['SupplierID']) ?>"
                               data-name="<?= htmlspecialchars($row['SupplierName'], ENT_QUOTES) ?>">
                               Restore
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>

<div id="addSupplierModal" class="supplier-modal">
    <div class="supplier-modal-content">
        <div class="modal-title-row">
            <h3>Add Supplier</h3>
            <button class="modal-close" type="button" onclick="closeSupplierModals()">&times;</button>
        </div>

        <form class="supplier-form" method="POST">
            <label>
                Supplier Name
                <input type="text" name="supplier_name" placeholder="Supplier name" required>
            </label>
            <label>
                Contact Person
                <input type="text" name="contact_person" placeholder="Contact person">
            </label>
            <label>
                Phone
                <input type="text" name="phone" placeholder="Phone number">
            </label>
            <label>
                Address
                <textarea name="address" placeholder="Supplier address"></textarea>
            </label>
            <label>
                Status
                <select name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </label>
            <div class="modal-actions">
                <button class="supplier-btn btn-secondary" type="button" onclick="closeSupplierModals()">Cancel</button>
                <button class="supplier-btn" name="add" type="submit">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<div id="editSupplierModal" class="supplier-modal">
    <div class="supplier-modal-content">
        <div class="modal-title-row">
            <h3>Edit Supplier</h3>
            <button class="modal-close" type="button" onclick="closeSupplierModals()">&times;</button>
        </div>

        <form class="supplier-form" method="POST">
            <input type="hidden" name="supplier_id" id="editSupplierId">
            <label>
                Supplier Name
                <input type="text" name="supplier_name" id="editSupplierName" placeholder="Supplier name" required>
            </label>
            <label>
                Contact Person
                <input type="text" name="contact_person" id="editContactPerson" placeholder="Contact person">
            </label>
            <label>
                Phone
                <input type="text" name="phone" id="editPhone" placeholder="Phone number">
            </label>
            <label>
                Address
                <textarea name="address" id="editAddress" placeholder="Supplier address"></textarea>
            </label>
            <label>
                Status
                <select name="status" id="editStatus">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </label>
            <div class="modal-actions">
                <button class="supplier-btn btn-secondary" type="button" onclick="closeSupplierModals()">Cancel</button>
                <button class="supplier-btn" name="update" type="submit">Update Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddSupplierModal() {
    document.getElementById('addSupplierModal').classList.add('open');
}

function openEditSupplierModal(button) {
    document.getElementById('editSupplierId').value = button.dataset.id;
    document.getElementById('editSupplierName').value = button.dataset.name;
    document.getElementById('editContactPerson').value = button.dataset.contact;
    document.getElementById('editPhone').value = button.dataset.phone;
    document.getElementById('editAddress').value = button.dataset.address;
    document.getElementById('editStatus').value = button.dataset.status;
    document.getElementById('editSupplierModal').classList.add('open');
}

function closeSupplierModals() {
    document.querySelectorAll('.supplier-modal').forEach(function(modal) {
        modal.classList.remove('open');
    });
}

document.querySelectorAll('.supplier-modal').forEach(function(modal) {
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeSupplierModals();
        }
    });
});

document.querySelectorAll('.btn-delete').forEach(function(button) {
    button.addEventListener('click', function(event) {
        event.preventDefault();

        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            title: 'Delete supplier?',
            text: button.dataset.name + ' will be removed from supplier records.',
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

document.querySelectorAll('.btn-restore').forEach(function(button) {
    button.addEventListener('click', function(event) {
        event.preventDefault();

        if (typeof Swal === 'undefined') {
            window.location.href = button.href;
            return;
        }

        Swal.fire({
            title: 'Restore supplier?',
            text: button.dataset.name + ' will return to active supplier records.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2D7A4F',
            cancelButtonColor: '#6B4C3B',
            confirmButtonText: 'Yes, restore',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = button.href;
            }
        });
    });
});

<?php if ($supplierDeleted): ?>
if (typeof Swal !== 'undefined') {
    Swal.fire({
        title: 'Deleted',
        text: 'Supplier has been removed.',
        icon: 'success',
        confirmButtonColor: '#B85C38'
    });
}

if (window.history.replaceState) {
    window.history.replaceState(null, '', '?page=supplier');
}
<?php endif; ?>
</script>
