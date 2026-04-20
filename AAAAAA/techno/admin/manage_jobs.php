<?php 
// technowatch/admin/manage_jobs.php - Full CRUD Management View (Table: job_postings)

session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db_connect.php'; 

$action = $_GET['action'] ?? 'list';
// *** FIX: Primary Key is job_id ***
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = 'Manage Job Postings';
$form_title = ($action == 'edit' ? 'Edit Job' : 'Add New Job');

// Job data structure (matched to table columns)
$item_data = [
    'title' => '', 'company_name' => '', 'company_website' => '',
    'location' => '', 'description' => '', 'salary_range' => '', 
    'application_link' => '', 'is_published' => 1, 'job_type' => 'full-time'
];

// Re-defining array_by_ref for bind_param function consistency
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}


// --- BULK DELETE LOGIC (TABLE: job_postings) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete_selected' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        
        if (!empty($item_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));

            // *** FIX: Use 'job_postings' table and 'job_id' key ***
            $delete_stmt = $conn->prepare("DELETE FROM job_postings WHERE job_id IN ($placeholders)"); 
            if ($delete_stmt) {
                $bind_params_delete = array_merge([$types], $item_ids_to_delete);
                if (call_user_func_array([$delete_stmt, 'bind_param'], array_by_ref($bind_params_delete))) {
                    if ($delete_stmt->execute()) {
                        $_SESSION['message'] = count($item_ids_to_delete) . " job posting(s) deleted successfully.";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error deleting records: " . $delete_stmt->error;
                        $_SESSION['msg_type'] = "danger";
                    }
                }
                $delete_stmt->close();
            } else {
                $_SESSION['message'] = "Database error preparing delete statement.";
                $_SESSION['msg_type'] = "danger";
            }
        }
        header('Location: manage_jobs.php');
        exit;
    }
}


// --- EDIT FETCH LOGIC ---
if ($action == 'edit' && $item_id > 0) {
    // *** FIX: Use 'job_postings' table and 'job_id' key ***
    $stmt = $conn->prepare("SELECT * FROM job_postings WHERE job_id = ?"); 
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $fetched_data = $result->fetch_assoc();
        $item_data = array_merge($item_data, $fetched_data);
    } else {
        $_SESSION['message'] = "Job not found";
        $_SESSION['msg_type'] = "danger";
        header('Location: manage_jobs.php');
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
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    // Consistent color definitions
                    'dark-bg': '#0f172a',      
                    'dark-card': '#1e293b',    
                    'dark-input': '#334155',   
                    'dark-border': '#475569',  
                    'primary-indigo': '#6366f1',
                    'text-light': '#e2e8f0',   
                },
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                },
                spacing: {
                    '250': '250px', 
                }
            }
        }
    }
