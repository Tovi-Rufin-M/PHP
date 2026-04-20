<?php 
// technowatch/admin/manage_events_news.php - Full CRUD Management View

session_start();
// Standalone DB connection
include 'includes/db_connect.php'; 

// Check for requested action
$action = $_GET['action'] ?? 'list';
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$page_title = 'Manage Events & News Items';
$form_title = ($action == 'edit' ? 'Edit Item' : 'Add New Item');

// Form pre-fill defaults
$item_data = [
    'title' => '',
    'type' => 'news',
    'summary' => '',
    'content' => '',
    'event_date' => '',
    'image_path' => '',
    'is_published' => 1,
    'location' => '', 
    'event_time' => ''
];

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    
    // Bulk deletion
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete_selected' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        
        if (!empty($item_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));
            $stmt = $conn->prepare("DELETE FROM events_news WHERE item_id IN ($placeholders)");
            
            if ($stmt) {
                // Using call_user_func_array for dynamic parameter binding
                $bind_params = array_merge([$types], $item_ids_to_delete);
                if (call_user_func_array([$stmt, 'bind_param'], array_by_ref($bind_params))) {
                    if ($stmt->execute()) {
                        $_SESSION['message'] = count($item_ids_to_delete) . " item(s) deleted successfully.";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error deleting items: " . $stmt->error;
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
        } else {
            $_SESSION['message'] = "No items were selected for deletion.";
            $_SESSION['msg_type'] = "warning";
        }
        header('Location: manage_events_news.php');
        exit;
    }
}

// Helper function for call_user_func_array with bind_param
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}


// --- Edit mode ---
if ($action == 'edit' && $item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM events_news WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Fetch data and merge with defaults to ensure all keys exist
        $fetched_data = $result->fetch_assoc();
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
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="stylesheet" href="assets/css/events.css">


<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-sidebar">
        <?php 
        // Logic to determine if this page should be active
        $current_file = basename($_SERVER['PHP_SELF']);
        $is_active = ($current_file == 'manage_events_news.php');
        // Include the sidebar with the active state check
        include 'includes/admin_sidebar.php'; 
        ?>
    </div>

    <div id="page-content-wrapper">
        <div class="container-fluid pt-4">
            <h1 class="mb-4"><?php echo $page_title; ?></h1>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <a href="manage_events_news.php?action=add" class="btn btn-primary mb-4">Add New Item</a>

                <?php 
                $query = "SELECT * FROM events_news ORDER BY created_at DESC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected items?')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th><input type="checkbox" id="select_all_items" title="Select All"></th>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Date/Time & Location</th>
                                        <th>Published</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_items[]" value="<?php echo $row['item_id']; ?>" class="item_checkbox"></td>
                                        <td><?php echo $row['item_id']; ?></td>
                                        <td><span class="badge bg-<?php echo ($row['type'] == 'event' ? 'success' : 'info'); ?>"><?php echo ucfirst($row['type']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td>
                                            <?php 
                                            // Combine date and time display
                                            $date_display = $row['event_date'] ? date('M j, Y', strtotime($row['event_date'])) : 'N/A';
                                            $time_display = $row['event_time'] ? ' @ ' . date('g:i A', strtotime($row['event_time'])) : '';
                                            ?>
                                            <?php echo $date_display . $time_display; ?>
                                            <?php if(!empty($row['location'])): ?><br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?></small><?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-<?php echo ($row['is_published'] ? 'primary' : 'secondary'); ?>"><?php echo $row['is_published'] ? 'Yes' : 'No'; ?></span></td>
                                        <td>
                                            <a href="manage_events_news.php?action=edit&id=<?php echo $row['item_id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="actions.php?delete=event&id=<?php echo $row['item_id']; ?>" onclick="return confirm('Are you sure you want to delete this item?')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-danger mt-3" id="delete_selected_btn" disabled><i class="fas fa-trash-alt"></i> Delete Selected Items</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">No events or news items found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2><?php echo $form_title; ?></h2>
                <hr>
                <form action="actions.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_event' : 'add_event'; ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($item_data['title'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="news" <?php if(($item_data['type'] ?? 'news') == 'news') echo 'selected'; ?>>News</option>
                            <option value="event" <?php if(($item_data['type'] ?? '') == 'event') echo 'selected'; ?>>Event</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="summary" class="form-label">Summary</label>
                        <textarea class="form-control" id="summary" name="summary" rows="2"><?php echo htmlspecialchars($item_data['summary'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Full Content</label>
                        <textarea class="form-control" id="content" name="content" rows="6" required><?php echo htmlspecialchars($item_data['content'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="event_date" class="form-label">Event Date</label>
                        <input type="date" class="form-control" id="event_date" name="event_date" value="<?php echo htmlspecialchars($item_data['event_date'] ?? ''); ?>">
                        <div class="form-text">Leave blank if this is a News item.</div>
                    </div>

                    <div class="mb-3">
                        <label for="event_time" class="form-label">Event Time</label>
                        <input type="time" class="form-control" id="event_time" name="event_time" value="<?php echo htmlspecialchars($item_data['event_time'] ?? ''); ?>">
                        <div class="form-text">Optional for Events.</div>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($item_data['location'] ?? ''); ?>">
                        <div class="form-text">Optional for Events.</div>
                    </div>

                    <div class="mb-3">
                        <label for="image_path" class="form-label">Image Upload</label>
                        <input type="file" class="form-control" id="image_path" name="image_path" accept="image/*">
                        <?php if (!empty($item_data['image_path'])): ?>
                            <div class="mt-2">Current Image: <img src="../<?php echo htmlspecialchars($item_data['image_path']); ?>" alt="Current Image" style="width:100px; height: auto; border: 1px solid #ccc;"></div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($item_data['image_path']); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1" <?php if(($item_data['is_published'] ?? 1)) echo 'checked'; ?>>
                        <label class="form-check-label" for="is_published">Publish to Main Site</label>
                    </div>

                    <button type="submit" class="btn btn-success me-2"><i class="fas fa-save"></i> Save</button>
                    <a href="manage_events_news.php" class="btn btn-secondary">Cancel</a>
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
                selectAllCheckbox.checked = Array.from(itemCheckboxes).every(cb => cb.checked);
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