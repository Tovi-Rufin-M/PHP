<?php 
// technowatch/admin/manage_officers.php - Club Officers CRUD Management View

session_start();
// Check for user login/authentication here (Omitted for brevity, but essential)
include 'includes/db_connect.php'; 

// Helper function for call_user_func_array with bind_param
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}

// --- Configuration & Initialization ---
$page_title = 'Manage Club Officers';
$action = $_GET['action'] ?? 'list';
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$form_title = ($action == 'edit' ? 'Edit Officer' : 'Add New Officer');

// Form pre-fill defaults
$officer_data = [
    'name' => '',
    'role' => '',
    'full_title' => '',
    'motto' => '',
    'bio_content' => '',
    'email' => '',
    'linkedin' => '',
    'twitter' => '',
    'image_path' => '',
    'category' => '',
    'sort_order' => 0,
];

$categories = ['EXECUTIVE OFFICERS', 'CLUB REPRESENTATIVES', 'CLUB CREATIVES']; 
$roles = ['CLUB PRESIDENT', 'VICE PRESIDENT', 'VICE PRESIDENT ASSISTANT', 'SECRETARY', 'TREASURER', 'AUDITOR', 'PIO', 'MEMBER'];

// --- DELETE Single ---
if ($action == 'delete' && $item_id > 0) {
    $image_to_delete = '';
    $stmt_select = $conn->prepare("SELECT image_path FROM officers WHERE id = ?");
    $stmt_select->bind_param("i", $item_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($row = $result_select->fetch_assoc()) $image_to_delete = $row['image_path'];
    $stmt_select->close();

    $stmt = $conn->prepare("DELETE FROM officers WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Officer (ID: $item_id) deleted successfully.";
            $_SESSION['msg_type'] = "success";

            if (!empty($image_to_delete)) {
                $file_path = '../' . $image_to_delete;
                if (file_exists($file_path)) @unlink($file_path);
            }
        } else {
            $_SESSION['message'] = "Error deleting officer: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error preparing delete statement.";
        $_SESSION['msg_type'] = "danger";
    }
    header('Location: manage_officers.php');
    exit;
}

// --- POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    // --- Bulk Delete ---
    if (isset($_POST['bulk_action'], $_POST['selected_items']) && $_POST['bulk_action'] === 'delete_selected' && is_array($_POST['selected_items'])) {
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        if (!empty($item_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));
            $stmt = $conn->prepare("DELETE FROM officers WHERE id IN ($placeholders)");
            if ($stmt) {
                $bind_params = array_merge([$types], $item_ids_to_delete);
                call_user_func_array([$stmt, 'bind_param'], array_by_ref($bind_params));
                if ($stmt->execute()) {
                    $_SESSION['message'] = count($item_ids_to_delete) . " officer(s) deleted successfully. (Images must be removed manually.)";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error deleting officers: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }
        header('Location: manage_officers.php');
        exit;
    }

    // --- Add/Update Single Officer ---
    if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['add_officer', 'update_officer'])) {
        $is_update = ($_POST['action_type'] === 'update_officer');
        $item_id = $is_update ? (int)($_POST['item_id'] ?? 0) : 0;

        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $full_title = trim($_POST['full_title'] ?? '');
        $motto = trim($_POST['motto'] ?? '');
        $bio_content = trim($_POST['bio_content'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');
        $twitter = trim($_POST['twitter'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';
        $new_image_path = $current_image;

        if (empty($name) || empty($role) || empty($full_title) || empty($category)) {
            $_SESSION['message'] = "Name, Role, Title, and Category are required.";
            $_SESSION['msg_type'] = "danger";
            header('Location: manage_officers.php?action=' . ($is_update ? 'edit&id=' . $item_id : 'add'));
            exit;
        }

        // Image upload
        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
            // NOTE: The upload path is different from manage_officials.php, retaining 'assets/imgs/uploads/'
            $upload_dir = '../assets/imgs/uploads/'; 
            
            // Ensure directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = basename($_FILES['image_path']['name']);
            $unique_name = 'officer_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $filename);
            $destination = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['image_path']['tmp_name'], $destination)) {
                $new_image_path = 'assets/imgs/uploads/' . $unique_name; // Path stored in DB relative to site root
                if ($is_update && !empty($current_image) && $current_image !== $new_image_path) {
                    $old_file = '../' . $current_image;
                    if (file_exists($old_file)) @unlink($old_file);
                }
            } else {
                $_SESSION['message'] = "Error uploading image.";
                $_SESSION['msg_type'] = "danger";
                header('Location: manage_officers.php?action=' . ($is_update ? 'edit&id=' . $item_id : 'add'));
                exit;
            }
        }

        if ($is_update) {
            $sql = "UPDATE officers SET name=?, role=?, full_title=?, motto=?, bio_content=?, email=?, linkedin=?, twitter=?, image_path=?, category=?, sort_order=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // ssssssssssii (10 strings, 2 integers)
                $stmt->bind_param("ssssssssssii", $name, $role, $full_title, $motto, $bio_content, $email, $linkedin, $twitter, $new_image_path, $category, $sort_order, $item_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Officer updated successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating officer: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        } else {
            $sql = "INSERT INTO officers (name, role, full_title, motto, bio_content, email, linkedin, twitter, image_path, category, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // ssssssssssi (10 strings, 1 integer)
                $stmt->bind_param("ssssssssssi", $name, $role, $full_title, $motto, $bio_content, $email, $linkedin, $twitter, $new_image_path, $category, $sort_order);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "New officer added successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding officer: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }

        header('Location: manage_officers.php');
        exit;
    }
}

// --- Edit Mode: Prefill Form ---
if ($action === 'edit' && $item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM officers WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $officer_data = array_merge($officer_data, $result->fetch_assoc());
    } else {
        $_SESSION['message'] = "Officer not found";
        $_SESSION['msg_type'] = "danger";
        header('Location: manage_officers.php');
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

<script src="https://cdn.tailwindcss.com"></script>

<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>

<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'dark-bg': '#0f172a',    // Deep Navy (for body)
                    'dark-card': '#1e293b',  // Dark Slate (for forms/tables)
                    'dark-input': '#334155', // Medium Slate (for inputs)
                    'dark-border': '#475569',// Grayish Blue (for borders/lines)
                    'primary-indigo': '#6366f1', // Indigo 500 (Primary button)
                    'text-light': '#e2e8f0', // Off-White
                },
                spacing: {
                    '250': '250px', // Custom width for sidebar offset
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
        $is_active = ($current_file == 'manage_officers.php'); 
        include 'includes/admin_sidebar.php'; 
        ?>
    </div>

    <div id="page-content-wrapper" class="ml-250 p-8 w-[calc(100%-250px)] min-h-screen box-border">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold mb-6 text-white"><?php echo $page_title; ?></h1>

            <?php if (isset($_SESSION['message'])): ?>
                <?php 
                    $alert_color = $_SESSION['msg_type'] == 'success' ? 'bg-green-500' : ($_SESSION['msg_type'] == 'danger' ? 'bg-red-500' : 'bg-blue-500');
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
                <a href="manage_officers.php?action=add" class="inline-flex items-center px-4 py-2 bg-primary-indigo hover:bg-indigo-600 text-white font-semibold rounded-lg shadow-md mb-6 transition duration-200">
                    <i class="fas fa-user-plus mr-2"></i> Add New Officer
                </a>

                <?php 
                $query = "SELECT id, name, role, category, sort_order, email, image_path FROM officers ORDER BY category ASC, sort_order ASC, name ASC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected officers? (This action is irreversible)')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        <div class="overflow-x-auto shadow-xl rounded-lg">
                            <table class="w-full text-left text-text-light rounded-lg">
                                <thead class="text-xs uppercase bg-dark-bg text-white border-b-2 border-dark-border">
                                    <tr>
                                        <th scope="col" class="p-4"><input type="checkbox" id="select_all_items" title="Select All" class="form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></th>
                                        <th scope="col" class="py-3 px-6">ID</th>
                                        <th scope="col" class="py-3 px-6">Sort</th>
                                        <th scope="col" class="py-3 px-6">Image</th>
                                        <th scope="col" class="py-3 px-6">Name</th>
                                        <th scope="col" class="py-3 px-6">Role</th>
                                        <th scope="col" class="py-3 px-6">Category</th>
                                        <th scope="col" class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4"><input type="checkbox" name="selected_items[]" value="<?php echo $row['id']; ?>" class="item_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></td>
                                        <td class="py-4 px-6"><?php echo $row['id']; ?></td>
                                        <td class="py-4 px-6"><?php echo $row['sort_order']; ?></td>
                                        <td class="py-4 px-6">
                                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                                class="officer-img-thumb w-12 h-12 object-cover rounded-full border border-dark-border"
                                                onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'">
                                        </td>
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="py-4 px-6"><?php echo htmlspecialchars($row['role']); ?></td>
                                        <td class="py-4 px-6"><?php echo htmlspecialchars($row['category']); ?></td>
                                        <td class="py-4 px-6 flex space-x-2">
                                            <a href="manage_officers.php?action=edit&id=<?php echo $row['id']; ?>" class="p-2 text-dark-bg bg-yellow-400 hover:bg-yellow-500 rounded-lg" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_officers.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this officer?')" class="p-2 text-white bg-red-600 hover:bg-red-700 rounded-lg" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="mt-6 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md disabled:opacity-50 transition duration-200" id="delete_selected_btn" disabled>
                            <i class="fas fa-trash-alt mr-2"></i> Delete Selected Officers
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-4 bg-blue-500 text-white rounded-lg">No club officers found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?></h2>
                <p class="text-gray-400 mb-6"><a href="manage_officers.php?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to Officer List</a></p>
                <hr class="border-dark-border mb-6">
                
                <form action="manage_officers.php" method="POST" enctype="multipart/form-data" class="bg-dark-card p-8 rounded-xl shadow-2xl max-w-4xl">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_officer' : 'add_officer'; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
                        <div>
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-text-light mb-1">Full Name</label>
                                <input type="text" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="name" name="name" value="<?php echo htmlspecialchars($officer_data['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="role" class="block text-sm font-medium text-text-light mb-1">Role</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="role" name="role" required>
                                    <option value="" disabled <?php echo empty($officer_data['role']) ? 'selected' : ''; ?>>Select Role</option>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?php echo htmlspecialchars($r); ?>" 
                                                    <?php echo (isset($officer_data['role']) && $officer_data['role'] == $r) ? 'selected' : ''; ?>>
                                                <?php echo ucwords(strtolower($r)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="category" class="block text-sm font-medium text-text-light mb-1">Category (Determines Grouping)</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="category" name="category" required>
                                    <option value="" disabled <?php echo empty($officer_data['category']) ? 'selected' : ''; ?>>Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                                                    <?php echo (isset($officer_data['category']) && $officer_data['category'] == $cat) ? 'selected' : ''; ?>>
                                                <?php echo ucwords(strtolower(str_replace('_', ' ', $cat))); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="full_title" class="block text-sm font-medium text-text-light mb-1">Full Title/Course/Section</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="full_title" name="full_title" required>
                                    <option value="" disabled <?php echo empty($officer_data['full_title']) ? 'selected' : ''; ?>>Select Section</option>
                                    <?php 
                                    // Define the fixed Course and Sections
                                    $course_prefix = 'COMPUTER ENGINEERING TECHNOLOGY ';
                                    $sections = ['S09-A', 'T09-A', 'F09-A'];
                                    
                                    foreach ($sections as $section): 
                                        $full_value = $course_prefix . $section;
                                    ?>
                                        <option value="<?php echo htmlspecialchars($full_value); ?>" 
                                                    <?php echo (isset($officer_data['full_title']) && $officer_data['full_title'] == $full_value) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($full_value); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="mb-4">
                                <label for="sort_order" class="block text-sm font-medium text-text-light mb-1">Sort Order</label>
                                <input type="number" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($officer_data['sort_order'] ?? '0'); ?>" required>
                                <small class="text-sm text-gray-400">Lower number means higher on the chart (e.g., President=0).</small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="motto" class="block text-sm font-medium text-text-light mb-1">Motto (Optional)</label>
                                <input type="text" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="motto" name="motto" value="<?php echo htmlspecialchars($officer_data['motto'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="bio_content" class="block text-sm font-medium text-text-light mb-1">Biography/Description</label>
                                <textarea class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="bio_content" name="bio_content" rows="4"><?php echo htmlspecialchars($officer_data['bio_content'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-semibold text-white mt-6 mb-3">Contact & Social Links</h3>
                    <hr class="border-dark-border mb-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                        <div class="mb-4 md:mb-0">
                            <label for="email" class="block text-sm font-medium text-text-light mb-1">Email</label>
                            <input type="email" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="email" name="email" value="<?php echo htmlspecialchars($officer_data['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-4 md:mb-0">
                            <label for="linkedin" class="block text-sm font-medium text-text-light mb-1">LinkedIn URL (Optional)</label>
                            <input type="url" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($officer_data['linkedin'] ?? ''); ?>">
                        </div>
                        <div class="mb-4 md:mb-0">
                            <label for="twitter" class="block text-sm font-medium text-text-light mb-1">Twitter/X URL (Optional)</label>
                            <input type="url" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="twitter" name="twitter" value="<?php echo htmlspecialchars($officer_data['twitter'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-semibold text-white mt-6 mb-3">Image Upload</h3>
                    <hr class="border-dark-border mb-6">
                    
                    <div class="mb-6">
                        <label for="image_path" class="block text-sm font-medium text-text-light mb-1">Officer Profile Image Upload</label>
                        <input type="file" class="block w-full text-sm text-text-light file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 cursor-pointer" id="image_path" name="image_path" accept="image/*" <?php echo ($action == 'add' && empty($officer_data['image_path'])) ? 'required' : ''; ?>>
                        
                        <?php if (!empty($officer_data['image_path'])): ?>
                            <div class="mt-4">
                                <span class="text-sm font-medium text-text-light">Current Image:</span> <br>
                                <img src="../<?php echo htmlspecialchars($officer_data['image_path']); ?>" alt="Current Officer Image" class="w-24 h-24 object-cover rounded-full mt-2 border-4 border-primary-indigo">
                            </div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($officer_data['image_path']); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="pt-6 border-t border-dark-border flex space-x-3">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                            <i class="fas fa-save mr-2"></i> Save Officer
                        </button>
                        <a href="manage_officers.php" class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-200">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select_all_items');
    const itemCheckboxes = document.querySelectorAll('.item_checkbox');
    const deleteButton = document.getElementById('delete_selected_btn');

    if (selectAllCheckbox && itemCheckboxes.length > 0) {
        function updateDeleteButtonState() {
            deleteButton.disabled = document.querySelectorAll('.item_checkbox:checked').length === 0;
            // Tailwind class for visual disabled state
            deleteButton.classList.toggle('opacity-50', deleteButton.disabled);
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