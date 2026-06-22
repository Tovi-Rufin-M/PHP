<?php
/**
 * Registrar Student Enrollment API
 * Verifies department approval and officially enrolls the student.
 * Updates academic history to add newly enrolled subjects as 'Ongoing'.
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

// 1. Verify Registrar Session
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

$studentId = isset($data['student_id']) ? trim($data['student_id']) : null;

if (empty($studentId)) {
    // Fallback to POST
    $studentId = isset($_POST['student_id']) ? trim($_POST['student_id']) : null;
}

if (empty($studentId)) {
    echo json_encode([
        "success" => false,
        "message" => "Student ID is required for final enrollment."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    // Verify student exists and is 'Approved by Dept Head'
    $stmtCheck = $db->prepare("SELECT name, approval_status, program_code, current_term FROM students WHERE student_id = :student_id LIMIT 1");
    $stmtCheck->execute([':student_id' => $studentId]);
    $student = $stmtCheck->fetch();

    if (!$student) {
        echo json_encode([
            "success" => false,
            "message" => "Student not found."
        ]);
        exit;
    }

    if ($student['approval_status'] !== 'Approved by Dept Head') {
        echo json_encode([
            "success" => false,
            "message" => "Cannot enroll. Student registration is '{$student['approval_status']}', not 'Approved by Dept Head'."
        ]);
        exit;
    }

    // Start Transaction
    $db->beginTransaction();

    // Fetch selections
    $stmtSelections = $db->prepare("
        SELECT subject_code, status 
        FROM student_enrollment_selections 
        WHERE student_id = :student_id AND status IN ('Regular', 'Retake')
    ");
    $stmtSelections->execute([':student_id' => $studentId]);
    $selections = $stmtSelections->fetchAll();

    if (empty($selections)) {
        // Automatically populate regular selections based on the student's curriculum and current term
        $yearLevel = ($student['program_code'] === 'BET-00-V') ? 1 : 2;
        $stmtCurriculum = $db->prepare("
            SELECT subject_code 
            FROM curriculums 
            WHERE program_code = :program_code AND year_level = :year_level AND term = :term
        ");
        $stmtCurriculum->execute([
            ':program_code' => $student['program_code'],
            ':year_level' => $yearLevel,
            ':term' => $student['current_term']
        ]);
        $currSubjects = $stmtCurriculum->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($currSubjects)) {
            $stmtInsertSel = $db->prepare("
                INSERT INTO student_enrollment_selections (student_id, subject_code, status, retake_method, schedule_id)
                VALUES (:student_id, :subject_code, 'Regular', NULL, NULL)
                ON DUPLICATE KEY UPDATE status = 'Regular'
            ");
            foreach ($currSubjects as $code) {
                $stmtInsertSel->execute([
                    ':student_id' => $studentId,
                    ':subject_code' => $code
                ]);
            }

            // Re-fetch selections
            $stmtSelections->execute([':student_id' => $studentId]);
            $selections = $stmtSelections->fetchAll();
        }
    }

    if (empty($selections)) {
        throw new Exception("No approved subjects found in student's enrollment record.");
    }

    // Update status to 'Enrolled'
    $stmtUpdate = $db->prepare("
        UPDATE students 
        SET approval_status = 'Enrolled' 
        WHERE student_id = :student_id
    ");
    $stmtUpdate->execute([':student_id' => $studentId]);

    // Insert selections as 'Ongoing' in student's academic history
    $stmtInsertHistory = $db->prepare("
        INSERT INTO student_subject_history (student_id, subject_code, grade, status)
        VALUES (:student_id, :subject_code, NULL, 'Ongoing')
        ON DUPLICATE KEY UPDATE status = 'Ongoing', grade = NULL
    ");

    foreach ($selections as $sel) {
        $stmtInsertHistory->execute([
            ':student_id' => $studentId,
            ':subject_code' => $sel['subject_code']
        ]);
    }

    // Insert Audit Log entry
    $stmtLog = $db->prepare("
        INSERT INTO audit_logs (staff_id, student_id, action, details)
        VALUES (:staff_id, :student_id, 'Enrolled', :details)
    ");
    $details = "Registrar {$staffName} verified files and officially ENROLLED the student.";
    $stmtLog->execute([
        ':staff_id' => $staffId,
        ':student_id' => $studentId,
        ':details' => $details
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "Student enrollment officially completed! Account updated to Enrolled."
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Error completing enrollment: " . $e->getMessage()
    ]);
}
?>
