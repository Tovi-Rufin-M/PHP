<?php
/**
 * Admin Save Student API
 * Creates or updates a student profile record.
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

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$mode = isset($data['mode']) ? trim($data['mode']) : 'create'; // 'create' or 'edit'
$studentId = isset($data['student_id']) ? trim($data['student_id']) : '';
$originalStudentId = isset($data['original_student_id']) ? trim($data['original_student_id']) : '';
$name = isset($data['name']) ? trim($data['name']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$birthday = isset($data['birthday']) ? trim($data['birthday']) : '';
$programCode = isset($data['program_code']) ? trim($data['program_code']) : '';
$section = isset($data['section']) ? trim($data['section']) : '';
$currentTerm = isset($data['current_term']) ? trim($data['current_term']) : '';
$approvalStatus = isset($data['approval_status']) ? trim($data['approval_status']) : 'Pending';

// Validation
if (empty($studentId) || empty($name) || empty($birthday) || empty($programCode) || empty($section) || empty($currentTerm)) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    $db->beginTransaction();

    $subjectGrades = isset($data['grades']) ? $data['grades'] : [];

    if ($mode === 'create') {
        // Check for existing Student ID
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
        $stmtCheck->execute([$studentId]);
        if ($stmtCheck->fetchColumn() > 0) {
            $db->rollBack();
            echo json_encode([
                "success" => false,
                "message" => "Student ID '$studentId' already exists."
            ]);
            exit;
        }

        // Handle default password
        if (empty($password)) {
            $password = 'password123';
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new student
        $stmtInsert = $db->prepare("
            INSERT INTO students (student_id, name, password, program_code, section, birthday, current_term, approval_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([
            $studentId,
            $name,
            $passwordHash,
            $programCode,
            $section,
            $birthday,
            $currentTerm,
            $approvalStatus
        ]);

        // Insert subject history grades
        $stmtHist = $db->prepare("
            INSERT INTO student_subject_history (student_id, subject_code, grade, status)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($subjectGrades as $subCode => $val) {
            if ($val === 'not_taken') {
                continue;
            }
            $grade = '1.5';
            $status = 'Passed';
            if ($val === 'failed' || strpos($val, 'failed') === 0) {
                $grade = '3.0';
                $status = 'Failed';
                if ($val === 'failed_5.0') $grade = '5.0';
            } else if ($val === 'passed' || strpos($val, 'passed') === 0) {
                $grade = '1.5';
                $status = 'Passed';
                if ($val === 'passed_1.0') $grade = '1.0';
                if ($val === 'passed_2.5') $grade = '2.5';
            }
            $stmtHist->execute([$studentId, $subCode, $grade, $status]);
        }

        $db->commit();
        echo json_encode([
            "success" => true,
            "message" => "Student profile created successfully."
        ]);

    } else {
        // Mode is Edit
        if (empty($originalStudentId)) {
            $originalStudentId = $studentId;
        }

        // If Student ID changed, check duplicate on new ID
        if ($studentId !== $originalStudentId) {
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
            $stmtCheck->execute([$studentId]);
            if ($stmtCheck->fetchColumn() > 0) {
                $db->rollBack();
                echo json_encode([
                    "success" => false,
                    "message" => "Student ID '$studentId' already exists."
                ]);
                exit;
            }
        }

        // Build Update Query
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $db->prepare("
                UPDATE students 
                SET student_id = ?, name = ?, password = ?, program_code = ?, section = ?, birthday = ?, current_term = ?, approval_status = ?
                WHERE student_id = ?
            ");
            $stmtUpdate->execute([
                $studentId,
                $name,
                $passwordHash,
                $programCode,
                $section,
                $birthday,
                $currentTerm,
                $approvalStatus,
                $originalStudentId
            ]);
        } else {
            $stmtUpdate = $db->prepare("
                UPDATE students 
                SET student_id = ?, name = ?, program_code = ?, section = ?, birthday = ?, current_term = ?, approval_status = ?
                WHERE student_id = ?
            ");
            $stmtUpdate->execute([
                $studentId,
                $name,
                $programCode,
                $section,
                $birthday,
                $currentTerm,
                $approvalStatus,
                $originalStudentId
            ]);
        }

        // Clear existing history
        $stmtDeleteHist = $db->prepare("DELETE FROM student_subject_history WHERE student_id = ?");
        $stmtDeleteHist->execute([$originalStudentId]);

        // Insert new/updated history
        $stmtHist = $db->prepare("
            INSERT INTO student_subject_history (student_id, subject_code, grade, status)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($subjectGrades as $subCode => $val) {
            if ($val === 'not_taken') {
                continue;
            }
            $grade = '1.5';
            $status = 'Passed';
            if ($val === 'failed' || strpos($val, 'failed') === 0) {
                $grade = '3.0';
                $status = 'Failed';
                if ($val === 'failed_5.0') $grade = '5.0';
            } else if ($val === 'passed' || strpos($val, 'passed') === 0) {
                $grade = '1.5';
                $status = 'Passed';
                if ($val === 'passed_1.0') $grade = '1.0';
                if ($val === 'passed_2.5') $grade = '2.5';
            }
            $stmtHist->execute([$studentId, $subCode, $grade, $status]);
        }

        $db->commit();
        echo json_encode([
            "success" => true,
            "message" => "Student profile updated successfully."
        ]);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
