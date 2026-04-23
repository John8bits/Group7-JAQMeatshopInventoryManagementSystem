<?php 

    session_start();
    $errors = $_SESSION['errors'] ?? [];
    $old_input = $_SESSION['old_val'] ?? '';
    $pass_old = $_SESSION['pass_old'] ?? '';

    unset($_SESSION['errors'], $_SESSION['old_val'], $_SESSION['pass_old']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/login_style.css">
    <!-- <style>
        
    * {
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }   

    body {
        margin: 0;
        height: 100vh;
        /* background: linear-gradient(135deg, #D53E0F, #FF7A3D); */
        background: #091413;
        display: flex;
        align-items: center;
        justify-content: center;
        background-image: url('bg_login.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .container {
        background: #fff;
        color: #333;
        padding: 40px 30px;
        border-radius: 10px;
        width: 320px;
        /* width:380px; */
        /* height:50%; */
        box-shadow: 0 1px 5px rgba(236, 233, 233, 0.94); 
        text-align: center;
        /* border:4px solid black; */
    }

    h2 {
        margin-bottom: 30px;
        margin-top:-2px;
        /* color: #D53E0F; */
        color:darkred;
    }

    input, select {
        width: 100%;
        padding: 10px;
        margin-bottom: 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
    }

    input:focus, select:focus {
        outline: none;
        border-color: #D53E0F;
    }

    button {
        width: 100%;
        padding: 10px;
        background: #D53E0F;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
    }

    button:hover {
        background: #b7320c;
    }

    .error {
        background: #ffe0e0;
        color: #a10000;
        padding: 8px;
        margin-bottom: 10px;
        border-radius: 5px;
        font-size: 13px;
    }

    .password-c {
        position: relative;
    }

    .password-c input {
        width: 100%;
        padding-right: 40px;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        top: 40%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #555;
        font-size: 16px;
    }

    .toggle-password:hover {
        color: black;
    }

    footer{
        position: absolute;
        margin:1% 0 0 5%;
        font-size: 10px;
    }


    </style> -->
</head>
<body>
<!-- 
<img src="bg_login.png" style="width:500px; "> -->

<div class ="container">
    
    <h2>LOGIN</h2>
    <form method="POST" action="login_process.php">

        <?php if (!empty($errors['invalid'])): ?>
            <div class="error"><?= $errors['invalid']; ?></div>
        <?php endif; ?>
        
        <input type="text" name="username" placeholder="Username" value="<?=htmlspecialchars($old_input); ?>">

        <?php if (!empty($errors['empty_username'])): ?>
            <div class="error"><?= $errors['empty_username']; ?></div>
        <?php endif; ?>

        <div class="password-c">
            <input type="password" name="password" id="password" placeholder="Password" value="<?=htmlspecialchars($pass_old);?>">
            <i class="fa-solid fa-eye toggle-password" id="eye" onclick="togglePassword()"></i>
        </div>

        <?php if (!empty($errors['empty_password'])): ?>
            <div class="error"><?= $errors['empty_password']; ?></div>
        <?php endif; ?>

        <select name="role">
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="cashier">Cashier</option>
        </select>

        <?php if(!empty($errors['empty_role'])): ?>
            <div class="error"><?= $errors['empty_role'];?></div>
        <?php endif;?>
        <button type="submit">Sign in</button>
    </form>
    <footer>&COPY;JAQ Meatshop</footer>
    
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById("password");
        const eye = document.getElementById("eye");

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            eye.classList.remove("fa-eye");
            eye.classList.add("fa-eye-slash");
        } else {
            passwordInput.type = "password";
            eye.classList.remove("fa-eye-slash");
            eye.classList.add("fa-eye");
        }
    }
</script>
    
</body>
</html>
