<?php
/**
 * Student Shifting Submission API
 * Handles creation and resetting of shifting requests.
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

// 1. Verify Student Session
if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Please log in as a student."
    ]);
    exit;
}

$studentId = $_SESSION['student_id'];
$dbClass = new Database();
$db = $dbClass->getConnection();

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection error."
    ]);
    exit;
}

// Check action parameter for resetting a rejected request
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'reset') {
    try {
        // Delete or archive the rejected request
        $stmtDel = $db->prepare("DELETE FROM shifting_requests WHERE student_id = :student_id AND status = 'Rejected'");
        $stmtDel->execute([':student_id' => $studentId]);
        
        echo json_encode([
            "success" => true,
            "message" => "Previous request reset successfully. You may now file a new application."
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage()
        ]);
        exit;
    }
}

// Default submit action
try {
    // 2. Fetch input data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $targetProgram = isset($data['target_program_code']) ? trim($data['target_program_code']) : null;
    $eligibilityAnswers = isset($data['eligibility_answers']) ? trim($data['eligibility_answers']) : null;

    if (empty($targetProgram) || empty($eligibilityAnswers)) {
        echo json_encode([
            "success" => false,
            "message" => "Target program and eligibility responses are required."
        ]);
        exit;
    }

    // 3. Verify Student Eligibility
    $stmtCheck = $db->prepare("SELECT program_code, current_term, approval_status FROM students WHERE student_id = :student_id LIMIT 1");
    $stmtCheck->execute([':student_id' => $studentId]);
    $student = $stmtCheck->fetch();

    if (!$student) {
        echo json_encode([
            "success" => false,
            "message" => "Student profile not found."
        ]);
        exit;
    }

    if ($student['program_code'] !== 'BET-00-V' || $student['current_term'] !== 'Third Term' || $student['approval_status'] !== 'Enrolled') {
        echo json_encode([
            "success" => false,
            "message" => "Ineligible to shift. You must complete all terms of the Common First Year curriculum first."
        ]);
        exit;
    }

    // 4. Verify no pending shifting request exists
    $stmtReq = $db->prepare("SELECT status FROM shifting_requests WHERE student_id = :student_id LIMIT 1");
    $stmtReq->execute([':student_id' => $studentId]);
    $existing = $stmtReq->fetch();

    if ($existing) {
        if ($existing['status'] !== 'Rejected') {
            echo json_encode([
                "success" => false,
                "message" => "You already have an active shifting request (Status: {$existing['status']})."
            ]);
            exit;
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Your previous request was rejected. Please reset it first before submitting a new one."
            ]);
            exit;
        }
    }

    // 5. Insert new shifting request
    $stmtInsert = $db->prepare("
        INSERT INTO shifting_requests (student_id, current_program_code, target_program_code, target_section, status, eligibility_answers)
        VALUES (:student_id, :current_program, :target_program, NULL, 'Pending Dept Head', :answers)
    ");
    $stmtInsert->execute([
        ':student_id' => $studentId,
        ':current_program' => $student['program_code'],
        ':target_program' => $targetProgram,
        ':answers' => $eligibilityAnswers
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Shifting request submitted successfully! Awaiting Department Head screening."
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
