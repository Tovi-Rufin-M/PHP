<?php
// technowatch/admin/manage_jobs.php - Admin interface for creating, editing, and deleting job postings

session_start();
// Security check placeholder (assuming authentication is handled elsewhere)
// if (!isset($_SESSION['admin_logged_in'])) {
//     header('Location: login.php');
//     exit;
// }

include 'includes/db_connect.php';

// Initialize action and ID
$action = $_GET['action'] ?? 'list';
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$job_data = [];

// --- Helper function to safely get data for form pre-filling ---
function getData($key, $job_data) {
    // Use the null coalescing operator (??) to safely return the value or an empty string
    return htmlspecialchars($job_data[$key] ?? '');
}

// --- Hardcoded Job Types (for select boxes) ---
$job_types = [
    'Internship', 'Full-time', 'Part-time', 'Contract'
];
// --- End Hardcoded Lists ---

// --- 1. HANDLE DELETION & BULK ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete_selected' && isset($_POST['selected_jobs']) && is_array($_POST['selected_jobs'])) {
        // --- HANDLE BULK DELETE ---
        $job_ids_to_delete = array_map('intval', $_POST['selected_jobs']);
        
        if (empty($job_ids_to_delete)) {
            $_SESSION['message'] = "No jobs were selected for deletion.";
            $_SESSION['msg_type'] = "warning";
            header('Location: manage_jobs.php');
            exit;
        }
        
        $placeholders = implode(',', array_fill(0, count($job_ids_to_delete), '?'));
        $types = str_repeat('i', count($job_ids_to_delete));

        // Helper function for bind_param with variable number of arguments
        // This is often needed when using bulk binding with `call_user_func_array` or `...`
        function array_by_ref_jobs(&$arr) {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs; 
        }

        $stmt = $conn->prepare("DELETE FROM job_postings WHERE job_id IN ($placeholders)");
        
        if ($stmt) {
            $bind_params = array_merge([$types], $job_ids_to_delete);

            if (call_user_func_array([$stmt, 'bind_param'], array_by_ref_jobs($bind_params))) {
                if ($stmt->execute()) {
                    $_SESSION['message'] = count($job_ids_to_delete) . " job(s) deleted successfully.";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error deleting jobs: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
            } else {
                 $_SESSION['message'] = "Error binding parameters for bulk delete.";
                 $_SESSION['msg_type'] = "danger";
            }
            $stmt->close();
        } else {
             $_SESSION['message'] = "Database error preparing statement for bulk delete.";
             $_SESSION['msg_type'] = "danger";
        }

        header('Location: manage_jobs.php');
        exit;

    } elseif (isset($_POST['job_id'])) {
        // --- HANDLE SINGLE JOB CREATE/UPDATE ---
        $job_id_post = $_POST['job_id'] ?? null;
        $title = $_POST['title'] ?? '';
        $company_name = $_POST['company_name'] ?? '';
        $location = $_POST['location'] ?? '';
        $job_type = $_POST['job_type'] ?? '';
        $description = $_POST['description'] ?? '';
        $application_link = $_POST['application_link'] ?? '';
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $salary_range = $_POST['salary_range'] ?? ''; 
        
        if ($job_id_post) {
            // UPDATE Existing Job - Removed 'category'
            $stmt = $conn->prepare("UPDATE job_postings SET title=?, company_name=?, location=?, job_type=?, description=?, application_link=?, is_published=?, salary_range=? WHERE job_id=?");
            // Binding: (6x s, i, s, i)
            $stmt->bind_param("ssssssisi", $title, $company_name, $location, $job_type, $description, $application_link, $is_published, $salary_range, $job_id_post);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Job ID {$job_id_post} updated successfully!";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating job: " . $stmt->error;
                $_SESSION['msg_type'] = "danger";
            }
            $stmt->close();
        } else {
            // CREATE New Job - Removed 'category' column
            $stmt = $conn->prepare("INSERT INTO job_postings (title, company_name, location, job_type, description, application_link, is_published, salary_range) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            // Binding: (6x s, i, s)
            $stmt->bind_param("ssssssis", $title, $company_name, $location, $job_type, $description, $application_link, $is_published, $salary_range);

            if ($stmt->execute()) {
                $_SESSION['message'] = "New job posted successfully!";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['message'] = "Error creating job: " . $stmt->error;
                $_SESSION['msg_type'] = "danger";
            }
            $stmt->close();
        }
        // Redirect after successful POST to prevent resubmission
        header('Location: manage_jobs.php');
        exit;
    }
}

