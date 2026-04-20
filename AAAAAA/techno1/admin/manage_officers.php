<?php 
// technowatch/admin/manage_officers.php - Club Officers/Members CRUD Management View (REWRITTEN FOR ID COMPACTION & RESET BUTTON)

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

// Helper function for call_user_func_array with bind_param (for bulk delete)
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}

/**
 * Executes the ID Compaction logic: Resets IDs to be sequential (1, 2, 3...)
 * This method is inefficient and destructive, as it truncates and recreates the table.
 * It also resets the table's AUTO_INCREMENT value.
 * @param mysqli $conn The database connection.
 * @param string $table_name The name of the officers table.
 */
function compact_officer_ids($conn, $table_name) {
    // 1. Fetch all records sorted by current officer_id
    // Note: We MUST retrieve the records in the order they were inserted or currently stored (by officer_id ASC)
    $sql_select = "SELECT full_name, position, category, short_bio, email, image_path, sort_order FROM $table_name ORDER BY officer_id ASC";
    $result = $conn->query($sql_select);
    if (!$result) return false;

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    // 2. Clear the table and reset AUTO_INCREMENT to 1
    // TRUNCATE is a DDL command that resets the AUTO_INCREMENT value.
    if (!$conn->query("TRUNCATE TABLE $table_name")) return false;

    // 3. Re-insert the records. Since officer_id is AUTO_INCREMENT,
    //    it will automatically get sequential IDs starting from 1.
    if (!empty($records)) {
        // Ensure the columns match the order in the SELECT statement
        $sql_insert = "INSERT INTO $table_name (full_name, position, category, short_bio, email, image_path, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        if (!$stmt) return false;

        foreach ($records as $record) {
            $stmt->bind_param(
                "ssssssi", 
                $record['full_name'], 
                $record['position'], 
                $record['category'], 
                $record['short_bio'], 
                $record['email'], 
                $record['image_path'], 
                $record['sort_order']
            );
            $stmt->execute();
        }
        $stmt->close();
    }
    return true;
}

// --- Configuration & Initialization ---
$page_title = 'Manage Club Officers & Members';
$item_key_name = 'officer_id'; // The primary key column name
$table_name = 'officers_club';
$action = $_GET['action'] ?? 'list';
$item_id = isset($_GET[$item_key_name]) ? (int)$_GET[$item_key_name] : 0; 
$form_title = ($action == 'edit' ? 'Edit Club Officer/Member' : 'Add New Club Officer/Member');
$redirect_file = 'manage_officers.php';
$upload_dir_name = 'officers'; // Directory inside assets

// Form pre-fill defaults
$officer_data = [
    'officer_id' => 0,
    'full_name' => '',
    'position' => '',
    'category' => '', 
    'short_bio' => '', 
    'email' => '',
    'image_path' => '',
    'sort_order' => 0, 
];

// Configuration for select options (based on your latest images)
$roles_club = [
    'Club President', 
    'Vice President', 
    'Vice President Assistant', 
    'Secretary', 
    'Treasurer', 
    'Auditor', 
    'Pio', 
    'Member' 
];
$categories_club = [
    'EXECUTIVE' => 'Executive Officers',
    'REPRESENTATIVES' => 'Club Representatives',
    'CREATIVES' => 'Club Creatives',
];

// --- LOGIC ADDED: Fetch occupied roles for EXECUTIVE category ---
$occupied_executive_roles = [];
if ($action == 'add' || $action == 'edit') {
    // Select all roles currently assigned to the EXECUTIVE category, excluding the one being edited
    $sql_occupied = "SELECT position FROM $table_name WHERE category = 'EXECUTIVE'";
    if ($action == 'edit' && $item_id > 0) {
        // Exclude the current officer's role when editing
        $sql_occupied .= " AND officer_id != ?";
        $stmt_occupied = $conn->prepare($sql_occupied);
        $stmt_occupied->bind_param("i", $item_id);
    } else {
        $stmt_occupied = $conn->prepare($sql_occupied);
    }
    
    if ($stmt_occupied) {
        $stmt_occupied->execute();
        $result_occupied = $stmt_occupied->get_result();
        while ($row = $result_occupied->fetch_assoc()) {
            $occupied_executive_roles[] = $row['position'];
        }
        $stmt_occupied->close();
    }
}
// Pass occupied roles to the JavaScript for dynamic filtering
$occupied_roles_json = json_encode($occupied_executive_roles);


