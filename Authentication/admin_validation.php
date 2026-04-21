<?php
    require_once "../DatabaseConnection/database.php";
    session_start();

    if($_SERVER['REQUEST_METHOD']=="POST"){
        $admin = $_POST['admin'];
        // $password = $_POST['password'];
        $password = trim($_POST['password']);

        $errors = [];

        if(empty($admin)){
            $errors['admin_empty_input'] = "ERROR: Admin is empty!";
        }else{
            $sanitize_admin = htmlspecialchars(filter_var($admin, FILTER_SANITIZE_STRING), ENT_QUOTES, 'UTF-8');
            echo $sanitize_admin;
        }

        if(empty($password)){
            $errors['password_empty_input'] = "ERROR: Password is empty!";
        }

        if(!empty($errors)){
            $_SESSION['errors'] = $errors;
            header("Location: login_admin.php");
            exit;
        }

        // $sanitize_admin = filter_var($admin,FILTER_SANITIZE_SPECIAL_CHARS);
        // $sanitize_pass = filter_var($password,FILTER_SANITIZE_SPECIAL_CHARS);

        $db = new Database();
        $conn = $db->conn;
        
        $stmt = $conn->prepare("SELECT * FROM admins WHERE admin = :admin");
        $stmt->execute([
            'admin'=>$admin
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user){
            if(password_verify($user,$user['password'])){
                $_SESSION['admin'] = $user['admin_username'];
                $_SESSION['logged'] = true;

                header('Location:../dashboards/admin_dashboard.php');
                exit;
            }else $errors['admin_wrong_pass'] = "Wrong Password!"; 

        }else $errors['admin_wrong_username'] = "Admin doesn't exist!";

    }
?>