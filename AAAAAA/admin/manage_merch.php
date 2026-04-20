<?php 
// technowatch/admin/manage_merch.php - Simplified CRUD Management View

session_start();
// Standalone DB connection
include 'includes/db_connect.php'; 

// Helper function for call_user_func_array with bind_param
function array_by_ref(&$arr) {
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}

// Check for requested action
$action = $_GET['action'] ?? 'list';
// CORRECTED: Use 'id' instead of 'merch_id'
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$page_title = 'Manage Merch';
$form_title = ($action == 'edit' ? 'Edit Product' : 'Add New Product');

// Form pre-fill defaults - ADDED 'is_in_stock'
$merch_data = [
    'name' => '',
    'category' => '',
    'price' => '',
    'image_path' => '',
    'is_in_stock' => 1, // Default to In Stock
];

// Define the allowed categories (all lowercase, matching database storage)
$categories = ['tshirts', 'pins', 'lanyards', 'caps', 'others'];
// Store the current category from the database, ensure it's treated as lowercase for comparison
$current_category = strtolower($merch_data['category'] ?? '');

// --- Handle DELETE via GET (Single Delete) ---
if ($action == 'delete' && $item_id > 0) {
    // 1. Fetch item to get image path for physical file deletion
    $image_to_delete = '';
    // CORRECTED: Use 'id' in the WHERE clause
    $stmt_select = $conn->prepare("SELECT image_path FROM merch WHERE id = ?");
    $stmt_select->bind_param("i", $item_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($row = $result_select->fetch_assoc()) {
        $image_to_delete = $row['image_path'];
    }
    $stmt_select->close();

    // 2. Delete the database record
    // CORRECTED: Use 'id' in the WHERE clause
    $stmt = $conn->prepare("DELETE FROM merch WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Product (ID: $item_id) deleted successfully.";
            $_SESSION['msg_type'] = "success";

            // 3. Delete physical image file
            if (!empty($image_to_delete)) {
                $file_path = '../' . $image_to_delete; // Go up one directory to site root
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
        } else {
            $_SESSION['message'] = "Error deleting product: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error preparing delete statement.";
        $_SESSION['msg_type'] = "danger";
    }
    header('Location: manage_merch.php');
    exit;
}

// --- Handle POST actions (Bulk Delete, Add, Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    
    // --- 1. Bulk deletion ---
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete_selected' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        // CORRECTED: Use 'id' in the WHERE clause
        $item_ids_to_delete = array_map('intval', $_POST['selected_items']);
        
        if (!empty($item_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
            $types = str_repeat('i', count($item_ids_to_delete));
            $stmt = $conn->prepare("DELETE FROM merch WHERE id IN ($placeholders)");
            
            if ($stmt) {
                $bind_params = array_merge([$types], $item_ids_to_delete);
                if (call_user_func_array([$stmt, 'bind_param'], array_by_ref($bind_params))) {
                    if ($stmt->execute()) {
                        $_SESSION['message'] = count($item_ids_to_delete) . " product(s) deleted successfully.";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error deleting products: " . $stmt->error;
                        $_SESSION['msg_type'] = "danger";
                    }
                }
                $stmt->close();
            }
        }
        header('Location: manage_merch.php');
        exit;
    }

    // --- 2. Single Item Add/Update ---
    if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['add_merch', 'update_merch'])) {
        $is_update = ($_POST['action_type'] == 'update_merch');
        // CORRECTED: Use 'id' instead of 'merch_id'
        $item_id = $is_update ? (int)$_POST['item_id'] : 0;

        // Collect and sanitize data - ADDED 'is_in_stock'
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float)($_POST['price'] ?? 0.00);
        $is_in_stock = (int)($_POST['is_in_stock'] ?? 1); // 1 (In Stock) or 0 (Out of Stock)
        $current_image = $_POST['current_image'] ?? '';
        $new_image_path = $current_image;

        if (empty($name) || empty($category) || $price <= 0) {
            $_SESSION['message'] = "Product Name, Category, and a valid Price are required.";
            $_SESSION['msg_type'] = "danger";
            header('Location: manage_merch.php?action=' . ($is_update ? 'edit&id=' . $item_id : 'add'));
            exit;
        }

        // Handle Image Upload (remains the same)
        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/imgs/uploads/'; 
            
            // Ensure directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $filename = basename($_FILES['image_path']['name']);
            $unique_name = 'merch_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $filename);
            $destination = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['image_path']['tmp_name'], $destination)) {
                $new_image_path = 'assets/imgs/uploads/' . $unique_name;
                
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
                header('Location: manage_merch.php?action=' . ($is_update ? 'edit&id=' . $item_id : 'add'));
                exit;
            }
        }

        if ($is_update) {
            // UPDATE Query - ADDED 'is_in_stock'
            $sql = "UPDATE merch SET name=?, category=?, price=?, image_path=?, is_in_stock=? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Binding: string, string, double, string, integer(is_in_stock), integer(id)
                $stmt->bind_param("sdsisi", $name, $category, $price, $new_image_path, $is_in_stock, $item_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Product updated successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating product: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        } else {
            // INSERT Query - ADDED 'is_in_stock'
            $sql = "INSERT INTO merch (name, category, price, image_path, is_in_stock) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Binding: string, string, double, string, integer(is_in_stock)
                $stmt->bind_param("sdsis", $name, $category, $price, $new_image_path, $is_in_stock);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "New product added successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding product: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }

        header('Location: manage_merch.php');
        exit;
    }
}


