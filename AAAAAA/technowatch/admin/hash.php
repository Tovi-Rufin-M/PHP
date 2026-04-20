<?php
// Temporary file to generate a password hash
$password = 'admin123'; 
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Use this hash for 'pass123': " . $hashed_password;
?>