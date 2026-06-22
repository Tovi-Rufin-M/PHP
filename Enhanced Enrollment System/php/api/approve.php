<?php
/**
 * Department Head Approval API
 * Approves a student's enrollment and forwards it to the Registrar.
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

// 1. Verify Staff Session and Role
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Dept Head') {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Academic Department Head access only."
    ]);
    exit;
}

$staffId = $_SESSION['staff_id'];
$staffName = $_SESSION['staff_name'];

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$studentId = isset($data['student_id']) ? trim($data['student_id']) : null;

if (empty($studentId)) {
    // Fallback to POST
    $studentId = isset($_POST['student_id']) ? trim($_POST['student_id']) : null;
}

if (empty($studentId)) {
    echo json_encode([
        "success" => false,
        "message" => "Student ID is required for approval."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    // Verify student exists and is currently in 'Pending' status
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

    if ($student['approval_status'] !== 'Pending') {
        echo json_encode([
            "success" => false,
            "message" => "Cannot approve. Student status is currently '{$student['approval_status']}', not 'Pending'."
        ]);
        exit;
    }

    // Verify program code matches head's program code if restricted
    if (isset($_SESSION['program_code']) && $_SESSION['program_code'] !== $student['program_code']) {
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized. You can only approve students within your own department ({$_SESSION['program_code']})."
        ]);
        exit;
    }

    // Start Transaction
    $db->beginTransaction();

    // Update status to 'Approved by Dept Head'
    $stmtUpdate = $db->prepare("
        UPDATE students 
        SET approval_status = 'Approved by Dept Head' 
        WHERE student_id = :student_id
    ");
    $stmtUpdate->execute([':student_id' => $studentId]);

    // Insert Audit Log entry
    $stmtLog = $db->prepare("
        INSERT INTO audit_logs (staff_id, student_id, action, details)
        VALUES (:staff_id, :student_id, 'Department Head Approval', :details)
    ");
    $details = "Department Head {$staffName} approved enrollment registration files.";
    $stmtLog->execute([
        ':staff_id' => $staffId,
        ':student_id' => $studentId,
        ':details' => $details
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "Student registration successfully approved and forwarded to Registrar."
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Error approving registration: " . $e->getMessage()
    ]);
}
?>
