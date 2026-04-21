<?php
$password = "cashier123"; 
$hash = password_hash($password, PASSWORD_DEFAULT);

echo $hash;

?>