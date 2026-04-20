<?php
// technowatch/admin/dashboard.php - The main admin dashboard interface

session_start();
// Check if the user is authenticated as an admin (assuming this check exists)
// if (!isset($_SESSION['admin_logged_in'])) {
//     header('Location: login.php');
//     exit;
// }

// Includes the database connection (required for the dashboard counts)
include 'includes/db_connect.php'; 

// --- LOGIC TO FETCH COUNTS ---
$counts = [];
$tables = ['events_news', 'projects', 'job_postings', 'merch', 'officials', 'officers'];

foreach ($tables as $table) {
    // We use the $conn object provided by the included db_connect.php
    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    if ($result && $row = $result->fetch_assoc()) {
        $counts[$table] = $row['total'];
    } else {
        $counts[$table] = 'Error';
    }
}
// -----------------------------

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Technowatch Admin</title>
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <!-- Font Awesome for Icons -->
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
    
</head>
<body>

    <!-- ADMIN SIDEBAR -->
    <div class="admin-sidebar">
        <?php include 'includes/admin_sidebar.php'; ?>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        <div class="container">
            
            <!-- START: Dashboard Content -->

            <h1 class="mb-4">Dashboard Overview</h1>

            <div class="row">
                <?php foreach ($counts as $table => $count): ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters">
                                <div class="col mr-2">
                                    <div class="text-xs text-primary text-uppercase mb-1">
                                        <?php echo ucwords(str_replace('_', ' ', $table)); ?>
                                    </div>
                                    <div class="h5 mb-0 text-gray-800"><?php echo $count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <!-- Dynamic Icon based on table name -->
                                    <?php
                                    $icon = 'fa-file-alt'; // Default
                                    if ($table == 'job_postings') $icon = 'fa-briefcase';
                                    if ($table == 'officials' || $table == 'officers') $icon = 'fa-user-tie';
                                    if ($table == 'events_news') $icon = 'fa-calendar-alt';
                                    if ($table == 'projects') $icon = 'fa-laptop-code';
                                    if ($table == 'merch') $icon = 'fa-tshirt';
                                    ?>
                                    <i class="fas <?php echo $icon; ?> fa-2x text-gray-300" style="color:#adb5bd !important;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- END: Dashboard Content -->
            
        </div>
    </div>

</body>
</html>