<?php
// technowatch/db_connect.php - Local (XAMPP/MAMP) Database Connection

// --- Database credentials for LOCALHOST (XAMPP/MAMP) ---
$host = 'localhost';
// IMPORTANT: Change this to the database name you created in XAMPP phpMyAdmin
$db_name = 'technowatchclub_db'; 
$username = 'root';
// IMPORTANT: Change this if you have set a password for the root user in XAMPP
$password = ''; 

// Create a new MySQLi connection
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    // Die with the error if the connection fails locally
    die("❌ Localhost Database Connection failed: " . $conn->connect_error);
}

// Set character set to UTF8MB4
$conn->set_charset("utf8mb4");

// NOTE: The connection object $conn is now available for use in organization.php
?>