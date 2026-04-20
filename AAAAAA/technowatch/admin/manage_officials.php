<?php 
// technowatch/admin/manage_officials.php - Club Officials CRUD Management View

session_start();
// Check for user login/authentication here (Omitted for brevity, but essential)

// Standalone DB connection
// ASSUMES 'includes/db_connect.php' ESTABLISHES $conn OBJECT
include 'includes/db_connect.php'; 

// Helper function for call_user_func_array with bind_param (Required for bulk delete)
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}

// --- Configuration & Initialization ---
$page_title = 'Manage Club Officials & Advisers';
$action = $_GET['action'] ?? 'list';
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$form_title = ($action == 'edit' ? 'Edit Official' : 'Add New Official');
$table_name = 'officials'; // New table name for officials

// Form pre-fill defaults
$official_data = [
    'name' => '',
    'role' => '',
    'full_title' => '',
    'motto' => '',
    'bio_content' => '',
    'email' => '',
    'linkedin' => '',
    'twitter' => '',
    'github' => '', // Added GitHub field based on officials.php frontend
    'image_path' => '',
    'category' => '',
    'sort_order' => 0,
];

// Define the allowed categories (matching officials.php structure)
$categories = ['HEAD', 'FACULTY', 'SECTION MAYORS']; 
// Define specific roles/titles for this page
$roles = ['DEPARTMENT HEAD', 'FACULTY MEMBER', 'S09 MAYOR', 'T09 MAYOR', 'F09 MAYOR']; 

// --- Handle DELETE via GET (Single Delete) ---
if ($action == 'delete' && $item_id > 0) {
    // 1. Fetch item to get image path for physical file deletion
    $image_to_delete = '';
    $stmt_select = $conn->prepare("SELECT image_path FROM $table_name WHERE id = ?");
    $stmt_select->bind_param("i", $item_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($row = $result_select->fetch_assoc()) {
        $image_to_delete = $row['image_path'];
    }
    $stmt_select->close();

    // 2. Delete the database record
    $stmt = $conn->prepare("DELETE FROM $table_name WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Official (ID: $item_id) deleted successfully.";
            $_SESSION['msg_type'] = "success";

            // 3. Delete physical image file (assumes image path is relative to site root)
            if (!empty($image_to_delete)) {
                // IMPORTANT: Adjust path if 'assets/officials/' is not where officers images are stored
                $file_path = '../' . $image_to_delete; 
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
        } else {
            $_SESSION['message'] = "Error deleting official: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error preparing delete statement.";
        $_SESSION['msg_type'] = "danger";
    }
    header('Location: manage_officials.php');
    exit;
}

// --- Handle POST actions (Bulk Delete, Add, Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    
    // --- 1. Bulk deletion ---
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete_selected' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        
        if (!empty($item_ids_to_delete)) {
            // Bulk deletion of records
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));
            $stmt = $conn->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)");
            
            if ($stmt) {
                $bind_params = array_merge([$types], $item_ids_to_delete);
                if (call_user_func_array([$stmt, 'bind_param'], array_by_ref($bind_params))) {
                    if ($stmt->execute()) {
                        $_SESSION['message'] = count($item_ids_to_delete) . " official(s) deleted successfully. (Note: Associated image files must be deleted manually if needed.)";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error deleting officials: " . $stmt->error;
                        $_SESSION['msg_type'] = "danger";
                    }
                }
                $stmt->close();
            }
        }
        header('Location: manage_officials.php');
        exit;
    }

    // --- 2. Single Item Add/Update ---
    if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['add_official', 'update_official'])) {
        $is_update = ($_POST['action_type'] == 'update_official');
        $item_id = $is_update ? (int)$_POST['item_id'] : 0;

        // Collect and sanitize data
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $full_title = trim($_POST['full_title'] ?? '');
        $motto = trim($_POST['motto'] ?? '');
        $bio_content = trim($_POST['bio_content'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');
        $twitter = trim($_POST['twitter'] ?? '');
        $github = trim($_POST['github'] ?? ''); // New field
        $category = trim($_POST['category'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';
        $new_image_path = $current_image;
        
        // Validation check (Name, Role, Category, Image)
        if (empty($name) || empty($role) || empty($category)) {
            $_SESSION['message'] = "Name, Role, and Category are required fields.";
            $_SESSION['msg_type'] = "danger";
            header('Location: manage_officials.php?action=' . ($is_update ? 'edit&id=' . $item_id : 'add'));
            exit;
        }

        // Handle Image Upload
        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
            // **NOTE:** We use 'assets/officials/' as the upload path for consistency with the frontend.
            $upload_dir = '../assets/officials/'; 
            
            // Ensure directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $filename = basename($_FILES['image_path']['name']);
            // Sanitized unique filename
            $unique_name = 'official_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $filename);
            $destination = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['image_path']['tmp_name'], $destination)) {
                $new_image_path = 'assets/officials/' . $unique_name; // Path stored in DB relative to site root
                
                // Delete old image if updating
                if ($is_update && !empty($current_image) && $current_image !== $new_image_path) {
                    $old_file = '../' . $current_image;
                    if (file_exists($old_file)) {
                        @unlink($old_file);
                    }
                }
            } else {
                $_SESSION['message'] = "Error uploading image.";
                $_SESSION['msg_type'] = "danger";
                header('Location: manage_officials.php?action=' . ($is_update ? 'edit&id=' . $item_id : 'add'));
                exit;
            }
        }

        if ($is_update) {
            // UPDATE Query - Added 'github' field
            $sql = "UPDATE $table_name SET name=?, role=?, full_title=?, motto=?, bio_content=?, email=?, linkedin=?, twitter=?, github=?, image_path=?, category=?, sort_order=? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Binding: ssssssssssii (11 strings, 2 integers)
                $stmt->bind_param("ssssssssss sii", 
                    $name, $role, $full_title, $motto, $bio_content, $email, $linkedin, $twitter, $github, 
                    $new_image_path, $category, $sort_order, $item_id);


                
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Official updated successfully!";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error updating official: " . $stmt->error;
                        $_SESSION['msg_type'] = "danger";
                    }
                $stmt->close();
            }
        } else {
            // INSERT Query - Added 'github' field
            $sql = "INSERT INTO $table_name (name, role, full_title, motto, bio_content, email, linkedin, twitter, github, image_path, category, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Binding: ssssssssssi (11 strings, 1 integer)
                $stmt->bind_param("sssssssssssi", 
                    $name, $role, $full_title, $motto, $bio_content, $email, $linkedin, $twitter, $github, 
                    $new_image_path, $category, $sort_order);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "New official added successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding official: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }

        header('Location: manage_officials.php');
        exit;
    }
}

