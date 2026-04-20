<?php
// manage_officials.php | Technowatch Club Admin Panel
header('Content-Type: text/html; charset=utf-8');
session_start();

// --- Security Check (Example - adjust as needed) ---
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
// header('Location: login.php'); // Redirect unauthorized users
// exit;
// }

// --- Database Connection ---
include 'includes/db_connect.php';

$message = '';
$error = '';

// --- CRUD Operations ---

// 1. CREATE / UPDATE Official (PHP logic remains the same)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_official'])) {
    // Basic sanitization and variable assignment
    $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name']);
    $role = trim($_POST['role']);
    $category = strtoupper(trim($_POST['category']));
    $full_title = trim($_POST['full_title']);
    $motto = trim($_POST['motto']);
    $bio_content = trim($_POST['bio_content']);
    $email = trim($_POST['email']);
    $linkedin = trim($_POST['linkedin']);
    $github = trim($_POST['github']);
    $twitter = trim($_POST['twitter']);
    $sort_order = is_numeric($_POST['sort_order']) ? (int)$_POST['sort_order'] : 99;
    $image_path = isset($_POST['existing_image']) ? trim($_POST['existing_image']) : '';

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../assets/images/officials/"; // Path relative to this script's location (assuming it's in an admin directory)
        $image_file_name = uniqid('official_') . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            if ($_FILES["image"]["size"] > 5000000) {
                $error = "Sorry, your file is too large (max 5MB).";
            } elseif ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                $error = "Sorry, only JPG, JPEG, & PNG files are allowed.";
            } else {
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Store path relative to the website root for display on the frontend
                    $image_path = "assets/images/officials/" . $image_file_name;
                } else {
                    $error = "Sorry, there was an error uploading your file.";
                }
            }
        } else {
            $error = "File is not an image.";
        }
    }

    if (empty($error)) {
        if ($id > 0) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE officials SET name=?, role=?, category=?, full_title=?, motto=?, bio_content=?, email=?, linkedin=?, github=?, twitter=?, sort_order=?, image_path=? WHERE id=?");
            $stmt->bind_param("ssssssssssisi", $name, $role, $category, $full_title, $motto, $bio_content, $email, $linkedin, $github, $twitter, $sort_order, $image_path, $id);
            if ($stmt->execute()) {
                $message = "Official *$name* updated successfully!";
            } else {
                $error = "Error updating official: " . $conn->error;
            }
            $stmt->close();
        } else {
            // CREATE
            $stmt = $conn->prepare("INSERT INTO officials (name, role, category, full_title, motto, bio_content, email, linkedin, github, twitter, sort_order, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssis", $name, $role, $category, $full_title, $motto, $bio_content, $email, $linkedin, $github, $twitter, $sort_order, $image_path);
            if ($stmt->execute()) {
                $message = "New official *$name* added successfully!";
            } else {
                $error = "Error adding official: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// 2. DELETE Official
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    $stmt = $conn->prepare("SELECT image_path FROM officials WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $official_data = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM officials WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "Official deleted successfully!";
        // Delete image file (adjust path relative to this script)
        if ($official_data && !empty($official_data['image_path']) && file_exists("../" . $official_data['image_path'])) {
            unlink("../" . $official_data['image_path']);
        }
    } else {
        $error = "Error deleting official: " . $conn->error;
    }
    $stmt->close();
}

// 3. READ Officials (Fetch all for display)
$query = "SELECT * FROM officials ORDER BY FIELD(category, 'HEAD', 'FACULTY', 'SECTION MAYORS'), sort_order ASC, name ASC";
$result = $conn->query($query);
$officials = [];
while ($row = $result->fetch_assoc()) {
    $officials[] = $row;
}

// 4. READ Single Official (for editing)
$official_to_edit = null;
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM officials WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $official_to_edit = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Officials | Technowatch Admin</title>
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <style>
        /* Custom styles for the dark theme */
        .img-preview {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            margin-right: 10px;
            border: 2px solid #334155; /* dark-input/border */
        }
        /* To make sure the image preview is small in the form */
        form .img-preview {
            width: 30px;
            height: 30px;
            margin-left: 8px;
        }
    </style>
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
                    'text-muted': '#94a3b8', // Slate 400
                },
                spacing: {
                    '250': '250px', // Custom width for sidebar offset
                }
            }
        }
    }
