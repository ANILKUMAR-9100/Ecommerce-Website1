<?php
$password = "12332100"; // Change this to the new password you want
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "New Hashed Password: " . $hashed_password;
?>