// --- Edit mode (Retrieve data for form) ---
if ($action == 'edit' && $item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM $table_name WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $fetched_data = $result->fetch_assoc();
        // Merge fetched data into defaults
        $official_data = array_merge($official_data, $fetched_data); 
    } else {
        $_SESSION['message'] = "Official not found";
        $_SESSION['msg_type'] = "danger";
        header('Location: manage_officials.php');
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
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/officers.css"> 

<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-sidebar">
        <?php 
        $current_file = basename($_SERVER['PHP_SELF']);
        $is_active = ($current_file == 'manage_officials.php'); // Check for this new file
        include 'includes/admin_sidebar.php'; 
        ?>
    </div>

    <div id="page-content-wrapper">
        <div class="container-fluid pt-4">
            <h1 class="mb-4">👑 <?php echo $page_title; ?></h1>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <a href="manage_officials.php?action=add" class="btn btn-primary mb-4"><i class="fas fa-user-plus"></i> Add New Official/Adviser</a>

                <?php 
                // Adjust query to select all necessary fields and order by sort_order
                $query = "SELECT id, name, role, category, sort_order, email, image_path FROM $table_name ORDER BY category = 'HEAD' DESC, sort_order ASC, name ASC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected officials? (This action is irreversible)')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th><input type="checkbox" id="select_all_items" title="Select All"></th>
                                        <th>ID</th>
                                        <th>Sort</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_items[]" value="<?php echo $row['id']; ?>" class="item_checkbox"></td>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['sort_order']; ?></td>
                                        <td>
                                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                                class="officer-img-thumb"
                                                onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'"
                                                style="width:50px; height:50px; object-fit: cover; border-radius: 50%;">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                        <td>
                                            <a href="manage_officials.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_officials.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this official?')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-danger mt-3" id="delete_selected_btn" disabled><i class="fas fa-trash-alt"></i> Delete Selected Officials</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">No club officials found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2><?php echo $form_title; ?></h2>
                <hr>
                <form action="manage_officials.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_official' : 'add_official'; ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($official_data['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="" disabled <?php echo empty($official_data['role']) ? 'selected' : ''; ?>>Select Role</option>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?php echo htmlspecialchars($r); ?>" 
                                                <?php echo (isset($official_data['role']) && $official_data['role'] == $r) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Category (Determines Grouping on Officials Page)</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" disabled <?php echo empty($official_data['category']) ? 'selected' : ''; ?>>Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                                                <?php echo (isset($official_data['category']) && $official_data['category'] == $cat) ? 'selected' : ''; ?>>
                                            <?php echo ucwords(str_replace('_', ' ', $cat)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_title" class="form-label">Full Title/Description (e.g., Faculty Adviser or Section Name)</label>
                                <input type="text" class="form-control" id="full_title" name="full_title" value="<?php echo htmlspecialchars($official_data['full_title'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($official_data['sort_order'] ?? '0'); ?>" required>
                                <small class="form-text text-muted">Lower number means higher on the chart (e.g., Head=0, Managers=10, Developers=20, Mayors=30)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="motto" class="form-label">Motto/Quote (For Head Card only)</label>
                                <input type="text" class="form-control" id="motto" name="motto" value="<?php echo htmlspecialchars($official_data['motto'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio_content" class="form-label">Biography/Description (For Modal)</label>
                                <textarea class="form-control" id="bio_content" name="bio_content" rows="4"><?php echo htmlspecialchars($official_data['bio_content'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <h3>Contact & Social Links</h3>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($official_data['email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="linkedin" class="form-label">LinkedIn URL (Optional)</label>
                                <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($official_data['linkedin'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="twitter" class="form-label">Twitter/X URL (Optional)</label>
                                <input type="url" class="form-control" id="twitter" name="twitter" value="<?php echo htmlspecialchars($official_data['twitter'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="github" class="form-label">GitHub URL (Optional)</label>
                                <input type="url" class="form-control" id="github" name="github" value="<?php echo htmlspecialchars($official_data['github'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <h3>Image Upload</h3>
                    <hr>
                    <div class="mb-3">
                        <label for="image_path" class="form-label">Official Profile Image Upload</label>
                        <input type="file" class="form-control" id="image_path" name="image_path" accept="image/*" <?php echo ($action == 'add' && empty($official_data['image_path'])) ? 'required' : ''; ?>>
                        <?php if (!empty($official_data['image_path'])): ?>
                            <div class="mt-2">
                                Current Image: <br>
                                <img src="../<?php echo htmlspecialchars($official_data['image_path']); ?>" alt="Current Official Image" style="width:100px; height: auto; border: 1px solid #ccc; border-radius: 50%;">
                            </div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($official_data['image_path']); ?>">
                        <?php endif; ?>
                        <small class="form-text text-muted">Files will be saved in `../assets/officials/`.</small>
                    </div>

                    <button type="submit" class="btn btn-success me-2"><i class="fas fa-save"></i> Save Official</button>
                    <a href="manage_officials.php" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select_all_items');
    const itemCheckboxes = document.querySelectorAll('.item_checkbox');
    const deleteButton = document.getElementById('delete_selected_btn');

    if (selectAllCheckbox && itemCheckboxes.length > 0) {
        function updateDeleteButtonState() {
            deleteButton.disabled = document.querySelectorAll('.item_checkbox:checked').length === 0;
        }

        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateDeleteButtonState();
        });

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                // Uncheck select all if any single item is unchecked
                if (!cb.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // Check select all if all items are checked
                    selectAllCheckbox.checked = Array.from(itemCheckboxes).every(c => c.checked);
                }
                updateDeleteButtonState();
            });
        });

        updateDeleteButtonState();
    }
});
</script>

</body>
</html>

<?php 
if (isset($conn)) $conn->close();
?>