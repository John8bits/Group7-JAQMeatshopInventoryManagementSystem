<?php
require_once "../DatabaseConnection/database.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $errors = [];

    if (empty($username)) {
        $errors['empty_username'] = "Username is required!";
    }else $_SESSION['old_val'] = $username;

    if (empty($password)) {
        $errors['empty_password'] = "Password is required!";
    }else $_SESSION['pass_old'] = $password;

    if(empty($role)){
        $errors['empty_role'] = "Please choose a role!";
    }

    if (empty($errors)) {

        $db = new Database();
        $conn = $db->conn;

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND role = :role");
        $stmt->execute([
            ':username' => $username,
            ':role' => $role
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            // session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: ../dashboards/admin_dashboard.php"); 
                exit;
            } else {
                header("Location: ../dashboards/cashier_dashboard.php");
                exit;
            }

        } else {
            $errors['invalid'] = "Invalid Login!";
        }
    }

    $_SESSION['errors'] = $errors;
    header("Location: login.php");
    exit;

}