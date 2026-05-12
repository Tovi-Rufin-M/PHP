<?php
    header('Content-Type: application/json');

    $host = 'localhost';
    $db   = 'enrollment_db';
    $user = 'root';
    $pass = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Connection failed.']);
        exit;
    }

    $subject_code = $_GET['code'] ?? '';

    if (empty($subject_code)) {
        echo json_encode(['success' => false, 'message' => 'No subject code provided.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, course_name, director, semester
            FROM course_sections
            WHERE subject_code = :code
        ");
        $stmt->execute([':code' => $subject_code]);
        $sections = $stmt->fetchAll();

        echo json_encode(['success' => true, 'sections' => $sections]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
?>