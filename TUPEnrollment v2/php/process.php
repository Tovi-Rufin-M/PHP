<?php
    // =============================================================================
    // process.php
    // PURPOSE : Single entry point for all subject-related data operations.
    //           GET  → fetches failed subjects for HTML rendering
    //           POST → receives selected subjects from JS and saves to DB
    // =============================================================================

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // ─── SECURITY: Must be authenticated for ALL requests ────────────────────────
    if (empty($_SESSION['authenticated'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthenticated.']);
        exit;
    }

    // ─── DB CONFIG ────────────────────────────────────────────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'enrollment_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    function getConnection(): PDO {
        static $pdo = null;

        if ($pdo === null) {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }

        return $pdo;
    }

    function getFailedSubjects(): array {
        $stmt = getConnection()->query("
            SELECT name, course, units, room, instructor, grade
            FROM   failed_subjects
        ");
        return $stmt->fetchAll();
    }

    function sanitizeField(mixed $value): string {
        return htmlspecialchars(strip_tags(trim((string) $value)), ENT_QUOTES, 'UTF-8');
    }

    function getPayload(): array|null {
        $rawInput = file_get_contents('php://input');
        $data     = json_decode($rawInput, true);

        if ($data === null)               return null; // Malformed JSON
        if (!isset($data['subjects']))    return null; // Missing subjects key
        if (!is_array($data['subjects'])) return null; // Wrong type
        if (empty($data['subjects']))     return null; // Nothing to save

        return $data['subjects'];
    }

    function handlePost(): void {
        header('Content-Type: application/json; charset=utf-8');

        // Reject non-AJAX requests (e.g. direct browser navigation to process.php)
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: Invalid request mechanism.']);
            exit;
        }

        // Reject malformed or empty JSON payloads
        $subjects = getPayload();
        if ($subjects === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or missing payload structure.']);
            exit;
        }

        // ── Place your DB insertion logic here ────────────────────────────────────

        echo json_encode([
            'success'  => true,
            'message'  => count($subjects) . ' subject(s) received.',
            'total'    => count($subjects),
            'subjects' => $subjects,
        ]);

        exit;
    }

    function handleGet(): void {
        global $subjectToRetake;
        $subjectToRetake = getFailedSubjects();
    }

    function route(): void {
        match ($_SERVER['REQUEST_METHOD']) {
            'POST'  => handlePost(),
            'GET'   => handleGet(),
            default => (function () {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method Not Allowed.']);
                exit;
            })()
        };
    }

    route();

    // =============================================================================
    // HTML RENDERING — only reached on GET, POST exits inside handlePost()
    // =============================================================================
?>