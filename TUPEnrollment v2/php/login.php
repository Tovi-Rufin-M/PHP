<?php
// ─────────────────────────────────────────────────────────────────────────────
// ALL PHP MUST RUN FIRST — before a single byte of HTML is sent
// ─────────────────────────────────────────────────────────────────────────────
session_start();

// ─── DB CONFIG ───────────────────────────────────────────────────────────────
$host = 'localhost';
$db   = 'enrollment_db';
$user = 'root';
$pass = '';

// ─── CONSTANTS ───────────────────────────────────────────────────────────────
define('MAX_ATTEMPTS', 5);
define('LOCKOUT_TIME', 10);
define('TOKEN_NAME',   '_token');

// ── 7. CSRF TOKEN GENERATOR ──────────────────────────────────────────────────
// Defined HERE (top) so it is available when the HTML form renders below
/**
 * Generates and stores a cryptographically secure CSRF token in the session
 * if one does not already exist.
 *
 * @return string - The current session CSRF token
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ─────────────────────────────────────────────────────────────────────────────
// Only process when the form is submitted via POST
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 1. CSRF VALIDATION ───────────────────────────────────────────────────
    /**
     * Validates the submitted CSRF token against the one stored in the session.
     * Prevents cross-site request forgery attacks.
     *
     * @return bool True if the token is valid, false otherwise
     */
    function isValidCsrfToken(): bool {
        $submitted = $_POST[TOKEN_NAME]      ?? '';
        $stored    = $_SESSION['csrf_token'] ?? '';
        return !empty($stored) && hash_equals($stored, $submitted);
    }

    if (!isValidCsrfToken()) {
        die('<p style="color:red;">Invalid request. Please refresh and try again.</p>');
    }

    // ── 2. BRUTE FORCE / RATE LIMITING ──────────────────────────────────────
    /**
     * Checks if the current session has exceeded the maximum login attempts
     * within the defined lockout window.
     *
     * @return bool True if locked out, false if attempts are still allowed
     */
    function isLockedOut(): bool {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastFail = $_SESSION['last_fail_time'] ?? 0;
        $elapsed  = time() - $lastFail;

        if ($elapsed > LOCKOUT_TIME) {
            $_SESSION['login_attempts'] = 0;
            return false;
        }

        return $attempts >= MAX_ATTEMPTS;
    }

    /**
     * Increments the failed login counter and records the timestamp.
     *
     * @return void
     */
    function recordFailedAttempt(): void {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_fail_time'] = time();
    }

    /**
     * Clears the login attempt counters on a successful login.
     *
     * @return void
     */
    function clearAttempts(): void {
        unset($_SESSION['login_attempts'], $_SESSION['last_fail_time']);
    }

    if (isLockedOut()) {
        $remaining = LOCKOUT_TIME - (time() - $_SESSION['last_fail_time']);
        die("<p style='color:red;'>Too many failed attempts. Try again in {$remaining} seconds.</p>");
    }

    // ── 3. SANITIZE & VALIDATE INPUT ────────────────────────────────────────
    /**
     * Strips whitespace and unsafe characters from a plain text input.
     *
     * @param  string $value - Raw input value from $_POST
     * @return string        - Cleaned string safe for comparison
     */
    function sanitizeInput(string $value): string {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    $username = sanitizeInput($_POST['username'] ?? '');
    $password = trim($_POST['password']          ?? '');
    $bdate    = sanitizeInput($_POST['bdate']    ?? '');
    $usertype = (int) ($_POST['usertype']        ?? 0);

    if (empty($username) || empty($password) || empty($bdate)) {
        die("<p style='color:red;'>All fields are required.</p>");
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d', $bdate);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $bdate) {
        die("<p style='color:red;'>Invalid birthdate format.</p>");
    }

    // ── 4. DATABASE LOOKUP ───────────────────────────────────────────────────
    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$db};charset=utf8",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        /**
         * Fetches a student record by username and usertype using
         * a prepared statement to prevent SQL injection.
         *
         * @param  PDO    $pdo
         * @param  string $username
         * @param  int    $usertype
         * @return array|false
         */
        function fetchStudent(PDO $pdo, string $username, int $usertype): array|false {
            $stmt = $pdo->prepare("
                SELECT id, username, password, birthdate, usertype
                FROM   students
                WHERE  username = :username
                AND    usertype = :usertype
                LIMIT  1
            ");
            $stmt->execute([':username' => $username, ':usertype' => $usertype]);
            return $stmt->fetch();
        }

        $student = fetchStudent($pdo, $username, $usertype);

        // ── 5. VERIFY CREDENTIALS ────────────────────────────────────────────
        /**
         * Validates all three credential factors — user existence,
         * bcrypt password hash, and birthdate as a second factor.
         *
         * @param  array|false $student
         * @param  string      $password
         * @param  string      $bdate
         * @return bool
         */
        function verifyCredentials(array|false $student, string $password, string $bdate): bool {
            if (!$student)                                         return false;
            if (!password_verify($password, $student['password'])) return false;
            if ($student['birthdate'] !== $bdate)                  return false;
            return true;
        }

        if (!verifyCredentials($student, $password, $bdate)) {
            recordFailedAttempt();
            $left = MAX_ATTEMPTS - ($_SESSION['login_attempts'] ?? 0);
            die("<p style='color:red;'>Invalid credentials. {$left} attempt(s) remaining.</p>");
        }

        // ── 6. SUCCESSFUL LOGIN — HARDEN SESSION ─────────────────────────────
        clearAttempts();

        /**
         * Regenerates the session ID to prevent session fixation,
         * then stores only the minimum required user data.
         *
         * @param  array $student
         * @return void
         */
        function startAuthenticatedSession(array $student): void {
            session_regenerate_id(true);

            $_SESSION['user_id']       = $student['id'];
            $_SESSION['username']      = $student['username'];
            $_SESSION['usertype']      = $student['usertype'];
            $_SESSION['authenticated'] = true;
            $_SESSION['login_time']    = time();
            $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
        }

        startAuthenticatedSession($student);

        // Dynamically build the redirect URL — works on any machine/folder name
        $base = dirname(dirname($_SERVER['SCRIPT_NAME']));
        header('Location: ' . rtrim($base, '/') . 'php/form.php');
        exit();

    } catch (PDOException $e) {
        error_log('[LOGIN ERROR] ' . $e->getMessage());
        die("<p style='color:red;'>A server error occurred. Please try again later.</p>");
    }
}
// ─────────────────────────────────────────────────────────────────────────────
// HTML renders BELOW — only after all PHP logic is done
// ─────────────────────────────────────────────────────────────────────────────
?>

