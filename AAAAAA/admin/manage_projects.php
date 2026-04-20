<?php 
// technowatch/admin/manage_projects.php - Project CRUD Management View

session_start();
// Check for user login/authentication here (Omitted for brevity, but essential)
include 'includes/db_connect.php'; 

// Helper function for call_user_func_array with bind_param (for bulk delete)
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}

// --- Configuration & Initialization ---
$page_title = 'Manage Projects';
$action = $_GET['action'] ?? 'list';
// Ensure 'project_id' is used consistently
$item_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0; 
$form_title = ($action == 'edit' ? 'Edit Project' : 'Add New Project');

// Form pre-fill defaults
$project_data = [
    'project_id' => 0, // Added primary key for consistency
    'title' => '',
    'short_description' => '',
    'full_description' => '',
    'tag' => '', 
    'categories' => '', 
    'image_path' => '',
    'features' => '', 
    'case_study_link' => '',
    'sort_order' => 0, 
];

// Configuration for select options
$tags = ['FEATURED', 'CURRENT', 'ARCHIVED'];

// --- RESTORED ORIGINAL CATEGORIES START ---
// The values here match the original format (ALL CAPS, hyphen-separated for slugs)
$categories = [
    'AI-ROBOTICS' => 'Ai Robotics', 
    'IOT-MOBILE' => 'Iot Mobile', 
    'DATA-ANALYTICS' => 'Data Analytics', 
    'WEB-DEVELOPMENT' => 'Web Development', 
    'GAMING' => 'Gaming',
]; 
// --- RESTORED ORIGINAL CATEGORIES END ---

