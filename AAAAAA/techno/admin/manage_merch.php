<?php 
// technowatch/admin/manage_merch.php - Merchandise CRUD Management View (Final Version)

session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Corrected path to use your db_connect.php
include 'includes/db_connect.php'; 

// --- Helper Functions ---

/**
 * Helper function for call_user_func_array with bind_param (for bulk operations)
 * Ensures array elements are passed by reference, required by mysqli_stmt::bind_param in PHP versions < 8.0.
 */
function array_by_ref(&$arr) {
    $refs = array();
    foreach ($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs; 
}

/**
 * Custom ID Re-indexing Logic
 * Manually re-assigns merch_id values sequentially (1, 2, 3...) after deletions 
 * and resets the ID counter if the table is empty.
 * @param mysqli $conn The database connection object.
 * @return bool True on success, False on failure.
 */
function reindex_merch_ids($conn) {
    // 1. Fetch all items ordered by their current ID (maintaining user order preference).
    $sql_select = "SELECT merch_id, name, description, category, price, stock, image_url FROM merch ORDER BY merch_id ASC";
    $result = $conn->query($sql_select);

    if (!$result) {
        return false; // Error selecting data
    }

    $all_merch_data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();

    // 2. Clear the entire table to re-insert with new IDs
    if (!$conn->query("TRUNCATE TABLE merch")) {
        // Fallback if TRUNCATE is not allowed: DELETE FROM merch;
        if (!$conn->query("DELETE FROM merch")) {
             return false;
        }
    }

    // 3. Re-insert items with new sequential IDs starting from 1
    if (!empty($all_merch_data)) {
        $new_id = 1;
        // INSERT statement for 7 columns (merch_id, name, description, category, price, stock, image_url)
        $sql_insert = "INSERT INTO merch (merch_id, name, description, category, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);

        if (!$stmt_insert) {
            return false; // Error preparing statement
        }
        
        // Define types for binding (7 total): i (merch_id), s (name), s (description), s (category), d (price), i (stock), s (image_url)
        // Note: The structure of the $row data is slightly different here (category is fourth element)
        // Let's rely on the explicit bind_param call structure below.
        $types = "isssdis"; // i(id), s(name), s(desc), s(cat), d(price), i(stock), s(url)

        foreach ($all_merch_data as $row) {
            
            // Prepare parameters by matching the INSERT column order (id, name, desc, cat, price, stock, url)
            $params = [
                $types, 
                $new_id, 
                $row['name'], 
                $row['description'], 
                $row['category'], 
                $row['price'], 
                $row['stock'], 
                $row['image_url']
            ];
            
            // Use array_by_ref for dynamic binding
            call_user_func_array([$stmt_insert, 'bind_param'], array_by_ref($params));

            if (!$stmt_insert->execute()) {
                error_log("Failed to re-insert merch item with new ID $new_id: " . $stmt_insert->error);
                $stmt_insert->close();
                return false; 
            }
            $new_id++;
        }
        $stmt_insert->close();
    }
    
    return true;
}


/**
 * Get the next available ID manually (since we removed AUTO_INCREMENT)
 * @param mysqli $conn The database connection object.
 * @return int The next ID (1 if empty, max ID + 1 otherwise).
 */
function get_next_merch_id($conn) {
    // Get the maximum existing ID
    $result = $conn->query("SELECT MAX(merch_id) AS max_id FROM merch");
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['max_id'] + 1;
    }
    return 1; // Default to 1 if table is empty or query fails
}

// --- Configuration & Initialization ---
$page_title = 'Manage Merchandise';
$upload_dir = '../assets/merch/'; 
$action = $_GET['action'] ?? 'list';
$item_id = isset($_GET['merch_id']) ? (int)$_GET['merch_id'] : 0; 
$form_title = ($action == 'edit' ? 'Edit Merchandise Item' : 'Add New Item');

$merch_categories = [
    'T-Shirts',
    'Lanyards',
    'Pins',
    'Others',
];

