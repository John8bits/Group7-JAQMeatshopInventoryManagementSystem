<?php
    
require_once "../DatabaseConnection/database.php";
$db = new Database();
$conn = $db->conn;

    if (isset($_POST['add'])) {
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users (username, Password, role)
            VALUES (?, ?, 'cashier')
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['username'],
            $hashedPassword,
        ]);
    }


    if (isset($_GET['delete'])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE Role = 'cashier'");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
.container {
    padding: 20px;
    font-family: Arial;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h2 {
    margin: 0;
}


.btn {
    background: #D53E0F;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.btn:hover {
    background: #b7320c;
}


.form-box {
    background: white;
    padding: 15px;
    margin-top: 15px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.form-box input {
    width: 45%;
    padding: 10px;
    margin: 5px;
    border: 1px solid #ccc;
    border-radius: 8px;
}


table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

th {
    background: #091413;
    color: white;
    padding: 12px;
}

td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background: #f5f5f5;
}


.delete {
    color: white;
    background: red;
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
}

.delete:hover {
    background: darkred;
}
</style>

<div class="container">

    <div class="header">
        <h2>Manage Cashiers</h2>
    </div>

    <div class="form-box">
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button class="btn" name="add">+ Add Cashier</button>
        </form>
    </div>

    
    <table>
        <tr>
            <th>Username</th>
            <th>Action</th>
        </tr>

        <?php foreach ($users as $row): ?>
        <tr>
            <td><?= $row['username'] ?></td>
            <td>
                <a class="delete" 
                   href="?page=cashier&delete=<?= $row['id'] ?>"
                   onclick="return confirm('Delete cashier?')">
                   Delete
                </a>
            </td>
        </tr>
        <?php endforeach; ?>

    </table>

</div>