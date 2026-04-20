<?php 
// technowatch/admin/manage_events_news.php - Full CRUD Management View

session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db_connect.php'; 

$action = $_GET['action'] ?? 'list';
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = 'Manage Events & News Items';
$form_title = ($action == 'edit' ? 'Edit Item' : 'Add New Item');

$item_data = [
    'title' => '', 'type' => 'news', 'summary' => '', 'content' => '', 
    'event_date' => '', 'image_path' => '', 'is_published' => 1, 
    'location' => '', 'event_time' => ''
];

// Re-defining array_by_ref for bind_param function consistency
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}


// --- BULK DELETE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete_selected' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        
        if (!empty($item_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));

            // --- STEP 1: Fetch Image Paths before Deleting Records ---
            $fetch_stmt = $conn->prepare("SELECT image_path FROM events_news WHERE item_id IN ($placeholders) AND image_path IS NOT NULL AND image_path != ''");
            if ($fetch_stmt) {
                $bind_params_fetch = array_merge([$types], $item_ids_to_delete);
                if (call_user_func_array([$fetch_stmt, 'bind_param'], array_by_ref($bind_params_fetch))) {
                    $fetch_stmt->execute();
                    $result = $fetch_stmt->get_result();
                    $images_to_delete = [];
                    while ($row = $result->fetch_assoc()) {
                        $images_to_delete[] = $row['image_path'];
                    }
                    $fetch_stmt->close();

                    // --- STEP 2: Delete Files from the Server ---
                    $files_deleted_count = 0;
                    foreach ($images_to_delete as $image_path_db) {
                        $full_file_path = __DIR__ . '/../' . $image_path_db; 
                        
                        if (file_exists($full_file_path) && is_file($full_file_path)) {
                            if (unlink($full_file_path)) {
                                $files_deleted_count++;
                            } else {
                                error_log("Failed to delete file: " . $full_file_path);
                            }
                        }
                    }
                    
                    // --- STEP 3: Delete Records from Database ---
                    $delete_stmt = $conn->prepare("DELETE FROM events_news WHERE item_id IN ($placeholders)");
                    if ($delete_stmt) {
                        $bind_params_delete = array_merge([$types], $item_ids_to_delete);
                        if (call_user_func_array([$delete_stmt, 'bind_param'], array_by_ref($bind_params_delete))) {
                            if ($delete_stmt->execute()) {
                                $_SESSION['message'] = count($item_ids_to_delete) . " item(s) deleted successfully. ($files_deleted_count image(s) removed).";
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
            } else {
                $_SESSION['message'] = "Database error preparing fetch statement.";
                $_SESSION['msg_type'] = "danger";
            }
        }
        header('Location: manage_events_news.php');
        exit;
    }
}


// --- EDIT FETCH LOGIC ---
if ($action == 'edit' && $item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM events_news WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $fetched_data = $result->fetch_assoc();
        // Format dates/times for form input compatibility
        $fetched_data['event_date'] = date('Y-m-d', strtotime($fetched_data['event_date'])) ?: '';
        $fetched_data['event_time'] = $fetched_data['event_time'] ? date('H:i', strtotime($fetched_data['event_time'])) : '';
        
        $item_data = array_merge($item_data, $fetched_data);
    } else {
        $_SESSION['message'] = "Item not found";
        $_SESSION['msg_type'] = "danger";
        header('Location: manage_events_news.php');
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
                    'dark-bg': '#0f172a',       // Deep Navy (for body)
                    'dark-card': '#1e293b',     // Dark Slate (for forms/tables)
                    'dark-input': '#334155',    // Medium Slate (for inputs/table headers)
                    'dark-border': '#475569',   // Grayish Blue (for borders/lines)
                    'primary-indigo': '#6366f1',// Indigo 500 (Primary button)
                    'text-light': '#e2e8f0',    // Off-White (Primary text)
                    // Kept for backward compatibility but using the 'dark' names in new code
                    'bg-primary': '#0f172a',
                    'bg-secondary': '#1e293b',
                    'border-col': '#334155',
                    'text-primary': '#f8fafc',
                    'text-secondary': '#adb5bd',
                },
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                },
                spacing: {
                    '250': '250px', // Custom width for sidebar offset
                }
            }
        }
    }
