 <?php 
 // technowatch/admin/manage_projects.php - Projects CRUD Management View

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
 $page_title = 'Manage Projects';
 $action = $_GET['action'] ?? 'list';
 $item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
 $form_title = ($action == 'edit' ? 'Edit Project' : 'Add New Project');
 $table_name = 'projects';

 // Form pre-fill defaults
 $project_data = [
     'title' => '',
     'tag' => '',
     'category' => '',
     'image_path' => '',
     'card_description' => '',
     'modal_description' => '',
     'features' => '',
     'sort_order' => 0,
     'case_study_url' => '',
 ];

 // Define dropdown options
 $tags = ['AI & Robotics', 'IoT & Mobile', 'IoT & Hardware', 'Data & Analytics']; 
 $categories = ['current', 'ai-robotics', 'iot-mobile', 'data-analytics'];

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
             $_SESSION['message'] = "Project (ID: $item_id) deleted successfully.";
             $_SESSION['msg_type'] = "success";

             // 3. Delete physical image file (assumes image path is relative to site root)
             if (!empty($image_to_delete)) {
                 $file_path = '../' . $image_to_delete;
                 if (file_exists($file_path)) {
                     @unlink($file_path);
                 }
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

 // --- Handle POST actions (Bulk Delete, Add, Update) ---
 if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
     
     // --- 1. Bulk deletion ---
     if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete_selected' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
         $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
         
         if (!empty($item_ids_to_delete)) {
             $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
             $types = str_repeat('i', count($item_ids_to_delete));
             $stmt = $conn->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)");
             
             if ($stmt) {
                 $bind_params = array_merge([$types], $item_ids_to_delete);
                 if (call_user_func_array([$stmt, 'bind_param'], array_by_ref($bind_params))) {
                     if ($stmt->execute()) {
                         $_SESSION['message'] = count($item_ids_to_delete) . " project(s) deleted successfully. (Note: Associated image files must be deleted manually if needed.)";
                         $_SESSION['msg_type'] = "success";
                     } else {
                         $_SESSION['message'] = "Error deleting projects: " . $stmt->error;
                         $_SESSION['msg_type'] = "danger";
                     }
                 }
                 $stmt->close();
             }
         }
         header('Location: manage_projects.php');
         exit;
     }

     // --- 2. Single Item Add/Update ---
     if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['add_project', 'update_project'])) {
         $is_update = ($_POST['action_type'] == 'update_project');
         $item_id = $is_update ? (int)($_POST['item_id'] ?? 0) : 0;

         // Collect and sanitize data
         $title = trim($_POST['title'] ?? '');
         $tag = trim($_POST['tag'] ?? '');
         $category = isset($_POST['category']) ? implode(' ', $_POST['category']) : ''; // Handle multi-select
         $card_description = trim($_POST['card_description'] ?? '');
         $modal_description = trim($_POST['modal_description'] ?? '');
         $features = trim($_POST['features'] ?? '');
         $sort_order = (int)($_POST['sort_order'] ?? 0);
         $case_study_url = trim($_POST['case_study_url'] ?? '');
         $current_image = $_POST['current_image'] ?? '';
         $new_image_path = $current_image;
         
         // Validation check (Title, Tag, Category are required)
         if (empty($title) || empty($tag) || empty($category)) {
             $_SESSION['message'] = "Title, Tag, and Category are required fields.";
             $_SESSION['msg_type'] = "danger";
             header('Location: manage_projects.php?action=' . ($is_update ? 'edit&id=' . $item_id : 'add'));
             exit;
         }

         // Handle Image Upload
         if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
             // Use 'assets/imgs/uploads/' for consistency with officers table
             $upload_dir = '../assets/imgs/uploads/';
             
             // Ensure directory exists
             if (!is_dir($upload_dir)) {
                 mkdir($upload_dir, 0777, true);
             }

             $filename = basename($_FILES['image_path']['name']);
             $unique_name = 'project_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $filename);
             $destination = $upload_dir . $unique_name;
             
             if (move_uploaded_file($_FILES['image_path']['tmp_name'], $destination)) {
                 $new_image_path = 'assets/imgs/uploads/' . $unique_name; // Path stored in DB relative to site root
                 
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
                 header('Location: manage_projects.php?action=' . ($is_update ? 'edit&id=' . $item_id : 'add'));
                 exit;
             }
         }

         if ($is_update) {
             $sql = "UPDATE $table_name SET title=?, tag=?, category=?, card_description=?, modal_description=?, features=?, sort_order=?, case_study_url=? WHERE id = ?";
             $stmt = $conn->prepare($sql);
             if ($stmt) {
                 $stmt->bind_param("ssssssisi", 
                     $title, $tag, $category, $card_description, $modal_description, $features, 
                     $sort_order, $case_study_url, $item_id);
                 
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
             $sql = "INSERT INTO $table_name (title, tag, category, card_description, modal_description, features, sort_order, case_study_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
             $stmt = $conn->prepare($sql);
             if ($stmt) {
                 $stmt->bind_param("ssssssis", 
                     $title, $tag, $category, $card_description, $modal_description, $features, 
                     $sort_order, $case_study_url);
                 
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

 // --- Edit mode (Retrieve data for form) ---
 if ($action == 'edit' && $item_id > 0) {
     $stmt = $conn->prepare("SELECT * FROM $table_name WHERE id = ?");
     $stmt->bind_param("i", $item_id);
     $stmt->execute();
     $result = $stmt->get_result();
     
     if ($result->num_rows === 1) {
         $fetched_data = $result->fetch_assoc();
         // Merge fetched data into defaults
         $project_data = array_merge($project_data, $fetched_data); 
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
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css">
 <link rel="stylesheet" href="assets/css/sidebar.css">
 <link rel="stylesheet" href="assets/css/dashboard.css">
 <link rel="stylesheet" href="assets/css/officers.css"> <!-- Reusing existing admin CSS for styling -->

 <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
 </head>
 <body>
     <div class="admin-sidebar">
         <?php 
         $current_file = basename($_SERVER['PHP_SELF']);
         $is_active = ($current_file == 'manage_projects.php'); // Check for this file
         include 'includes/admin_sidebar.php'; 
         ?>
     </div>

     <div id="page-content-wrapper">
         <div class="container-fluid pt-4">
             <h1 class="mb-4">💻 <?php echo $page_title; ?></h1>

             <?php if (isset($_SESSION['message'])): ?>
                 <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                     <?php echo $_SESSION['message']; ?>
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                 </div>
                 <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
             <?php endif; ?>

             <?php if ($action == 'list'): ?>
                 <a href="manage_projects.php?action=add" class="btn btn-primary mb-4"><i class="fas fa-plus-circle"></i> Add New Project</a>

                 <?php 
                 // Adjust query to select key fields and order by sort_order
                 $query = "SELECT id, title, tag, category, sort_order, image_path FROM $table_name ORDER BY sort_order ASC, title ASC";
                 $result = $conn->query($query);

                 if ($result && $result->num_rows > 0): ?>
                     <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected projects? (This action is irreversible)')">
                         <input type="hidden" name="bulk_action" value="delete_selected">
                         <div class="table-responsive">
                             <table class="table table-striped table-hover align-middle">
                                 <thead class="table-dark">
                                     <tr>
                                         <th><input type="checkbox" id="select_all_items" title="Select All"></th>
                                         <th>ID</th>
                                         <th>Sort</th>
                                         <th>Image</th>
                                         <th>Title</th>
                                         <th>Tag</th>
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
                                                 alt="<?php echo htmlspecialchars($row['title']); ?>" 
                                                 class="project-img-thumb"
                                                 onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'"
                                                 style="width:80px; height:50px; object-fit: cover; border: 1px solid #ccc;">
                                         </td>
                                         <td><?php echo htmlspecialchars($row['title']); ?></td>
                                         <td><?php echo htmlspecialchars($row['tag']); ?></td>
                                         <td><?php echo htmlspecialchars($row['category']); ?></td>
                                         <td>
                                             <a href="manage_projects.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                             <a href="manage_projects.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this project?')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></a>
                                         </td>
                                     </tr>
                                     <?php endwhile; ?>
                                 </tbody>
                             </table>
                         </div>
                         <button type="submit" class="btn btn-danger mt-3" id="delete_selected_btn" disabled><i class="fas fa-trash-alt"></i> Delete Selected Projects</button>
                     </form>
                 <?php else: ?>
                     <div class="alert alert-info">No projects found.</div>
                 <?php endif; ?>

             <?php elseif ($action == 'add' || $action == 'edit'): ?>
                 <h2><?php echo $form_title; ?></h2>
                 <hr>
                 <form action="manage_projects.php" method="POST" enctype="multipart/form-data">
                     <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                     <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_project' : 'add_project'; ?>">

                     <div class="row">
                         <div class="col-md-6">
                             <div class="mb-3">
                                 <label for="title" class="form-label">Project Title</label>
                                 <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($project_data['title'] ?? ''); ?>" required>
                             </div>

                             <div class="mb-3">
                                 <label for="tag" class="form-label">Project Tag</label>
                                 <select class="form-select" id="tag" name="tag" required>
                                     <option value="" disabled <?php echo empty($project_data['tag']) ? 'selected' : ''; ?>>Select Tag</option>
                                     <?php foreach ($tags as $t): ?>
                                         <option value="<?php echo htmlspecialchars($t); ?>" 
                                                 <?php echo (isset($project_data['tag']) && $project_data['tag'] == $t) ? 'selected' : ''; ?>>
                                             <?php echo htmlspecialchars($t); ?>
                                         </option>
                                     <?php endforeach; ?>
                                 </select>
                             </div>
                             
                             <div class="mb-3">
                                 <label for="category" class="form-label">Categories (Hold Ctrl/Cmd to select multiple)</label>
                                 <select multiple class="form-select" id="category" name="category[]" required>
                                     <?php foreach ($categories as $cat): ?>
                                         <?php 
                                             $selected = '';
                                             if (isset($project_data['category'])) {
                                                 $cat_array = explode(' ', $project_data['category']);
                                                 $selected = in_array($cat, $cat_array) ? 'selected' : '';
                                             }
                                         ?>
                                         <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected; ?>>
                                             <?php echo ucwords(str_replace('-', ' ', $cat)); ?>
                                         </option>
                                     <?php endforeach; ?>
                                 </select>
                                 <small class="form-text text-muted">Select categories for filtering (e.g., 'current', 'ai-robotics').</small>
                             </div>
                             
                             <div class="mb-3">
                                 <label for="sort_order" class="form-label">Sort Order</label>
                                 <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($project_data['sort_order'] ?? '0'); ?>" required>
                                 <small class="form-text text-muted">Lower number means higher priority on the page.</small>
                             </div>

                             <div class="mb-3">
                                 <label for="image_path" class="form-label">Project Image Upload</label>
                                 <input type="file" class="form-control" id="image_path" name="image_path" accept="image/*" <?php echo ($action == 'add' && empty($project_data['image_path'])) ? 'required' : ''; ?>>
                                 <?php if (!empty($project_data['image_path'])): ?>
                                     <div class="mt-2">
                                         Current Image: <br>
                                         <img src="../<?php echo htmlspecialchars($project_data['image_path']); ?>" alt="Current Project Image" style="width:100px; height: auto; border: 1px solid #ccc;">
                                     </div>
                                     <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($project_data['image_path']); ?>">
                                 <?php endif; ?>
                                 <small class="form-text text-muted">Recommended size: at least 600x400px. Files saved to `assets/imgs/uploads/`.</small>
                             </div>

                         </div>

                         <div class="col-md-6">
                             <div class="mb-3">
                                 <label for="card_description" class="form-label">Card Description (Short)</label>
                                 <textarea class="form-control" id="card_description" name="card_description" rows="3" required><?php echo htmlspecialchars($project_data['card_description'] ?? ''); ?></textarea>
                                 <small class="form-text text-muted">Brief description shown on the project card.</small>
                             </div>
                             
                             <div class="mb-3">
                                 <label for="modal_description" class="form-label">Modal Description (Detailed)</label>
                                 <textarea class="form-control" id="modal_description" name="modal_description" rows="6" required><?php echo htmlspecialchars($project_data['modal_description'] ?? ''); ?></textarea>
                                 <small class="form-text text-muted">Detailed overview displayed in the project modal.</small>
                             </div>

                             <div class="mb-3">
                                 <label for="features" class="form-label">Key Features (Comma-separated)</label>
                                 <textarea class="form-control" id="features" name="features" rows="3"><?php echo htmlspecialchars($project_data['features'] ?? ''); ?></textarea>
                                 <small class="form-text text-muted">List features separated by commas (e.g., "Feature 1, Feature 2, Feature 3").</small>
                             </div>
                             
                             <div class="mb-3">
                                 <label for="case_study_url" class="form-label">Case Study URL (Optional)</label>
                                 <input type="url" class="form-control" id="case_study_url" name="case_study_url" value="<?php echo htmlspecialchars($project_data['case_study_url'] ?? ''); ?>">
                                 <small class="form-text text-muted">Full URL to a detailed case study or project page.</small>
                             </div>
                         </div>
                     </div>

                     <button type="submit" class="btn btn-success me-2"><i class="fas fa-save"></i> Save Project</button>
                     <a href="manage_projects.php" class="btn btn-secondary">Cancel</a>
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