<?php
// Function to insert an entry into the admin_activity_log table

/**
 * Logs an activity performed by an admin user.
 * * @param mysqli $conn The active database connection object.
 * @param string $actionType The type of action (e.g., 'ADD', 'EDIT', 'DELETE', 'LOGIN').
 * @param string $tableAffected The name of the table affected (e.g., 'merch', 'projects').
 * @param string $summary A brief description of the action (e.g., 'updated item: T-shirt', 'added new Project: Apollo').
 * @param int|null $recordId The ID of the record that was affected.
 * @return bool True on success, false on failure.
 */
function log_admin_activity($conn, $actionType, $tableAffected, $summary, $recordId = null) {
    // SECURITY CHECK: Ensure user details are available from the session
    if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_username'])) {
        error_log("Attempt to log activity without session data.");
        return false;
    }
    
    $userId = $_SESSION['admin_user_id'];
    $username = $_SESSION['admin_username'];
    
    $stmt = $conn->prepare("
        INSERT INTO admin_activity_log (user_id, username, action_type, table_affected, record_id, summary) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // 'isssis' specifies the data types: Integer, String, String, String, Integer, String
    // We use 's' for record_id if it's null, but we'll manually handle the binding for integer
    if ($recordId === null) {
        $recordId = 0; // Use 0 or NULL if your column allows NULL, but 0 is safer for prepared statements
    }

    $stmt->bind_param("isssis", 
        $userId, 
        $username, 
        $actionType, 
        $tableAffected, 
        $recordId, 
        $summary
    );
    
    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        error_log("Failed to insert into activity log: " . $conn->error);
    }
    
    return $success;
}
?>