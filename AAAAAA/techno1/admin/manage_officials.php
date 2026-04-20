<?php 
// technowatch/admin/manage_officials.php - Officials/Staff CRUD Management View (FINAL with Section/Role Availability Filter)

session_start();
// --- ADDED: Set PHP timezone to Philippine Time (Asia/Manila) for accurate logging timestamps ---
date_default_timezone_set('Asia/Manila'); 

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db_connect.php'; 

// --- ADDED: Activity Logging Setup ---
$admin_username = $_SESSION['admin_username'] ?? 'System'; 

function log_activity($conn, $username, $summary) {
    // The `timestamp` column in `admin_activity_log` will use the PHP time set above.
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (username, summary) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $username, $summary);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare activity log statement: " . $conn->error);
    }
}
// --- END Activity Logging Setup ---

// --- Helper Functions ---

/**
 * Helper function for call_user_func_array with bind_param (for bulk operations/re-indexing)
 * Ensures array elements are passed by reference, required by mysqli_stmt::bind_param in PHP versions < 8.0.
 * @param array $arr The array to pass elements of by reference.
 * @return array The array of references.
 */
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}

/**
 * Custom ID Re-indexing Logic for officials_staff.
 * Manually re-assigns staff_id values sequentially (1, 2, 3...) after deletions or resets.
 * @param mysqli $conn The database connection object.
 * @param string $table_name The name of the table ('officials_staff').
 * @param string $item_key_name The primary key name ('staff_id').
 * @return bool True on success, False on failure.
 */
function reindex_staff_ids($conn, $table_name, $item_key_name) {
    // 1. Fetch all items ordered by their current ID (maintaining existing display order).
    $sql_select = "SELECT full_name, role, section, quote, image_path, sort_order FROM $table_name ORDER BY $item_key_name ASC";
    $result = $conn->query($sql_select);

    if (!$result) {
        return false; // Error selecting data
    }

    $all_staff_data = $result->fetch_all(MYSQLI_ASSOC);

    // 2. Clear the entire table (TRUNCATE resets the internal AUTO_INCREMENT counter to 1).
    if (!$conn->query("TRUNCATE TABLE $table_name")) {
        return false; // Error clearing table
    }

    if (empty($all_staff_data)) {
        return true; // Table was empty or is now empty, re-indexing succeeded.
    }

    // 3. Prepare the INSERT statement for re-insertion with new sequential IDs
    $sql_insert = "INSERT INTO $table_name ($item_key_name, full_name, role, section, quote, image_path, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_insert);

    if (!$stmt) {
        return false; // Error preparing insert statement
    }

    // Bind types: i (staff_id), s*5 (strings), i (sort_order) -> 'isssssi'
    $bind_types = 'isssssi'; 

    $new_id = 1;
    $success = true;

    foreach ($all_staff_data as $data) {
        $params = [
            $new_id, 
            $data['full_name'], 
            $data['role'], 
            $data['section'], 
            $data['quote'], 
            $data['image_path'], 
            $data['sort_order']
        ];
        
        // Dynamically bind parameters using the helper function
        $bind_params = array_merge([$bind_types], $params);
            // This is required for bind_param with call_user_func_array
        call_user_func_array([$stmt, 'bind_param'], array_by_ref($bind_params));

        if (!$stmt->execute()) {
            $success = false;
            break; 
        }
        $new_id++;
    }

    $stmt->close();
    return $success;
}

// --- Configuration & Initialization ---
$page_title = 'Manage Officials & Faculty';
$item_key_name = 'staff_id'; // The primary key column name
$table_name = 'officials_staff';
$action = $_GET['action'] ?? 'list';
$item_id = isset($_GET[$item_key_name]) ? (int)$_GET[$item_key_name] : 0; 
$form_title = ($action == 'edit' ? 'Edit Official/Staff' : 'Add New Official/Staff');
$redirect_file = 'manage_officials.php';
$upload_dir_name = 'officials'; // Directory inside assets

// Form pre-fill defaults
$official_data = [
    'staff_id' => 0,
    'full_name' => '',
    'role' => '',
    'section' => '', 
    'quote' => '',
    'image_path' => '',
    'sort_order' => 0, 
];

