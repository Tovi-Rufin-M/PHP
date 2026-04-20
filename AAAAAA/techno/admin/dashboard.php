<?php
// technowatch/admin/dashboard.php - The main admin dashboard interface

session_start();
// Security Check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connect.php';

$tables = ['events_news', 'projects', 'job_postings', 'merch', 'officials_staff', 'officers_club'];
$counts = [];
$sqlParts = [];

// 1. Fetch TOTAL Counts
foreach ($tables as $table) {
    $sqlParts[] = "SELECT '$table' AS table_name, COUNT(*) AS total FROM `$table`";
}

$query = implode(" UNION ALL ", $sqlParts);
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counts[$row['table_name']] = $row['total'];
    }
} else {
    error_log("DB Error fetching counts: " . $conn->error);
    foreach ($tables as $table) {
        $counts[$table] = 'N/A';
    }
}

$chart_labels = array_map(fn($t) => ucwords(str_replace('_', ' ', $t)), $tables);
$chart_data = array_values($counts);

// 2. Hardcoded Recent Counts
$recent_counts = [
    'events_news' => 5, 'projects' => 2, 'job_postings' => 1,
    'merch' => 10, 'officials_staff' => 0, 'officers_club' => 0
];

$total_items = array_sum($counts);
$total_recent = array_sum($recent_counts);
$total_old = $total_items - $total_recent;

// 3. Fetch Recent Admin Activity
$activity_log = [];
$log_query = "SELECT `username`, `summary`, `timestamp` FROM `admin_activity_log` ORDER BY `timestamp` DESC LIMIT 10";
$log_result = $conn->query($log_query);

if ($log_result) {
    while ($row = $log_result->fetch_assoc()) {
        $activity_log[] = $row;
    }
} else {
    error_log("DB Error fetching activity log: " . $conn->error);
}

// Format timestamp to readable "time ago"
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day',
        'h' => 'hour', 'i' => 'minute', 's' => 'second'
    ];

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Technowatch Admin</title>
<link rel="icon" type="image/png" href="../assets/imgs/logo_white.png">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                'primary-indigo': '#6366f1', 'bg-primary': '#0f172a',
                'bg-secondary': '#1e293b', 'border-col': '#334155',
                'text-primary': '#f8fafc', 'text-secondary': '#adb5bd',
                'danger-red': '#ef4444',
            },
            fontFamily: { sans: ['Inter', 'sans-serif'] }
        }
    }
}
</script>

<link rel="stylesheet" href="assets/css/sidebar.css">
<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>

<body class="bg-bg-primary text-text-primary font-sans">
<div class="admin-sidebar"><?php include 'includes/admin_sidebar.php'; ?></div>

<div class="main-content ml-[250px] w-[calc(100%-250px)] p-8 bg-bg-primary min-h-screen">
<div class="container mx-auto max-w-7xl">

<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-white">Dashboard Overview</h1>
    <a href="add_new.php" class="flex items-center bg-primary-indigo text-white px-4 py-2 rounded-lg shadow-md hover:bg-indigo-500 transition">
        <i class="fas fa-plus mr-2"></i>Add New Content
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
<?php foreach ($counts as $table => $count): ?>
<div class="bg-bg-secondary rounded-xl shadow-xl hover:shadow-2xl hover:scale-[1.02] transition-all border-t-4 border-primary-indigo">
    <div class="p-5">
        <div class="text-xs font-bold text-primary-indigo uppercase tracking-wider mb-1">
            <?= ucwords(str_replace('_', ' ', $table)); ?>
        </div>
        <div class="flex items-center justify-between">
            <div class="text-3xl font-extrabold"><?= is_numeric($count) ? number_format($count) : $count; ?></div>
            <i class="fas <?= [
                'events_news' => 'fa-newspaper', 'projects' => 'fa-laptop-code', 'job_postings' => 'fa-briefcase',
                'merch' => 'fa-shopping-bag', 'officials_staff' => 'fa-user-tie', 'officers_club' => 'fa-users'
            ][$table] ?? 'fa-file-alt'; ?> text-2xl opacity-75"></i>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
<div class="lg:col-span-2 bg-bg-secondary p-6 rounded-xl shadow-xl border border-border-col">
    <h2 class="text-xl font-semibold mb-4">Content Distribution by Type</h2>
    <div class="h-80"><canvas id="contentBarChart"></canvas></div>
</div>

<div class="bg-bg-secondary p-6 rounded-xl shadow-xl border border-border-col">
    <h2 class="text-xl font-semibold mb-4">Recent vs Total Content</h2>
    <div class="h-80 flex items-center justify-center">
        <canvas id="ageDoughnutChart"></canvas>
    </div>
</div>
</div>

<div class="mt-8 bg-bg-secondary p-6 rounded-xl shadow-xl border border-border-col">
    <h2 class="text-xl font-semibold mb-4">Recent Admin Activity</h2>
    <ul class="space-y-3 text-text-secondary">
        <?php if (count($activity_log) > 0): ?>
            <?php foreach ($activity_log as $activity): ?>
            <li class="border-b border-border-col pb-2">
                <span class="font-semibold text-text-primary"><?= htmlspecialchars($activity['username']); ?></span>
                <?= htmlspecialchars($activity['summary']); ?>
                <span class="float-right text-sm text-text-secondary">
                    <?= time_elapsed_string($activity['timestamp']); ?>
                </span>
            </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="text-center">No recent admin activity found.</li>
        <?php endif; ?>
    </ul>
</div>

</div></div>

<script>
const chartLabels = <?= json_encode($chart_labels); ?>;
const chartData = <?= json_encode(array_map('intval', $chart_data)); ?>;
const totalRecent = <?= $total_recent; ?>;
const totalOld = <?= $total_old; ?>;

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { color: 'white' } } },
    scales: {
        y: { beginAtZero: true, ticks: { color: '#adb5bd' }, grid: { color: 'rgba(51,65,85,0.5)' } },
        x: { ticks: { color: '#adb5bd' }, grid: { color: 'rgba(51,65,85,0.5)' } }
    }
};

new Chart(document.getElementById('contentBarChart'), {
    type: 'bar',
    data: { labels: chartLabels, datasets: [{ label: 'Total Items', data: chartData, backgroundColor: '#6366f1' }] },
    options: chartOptions
});

new Chart(document.getElementById('ageDoughnutChart'), {
    type: 'doughnut',
    data: { labels: ['Added Last 30 Days', 'Older Content'], datasets: [{ data: [totalRecent, totalOld], backgroundColor: ['#6366f1', '#1e293b'] }] },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

</body>
</html>
