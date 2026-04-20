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
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $type = $conn->real_escape_string($_POST['type'] ?? 'news');
    $summary = $conn->real_escape_string($_POST['summary'] ?? '');
    $content = $conn->real_escape_string($_POST['content'] ?? '');
    $event_date = $conn->real_escape_string($_POST['event_date'] ?? NULL);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $location = $conn->real_escape_string($_POST['location'] ?? NULL);
    $event_time = $conn->real_escape_string($_POST['event_time'] ?? NULL);
    
    // Default image path to existing one if editing
    $image_path = $_POST['current_image'] ?? ''; 

    // 1. Handle File Upload (Optional but critical)
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/imgs/uploads/'; // Path relative to actions.php
        // Ensure the directory exists (create it if necessary)
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        
        $file_extension = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
        $new_filename = 'item_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image_path']['tmp_name'], $target_file)) {
            // Success: Update path to be relative to the root (for the main site)
            $image_path = 'assets/imgs/uploads/' . $new_filename; 
            
            // OPTIONAL: Delete old image if editing
            if ($action_type == 'update_event' && !empty($_POST['current_image']) && file_exists('../' . $_POST['current_image'])) {
                unlink('../' . $_POST['current_image']);
            }
        } else {
            $_SESSION['message'] = "Error uploading image.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $redirect_page);
            exit;
        }
    }
    
    // 2. Prepare SQL Statements
    if ($action_type == 'add_event') {
        // CREATE: Add new columns to the query
        $stmt = $conn->prepare("INSERT INTO events_news (title, type, summary, content, event_date, location, event_time, image_path, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // Add new parameters to the bind_param (ss: location, event_time)
        $stmt->bind_param("ssssssssi", $title, $type, $summary, $content, $event_date, $location, $event_time, $image_path, $is_published);
        $message = "New item created successfully!";
    
    // For UPDATE (`update_event`): Update the `UPDATE` query and its parameters.
    } elseif ($action_type == 'update_event' && isset($_POST['item_id'])) {
        // UPDATE: Add new columns to the query
        $item_id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("UPDATE events_news SET title=?, type=?, summary=?, content=?, event_date=?, location=?, event_time=?, image_path=?, is_published=? WHERE item_id=?");
        // Add new parameters to the bind_param (ss: location, event_time)
        $stmt->bind_param("ssssssssii", $title, $type, $summary, $content, $event_date, $location, $event_time, $image_path, $is_published, $item_id);
        $message = "Item updated successfully!";
    }

    // 3. Execute and Redirect
    if (isset($stmt)) {
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
    
    // --- JOB POSTING LOGIC ---
    if ($action_type == 'add_job' || $action_type == 'update_job') {
        
        // Collect job specific data
        $title = $conn->real_escape_string($_POST['title'] ?? '');
        $company_name = $conn->real_escape_string($_POST['company_name'] ?? '');
        $job_type = $conn->real_escape_string($_POST['job_type'] ?? 'Full-time');
        $location = $conn->real_escape_string($_POST['location'] ?? '');
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $application_link = $conn->real_escape_string($_POST['application_link'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        
        $redirect_page = 'manage_jobs.php';

        if ($action_type == 'add_job') {
            // CREATE
            $stmt = $conn->prepare("INSERT INTO job_postings (title, company_name, job_type, location, description, application_link, is_published) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $title, $company_name, $job_type, $location, $description, $application_link, $is_published);
            $message = "Job posting created successfully!";
        
        } elseif ($action_type == 'update_job' && isset($_POST['job_id'])) {
            // UPDATE
            $job_id = (int)$_POST['job_id'];
            $stmt = $conn->prepare("UPDATE job_postings SET title=?, company_name=?, job_type=?, location=?, description=?, application_link=?, is_published=? WHERE job_id=?");
            $stmt->bind_param("ssssssii", $title, $company_name, $job_type, $location, $description, $application_link, $is_published, $job_id);
            $message = "Job posting updated successfully!";
        }

        if (isset($stmt)) {
            if ($stmt->execute()) {
                $_SESSION['message'] = $message;
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['message'] = "Database Error: " . $stmt->error;
                $_SESSION['msg_type'] = "danger";
            }
            $stmt->close();
            header("Location: " . $redirect_page);
            exit;
        }
    }
    header("Location: " . $redirect_page);
    exit;
}

// --- DELETE LOGIC (Existing from Step 11) ---
if (isset($_GET['delete']) && isset($_GET['id'])) {
    
    $item_type = $_GET['delete'];
    $id = (int)$_GET['id'];
    $redirect_page = 'manage_events_news.php'; 
    $table = '';
    $id_column = '';
    
    if ($item_type == 'event') {
    $table = 'events_news';
    $id_column = 'item_id';
    $redirect_page = 'manage_events_news.php';
    } 
    // ADD JOB POSTING DELETE LOGIC:
    else if ($item_type == 'job') {
        $table = 'job_postings';
        $id_column = 'job_id';
        $redirect_page = 'manage_jobs.php';
    }
    // Add other delete cases here
    
    if ($table && $id_column) {
        // Optional: Fetch image path to delete file from server
        $img_stmt = $conn->prepare("SELECT image_path FROM `$table` WHERE `$id_column` = ?");
        $img_stmt->bind_param("i", $id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        if ($img_row = $img_result->fetch_assoc() && !empty($img_row['image_path'])) {
            // Delete file from server
            if (file_exists('../' . $img_row['image_path'])) {
                unlink('../' . $img_row['image_path']);
            }
        }
        $img_stmt->close();
        
        // Delete record from database
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$id_column` = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Successfully deleted the " . ucfirst($item_type) . " item!";
            $_SESSION['msg_type'] = "success";
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

// If accessed directly without an action, redirect to dashboard
header('Location: index.php');
exit;