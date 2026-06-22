<?php
/**
 * Submit Student Enrollment Selections API
 * Saves the student's chosen regular subjects, dropped subjects, and retake selections.
 * Sets the student's status to 'Pending' for Department Head review.
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

// 1. Verify Session
if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Please log in first."
    ]);
    exit;
}

$studentId = $_SESSION['student_id'];

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$droppedSubjects = isset($data['dropped_subjects']) && is_array($data['dropped_subjects']) ? $data['dropped_subjects'] : [];
$retakeSelections = isset($data['retake_selections']) && is_array($data['retake_selections']) ? $data['retake_selections'] : [];

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    // 3. Fetch student profile to get program details
    $stmtStudent = $db->prepare("SELECT * FROM students WHERE student_id = :student_id LIMIT 1");
    $stmtStudent->execute([':student_id' => $studentId]);
    $student = $stmtStudent->fetch();

    if (!$student) {
        echo json_encode([
            "success" => false,
            "message" => "Student profile not found."
        ]);
        exit;
    }

    $currentTerm = $student['current_term'];
    $programCode = $student['program_code'];
    $section = $student['section'];

    // Determine Upcoming Term
    if ($currentTerm === "First Term") {
        $upcomingTerm = "Second Term";
    } elseif ($currentTerm === "Second Term") {
        $upcomingTerm = "Third Term";
    } else {
        $upcomingTerm = "Summer Term";
    }

    // 4. Fetch regular schedule subjects to determine which ones are available
    $schedule = [];
    $isCommonFirstYearThirdTerm = ($programCode === "BET-00-V" && $currentTerm === "Third Term");
    if (!$isCommonFirstYearThirdTerm) {
        $stmtSched = $db->prepare("
            SELECT DISTINCT ss.subject_code
            FROM section_schedules ss
            WHERE ss.program_code = :program_code 
              AND ss.section_name = :section
              AND ss.term = :term
        ");
        $stmtSched->execute([
            ':program_code' => $programCode,
            ':section' => $section,
            ':term' => $upcomingTerm
        ]);
        $schedule = $stmtSched->fetchAll(PDO::FETCH_COLUMN);
    }

    // Fetch student failed subjects
    $stmtFailed = $db->prepare("
        SELECT subject_code FROM student_subject_history 
        WHERE student_id = :student_id AND status = 'Failed'
    ");
    $stmtFailed->execute([':student_id' => $studentId]);
    $failedCodes = $stmtFailed->fetchAll(PDO::FETCH_COLUMN);

    // Fetch prerequisite mapping
    $stmtPrereqs = $db->prepare("SELECT subject_code, prerequisite_code FROM subject_prerequisites");
    $stmtPrereqs->execute();
    $prereqs = $stmtPrereqs->fetchAll();
    
    $prereqMap = [];
    foreach ($prereqs as $p) {
        $prereqMap[$p['subject_code']][] = $p['prerequisite_code'];
    }

    // Determine disabled subjects (failed prerequisites)
    $disabledSubjects = [];
    foreach ($schedule as $code) {
        if (isset($prereqMap[$code])) {
            foreach ($prereqMap[$code] as $prereq) {
                if (in_array($prereq, $failedCodes)) {
                    $disabledSubjects[] = $code;
                }
            }
        }
    }

    // Start Transaction
    $db->beginTransaction();

    // 5. Clear any existing selections
    $stmtClear = $db->prepare("DELETE FROM student_enrollment_selections WHERE student_id = :student_id");
    $stmtClear->execute([':student_id' => $studentId]);

    // 6. Insert regular and dropped subjects
    $stmtInsertSelection = $db->prepare("
        INSERT INTO student_enrollment_selections (student_id, subject_code, status, retake_method, schedule_id)
        VALUES (:student_id, :subject_code, :status, :retake_method, :schedule_id)
    ");

    foreach ($schedule as $code) {
        // Skip disabled subjects entirely as they cannot be taken as regular classes
        if (in_array($code, $disabledSubjects)) {
            continue;
        }

        $status = in_array($code, $droppedSubjects) ? 'Dropped' : 'Regular';
        $stmtInsertSelection->execute([
            ':student_id' => $studentId,
            ':subject_code' => $code,
            ':status' => $status,
            ':retake_method' => null,
            ':schedule_id' => null
        ]);
    }

    // 7. Insert retake selections
    foreach ($retakeSelections as $code => $retakeInfo) {
        $method = $retakeInfo['method'] ?? null;
        $schedId = !empty($retakeInfo['scheduleId']) ? intval($retakeInfo['scheduleId']) : null;

        $stmtInsertSelection->execute([
            ':student_id' => $studentId,
            ':subject_code' => $code,
            ':status' => 'Retake',
            ':retake_method' => $method,
            ':schedule_id' => $schedId
        ]);
    }

    // 8. Update student status to 'Pending'
    $stmtUpdateStatus = $db->prepare("UPDATE students SET approval_status = 'Pending' WHERE student_id = :student_id");
    $stmtUpdateStatus->execute([':student_id' => $studentId]);

    // 9. Log action in audit logs
    $stmtAudit = $db->prepare("
        INSERT INTO audit_logs (staff_id, student_id, action, details)
        VALUES (NULL, :student_id, 'Enrollment Submission', :details)
    ");
    $details = "Student submitted enrollment selections: " . count($schedule) . " regular subject(s), " . count($droppedSubjects) . " dropped, " . count($retakeSelections) . " retake(s).";
    $stmtAudit->execute([
        ':student_id' => $studentId,
        ':details' => $details
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "Enrollment selections submitted successfully. Status set to Pending review."
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Error processing submission: " . $e->getMessage()
    ]);
}
?>
