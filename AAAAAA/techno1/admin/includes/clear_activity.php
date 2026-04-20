<?php
// technowatch/admin/clear_activity.php - Clears the admin activity log

session_start();
header('Content-Type: application/json');

// Security Check: Only logged-in admins can access this
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Check if the request method is POST for security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

include 'db_connect.php';

$tableName = 'admin_activity_log';
$truncate_query = "TRUNCATE TABLE `$tableName`";

if ($conn->query($truncate_query)) {
    // Log the action itself before closing the connection (optional, but good practice)
    // NOTE: If you log this TRUNCATE, it will be the ONLY entry immediately after!
    // $username = $_SESSION['admin_username'] ?? 'Admin'; 
    // $summary = 'Cleared all admin activity logs.';
    // $log_query = "INSERT INTO admin_activity_log (username, summary, timestamp) VALUES (?, ?, NOW())";
    // $stmt = $conn->prepare($log_query);
    // $stmt->bind_param("ss", $username, $summary);
    // $stmt->execute();

    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Admin activity log cleared successfully.']);
} else {
    // Log the error for internal review
    error_log("DB Error truncating $tableName: " . $conn->error);
    $conn->close();
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error: Could not clear log.']);
}
?>