</script>
<link rel="stylesheet" href="assets/css/sidebar.css">
<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body class="bg-dark-bg text-text-light font-sans">
    <div class="admin-sidebar">
        <?php $current_file = basename($_SERVER['PHP_SELF']); include 'includes/admin_sidebar.php'; ?>
    </div>

    <div id="page-content-wrapper" class="ml-250 p-8 w-[calc(100%-250px)] min-h-screen box-border">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold mb-6 text-white"> Manage Job Postings</h1>

            <?php 
            // Display alert messages
            if (isset($_SESSION['message'])): 
                $alert_color_base = $_SESSION['msg_type'] == 'success' ? 'bg-green-600' : ($_SESSION['msg_type'] == 'danger' ? 'bg-red-600' : 'bg-yellow-600');
                $alert_icon = $_SESSION['msg_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            ?>
                <div class="<?php echo $alert_color_base; ?> text-white p-4 rounded-lg mb-6 flex items-center justify-between shadow-lg">
                    <div class="flex items-center">
                        <i class="fas <?php echo $alert_icon; ?> mr-3"></i>
                        <?php echo $_SESSION['message']; ?>
                    </div>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <div class="flex gap-4 mb-6">
                    <a href="manage_jobs.php?action=add" class="inline-flex items-center px-4 py-2 bg-primary-indigo hover:bg-indigo-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Job
                    </a>
                    <a href="actions.php?reset_id=jobs" onclick="return confirm('WARNING: This will reset the Job ID counter to 1. ONLY do this if the table is completely empty or you risk data corruption. Are you sure you want to proceed?')" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-redo-alt mr-2"></i> Reset Job IDs
                    </a>
                </div>
                <hr class="border-dark-border mb-6">

                <?php 
                // *** FIX: Use 'job_postings' table ***
                $query = "SELECT * FROM job_postings ORDER BY created_at DESC";
                $result = $conn->query($query);
                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected jobs?')" class="mb-6">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        
                        <div class="shadow-xl rounded-lg border border-dark-border overflow-x-auto">
                            <table class="min-w-full text-left text-text-light">
                                <thead class="text-xs uppercase bg-dark-input text-white border-b border-dark-border">
                                    <tr>
                                        <th scope="col" class="p-4"><input type="checkbox" id="select_all_items" title="Select All" class="form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></th>
                                        <th scope="col" class="py-3 px-6">No.</th> 
                                        <th scope="col" class="py-3 px-6">Title</th>
                                        <th scope="col" class="py-3 px-6">Company</th>
                                        <th scope="col" class="py-3 px-6">Type / Location</th>
                                        <th scope="col" class="py-3 px-6">Salary</th>
                                        <th scope="col" class="py-3 px-6">Published</th>
                                        <th scope="col" class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1; 
                                    while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4">
                                            <input type="checkbox" name="selected_items[]" value="<?php echo $row['job_id']; ?>" class="item_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"> 
                                        </td>
                                        <td class="py-4 px-6"><?php echo $counter++; ?></td> 
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td class="py-4 px-6"><?php echo htmlspecialchars($row['company_name']); ?></td>
                                        <td class="py-4 px-6 text-sm text-gray-400">
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-900/40 text-blue-300">
                                                <?php echo htmlspecialchars(ucfirst($row['job_type'])); ?>
                                            </span>
                                            <?php if(!empty($row['location'])): ?><br><small class="text-gray-400"><i class="fas fa-map-marker-alt text-primary-indigo mr-1"></i> <?php echo htmlspecialchars($row['location']); ?></small><?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-400">
                                            <?php echo htmlspecialchars($row['salary_range']); ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full 
                                                <?php echo $row['is_published'] ? 'bg-primary-indigo/40 text-primary-indigo' : 'bg-gray-600/40 text-gray-300'; ?>">
                                                <?php echo $row['is_published'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 flex items-center">
                                            <a href="manage_jobs.php?action=edit&id=<?php echo $row['job_id']; ?>" class="inline-flex items-center justify-center bg-yellow-600 hover:bg-yellow-700 text-white p-2 rounded-md mr-2 transition-colors duration-200" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="actions.php?delete=job&id=<?php echo $row['job_id']; ?>" onclick="return confirm('Are you sure you want to delete this job posting?')" class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white p-2 rounded-md transition-colors duration-200" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed" id="delete_selected_btn" disabled>
                                <i class="fas fa-trash-alt mr-2"></i> Delete Selected Jobs
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="bg-blue-900/40 text-blue-300 p-4 rounded-xl border border-blue-700 shadow-lg">No job postings found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?></h2>
                <p class="text-gray-400 mb-6"><a href="manage_jobs.php?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to Job Postings List</a></p>
                
                <form action="actions.php" method="POST" class="bg-dark-card p-8 rounded-xl shadow-2xl space-y-6 border border-dark-border">
                    <input type="hidden" name="job_id" value="<?php echo $item_id; ?>"> 
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_job' : 'add_job'; ?>">

                    <div>
                        <label for="title" class="block text-sm font-semibold text-text-light mb-2">Job Title</label>
                        <input type="text" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="title" name="title" value="<?php echo htmlspecialchars($item_data['title'] ?? ''); ?>" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="company_name" class="block text-sm font-semibold text-text-light mb-2">Company Name</label>
                            <input type="text" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="company_name" name="company_name" value="<?php echo htmlspecialchars($item_data['company_name'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="company_website" class="block text-sm font-semibold text-text-light mb-2">Company Website (URL)</label>
                            <input type="url" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="company_website" name="company_website" value="<?php echo htmlspecialchars($item_data['company_website'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="job_type" class="block text-sm font-semibold text-text-light mb-2">Employment Type</label>
                            <select class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="job_type" name="job_type" required>
                                <option value="full-time" <?php if(($item_data['job_type'] ?? '') == 'full-time') echo 'selected'; ?>>Full-Time</option>
                                <option value="part-time" <?php if(($item_data['job_type'] ?? '') == 'part-time') echo 'selected'; ?>>Part-Time</option>
                                <option value="contract" <?php if(($item_data['job_type'] ?? '') == 'contract') echo 'selected'; ?>>Contract</option>
                                <option value="internship" <?php if(($item_data['job_type'] ?? '') == 'internship') echo 'selected'; ?>>Internship</option>
                            </select>
                        </div>
                        <div>
                            <label for="location" class="block text-sm font-semibold text-text-light mb-2">Location</label>
                            <input type="text" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="location" name="location" value="<?php echo htmlspecialchars($item_data['location'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="salary_range" class="block text-sm font-semibold text-text-light mb-2">Salary Range (e.g., $60k - $80k)</label>
                            <input type="text" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="salary_range" name="salary_range" value="<?php echo htmlspecialchars($item_data['salary_range'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div>
                        <label for="application_link" class="block text-sm font-semibold text-text-light mb-2">Application Link (URL)</label>
                        <input type="url" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="application_link" name="application_link" value="<?php echo htmlspecialchars($item_data['application_link'] ?? ''); ?>">
                    </div>


                    <div>
                        <label for="description" class="block text-sm font-semibold text-text-light mb-2">Full Job Description</label>
                        <textarea class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="description" name="description" rows="8" required><?php echo htmlspecialchars($item_data['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" class="form-checkbox w-5 h-5 accent-primary-indigo cursor-pointer bg-dark-input border-dark-border rounded" id="is_published" name="is_published" value="1" <?php if(($item_data['is_published'] ?? 1)) echo 'checked'; ?>>
                        <label for="is_published" class="ml-3 text-text-light font-medium">Publish Job Listing</label>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i> <?php echo $action == 'edit' ? 'Update Job' : 'Create Job'; ?>
                        </button>
                        <a href="manage_jobs.php" class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200">
                            Cancel
                        </a>
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
            // Check if at least one checkbox is checked
            const isChecked = document.querySelectorAll('.item_checkbox:checked').length > 0;
            if (deleteButton) {
                deleteButton.disabled = !isChecked;
            }
        }

        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateDeleteButtonState();
        });

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                // Check if all individual boxes are checked to update the select all box
                selectAllCheckbox.checked = Array.from(itemCheckboxes).every(cb => cb.checked);
                updateDeleteButtonState();
            });
        });

        // Initial state update on page load
        updateDeleteButtonState();
    }
});
</script>

</body>
</html>