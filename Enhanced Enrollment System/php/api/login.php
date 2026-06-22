<?php
/**
 * Student Login API
 * Validates Student ID, Password, and Date of Birth
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}

// Get inputs (supporting both form-data and JSON payloads)
$student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;
$birthday = isset($_POST['birthday']) ? trim($_POST['birthday']) : null;

if (!$student_id || !$password) {
    // Attempt JSON parsing
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if ($data) {
        $student_id = isset($data['student_id']) ? trim($data['student_id']) : null;
        $password = isset($data['password']) ? $data['password'] : null;
        $birthday = isset($data['birthday']) ? trim($data['birthday']) : null;
    }
}

// Determine if this is a staff login
$is_staff = false;
if ($student_id && (strpos(strtoupper($student_id), 'DEPT-') === 0 || strpos(strtoupper($student_id), 'REG-') === 0 || strpos(strtoupper($student_id), 'ADMIN-') === 0)) {
    $is_staff = true;
}

// Validation
if (empty($student_id) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "ID and Password are required."
    ]);
    exit;
}

if (!$is_staff && empty($birthday)) {
    echo json_encode([
        "success" => false,
        "message" => "Date of Birth is required for students."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    if ($is_staff) {
        // Query staff
        $query = "SELECT * FROM staff WHERE staff_id = :staff_id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':staff_id', $student_id);
        $stmt->execute();
        
        $staff = $stmt->fetch();

        if (!$staff) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid credentials. Staff member not found."
            ]);
            exit;
        }

        // Verify Password
        if (!password_verify($password, $staff['password'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid credentials. Incorrect password."
            ]);
            exit;
        }

        // Staff login successful
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['staff_id'] = $staff['staff_id'];
        $_SESSION['staff_name'] = $staff['name'];
        $_SESSION['staff_role'] = $staff['role'];
        $_SESSION['program_code'] = $staff['program_code'];

        $redirect = 'index.php?page=registrar_dashboard';
        if ($staff['role'] === 'Dept Head') {
            $redirect = 'index.php?page=dept_head_dashboard';
        } elseif ($staff['role'] === 'Admin') {
            $redirect = 'index.php?page=admin_dashboard';
        }

        echo json_encode([
            "success" => true,
            "message" => "Staff login successful!",
            "redirect" => $redirect,
            "data" => [
                "staff_id" => $staff['staff_id'],
                "name" => $staff['name'],
                "role" => $staff['role'],
                "program_code" => $staff['program_code']
            ]
        ]);
        exit;
    } else {
        // Query student
        $query = "SELECT * FROM students WHERE student_id = :student_id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        $student = $stmt->fetch();

        if (!$student) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid credentials. Student not found."
            ]);
            exit;
        }

        // Verify Date of Birth (DB format is YYYY-MM-DD)
        $dbBirthday = $student['birthday'];
        $inputBirthday = date('Y-m-d', strtotime($birthday));

        if ($dbBirthday !== $inputBirthday) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid credentials. Date of Birth does not match."
            ]);
            exit;
        }

        // Verify Password
        if (!password_verify($password, $student['password'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid credentials. Incorrect password."
            ]);
            exit;
        }

        // Student login successful
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['student_name'] = $student['name'];

        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "redirect" => "index.php?page=enrollment",
            "data" => [
                "student_id" => $student['student_id'],
                "name" => $student['name'],
                "section" => $student['section'],
                "program_code" => $student['program_code'],
                "current_term" => $student['current_term']
            ]
        ]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server database error: " . $e->getMessage()
    ]);
}
?>