// --- DELETE Single (COMPACTS IDS & DELETES IMAGE) ---
if ($action == 'delete' && $item_id > 0) {
    $image_to_delete = '';
    
    // Fetch image path
    $stmt_select = $conn->prepare("SELECT image_path FROM $table_name WHERE $item_key_name = ?");
    $stmt_select->bind_param("i", $item_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($row = $result_select->fetch_assoc()) $image_to_delete = $row['image_path'];
    $stmt_select->close();

    // START Transaction for Delete & Compaction
    $conn->begin_transaction();
    $delete_success = false;

    try {
        // 1. Delete the record
        $stmt = $conn->prepare("DELETE FROM $table_name WHERE $item_key_name = ?");
        if (!$stmt) throw new Exception("Database error preparing delete statement: " . $conn->error);
        
        $stmt->bind_param("i", $item_id);
        if (!$stmt->execute()) throw new Exception("Error deleting club officer/member: " . $stmt->error);
        $stmt->close();
        
        // 2. Compact IDs (Resets IDs to 1, 2, 3...)
        if (!compact_officer_ids($conn, $table_name)) throw new Exception("Error during ID compaction.");

        // 3. Commit transaction
        $conn->commit();
        $delete_success = true;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }

    if ($delete_success) {
        $_SESSION['message'] = "Club Officer/Member (Old ID: $item_id) deleted successfully and IDs reordered.";
        $_SESSION['msg_type'] = "success";
        // --- ADDED LOGGING ---
        log_activity($conn, $admin_username, "Deleted club officer/member (Old ID: **$item_id**) and performed ID compaction.");

        // 4. Delete the physical image file
        if (!empty($image_to_delete)) {
            $file_path = '../' . $image_to_delete;
            if (file_exists($file_path)) @unlink($file_path);
        }
    }
    
    header("Location: $redirect_file");
    exit;
}

// --- ACTION: Manual ID Reset (Compaction) ---
if ($action == 'reset_ids') {
    $conn->begin_transaction();
    $success = false;
    
    try {
        if (!compact_officer_ids($conn, $table_name)) throw new Exception("Error during ID compaction/reset.");
        
        $conn->commit();
        $success = true;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error resetting IDs: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }

    if ($success) {
        $_SESSION['message'] = "All officer IDs have been successfully reset and compacted (1, 2, 3...).";
        $_SESSION['msg_type'] = "success";
        // --- ADDED LOGGING ---
        log_activity($conn, $admin_username, "Performed **full ID reset/compaction** on Club Officers/Members table.");
    }
    
    header("Location: $redirect_file");
    exit;
}


// --- POST Actions (Bulk Delete & Single Add/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    // --- Bulk Delete (Compacts IDs & DELETES IMAGES) ---
    if (isset($_POST['bulk_action'], $_POST['selected_items']) && $_POST['bulk_action'] === 'delete_selected' && is_array($_POST['selected_items'])) {
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        $deleted_count = 0;
        $images_to_delete = [];

        if (!empty($item_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));

            // START Transaction for Bulk Delete & Compaction
            $conn->begin_transaction();
            $delete_success = false;
            
            try {
                // 1. Fetch image paths for selected items
                $stmt_select = $conn->prepare("SELECT image_path FROM $table_name WHERE $item_key_name IN ($placeholders)");
                if (!$stmt_select) throw new Exception("Database error preparing select statement: " . $conn->error);

                $bind_params_select = array_merge([$types], $item_ids_to_delete);
                call_user_func_array([$stmt_select, 'bind_param'], array_by_ref($bind_params_select));
                $stmt_select->execute();
                $result_select = $stmt_select->get_result();
                while ($row = $result_select->fetch_assoc()) {
                    if (!empty($row['image_path'])) $images_to_delete[] = $row['image_path'];
                }
                $stmt_select->close();

                // 2. Delete the records
                $stmt_delete = $conn->prepare("DELETE FROM $table_name WHERE $item_key_name IN ($placeholders)");
                if (!$stmt_delete) throw new Exception("Database error preparing delete statement: " . $conn->error);

                $bind_params_delete = array_merge([$types], $item_ids_to_delete);
                call_user_func_array([$stmt_delete, 'bind_param'], array_by_ref($bind_params_delete));
                
                if (!$stmt_delete->execute()) throw new Exception("Error deleting club officer/member records: " . $stmt_delete->error);
                $deleted_count = $stmt_delete->affected_rows;
                $stmt_delete->close();
                
                // 3. Compact IDs (Resets IDs to 1, 2, 3...)
                if (!compact_officer_ids($conn, $table_name)) throw new Exception("Error during ID compaction.");
                
                // 4. Commit transaction
                $conn->commit();
                $delete_success = true;

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['msg_type'] = "danger";
            }
            
            if ($delete_success) {
                $_SESSION['message'] = "$deleted_count club officer/member record(s) deleted successfully and IDs reordered.";
                $_SESSION['msg_type'] = "success";
                // --- ADDED LOGGING ---
                log_activity($conn, $admin_username, "Performed **bulk deletion** of $deleted_count club officer/member records (Old IDs: " . implode(', ', $item_ids_to_delete) . ") and compacted IDs.");

                // 5. Delete the physical image files
                foreach ($images_to_delete as $image_path) {
                    $file_path = '../' . $image_path;
                    if (file_exists($file_path)) @unlink($file_path);
                }
            }
        }
        header("Location: $redirect_file");
        exit;
    }

    // --- Add/Update Single Officer/Member (NO CHANGE NEEDED) ---
    if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['add_officer', 'update_officer'])) {
        $is_update = ($_POST['action_type'] === 'update_officer');
        $item_id = $is_update ? (int)($_POST['item_id'] ?? 0) : 0;

        // Collect and sanitize data (EMAIL FIELD ADDED HERE)
        $full_name = trim($_POST['full_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $short_bio = trim($_POST['short_bio'] ?? ''); 
        $email = trim($_POST['email'] ?? NULL); // Optional email field
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';
        $new_image_path = $current_image;

        // **LOGIC ADDED: Server-side check for executive role duplication**
        if ($category === 'EXECUTIVE' && $position !== 'Member') {
            // Check if this position is already taken by another executive officer
            $sql_check = "SELECT officer_id FROM $table_name WHERE category = 'EXECUTIVE' AND position = ?";
            if ($is_update && $item_id > 0) {
                $sql_check .= " AND officer_id != ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("si", $position, $item_id);
            } else {
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("s", $position);
            }
            
            if ($stmt_check) {
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $_SESSION['message'] = "Error: The role '{$position}' is already taken by another Executive Officer. Please choose an available role or select a different category.";
                    $_SESSION['msg_type'] = "danger";
                    $stmt_check->close();
                    header('Location: ' . $redirect_file . '?action=' . ($is_update ? 'edit&' . $item_key_name . '=' . $item_id : 'add'));
                    exit;
                }
                $stmt_check->close();
            }
        }

        if (empty($full_name) || empty($position) || empty($category)) {
            $_SESSION['message'] = "Full Name, Role, and Category are required.";
            $_SESSION['msg_type'] = "danger";
            header('Location: ' . $redirect_file . '?action=' . ($is_update ? 'edit&' . $item_key_name . '=' . $item_id : 'add'));
            exit;
        }

        // Image upload logic
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

        if ($is_update) {
            // UPDATED SQL: Added email column
            $sql = "UPDATE $table_name SET full_name=?, position=?, category=?, short_bio=?, email=?, image_path=?, sort_order=? WHERE $item_key_name=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // ssssssii (6 strings (including email), 1 integer sort_order, 1 integer officer_id)
                $stmt->bind_param("ssssssii", $full_name, $position, $category, $short_bio, $email, $new_image_path, $sort_order, $item_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Club Officer/Member updated successfully!";
                    $_SESSION['msg_type'] = "success";
                    // --- ADDED LOGGING ---
                    log_activity($conn, $admin_username, "Updated club officer/member ID **$item_id**: **" . htmlspecialchars($full_name) . " / " . htmlspecialchars($position) . "**.");
                } else {
                    $_SESSION['message'] = "Error updating club officer/member: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        } else {
            // UPDATED SQL: Added email column
            $sql = "INSERT INTO $table_name (full_name, position, category, short_bio, email, image_path, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // ssssssi (6 strings (including email), 1 integer sort_order)
                $stmt->bind_param("ssssssi", $full_name, $position, $category, $short_bio, $email, $new_image_path, $sort_order);
                if ($stmt->execute()) {
                    $new_id_log = $conn->insert_id; // Get the newly inserted ID
                    $_SESSION['message'] = "New club officer/member added successfully!";
                    $_SESSION['msg_type'] = "success";
                    // --- ADDED LOGGING ---
                    log_activity($conn, $admin_username, "Added new club officer/member (ID: **$new_id_log**): **" . htmlspecialchars($full_name) . " / " . htmlspecialchars($position) . "**.");
                } else {
                    $_SESSION['message'] = "Error adding club officer/member: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }

        header("Location: $redirect_file");
        exit;
    }
}

// --- Edit Mode: Prefill Form (NO CHANGE NEEDED) ---
if ($action === 'edit' && $item_id > 0) {
    // UPDATED SELECT: Ensure email is selected
    $stmt = $conn->prepare("SELECT officer_id, full_name, position, category, short_bio, email, image_path, sort_order FROM $table_name WHERE $item_key_name = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $officer_data = array_merge($officer_data, $result->fetch_assoc());
    } else {
        $_SESSION['message'] = "Club Officer/Member not found";
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
                    $alert_color = $_SESSION['msg_type'] == 'success' ? 'bg-green-500' : ($_SESSION['msg_type'] == 'danger' ? 'bg-red-500' : 'bg-blue-500');
                    // Add warning color check for consistency, though this file only uses success/danger
                    if ($_SESSION['msg_type'] == 'warning') {
                         $alert_color = 'bg-yellow-500';
                    }
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
                        <i class="fas fa-users-cog mr-2"></i> Add New Club Officer/Member
                    </a>

                    <button id="resetIdButton" onclick="confirmResetIds('<?php echo $redirect_file; ?>?action=reset_ids')" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                         <i class="fas fa-undo-alt mr-2"></i> Reset Item IDs
                    </button>
                    </div>

                    <hr class="border-dark-border mb-6">

                <?php 
                // Listing Query - No change, it will now reflect the reordered IDs
                $query = "SELECT officer_id, full_name, position, category, sort_order, image_path, short_bio 
                              FROM $table_name 
                              ORDER BY category ASC, sort_order ASC, officer_id DESC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected records? (This action will delete the corresponding images and reorder all remaining IDs sequentially starting from 1!)')">
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
                                        <th scope="col" class="py-3 px-6">Position</th>
                                        <th scope="col" class="py-3 px-6">Category</th>
                                        <th scope="col" class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4"><input type="checkbox" name="selected_items[]" value="<?php echo $row['officer_id']; ?>" class="item_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></td>
                                        <td class="py-4 px-6"><?php echo $row['officer_id']; ?></td>
                                        <td class="py-4 px-6"><?php echo $row['sort_order']; ?></td>
                                        <td class="py-4 px-6">
                                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['full_name']); ?>" 
                                                class="official-img-thumb w-12 h-12 object-cover rounded-full border border-dark-border"
                                                onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'">
                                        </td>
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="py-4 px-6"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary-indigo text-white"><?php echo htmlspecialchars($row['position']); ?></span></td>
                                        <td class="py-4 px-6 text-sm text-gray-400"><?php echo htmlspecialchars($categories_club[$row['category']] ?? $row['category']); ?></td>
                                        <td class="py-4 px-6 flex space-x-2">
                                            <a href="<?php echo $redirect_file; ?>?action=edit&<?php echo $item_key_name; ?>=<?php echo $row['officer_id']; ?>" class="p-2 text-dark-bg bg-yellow-400 hover:bg-yellow-500 rounded-lg" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="<?php echo $redirect_file; ?>?action=delete&<?php echo $item_key_name; ?>=<?php echo $row['officer_id']; ?>" onclick="return confirm('Are you sure you want to delete this record? (This action will delete the corresponding image and reorder all remaining IDs sequentially starting from 1!)')" class="p-2 text-white bg-red-600 hover:bg-red-700 rounded-lg" title="Delete"><i class="fas fa-trash"></i></a>
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
                    <div class="bg-blue-900/40 text-blue-300 p-4 rounded-xl border border-blue-700 shadow-lg">No club officers or member found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?> </h2>
                <p class="text-gray-400 mb-6"><a href="<?php echo $redirect_file; ?>?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to List</a></p>
                <hr class="border-dark-border mb-6">
                
                <form action="<?php echo $redirect_file; ?>" method="POST" enctype="multipart/form-data" class="bg-dark-card p-8 rounded-xl shadow-2xl max-w-6xl">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_officer' : 'add_officer'; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
                        <div>
                            <div class="mb-4">
                                <label for="full_name" class="block text-sm font-medium text-text-light mb-1">Full Name</label>
                                <input type="text" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="full_name" name="full_name" value="<?php echo htmlspecialchars($officer_data['full_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="category" class="block text-sm font-medium text-text-light mb-1">Category (Determines Grouping)</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="category" name="category" required>
                                    <option value="" disabled <?php echo empty($officer_data['category']) ? 'selected' : ''; ?>>Select Category</option>
                                    <?php foreach ($categories_club as $slug => $display_name): ?>
                                        <option value="<?php echo htmlspecialchars($slug); ?>" 
                                            <?php echo (isset($officer_data['category']) && $officer_data['category'] == $slug) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="position" class="block text-sm font-medium text-text-light mb-1">Role</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="position" name="position" required>
                                    <option value="" disabled <?php echo empty($officer_data['position']) ? 'selected' : ''; ?>>Select Role</option>
                                </select>
                                <small class="text-sm text-gray-400" id="role_hint"></small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="sort_order" class="block text-sm font-medium text-text-light mb-1">Sort Order</label>
                                <input type="number" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($officer_data['sort_order'] ?? '0'); ?>" required>
                                <small class="text-sm text-gray-400">Lower number means higher priority on the public page.</small>
                            </div>
                        </div>

                        <div>
                            <div class="mb-4">
                                <label for="short_bio" class="block text-sm font-medium text-text-light mb-1">Motto / Biography (Optional)</label>
                                <textarea class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="short_bio" name="short_bio" rows="4"><?php echo htmlspecialchars($officer_data['short_bio'] ?? ''); ?></textarea>
                            </div>

                            <h3 class="text-xl font-semibold text-white mt-6 mb-3 border-t border-dark-border pt-4">Contact Details</h3>
                            
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-text-light mb-1">Email (Optional)</label>
                                <input type="email" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="email" name="email" value="<?php echo htmlspecialchars($officer_data['email'] ?? ''); ?>">
                            </div>
                            <h3 class="text-xl font-semibold text-white mt-6 mb-3 border-t border-dark-border pt-4">Image Upload</h3>
                            
                            <div class="mb-6">
                                <label for="image_path" class="block w-full text-sm text-text-light file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 cursor-pointer">Officer Profile Image Upload</label>
                                
                                <input type="file" class="block w-full text-sm text-text-light file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 cursor-pointer" id="image_path" name="image_path" accept="image/*" <?php echo ($action == 'add' && empty($officer_data['image_path'])) ? 'required' : ''; ?>>

                                
                                <?php if (!empty($officer_data['image_path'])): ?>
                                    <div class="mt-4">
                                        <span class="text-sm font-medium text-text-light">Current Image:</span> <br>
                                        <img src="../<?php echo htmlspecialchars($officer_data['image_path']); ?>" alt="Current Profile Image" class="w-32 h-32 object-cover rounded-full mt-2 border-4 border-primary-indigo">
                                    </div>
                                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($officer_data['image_path']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t border-dark-border flex space-x-3">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                            <i class="fas fa-save mr-2"></i> Save Officer
                        </button>
                        <a href="<?php echo $redirect_file; ?>" class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-200">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

<script>
    // --- Configuration from PHP for JS ---
    const allRoles = <?php echo json_encode($roles_club); ?>;
    const occupiedExecutiveRoles = <?php echo $occupied_roles_json; ?>;
    const currentRole = '<?php echo htmlspecialchars($officer_data['position'] ?? ''); ?>';
    const currentCategory = '<?php echo htmlspecialchars($officer_data['category'] ?? ''); ?>';

    /**
     * Client-side confirmation for the dangerous ID reset action.
     */
    function confirmResetIds(redirectUrl) {
        if (confirm('WARNING: Are you absolutely sure you want to reset ALL Officer IDs?\n\nThis will reorder the IDs sequentially starting from 1 (e.g., old ID 3 will become new ID 2 if ID 1 was deleted). This cannot be undone and may break external references.')) {
            window.location.href = redirectUrl;
        }
    }

    // --- Dynamic Role/Category Logic ---
    function updateRoleOptions() {
        const categorySelect = document.getElementById('category');
        const positionSelect = document.getElementById('position');
        const roleHint = document.getElementById('role_hint');
        const selectedCategory = categorySelect.value;
        
        // Save the currently selected role to restore it if still valid
        const selectedPosition = positionSelect.value;

        // Clear existing options
        positionSelect.innerHTML = '<option value="" disabled>Select Role</option>';

        let availableRoles = [];
        let hint = '';

        if (selectedCategory === 'EXECUTIVE') {
            // Executive: Show available roles from President up to Pio
            const executiveRoles = allRoles.slice(0, allRoles.indexOf('Member'));
            
            // Filter out occupied roles, but ensure the current officer's role is included when editing
            availableRoles = executiveRoles.filter(role => 
                !occupiedExecutiveRoles.includes(role) || role === currentRole
            );
            hint = 'Only available executive roles (President - Pio) are shown. Roles are unique in this category.';

        } else if (selectedCategory === 'REPRESENTATIVES' || selectedCategory === 'CREATIVES') {
            // Representatives/Creatives: Only allow "Member"
            availableRoles = ['Member'];
            hint = 'Only the "Member" role is allowed for this category.';
        }

        // Add the available roles to the select dropdown
        availableRoles.forEach(role => {
            const option = document.createElement('option');
            option.value = role;
            option.textContent = role;
            
            // Re-select the option if it matches the previously selected role or the current data
            if (role === selectedPosition || (role === currentRole && role === selectedPosition && selectedCategory === currentCategory)) {
                 option.selected = true;
            }
            positionSelect.appendChild(option);
        });

        // If the old position is now invalid, reset the selection to the prompt
        if (!availableRoles.includes(selectedPosition) && selectedPosition !== currentRole) {
             positionSelect.value = "";
             // If in edit mode, and the role is valid for the category, select it
        } else if (currentRole && availableRoles.includes(currentRole) && selectedCategory === currentCategory) {
            positionSelect.value = currentRole;
        } else if (availableRoles.length === 1) {
            // Auto-select "Member" if it's the only option
            positionSelect.value = 'Member';
        } else {
             positionSelect.value = selectedPosition || ""; // Restore selection or keep default
        }

        roleHint.textContent = hint;
    }

    // --- General DOM Ready Logic ---
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('select_all_items');
        const itemCheckboxes = document.querySelectorAll('.item_checkbox');
        const deleteButton = document.getElementById('delete_selected_btn');
        const categorySelect = document.getElementById('category');

        // Initialize and bind bulk delete logic
        if (selectAllCheckbox && itemCheckboxes.length > 0) {
            function updateDeleteButtonState() {
                deleteButton.disabled = document.querySelectorAll('.item_checkbox:checked').length === 0;
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

        // Initialize and bind dynamic role/category logic
        if (categorySelect) {
            categorySelect.addEventListener('change', updateRoleOptions);

            // Initial call to populate roles on page load (important for 'edit' mode)
            updateRoleOptions();
        }
    });

</script>

</body>
</html>

<?php 
if (isset($conn)) $conn->close();
?>