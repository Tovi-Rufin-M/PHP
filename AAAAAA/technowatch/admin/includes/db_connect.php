<?php
// technowatch/db_connect.php - Front-end Database Connection

// Database credentials
$host = 'localhost';
$db_name = 'technowatchclub_db';
$username = 'root';
$password = ''; 

// Create a new MySQLi connection
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    // In a live environment, you would log the error and display a user-friendly message,
    // but we'll die() for debugging now.
    die("Front-end Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Function to safely close the connection if needed, though PHP handles it.
// function close_db_connection() { global $conn; $conn->close(); }
?>