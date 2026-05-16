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

// =============================================================================
// SECTION 1 — DATABASE CONNECTION
// =============================================================================

/**
 * Creates and returns a singleton PDO connection.
 * The static variable ensures only one connection is opened per request,
 * even if getConnection() is called multiple times in the same file.
 *
 * @return PDO - Active database connection
 * @throws PDOException - If the connection to MySQL fails
 */
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

// =============================================================================
// SECTION 2 — DATA FETCHER (GET only)
// =============================================================================

/**
 * Fetches all subjects the student has previously failed from the DB.
 * Called by handleGet() and the result is used in the HTML foreach loop
 * below to render the "Subjects for Retake" table rows.
 *
 * @return array - Rows with keys: name, course, units, room, instructor, grade
 */
function getFailedSubjects(): array {
    $stmt = getConnection()->query("
        SELECT name, course, units, room, instructor, grade
        FROM   failed_subjects
    ");
    return $stmt->fetchAll();
}

// =============================================================================
// SECTION 3 — INPUT HANDLERS (POST only)
// =============================================================================

/**
 * Sanitizes a single value from the incoming JSON payload.
 * Strips HTML tags and encodes special characters to prevent XSS.
 * Must be applied to every field before inserting into the database.
 *
 * @param  mixed  $value - Raw value from the decoded JSON body
 * @return string        - Safe, trimmed string ready for DB insertion
 */
function sanitizeField(mixed $value): string {
    return htmlspecialchars(strip_tags(trim((string) $value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Reads and decodes the raw JSON body sent by JS fetch().
 * The payload arrives as a JSON string in php://input — NOT in $_POST —
 * because the JS sends Content-Type: application/json.
 * Returns null if any validation step fails so handlePost() can reject early.
 *
 * Expected shape: { subjects: [ { name, course, units, room, instructor, grade } ], total: N }
 *
 * @return array|null - Validated subjects array, or null if invalid/empty
 */
function getPayload(): array|null {
    $rawInput = file_get_contents('php://input');
    $data     = json_decode($rawInput, true);

    if ($data === null)               return null; // Malformed JSON
    if (!isset($data['subjects']))    return null; // Missing subjects key
    if (!is_array($data['subjects'])) return null; // Wrong type
    if (empty($data['subjects']))     return null; // Nothing to save

    return $data['subjects'];
}

// =============================================================================
// SECTION 4 — REQUEST HANDLERS
// =============================================================================

/**
 * Handles POST requests from the JS sendRetakeSubjects() fetch call.
 * Validates the AJAX origin and payload, then processes the subjects array.
 * Always exits with a JSON response — HTML is never rendered after this.
 *
 * @return void - Outputs JSON and exits
 */
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

/**
 * Handles GET requests triggered by loadPage('php/process.php') in index.js.
 * Fetches only the failed subjects and stores them in a global variable
 * so the HTML template below can loop over $subjectToRetake directly.
 *
 * @return void - Sets $subjectToRetake in the global scope for HTML rendering
 */
function handleGet(): void {
    global $subjectToRetake;
    $subjectToRetake = getFailedSubjects();
}

// =============================================================================
// SECTION 5 — ROUTER
// =============================================================================

/**
 * Routes the request to the correct handler based on the HTTP method.
 * POST → handlePost() outputs JSON and exits (no HTML rendered).
 * GET  → handleGet() loads data, then falls through to the HTML below.
 * Anything else is rejected immediately with 405 Method Not Allowed.
 *
 * @return void
 */
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