</script>
<link rel="stylesheet" href="assets/css/sidebar.css">
<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>

<style>
/* Hides the default calendar/clock icons in WebKit browsers (Chrome, Safari, Edge) */
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator {
    opacity: 0;
    /* Use 'display: block' and positioning to ensure the whole field is clickable */
    position: absolute; 
    top: 0; 
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    /* Ensure the actual input text is not covered by this invisible overlay */
    z-index: 10; 
}

/* Make the input fields relative containers so the pseudo-element can be positioned */
input[type="date"], input[type="time"] {
    position: relative;
}
</style>
</head>
<body class="bg-dark-bg text-text-light font-sans">
    <div class="admin-sidebar">
        <?php $current_file = basename($_SERVER['PHP_SELF']); include 'includes/admin_sidebar.php'; ?>
    </div>

    <div id="page-content-wrapper" class="ml-250 p-8 w-[calc(100%-250px)] min-h-screen box-border">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold mb-6 text-white"> Manage Events & News</h1>

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
                    <a href="manage_events_news.php?action=add" class="inline-flex items-center px-4 py-2 bg-primary-indigo hover:bg-indigo-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Item
                    </a>
                    <a href="actions.php?reset_id=events" onclick="return confirm('WARNING: This will reset the Item ID counter to 1. ONLY do this if the table is completely empty or you risk data corruption. Are you sure you want to proceed?')" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-redo-alt mr-2"></i> Reset Item IDs
                    </a>
                </div>
                <hr class="border-dark-border mb-6">

                <?php 
                $query = "SELECT * FROM events_news ORDER BY created_at DESC";
                $result = $conn->query($query);
                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected items?')" class="mb-6">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        
                        <div class="shadow-xl rounded-lg border border-dark-border overflow-x-auto">
                            <table class="min-w-full text-left text-text-light">
                                <thead class="text-xs uppercase bg-dark-input text-white border-b border-dark-border">
                                    <tr>
                                        <th scope="col" class="p-4"><input type="checkbox" id="select_all_items" title="Select All" class="form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></th>
                                        <th scope="col" class="py-3 px-6">No.</th> <th scope="col" class="py-3 px-6">Type</th>
                                        <th scope="col" class="py-3 px-6">Title</th>
                                        <th scope="col" class="py-3 px-6">Date/Location</th>
                                        <th scope="col" class="py-3 px-6">Published</th>
                                        <th scope="col" class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1; // Initialize the sequential counter
                                    while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4">
                                            <input type="checkbox" name="selected_items[]" value="<?php echo $row['item_id']; ?>" class="item_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded">
                                        </td>
                                        <td class="py-4 px-6"><?php echo $counter++; ?></td> <td class="py-4 px-6">
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full 
                                                <?php echo $row['type'] == 'event' ? 'bg-green-900/40 text-green-300' : 'bg-blue-900/40 text-blue-300'; ?>">
                                                <?php echo ucfirst($row['type']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td class="py-4 px-6 text-sm text-gray-400">
                                            <?php 
                                            $date_display = $row['event_date'] ? date('M j, Y', strtotime($row['event_date'])) : 'N/A';
                                            $time_display = $row['event_time'] ? ' @ ' . date('g:i A', strtotime($row['event_time'])) : '';
                                            echo $date_display . $time_display;
                                            ?>
                                            <?php if(!empty($row['location'])): ?><br><small class="text-gray-400"><i class="fas fa-map-marker-alt text-primary-indigo mr-1"></i> <?php echo htmlspecialchars($row['location']); ?></small><?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full 
                                                <?php echo $row['is_published'] ? 'bg-primary-indigo/40 text-primary-indigo' : 'bg-gray-600/40 text-gray-300'; ?>">
                                                <?php echo $row['is_published'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 flex items-center">
                                            <a href="manage_events_news.php?action=edit&id=<?php echo $row['item_id']; ?>" class="inline-flex items-center justify-center bg-yellow-600 hover:bg-yellow-700 text-white p-2 rounded-md mr-2 transition-colors duration-200" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="actions.php?delete=event&id=<?php echo $row['item_id']; ?>" onclick="return confirm('Are you sure you want to delete this item?')" class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white p-2 rounded-md transition-colors duration-200" title="Delete">
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
                                <i class="fas fa-trash-alt mr-2"></i> Delete Selected Items
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="bg-blue-900/40 text-blue-300 p-4 rounded-xl border border-blue-700 shadow-lg">No events or news items found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?></h2>
                <p class="text-gray-400 mb-6"><a href="manage_events_news.php?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to Events & News List</a></p>
                
                <form action="actions.php" method="POST" enctype="multipart/form-data" class="bg-dark-card p-8 rounded-xl shadow-2xl space-y-6 border border-dark-border">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_event' : 'add_event'; ?>">

                    <div>
                        <label for="title" class="block text-sm font-semibold text-text-light mb-2">Title</label>
                        <input type="text" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="title" name="title" value="<?php echo htmlspecialchars($item_data['title'] ?? ''); ?>" required>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-semibold text-text-light mb-2">Type</label>
                        <select class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="type" name="type" required>
                            <option value="news" <?php if(($item_data['type'] ?? 'news') == 'news') echo 'selected'; ?>>News</option>
                            <option value="event" <?php if(($item_data['type'] ?? '') == 'event') echo 'selected'; ?>>Event</option>
                        </select>
                    </div>

                    <div>
                        <label for="summary" class="block text-sm font-semibold text-text-light mb-2">Summary</label>
                        <textarea class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="summary" name="summary" rows="2"><?php echo htmlspecialchars($item_data['summary'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-semibold text-text-light mb-2">Full Content</label>
                        <textarea class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="content" name="content" rows="6" required><?php echo htmlspecialchars($item_data['content'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="event_date" class="block text-sm font-semibold text-text-light mb-2">Event Date</label>
                            <input type="date" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="event_date" name="event_date" value="<?php echo htmlspecialchars($item_data['event_date'] ?? ''); ?>">
                            <p class="text-xs text-gray-500 mt-1">Leave blank if this is a News item.</p>
                        </div>

                        <div>
                            <label for="event_time" class="block text-sm font-semibold text-text-light mb-2">Event Time</label>
                            <input type="time" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="event_time" name="event_time" value="<?php echo htmlspecialchars($item_data['event_time'] ?? ''); ?>">
                            <p class="text-xs text-gray-500 mt-1">Optional for Events.</p>
                        </div>
                        
                        <div>
                            <label for="location" class="block text-sm font-semibold text-text-light mb-2">Location</label>
                            <input type="text" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="location" name="location" value="<?php echo htmlspecialchars($item_data['location'] ?? ''); ?>">
                            <p class="text-xs text-gray-500 mt-1">Optional for Events.</p>
                        </div>
                    </div>

                    <div>
                        <label for="image_path" class="block text-sm font-semibold text-text-light mb-2">Image Upload</label>
                        <input type="file" class="w-full px-4 py-3 bg-dark-input border border-dark-border rounded-lg text-text-light file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 transition" id="image_path" name="image_path" accept="image/*">
                        <?php if (!empty($item_data['image_path'])): ?>
                            <div class="mt-3">
                                <p class="text-sm text-gray-400">Current Image:</p>
                                <img src="../<?php echo htmlspecialchars($item_data['image_path']); ?>" alt="Current Image" class="w-24 h-auto rounded-md border border-dark-border mt-2">
                            </div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($item_data['image_path']); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" class="form-checkbox w-5 h-5 accent-primary-indigo cursor-pointer bg-dark-input border-dark-border rounded" id="is_published" name="is_published" value="1" <?php if(($item_data['is_published'] ?? 1)) echo 'checked'; ?>>
                        <label for="is_published" class="ml-3 text-text-light font-medium">Publish to Main Site</label>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i> <?php echo $action == 'edit' ? 'Update Item' : 'Create Item'; ?>
                        </button>
                        <a href="manage_events_news.php" class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200">
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