</script>
</head>
<body class="font-sans"> <div class="flex min-h-screen">
    <div class="admin-sidebar">
        <?php
        $current_file = basename($_SERVER['PHP_SELF']);
        // Note: Use 'manage_officials.php' for this specific file's active state
        $is_active = ($current_file == 'manage_officials.php');
        // You would typically pass $is_active to the sidebar include
        // to highlight the current page link. Assuming 'includes/admin_sidebar.php'
        // uses $current_file or a similar mechanism.
        include 'includes/admin_sidebar.php';
        ?>
    </div>

        <div class="flex-1 p-6 lg:p-10 ml-250">
        <div class="max-w-7xl mx-auto bg-dark-card p-8 rounded-lg shadow-xl border border-dark-border">
            <h1 class="text-3xl font-bold text-text-light mb-6 flex items-center">
                <i class="fas fa-user-tie mr-3 text-primary-indigo"></i> Manage Club Officials
            </h1>

            <?php if ($message): ?>
                <div class="bg-green-800 border-l-4 border-green-500 text-text-light p-4 mb-4" role="alert">
                    <p class="font-bold">Success</p>
                    <p><?= $message; ?></p>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-800 border-l-4 border-red-500 text-text-light p-4 mb-4" role="alert">
                    <p class="font-bold">Error</p>
                    <p><?= $error; ?></p>
                </div>
            <?php endif; ?>

            <hr class="my-8 border-dark-border">

            <h2 class="text-2xl font-semibold text-text-light mb-4"><?= $official_to_edit ? 'Edit Official' : 'Add New Official'; ?></h2>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id" value="<?= htmlspecialchars($official_to_edit['id'] ?? ''); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="name" class="block text-sm font-medium text-text-light">Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($official_to_edit['name'] ?? ''); ?>" required class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                    </div>

                    <div class="form-group">
                        <label for="role" class="block text-sm font-medium text-text-light">Role / Position <span class="text-red-500">*</span></label>
                        <input type="text" id="role" name="role" value="<?= htmlspecialchars($official_to_edit['role'] ?? ''); ?>" required class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                    </div>
                </div>

                <div class="form-group">
                    <label for="category" class="block text-sm font-medium text-text-light">Category <span class="text-red-500">*</span></label>
                    <select id="category" name="category" required class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                        <?php $current_cat = $official_to_edit['category'] ?? ''; ?>
                        <option value="HEAD" <?= $current_cat === 'HEAD' ? 'selected' : ''; ?>>HEAD</option>
                        <option value="FACULTY" <?= $current_cat === 'FACULTY' ? 'selected' : ''; ?>>FACULTY</option>
                        <option value="SECTION MAYORS" <?= $current_cat === 'SECTION MAYORS' ? 'selected' : ''; ?>>SECTION MAYORS</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="full_title" class="block text-sm font-medium text-text-light">Full Title (e.g., Department, Degree, etc.)</label>
                    <textarea id="full_title" name="full_title" class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light"><?= htmlspecialchars($official_to_edit['full_title'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="bio_content" class="block text-sm font-medium text-text-light">Biography</label>
                    <textarea id="bio_content" name="bio_content" rows="4" class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light"><?= htmlspecialchars($official_to_edit['bio_content'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="email" class="block text-sm font-medium text-text-light">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($official_to_edit['email'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                    </div>
                    <div class="form-group">
                        <label for="motto" class="block text-sm font-medium text-text-light">Motto / Quote</label>
                        <input type="text" id="motto" name="motto" value="<?= htmlspecialchars($official_to_edit['motto'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group">
                        <label for="linkedin" class="block text-sm font-medium text-text-light">LinkedIn URL</label>
                        <input type="text" id="linkedin" name="linkedin" value="<?= htmlspecialchars($official_to_edit['linkedin'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                    </div>
                    <div class="form-group">
                        <label for="github" class="block text-sm font-medium text-text-light">GitHub URL</label>
                        <input type="text" id="github" name="github" value="<?= htmlspecialchars($official_to_edit['github'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                    </div>
                    <div class="form-group">
                        <label for="twitter" class="block text-sm font-medium text-text-light">Twitter URL</label>
                        <input type="text" id="twitter" name="twitter" value="<?= htmlspecialchars($official_to_edit['twitter'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="sort_order" class="block text-sm font-medium text-text-light">Sort Order (Lower number appears first, default 99)</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?= htmlspecialchars($official_to_edit['sort_order'] ?? '99'); ?>" min="0" class="mt-1 block w-full rounded-md border-dark-border shadow-sm focus:border-primary-indigo focus:ring-primary-indigo p-2 border bg-dark-input text-text-light">
                    </div>

                    <div class="form-group">
                        <label for="image" class="block text-sm font-medium text-text-light">Profile Image (Max 5MB, JPG/PNG)</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-indigo file:text-white hover:file:bg-indigo-700">
                        <?php if (!empty($official_to_edit['image_path'])): ?>
                            <p class="mt-2 text-sm text-text-muted flex items-center">Current Image:
                                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($official_to_edit['image_path']); ?>">
                                <span class="img-preview" style="background-image:url('../<?= htmlspecialchars($official_to_edit['image_path']); ?>')"></span>
                                <span class="truncate max-w-[200px]"><?= htmlspecialchars(basename($official_to_edit['image_path'])); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>


                <div class="flex space-x-3 pt-4">
                    <button type="submit" name="submit_official" class="flex items-center justify-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary-indigo hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-indigo">
                        <i class="fas fa-save mr-2"></i> <?= $official_to_edit ? 'Update Official' : 'Add New Official'; ?>
                    </button>
                    <?php if ($official_to_edit): ?>
                        <a href="manage_officials.php" class="flex items-center justify-center px-4 py-2 border border-dark-border text-base font-medium rounded-md shadow-sm text-text-light bg-dark-input hover:bg-dark-border">
                            Cancel Edit
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <hr class="my-8 border-dark-border">

            <h2 class="text-2xl font-semibold text-text-light mb-4 flex items-center">
                <i class="fas fa-list-alt mr-2 text-primary-indigo"></i> Current Officials
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-dark-border shadow-md rounded-lg">
                    <thead class="bg-dark-input">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Image</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Sort</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-dark-card divide-y divide-dark-border">
                        <?php if (empty($officials)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center text-sm text-text-muted">No officials found.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($officials as $official): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-light"><?= htmlspecialchars($official['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($official['image_path'])): ?>
                                        <span class="img-preview" style="background-image:url('../<?= htmlspecialchars($official['image_path']); ?>')"></span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-muted"><?= htmlspecialchars($official['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-muted"><?= htmlspecialchars($official['role']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-indigo"><?= htmlspecialchars($official['category']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-muted"><?= htmlspecialchars($official['sort_order']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium action-links">
                                    <a href="manage_officials.php?edit_id=<?= $official['id']; ?>" title="Edit" class="text-primary-indigo hover:text-indigo-400">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete <?= addslashes($official['name']); ?>?');">
                                        <input type="hidden" name="delete_id" value="<?= $official['id']; ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-300 ml-2 p-1 rounded-md bg-dark-input hover:bg-dark-border">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</body>
</html>