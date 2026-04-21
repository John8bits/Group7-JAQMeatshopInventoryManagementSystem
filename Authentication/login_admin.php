<?php 
    session_start();
    $errors = $_SESSION['errors'] ?? [];

    unset($_SESSION['errors']);
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel="stylesheet" href="../css/admin_login.css">
</head>

<body>

<div class="login-box">

    <h2>Admin Login</h2>

    <form action="admin_validation.php" method="POST">

        <input type="text" name="admin" placeholder="Admin" >

        <?php if(!empty($errors['admin_empty_input'])): ?>
            <div class="error"> <?= $errors['admin_empty_input']; ?></div>
        <?php endif;?>
        
        <input type="password" name="password" placeholder="Password" >
        <?php if(!empty($errors['password_empty_input'])): ?>
            <div class="error"> <?= $errors['password_empty_input']; ?></div>
        <?php endif;?>

        <button type="submit">Login</button>

    </form>

    <div class="go_to_cashier">
        <a href="login_cashier.php">Login as Cashier</a>
    </div>

</div>

</body>
</html>