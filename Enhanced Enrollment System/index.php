<?php
/**
 * Main Entrypoint & Router (React-Style Architecture)
 * Centrally manages routes, sessions, route guards, and logouts.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 1. Centrally handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php?page=login");
    exit;
}

// 2. Determine requested page. Default to redirecting logged in users or showing login.
$page = isset($_GET['page']) ? trim($_GET['page']) : '';

if (empty($page)) {
    // Default Route Handler: Check if already logged in and redirect accordingly
    if (isset($_SESSION['student_id'])) {
        header("Location: index.php?page=enrollment");
        exit;
    } elseif (isset($_SESSION['staff_id'])) {
        $role = $_SESSION['staff_role'];
        if ($role === 'Admin') {
            header("Location: index.php?page=admin_dashboard");
            exit;
        } elseif ($role === 'Registrar') {
            header("Location: index.php?page=registrar_dashboard");
            exit;
        } elseif ($role === 'Dept Head') {
            header("Location: index.php?page=dept_head_dashboard");
            exit;
        }
    }
    // If not logged in, go to login
    $page = 'login';
}

// 3. Whitelist of valid page components and route guards
switch ($page) {
    case 'login':
        require_once __DIR__ . '/pages/login.php';
        break;

    case 'enrollment':
        // Route Guard for Students
        if (!isset($_SESSION['student_id'])) {
            header("Location: index.php?page=login");
            exit;
        }
        require_once __DIR__ . '/pages/enrollment.php';
        break;

    case 'shifting':
        // Route Guard for Shifting (First Year -> Second Year)
        if (!isset($_SESSION['student_id'])) {
            header("Location: index.php?page=login");
            exit;
        }
        require_once __DIR__ . '/php/config/db.php';
        $dbClass = new Database();
        $conn = $dbClass->getConnection();
        $studentId = $_SESSION['student_id'];
        
        $stmtCheck = $conn->prepare("SELECT program_code, current_term, approval_status FROM students WHERE student_id = :student_id LIMIT 1");
        $stmtCheck->execute([':student_id' => $studentId]);
        $stud = $stmtCheck->fetch();
        
        if (!$stud || $stud['program_code'] !== 'BET-00-V' || $stud['current_term'] !== 'Third Term' || $stud['approval_status'] !== 'Enrolled') {
            header("Location: index.php?page=enrollment&error=shifting_ineligible");
            exit;
        }
        require_once __DIR__ . '/pages/shifting.php';
        break;

    case 'admin_dashboard':
        // Route Guard for Admin
        if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Admin') {
            header("Location: index.php?page=login");
            exit;
        }
        require_once __DIR__ . '/pages/admin_dashboard.php';
        break;

    case 'registrar_dashboard':
        // Route Guard for Registrar
        if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Registrar') {
            header("Location: index.php?page=login");
            exit;
        }
        require_once __DIR__ . '/pages/registrar_dashboard.php';
        break;

    case 'dept_head_dashboard':
        // Route Guard for Department Head
        if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Dept Head') {
            header("Location: index.php?page=login");
            exit;
        }
        require_once __DIR__ . '/pages/dept_head_dashboard.php';
        break;

    default:
        // Fallback for unknown page requests
        header("HTTP/1.1 404 Not Found");
        echo "<h1>404 Not Found</h1><p>The requested page component was not found.</p>";
        break;
}
