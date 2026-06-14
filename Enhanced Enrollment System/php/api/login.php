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

if (!$student_id || !$password || !$birthday) {
    // Attempt JSON parsing
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if ($data) {
        $student_id = isset($data['student_id']) ? trim($data['student_id']) : null;
        $password = isset($data['password']) ? $data['password'] : null;
        $birthday = isset($data['birthday']) ? trim($data['birthday']) : null;
    }
}

// Validation
if (empty($student_id) || empty($password) || empty($birthday)) {
    echo json_encode([
        "success" => false,
        "message" => "Student ID, Password, and Date of Birth are required."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

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
    // Accept input either in YYYY-MM-DD or standard formats and compare
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

    // Login successful
    echo json_encode([
        "success" => true,
        "message" => "Login successful!",
        "data" => [
            "student_id" => $student['student_id'],
            "name" => $student['name'],
            "section" => $student['section'],
            "program_code" => $student['program_code'],
            "current_term" => $student['current_term']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server database error: " . $e->getMessage()
    ]);
}
?>
