<?php
// technowatch/job_details.php - Displays the full content of a Job Posting

session_start();
include 'admin/includes/db_connect.php'; // Use the established connection path

// 1. Get the Job ID from the URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id === 0) {
    // Redirect if no ID is provided
    header('Location: jobPosting.php');
    exit;
}

// 2. Fetch the specific job from the database
$stmt = $conn->prepare("SELECT * FROM job_postings WHERE job_id = ? AND is_published = 1");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Job not found or not published
    $title = "Job Not Found";
    $content_html = "<p>The requested job posting could not be found or is no longer available.</p>";
    $job_data = null;
} else {
    $job_data = $result->fetch_assoc();
    $title = $job_data['title'];
    $content_html = nl2br(htmlspecialchars($job_data['description'])); // Use nl2br for formatting
}

$stmt->close();
$conn->close();

// --- Safe access for display ---
$salary_display = htmlspecialchars($job_data['salary_range'] ?? 'Negotiable');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | Job Details</title>
    <!-- Include necessary CSS files -->
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
    <style>
        /* Basic styling for the detail view */
        .details-container { max-width: 900px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .details-header { border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .details-meta span { display: inline-block; margin-right: 20px; color: #555; font-size: 0.95em; }
        .details-content p { line-height: 1.7; margin-bottom: 1.5em; }
        .apply-btn-lg { display: inline-block; padding: 10px 25px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; transition: background-color 0.3s; }
        .apply-btn-lg:hover { background-color: #0056b3; }
        .back-link { display: block; margin-top: 30px; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="details-section">
        <div class="details-container">
            
            <?php if ($job_data): ?>
                <div class="details-header">
                    <h1><?php echo htmlspecialchars($title); ?></h1>
                    <div class="company-name mb-3 text-muted fs-5"><?php echo htmlspecialchars($job_data['company_name']); ?></div>

                    <div class="details-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job_data['location']); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($job_data['job_type']); ?></span>
                        <!-- Display Salary Range -->
                        <span><i class="fas fa-money-bill-wave"></i> <?php echo $salary_display; ?></span>
                        <!-- Placeholder for salary/date -->
                        <span><i class="fas fa-calendar-alt"></i> Posted: <?php echo date('M j, Y', strtotime($job_data['created_at'])); ?></span>
                    </div>
                </div>

                <h2>Job Description</h2>
                <div class="details-content">
                    <?php echo $content_html; ?>
                </div>
                
                <a href="<?php echo htmlspecialchars($job_data['application_link'] ?: '#'); ?>" target="_blank" class="apply-btn-lg">
                    Apply Now <i class="fas fa-external-link-alt"></i>
                </a>
                
            <?php else: ?>
                <div class="alert alert-danger">
                    <h2><?php echo htmlspecialchars($title); ?></h2>
                    <?php echo $content_html; ?>
                </div>
            <?php endif; ?>
            
            <a href="jobposting.php" class="back-link">← Back to Job Postings</a>
        </div>
    </section>

    <?php include 'footer.php'; ?>

</body>
</html>