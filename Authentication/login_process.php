<?php
    require_once "../DatabaseConnection/database.php";
    session_start();

    if ($_SERVER['REQUEST_METHOD'] !== "POST") {
        header("Location: login.php");
        exit;
    }


    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    $role = trim(filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS));

    $errors = [];
    $allowedRoles = ['admin', 'cashier'];



    if (empty($username)) {
        $errors['empty_username'] = "Username is required!";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['invalid_username'] = "Username must be 3–50 characters.";
    }

    if (empty($password)) {
        $errors['empty_password'] = "Password is required!";
    } elseif (strlen($password) < 6) {
        $errors['invalid_password'] = "Password must be at least 6 characters.";
    }

    if (empty($role)) {
        $errors['empty_role'] = "Please choose a role!";
    } elseif (!in_array($role, $allowedRoles)) {
        $errors['invalid_role'] = "Invalid role selected.";
    }


    $_SESSION['old_val'] = $username;
    $_SESSION['old_role'] = $role;


    if (empty($errors)) {
        try {
            $db = new Database();
            $conn = $db->conn;

            $stmt = $conn->prepare("
                SELECT id, username, password, role
                FROM users
                WHERE username = :username AND role = :role
                LIMIT 1
            ");

            $stmt->execute([
                ':username' => $username,
                ':role' => $role
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
                $_SESSION['role'] = $user['role'];

                unset($_SESSION['old_val'], $_SESSION['old_role'], $_SESSION['errors']);

                if ($user['role'] === 'admin') {
                    header("Location: ../dashboards/admin_dashboard.php");
                } else {
                    header("Location: ../dashboards/cashier_dashboard.php");
                }
                exit;

            } else {
                $errors['invalid'] = "Invalid username, password, or role!";
            }

        } catch (PDOException $e) {
            $errors['database'] = "Database error. Please try again later.";
        }
    }

    $_SESSION['errors'] = $errors;
    header("Location: login.php");
    exit;
?>
