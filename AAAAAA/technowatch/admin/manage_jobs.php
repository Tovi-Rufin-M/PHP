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

        $stmt = $conn->prepare("DELETE FROM job_postings WHERE job_id IN ($placeholders)");
        
        if ($stmt) {
            $stmt->bind_param($types, ...$job_ids_to_delete);

            if ($stmt->execute()) {
                $_SESSION['message'] = count($job_ids_to_delete) . " job(s) deleted successfully.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['message'] = "Error deleting jobs: " . $stmt->error;
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
        // NOTE: Category field removed from POST handling

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
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/.css">

    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- ADMIN SIDEBAR -->
    <div class="admin-sidebar">
        <?php include 'includes/admin_sidebar.php'; ?>
    </div>
    <div class="main-content">
        <div class="container">
            
            <h1>💼 Manage Job Postings</h1>

            <?php 
            // Display alert messages from session
            if (isset($_SESSION['message'])): 
            ?>
                <div class="alert alert-<?php echo $_SESSION['msg_type'] ?? 'info'; ?>">
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); ?>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                
                <h2>Job Postings List</h2>
                <a href="manage_jobs.php?action=add" class="btn btn-primary mb-4">➕ Post New Job</a>
                <hr>

                <?php
                // Fetch all jobs for the list view - Removed 'category' from SELECT
                $query = "SELECT job_id, title, company_name, location, job_type, salary_range, is_published, created_at FROM job_postings ORDER BY created_at DESC";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0): 
                ?>
                    <!-- Bulk Action Form -->
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected job postings? This action cannot be undone.')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <!-- Checkbox for Select All -->
                                        <th><input type="checkbox" id="select_all_jobs" title="Select All"></th>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Company</th>
                                        <!-- Removed Category Header -->
                                        <th>Type/Salary</th>
                                        <th>Location</th>
                                        <th>Published</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <!-- Individual Checkbox -->
                                        <td>
                                            <input type="checkbox" name="selected_jobs[]" value="<?php echo $row['job_id']; ?>" class="job_checkbox">
                                        </td>
                                        <td><?php echo $row['job_id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                        <!-- Removed Category Data Cell -->
                                        <td><?php echo htmlspecialchars($row['job_type']); ?><br><small class="text-muted"><?php echo htmlspecialchars($row['salary_range']); ?></small></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($row['is_published'] ? 'success' : 'secondary'); ?>">
                                                <?php echo $row['is_published'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="manage_jobs.php?action=edit&id=<?php echo $row['job_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="manage_jobs.php?action=delete&id=<?php echo $row['job_id']; ?>" 
                                                onclick="return confirm('Are you sure you want to delete job ID <?php echo $row['job_id']; ?>?')" 
                                                class="btn btn-sm btn-danger">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Bulk Delete Button (Disabled by default) -->
                        <button type="submit" class="btn btn-danger mt-3" id="delete_selected_btn" disabled>
                            <i class="fas fa-trash-alt me-1"></i> Delete Selected Jobs
                        </button>
                    </form>

                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        No job postings found. Click 'Post New Job' to create one.
                    </div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>

                <!-- Job Create/Edit Form -->
                <h2><?php echo $job_id ? 'Edit Job Posting (ID: ' . $job_id . ')' : 'Create New Job Posting'; ?></h2>
                <p class="text-muted"><a href="manage_jobs.php?action=list">← Back to Job List</a></p>
                <hr>

                <form method="POST">
                    <!-- Hidden field for Job ID (used during update) -->
                    <input type="hidden" name="job_id" value="<?php echo getData('job_id', $job_data); ?>">

                    <label for="title">Job Title</label>
                    <input type="text" id="title" name="title" value="<?php echo getData('title', $job_data); ?>" required>

                    <!-- Removed Category field/select box -->

                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo getData('company_name', $job_data); ?>" required>

                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo getData('location', $job_data); ?>" required>

                    <label for="job_type">Job Type</label>
                    <select id="job_type" name="job_type" required>
                        <?php $current_type = getData('job_type', $job_data); ?>
                        <?php foreach ($job_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" 
                                <?php if ($current_type === $type) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="salary_range">Salary Range (e.g., ₱20,000 - ₱25,000 / Negotiable)</label>
                    <input type="text" id="salary_range" name="salary_range" value="<?php echo getData('salary_range', $job_data); ?>">

                    <label for="description">Job Description</label>
                    <textarea id="description" name="description" required><?php echo getData('description', $job_data); ?></textarea>

                    <label for="application_link">Application Link (URL)</label>
                    <input type="text" id="application_link" name="application_link" value="<?php echo getData('application_link', $job_data); ?>">

                    <div class="checkbox-group">
                        <input type="checkbox" id="is_published" name="is_published" value="1" 
                            <?php if (isset($job_data['is_published']) && $job_data['is_published'] == 1) echo 'checked'; ?>>
                        <label for="is_published">Publish Job (Visible to public)</label>
                    </div>

                    <div class="form-actions mt-4 pt-3 border-top d-flex justify-content-between">
                        <button type="submit" class="btn btn-success me-2">
                            <?php echo $job_id ? 'Update Job' : 'Create Job'; ?>
                        </button>
                        <a href="manage_jobs.php" class="btn btn-secondary">Cancel</a>
                        
                        <?php if ($job_id): ?>
                            <a href="manage_jobs.php?action=delete&id=<?php echo $job_id; ?>" 
                                onclick="return confirm('WARNING: Are you sure you want to PERMANENTLY delete this job (ID <?php echo $job_id; ?>)?')" 
                                class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> Delete Job
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
    
    <!-- JavaScript for Select All functionality -->
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
                        }
                        // Check if all are checked to re-select 'Select All'
                        const allChecked = Array.from(jobCheckboxes).every(cb => cb.checked);
                        if (allChecked) {
                            selectAllCheckbox.checked = true;
                        }
                        updateDeleteButtonState();
                    });
                });
                
                // Initial check for button state (in case of browser back/forward cache)
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