<?php
// Technowatch Club | Resources
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources | Technowatch Club</title>
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">

    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/resources.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/script.js" defer></script>
    <script src="assets/js/resources.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>
<?php 
include 'admin/includes/db_connect.php'; 

// --- 1. Fetch Events & News (One Recent News, One Recent Event) ---
$events_news_results = [];
if (isset($conn) && $conn->ping()) {
    // NOTE: Requires 'type' and 'created_at' columns in 'events_news' table

    // Fetch the single most recently created NEWS item
    $stmt_news = $conn->prepare("SELECT item_id, title, summary, type, location, event_date, event_time, image_path, created_at FROM events_news WHERE is_published = 1 AND type = 'news' ORDER BY created_at DESC, item_id DESC LIMIT 1");
    if ($stmt_news) {
        $stmt_news->execute();
        $result_news = $stmt_news->get_result();
        $latest_news = $result_news->fetch_assoc();
        $stmt_news->close();
        if ($latest_news) {
            $events_news_results[] = $latest_news;
        }
    }

    // Fetch the single most recently created EVENT item
    $stmt_event = $conn->prepare("SELECT item_id, title, summary, type, location, event_date, event_time, image_path, created_at FROM events_news WHERE is_published = 1 AND type = 'event' ORDER BY created_at DESC, item_id DESC LIMIT 1");
    if ($stmt_event) {
        $stmt_event->execute();
        $result_event = $stmt_event->get_result();
        $latest_event = $result_event->fetch_assoc();
        $stmt_event->close();
        if ($latest_event) {
            $events_news_results[] = $latest_event;
        }
    }

    // Combine and sort the results by 'created_at' DESC to ensure the absolute newest overall item is always at index 0.
    if (count($events_news_results) > 1) {
        usort($events_news_results, function($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });
    }
}

// --- 2. Fetch Job Postings ---
$jobs_results = [];
if (isset($conn) && $conn->ping()) {
    $sql_jobs = "SELECT job_id, title, company_name, location, salary_range, job_type, company_website FROM job_postings WHERE is_published = 1 ORDER BY job_id DESC LIMIT 2";
    if ($stmt_jobs = $conn->prepare($sql_jobs)) {
        $stmt_jobs->execute();
        $result_jobs = $stmt_jobs->get_result();
        while ($row = $result_jobs->fetch_assoc()) {
            $jobs_results[] = $row;
        }
        $stmt_jobs->close();
    }
}

// --- 3. Fetch Projects (Limited to 2) ---
$projects_results = [];
if (isset($conn) && $conn->ping()) {
    // FIX APPLIED: Changed LIMIT 3 to LIMIT 2 as requested.
    $sql_projects = "SELECT project_id, title, short_description, categories, image_path FROM projects ORDER BY project_id DESC LIMIT 2";
    if ($stmt_projects = $conn->prepare($sql_projects)) {
        $stmt_projects->execute();
        $result_projects = $stmt_projects->get_result();
        while ($row = $result_projects->fetch_assoc()) {
            $projects_results[] = $row;
        }
        $stmt_projects->close();
    }
}
?>

<?php include 'header.php'; ?>

<section class="resources-hero-section" style="background-image: url('assets/imgs/bg.png');">
    <div class="hero-content-overlay">
        <h1 class="hero-main-text">TECHNOWATCH <br><span class="transparent-text">RESOURCES</span></h1>
        <p class="hero-sub-text">Your central hub for projects, career opportunities, and club events.</p>
    </div>
</section>