// --- Configuration for select options (Structured for conditional roles - UNFILTERED) ---
$roles_by_section = [
    'DEPT_HEAD' => [
        'display' => 'Department Head',
        'roles' => ['DEPARTMENT HEAD'] // Single role for this section
    ],
    'FACULTY' => [
        'display' => 'Faculty/Advisers',
        // Roles limited to Instructor 1 through 5
        'roles' => ['INSTRUCTOR 1', 'INSTRUCTOR 2', 'INSTRUCTOR 3', 'INSTRUCTOR 4', 'INSTRUCTOR 5'] 
    ],
    'MAYOR' => [
        'display' => 'Mayor', 
        // Roles for Mayors limited to S09-A, T09-A, F09-A
        'roles' => ['MAYOR S09-A', 'MAYOR T09-A', 'MAYOR F09-A']
    ],
];

// Flat arrays for POST validation (uses ALL roles/sections for consistency)
$all_sections = [];
foreach ($roles_by_section as $slug => $data) {
    $all_sections[$slug] = $data['display'];
}


// --- Dynamic Filtering Logic for Available Sections and Roles (NEW) ---

// 1. Fetch all occupied positions, excluding the one being edited
$occupied_positions = [];
$sql_occupied = "SELECT staff_id, role, section FROM $table_name";
$result_occupied = $conn->query($sql_occupied);
if ($result_occupied) {
    while ($row = $result_occupied->fetch_assoc()) {
        $key = $row['section'] . '_' . $row['role'];
        
        // Exclude the currently edited item from the occupied list by checking its ID
        if (!($action == 'edit' && $row['staff_id'] == $item_id)) {
             $occupied_positions[$key] = true;
        }
    }
}

// 2. Filter sections and roles: remove a section if ALL its roles are occupied
$sections_for_form_display = [];
$roles_by_section_for_js = []; 

foreach ($roles_by_section as $section_slug => $data) {
    $available_roles_in_section = [];
    $all_roles_in_section = $data['roles'];
    $is_current_section = ($action == 'edit' && $official_data['section'] == $section_slug);

    foreach ($all_roles_in_section as $role) {
        $key = $section_slug . '_' . $role;
        
        // A role is available if it's NOT occupied, OR if it is the current official's role
        if (!isset($occupied_positions[$key]) || ($is_current_section && $official_data['role'] == $role)) {
            $available_roles_in_section[] = $role;
        }
    }
    
    // Only keep the section if there is at least one role available, 
    // or if this is the section currently selected for editing.
    if (!empty($available_roles_in_section) || $is_current_section) {
        $sections_for_form_display[$section_slug] = $data['display'];
        
        // Use the list of available roles for the JS to populate the dropdown
        $roles_by_section_for_js[$section_slug] = [
            'display' => $data['display'],
            // Ensure array_unique for safety, though it shouldn't be needed here
            'roles' => array_unique($available_roles_in_section) 
        ];
    }
}

// Re-assign the variable used in the Section dropdown template
$sections = $sections_for_form_display;
// --- END Dynamic Filtering Logic ---


