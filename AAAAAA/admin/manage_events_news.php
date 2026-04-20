<?php 
// technowatch/admin/manage_events_news.php - Full CRUD Management View

session_start();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete_selected' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        if (!empty($item_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));
            $stmt = $conn->prepare("DELETE FROM events_news WHERE item_id IN ($placeholders)");
            if ($stmt) {
                $bind_params = array_merge([$types], $item_ids_to_delete);
                if (call_user_func_array([$stmt, 'bind_param'], array_by_ref($bind_params))) {
                    if ($stmt->execute()) {
                        $_SESSION['message'] = count($item_ids_to_delete) . " item(s) deleted successfully.";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error deleting items: " . $stmt->error;
                        $_SESSION['msg_type'] = "danger";
                    }
                }
                $stmt->close();
            }
        }
        header('Location: manage_events_news.php');
        exit;
    }
}

function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}

if ($action == 'edit' && $item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM events_news WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
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
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'primary-indigo': '#6366f1',
                    'bg-primary': '#0f172a',
                    'bg-secondary': '#1e293b',
                    'border-col': '#334155',
                    'text-primary': '#f8fafc',
                    'text-secondary': '#adb5bd',
                },
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                }
            }
        }
    }
</script>
<link rel="stylesheet" href="assets/css/sidebar.css">
<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body class="bg-bg-primary text-text-primary font-sans">
    <div class="admin-sidebar">
        <?php $current_file = basename($_SERVER['PHP_SELF']); include 'includes/admin_sidebar.php'; ?>
    </div>

    <div id="page-content-wrapper" class="ml-[250px] w-[calc(100%-250px)] p-8">
        <div class="container-fluid max-w-7xl mx-auto">
            <h1 class="text-4xl font-bold mb-6 text-white"> Manage Events & News</h1>


            <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-<?php echo $_SESSION['msg_type'] == 'success' ? 'green' : ($_SESSION['msg_type'] == 'danger' ? 'red' : 'yellow'); ?>-900/40 text-<?php echo $_SESSION['msg_type'] == 'success' ? 'green' : ($_SESSION['msg_type'] == 'danger' ? 'red' : 'yellow'); ?>-300 p-4 rounded-xl border border-<?php echo $_SESSION['msg_type'] == 'success' ? 'green' : ($_SESSION['msg_type'] == 'danger' ? 'red' : 'yellow'); ?>-700 mb-6">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <a href="manage_events_news.php?action=add" class="bg-primary-indigo hover:bg-indigo-700 text-white px-6 py-3 rounded-lg mb-6 inline-block transition-colors duration-200">
                    <i class="fas fa-plus-circle mr-2"></i> Add New Item
                </a>

                <?php 
                $query = "SELECT * FROM events_news ORDER BY created_at DESC";
                $result = $conn->query($query);
                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected items?')" class="bg-bg-secondary rounded-lg p-4 shadow-lg">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        <div class="overflow-x-auto rounded-lg border border-border-col">
                            <table class="min-w-full divide-y divide-border-col">
                                <thead class="bg-bg-secondary">
                                    <tr>
                                        <th class="px-4 py-3 text-left"><input type="checkbox" id="select_all_items" class="w-5 h-5 accent-primary-indigo cursor-pointer"></th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-text-secondary uppercase tracking-wider">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-text-secondary uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-text-secondary uppercase tracking-wider">Title</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-text-secondary uppercase tracking-wider">Date/Time & Location</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-text-secondary uppercase tracking-wider">Published</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-text-secondary uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-col">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-800/30">
                                        <td class="px-4 py-3"><input type="checkbox" name="selected_items[]" value="<?php echo $row['item_id']; ?>" class="item_checkbox w-5 h-5 accent-primary-indigo cursor-pointer"></td>
                                        <td class="px-4 py-3 text-text-primary"><?php echo $row['item_id']; ?></td>
                                        <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $row['type'] == 'event' ? 'bg-green-900 text-green-300' : 'bg-blue-900 text-blue-300'; ?>"><?php echo ucfirst($row['type']); ?></span></td>
                                        <td class="px-4 py-3 text-text-primary"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td class="px-4 py-3 text-text-secondary">
                                            <?php 
                                            $date_display = $row['event_date'] ? date('M j, Y', strtotime($row['event_date'])) : 'N/A';
                                            $time_display = $row['event_time'] ? ' @ ' . date('g:i A', strtotime($row['event_time'])) : '';
                                            echo $date_display . $time_display;
                                            ?>
                                            <?php if(!empty($row['location'])): ?><br><small class="text-text-secondary"><i class="fas fa-map-marker-alt text-primary-indigo mr-1"></i> <?php echo htmlspecialchars($row['location']); ?></small><?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $row['is_published'] ? 'bg-primary-indigo text-white' : 'bg-gray-600 text-gray-300'; ?>"><?php echo $row['is_published'] ? 'Yes' : 'No'; ?></span></td>
                                        <td class="px-4 py-3">
                                            <a href="manage_events_news.php?action=edit&id=<?php echo $row['item_id']; ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded-md mr-2 transition-colors duration-200" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="actions.php?delete=event&id=<?php echo $row['item_id']; ?>" onclick="return confirm('Are you sure you want to delete this item?')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md transition-colors duration-200" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md mt-4 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed" id="delete_selected_btn" disabled>
                            <i class="fas fa-trash-alt mr-2"></i> Delete Selected Items
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-blue-900/40 text-blue-300 p-4 rounded-xl border border-blue-700">No events or news items found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?></h2>
                <p class="text-gray-400 mb-6"><a href="manage_events_news.php?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to Events & News List</a></p>
                <form action="actions.php" method="POST" enctype="multipart/form-data" class="bg-bg-secondary p-6 rounded-lg shadow-lg space-y-6">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_event' : 'add_event'; ?>">

                    <div>
                        <label for="title" class="block text-sm font-semibold text-text-primary mb-2">Title</label>
                        <input type="text" class="w-full px-4 py-3 bg-bg-primary border border-border-col rounded-lg text-white focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="title" name="title" value="<?php echo htmlspecialchars($item_data['title'] ?? ''); ?>" required>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-semibold text-text-primary mb-2">Type</label>
                        <select class="w-full px-4 py-3 bg-bg-primary border border-border-col rounded-lg text-white focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="type" name="type" required>
                            <option value="news" <?php if(($item_data['type'] ?? 'news') == 'news') echo 'selected'; ?>>News</option>
                            <option value="event" <?php if(($item_data['type'] ?? '') == 'event') echo 'selected'; ?>>Event</option>
                        </select>
                    </div>

                    <div>
                        <label for="summary" class="block text-sm font-semibold text-text-primary mb-2">Summary</label>
                        <textarea class="w-full px-4 py-3 bg-bg-primary border border-border-col rounded-lg text-white focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="summary" name="summary" rows="2"><?php echo htmlspecialchars($item_data['summary'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-semibold text-text-primary mb-2">Full Content</label>
                        <textarea class="w-full px-4 py-3 bg-bg-primary border border-border-col rounded-lg text-white focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="content" name="content" rows="6" required><?php echo htmlspecialchars($item_data['content'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label for="event_date" class="block text-sm font-semibold text-text-primary mb-2">Event Date</label>
                        <input type="date" class="w-full px-4 py-3 bg-bg-primary border border-border-col rounded-lg text-white focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="event_date" name="event_date" value="<?php echo htmlspecialchars($item_data['event_date'] ?? ''); ?>">
                        <p class="text-sm text-text-secondary mt-1">Leave blank if this is a News item.</p>
                    </div>

                    <div>
                        <label for="event_time" class="block text-sm font-semibold text-text-primary mb-2">Event Time</label>
                        <input type="time" class="w-full px-4 py-3 bg-bg-primary border border-border-col rounded-lg text-white focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="event_time" name="event_time" value="<?php echo htmlspecialchars($item_data['event_time'] ?? ''); ?>">
                        <p class="text-sm text-text-secondary mt-1">Optional for Events.</p>
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-semibold text-text-primary mb-2">Location</label>
                        <input type="text" class="w-full px-4 py-3 bg-bg-primary border border-border-col rounded-lg text-white focus:ring-2 focus:ring-primary-indigo focus:border-primary-indigo transition" id="location" name="location" value="<?php echo htmlspecialchars($item_data['location'] ?? ''); ?>">
                        <p class="text-sm text-text-secondary mt-1">Optional for Events.</p>
                    </div>

                    <div>
                        <label for="image_path" class="block text-sm font-semibold text-text-primary mb-2">Image Upload</label>
                        <input type="file" class="w-full px-4 py-3 bg-bg-primary border border-border-col rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 transition" id="image_path" name="image_path" accept="image/*">
                        <?php if (!empty($item_data['image_path'])): ?>
                            <div class="mt-3">
                                Current Image: <br>
                                <img src="../<?php echo htmlspecialchars($item_data['image_path']); ?>" alt="Current Image" class="w-24 h-auto rounded-md border border-border-col mt-2">
                            </div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($item_data['image_path']); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" class="w-5 h-5 accent-primary-indigo cursor-pointer" id="is_published" name="is_published" value="1" <?php if(($item_data['is_published'] ?? 1)) echo 'checked'; ?>>
                        <label for="is_published" class="ml-3 text-text-primary">Publish to Main Site</label>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i> Save
                        </button>
                        <a href="manage_events_news.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">Cancel</a>
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