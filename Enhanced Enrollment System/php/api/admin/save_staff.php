<?php
/**
 * Admin Save Staff API
 * Creates or updates a staff member account record.
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

$currentAdminId = $_SESSION['staff_id'];

// 2. Fetch input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$mode = isset($data['mode']) ? trim($data['mode']) : 'create'; // 'create' or 'edit'
$staffId = isset($data['staff_id']) ? trim($data['staff_id']) : '';
$originalStaffId = isset($data['original_staff_id']) ? trim($data['original_staff_id']) : '';
$name = isset($data['name']) ? trim($data['name']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$role = isset($data['role']) ? trim($data['role']) : '';
$programCode = isset($data['program_code']) && !empty($data['program_code']) ? trim($data['program_code']) : null;

// Validation
if (empty($staffId) || empty($name) || empty($role)) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields."
    ]);
    exit;
}

// Check role logic
if ($role === 'Registrar' || $role === 'Admin') {
    $programCode = null; // Programs are only for Department Heads
}

if ($role === 'Dept Head' && empty($programCode)) {
    echo json_encode([
        "success" => false,
        "message" => "Department program code is required for Department Heads."
    ]);
    exit;
}

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    if ($mode === 'create') {
        // Check for existing Staff ID
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM staff WHERE staff_id = ?");
        $stmtCheck->execute([$staffId]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Staff ID '$staffId' already exists."
            ]);
            exit;
        }

        // Handle default password
        if (empty($password)) {
            $password = 'password123';
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new staff member
        $stmtInsert = $db->prepare("
            INSERT INTO staff (staff_id, name, password, role, program_code)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([
            $staffId,
            $name,
            $passwordHash,
            $role,
            $programCode
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Staff account created successfully."
        ]);

    } else {
        // Mode is Edit
        if (empty($originalStaffId)) {
            $originalStaffId = $staffId;
        }

        // Prevent self-lockout actions
        if ($originalStaffId === $currentAdminId) {
            if ($staffId !== $currentAdminId) {
                echo json_encode([
                    "success" => false,
                    "message" => "Security constraint: You cannot change your own Staff ID."
                ]);
                exit;
            }
            if ($role !== 'Admin') {
                echo json_encode([
                    "success" => false,
                    "message" => "Security constraint: You cannot downgrade your own Admin role."
                ]);
                exit;
            }
        }

        // If Staff ID changed, check duplicate on new ID
        if ($staffId !== $originalStaffId) {
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM staff WHERE staff_id = ?");
            $stmtCheck->execute([$staffId]);
            if ($stmtCheck->fetchColumn() > 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Staff ID '$staffId' already exists."
                ]);
                exit;
            }
        }

        // Build Update Query
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $db->prepare("
                UPDATE staff 
                SET staff_id = ?, name = ?, password = ?, role = ?, program_code = ?
                WHERE staff_id = ?
            ");
            $stmtUpdate->execute([
                $staffId,
                $name,
                $passwordHash,
                $role,
                $programCode,
                $originalStaffId
            ]);
        } else {
            $stmtUpdate = $db->prepare("
                UPDATE staff 
                SET staff_id = ?, name = ?, role = ?, program_code = ?
                WHERE staff_id = ?
            ");
            $stmtUpdate->execute([
                $staffId,
                $name,
                $role,
                $programCode,
                $originalStaffId
            ]);
        }

        // If the current admin updated their own name, update the session name
        if ($originalStaffId === $currentAdminId) {
            $_SESSION['staff_name'] = $name;
        }

        echo json_encode([
            "success" => true,
            "message" => "Staff account updated successfully."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