// --- ID Reset Action (Existing) ---
if ($action == 'reset_ids') {
    if (reindex_staff_ids($conn, $table_name, $item_key_name)) {
        $_SESSION['message'] = "All Staff/Official IDs have been reset and re-indexed successfully (starting from 1).";
        $_SESSION['msg_type'] = "success";
        // --- ADDED LOGGING ---
        log_activity($conn, $admin_username, "Performed **full ID reset** on Officials/Staff table.");
    } else {
        $_SESSION['message'] = "DANGER: Failed to reset Staff/Official IDs. Database Error: " . $conn->error;
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: $redirect_file");
    exit;
}

// --- DELETE Single & Re-index (Existing) ---
if ($action == 'delete' && $item_id > 0) {
    $image_to_delete = '';
    
    // 1. Select image path before deleting the record
    $stmt_select = $conn->prepare("SELECT image_path FROM $table_name WHERE $item_key_name = ?");
    $stmt_select->bind_param("i", $item_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($row = $result_select->fetch_assoc()) $image_to_delete = $row['image_path'];
    $stmt_select->close();

    // 2. Delete the record
    $stmt = $conn->prepare("DELETE FROM $table_name WHERE $item_key_name = ?");
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            
            // 3. Perform Re-indexing after deletion
            if (reindex_staff_ids($conn, $table_name, $item_key_name)) {
                $_SESSION['message'] = "Official/Staff (ID: $item_id) deleted and IDs re-indexed successfully.";
                $_SESSION['msg_type'] = "success";
                // --- ADDED LOGGING ---
                log_activity($conn, $admin_username, "Deleted official/staff ID **$item_id** and performed ID re-indexing.");
            } else {
                $_SESSION['message'] = "Official/Staff deleted, but error during ID re-indexing. Please perform a manual ID reset.";
                $_SESSION['msg_type'] = "warning";
            }

            // 4. Delete the physical image file
            if (!empty($image_to_delete)) {
                $file_path = '../' . $image_to_delete;
                if (file_exists($file_path)) @unlink($file_path);
            }
        } else {
            $_SESSION['message'] = "Error deleting official/staff: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error preparing delete statement.";
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: $redirect_file");
    exit;
}

// --- POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    // --- Bulk Delete & Re-index (Existing) ---
    if (isset($_POST['bulk_action'], $_POST['selected_items']) && $_POST['bulk_action'] === 'delete_selected' && is_array($_POST['selected_items'])) {
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        
        if (!empty($item_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));

            // 1. SELECT all image paths before deleting the records (for file cleanup)
            $images_to_delete = [];
            $sql_select_images = "SELECT image_path FROM $table_name WHERE $item_key_name IN ($placeholders)";
            $stmt_select = $conn->prepare($sql_select_images);
            
            // Bind the dynamic parameters
            $bind_params_select = array_merge([$types], $item_ids_to_delete);
            call_user_func_array([$stmt_select, 'bind_param'], array_by_ref($bind_params_select));
            $stmt_select->execute();
            $result_select = $stmt_select->get_result();
            while ($row = $result_select->fetch_assoc()) {
                $images_to_delete[] = $row['image_path'];
            }
            $stmt_select->close();

            // 2. DELETE the records
            $stmt_delete = $conn->prepare("DELETE FROM $table_name WHERE $item_key_name IN ($placeholders)");
            if ($stmt_delete) {
                // Bind the dynamic parameters for deletion
                $bind_params_delete = array_merge([$types], $item_ids_to_delete);
                call_user_func_array([$stmt_delete, 'bind_param'], array_by_ref($bind_params_delete));

                if ($stmt_delete->execute()) {
                    $deleted_count = count($item_ids_to_delete);

                    // 3. Perform Re-indexing after bulk deletion
                    if (reindex_staff_ids($conn, $table_name, $item_key_name)) {
                        $_SESSION['message'] = "$deleted_count record(s) deleted and IDs re-indexed successfully.";
                        $_SESSION['msg_type'] = "success";
                        // --- ADDED LOGGING ---
                        log_activity($conn, $admin_username, "Performed **bulk deletion** of $deleted_count official/staff records (IDs: " . implode(', ', $item_ids_to_delete) . ") and re-indexed IDs.");
                    } else {
                        $_SESSION['message'] = "$deleted_count record(s) deleted, but error during ID re-indexing. Please perform a manual ID reset.";
                        $_SESSION['msg_type'] = "warning";
                    }

                    // 4. Delete the physical image files
                    foreach ($images_to_delete as $image_path) {
                        if (!empty($image_path)) {
                            $file_path = '../' . $image_path;
                            if (file_exists($file_path)) @unlink($file_path);
                        }
                    }

                } else {
                    $_SESSION['message'] = "Error deleting records: " . $stmt_delete->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt_delete->close();
            }
        }
        header("Location: $redirect_file");
        exit;
    }

    // --- Add/Update Single Official/Staff (Includes Uniqueness Check) ---
    if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['add_official', 'update_official'])) {
        $is_update = ($_POST['action_type'] === 'update_official');
        $item_id = $is_update ? (int)($_POST['item_id'] ?? 0) : 0;

        // Collect and sanitize data
        $full_name = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $section = trim($_POST['section'] ?? '');
        $quote = trim($_POST['quote'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';
        $new_image_path = $current_image;

        if (empty($full_name) || empty($role) || empty($section)) {
            $_SESSION['message'] = "Full Name, Role, and Section are required.";
            $_SESSION['msg_type'] = "danger";
            header('Location: ' . $redirect_file . '?action=' . ($is_update ? 'edit&' . $item_key_name . '=' . $item_id : 'add'));
            exit;
        }

        // VALIDATION: Role/Section Consistency (Uses UNFILTERED $roles_by_section)
        $valid_roles = $roles_by_section[$section]['roles'] ?? [];
        if (!in_array($role, $valid_roles)) {
            $_SESSION['message'] = "Invalid Role selected for the chosen Section.";
            $_SESSION['msg_type'] = "danger";
            header('Location: ' . $redirect_file . '?action=' . ($is_update ? 'edit&' . $item_key_name . '=' . $item_id : 'add'));
            exit;
        }

        // 🚨 UNQIUENESS CHECK: Check if the Role + Section combination is already taken by a DIFFERENT record.
        $sql_check = "SELECT $item_key_name FROM $table_name WHERE role = ? AND section = ?";
        $params = [$role, $section];
        $types = 'ss';

        // Exclude the current record's ID if we are updating
        if ($is_update) {
            $sql_check .= " AND $item_key_name != ?";
            $params[] = $item_id;
            $types .= 'i';
        }

        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
            $bind_params_check = array_merge([$types], $params);
            call_user_func_array([$stmt_check, 'bind_param'], array_by_ref($bind_params_check)); 
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // If a duplicate is found, show a WARNING and redirect back.
                $_SESSION['message'] = "WARNING: The position **" . htmlspecialchars(ucwords(strtolower($role))) . "** in the **" . htmlspecialchars($all_sections[$section]) . "** section is already taken by another official/staff. Choose a different Role or Section.";
                $_SESSION['msg_type'] = "warning";
                $stmt_check->close();
                header('Location: ' . $redirect_file . '?action=' . ($is_update ? 'edit&' . $item_key_name . '=' . $item_id : 'add'));
                exit;
            }
            $stmt_check->close();
        }


        // Image upload logic (Image cleanup on replace is already handled)
        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/' . $upload_dir_name . '/'; 
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = basename($_FILES['image_path']['name']);
            $name_slug = preg_replace("/[^a-zA-Z0-9\-]/", "-", strtolower(str_replace(' ', '-', $full_name)));
            $unique_name = $name_slug . '_' . time() . '_' . $filename;
            $destination = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['image_path']['tmp_name'], $destination)) {
                $new_image_path = 'assets/' . $upload_dir_name . '/' . $unique_name;
                
                if ($is_update && !empty($current_image) && $current_image !== $new_image_path) {
                    $old_file = '../' . $current_image;
                    if (file_exists($old_file)) @unlink($old_file);
                }
            } else {
                $_SESSION['message'] = "Error uploading image.";
                $_SESSION['msg_type'] = "danger";
                header('Location: ' . $redirect_file . '?action=' . ($is_update ? 'edit&' . $item_key_name . '=' . $item_id : 'add'));
                exit;
            }
        }

        // Database Insert/Update
        if ($is_update) {
            $sql = "UPDATE $table_name SET full_name=?, role=?, section=?, quote=?, image_path=?, sort_order=? WHERE $item_key_name=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // sssssii (5 strings, 1 integer for sort_order, 1 integer for staff_id)
                $stmt->bind_param("sssssii", $full_name, $role, $section, $quote, $new_image_path, $sort_order, $item_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Official/Staff updated successfully!";
                    $_SESSION['msg_type'] = "success";
                    // --- ADDED LOGGING ---
                    log_activity($conn, $admin_username, "Updated official/staff ID **$item_id**: **" . htmlspecialchars($full_name) . " / " . htmlspecialchars(ucwords(strtolower($role))) . "**.");
                } else {
                    $_SESSION['message'] = "Error updating official/staff: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        } else {
            $sql = "INSERT INTO $table_name (full_name, role, section, quote, image_path, sort_order) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // sssssi (5 strings, 1 integer)
                $stmt->bind_param("sssssi", $full_name, $role, $section, $quote, $new_image_path, $sort_order);
                if ($stmt->execute()) {
                    // Assuming staff_id is AUTO_INCREMENT, get the new ID for logging
                    $new_id_log = $conn->insert_id; 

                    $_SESSION['message'] = "New official/staff added successfully! NOTE: Consider running 'Reset Item IDs' if IDs seem out of sequence.";
                    $_SESSION['msg_type'] = "success";
                    // --- ADDED LOGGING ---
                    log_activity($conn, $admin_username, "Added new official/staff (ID: **$new_id_log**): **" . htmlspecialchars($full_name) . " / " . htmlspecialchars(ucwords(strtolower($role))) . "**.");
                } else {
                    $_SESSION['message'] = "Error adding official/staff: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }

        header("Location: $redirect_file");
        exit;
    }
}

// --- Edit Mode: Prefill Form ---
if ($action === 'edit' && $item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM $table_name WHERE $item_key_name = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $official_data = array_merge($official_data, $result->fetch_assoc());
    } else {
        $_SESSION['message'] = "Official/Staff not found";
        $_SESSION['msg_type'] = "danger";
        header("Location: $redirect_file");
        exit;
    }
    $stmt->close();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?></title>
<link rel="icon" type="image/png" href="../assets/imgs/logo_white.png">

<script src="https://cdn.tailwindcss.com"></script>

<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>

<script>
    // Tailwind Config (Same as the original design)
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'dark-bg': '#0f172a',      
                    'dark-card': '#1e293b',    
                    'dark-input': '#334155',   
                    'dark-border': '#475569',  
                    'primary-indigo': '#6366f1', 
                    'text-light': '#e2e8f0',   
                },
                spacing: {
                    '250': '250px', 
                }
            }
        }
    }
