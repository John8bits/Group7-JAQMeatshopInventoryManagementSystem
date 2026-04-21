<?php
    session_start();

    if($_SERVER['REQUEST_METHOD']=="POST"){
        $cashier = $_POST['cashier'];
        $password = $_POST['password'];

        $errors = [];

        if(empty($cashier)){
            $errors['cashier_empty_input'] = "ERROR: Cashier is empty!";
        }else{
            // $sc = filter_input(INPUT_POST,$cashier,FILTER_SANITIZE_STRING);
            $sanitize_cashier = htmlspecialchars(filter_var($cashier,FILTER_SANITIZE_STRING),ENT_QUOTES,'UTF-8');
            // echo htmlspecialchars($sanitize_cashier,ENT_QUOTES, 'UTF-8');
        }

        if(empty($password)){
            $errors['password_empty_input'] = "ERROR: Password is empty!";
        }else{ 
            // $sanitize_cashier = filter_var($cashier,FILTER_SANITIZE_SPECIAL_CHARS);
            $sanitize_pass = htmlspecialchars(filter_var($password,FILTER_SANITIZE_STRING),ENT_QUOTES, 'UTF-8');
        }

        if(!empty($errors)){
            $_SESSION['errors'] = $errors;
            header("Location: login_cashier.php");
            exit;
        }


    }
?>