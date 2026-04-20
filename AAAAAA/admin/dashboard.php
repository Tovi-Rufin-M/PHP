<?php
// technowatch/admin/dashboard.php - The main admin dashboard interface

session_start();
include 'includes/db_connect.php'; 

$counts = [];
$tables = ['events_news', 'projects', 'job_postings', 'merch', 'officials', 'officers'];

foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    if ($result && $row = $result->fetch_assoc()) {
        $counts[$table] = $row['total'];
    } else {
        $counts[$table] = 'Error';
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Technowatch Admin</title>
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
    <!-- ADMIN SIDEBAR -->
    <div class="admin-sidebar">
        <?php include 'includes/admin_sidebar.php'; ?>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content ml-[250px] w-[calc(100%-250px)] p-8 bg-bg-primary min-h-screen">
        <div class="container mx-auto max-w-7xl">
            
            <!-- START: Dashboard Content -->
            <h1 class="text-3xl font-bold text-white mb-8">Dashboard Overview</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <?php foreach ($counts as $table => $count): ?>
                <div class="bg-bg-secondary rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-l-4 border-primary-indigo">
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="text-xs font-bold text-primary-indigo uppercase tracking-wider mb-2">
                                    <?php echo ucwords(str_replace('_', ' ', $table)); ?>
                                </div>
                                <div class="text-2xl font-semibold text-white"><?php echo $count; ?></div>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                <?php
                                $icon = 'fa-file-alt';
                                if ($table == 'job_postings') $icon = 'fa-briefcase';
                                if ($table == 'officials' || $table == 'officers') $icon = 'fa-user-tie';
                                if ($table == 'events_news') $icon = 'fa-calendar-alt';
                                if ($table == 'projects') $icon = 'fa-laptop-code';
                                if ($table == 'merch') $icon = 'fa-tshirt';
                                ?>
                                <i class="fas <?php echo $icon; ?> text-3xl text-text-secondary"></i>
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