</script>

<link rel="stylesheet" href="assets/css/sidebar.css">

</head>
<body class="bg-dark-bg text-text-light">
    <div class="admin-sidebar">
        <?php 
        $current_file = basename($_SERVER['PHP_SELF']);
        include 'includes/admin_sidebar.php'; 
        ?>
    </div>

    <div id="page-content-wrapper" class="ml-250 p-8 w-[calc(100%-250px)] min-h-screen box-border">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold mb-6 text-white"><?php echo $page_title; ?></h1>

            <?php if (isset($_SESSION['message'])): ?>
                <?php 
                    // Added check for 'warning' message type
                    $alert_color = $_SESSION['msg_type'] == 'success' ? 'bg-green-500' : ($_SESSION['msg_type'] == 'danger' ? 'bg-red-500' : ($_SESSION['msg_type'] == 'warning' ? 'bg-yellow-500' : 'bg-blue-500'));
                    $alert_icon = $_SESSION['msg_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
                ?>
                <div class="<?php echo $alert_color; ?> text-white p-4 rounded-lg mb-6 flex items-center justify-between">
                    <div class="flex items-center">
                           <i class="fas <?php echo $alert_icon; ?> mr-3"></i>
                           <?php echo $_SESSION['message']; ?>
                    </div>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <div class="flex items-center space-x-4 mb-6">
                    <a href="<?php echo $redirect_file; ?>?action=add" class="inline-flex items-center px-4 py-2 bg-primary-indigo hover:bg-indigo-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-user-plus mr-2"></i> Add New Official/Staff
                    </a>

                    <button id="resetIdButton" onclick="confirmResetIds()" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-undo-alt mr-2"></i> Reset Item IDs
                    </button>
                    </div>
                    <hr class="border-dark-border mb-6">

                <?php 
                // Listing Query - Order by staff_id ASC to reflect re-indexing
                $query = "SELECT staff_id, full_name, role, section, sort_order, image_path, quote 
                              FROM $table_name 
                              ORDER BY staff_id ASC"; // Changed ORDER BY to staff_id ASC for sequence integrity
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected records? (This action is irreversible and will re-index all remaining IDs)')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        <div class="overflow-x-auto shadow-xl rounded-lg">
                            <table class="w-full text-left text-text-light rounded-lg">
                                <thead class="text-xs uppercase bg-dark-bg text-white border-b-2 border-dark-border">
                                    <tr>
                                        <th scope="col" class="p-4"><input type="checkbox" id="select_all_items" title="Select All" class="form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></th>
                                        <th scope="col" class="py-3 px-6">ID</th>
                                        <th scope="col" class="py-3 px-6">Sort</th>
                                        <th scope="col" class="py-3 px-6">Image</th>
                                        <th scope="col" class="py-3 px-6">Full Name</th>
                                        <th scope="col" class="py-3 px-6">Role</th>
                                        <th scope="col" class="py-3 px-6">Section</th>
                                        <th scope="col" class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4"><input type="checkbox" name="selected_items[]" value="<?php echo $row['staff_id']; ?>" class="item_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></td>
                                        <td class="py-4 px-6"><?php echo $row['staff_id']; ?></td>
                                        <td class="py-4 px-6"><?php echo $row['sort_order']; ?></td>
                                        <td class="py-4 px-6">
                                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['full_name']); ?>" 
                                                class="official-img-thumb w-12 h-12 object-cover rounded-full border border-dark-border"
                                                onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'">
                                        </td>
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="py-4 px-6"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary-indigo text-white"><?php echo htmlspecialchars(ucwords(strtolower($row['role']))); ?></span></td>
                                        <td class="py-4 px-6 text-sm text-gray-400"><?php echo htmlspecialchars($all_sections[$row['section']] ?? $row['section']); ?></td>
                                        <td class="py-4 px-6 flex space-x-2">
                                            <a href="<?php echo $redirect_file; ?>?action=edit&<?php echo $item_key_name; ?>=<?php echo $row['staff_id']; ?>" class="p-2 text-dark-bg bg-yellow-400 hover:bg-yellow-500 rounded-lg" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="<?php echo $redirect_file; ?>?action=delete&<?php echo $item_key_name; ?>=<?php echo $row['staff_id']; ?>" onclick="return confirm('Are you sure you want to delete this record? (This will re-index all remaining IDs)')" class="p-2 text-white bg-red-600 hover:bg-red-700 rounded-lg" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="mt-6 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md disabled:opacity-50 transition duration-200" id="delete_selected_btn" disabled>
                            <i class="fas fa-trash-alt mr-2"></i> Delete Selected Records
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-blue-900/40 text-blue-300 p-4 rounded-xl border border-blue-700 shadow-lg">No officials or staffs found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?></h2>
                <p class="text-gray-400 mb-6"><a href="<?php echo $redirect_file; ?>?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to List</a></p>
                <hr class="border-dark-border mb-6">
                
                <form action="<?php echo $redirect_file; ?>" method="POST" enctype="multipart/form-data" class="bg-dark-card p-8 rounded-xl shadow-2xl max-w-6xl">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_official' : 'add_official'; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
                        <div>
                            <div class="mb-4">
                                <label for="full_name" class="block text-sm font-medium text-text-light mb-1">Full Name</label>
                                <input type="text" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="full_name" name="full_name" value="<?php echo htmlspecialchars($official_data['full_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="section" class="block text-sm font-medium text-text-light mb-1">Section / Group</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="section" name="section" required onchange="updateRoleOptions()">
                                    <option value="" disabled <?php echo empty($official_data['section']) ? 'selected' : ''; ?>>Select Group/Section</option>
                                    <?php foreach ($sections as $slug => $display_name): // $sections is now FILTERED ?>
                                        <option value="<?php echo htmlspecialchars($slug); ?>" 
                                                     <?php echo (isset($official_data['section']) && $official_data['section'] == $slug) ? 'selected' : ''; ?>>
                                                     <?php echo htmlspecialchars($display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="role" class="block text-sm font-medium text-text-light mb-1">Specific Role / Position</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="role" name="role" required>
                                    <option value="" disabled selected>Select Role</option>
                                    </select>
                            </div>

                            <div class="mb-4">
                                <label for="sort_order" class="block text-sm font-medium text-text-light mb-1">Sort Order</label>
                                <input type="number" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($official_data['sort_order'] ?? '0'); ?>" required>
                                <small class="text-sm text-gray-400">Lower number means higher priority on the public page.</small>
                            </div>
                        </div>

                        <div>
                            <div class="mb-4">
                                <label for="quote" class="block text-sm font-medium text-text-light mb-1">Quote / Short Description (Optional)</label>
                                <textarea class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="quote" name="quote" rows="4"><?php echo htmlspecialchars($official_data['quote'] ?? ''); ?></textarea>
                            </div>
                            
                            <h3 class="text-xl font-semibold text-white mt-6 mb-3">Image Upload</h3>
                            <hr class="border-dark-border mb-6">
                            
                            <div class="mb-6">
                                <label for="image_path" class="block w-full text-sm font-medium text-text-light mb-1">Profile Image Upload</label>
                                <input type="file" class="block w-full text-sm text-text-light file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 cursor-pointer" id="image_path" name="image_path" accept="image/*" <?php echo ($action == 'add' && empty($official_data['image_path'])) ? 'required' : ''; ?>>
                                
                                <?php if (!empty($official_data['image_path'])): ?>
                                    <div class="mt-4">
                                        <span class="text-sm font-medium text-text-light">Current Image:</span> <br>
                                        <img src="../<?php echo htmlspecialchars($official_data['image_path']); ?>" alt="Current Profile Image" class="w-32 h-32 object-cover rounded-full mt-2 border-4 border-primary-indigo">
                                    </div>
                                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($official_data['image_path']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t border-dark-border flex space-x-3">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                            <i class="fas fa-save mr-2"></i> Save Record
                        </button>
                        <a href="<?php echo $redirect_file; ?>" class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-200">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

<script>
// --- JavaScript for Bulk Select and Delete Button Activation (Existing) ---
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select_all_items');
    const itemCheckboxes = document.querySelectorAll('.item_checkbox');
    const deleteButton = document.getElementById('delete_selected_btn');

    if (selectAllCheckbox && itemCheckboxes.length > 0) {
        
        function updateDeleteButtonState() {
            const selected = document.querySelectorAll('.item_checkbox:checked').length;
            deleteButton.disabled = selected === 0;
            deleteButton.classList.toggle('opacity-50', deleteButton.disabled);
        }

        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateDeleteButtonState();
        });

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!cb.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    selectAllCheckbox.checked = Array.from(itemCheckboxes).every(c => c.checked);
                }
                updateDeleteButtonState();
            });
        });

        updateDeleteButtonState();
    }
    
    // Initial call for the conditional role selector on page load
    const sectionSelect = document.getElementById('section');
    if (sectionSelect && sectionSelect.value) {
        updateRoleOptions();
    }
});

