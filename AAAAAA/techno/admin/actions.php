<?php
// technowatch/admin/actions.php - Central handler for all CRUD operations

session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Default redirect page
$redirect_page = 'manage_events_news.php';

// --- HANDLE POST REQUESTS (CREATE/UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {

    $action_type = $_POST['action_type'];
    $stmt = null;

    // ===============================================
    // 1. EVENT/NEWS LOGIC
    // ===============================================
    if ($action_type == 'add_event' || $action_type == 'update_event') {

        // Collect & Sanitize shared data
        $title = $conn->real_escape_string($_POST['title'] ?? '');
        $type = $conn->real_escape_string($_POST['type'] ?? 'news');
        $summary = $conn->real_escape_string($_POST['summary'] ?? '');
        $content = $conn->real_escape_string($_POST['content'] ?? '');
        $event_date = empty($_POST['event_date']) ? NULL : $conn->real_escape_string($_POST['event_date']);
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $location = empty($_POST['location']) ? NULL : $conn->real_escape_string($_POST['location']);
        $event_time = empty($_POST['event_time']) ? NULL : $conn->real_escape_string($_POST['event_time']);

        // Default image path
        $image_path = $_POST['current_image'] ?? '';

        // Handle file upload
        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/imgs/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
            $new_filename = 'item_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['image_path']['tmp_name'], $target_file)) {
                $image_path = 'assets/imgs/uploads/' . $new_filename;

                // Delete old image
                if ($action_type == 'update_event' && !empty($_POST['current_image'])) {
                    $old_file_path = '../' . $_POST['current_image'];
                    if (file_exists($old_file_path)) {
                        @unlink($old_file_path);
                    }
                }
            } else {
                $_SESSION['message'] = "Error uploading image.";
                $_SESSION['msg_type'] = "danger";
                header("Location: " . $redirect_page);
                exit;
            }
        }

        if ($action_type == 'add_event') {
            $stmt = $conn->prepare("INSERT INTO events_news (title, type, summary, content, event_date, location, event_time, image_path, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssi", $title, $type, $summary, $content, $event_date, $location, $event_time, $image_path, $is_published);
            $message = "New item created successfully!";
        } elseif ($action_type == 'update_event' && isset($_POST['item_id'])) {
            $item_id = (int)$_POST['item_id'];
            $stmt = $conn->prepare("UPDATE events_news SET title=?, type=?, summary=?, content=?, event_date=?, location=?, event_time=?, image_path=?, is_published=? WHERE item_id=?");
            $stmt->bind_param("ssssssssii", $title, $type, $summary, $content, $event_date, $location, $event_time, $image_path, $is_published, $item_id);
            $message = "Item updated successfully!";
        }
    }

    // ===============================================
    // 2. JOB POSTING LOGIC
    // ===============================================
    else if ($action_type == 'add_job' || $action_type == 'update_job') {

        $redirect_page = 'manage_jobs.php';

        $title = $conn->real_escape_string($_POST['title'] ?? '');
        $company_name = $conn->real_escape_string($_POST['company_name'] ?? '');
        $job_type = $conn->real_escape_string($_POST['job_type'] ?? 'Full-time');
        $location = $conn->real_escape_string($_POST['location'] ?? '');
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $application_link = $conn->real_escape_string($_POST['application_link'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $salary_range = $conn->real_escape_string($_POST['salary_range'] ?? '');
        $company_website = $conn->real_escape_string($_POST['company_website'] ?? '');

        if ($action_type == 'add_job') {
            $stmt = $conn->prepare("INSERT INTO job_postings (title, company_name, company_website, job_type, location, description, application_link, is_published, salary_range) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $title, $company_name, $company_website, $job_type, $location, $description, $application_link, $is_published, $salary_range);
            $message = "Job posting created successfully!";
        } elseif ($action_type == 'update_job' && isset($_POST['job_id'])) {
            $job_id = (int)$_POST['job_id'];
            $stmt = $conn->prepare("UPDATE job_postings SET title=?, company_name=?, company_website=?, job_type=?, location=?, description=?, application_link=?, is_published=?, salary_range=? WHERE job_id=?");
            $stmt->bind_param("sssssssssi", $title, $company_name, $company_website, $job_type, $location, $description, $application_link, $is_published, $salary_range, $job_id);
            $message = "Job posting updated successfully!";
        }
    }

    // ===============================================
    // Execute query
    // ===============================================
    if ($stmt) {
        if ($stmt->execute()) {
            $_SESSION['message'] = $message;
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Database Error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Invalid action request.";
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: " . $redirect_page);
    exit;
}

// --- DELETE LOGIC ---
if (isset($_GET['delete']) && isset($_GET['id'])) {

    $item_type = $_GET['delete'];
    $id = (int)$_GET['id'];
    $redirect_page = 'manage_events_news.php';
    $table = '';
    $id_column = '';
    $image_path_to_delete = '';

    if ($item_type == 'event' || $item_type == 'news') {
        $table = 'events_news';
        $id_column = 'item_id';
        $redirect_page = 'manage_events_news.php';
    } elseif ($item_type == 'job') {
        $table = 'job_postings';
        $id_column = 'job_id';
        $redirect_page = 'manage_jobs.php';
    }

    if ($table && $id_column) {

        if ($table == 'events_news') {
            $img_stmt = $conn->prepare("SELECT image_path FROM `$table` WHERE `$id_column` = ?");
            $img_stmt->bind_param("i", $id);
            $img_stmt->execute();
            $img_result = $img_stmt->get_result();
            if ($img_row = $img_result->fetch_assoc()) {
                $image_path_to_delete = $img_row['image_path'];
            }
            $img_stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$id_column` = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $label = ucfirst($item_type);
            $_SESSION['message'] = "Successfully deleted the " . ($label == "Job" ? "job posting" : $label . " item") . "!";
            $_SESSION['msg_type'] = "success";

            if (!empty($image_path_to_delete)) {
                $full_file_path = __DIR__ . '/../' . $image_path_to_delete;

                if (file_exists($full_file_path) && is_file($full_file_path)) {
                    @unlink($full_file_path);
                }
            }
        } else {
            $_SESSION['message'] = "Error deleting " . ucfirst($item_type) . ": " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }

        $stmt->close();
    } else {
        $_SESSION['message'] = "Invalid item type specified for deletion.";
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: " . $redirect_page);
    exit;
}

// --- RESET AUTO_INCREMENT FOR EVENTS ---
if (isset($_GET['reset_id']) && $_GET['reset_id'] == 'events') {

    $table = 'events_news';
    $redirect_page = 'manage_events_news.php';
    $message = "Auto-Increment ID for Events/News reset to 1.";
    $success = false;

    $count_stmt = $conn->query("SELECT COUNT(*) AS total FROM $table");
    $total_rows = $count_stmt->fetch_assoc()['total'];

    if ($total_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` AUTO_INCREMENT = 1")) {
            $success = true;
        } else {
            $message = "Database Error: Failed to reset ID. " . $conn->error;
        }
    } else {
        $message = "Reset failed: Table is not empty ($total_rows rows). Delete all records first.";
    }

    $_SESSION['message'] = $message;
    $_SESSION['msg_type'] = $success ? "success" : "danger";

    header("Location: " . $redirect_page);
    exit;
}

// --- RESET AUTO_INCREMENT FOR JOBS ---
if (isset($_GET['reset_id']) && $_GET['reset_id'] == 'jobs') {

    $table = 'job_postings';
    $redirect_page = 'manage_jobs.php';
    $message = "Auto-Increment ID for Job Postings reset to 1.";
    $success = false;

    $count_stmt = $conn->query("SELECT COUNT(*) AS total FROM $table");
    $total_rows = $count_stmt->fetch_assoc()['total'];

    if ($total_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` AUTO_INCREMENT = 1")) {
            $success = true;
        } else {
            $message = "Database Error: Failed to reset ID. " . $conn->error;
        }
    } else {
        $message = "Reset failed: Table is not empty ($total_rows rows). Delete all records first.";
    }

    $_SESSION['message'] = $message;
    $_SESSION['msg_type'] = $success ? "success" : "danger";

    header("Location: " . $redirect_page);
    exit;
}

// Default redirect
header('Location: index.php');
exit;