// --- DELETE Single ---
if ($action == 'delete' && $item_id > 0) {
    $image_to_delete = '';
    
    // 1. Select image path before deleting the record
    $stmt_select = $conn->prepare("SELECT image_path FROM projects WHERE project_id = ?");
    $stmt_select->bind_param("i", $item_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($row = $result_select->fetch_assoc()) $image_to_delete = $row['image_path'];
    $stmt_select->close();

    // 2. Delete the record
    $stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Project (ID: $item_id) deleted successfully.";
            $_SESSION['msg_type'] = "success";

            // 3. Delete the image file
            if (!empty($image_to_delete)) {
                $file_path = '../' . $image_to_delete;
                if (file_exists($file_path)) @unlink($file_path);
            }
        } else {
            $_SESSION['message'] = "Error deleting project: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error preparing delete statement.";
        $_SESSION['msg_type'] = "danger";
    }
    header('Location: manage_projects.php');
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
            
            $stmt = $conn->prepare("DELETE FROM projects WHERE project_id IN ($placeholders)");
            if ($stmt) {
                $bind_params = array_merge([$types], $item_ids_to_delete);
                call_user_func_array([$stmt, 'bind_param'], array_by_ref($bind_params));
                if ($stmt->execute()) {
                    $_SESSION['message'] = count($item_ids_to_delete) . " project(s) deleted successfully.";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error deleting projects: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }
        header('Location: manage_projects.php');
        exit;
    }

    // --- Add/Update Single Project ---
    if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['add_project', 'update_project'])) {
        $is_update = ($_POST['action_type'] === 'update_project');
        $item_id = $is_update ? (int)($_POST['item_id'] ?? 0) : 0;

        // Collect and sanitize data
        $title = trim($_POST['title'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $full_description = trim($_POST['full_description'] ?? '');
        $tag = trim($_POST['tag'] ?? '');
        $categories_array = $_POST['categories'] ?? [];
        // Categories are joined by a space to save as a filter string
        $categories_db_string = is_array($categories_array) ? implode(' ', array_map('trim', $categories_array)) : ''; 
        $features = trim($_POST['features'] ?? ''); 
        $case_study_link = trim($_POST['case_study_link'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';
        $new_image_path = $current_image;

        if (empty($title) || empty($short_description) || empty($full_description) || empty($categories_db_string) || empty($tag)) {
            $_SESSION['message'] = "Title, Short Description, Full Description, Tag, and Category are required.";
            $_SESSION['msg_type'] = "danger";
            header('Location: manage_projects.php?action=' . ($is_update ? 'edit&project_id=' . $item_id : 'add'));
            exit;
        }

        // Image upload logic
        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/imgs/uploads/'; 
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = basename($_FILES['image_path']['name']);
            $unique_name = 'project_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $filename);
            $destination = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['image_path']['tmp_name'], $destination)) {
                $new_image_path = 'assets/imgs/uploads/' . $unique_name;
                if ($is_update && !empty($current_image) && $current_image !== $new_image_path) {
                    $old_file = '../' . $current_image;
                    if (file_exists($old_file)) @unlink($old_file);
                }
            } else {
                $_SESSION['message'] = "Error uploading image.";
                $_SESSION['msg_type'] = "danger";
                header('Location: manage_projects.php?action=' . ($is_update ? 'edit&project_id=' . $item_id : 'add'));
                exit;
            }
        }

        if ($is_update) {
            $sql = "UPDATE projects SET title=?, short_description=?, full_description=?, tag=?, categories=?, image_path=?, features=?, case_study_link=?, sort_order=? WHERE project_id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // ssssssssii (9 strings, 1 integer for sort_order, 1 integer for project_id)
                $stmt->bind_param("ssssssssii", $title, $short_description, $full_description, $tag, $categories_db_string, $new_image_path, $features, $case_study_link, $sort_order, $item_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Project updated successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating project: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        } else {
            $sql = "INSERT INTO projects (title, short_description, full_description, tag, categories, image_path, features, case_study_link, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // sssssssssi (9 strings, 1 integer)
                $stmt->bind_param("ssssssssi", $title, $short_description, $full_description, $tag, $categories_db_string, $new_image_path, $features, $case_study_link, $sort_order);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "New project added successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding project: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }

        header('Location: manage_projects.php');
        exit;
    }
}

// --- Edit Mode: Prefill Form ---
if ($action === 'edit' && $item_id > 0) {
    // Correct SELECT to use project_id
    $stmt = $conn->prepare("SELECT * FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $project_data = array_merge($project_data, $result->fetch_assoc());
    } else {
        $_SESSION['message'] = "Project not found";
        $_SESSION['msg_type'] = "danger";
        header('Location: manage_projects.php');
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
        $is_active = ($current_file == 'manage_projects.php'); 
        // NOTE: Ensure 'includes/admin_sidebar.php' exists and works.
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
                <a href="manage_projects.php?action=add" class="inline-flex items-center px-4 py-2 bg-primary-indigo hover:bg-indigo-600 text-white font-semibold rounded-lg shadow-md mb-6 transition duration-200">
                    <i class="fas fa-plus-circle mr-2"></i> Add New Project
                </a>

                <?php 
                // Listing Query - SELECTs the necessary columns including project_id and the added sort_order
                $query = "SELECT project_id, title, tag, categories, sort_order, image_path, short_description 
                              FROM projects 
                              ORDER BY sort_order ASC, project_id DESC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected projects? (This action is irreversible)')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        <div class="overflow-x-auto shadow-xl rounded-lg">
                            <table class="w-full text-left text-text-light rounded-lg">
                                <thead class="text-xs uppercase bg-dark-bg text-white border-b-2 border-dark-border">
                                    <tr>
                                        <th scope="col" class="p-4"><input type="checkbox" id="select_all_items" title="Select All" class="form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></th>
                                        <th scope="col" class="py-3 px-6">ID</th>
                                        <th scope="col" class="py-3 px-6">Sort</th>
                                        <th scope="col" class="py-3 px-6">Image</th>
                                        <th scope="col" class="py-3 px-6">Title</th>
                                        <th scope="col" class="py-3 px-6">Tag</th>
                                        <th scope="col" class="py-3 px-6">Categories</th>
                                        <th scope="col" class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4"><input type="checkbox" name="selected_items[]" value="<?php echo $row['project_id']; ?>" class="item_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></td>
                                        <td class="py-4 px-6"><?php echo $row['project_id']; ?></td>
                                        <td class="py-4 px-6"><?php echo $row['sort_order']; ?></td>
                                        <td class="py-4 px-6">
                                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['title']); ?>" 
                                                class="project-img-thumb w-12 h-12 object-cover rounded-md border border-dark-border"
                                                onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'">
                                        </td>
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td class="py-4 px-6"><span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo strtolower($row['tag']) == 'featured' ? 'bg-primary-indigo text-white' : 'bg-gray-500 text-text-light'; ?>"><?php echo htmlspecialchars($row['tag']); ?></span></td>
                                        <td class="py-4 px-6 text-sm text-gray-400"><?php echo htmlspecialchars($row['categories']); ?></td>
                                        <td class="py-4 px-6 flex space-x-2">
                                            <a href="manage_projects.php?action=edit&project_id=<?php echo $row['project_id']; ?>" class="p-2 text-dark-bg bg-yellow-400 hover:bg-yellow-500 rounded-lg" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_projects.php?action=delete&project_id=<?php echo $row['project_id']; ?>" onclick="return confirm('Are you sure you want to delete this project?')" class="p-2 text-white bg-red-600 hover:bg-red-700 rounded-lg" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="mt-6 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md disabled:opacity-50 transition duration-200" id="delete_selected_btn" disabled>
                            <i class="fas fa-trash-alt mr-2"></i> Delete Selected Projects
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-4 bg-blue-500 text-white rounded-lg">No projects found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?></h2>
                <p class="text-gray-400 mb-6"><a href="manage_projects.php?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to Project List</a></p>
                <hr class="border-dark-border mb-6">
                
                <form action="manage_projects.php" method="POST" enctype="multipart/form-data" class="bg-dark-card p-8 rounded-xl shadow-2xl max-w-6xl">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_project' : 'add_project'; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
                        <div>
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-text-light mb-1">Project Title</label>
                                <input type="text" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="title" name="title" value="<?php echo htmlspecialchars($project_data['title'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="tag" class="block text-sm font-medium text-text-light mb-1">Status Tag</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="tag" name="tag" required>
                                    <option value="" disabled <?php echo empty($project_data['tag']) ? 'selected' : ''; ?>>Select Tag</option>
                                    <?php foreach ($tags as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t); ?>" 
                                                    <?php echo (isset($project_data['tag']) && $project_data['tag'] == $t) ? 'selected' : ''; ?>>
                                                    <?php echo ucwords(strtolower($t)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="categories" class="block text-sm font-medium text-text-light mb-1">Categories (Hold CTRL/CMD to select multiple)</label>
                                <select multiple class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light h-32" id="categories" name="categories[]" required>
                                    <?php 
                                    // Categories are space-separated in the DB, so we explode them for comparison
                                    $current_categories = explode(' ', $project_data['categories'] ?? '');
                                    
                                    // Loop through the restored original categories array
                                    foreach ($categories as $slug => $display_name): ?>
                                        <option value="<?php echo htmlspecialchars($slug); ?>" 
                                                    <?php echo in_array($slug, $current_categories) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="sort_order" class="block text-sm font-medium text-text-light mb-1">Sort Order</label>
                                <input type="number" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($project_data['sort_order'] ?? '0'); ?>" required>
                                <small class="text-sm text-gray-400">Lower number means higher priority on the public page.</small>
                            </div>
                        </div>

                        <div>
                            <div class="mb-4">
                                <label for="short_description" class="block text-sm font-medium text-text-light mb-1">Short Description (For Card)</label>
                                <textarea class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="short_description" name="short_description" rows="3" required><?php echo htmlspecialchars($project_data['short_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="full_description" class="block text-sm font-medium text-text-light mb-1">Full Description (For Modal/Case Study)</label>
                                <textarea class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="full_description" name="full_description" rows="5" required><?php echo htmlspecialchars($project_data['full_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="features" class="block text-sm font-medium text-text-light mb-1">Key Features (Comma-separated)</label>
                                <input type="text" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="features" name="features" value="<?php echo htmlspecialchars($project_data['features'] ?? ''); ?>" placeholder="Feature 1, Feature 2, Feature 3">
                            </div>
                            
                            <div class="mb-4">
                                <label for="case_study_link" class="block text-sm font-medium text-text-light mb-1">Case Study URL (Optional)</label>
                                <input type="url" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="case_study_link" name="case_study_link" value="<?php echo htmlspecialchars($project_data['case_study_link'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-semibold text-white mt-6 mb-3">Image Upload</h3>
                    <hr class="border-dark-border mb-6">
                    
                    <div class="mb-6">
                        <label for="image_path" class="block text-sm font-medium text-text-light mb-1">Featured Image Upload</label>
                        <input type="file" class="block w-full text-sm text-text-light file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 cursor-pointer" id="image_path" name="image_path" accept="image/*" <?php echo ($action == 'add' && empty($project_data['image_path'])) ? 'required' : ''; ?>>
                        
                        <?php if (!empty($project_data['image_path'])): ?>
                            <div class="mt-4">
                                <span class="text-sm font-medium text-text-light">Current Image:</span> <br>
                                <img src="../<?php echo htmlspecialchars($project_data['image_path']); ?>" alt="Current Project Image" class="w-40 h-auto object-cover rounded-md mt-2 border-4 border-primary-indigo">
                            </div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($project_data['image_path']); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="pt-6 border-t border-dark-border flex space-x-3">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                            <i class="fas fa-save mr-2"></i> Save Project
                        </button>
                        <a href="manage_projects.php" class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-200">Cancel</a>
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
});
</script>

</body>
</html>

<?php 
if (isset($conn)) $conn->close();
?>