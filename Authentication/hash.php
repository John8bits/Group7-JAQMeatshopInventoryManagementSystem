<?php
$password = "cashier2"; 
$hash = password_hash($password, PASSWORD_DEFAULT);

echo $hash;

?>