// Form pre-fill defaults
$merch_data = [
    'merch_id' => 0,
    'name' => '',
    'description' => '',
    'price' => 0.00,
    'stock' => 0,
    'image_url' => '',
    'category' => 'T-Shirts',
];

// --- DELETE Single ---
if ($action == 'delete' && $item_id > 0) {
    
    $image_to_delete = '';
    
    // 1. Select image path before deleting the record
    $stmt_select = $conn->prepare("SELECT image_url FROM merch WHERE merch_id = ?");
    $stmt_select->bind_param("i", $item_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($row = $result_select->fetch_assoc()) $image_to_delete = $row['image_url'];
    $stmt_select->close();

    // 2. Delete the record
    $stmt = $conn->prepare("DELETE FROM merch WHERE merch_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            
            // 3. Delete the image file 
            if (!empty($image_to_delete)) {
                $file_path = '../' . $image_to_delete; 
                if (file_exists($file_path)) @unlink($file_path); // @ suppresses errors if unlinking fails
            }
            
            // Re-index IDs after successful deletion
            if (reindex_merch_ids($conn)) {
                $_SESSION['message'] = "Merchandise Item (ID: $item_id) deleted and IDs re-indexed successfully.";
                $_SESSION['msg_type'] = "success";
            } else {
                 $_SESSION['message'] = "Merchandise Item deleted, but there was an **ERROR RE-INDEXING** the IDs. Check database integrity.";
                 $_SESSION['msg_type'] = "warning";
            }
            
        } else {
            $_SESSION['message'] = "Error deleting item: " . $stmt->error;
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

// --- Reset ID Action Handler ---
if ($action == 'reset_ids') {
    if (reindex_merch_ids($conn)) {
        $_SESSION['message'] = "All Merchandise Item IDs have been manually reset and re-indexed starting from 1.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "ERROR: Failed to reset and re-index Merchandise Item IDs.";
        $_SESSION['msg_type'] = "danger";
    }
    header('Location: manage_merch.php');
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
            
            $image_paths_to_delete = [];

            // 1. Select all image paths
            $stmt_select = $conn->prepare("SELECT image_url FROM merch WHERE merch_id IN ($placeholders)");
            
            $bind_params_select = array_merge([$types], $item_ids_to_delete);
            call_user_func_array([$stmt_select, 'bind_param'], array_by_ref($bind_params_select));
            
            $stmt_select->execute();
            $result_select = $stmt_select->get_result();
            while ($row = $result_select->fetch_assoc()) {
                if (!empty($row['image_url'])) {
                    $image_paths_to_delete[] = $row['image_url'];
                }
            }
            $stmt_select->close();

            // 2. Delete the records from the database
            $stmt_delete = $conn->prepare("DELETE FROM merch WHERE merch_id IN ($placeholders)");
            
            if ($stmt_delete) {
                $bind_params_delete = array_merge([$types], $item_ids_to_delete);
                call_user_func_array([$stmt_delete, 'bind_param'], array_by_ref($bind_params_delete));
                
                if ($stmt_delete->execute()) {
                    // 3. Delete the physical image files
                    foreach ($image_paths_to_delete as $image_url) {
                        $file_path = '../' . $image_url; 
                        if (file_exists($file_path)) @unlink($file_path);
                    }
                    
                    // Re-index IDs after successful bulk deletion
                    if (reindex_merch_ids($conn)) {
                        $_SESSION['message'] = count($item_ids_to_delete) . " item(s) and their images deleted and IDs re-indexed successfully.";
                        $_SESSION['msg_type'] = "success";
                    } else {
                         $_SESSION['message'] = count($item_ids_to_delete) . " item(s) deleted, but there was an **ERROR RE-INDEXING** the IDs. Check database integrity.";
                         $_SESSION['msg_type'] = "warning";
                    }
                } else {
                    $_SESSION['message'] = "Error deleting items from database: " . $stmt_delete->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt_delete->close();
            } else {
                $_SESSION['message'] = "Database error preparing delete statement for bulk action.";
                $_SESSION['msg_type'] = "danger";
            }
        }
        header('Location: manage_merch.php');
        exit;
    }

    // --- Add/Update Single Merchandise Item ---
    if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['add_merch', 'update_merch'])) {
        $is_update = ($_POST['action_type'] === 'update_merch');
        $item_id = $is_update ? (int)($_POST['item_id'] ?? 0) : 0;

        // Collect and sanitize data
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0.00);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? 'Others'); 
        if (!in_array($category, $merch_categories)) {
            $category = 'Others';
        }

        $current_image = $_POST['current_image'] ?? '';
        $new_image_url = $current_image;

        if (empty($name) || $price <= 0) {
            $_SESSION['message'] = "Name and Price are required and must be valid.";
            $_SESSION['msg_type'] = "danger";
            header('Location: manage_merch.php?action=' . ($is_update ? 'edit&merch_id=' . $item_id : 'add'));
            exit;
        }

        // Image upload logic
        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
            
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $_SESSION['message'] = "Error: Cannot create upload directory. Check permissions.";
                    $_SESSION['msg_type'] = "danger";
                    header('Location: manage_merch.php?action=' . ($is_update ? 'edit&merch_id=' . $item_id : 'add'));
                    exit;
                }
            }
            
            $filename = basename($_FILES['image_url']['name']);
            $unique_name = 'merch_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $filename);
            $destination = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['image_url']['tmp_name'], $destination)) {
                $new_image_url = 'assets/merch/' . $unique_name; 
                
                // If updating, delete the old image file
                if ($is_update && !empty($current_image) && $current_image !== $new_image_url) {
                    $old_file = '../' . $current_image;
                    if (file_exists($old_file)) @unlink($old_file);
                }
            } else {
                $_SESSION['message'] = "Error uploading image. Check permissions.";
                $_SESSION['msg_type'] = "danger";
                header('Location: manage_merch.php?action=' . ($is_update ? 'edit&merch_id=' . $item_id : 'add'));
                exit;
            }
        }

        if ($is_update) {
            // UPDATE: 6 parameters (name, description, price, stock, image_url, category) + WHERE clause (merch_id)
            $sql = "UPDATE merch SET name=?, description=?, price=?, stock=?, image_url=?, category=? WHERE merch_id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Types: s (name), s (description), d (price), i (stock), s (image_url), s (category), i (merch_id)
                $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $new_image_url, $category, $item_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Merchandise updated successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating merch: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        } else {
            // INSERT: 7 parameters (merch_id, name, description, price, stock, image_url, category)
            $new_merch_id = get_next_merch_id($conn);
            
            $sql = "INSERT INTO merch (merch_id, name, description, price, stock, image_url, category) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Types: i (merch_id), s (name), s (description), d (price), i (stock), s (image_url), s (category)
                $stmt->bind_param("issdiss", $new_merch_id, $name, $description, $price, $stock, $new_image_url, $category);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "New merchandise (ID: $new_merch_id) added successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding merch: " . $stmt->error;
                    $_SESSION['msg_type'] = "danger";
                }
                $stmt->close();
            }
        }

        header('Location: manage_merch.php');
        exit;
    }
}

