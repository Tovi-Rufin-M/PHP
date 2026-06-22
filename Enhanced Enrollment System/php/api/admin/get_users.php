<?php
/**
 * Admin Get Users API
 * Fetches lists of students, staff members, and programs for administration.
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

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    // Fetch Students
    $stmtStudents = $db->query("
        SELECT student_id, name, program_code, section, birthday, current_term, approval_status, created_at 
        FROM students 
        ORDER BY student_id DESC
    ");
    $students = $stmtStudents->fetchAll();

    // Fetch Staff
    $stmtStaff = $db->query("
        SELECT staff_id, name, role, program_code, created_at 
        FROM staff 
        ORDER BY staff_id ASC
    ");
    $staff = $stmtStaff->fetchAll();

    // Fetch Programs
    $stmtPrograms = $db->query("
        SELECT program_code, program_name 
        FROM programs 
        ORDER BY program_code ASC
    ");
    $programs = $stmtPrograms->fetchAll();

    // Fetch Subjects
    $stmtSubjects = $db->query("
        SELECT subject_code, description, units 
        FROM subjects 
        ORDER BY subject_code ASC
    ");
    $subjects = $stmtSubjects->fetchAll();

    // Fetch Curriculums mapping
    $stmtCurriculums = $db->query("
        SELECT program_code, year_level, term, subject_code 
        FROM curriculums
    ");
    $curriculums = $stmtCurriculums->fetchAll();

    echo json_encode([
        "success" => true,
        "students" => $students,
        "staff" => $staff,
        "programs" => $programs,
        "subjects" => $subjects,
        "curriculums" => $curriculums
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server database error: " . $e->getMessage()
    ]);
}
?>