<section class="resources-hub-section">
    <div class="hub-container">

        <div class="hub-category-block">
            <div class="block-header">
                <h2><i class="fas fa-calendar-alt"></i>Events & News</h2>
                <p>See what's happening and join our next workshop or hackathon.</p>
            </div>

            <div class="preview-grid">
                <?php if (count($events_news_results) > 0): ?>
                    <?php foreach ($events_news_results as $index => $item): ?>
                        <?php
                        $image_src = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'assets/imgs/default_event.png';
                        $date_formatted = (!empty($item['event_date']) && strtotime($item['event_date'])) ? date('F j, Y', strtotime($item['event_date'])) : 'Date TBD';
                        $time_formatted = !empty($item['event_time']) ? date('g:i A', strtotime($item['event_time'])) : '';
                        
                        // 1. NEW Badge Logic: Only the absolute newest item (index 0 after sorting) gets the "NEW" label
                        $is_new = ($index === 0); 

                        // 2. Type Label Logic: Only show the "LATEST NEWS" label if the type is 'news'
                        $show_news_label = ($item['type'] === 'news');
                        ?>
                    <div class="timeline-item">
                        <?php if ($is_new): ?>
                            <div class="featured-label">NEW</div> 
                        <?php endif; ?>
                        <div class="timeline-image">
                            <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date"><?= $date_formatted ?></div>
                            
                            <h3>
                                <?= htmlspecialchars($item['title']) ?> 
                                <?php if ($show_news_label): ?>
                                <span class="type-label type-news">LATEST NEWS</span>
                                <?php endif; ?>
                            </h3>
                            
                            <p><?= htmlspecialchars($item['summary']) ?></p>
                            <div class="event-meta">
                                <?php if (!empty($item['location'])): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['location']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($time_formatted)): ?>
                                    <span><i class="fas fa-clock"></i> <?= $time_formatted ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="eventsNews.php?id=<?= $item['item_id'] ?>" class="register-btn">View Details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center w-full" style="padding: 20px;">No recent events or news posted yet.</p>
                <?php endif; ?>
            </div>

            <div class="category-footer-button">
                <a href="eventsNews.php" class="view-all-btn">View All Events & News <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="hub-category-block">
            <div class="block-header">
                <h2><i class="fas fa-briefcase"></i>Career Opportunities</h2>
                <p>Find your next tech job or internship through our network.</p>
            </div>
            
            <?php
            // ADDED: Array of themed placeholder URLs for job listings
            $job_placeholders = [
                'https://picsum.photos/seed/career_code/100',    
                'https://picsum.photos/seed/career_meeting/100', 
                'https://picsum.photos/seed/career_chart/100',   
            ];
            ?>

            <div class="jobs-preview-grid">
                <?php if (count($jobs_results) > 0): ?>
                    <?php foreach ($jobs_results as $job):
                        // Logic to cycle through the placeholder images based on job_id for consistent 'randomness'
                        $random_index = $job['job_id'] % count($job_placeholders);
                        $random_job_image = $job_placeholders[$random_index];

                        // MODIFIED: Use the random job-themed image if company_logo_url is not available.
                        $logo_src = !empty($job['company_logo_url']) ? htmlspecialchars($job['company_logo_url']) : $random_job_image;
                    ?>
                    <div class="job-card">
                        <div class="job-header">
                            <img src="<?= $logo_src ?>" alt="<?= htmlspecialchars($job['company_name']) ?>" class="company-logo">
                            <div>
                                <h3><?= htmlspecialchars($job['title']) ?></h3>
                                <div class="company-name">
                                    <?= htmlspecialchars($job['company_name']) ?>
                                    <?php if (!empty($job['company_website'])): ?>
                                        <a href="<?= htmlspecialchars($job['company_website']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="job-details">
                            <?php if (!empty($job['location'])): ?><span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span><?php endif; ?>
                            <?php if (!empty($job['job_type'])): ?><span><i class="fas fa-clock"></i> <?= htmlspecialchars($job['job_type']) ?></span><?php endif; ?>
                            <?php if (!empty($job['salary_range'])): ?><span><i class="fas fa-money-bill-wave"></i> <?= htmlspecialchars($job['salary_range']) ?></span><?php endif; ?>
                        </div>
                        <div class="job-footer">
                            <a href="jobposting.php?id=<?= $job['job_id'] ?>" class="apply-btn">View & Apply</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center w-full" style="padding: 20px;">No career opportunities posted yet.</p>
                <?php endif; ?>
            </div>

            <div class="category-footer-button">
                <a href="jobposting.php" class="view-all-btn">View All Jobs <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="hub-category-block">
            <div class="block-header">
                <h2><i class="fas fa-cogs"></i>Our Projects</h2>
                <p>Explore innovative ideas and tech development from members.</p>
            </div>

            <div class="projects-showcase-grid">
                <?php if (count($projects_results) > 0): ?>
                    <?php foreach ($projects_results as $project):
                        $image_src = !empty($project['image_path']) ? htmlspecialchars($project['image_path']) : 'assets/imgs/default_project.jpg';
                        $category = strtolower($project['categories'] ?? '');
                        $icon_class = 'fas fa-cogs';
                        if (str_contains($category, 'ai') || str_contains($category, 'machine')) $icon_class = 'fas fa-microchip';
                        elseif (str_contains($category, 'iot')) $icon_class = 'fas fa-wifi';
                        elseif (str_contains($category, 'web') || str_contains($category, 'app')) $icon_class = 'fas fa-code';
                    ?>
                    <div class="project-card">
                        <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($project['title']) ?>" class="project-image">
                        <div class="project-info">
                            <h3><?= htmlspecialchars($project['title']) ?></h3>
                            <p><?= htmlspecialchars($project['short_description']) ?></p>
                            <div class="project-meta">
                                <?php if (!empty($project['categories'])): ?>
                                    <span><i class="<?= $icon_class ?>"></i> <?= htmlspecialchars($project['categories']) ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="projects.php?id=<?= $project['project_id'] ?>" class="view-project-btn">View Project →</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center w-full" style="padding: 20px;">No projects posted yet.</p>
                <?php endif; ?>
            </div>

            <div class="category-footer-button">
                <a href="projects.php" class="view-all-btn">View All Projects <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

    </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>