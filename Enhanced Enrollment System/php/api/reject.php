<?php
/**
 * Student Registration Rejection API
 * Returns the student's submission to they can correct it, with remarks.
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

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 1. Verify Staff Session
if (!isset($_SESSION['staff_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Staff authentication required."
    ]);
    exit;
}

$staffId = $_SESSION['staff_id'];
$staffName = $_SESSION['staff_name'];
$staffRole = $_SESSION['staff_role'];

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$studentId = isset($data['student_id']) ? trim($data['student_id']) : null;
$reason = isset($data['reason']) ? trim($data['reason']) : null;

if (empty($studentId)) {
    // Fallback to POST
    $studentId = isset($_POST['student_id']) ? trim($_POST['student_id']) : null;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;
}

if (empty($studentId) || empty($reason)) {
    echo json_encode([
        "success" => false,
        "message" => "Student ID and Rejection Reason are required."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    // Verify student exists and status is either 'Pending' or 'Approved by Dept Head'
    $stmtCheck = $db->prepare("SELECT name, approval_status, program_code FROM students WHERE student_id = :student_id LIMIT 1");
    $stmtCheck->execute([':student_id' => $studentId]);
    $student = $stmtCheck->fetch();

    if (!$student) {
        echo json_encode([
            "success" => false,
            "message" => "Student not found."
        ]);
        exit;
    }

    // Verify role permissions
    if ($staffRole === 'Dept Head' && $student['approval_status'] !== 'Pending') {
        echo json_encode([
            "success" => false,
            "message" => "Department Head can only reject files that are 'Pending'."
        ]);
        exit;
    }

    if ($staffRole === 'Registrar' && $student['approval_status'] !== 'Approved by Dept Head') {
        echo json_encode([
            "success" => false,
            "message" => "Registrar can only reject files that are 'Approved by Dept Head'."
        ]);
        exit;
    }

    // Start Transaction
    $db->beginTransaction();

    // Clear selections on rejection so they start fresh
    $stmtClear = $db->prepare("DELETE FROM student_enrollment_selections WHERE student_id = :student_id");
    $stmtClear->execute([':student_id' => $studentId]);

    // Update status to 'Rejected'
    $stmtUpdate = $db->prepare("
        UPDATE students 
        SET approval_status = 'Rejected' 
        WHERE student_id = :student_id
    ");
    $stmtUpdate->execute([':student_id' => $studentId]);

    // Insert Audit Log entry
    $stmtLog = $db->prepare("
        INSERT INTO audit_logs (staff_id, student_id, action, details)
        VALUES (:staff_id, :student_id, 'Rejection', :details)
    ");
    $details = "Returned by {$staffRole} ({$staffName}). Remarks: {$reason}";
    $stmtLog->execute([
        ':staff_id' => $staffId,
        ':student_id' => $studentId,
        ':details' => $details
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "Student registration successfully returned with comments."
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Error rejecting registration: " . $e->getMessage()
    ]);
}
?>