// --- 2. HANDLE SINGLE JOB DELETION (via GET parameter) ---
if ($action == 'delete' && $job_id && $conn) {
    $stmt = $conn->prepare("DELETE FROM job_postings WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Job ID {$job_id} deleted successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting job: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
    // Redirect back to list view
    header('Location: manage_jobs.php');
    exit;
}

// --- 3. LOAD JOB DATA FOR EDITING (if action is edit) ---
if ($action == 'edit' && $job_id && $conn) {
    // Select all fields (including any existing 'category' data if present in DB)
    $stmt = $conn->prepare("SELECT * FROM job_postings WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $job_data = $result->fetch_assoc();
    } else {
        $_SESSION['message'] = "Job not found. Creating a new job instead.";
        $_SESSION['msg_type'] = "warning";
        $job_id = null;
        $action = 'add'; 
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($action == 'edit' ? 'Edit Job Posting' : ($action == 'add' ? 'Create New Job' : 'Manage Jobs')); ?> | Admin</title>

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
        $is_active = ($current_file == 'manage_jobs.php');
        include 'includes/admin_sidebar.php'; 
        ?>
    </div>
    
    <div id="page-content-wrapper" class="ml-250 p-8 w-[calc(100%-250px)] min-h-screen box-border">
        <div class="container mx-auto">
            
            <h1 class="text-4xl font-bold mb-6 text-white"> Manage Job Postings</h1>

            <?php 
            // Display alert messages from session
            if (isset($_SESSION['message'])): 
                // Determine Tailwind classes based on message type
                $alert_color = $_SESSION['msg_type'] == 'success' ? 'bg-green-600' : ($_SESSION['msg_type'] == 'danger' ? 'bg-red-600' : ($_SESSION['msg_type'] == 'warning' ? 'bg-yellow-600' : 'bg-blue-600'));
                $alert_icon = $_SESSION['msg_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            ?>
                <div class="<?php echo $alert_color; ?> text-white p-4 rounded-lg mb-6 flex items-center justify-between shadow-lg">
                    <div class="flex items-center">
                         <i class="fas <?php echo $alert_icon; ?> mr-3"></i>
                        <?php echo $_SESSION['message']; ?>
                    </div>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); ?>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                

                
                <a href="manage_jobs.php?action=add" class="inline-flex items-center px-4 py-2 bg-primary-indigo hover:bg-indigo-600 text-white font-semibold rounded-lg shadow-md mb-6 transition duration-200">
                    <i class="fas fa-plus-circle mr-2"></i> Post New Job
                </a>
                <hr class="border-dark-border mb-6">

                <?php
                // Fetch all jobs for the list view - Removed 'category' from SELECT
                $query = "SELECT job_id, title, company_name, location, job_type, salary_range, is_published, created_at FROM job_postings ORDER BY created_at DESC";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0): 
                ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected job postings? This action cannot be undone.')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        
                        <div class="overflow-x-auto shadow-xl rounded-lg border border-dark-border">
                            <table class="w-full text-left text-text-light">
                                <thead class="text-xs uppercase bg-dark-input text-white border-b border-dark-border">
                                    <tr>
                                        <th scope="col" class="p-4"><input type="checkbox" id="select_all_jobs" title="Select All" class="form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></th>
                                        <th scope="col" class="py-3 px-6">ID</th>
                                        <th scope="col" class="py-3 px-6">Title</th>
                                        <th scope="col" class="py-3 px-6">Company</th>
                                        <th scope="col" class="py-3 px-6">Type/Salary</th>
                                        <th scope="col" class="py-3 px-6">Location</th>
                                        <th scope="col" class="py-3 px-6">Published</th>
                                        <th scope="col" class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4">
                                            <input type="checkbox" name="selected_jobs[]" value="<?php echo $row['job_id']; ?>" class="job_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded">
                                        </td>
                                        <td class="py-4 px-6"><?php echo $row['job_id']; ?></td>
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td class="py-4 px-6 text-sm"><?php echo htmlspecialchars($row['company_name']); ?></td>
                                        <td class="py-4 px-6 text-sm font-light"><?php echo htmlspecialchars($row['job_type']); ?><br><small class="text-gray-400"><?php echo htmlspecialchars($row['salary_range']); ?></small></td>
                                        <td class="py-4 px-6 text-sm italic"><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td class="py-4 px-6">
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full 
                                                <?php echo ($row['is_published'] ? 'bg-green-600 text-white' : 'bg-gray-600 text-gray-200'); ?>">
                                                <?php echo $row['is_published'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 flex space-x-2">
                                            <a href="manage_jobs.php?action=edit&id=<?php echo $row['job_id']; ?>" class="p-2 text-dark-bg bg-yellow-400 hover:bg-yellow-500 rounded-lg" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_jobs.php?action=delete&id=<?php echo $row['job_id']; ?>" 
                                                onclick="return confirm('Are you sure you want to delete job ID <?php echo $row['job_id']; ?>?')" 
                                                class="p-2 text-white bg-red-600 hover:bg-red-700 rounded-lg" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="submit" class="mt-6 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md disabled:opacity-50 transition duration-200" id="delete_selected_btn" disabled>
                            <i class="fas fa-trash-alt mr-2"></i> Delete Selected Jobs
                        </button>
                    </form>

                <?php else: ?>
                    <div class="p-4 bg-blue-600 text-white rounded-lg shadow-md" role="alert">
                        No job postings found. Click 'Post New Job' to create one.
                    </div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>

                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $job_id ? 'Edit Job Posting (ID: ' . $job_id . ')' : 'Create New Job Posting'; ?></h2>
                <p class="text-gray-400 mb-6"><a href="manage_jobs.php?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to Job List</a></p>
                <hr class="border-dark-border mb-6">

                <form method="POST" class="bg-dark-card p-8 rounded-xl shadow-2xl max-w-4xl space-y-4">
                    <input type="hidden" name="job_id" value="<?php echo getData('job_id', $job_data); ?>">

                    <div>
                        <label for="title" class="block text-sm font-medium text-text-light mb-1">Job Title</label>
                        <input type="text" id="title" name="title" value="<?php echo getData('title', $job_data); ?>" required class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-text-light mb-1">Company Name</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo getData('company_name', $job_data); ?>" required class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light">
                        </div>
                        
                        <div>
                            <label for="location" class="block text-sm font-medium text-text-light mb-1">Location</label>
                            <input type="text" id="location" name="location" value="<?php echo getData('location', $job_data); ?>" required class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light">
                        </div>
                        
                        <div>
                            <label for="job_type" class="block text-sm font-medium text-text-light mb-1">Job Type</label>
                            <select id="job_type" name="job_type" required class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light">
                                <?php $current_type = getData('job_type', $job_data); ?>
                                <option value="" disabled <?php if (empty($current_type)) echo 'selected'; ?>>Select Type</option>
                                <?php foreach ($job_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php if ($current_type === $type) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="salary_range" class="block text-sm font-medium text-text-light mb-1">Salary Range (e.g., ₱20,000 - ₱25,000 / Negotiable)</label>
                        <input type="text" id="salary_range" name="salary_range" value="<?php echo getData('salary_range', $job_data); ?>" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-text-light mb-1">Job Description</label>
                        <textarea id="description" name="description" required rows="6" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light"><?php echo getData('description', $job_data); ?></textarea>
                    </div>

                    <div>
                        <label for="application_link" class="block text-sm font-medium text-text-light mb-1">Application Link (URL)</label>
                        <input type="url" id="application_link" name="application_link" value="<?php echo getData('application_link', $job_data); ?>" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" placeholder="e.g., https://yourcompany.com/apply">
                    </div>

                    <div class="flex items-center pt-2">
                        <input type="checkbox" id="is_published" name="is_published" value="1" 
                            <?php if (isset($job_data['is_published']) && $job_data['is_published'] == 1) echo 'checked'; ?>
                            class="form-checkbox h-5 w-5 text-primary-indigo bg-dark-input border-dark-border rounded focus:ring-primary-indigo">
                        <label for="is_published" class="ml-2 text-sm text-text-light">Publish Job (Visible to public)</label>
                    </div>

                    <div class="form-actions pt-6 border-t border-dark-border flex space-x-3 items-center">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                            <i class="fas fa-save mr-2"></i> <?php echo $job_id ? 'Update Job' : 'Create Job'; ?>
                        </button>
                        <a href="manage_jobs.php" class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                            Cancel
                        </a>
                        
                        <?php if ($job_id): ?>
                            <a href="manage_jobs.php?action=delete&id=<?php echo $job_id; ?>" 
                                onclick="return confirm('WARNING: Are you sure you want to PERMANENTLY delete this job (ID <?php echo $job_id; ?>)?')" 
                                class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200 ml-auto">
                                <i class="fas fa-trash mr-2"></i> Delete Job
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('select_all_jobs');
            const jobCheckboxes = document.querySelectorAll('.job_checkbox');
            const deleteButton = document.getElementById('delete_selected_btn');

            if (selectAllCheckbox && jobCheckboxes.length > 0) {
                // Function to update the state of the delete button
                function updateDeleteButtonState() {
                    const checkedCount = document.querySelectorAll('.job_checkbox:checked').length;
                    // Button is enabled if at least one box is checked
                    deleteButton.disabled = checkedCount === 0; 
                    // Add Tailwind class for visual disabled state
                    deleteButton.classList.toggle('opacity-50', deleteButton.disabled);
                }

                // Select All handler
                selectAllCheckbox.addEventListener('change', function() {
                    jobCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                    updateDeleteButtonState();
                });

                // Individual checkbox handlers
                jobCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        // If one is unchecked, uncheck the Select All box
                        if (!this.checked) {
                            selectAllCheckbox.checked = false;
                        } else {
                            // Check if all are checked to re-select 'Select All'
                            const allChecked = Array.from(jobCheckboxes).every(cb => cb.checked);
                            if (allChecked) {
                                selectAllCheckbox.checked = true;
                            }
                        }
                        updateDeleteButtonState();
                    });
                });
                
                // Initial check for button state
                updateDeleteButtonState();
            }
        });
    </script>

</body>
</html>
<?php 
// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>