<br>
<h1 class="text-center">Students Access Module</h1>
<div class="aims-container space-between">
    <div class="aims-loginpanel margin-center">
        <h3>User Authentication</h3>
        <hr>
        <br>
        <form name="frmLogin" method="POST" action="php/login.php" autocomplete="off">
            <input type="hidden" name="_token" value="<?php echo getCsrfToken(); ?>">
            <input type="hidden" name="usertype" value="1">
            <div class="aims-textfield">
                <input type="text" name="username" placeholder="Username" autofocus="" required="">
                <label>Username:</label>
            </div>
            <div class="aims-textfield">
                <input type="password" name="password" placeholder="Password" required="">
                <label>Password:</label>
            </div>
            <div class="aims-textfield">
                <input type="date" name="bdate" required>
                <label>Birthdate:</label>
            </div>
            <div class="aims-textfield flex space-between">
                <button type="reset" class="aims-button red"><span>Clear Entries</span></button>
                <button type="submit" class="aims-button red"><span>Login</span></button>
            </div>
            <p style="color:#ddd">Forgot your password? <a href="forgot.php" style="color:#fff">Click here</a></p>
            <br>
        </form>
    </div>
</div>
<script>
    /**
     * Intercepts the login form submission and handles it via fetch
     * so the page stays inside the SPA without a full navigation.
     *
     * @param {Event} event - The form submit event
     * @returns {Promise<void>}
     */
    const handleLoginSubmit = async (event) => {
        event.preventDefault(); // ✅ Stop full page navigation

        const form    = event.target;
        const formData = new FormData(form);

        try {
            const res  = await fetch(form.action, {
                method:      'POST',
                credentials: 'same-origin', // Send session cookies
                body:        formData,
            });

            const html = await res.text();

            // If login succeeded, PHP sends a redirect header — follow it
            if (res.redirected) {
                window.location.href = res.url;
                return;
            }

            // Otherwise show the error message inside the form panel
            document.querySelector('.aims-loginpanel').innerHTML = html;

        } catch (err) {
            console.error('[LOGIN ERROR]', err);
        }
    };

    // Attach the interceptor directly (no DOMContentLoaded needed)
    const loginForm = document.querySelector('form[name="frmLogin"]');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }
</script>