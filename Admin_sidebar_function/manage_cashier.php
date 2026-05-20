<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

$nameColumnStmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'cashier_name'");
$nameColumnStmt->execute();
if (!$nameColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE users ADD cashier_name VARCHAR(100) NULL AFTER id");
}

if (isset($_POST['add'])) {
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users (cashier_name, username, password, role)
        VALUES (?, ?, ?, 'cashier')
    ");
    $stmt->execute([
        trim($_POST['cashier_name']),
        trim($_POST['username']),
        $hashedPassword,
    ]);
}

if (isset($_POST['update'])) {
    $params = [
        trim($_POST['cashier_name']),
        trim($_POST['username']),
    ];
    $passwordSql = '';

    if (!empty($_POST['password'])) {
        $passwordSql = ', password = ?';
        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    $params[] = (int)$_POST['cashier_id'];

    $stmt = $conn->prepare("
        UPDATE users
        SET cashier_name = ?, username = ?{$passwordSql}
        WHERE id = ? AND role = 'cashier'
    ");
    $stmt->execute($params);
}

$cashierDeleted = false;

if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'cashier'");
    $stmt->execute([(int)$_GET['delete']]);
    $cashierDeleted = true;
}

$stmt = $conn->prepare("
    SELECT id, cashier_name, username
    FROM users
    WHERE role = 'cashier'
    ORDER BY cashier_name, username
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.cashier-page {
    font-family: Arial, sans-serif;
}

.cashier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
}

.cashier-header h2 {
    color: #1A0F0A;
    margin: 0;
}

.cashier-header p {
    color: #6B4C3B;
    margin: 4px 0 0;
    font-size: 14px;
}

.cashier-btn {
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

.cashier-btn:hover {
    background: #7A3520;
}

.cashier-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border: 1px solid #E8D5C8;
    border-radius: 10px;
    overflow: hidden;
}

.cashier-table th {
    background: #091413;
    color: white;
    padding: 13px 14px;
    text-align: left;
    font-size: 13px;
}

.cashier-table td {
    padding: 14px;
    border-bottom: 1px solid #F0E2D8;
    color: #1A0F0A;
    vertical-align: middle;
}

.cashier-table tr:last-child td {
    border-bottom: none;
}

.cashier-table tr:hover {
    background: #FDF8F5;
}

.cashier-name {
    font-weight: 700;
}

.cashier-username {
    color: #6B4C3B;
    font-family: Arial, sans-serif;
}

.action-cell {
    display: flex;
    gap: 8px;
    justify-content: flex-start;
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

.empty-row {
    text-align: center;
    color: #6B4C3B;
    padding: 28px !important;
}

.cashier-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(9, 20, 19, 0.55);
    z-index: 100;
    padding: 24px;
}

.cashier-modal.open {
    display: flex;
    align-items: center;
    justify-content: center;
}

.cashier-modal-content {
    width: min(430px, 100%);
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

.cashier-form {
    display: grid;
    gap: 12px;
}

.cashier-form label {
    display: grid;
    gap: 6px;
    color: #6B4C3B;
    font-size: 13px;
    font-weight: 700;
}

.cashier-form input {
    width: 100%;
    border: 1px solid #E8D5C8;
    border-radius: 8px;
    padding: 11px 12px;
    font-size: 14px;
    color: #1A0F0A;
}

.cashier-form input:focus {
    outline: none;
    border-color: #B85C38;
    box-shadow: 0 0 0 3px rgba(184, 92, 56, 0.14);
}

.form-note {
    color: #6B4C3B;
    font-size: 12px;
    margin-top: -4px;
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

<div class="cashier-page">
    <div class="cashier-header">
        <div>
            <h2>Manage Cashiers</h2>
            <p>Cashier accounts and access controls</p>
        </div>
        <button class="cashier-btn" type="button" onclick="openAddCashierModal()">
            <i class="ti ti-user-plus"></i>
            Add New Cashier
        </button>
    </div>

    <table class="cashier-table">
        <tr>
            <th>Cashier Name</th>
            <th>Username</th>
            <th>Action</th>
        </tr>

        <?php if (empty($users)): ?>
            <tr>
                <td class="empty-row" colspan="3">No cashier accounts yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($users as $row): ?>
            <tr>
                <td class="cashier-name">
                    <?= htmlspecialchars($row['cashier_name'] ?: $row['username']) ?>
                </td>
                <td class="cashier-username">
                    <?= htmlspecialchars($row['username']) ?>
                </td>
                <td>
                    <div class="action-cell">
                        <button
                            class="btn-edit"
                            type="button"
                            data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES) ?>"
                            data-name="<?= htmlspecialchars($row['cashier_name'] ?: $row['username'], ENT_QUOTES) ?>"
                            data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>"
                            onclick="openEditCashierModal(this)">
                            Edit
                        </button>
                        <a class="btn-delete"
                           href="?page=cashier&delete=<?= htmlspecialchars($row['id']) ?>"
                           data-name="<?= htmlspecialchars($row['cashier_name'] ?: $row['username'], ENT_QUOTES) ?>">
                           Delete
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

<div id="addCashierModal" class="cashier-modal">
    <div class="cashier-modal-content">
        <div class="modal-title-row">
            <h3>Add New Cashier</h3>
            <button class="modal-close" type="button" onclick="closeCashierModals()">&times;</button>
        </div>

        <form class="cashier-form" method="POST">
            <label>
                Cashier Name
                <input type="text" name="cashier_name" placeholder="Full name" required>
            </label>
            <label>
                Username
                <input type="text" name="username" placeholder="Username" required>
            </label>
            <label>
                Password
                <input type="password" name="password" placeholder="Password" required>
            </label>
            <div class="modal-actions">
                <button class="cashier-btn btn-secondary" type="button" onclick="closeCashierModals()">Cancel</button>
                <button class="cashier-btn" name="add" type="submit">Save Cashier</button>
            </div>
        </form>
    </div>
</div>

<div id="editCashierModal" class="cashier-modal">
    <div class="cashier-modal-content">
        <div class="modal-title-row">
            <h3>Edit Cashier</h3>
            <button class="modal-close" type="button" onclick="closeCashierModals()">&times;</button>
        </div>

        <form class="cashier-form" method="POST">
            <input type="hidden" name="cashier_id" id="editCashierId">
            <label>
                Cashier Name
                <input type="text" name="cashier_name" id="editCashierName" placeholder="Full name" required>
            </label>
            <label>
                Username
                <input type="text" name="username" id="editCashierUsername" placeholder="Username" required>
            </label>
            <label>
                New Password
                <input type="password" name="password" placeholder="Leave blank to keep current password">
            </label>
            <div class="form-note">Password is only changed when this field is filled.</div>
            <div class="modal-actions">
                <button class="cashier-btn btn-secondary" type="button" onclick="closeCashierModals()">Cancel</button>
                <button class="cashier-btn" name="update" type="submit">Update Cashier</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCashierModal() {
    document.getElementById('addCashierModal').classList.add('open');
}

function openEditCashierModal(button) {
    document.getElementById('editCashierId').value = button.dataset.id;
    document.getElementById('editCashierName').value = button.dataset.name;
    document.getElementById('editCashierUsername').value = button.dataset.username;
    document.getElementById('editCashierModal').classList.add('open');
}

function closeCashierModals() {
    document.querySelectorAll('.cashier-modal').forEach(function(modal) {
        modal.classList.remove('open');
    });
}

document.querySelectorAll('.cashier-modal').forEach(function(modal) {
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeCashierModals();
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
            title: 'Delete cashier?',
            text: button.dataset.name + ' will be removed from cashier access.',
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

<?php if ($cashierDeleted): ?>
if (typeof Swal !== 'undefined') {
    Swal.fire({
        title: 'Deleted',
        text: 'Cashier account has been removed.',
        icon: 'success',
        confirmButtonColor: '#B85C38'
    });
}

if (window.history.replaceState) {
    window.history.replaceState(null, '', '?page=cashier');
}
<?php endif; ?>
</script>