// --- Edit Mode: Prefill Form ---
if ($action === 'edit' && $item_id > 0) {
    // SELECT query with only 7 columns
    $stmt = $conn->prepare("SELECT merch_id, name, description, price, stock, image_url, category FROM merch WHERE merch_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $merch_data = array_merge($merch_data, $result->fetch_assoc());
    } else {
        $_SESSION['message'] = "Merchandise item not found";
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
<link rel="icon" type="image/png" href="../assets/imgs/logo_white.png">

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
                    if ($_SESSION['msg_type'] == 'warning') {
                        $alert_color = 'bg-yellow-500';
                        $alert_icon = 'fa-exclamation-triangle';
                    } else {
                        $alert_icon = $_SESSION['msg_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
                    }
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
                
                <div class="flex flex-wrap items-center gap-4 mb-6">
                    <a href="manage_merch.php?action=add" class="inline-flex items-center px-4 py-2 bg-primary-indigo hover:bg-indigo-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Merch
                    </a>
                    
                    <button id="resetIdButton" onclick="confirmResetIds()" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-undo-alt mr-2"></i> Reset Merch IDs
                    </button>
                    </div>

                    <hr class="border-dark-border mb-6">

                <?php 
                // List view SELECT query (7 columns)
                $query = "SELECT merch_id, name, price, stock, image_url, category FROM merch ORDER BY merch_id ASC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0): ?>

                    <form method="POST" onsubmit="return confirm('WARNING: Are you sure you want to delete the selected merchandise items? This action will also delete associated images AND re-index all remaining item IDs (e.g., ID 3 will become 2 if ID 2 is deleted).')">
                        <input type="hidden" name="bulk_action" value="delete_selected">

                        <div class="overflow-x-auto shadow-xl rounded-lg">
                            <table class="w-full text-left text-text-light rounded-lg">
                                <thead class="text-xs uppercase bg-dark-bg text-white border-b-2 border-dark-border">
                                    <tr>
                                        <th class="p-4">
                                            <input type="checkbox" id="select_all_items" class="form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded">
                                        </th>
                                        <th class="py-3 px-6">ID</th>
                                        <th class="py-3 px-6">Image</th>
                                        <th class="py-3 px-6">Name</th>
                                        <th class="py-3 px-6">Category</th>
                                        <th class="py-3 px-6">Price (₱)</th>
                                        <th class="py-3 px-6">Stock</th>
                                        <th class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-dark-border even:bg-dark-card odd:bg-dark-card/70 hover:bg-dark-input transition duration-150">
                                        <td class="p-4">
                                            <input type="checkbox" name="selected_items[]" value="<?php echo $row['merch_id']; ?>" class="item_checkbox form-checkbox text-primary-indigo h-4 w-4 bg-dark-card border-dark-border rounded">
                                        </td>
                                        <td class="py-4 px-6"><?php echo $row['merch_id']; ?></td>
                                        <td class="py-4 px-6">
                                            <?php 
                                            $img_src = !empty($row['image_url']) ? '../' . htmlspecialchars($row['image_url']) : '../assets/imgs/placeholder.png';
                                            ?>
                                            <img src="<?php echo $img_src; ?>" 
                                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                class="project-img-thumb w-12 h-12 object-cover rounded-md border border-dark-border"
                                                onerror="this.onerror=null; this.src='../assets/imgs/placeholder.png'">
                                        </td>
                                        <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="py-4 px-6 text-indigo-300 font-medium"><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                                        <td class="py-4 px-6 text-green-400">₱<?php echo number_format($row['price'], 2); ?></td>
                                        <td class="py-4 px-6 <?php echo $row['stock'] < 10 ? 'text-red-400 font-bold' : 'text-text-light'; ?>">
                                            <?php echo $row['stock']; ?>
                                        </td>
                                        <td class="py-4 px-6 flex space-x-2">
                                            <a href="manage_merch.php?action=edit&merch_id=<?php echo $row['merch_id']; ?>" class="p-2 text-dark-bg bg-yellow-400 hover:bg-yellow-500 rounded-lg" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_merch.php?action=delete&merch_id=<?php echo $row['merch_id']; ?>" onclick="return confirm('Delete <?php echo htmlspecialchars($row['name']); ?>? This will delete the image and re-index all IDs.')" class="p-2 text-white bg-red-600 hover:bg-red-700 rounded-lg" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="mt-6 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md disabled:opacity-50 transition duration-200" id="delete_selected_btn" disabled>
                            <i class="fas fa-trash-alt mr-2"></i> Delete Selected Items
                        </button>

                    </form>

                <?php else: ?>
                    <div class="bg-blue-900/40 text-blue-300 p-4 rounded-xl border border-blue-700 shadow-lg">No merchandise items found.</div>
                <?php endif; ?>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>

                <h2 class="text-3xl font-semibold text-white mb-4"><?php echo $form_title; ?></h2>
                <p class="text-gray-400 mb-6">
                    <a href="manage_merch.php?action=list" class="text-primary-indigo hover:text-indigo-400 transition duration-200">
                        ← Back to Merchandise List
                    </a>
                </p>
                <hr class="border-dark-border mb-6">

                <form action="manage_merch.php" method="POST" enctype="multipart/form-data" class="bg-dark-card p-8 rounded-xl shadow-2xl max-w-6xl">

                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="action_type" value="<?php echo $action == 'edit' ? 'update_merch' : 'add_merch'; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">

                        <div>
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-text-light mb-1">Item Name</label>
                                <input type="text" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="name" name="name" value="<?php echo htmlspecialchars($merch_data['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="category" class="block text-sm font-medium text-text-light mb-1">Category</label>
                                <select class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="category" name="category" required>
                                    <?php foreach ($merch_categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($merch_data['category'] == $cat) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="price" class="block text-sm font-medium text-text-light mb-1">Price (₱)</label>
                                <input type="number" step="0.01" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="price" name="price" value="<?php echo htmlspecialchars($merch_data['price']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="stock" class="block text-sm font-medium text-text-light mb-1">Stock Quantity</label>
                                <input type="number" class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="stock" name="stock" value="<?php echo htmlspecialchars($merch_data['stock']); ?>" required>
                            </div>
                        </div>

                        <div>
                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-text-light mb-1">Description (Full Details)</label>
                                <textarea class="w-full p-3 bg-dark-input border border-dark-border rounded-lg focus:ring-primary-indigo focus:border-primary-indigo text-text-light" id="description" name="description" rows="7"><?php echo htmlspecialchars($merch_data['description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-white mt-6 mb-3">Image Upload</h3>
                    <hr class="border-dark-border mb-6">

                    <div class="mb-6">
                        <label for="image_url" class="block w-full text-sm font-medium text-text-light mb-1">Featured Image Upload</label>
                        <input type="file" class="block w-full text-sm text-text-light file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-indigo file:text-white hover:file:bg-indigo-600 cursor-pointer" id="image_url" name="image_url" accept="image/*" <?php echo ($action == 'add' && empty($merch_data['image_url'])) ? 'required' : ''; ?>>

                        <?php if (!empty($merch_data['image_url'])): ?>
                            <div class="mt-4">
                                <span class="text-sm font-medium text-text-light">Current Image:</span><br>
                                <img src="../<?php echo htmlspecialchars($merch_data['image_url']); ?>" alt="Current Item Image" class="w-40 h-auto object-cover rounded-md mt-2 border-4 border-primary-indigo">
                            </div>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($merch_data['image_url']); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="pt-6 border-t border-dark-border flex space-x-3">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition duration-200">
                            <i class="fas fa-save mr-2"></i> Save Merchandise Item
                        </button>
                        <a href="manage_merch.php" class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-200">Cancel</a>
                    </div>

                </form>

            <?php endif; ?>
        </div>
    </div>

<script>
// --- Bulk Delete / Select All Logic ---
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select_all_items');
    const itemCheckboxes = document.querySelectorAll('.item_checkbox');
    const deleteButton = document.getElementById('delete_selected_btn');

    if (selectAllCheckbox && itemCheckboxes.length > 0) {
        
        function updateDeleteButtonState() {
            const selected = document.querySelectorAll('.item_checkbox:checked').length;
            deleteButton.disabled = selected === 0;
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

// --- JavaScript for ID Reset Button ---
function confirmResetIds() {
    if (confirm("DANGER: This action will permanently re-index ALL Merchandise IDs in the database to start from 1. Proceed?")) {
        window.location.href = 'manage_merch.php?action=reset_ids';
    }
}
</script>
</body>
</html>