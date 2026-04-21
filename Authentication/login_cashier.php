<?php 
    session_start();
    $errors = $_SESSION['errors'] ?? [];

    unset($_SESSION['errors']);
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel="stylesheet"href="../css/cashier_login.css">
</head>

<body>

<div class="login-box">

    <h2>Cashier Login</h2>

    <form method="post" action="cashier_validation.php">

        <input type="text" name="cashier" placeholder="Cashier Username" >

        <?php if(!empty($errors['cashier_empty_input'])): ?>
            <div class="error"> <?= $errors['cashier_empty_input']; ?></div>
        <?php endif;?>

        <?php if(!empty($errors['spec'])): ?>
            <div class="error"> <?= $errors['spec']; ?></div>
        <?php endif;?>

        <input type="password" name="password" placeholder="Password" >

        <?php if(!empty($errors['password_empty_input'])): ?>
            <div class="error"> <?= $errors['password_empty_input']; ?></div>
        <?php endif;?>

        <button type="submit">Login</button>

    </form>

    <div class="go_to_admin">
        <a href="login_admin.php">Login as Admin</a>
    </div>

</div>

</body>
</html>