// --- JavaScript for ID Reset Button (Existing) ---
function confirmResetIds() {
    if (confirm("DANGER: This action will permanently re-index ALL Official/Staff IDs in the database to start from 1. Proceed?")) {
        window.location.href = '<?php echo $redirect_file; ?>?action=reset_ids';
    }
}

// --- JavaScript for Conditional Role Selection (Uses Filtered Data) ---
// PHP passes the FILTERED roles structure to JavaScript
const rolesBySection = <?php echo json_encode($roles_by_section_for_js); ?>;
const initialRole = "<?php echo htmlspecialchars($official_data['role'] ?? ''); ?>";

function updateRoleOptions() {
    const sectionSelect = document.getElementById('section');
    const roleSelect = document.getElementById('role');
    const selectedSection = sectionSelect.value;
    
    // Clear existing options, maintain the disabled placeholder
    roleSelect.innerHTML = '<option value="" disabled selected>Select Role</option>';
    
    if (selectedSection && rolesBySection[selectedSection]) {
        const roles = rolesBySection[selectedSection].roles;
        
        roles.forEach(role => {
            const option = document.createElement('option');
            option.value = role;
            // Title case display, but all caps value for consistency with database
            option.textContent = role.split(' ').map(w => w.charAt(0) + w.slice(1).toLowerCase()).join(' '); 
            
            // Re-select the existing role in edit mode
            if (role === initialRole) {
                option.selected = true;
            }
            
            roleSelect.appendChild(option);
        });

        // Auto-select the role if there is only one option available 
        // AND the initialRole (from database in edit mode) is not already set.
        if (roles.length === 1 && !initialRole) {
            roleSelect.value = roles[0];
        }
    }
}
</script>

</body>
</html>

<?php 
if (isset($conn)) $conn->close();
?>