<?php
/**
 * Department Head Shifting Approval API
 * Approves a shifting request and assigns a section.
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
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Dept Head') {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Department Head access only."
    ]);
    exit;
}

$staffId = $_SESSION['staff_id'];
$staffProgram = $_SESSION['program_code']; // The program this head governs (e.g. BET-09-V)

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$requestId = isset($data['request_id']) ? intval($data['request_id']) : null;
$section = isset($data['target_section']) ? trim($data['target_section']) : null;

if (empty($requestId) || empty($section)) {
    echo json_encode([
        "success" => false,
        "message" => "Shifting Request ID and Target Section assignment are required."
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

    if ($request['status'] !== 'Pending Dept Head') {
        echo json_encode([
            "success" => false,
            "message" => "Cannot approve. Request status is already '{$request['status']}'."
        ]);
        exit;
    }

    // Verify program target match
    if ($request['target_program_code'] !== $staffProgram) {
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized. You cannot approve requests for other departments."
        ]);
        exit;
    }

    // 4. Begin transaction to update request and log
    $db->beginTransaction();

    $stmtUpdate = $db->prepare("
        UPDATE shifting_requests 
        SET status = 'Approved by Dept Head', target_section = :section 
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        ':section' => $section,
        ':id' => $requestId
    ]);

    // Log action in audit log
    $stmtLog = $db->prepare("
        INSERT INTO audit_logs (staff_id, student_id, action, details)
        VALUES (:staff_id, :student_id, 'Shifting Dept Approval', :details)
    ");
    $stmtLog->execute([
        ':staff_id' => $staffId,
        ':student_id' => $request['student_id'],
        ':details' => "Department Head approved shifting request to {$request['target_program_code']} and assigned section: {$section}."
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "Shifting request approved and section '{$section}' assigned successfully."
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Error processing approval: " . $e->getMessage()
    ]);
}
