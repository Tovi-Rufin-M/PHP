<?php
/**
 * Admin Delete Student API
 * Deletes a student profile and cascades related table constraints.
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

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$studentId = isset($data['student_id']) ? trim($data['student_id']) : '';

if (empty($studentId)) {
    // Fallback to POST
    $studentId = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
}

if (empty($studentId)) {
    echo json_encode([
        "success" => false,
        "message" => "Student ID is required for deletion."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    // Verify student exists
    $stmtCheck = $db->prepare("SELECT name FROM students WHERE student_id = ? LIMIT 1");
    $stmtCheck->execute([$studentId]);
    $studentName = $stmtCheck->fetchColumn();

    if (!$studentName) {
        echo json_encode([
            "success" => false,
            "message" => "Student profile not found."
        ]);
        exit;
    }

    // Delete student
    $stmtDelete = $db->prepare("DELETE FROM students WHERE student_id = ?");
    $stmtDelete->execute([$studentId]);

    echo json_encode([
        "success" => true,
        "message" => "Student profile '$studentName' ($studentId) was deleted successfully."
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error during deletion: " . $e->getMessage()
    ]);
}
?>