// --- Edit mode (Retrieve data for form) ---
if ($action == 'edit' && $item_id > 0) {
    // CORRECTED: Use 'id' in the WHERE clause
    $stmt = $conn->prepare("SELECT * FROM merch WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $fetched_data = $result->fetch_assoc();
        $merch_data = array_merge($merch_data, $fetched_data);
    } else {
        $_SESSION['message'] = "Product not found";
        $_SESSION['msg_type'] = "danger";
        header('Location: manage_merch.php');
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
<link rel="stylesheet" href="assets/css/dashboard.css">

</head>
<body class="bg-dark-bg text-text-light">
    <div class="admin-sidebar">
        <?php 
        $current_file = basename($_SERVER['PHP_SELF']);
        $is_active = ($current_file == 'manage_merch.php');
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
                <a href="manage_merch.php?action=add" class="inline-flex items-center px-4 py-2 bg-primary-indigo hover:bg-indigo-600 text-white font-semibold rounded-lg shadow-md mb-6 transition duration-200">
                    <i class="fas fa-plus-circle mr-2"></i> Add New Product
                </a>    

                <?php 
                // UPDATED: Select 'is_in_stock'
                $query = "SELECT id, name, category, price, image_path, is_in_stock FROM merch ORDER BY created_at DESC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected products? (This action is irreversible)')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        <div class="overflow-x-auto shadow-xl rounded-lg">
                            <table class="w-full text-left text-text-light rounded-lg">
                                <thead class="text-xs uppercase bg-dark-bg text-white border-b-2 border-dark-border">
                                    <tr>
                                        <th scope="col" class="p-4"><input type="checkbox" id="select_all_items" title="Select All" class="form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></th>
                                        <th scope="col" class="py-3 px-6">ID</th>
                                        <th scope="col" class="py-3 px-6">Image</th>
                                        <th scope="col" class="py-3 px-6">Name</th>
                                        <th scope="col" class="py-3 px-6">Category</th>
                                        <th scope="col" class="py-3 px-6">Price (₱)</th>
                                        <th scope="col" class="py-3 px-6">Stock</th>
                                        <th scope="col" class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): 
                                        // Determine stock badge (Tailwind classes)
                                        $stock_status = (int)$row['is_in_stock'] === 1 
                                            ? '<span class="bg-green-600 text-white text-xs font-medium px-2 py-0.5 rounded-full">In Stock</span>' 
                                            : '<span class="bg-red-600 text-white text-xs font-medium px-2 py-0.5 rounded-full">Out of Stock</span>';
                                    ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4"><input type="checkbox" name="selected_items[]" value="<?php echo $row['id']; ?>" class="item_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded"></td>
                                        <td class="py-4 px-6"><?php echo $row['id']; ?></td>
                                        <td class="py-4 px-6">
                                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                                class="w-12 h-12 object-cover border border-dark-border rounded-lg"
                                                onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'">
                                        </td>
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="py-4 px-6 text-sm italic capitalize"><?php echo htmlspecialchars($row['category']); ?></td>
                                        <td class="py-4 px-6 font-semibold text-yellow-300">₱<?php echo number_format($row['price'], 2); ?></td>
                                        <td class="py-4 px-6"><?php echo $stock_status; ?></td>
                                        <td class="py-4 px-6 flex space-x-2">
                                            <a href="manage_merch.php?action=edit&id=<?php echo $row['id']; ?>" class="p-2 text-dark-bg bg-yellow-400 hover:bg-yellow-500 rounded-lg" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_merch.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')" class="p-2 text-white bg-red-600 hover:bg-red-700 rounded-lg" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="mt-6 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md disabled:opacity-50 transition duration-200" id="delete_selected_btn" disabled>
                            <i class="fas fa-trash-alt mr-2"></i> Delete Selected Products
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-4 bg-blue-500 text-white rounded-lg">No merch found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?></h2>
                <p class="text-gray-400 mb-6"><a href="manage_merch.php?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">← Back to Merchandise List</a></p>
                <hr class="border-dark-border mb-6">
                
                <form action="manage_merch.php" method="POST" enctype="multipart/form-data" class="bg-dark-card p-8 rounded-xl shadow-2xl max-w-2xl">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_merch' : 'add_merch'; ?>">

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-text-light mb-1">Product Name</label>
                        <input type="text" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="name" name="name" value="<?php echo htmlspecialchars($merch_data['name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="category" class="block text-sm font-medium text-text-light mb-1">Category</label>
                        <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="category" name="category" required>
                            <option value="" disabled <?php echo empty($merch_data['category']) ? 'selected' : ''; ?>>Select a Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo (isset($merch_data['category']) && $merch_data['category'] == $cat) ? 'selected' : ''; ?>>
                                    <?php echo ucwords($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="mb-4">
                            <label for="price" class="block text-sm font-medium text-text-light mb-1">Price (₱)</label>
                            <input type="number" step="0.01" min="0" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="price" name="price" value="<?php echo htmlspecialchars($merch_data['price'] ?? '0.00'); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="is_in_stock" class="block text-sm font-medium text-text-light mb-1">Stock Status</label>
                            <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="is_in_stock" name="is_in_stock" required>
                                <option value="1" class="text-green-500" <?php echo (int)($merch_data['is_in_stock'] ?? 1) === 1 ? 'selected' : ''; ?>>In Stock</option>
                                <option value="0" class="text-red-500" <?php echo (int)($merch_data['is_in_stock'] ?? 1) === 0 ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="image_path" class="block text-sm font-medium text-text-light mb-1">Product Image Upload</label>
                        <input type="file" class="block w-full text-sm text-text-light file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 cursor-pointer" id="image_path" name="image_path" accept="image/*" <?php echo ($action == 'add' && empty($merch_data['image_path'])) ? 'required' : ''; ?>>
                        
                        <?php if (!empty($merch_data['image_path'])): ?>
                            <div class="mt-4">
                                <span class="text-sm font-medium text-text-light">Current Image:</span> <br>
                                <img src="../<?php echo htmlspecialchars($merch_data['image_path']); ?>" alt="Current Product Image" class="w-24 h-24 object-contain mt-2 border-4 border-dark-border bg-white rounded-lg">
                            </div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($merch_data['image_path']); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="pt-6 border-t border-dark-border flex space-x-3">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                            <i class="fas fa-save mr-2"></i> Save Product
                        </button>
                        <a href="manage_merch.php" class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-200">Cancel</a>
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