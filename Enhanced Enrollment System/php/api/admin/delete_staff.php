<?php
/**
 * Admin Delete Staff API
 * Deletes a staff account (with self-deletion protection).
 */

header('Content-Type: application/json');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 1. Verify Admin Session
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Admin') {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Administrator access only."
    ]);
    exit;
}

$currentAdminId = $_SESSION['staff_id'];

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$staffId = isset($data['staff_id']) ? trim($data['staff_id']) : '';

if (empty($staffId)) {
    // Fallback to POST
    $staffId = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : '';
}

if (empty($staffId)) {
    echo json_encode([
        "success" => false,
        "message" => "Staff ID is required for deletion."
    ]);
    exit;
}

// 3. Self Deletion Protection
if ($staffId === $currentAdminId) {
    echo json_encode([
        "success" => false,
        "message" => "Security constraint: You cannot delete your own active administrator account."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    // Verify staff member exists
    $stmtCheck = $db->prepare("SELECT name FROM staff WHERE staff_id = ? LIMIT 1");
    $stmtCheck->execute([$staffId]);
    $staffName = $stmtCheck->fetchColumn();

    if (!$staffName) {
        echo json_encode([
            "success" => false,
            "message" => "Staff profile not found."
        ]);
        exit;
    }

    // Delete staff
    $stmtDelete = $db->prepare("DELETE FROM staff WHERE staff_id = ?");
    $stmtDelete->execute([$staffId]);

    echo json_encode([
        "success" => true,
        "message" => "Staff account '$staffName' ($staffId) was deleted successfully."
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error during deletion: " . $e->getMessage()
    ]);
}
?>
