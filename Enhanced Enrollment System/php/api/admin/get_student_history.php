<?php
/**
 * Admin Get Student History API
 * Fetches the selected student's academic transcript/subject history.
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

    $stmt = $db->prepare("
        SELECT subject_code, grade, status 
        FROM student_subject_history 
        WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $history = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "history" => $history
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server database error: " . $e->getMessage()
    ]);
}
?>
