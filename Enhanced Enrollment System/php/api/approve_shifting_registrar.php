<?php
/**
 * Registrar Final Shifting Confirmation API
 * Updates the student's program and section, clears enrollment selections, and resets term.
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

// 1. Verify Staff Session & Role
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Registrar') {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Registrar access only."
    ]);
    exit;
}

$staffId = $_SESSION['staff_id'];
$staffName = $_SESSION['staff_name'];

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$requestId = isset($data['request_id']) ? intval($data['request_id']) : null;

if (empty($requestId)) {
    echo json_encode([
        "success" => false,
        "message" => "Shifting Request ID is required."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed.");
    }

    // 3. Retrieve and verify the request
    $stmtCheck = $db->prepare("
        SELECT r.*, s.name as student_name 
        FROM shifting_requests r
        JOIN students s ON r.student_id = s.student_id
        WHERE r.id = :id LIMIT 1
    ");
    $stmtCheck->execute([':id' => $requestId]);
    $request = $stmtCheck->fetch();

    if (!$request) {
        echo json_encode([
            "success" => false,
            "message" => "Shifting request not found."
        ]);
        exit;
    }

    if ($request['status'] !== 'Approved by Dept Head') {
        echo json_encode([
            "success" => false,
            "message" => "Cannot finalize. Request status must be 'Approved by Dept Head', currently '{$request['status']}'."
        ]);
        exit;
    }

    // 4. Execute Shifting Database Updates in a Transaction
    $db->beginTransaction();

    $studentId = $request['student_id'];
    $targetProgram = $request['target_program_code'];
    $targetSection = $request['target_section'];

    // Update student's course, section, and reset term to first term of second year
    $stmtUpdateStudent = $db->prepare("
        UPDATE students 
        SET program_code = :program_code,
            section = :section,
            current_term = 'First Term',
            approval_status = 'Pending'
        WHERE student_id = :student_id
    ");
    $stmtUpdateStudent->execute([
        ':program_code' => $targetProgram,
        ':section' => $targetSection,
        ':student_id' => $studentId
    ]);

    // Clear old enrollment selections (first year selections) for this student
    $stmtClearSelections = $db->prepare("DELETE FROM student_enrollment_selections WHERE student_id = :student_id");
    $stmtClearSelections->execute([':student_id' => $studentId]);

    // Update shifting request status to 'Approved'
    $stmtUpdateRequest = $db->prepare("UPDATE shifting_requests SET status = 'Approved' WHERE id = :id");
    $stmtUpdateRequest->execute([':id' => $requestId]);

    // Log action in audit logs
    $stmtLog = $db->prepare("
        INSERT INTO audit_logs (staff_id, student_id, action, details)
        VALUES (:staff_id, :student_id, 'Shifting Registrar Approval', :details)
    ");
    $stmtLog->execute([
        ':staff_id' => $staffId,
        ':student_id' => $studentId,
        ':details' => "Registrar finalized major shift. Student shifted to {$targetProgram} - {$targetSection}. Session reset for Second Year First Term."
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "Shifting request finalized successfully. Student {$request['student_name']} is now shifted to {$targetProgram} - {$targetSection}."
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Error finalizing shifting request: " . $e->getMessage()
    ]);
}
