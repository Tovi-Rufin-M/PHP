<?php
/**
 * Get Student Enrollment Details API
 * Fetches profile info and selections for modal displays on dashboards.
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

    // Query student profile
    $stmt = $db->prepare("
        SELECT s.*, p.program_name 
        FROM students s
        LEFT JOIN programs p ON s.program_code = p.program_code
        WHERE s.student_id = :student_id
        LIMIT 1
    ");
    $stmt->execute([':student_id' => $studentId]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode([
            "success" => false,
            "message" => "Student not found."
        ]);
        exit;
    }

    // Verify program code permissions for Dept Head
    if ($_SESSION['staff_role'] === 'Dept Head' && isset($_SESSION['program_code']) && $_SESSION['program_code'] !== $student['program_code']) {
        echo json_encode([
            "success" => false,
            "message" => "Access denied. Student is not in your department."
        ]);
        exit;
    }

    // Query enrollment selections
    $stmtSelections = $db->prepare("
        SELECT es.*, s.description, s.units 
        FROM student_enrollment_selections es
        JOIN subjects s ON es.subject_code = s.subject_code
        WHERE es.student_id = :student_id
    ");
    $stmtSelections->execute([':student_id' => $studentId]);
    $selections = $stmtSelections->fetchAll();

    // Get audit logs for this student
    $stmtLogs = $db->prepare("
        SELECT al.*, st.name as staff_name, st.role as staff_role
        FROM audit_logs al
        LEFT JOIN staff st ON al.staff_id = st.staff_id
        WHERE al.student_id = :student_id
        ORDER BY al.timestamp DESC
    ");
    $stmtLogs->execute([':student_id' => $studentId]);
    $logs = $stmtLogs->fetchAll();

    echo json_encode([
        "success" => true,
        "student" => [
            "student_id" => $student['student_id'],
            "name" => $student['name'],
            "program_code" => $student['program_code'],
            "program_name" => $student['program_name'],
            "section" => $student['section'],
            "current_term" => $student['current_term'],
            "approval_status" => $student['approval_status'],
            "birthday" => $student['birthday']
        ],
        "selections" => $selections,
        "logs" => $logs
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server database error: " . $e->getMessage()
    ]);
}
?>
