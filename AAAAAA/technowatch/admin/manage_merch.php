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
            $filename = basename($_FILES['image_path']['name']);
            $unique_name = 'merch_' . time() . '_' . $filename;
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
                // Binding: string, string, float, string, integer(is_in_stock), integer(id)
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
                // Binding: string, string, float, string, integer(is_in_stock)
                $stmt->bind_param("ssdsi", $name, $category, $price, $new_image_path, $is_in_stock);
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
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="stylesheet" href="assets/css/dashboard.css">

<link rel="stylesheet" href="assets/css/merch.css">

<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-sidebar">
        <?php 
        $current_file = basename($_SERVER['PHP_SELF']);
        $is_active = ($current_file == 'manage_merch.php');
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
                <a href="manage_merch.php?action=add" class="btn btn-primary mb-4"><i class="fas fa-plus-circle"></i> Add New Product</a>

                <?php 
                // UPDATED: Select 'is_in_stock'
                $query = "SELECT id, name, category, price, image_path, is_in_stock FROM merch ORDER BY created_at DESC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected products?')">
                        <input type="hidden" name="bulk_action" value="delete_selected">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th><input type="checkbox" id="select_all_items" title="Select All"></th>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price (₱)</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): 
                                        // Determine stock badge
                                        $stock_status = (int)$row['is_in_stock'] === 1 
                                            ? '<span class="badge bg-success">In Stock</span>' 
                                            : '<span class="badge bg-danger">Out of Stock</span>';
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_items[]" value="<?php echo $row['id']; ?>" class="item_checkbox"></td>
                                        <td><?php echo $row['id']; ?></td>
                                        <td>
                                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                                class="product-img-thumb"
                                                onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'"
                                                style="width:50px; height:50px; object-fit: cover;">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                        <td>₱<?php echo number_format($row['price'], 2); ?></td>
                                        <td><?php echo $stock_status; ?></td>
                                        <td>
                                            <a href="manage_merch.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_merch.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-danger mt-3" id="delete_selected_btn" disabled><i class="fas fa-trash-alt"></i> Delete Selected Products</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">No merch found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <h2><?php echo $form_title; ?></h2>
                <hr>
                <form action="manage_merch.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_merch' : 'add_merch'; ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($merch_data['name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="" disabled <?php echo empty($merch_data['category']) ? 'selected' : ''; ?>>Select a Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo (isset($merch_data['category']) && $merch_data['category'] == $cat) ? 'selected' : ''; ?>>
                                    <?php echo ucwords($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">Price (₱)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($merch_data['price'] ?? '0.00'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="is_in_stock" class="form-label">Stock Status</label>
                        <select class="form-select" id="is_in_stock" name="is_in_stock" required>
                            <option value="1" <?php echo (int)($merch_data['is_in_stock'] ?? 1) === 1 ? 'selected' : ''; ?>>In Stock</option>
                            <option value="0" <?php echo (int)($merch_data['is_in_stock'] ?? 1) === 0 ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image_path" class="form-label">Product Image Upload</label>
                        <input type="file" class="form-control" id="image_path" name="image_path" accept="image/*" <?php echo ($action == 'add' && empty($merch_data['image_path'])) ? 'required' : ''; ?>>
                        <?php if (!empty($merch_data['image_path'])): ?>
                            <div class="mt-2">
                                Current Image: <br>
                                <img src="../<?php echo htmlspecialchars($merch_data['image_path']); ?>" alt="Current Product Image" style="width:100px; height: auto; border: 1px solid #ccc;">
                            </div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($merch_data['image_path']); ?>">
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-success me-2"><i class="fas fa-save"></i> Save Product</button>
                    <a href="manage_merch.php" class="btn btn-secondary">Cancel</a>
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