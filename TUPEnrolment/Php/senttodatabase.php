<?php

header('Content-Type: application/json');

// --- DB Config ---
$host = 'localhost';
$db   = 'enrollment_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// --- Connect ---
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Connection failed.']);
    exit;
}

// --- Read JSON input ---
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// --- Validate ---
if (!$data || empty($data['subjects']) || !is_array($data['subjects'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing subjects.']);
    exit;
}

$selectedSubjects = $data['subjects'];

// --- Insert ---
try {
    $pdo->beginTransaction();

    // ✅ Remove all existing records first
    $pdo->exec("DELETE FROM nextsubjects");

    // ✅ Then insert the new subjects
    $stmt = $pdo->prepare("
        INSERT INTO nextsubjects (code, units, room, instructor, created_at)
        VALUES (:code, :units, :room, :instructor, NOW())
    ");

    foreach ($selectedSubjects as $subject) {
        if (empty($subject['code'])) continue;

        $stmt->execute([
            ':code'       => $subject['code'],
            ':units'      => $subject['units'],
            ':room'       => $subject['room'],
            ':instructor' => $subject['instructor'],
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => count($selectedSubjects) . ' subject(s) saved successfully.'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
}
?>