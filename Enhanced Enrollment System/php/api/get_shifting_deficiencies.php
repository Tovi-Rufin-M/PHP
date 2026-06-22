<?php
/**
 * Get Shifting Student Deficiencies API
 * Retrieves a list of failed subjects for a student applying to shift.
 * Ensures the requesting Department Head governs the target program.
 */

header('Content-Type: application/json');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
        "message" => "Unauthorized. Staff login required."
    ]);
    exit;
}

$staffId = $_SESSION['staff_id'];
$staffRole = $_SESSION['staff_role'];
$staffProgram = isset($_SESSION['program_code']) ? $_SESSION['program_code'] : null;

$studentId = isset($_GET['student_id']) ? trim($_GET['student_id']) : null;

if (empty($studentId)) {
    echo json_encode([
        "success" => false,
        "message" => "Student ID is required."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed.");
    }

    // 2. Security Check: If Dept Head, verify student has a shifting request targeting their program
    if ($staffRole === 'Dept Head') {
        $stmtVerify = $db->prepare("
            SELECT COUNT(*) 
            FROM shifting_requests 
            WHERE student_id = :student_id AND target_program_code = :target_program
        ");
        $stmtVerify->execute([
            ':student_id' => $studentId,
            ':target_program' => $staffProgram
        ]);
        if ($stmtVerify->fetchColumn() == 0) {
            echo json_encode([
                "success" => false,
                "message" => "Access denied. Student is not applying to your department."
            ]);
            exit;
        }
    }

    // 3. Fetch failed subjects
    $stmtDef = $db->prepare("
        SELECT h.*, s.description, s.units 
        FROM student_subject_history h
        JOIN subjects s ON h.subject_code = s.subject_code
        WHERE h.student_id = :student_id AND h.status = 'Failed'
        ORDER BY h.subject_code
    ");
    $stmtDef->execute([':student_id' => $studentId]);
    $deficiencies = $stmtDef->fetchAll();

    echo json_encode([
        "success" => true,
        "deficiencies" => $deficiencies
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving deficiencies: " . $e->getMessage()
    ]);
}
